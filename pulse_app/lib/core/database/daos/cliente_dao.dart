// lib/core/database/daos/cliente_dao.dart

import 'package:drift/drift.dart';
import '../app_database.dart';
import '../tables/tables.dart';

part 'cliente_dao.g.dart';

@DriftAccessor(tables: [Clientes])
class ClienteDao extends DatabaseAccessor<AppDatabase> with _$ClienteDaoMixin {
  ClienteDao(super.db);

  Future<List<Cliente>> buscar(String usuarioId, String termo) {
    final lower = '%${termo.toLowerCase()}%';
    return (select(clientes)
          ..where((c) =>
              c.usuarioId.equals(usuarioId) &
              c.ativo.equals(true) &
              (c.nomeCompleto.lower().like(lower) |
                  c.cpf.like(lower) |
                  c.telefone.lower().like(lower)))
          ..orderBy([(c) => OrderingTerm.asc(c.nomeCompleto)])
          ..limit(30))
        .get();
  }

  Future<Cliente?> getByCpf(String usuarioId, String cpf) {
    return (select(clientes)
          ..where((c) => c.usuarioId.equals(usuarioId) & c.cpf.equals(cpf)))
        .getSingleOrNull();
  }

  Future<Cliente?> getById(String id) {
    return (select(clientes)..where((c) => c.id.equals(id))).getSingleOrNull();
  }

  Future<void> upsertBatch(List<ClientesCompanion> items) async {
    await batch((b) {
      for (final item in items) {
        b.insertAll(clientes, [item], mode: InsertMode.insertOrReplace);
      }
    });
  }

  Future<void> inserirCliente(ClientesCompanion cliente) async {
    await into(clientes).insert(cliente, mode: InsertMode.insertOrReplace);
  }

  Future<int> count(String usuarioId) async {
    final count = clientes.id.count();
    final query = selectOnly(clientes)
      ..addColumns([count])
      ..where(clientes.usuarioId.equals(usuarioId));
    final row = await query.getSingle();
    return row.read(count) ?? 0;
  }
}
