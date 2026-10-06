// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cliente_dao.dart';

// ignore_for_file: type=lint
mixin _$ClienteDaoMixin on DatabaseAccessor<AppDatabase> {
  $ClientesTable get clientes => attachedDatabase.clientes;
  ClienteDaoManager get managers => ClienteDaoManager(this);
}

class ClienteDaoManager {
  final _$ClienteDaoMixin _db;
  ClienteDaoManager(this._db);
  $$ClientesTableTableManager get clientes =>
      $$ClientesTableTableManager(_db.attachedDatabase, _db.clientes);
}
