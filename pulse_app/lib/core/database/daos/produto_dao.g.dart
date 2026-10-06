// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'produto_dao.dart';

// ignore_for_file: type=lint
mixin _$ProdutoDaoMixin on DatabaseAccessor<AppDatabase> {
  $ProdutosTable get produtos => attachedDatabase.produtos;
  ProdutoDaoManager get managers => ProdutoDaoManager(this);
}

class ProdutoDaoManager {
  final _$ProdutoDaoMixin _db;
  ProdutoDaoManager(this._db);
  $$ProdutosTableTableManager get produtos =>
      $$ProdutosTableTableManager(_db.attachedDatabase, _db.produtos);
}
