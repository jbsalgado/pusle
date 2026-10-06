// lib/features/auth/auth_service.dart
//
// Gerencia login, logout, lojas disponíveis e estado de autenticação.
// Persiste JWT, loja ativa e URL do servidor via ApiClient (flutter_secure_storage).

import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import '../../core/network/api_client.dart';
import '../../core/database/app_database.dart';
import 'package:drift/drift.dart';
import 'models/loja_acesso.dart';

enum AuthEstado { desconhecido, autenticado, naoAutenticado }

class AuthService extends ChangeNotifier {
  final AppDatabase _db;

  AuthEstado _estado = AuthEstado.desconhecido;
  String? _tenantId;
  String? _nomeUsuario;
  bool _precisaSyncInicial = false;
  bool _precisaSelecionarLoja = false;

  List<LojaAcesso> _lojasDisponiveis = [];
  LojaAcesso? _lojaAtiva;

  AuthService(this._db);

  AuthEstado get estado => _estado;
  String? get tenantId => _lojaAtiva?.lojaId ?? _tenantId;
  String? get nomeUsuario => _nomeUsuario;
  bool get isAutenticado => _estado == AuthEstado.autenticado;
  bool get precisaSyncInicial => _precisaSyncInicial;
  bool get precisaSelecionarLoja => _precisaSelecionarLoja;

  List<LojaAcesso> get lojasDisponiveis => _lojasDisponiveis;
  LojaAcesso? get lojaAtiva => _lojaAtiva;
  String? get nomeLojaAtiva => _lojaAtiva?.nomeExibicao;

  /// Verifica se já há um token salvo ao iniciar o app e restaura a loja ativa.
  Future<void> verificarSessao() async {
    final autenticado = await ApiClient.estaAutenticado();
    if (autenticado) {
      // 1. Restaura lista de lojas salvas em cache local
      final lojasJson = await ApiClient.getLojasJson();
      if (lojasJson != null && lojasJson.isNotEmpty) {
        try {
          final list = jsonDecode(lojasJson) as List;
          _lojasDisponiveis = list
              .map((e) => LojaAcesso.fromJson(e as Map<String, dynamic>))
              .toList();
        } catch (e) {
          debugPrint('[Auth] Erro ao decodificar lojas em cache: $e');
        }
      }

      final lojaAtivaId =
          await ApiClient.getLojaAtivaId() ?? await ApiClient.getTenantId();
      final tenant = await _db.catalogDao.getTenantAtivo();

      _tenantId = tenant?.id ?? lojaAtivaId;
      _nomeUsuario = tenant?.nome;

      if (_lojasDisponiveis.isNotEmpty && lojaAtivaId != null) {
        _lojaAtiva = _lojasDisponiveis.firstWhere(
          (l) => l.lojaId == lojaAtivaId,
          orElse: () => _lojasDisponiveis.first,
        );
      }

      // Se ainda não tiver loja ativa e há lojas disponíveis, precisa selecionar
      if (_lojaAtiva == null && _lojasDisponiveis.length > 1) {
        _precisaSelecionarLoja = true;
      } else {
        _precisaSyncInicial = tenant?.ultimaSync == null;
      }

      _estado = AuthEstado.autenticado;
    } else {
      _estado = AuthEstado.naoAutenticado;
    }
    notifyListeners();
  }

