// lib/core/database/daos/produto_dao.dart

import 'package:drift/drift.dart';
import '../app_database.dart';
import '../tables/tables.dart';

part 'produto_dao.g.dart';

@DriftAccessor(tables: [Produtos])
class ProdutoDao extends DatabaseAccessor<AppDatabase> with _$ProdutoDaoMixin {
  ProdutoDao(super.db);

  // Todos os produtos ativos do tenant
  Future<List<Produto>> getProdutosPorTenant(String usuarioId) {
    return (select(produtos)
          ..where((p) => p.usuarioId.equals(usuarioId) & p.ativo.equals(true))
          ..orderBy([(p) => OrderingTerm.asc(p.nome)]))
        .get();
  }

  // Busca por nome, código de barras ou referência
  Future<List<Produto>> buscarProdutos(String usuarioId, String termo) {
    final lower = '%${termo.toLowerCase()}%';
    return (select(produtos)
          ..where((p) =>
              p.usuarioId.equals(usuarioId) &
              p.ativo.equals(true) &
              (p.nome.lower().like(lower) |
                  p.codigoBarras.like(lower) |
                  p.codigoReferencia.lower().like(lower))))
        .get();
  }

  // Busca exata por código de barras (para scanner)
  Future<Produto?> getByCodigo(String usuarioId, String codigo) {
    return (select(produtos)
          ..where((p) =>
              p.usuarioId.equals(usuarioId) &
              (p.codigoBarras.equals(codigo) | p.codigoReferencia.equals(codigo))))
        .getSingleOrNull();
  }

  // Busca por categoria
  Future<List<Produto>> getPorCategoria(String usuarioId, String categoriaId) {
    return (select(produtos)
          ..where((p) =>
              p.usuarioId.equals(usuarioId) &
              p.ativo.equals(true) &
              p.categoriaId.equals(categoriaId))
          ..orderBy([(p) => OrderingTerm.asc(p.nome)]))
        .get();
  }

  // Stream reativo para a lista de produtos (atualiza a UI automaticamente)
  Stream<List<Produto>> watchProdutos(String usuarioId) {
    return (select(produtos)
          ..where((p) => p.usuarioId.equals(usuarioId) & p.ativo.equals(true))
          ..orderBy([(p) => OrderingTerm.asc(p.nome)]))
        .watch();
  }

  // Upsert (insert ou update)
  Future<void> upsertProduto(ProdutosCompanion produto) async {
    await into(produtos).insertOnConflictUpdate(produto);
  }

  // Upsert em lote (sync) preservando o caminho local da foto se já baixado
  Future<void> upsertBatch(List<ProdutosCompanion> items) async {
    await batch((b) {
      for (final item in items) {
        b.insert(
          produtos,
          item,
          onConflict: DoUpdate(
            (old) => item.copyWith(
              fotoLocalPath: const Value.absent(),
            ),
          ),
        );
      }
    });
  }

  // Atualiza apenas o caminho local da foto
  Future<void> atualizarFotoLocal(String produtoId, String localPath) async {
    await (update(produtos)..where((p) => p.id.equals(produtoId))).write(
      ProdutosCompanion(fotoLocalPath: Value(localPath)),
    );
  }

  // Busca produtos com fotoUrl mas sem fotoLocalPath
  Future<List<Produto>> getProdutosPendentesFoto(String usuarioId) {
    return (select(produtos)
          ..where((p) =>
              p.usuarioId.equals(usuarioId) &
              p.ativo.equals(true) &
              p.fotoUrl.isNotNull() &
              (p.fotoLocalPath.isNull() | p.fotoLocalPath.equals(''))))
        .get();
  }

  Future<int> count(String usuarioId) async {
    final count = produtos.id.count();
    final query = selectOnly(produtos)
      ..addColumns([count])
      ..where(produtos.usuarioId.equals(usuarioId));
    final row = await query.getSingle();
    return row.read(count) ?? 0;
  }
}
