// lib/core/sync/sync_service.dart
//
// Orquestrador central de sincronização.
//
// RESPONSABILIDADES:
//   1. Pull  → baixar dados do servidor para o SQLite (produtos, clientes, etc.)
//   2. Push  → enviar vendas offline pendentes para o servidor
//   3. Delta → usar ?since=timestamp para baixar apenas o que mudou
//
// TRIGGER AUTOMÁTICO:
//   - ConnectivityService notifica → SyncService.sincronizarAoReconectar()
//   - Chamado manualmente no SyncInitialScreen (sync completo)

import 'dart:convert';
import 'package:drift/drift.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../database/app_database.dart';
import '../network/api_client.dart';
import '../network/connectivity_service.dart';
import 'media_sync_manager.dart';

enum SyncEstado { idle, sincronizando, concluido, erro }

/// Resultado de um ciclo de sync
class SyncResultado {
  final int produtosBaixados;
  final int clientesBaixados;
  final int categoriasBaixadas;
  final int formasBaixadas;
  final int vendasEnviadas;
  final int vendasComErro;
  final List<String> erros;

  SyncResultado({
    this.produtosBaixados = 0,
    this.clientesBaixados = 0,
    this.categoriasBaixadas = 0,
    this.formasBaixadas = 0,
    this.vendasEnviadas = 0,
    this.vendasComErro = 0,
    this.erros = const [],
  });

  bool get temErros => vendasComErro > 0 || erros.isNotEmpty;
}

class SyncService extends ChangeNotifier {
  final AppDatabase _db;
  final ConnectivityService _connectivity;

  SyncEstado _estado = SyncEstado.idle;
  String _mensagem = '';
  double _progresso = 0.0; // 0.0 a 1.0
  SyncResultado? _ultimoResultado;

  static const String _prefixUltimaSync = 'ultima_sync_';

  SyncService(this._db, this._connectivity) {
    // Escuta mudanças de conectividade para auto-sync
    _connectivity.addListener(_onConectividadeAlterada);
  }

  SyncEstado get estado => _estado;
  String get mensagem => _mensagem;
  double get progresso => _progresso;
  SyncResultado? get ultimoResultado => _ultimoResultado;
  bool get sincronizando => _estado == SyncEstado.sincronizando;

  // ─── Listener automático ──────────────────────────────────────

  void _onConectividadeAlterada() {
    if (_connectivity.isOnline && _estado == SyncEstado.idle) {
      sincronizarAoReconectar();
    }
  }

  // ─── SYNC INICIAL (obrigatório no 1º uso) ─────────────────────

  /// Baixa TODOS os dados do servidor. Deve ser executado antes do primeiro uso.
  Future<SyncResultado> sincronizarInicial(String tenantId) async {
    return _executarSync(tenantId, isInicial: true);
  }

  // ─── SYNC AO RECONECTAR (automático) ──────────────────────────

  /// Delta sync + envio de vendas pendentes.
  Future<void> sincronizarAoReconectar() async {
    if (_estado == SyncEstado.sincronizando) return;

    final tenant = await _db.catalogDao.getTenantAtivo();
    if (tenant == null) return;

    debugPrint('[Sync] Auto-sync ao reconectar...');
    await _executarSync(tenant.id, isInicial: false);
  }

  // ─── SYNC FINAL (Fechamento do Dia/Turno) ──────────────────────

  /// Envia todas as vendas pendentes e atualiza estoque para fechamento.
  Future<SyncResultado> sincronizarFinal(String tenantId) async {
    return _executarSync(tenantId, isInicial: false);
  }

  // ─── SYNC COMPLETO ─────────────────────────────────────────────

