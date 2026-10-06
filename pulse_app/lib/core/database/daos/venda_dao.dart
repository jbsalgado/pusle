// lib/core/database/daos/venda_dao.dart

import 'package:drift/drift.dart';
import '../app_database.dart';
import '../tables/tables.dart';

part 'venda_dao.g.dart';

@DriftAccessor(tables: [Vendas, VendaItens, Parcelas])
class VendaDao extends DatabaseAccessor<AppDatabase> with _$VendaDaoMixin {
  VendaDao(super.db);

  // ─── Vendas ───────────────────────────────────────────────

  Future<List<Venda>> getVendasPendentes(String usuarioId) {
    return (select(vendas)
          ..where((v) =>
              v.usuarioId.equals(usuarioId) &
              v.syncStatus.equals('pending')))
        .get();
  }

  Future<List<Venda>> getHistorico(String usuarioId, {int limit = 50}) {
    return (select(vendas)
          ..where((v) => v.usuarioId.equals(usuarioId))
          ..orderBy([(v) => OrderingTerm.desc(v.dataVenda)])
          ..limit(limit))
        .get();
  }

  Future<Venda?> getById(String id) {
    return (select(vendas)..where((v) => v.id.equals(id))).getSingleOrNull();
  }

  Future<void> inserirVenda(VendasCompanion venda) async {
    await into(vendas).insert(venda);
  }

  Future<void> atualizarSyncStatus(
    String id,
    String status, {
    String? erro,
    int? tentativas,
  }) async {
    await (update(vendas)..where((v) => v.id.equals(id))).write(
      VendasCompanion(
        syncStatus: Value(status),
        syncError: erro != null ? Value(erro) : const Value.absent(),
        syncTentativas: tentativas != null ? Value(tentativas) : const Value.absent(),
      ),
    );
  }

  Future<int> countPendentes(String usuarioId) async {
    final count = vendas.id.count();
    final query = selectOnly(vendas)
      ..addColumns([count])
      ..where(vendas.usuarioId.equals(usuarioId) &
          vendas.syncStatus.equals('pending'));
    final row = await query.getSingle();
    return row.read(count) ?? 0;
  }

  Stream<int> watchCountPendentes(String usuarioId) {
    final count = vendas.id.count();
    final query = selectOnly(vendas)
      ..addColumns([count])
      ..where(vendas.usuarioId.equals(usuarioId) &
          vendas.syncStatus.equals('pending'));
    return query.watchSingle().map((row) => row.read(count) ?? 0);
  }

  // ─── Itens ────────────────────────────────────────────────

  Future<void> inserirItens(List<VendaItensCompanion> itens) async {
    await batch((b) => b.insertAll(vendaItens, itens));
  }

  Future<List<VendaItem>> getItensPorVenda(String vendaId) {
    return (select(vendaItens)
          ..where((i) => i.vendaId.equals(vendaId)))
        .get();
  }

  // ─── Parcelas ─────────────────────────────────────────────

  Future<void> inserirParcelas(List<ParcelasCompanion> ps) async {
    await batch((b) => b.insertAll(parcelas, ps));
  }

  Future<List<Parcela>> getParcelasPorVenda(String vendaId) {
    return (select(parcelas)
          ..where((p) => p.vendaId.equals(vendaId))
          ..orderBy([(p) => OrderingTerm.asc(p.numeroParcela)]))
        .get();
  }
}
