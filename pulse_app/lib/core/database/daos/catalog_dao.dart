// lib/core/database/daos/catalog_dao.dart
// DAOs para Categorias, FormasPagamento e Tenants

import 'package:drift/drift.dart';
import '../app_database.dart';
import '../tables/tables.dart';

part 'catalog_dao.g.dart';

@DriftAccessor(tables: [Categorias, FormasPagamento, Tenants])
class CatalogDao extends DatabaseAccessor<AppDatabase> with _$CatalogDaoMixin {
  CatalogDao(super.db);

  // ─── Categorias ────────────────────────────────────────────────

  Future<List<Categoria>> getCategorias(String usuarioId) {
    return (select(categorias)
          ..where((c) => c.usuarioId.equals(usuarioId) & c.ativo.equals(true))
          ..orderBy([(c) => OrderingTerm.asc(c.nome)]))
        .get();
  }

  Future<void> upsertCategorias(List<CategoriasCompanion> items) async {
    await batch((b) {
      for (final item in items) {
        b.insertAll(categorias, [item], mode: InsertMode.insertOrReplace);
      }
    });
  }

  // ─── Formas de Pagamento ───────────────────────────────────────

  Future<List<FormaPagamento>> getFormasPagamento(String usuarioId) {
    return (select(formasPagamento)
          ..where((f) => f.usuarioId.equals(usuarioId) & f.ativo.equals(true))
          ..orderBy([(f) => OrderingTerm.asc(f.nome)]))
        .get();
  }

  Future<FormaPagamento?> getFormaPagamentoById(String id) {
    return (select(formasPagamento)..where((f) => f.id.equals(id)))
        .getSingleOrNull();
  }

  Future<void> upsertFormasPagamento(List<FormasPagamentoCompanion> items) async {
    await batch((b) {
      for (final item in items) {
        b.insertAll(formasPagamento, [item], mode: InsertMode.insertOrReplace);
      }
    });
  }

  // ─── Tenants ───────────────────────────────────────────────────

  Future<Tenant?> getTenantAtivo() {
    return (select(tenants)..where((t) => t.ativo.equals(true)))
        .getSingleOrNull();
  }

  Future<Tenant?> getTenantById(String id) {
    return (select(tenants)..where((t) => t.id.equals(id))).getSingleOrNull();
  }

  Future<void> marcarTenantAtivo(String tenantId) async {
    await (update(tenants)).write(const TenantsCompanion(ativo: Value(false)));
    await (update(tenants)..where((t) => t.id.equals(tenantId)))
        .write(const TenantsCompanion(ativo: Value(true)));
  }

  Future<void> salvarTenant(TenantsCompanion tenant) async {
    await into(tenants).insertOnConflictUpdate(tenant);
  }

  Future<void> atualizarUltimaSync(String tenantId, String timestamp) async {
    await (update(tenants)..where((t) => t.id.equals(tenantId)))
        .write(TenantsCompanion(ultimaSync: Value(timestamp)));
  }

  Future<void> removerTodos() async {
    await delete(tenants).go();
  }
}