  Future<SyncResultado> _executarSync(
    String tenantId, {
    required bool isInicial,
  }) async {
    _setEstado(SyncEstado.sincronizando, 'Iniciando sincronização...', 0.0);

    final erros = <String>[];
    int produtosBaixados = 0;
    int clientesBaixados = 0;
    int categoriasBaixadas = 0;
    int formasBaixadas = 0;
    int vendasEnviadas = 0;
    int vendasComErro = 0;

    try {
      final since = isInicial ? null : await _getUltimaSync(tenantId, 'geral');

      // 1. Formas de pagamento (rápido, poucas entradas)
      _setEstado(SyncEstado.sincronizando, 'Sincronizando formas de pagamento...', 0.1);
      formasBaixadas = await _syncFormasPagamento(tenantId);

      // 2. Categorias
      _setEstado(SyncEstado.sincronizando, 'Sincronizando categorias...', 0.2);
      categoriasBaixadas = await _syncCategorias(tenantId, since);

      // 3. Produtos (maior volume — paginado)
      _setEstado(SyncEstado.sincronizando, 'Sincronizando produtos...', 0.3);
      produtosBaixados = await _syncProdutos(tenantId, since, isInicial);

      // 4. Clientes
      _setEstado(SyncEstado.sincronizando, 'Sincronizando clientes...', 0.75);
      clientesBaixados = await _syncClientes(tenantId, since, isInicial);

      // 5. Push: enviar vendas offline pendentes
      _setEstado(SyncEstado.sincronizando, 'Enviando vendas offline...', 0.90);
      final pushResult = await _pushVendasPendentes(tenantId);
      vendasEnviadas = pushResult.$1;
      vendasComErro = pushResult.$2;
      erros.addAll(pushResult.$3);

      // Salva timestamp do último sync isolado por tenantId
      final agora = DateTime.now().toIso8601String();
      await _salvarUltimaSync(tenantId, 'geral', agora);
      await _db.catalogDao.atualizarUltimaSync(tenantId, agora);

      _ultimoResultado = SyncResultado(
        produtosBaixados: produtosBaixados,
        clientesBaixados: clientesBaixados,
        categoriasBaixadas: categoriasBaixadas,
        formasBaixadas: formasBaixadas,
        vendasEnviadas: vendasEnviadas,
        vendasComErro: vendasComErro,
        erros: erros,
      );

      _setEstado(SyncEstado.concluido, 'Sincronização concluída!', 1.0);
      debugPrint('[Sync] ✅ Concluído: $produtosBaixados produtos, $vendasEnviadas vendas enviadas.');

      // Dispara download em segundo plano das imagens dos produtos (não bloqueia UI nem vendas)
      _sincronizarFotosEmBackground(tenantId);
    } catch (e) {
      debugPrint('[Sync] ❌ Erro: $e');
      erros.add(e.toString());
      _ultimoResultado = SyncResultado(erros: erros);
      _setEstado(SyncEstado.erro, 'Erro: $e', 0.0);
    }

    return _ultimoResultado!;
  }

  /// Dispara o download das fotos em background de forma assíncrona
  void _sincronizarFotosEmBackground(String tenantId) {
    Future.microtask(() async {
      try {
        final tenant = await _db.catalogDao.getTenantAtivo();
        final baseUrl = tenant?.urlServidor ?? await ApiClient.getServerUrl();
        await MediaSyncManager.sincronizarFotosProdutos(
          tenantId: tenantId,
          db: _db,
          baseUrl: baseUrl,
        );
      } catch (e) {
        debugPrint('[Sync] Erro no download em background de fotos: $e');
      }
    });
  }

  /// Permite sincronizar manualmente as fotos dos produtos com acompanhamento de progresso
  Future<int> sincronizarFotosManualmente(
    String tenantId, {
    void Function(int processados, int total)? onProgress,
  }) async {
    final tenant = await _db.catalogDao.getTenantAtivo();
    final baseUrl = tenant?.urlServidor ?? await ApiClient.getServerUrl();
    return MediaSyncManager.sincronizarFotosProdutos(
      tenantId: tenantId,
      db: _db,
      baseUrl: baseUrl,
      onProgress: onProgress,
    );
  }

