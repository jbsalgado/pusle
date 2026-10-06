// lib/core/database/tables/tables.dart
//
// Definição de TODAS as tabelas do banco SQLite local (drift).
// Mapeamento fiel das tabelas PostgreSQL do servidor Pulse.

import 'package:drift/drift.dart';

// ─────────────────────────────────────────────────────────────
// TENANTS (lojas/contas autenticadas no dispositivo)
// ─────────────────────────────────────────────────────────────
class Tenants extends Table {
  TextColumn get id => text()();
  TextColumn get nome => text()();
  TextColumn get tokenJwt => text()();
  TextColumn get urlServidor => text()();
  TextColumn get logoUrl => text().nullable()();
  TextColumn get ultimaSync => text().nullable()();
  BoolColumn get ativo => boolean().withDefault(const Constant(true))();

  @override
  Set<Column> get primaryKey => {id};
}

// ─────────────────────────────────────────────────────────────
// CATEGORIAS
// ─────────────────────────────────────────────────────────────
class Categorias extends Table {
  TextColumn get id => text()();
  TextColumn get usuarioId => text()();
  TextColumn get nome => text()();
  BoolColumn get ativo => boolean().withDefault(const Constant(true))();
  TextColumn get dataAtualizacao => text().nullable()();

  @override
  Set<Column> get primaryKey => {id};
}

// ─────────────────────────────────────────────────────────────
// PRODUTOS
// ─────────────────────────────────────────────────────────────
class Produtos extends Table {
  TextColumn get id => text()();
  TextColumn get usuarioId => text()();
  TextColumn get categoriaId => text().nullable()();
  TextColumn get nome => text()();
  TextColumn get codigoBarras => text().nullable()();
  TextColumn get codigoReferencia => text().nullable()();
  RealColumn get precoVendaSugerido => real().withDefault(const Constant(0.0))();
  RealColumn get precoCusto => real().withDefault(const Constant(0.0))();
  RealColumn get precoPromocional => real().withDefault(const Constant(0.0))();
  TextColumn get dataInicioPromocao => text().nullable()();
  TextColumn get dataFimPromocao => text().nullable()();
  RealColumn get precoVigente => real().withDefault(const Constant(0.0))();
  BoolColumn get emPromocao => boolean().withDefault(const Constant(false))();
  IntColumn get estoqueAtual => integer().withDefault(const Constant(0))();
  IntColumn get estoqueMinimo => integer().withDefault(const Constant(0))();
  TextColumn get unidadeMedida => text().withDefault(const Constant('UN'))();
  BoolColumn get vendaFracionada => boolean().withDefault(const Constant(false))();
  TextColumn get marca => text().nullable()();
  TextColumn get fotoUrl => text().nullable()();
  TextColumn get fotoLocalPath => text().nullable()();
  BoolColumn get ativo => boolean().withDefault(const Constant(true))();
  TextColumn get dataAtualizacao => text().nullable()();
  // Variantes serializadas como JSON (simplificação)
  TextColumn get variantesJson => text().nullable()();

  @override
  Set<Column> get primaryKey => {id};
}

// ─────────────────────────────────────────────────────────────
// CLIENTES
// ─────────────────────────────────────────────────────────────
class Clientes extends Table {
  TextColumn get id => text()();
  TextColumn get usuarioId => text()();
  TextColumn get nomeCompleto => text()();
  TextColumn get cpf => text().nullable()();
  TextColumn get telefone => text().nullable()();
  TextColumn get email => text().nullable()();
  TextColumn get enderecoLogradouro => text().nullable()();
  TextColumn get enderecoNumero => text().nullable()();
  TextColumn get enderecoBairro => text().nullable()();
  TextColumn get enderecoCidade => text().nullable()();
  TextColumn get enderecoEstado => text().nullable()();
  TextColumn get enderecoCep => text().nullable()();
  BoolColumn get ativo => boolean().withDefault(const Constant(true))();
  TextColumn get dataCriacao => text().nullable()();
  TextColumn get dataAtualizacao => text().nullable()();
  // 'synced' | 'pending' | 'error'
  TextColumn get syncStatus => text().withDefault(const Constant('synced'))();

  @override
  Set<Column> get primaryKey => {id};
}

