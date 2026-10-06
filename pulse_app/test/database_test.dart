import 'package:flutter_test/flutter_test.dart';
import 'package:drift/drift.dart' hide isNotNull;
import 'package:drift/native.dart';
import 'package:pulse_app/core/database/app_database.dart';

void main() {
  late AppDatabase db;

  setUp(() {
    db = AppDatabase.forTesting(NativeDatabase.memory());
  });

  tearDown(() async {
    await db.close();
  });

  group('Pulse Offline Database Tests', () {
    const tenantId = 'tenant-test-123';

    test('Produtos: upsertBatch e busca por nome e código de barras', () async {
      // 1. Inserir produtos
      await db.produtoDao.upsertBatch([
        ProdutosCompanion(
          id: const Value('prod-1'),
          usuarioId: const Value(tenantId),
          nome: const Value('Coca-Cola 350ml'),
          codigoBarras: const Value('7894900010015'),
          precoVendaSugerido: const Value(5.50),
          precoVigente: const Value(5.50),
          estoqueAtual: const Value(40),
          ativo: const Value(true),
        ),
        ProdutosCompanion(
          id: const Value('prod-2'),
          usuarioId: const Value(tenantId),
          nome: const Value('Guaraná Antarctica 350ml'),
          codigoBarras: const Value('7891991000826'),
          precoVendaSugerido: const Value(4.50),
          precoVigente: const Value(4.50),
          estoqueAtual: const Value(25),
          ativo: const Value(true),
        ),
      ]);

      // 2. Buscar por nome
      final resultadoBusca = await db.produtoDao.buscarProdutos(tenantId, 'coca');
      expect(resultadoBusca.length, 1);
      expect(resultadoBusca.first.nome, 'Coca-Cola 350ml');
      expect(resultadoBusca.first.precoVigente, 5.50);

      // 3. Buscar por código de barras
      final prodBarcode = await db.produtoDao.getByCodigo(tenantId, '7891991000826');
      expect(prodBarcode, isNotNull);
      expect(prodBarcode!.nome, 'Guaraná Antarctica 350ml');
      expect(prodBarcode.estoqueAtual, 25);
    });

    test('Clientes: upsertBatch e busca por CPF e nome', () async {
      await db.clienteDao.upsertBatch([
        ClientesCompanion(
          id: const Value('cli-1'),
          usuarioId: const Value(tenantId),
          nomeCompleto: const Value('Maria da Silva'),
          cpf: const Value('12345678901'),
          telefone: const Value('11988887777'),
          ativo: const Value(true),
        ),
      ]);

      final porNome = await db.clienteDao.buscar(tenantId, 'Maria');
      expect(porNome.length, 1);
      expect(porNome.first.cpf, '12345678901');

      final porCpf = await db.clienteDao.buscar(tenantId, '123456');
      expect(porCpf.length, 1);
      expect(porCpf.first.nomeCompleto, 'Maria da Silva');
    });

    test('Catalog: Formas de Pagamento e Categorias', () async {
      await db.catalogDao.upsertCategorias([
        CategoriasCompanion(
          id: const Value('cat-1'),
          usuarioId: const Value(tenantId),
          nome: const Value('Bebidas'),
          ativo: const Value(true),
        ),
      ]);

      final categorias = await db.catalogDao.getCategorias(tenantId);
      expect(categorias.length, 1);
      expect(categorias.first.nome, 'Bebidas');

      await db.catalogDao.upsertFormasPagamento([
        FormasPagamentoCompanion(
          id: const Value('fp-1'),
          usuarioId: const Value(tenantId),
          nome: const Value('Dinheiro'),
          tipo: const Value('DINHEIRO'),
          ativo: const Value(true),
        ),
        FormasPagamentoCompanion(
          id: const Value('fp-2'),
          usuarioId: const Value(tenantId),
          nome: const Value('PIX'),
          tipo: const Value('PIX'),
          ativo: const Value(true),
        ),
      ]);

      final formas = await db.catalogDao.getFormasPagamento(tenantId);
      expect(formas.length, 2);
    });

    test('Vendas: ciclo completo offline (registro, pendente, itens e marcação como sincronizada)', () async {
      const vendaId = 'venda-uuid-001';
      final agora = DateTime.now().toIso8601String();

      // 1. Registra venda com status pending (offline)
      await db.vendaDao.inserirVenda(VendasCompanion(
        id: const Value(vendaId),
        usuarioId: const Value(tenantId),
        dataVenda: Value(agora),
        valorTotal: const Value(25.00),
        statusVendaCodigo: const Value('QUITADA'),
        tipoVenda: const Value('BALCAO'),
        dataCriacao: Value(agora),
        syncStatus: const Value('pending'),
      ));

      // 2. Insere itens
      await db.vendaDao.inserirItens([
        VendaItensCompanion(
          id: const Value('item-1'),
          vendaId: const Value(vendaId),
          produtoId: const Value('prod-1'),
          quantidade: const Value(2.0),
          precoUnitarioVenda: const Value(12.50),
          valorTotalItem: const Value(25.00),
        ),
      ]);

      // 3. Verifica pendentes
      final pendentes = await db.vendaDao.getVendasPendentes(tenantId);
      expect(pendentes.length, 1);
      expect(pendentes.first.id, vendaId);
      expect(pendentes.first.syncStatus, 'pending');

      final itens = await db.vendaDao.getItensPorVenda(vendaId);
      expect(itens.length, 1);
      expect(itens.first.quantidade, 2.0);

      // 4. Simula sincronização bem-sucedida
      await db.vendaDao.atualizarSyncStatus(vendaId, 'synced');

      // 5. Verifica que não há mais pendentes
      final pendentesAposSync = await db.vendaDao.getVendasPendentes(tenantId);
      expect(pendentesAposSync.isEmpty, isTrue);

      // 6. Confirma que a venda ainda existe no histórico como 'synced'
      final historico = await db.vendaDao.getHistorico(tenantId);
      expect(historico.length, 1);
      expect(historico.first.syncStatus, 'synced');
    });
  });
}