  // ─── SYNC: Formas de Pagamento ─────────────────────────────────

  Future<int> _syncFormasPagamento(String tenantId) async {
    try {
      final resp = await ApiClient.get('/api/mobile/sync/formas-pagamento');
      final items = (resp['data']?['items'] as List? ?? []);

      final companions = items.map<FormasPagamentoCompanion>((e) {
        return FormasPagamentoCompanion(
          id: Value(e['id']),
          usuarioId: Value(tenantId),
          nome: Value(e['nome'] ?? ''),
          tipo: Value(e['tipo'] ?? 'OUTRO'),
          ativo: Value((e['ativo'] as bool?) ?? true),
          aceitaParcelamento: Value((e['aceita_parcelamento'] as bool?) ?? false),
        );
      }).toList();

      await _db.catalogDao.upsertFormasPagamento(companions);
      return companions.length;
    } catch (e) {
      debugPrint('[Sync] Erro formas-pagamento: $e');
      return 0;
    }
  }

  // ─── SYNC: Categorias ──────────────────────────────────────────

  Future<int> _syncCategorias(String tenantId, String? since) async {
    try {
      final params = <String, String>{};
      if (since != null) params['since'] = since;

      final resp = await ApiClient.get('/api/mobile/sync/categorias', params: params);
      final items = (resp['data']?['items'] as List? ?? []);

      final companions = items.map<CategoriasCompanion>((e) {
        return CategoriasCompanion(
          id: Value(e['id']),
          usuarioId: Value(tenantId),
          nome: Value(e['nome'] ?? ''),
          ativo: Value((e['ativo'] as bool?) ?? true),
        );
      }).toList();

      await _db.catalogDao.upsertCategorias(companions);
      return companions.length;
    } catch (e) {
      debugPrint('[Sync] Erro categorias: $e');
      return 0;
    }
  }

  // ─── SYNC: Produtos (paginado) ─────────────────────────────────

  Future<int> _syncProdutos(
    String tenantId,
    String? since,
    bool isInicial,
  ) async {
    int totalBaixados = 0;
    int page = 1;
    bool hasMore = true;

    while (hasMore) {
      try {
        final params = <String, String>{
          'page': page.toString(),
          'per_page': '200',
        };
        if (since != null) params['since'] = since;

        final resp = await ApiClient.get('/api/mobile/sync/produtos', params: params);
        final data = resp['data'] as Map<String, dynamic>? ?? {};
        final items = (data['items'] as List? ?? []);
        final meta = data['meta'] as Map<String, dynamic>? ?? {};

        final companions = items.map<ProdutosCompanion>((e) {
          return ProdutosCompanion(
            id: Value(e['id']),
            usuarioId: Value(tenantId),
            categoriaId: Value(e['categoria_id']),
            nome: Value(e['nome'] ?? ''),
            codigoBarras: Value(e['codigo_barras']),
            codigoReferencia: Value(e['codigo_referencia']),
            precoVendaSugerido: Value((e['preco_venda_sugerido'] as num?)?.toDouble() ?? 0.0),
            precoCusto: Value((e['preco_custo'] as num?)?.toDouble() ?? 0.0),
            precoPromocional: Value((e['preco_promocional'] as num?)?.toDouble() ?? 0.0),
            precoVigente: Value((e['preco_vigente'] as num?)?.toDouble() ?? 0.0),
            emPromocao: Value((e['em_promocao'] as bool?) ?? false),
            dataInicioPromocao: Value(e['data_inicio_promocao']),
            dataFimPromocao: Value(e['data_fim_promocao']),
            estoqueAtual: Value((e['estoque_atual'] as num?)?.toInt() ?? 0),
            estoqueMinimo: Value((e['estoque_minimo'] as num?)?.toInt() ?? 0),
            unidadeMedida: Value(e['unidade_medida'] ?? 'UN'),
            vendaFracionada: Value((e['venda_fracionada'] as bool?) ?? false),
            marca: Value(e['marca']),
            fotoUrl: Value(e['foto_url']),
            ativo: Value((e['ativo'] as bool?) ?? true),
            dataAtualizacao: Value(e['data_atualizacao']),
            variantesJson: Value(
              e['variantes'] != null ? jsonEncode(e['variantes']) : null,
            ),
          );
        }).toList();

        await _db.produtoDao.upsertBatch(companions);
        totalBaixados += companions.length;

        hasMore = (meta['has_more'] as bool?) ?? false;
        page++;

        // Atualiza progresso (30% a 75% para produtos)
        final totalPages = (meta['total_pages'] as num?)?.toInt() ?? 1;
        final progressoProdutos = 0.3 + (page / totalPages) * 0.45;
        _setEstado(
          SyncEstado.sincronizando,
          'Sincronizando produtos... ($totalBaixados baixados)',
          progressoProdutos.clamp(0.3, 0.74),
        );
      } catch (e) {
        debugPrint('[Sync] Erro produtos página $page: $e');
        hasMore = false;
      }
    }

    return totalBaixados;
  }