// ─────────────────────────────────────────────────────────────
// FORMAS DE PAGAMENTO
// ─────────────────────────────────────────────────────────────
@DataClassName('FormaPagamento')
class FormasPagamento extends Table {
  TextColumn get id => text()();
  TextColumn get usuarioId => text()();
  TextColumn get nome => text()();
  TextColumn get tipo => text()();
  BoolColumn get ativo => boolean().withDefault(const Constant(true))();
  BoolColumn get aceitaParcelamento => boolean().withDefault(const Constant(false))();
  TextColumn get dataAtualizacao => text().nullable()();

  @override
  Set<Column> get primaryKey => {id};
}

// ─────────────────────────────────────────────────────────────
// VENDAS (coração offline)
// ─────────────────────────────────────────────────────────────
class Vendas extends Table {
  TextColumn get id => text()();
  TextColumn get usuarioId => text()();
  TextColumn get clienteId => text().nullable()();
  TextColumn get colaboradorVendedorId => text().nullable()();
  TextColumn get formaPagamentoId => text().nullable()();
  TextColumn get dataVenda => text()();
  RealColumn get valorTotal => real().withDefault(const Constant(0.0))();
  IntColumn get numeroParcelas => integer().withDefault(const Constant(1))();
  TextColumn get statusVendaCodigo => text().withDefault(const Constant('QUITADA'))();
  TextColumn get observacoes => text().nullable()();
  TextColumn get tipoVenda => text().withDefault(const Constant('BALCAO'))();
  RealColumn get acrescimoValor => real().withDefault(const Constant(0.0))();
  TextColumn get acrescimoTipo => text().nullable()();
  RealColumn get descontoGlobalValor => real().withDefault(const Constant(0.0))();
  TextColumn get descontoGlobalTipo => text().nullable()();
  TextColumn get cpfConsumidor => text().nullable()();
  TextColumn get dataPrimeiroVencimento => text().nullable()();
  TextColumn get dataCriacao => text()();
  TextColumn get dataAtualizacao => text().nullable()();
  // 'pending' | 'syncing' | 'synced' | 'error'
  TextColumn get syncStatus => text().withDefault(const Constant('pending'))();
  TextColumn get syncError => text().nullable()();
  IntColumn get syncTentativas => integer().withDefault(const Constant(0))();

  @override
  Set<Column> get primaryKey => {id};
}

// ─────────────────────────────────────────────────────────────
// ITENS DA VENDA
// ─────────────────────────────────────────────────────────────
@DataClassName('VendaItem')
class VendaItens extends Table {
  TextColumn get id => text()();
  TextColumn get vendaId => text()();
  TextColumn get produtoId => text().nullable()();
  TextColumn get varianteId => text().nullable()();
  TextColumn get nomeItemManual => text().nullable()();
  RealColumn get quantidade => real()();
  RealColumn get precoUnitarioVenda => real()();
  RealColumn get descontoPercentual => real().withDefault(const Constant(0.0))();
  RealColumn get descontoValor => real().withDefault(const Constant(0.0))();
  RealColumn get valorTotalItem => real().withDefault(const Constant(0.0))();

  @override
  Set<Column> get primaryKey => {id};
}

// ─────────────────────────────────────────────────────────────
// PARCELAS
// ─────────────────────────────────────────────────────────────
class Parcelas extends Table {
  TextColumn get id => text()();
  TextColumn get vendaId => text()();
  TextColumn get usuarioId => text()();
  IntColumn get numeroParcela => integer()();
  RealColumn get valorParcela => real()();
  TextColumn get dataVencimento => text()();
  TextColumn get statusParcelaCodigo => text().withDefault(const Constant('ABERTA'))();
  TextColumn get formaPagamentoId => text().nullable()();
  TextColumn get observacoes => text().nullable()();

  @override
  Set<Column> get primaryKey => {id};
}

// ─────────────────────────────────────────────────────────────
// FILA DE SYNC (log de operações offline pendentes)
// ─────────────────────────────────────────────────────────────
class SyncQueue extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get tabela => text()();
  TextColumn get registroId => text()();
  // 'INSERT' | 'UPDATE' | 'DELETE'
  TextColumn get operacao => text()();
  TextColumn get payload => text()(); // JSON serializado
  IntColumn get tentativas => integer().withDefault(const Constant(0))();
  TextColumn get ultimoErro => text().nullable()();
  TextColumn get criadoEm => text()();
}