  /// Realiza o login no servidor, identifica as lojas disponíveis e define a loja ativa.
  Future<void> login({
    required String serverUrl,
    required String username,
    required String password,
  }) async {
    // Normaliza URL
    final url = serverUrl.trimRight().replaceAll(RegExp(r'/$'), '');

    // Salva a URL temporariamente para o ApiClient usar
    await ApiClient.salvarCredenciais(
      token: '',
      serverUrl: url,
      tenantId: '',
    );

    Map<String, dynamic> resp;
    try {
      resp = await ApiClient.login(username, password);
    } on SocketException {
      throw Exception('Sem conexão com o servidor. Verifique a URL e a internet.');
    }

    final data = resp['data'] as Map<String, dynamic>?;
    if (data == null) {
      throw Exception(resp['message'] ?? 'Resposta inesperada do servidor.');
    }

    final token = data['token'] as String?;
    final usuario = data['usuario'] as Map<String, dynamic>?;

    if (token == null || token.isEmpty) {
      throw Exception('Token não recebido do servidor.');
    }

    final nomeUsuario = usuario?['nome'] ?? usuario?['username'] ?? username;
    _nomeUsuario = nomeUsuario;

    // Processa lista de lojas
    final lojasRaw = data['lojas'] as List? ?? [];
    _lojasDisponiveis = lojasRaw
        .map((e) => LojaAcesso.fromJson(e as Map<String, dynamic>))
        .toList();

    // Fallback para caso o backend não tenha enviado array de lojas
    if (_lojasDisponiveis.isEmpty) {
      final colab = data['colaborador'] as Map<String, dynamic>?;
      final colabUsuarioId = colab?['usuario_id'] as String?;
      final usuarioId = usuario?['id'] as String?;
      final fallbackTenantId = colabUsuarioId ?? usuarioId;

      if (fallbackTenantId == null || fallbackTenantId.isEmpty) {
        throw Exception('Nenhuma loja associada a este usuário.');
      }

      final ehDono = usuario?['eh_dono_loja'] == true || colab == null;

      _lojasDisponiveis = [
        LojaAcesso(
          lojaId: fallbackTenantId,
          nome: nomeUsuario,
          papel: ehDono ? 'dono' : 'vendedor',
          colaboradorId: colab?['id'] as String?,
          ehVendedor: (colab?['eh_vendedor'] as bool?) ?? true,
          ehDono: ehDono,
        ),
      ];
    }

    // Persiste a lista de lojas no storage seguro
    await ApiClient.salvarLojasJson(
      jsonEncode(_lojasDisponiveis.map((e) => e.toJson()).toList()),
    );

    if (_lojasDisponiveis.length == 1) {
      // Usuário tem apenas 1 loja: seleciona automaticamente
      await selecionarLoja(
        _lojasDisponiveis.first,
        serverUrl: url,
        token: token,
      );
    } else {
      // Usuário tem múltiplas lojas: salva token e vai para a tela de seleção de loja
      await ApiClient.salvarCredenciais(
        token: token,
        serverUrl: url,
        tenantId: '',
      );
      _precisaSelecionarLoja = true;
      _estado = AuthEstado.autenticado;
      notifyListeners();
    }
  }

  /// Define a loja ativa no aplicativo e salva no SQLite e SecureStorage.
  Future<void> selecionarLoja(
    LojaAcesso loja, {
    String? serverUrl,
    String? token,
  }) async {
    final url = serverUrl ?? (await ApiClient.getServerUrl()) ?? '';
    final jwt = token ?? (await ApiClient.getToken()) ?? '';

    // Salva credenciais com a loja ativa
    await ApiClient.salvarCredenciais(
      token: jwt,
      serverUrl: url,
      tenantId: loja.lojaId,
      lojaAtivaId: loja.lojaId,
    );

    // Salva / atualiza tenant no banco SQLite Drift
    await _db.catalogDao.salvarTenant(TenantsCompanion(
      id: Value(loja.lojaId),
      nome: Value(loja.nomeExibicao),
      tokenJwt: Value(jwt),
      urlServidor: Value(url),
      logoUrl: Value(loja.logoUrl),
      ativo: const Value(true),
    ));

    // Marca esta loja como ativa no banco local
    await _db.catalogDao.marcarTenantAtivo(loja.lojaId);

    final tenant = await _db.catalogDao.getTenantById(loja.lojaId);

    _tenantId = loja.lojaId;
    _lojaAtiva = loja;
    _precisaSyncInicial = (tenant?.ultimaSync == null);
    _precisaSelecionarLoja = false;
    _estado = AuthEstado.autenticado;
    notifyListeners();
  }

  /// Alterna dinamicamente a loja ativa a partir da Dashboard ou menu lateral.
  Future<void> trocarLoja(LojaAcesso novaLoja) async {
    await selecionarLoja(novaLoja);
  }

  /// Recarrega as lojas disponíveis do servidor (caso novas filiais tenham sido liberadas).
  Future<void> atualizarLojasDoServidor() async {
    try {
      final lojasRaw = await ApiClient.buscarMinhasLojas();
      if (lojasRaw.isNotEmpty) {
        _lojasDisponiveis = lojasRaw.map((e) => LojaAcesso.fromJson(e)).toList();
        await ApiClient.salvarLojasJson(
          jsonEncode(_lojasDisponiveis.map((e) => e.toJson()).toList()),
        );
        notifyListeners();
      }
    } catch (e) {
      debugPrint('[Auth] Erro ao atualizar lojas do servidor: $e');
    }
  }

  /// Logout: limpa credenciais e banco local.
  Future<void> logout() async {
    await ApiClient.limparCredenciais();
    await _db.limparTudo();
    _tenantId = null;
    _nomeUsuario = null;
    _lojaAtiva = null;
    _lojasDisponiveis = [];
    _precisaSyncInicial = false;
    _precisaSelecionarLoja = false;
    _estado = AuthEstado.naoAutenticado;
    notifyListeners();
  }

  /// Marca que a sync inicial foi concluída.
  void marcarSyncInicialConcluida() {
    _precisaSyncInicial = false;
    notifyListeners();
  }
}