  // ─── SYNC: Clientes (paginado) ─────────────────────────────────

  Future<int> _syncClientes(
    String tenantId,
    String? since,
    bool isInicial,
  ) async {
    int totalBaixados = 0;
    int page = 1;
    bool hasMore = true;

    while (hasMore) {
      try {
        final params = <String, String>{
          'page': page.toString(),
          'per_page': '200',
        };
        if (since != null) params['since'] = since;

        final resp = await ApiClient.get('/api/mobile/sync/clientes', params: params);
        final data = resp['data'] as Map<String, dynamic>? ?? {};
        final items = (data['items'] as List? ?? []);
        final meta = data['meta'] as Map<String, dynamic>? ?? {};

        final companions = items.map<ClientesCompanion>((e) {
          return ClientesCompanion(
            id: Value(e['id']),
            usuarioId: Value(tenantId),
            nomeCompleto: Value(e['nome_completo'] ?? ''),
            cpf: Value(e['cpf']),
            telefone: Value(e['telefone']),
            email: Value(e['email']),
            enderecoLogradouro: Value(e['endereco_logradouro']),
            enderecoNumero: Value(e['endereco_numero']),
            enderecoBairro: Value(e['endereco_bairro']),
            enderecoCidade: Value(e['endereco_cidade']),
            enderecoEstado: Value(e['endereco_estado']),
            enderecoCep: Value(e['endereco_cep']),
            ativo: Value((e['ativo'] as bool?) ?? true),
            dataCriacao: Value(e['data_criacao']),
            dataAtualizacao: Value(e['data_atualizacao']),
            syncStatus: const Value('synced'),
          );
        }).toList();

        await _db.clienteDao.upsertBatch(companions);
        totalBaixados += companions.length;

        hasMore = (meta['has_more'] as bool?) ?? false;
        page++;
      } catch (e) {
        debugPrint('[Sync] Erro clientes página $page: $e');
        hasMore = false;
      }
    }

    return totalBaixados;
  }

  // ─── PUSH: Vendas Pendentes ────────────────────────────────────

