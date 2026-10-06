// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'venda_dao.dart';

// ignore_for_file: type=lint
mixin _$VendaDaoMixin on DatabaseAccessor<AppDatabase> {
  $VendasTable get vendas => attachedDatabase.vendas;
  $VendaItensTable get vendaItens => attachedDatabase.vendaItens;
  $ParcelasTable get parcelas => attachedDatabase.parcelas;
  VendaDaoManager get managers => VendaDaoManager(this);
}

class VendaDaoManager {
  final _$VendaDaoMixin _db;
  VendaDaoManager(this._db);
  $$VendasTableTableManager get vendas =>
      $$VendasTableTableManager(_db.attachedDatabase, _db.vendas);
  $$VendaItensTableTableManager get vendaItens =>
      $$VendaItensTableTableManager(_db.attachedDatabase, _db.vendaItens);
  $$ParcelasTableTableManager get parcelas =>
      $$ParcelasTableTableManager(_db.attachedDatabase, _db.parcelas);
}
