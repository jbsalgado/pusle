// lib/core/database/app_database.dart
//
// Banco de dados principal (drift). Registra todas as tabelas e DAOs.
// Versão atual: 1
//
// Para regenerar o código gerado após alterações:
//   cd pulse_app
//   flutter pub run build_runner build --delete-conflicting-outputs

import 'package:drift/drift.dart';
import 'package:drift_flutter/drift_flutter.dart';

import 'tables/tables.dart';
import 'daos/produto_dao.dart';
import 'daos/venda_dao.dart';
import 'daos/cliente_dao.dart';
import 'daos/catalog_dao.dart';

part 'app_database.g.dart';

@DriftDatabase(
  tables: [
    Tenants,
    Categorias,
    Produtos,
    Clientes,
    FormasPagamento,
    Vendas,
    VendaItens,
    Parcelas,
    SyncQueue,
  ],
  daos: [
    ProdutoDao,
    VendaDao,
    ClienteDao,
    CatalogDao,
  ],
)
class AppDatabase extends _$AppDatabase {
  AppDatabase() : super(_openConnection());

  // Para testes: injeta uma conexão customizada
  AppDatabase.forTesting(super.connection);

  @override
  int get schemaVersion => 2;

  @override
  MigrationStrategy get migration {
    return MigrationStrategy(
      onCreate: (m) async {
        await m.createAll();
      },
      onUpgrade: (m, from, to) async {
        if (from < 2) {
          await m.addColumn(produtos, produtos.fotoLocalPath);
        }
      },
    );
  }

  /// Limpa todos os dados do tenant atual (logout / troca de conta)
  Future<void> limparTudo() async {
    await transaction(() async {
      await delete(syncQueue).go();
      await delete(parcelas).go();
      await delete(vendaItens).go();
      await delete(vendas).go();
      await delete(clientes).go();
      await delete(formasPagamento).go();
      await delete(produtos).go();
      await delete(categorias).go();
      await delete(tenants).go();
    });
  }
}

QueryExecutor _openConnection() {
  return driftDatabase(name: 'pulse_sales_db');
}