  /// Envia vendas offline em lote. Retorna (enviadas, erros, mensagens de erro).
  Future<(int, int, List<String>)> _pushVendasPendentes(String tenantId) async {
    final pendentes = await _db.vendaDao.getVendasPendentes(tenantId);
    if (pendentes.isEmpty) return (0, 0, <String>[]);

    // Monta payload do batch
    final vendasPayload = <Map<String, dynamic>>[];
    for (final venda in pendentes) {
      final itens = await _db.vendaDao.getItensPorVenda(venda.id);
      final parcelas = await _db.vendaDao.getParcelasPorVenda(venda.id);

      vendasPayload.add({
        'id': venda.id,
        'usuario_id': venda.usuarioId,
        'cliente_id': venda.clienteId,
        'colaborador_vendedor_id': venda.colaboradorVendedorId,
        'forma_pagamento_id': venda.formaPagamentoId,
        'data_venda': venda.dataVenda,
        'valor_total': venda.valorTotal,
        'numero_parcelas': venda.numeroParcelas,
        'status_venda_codigo': venda.statusVendaCodigo,
        'tipo_venda': venda.tipoVenda,
        'observacoes': venda.observacoes,
        'cpf_consumidor': venda.cpfConsumidor,
        'acrescimo_valor': venda.acrescimoValor,
        'acrescimo_tipo': venda.acrescimoTipo,
        'desconto_global_valor': venda.descontoGlobalValor,
        'desconto_global_tipo': venda.descontoGlobalTipo,
        'itens': itens.map((i) => {
          'id': i.id,
          'produto_id': i.produtoId,
          'variante_id': i.varianteId,
          'nome_item_manual': i.nomeItemManual,
          'quantidade': i.quantidade,
          'preco_unitario': i.precoUnitarioVenda,
          'desconto_percentual': i.descontoPercentual,
          'desconto_valor': i.descontoValor,
        }).toList(),
        'parcelas': parcelas.map((p) => {
          'id': p.id,
          'numero_parcela': p.numeroParcela,
          'valor_parcela': p.valorParcela,
          'data_vencimento': p.dataVencimento,
          'status_parcela_codigo': p.statusParcelaCodigo,
          'forma_pagamento_id': p.formaPagamentoId,
        }).toList(),
      });
    }

    // Marca todas como "syncing"
    for (final v in pendentes) {
      await _db.vendaDao.atualizarSyncStatus(v.id, 'syncing');
    }

    try {
      final resp = await ApiClient.post('/api/mobile/venda/batch', {
        'vendas': vendasPayload,
      });

      final resultados =
          (resp['data']?['resultados'] as List? ?? []);
      int enviadas = 0;
      int erros = 0;
      final erroMsgs = <String>[];

      for (final r in resultados) {
        final id = r['id'] as String? ?? '';
        final sucesso = (r['sucesso'] as bool?) ?? false;

        if (sucesso) {
          await _db.vendaDao.atualizarSyncStatus(id, 'synced');
          enviadas++;
        } else {
          final erro = r['erro'] as String? ?? 'Erro desconhecido';
          final tentativas = pendentes
              .firstWhere((v) => v.id == id,
                  orElse: () => pendentes.first)
              .syncTentativas;
          await _db.vendaDao.atualizarSyncStatus(
            id,
            'error',
            erro: erro,
            tentativas: tentativas + 1,
          );
          erros++;
          erroMsgs.add('Venda $id: $erro');
        }
      }

      return (enviadas, erros, erroMsgs);
    } catch (e) {
      // Reverte todas para 'pending'
      for (final v in pendentes) {
        await _db.vendaDao.atualizarSyncStatus(v.id, 'pending',
            erro: e.toString());
      }
      return (0, pendentes.length, [e.toString()]);
    }
  }

  // ─── Helpers de SharedPreferences ─────────────────────────────

  Future<String?> _getUltimaSync(String tenantId, String tabela) async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('$_prefixUltimaSync${tenantId}_$tabela');
  }

  Future<void> _salvarUltimaSync(String tenantId, String tabela, String timestamp) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('$_prefixUltimaSync${tenantId}_$tabela', timestamp);
  }

  // ─── Notificação de estado ─────────────────────────────────────

  void _setEstado(SyncEstado estado, String mensagem, double progresso) {
    _estado = estado;
    _mensagem = mensagem;
    _progresso = progresso;
    notifyListeners();
  }

  @override
  void dispose() {
    _connectivity.removeListener(_onConectividadeAlterada);
    super.dispose();
  }
}
