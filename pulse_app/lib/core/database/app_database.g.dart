// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'app_database.dart';

// ignore_for_file: type=lint
class $TenantsTable extends Tenants with TableInfo<$TenantsTable, Tenant> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $TenantsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _nomeMeta = const VerificationMeta('nome');
  @override
  late final GeneratedColumn<String> nome = GeneratedColumn<String>(
    'nome',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _tokenJwtMeta = const VerificationMeta(
    'tokenJwt',
  );
  @override
  late final GeneratedColumn<String> tokenJwt = GeneratedColumn<String>(
    'token_jwt',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _urlServidorMeta = const VerificationMeta(
    'urlServidor',
  );
  @override
  late final GeneratedColumn<String> urlServidor = GeneratedColumn<String>(
    'url_servidor',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _logoUrlMeta = const VerificationMeta(
    'logoUrl',
  );
  @override
  late final GeneratedColumn<String> logoUrl = GeneratedColumn<String>(
    'logo_url',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _ultimaSyncMeta = const VerificationMeta(
    'ultimaSync',
  );
  @override
  late final GeneratedColumn<String> ultimaSync = GeneratedColumn<String>(
    'ultima_sync',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _ativoMeta = const VerificationMeta('ativo');
  @override
  late final GeneratedColumn<bool> ativo = GeneratedColumn<bool>(
    'ativo',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("ativo" IN (0, 1))',
    ),
    defaultValue: const Constant(true),
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    nome,
    tokenJwt,
    urlServidor,
    logoUrl,
    ultimaSync,
    ativo,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'tenants';
  @override
  VerificationContext validateIntegrity(
    Insertable<Tenant> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('nome')) {
      context.handle(
        _nomeMeta,
        nome.isAcceptableOrUnknown(data['nome']!, _nomeMeta),
      );
    } else if (isInserting) {
      context.missing(_nomeMeta);
    }
    if (data.containsKey('token_jwt')) {
      context.handle(
        _tokenJwtMeta,
        tokenJwt.isAcceptableOrUnknown(data['token_jwt']!, _tokenJwtMeta),
      );
    } else if (isInserting) {
      context.missing(_tokenJwtMeta);
    }
    if (data.containsKey('url_servidor')) {
      context.handle(
        _urlServidorMeta,
        urlServidor.isAcceptableOrUnknown(
          data['url_servidor']!,
          _urlServidorMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_urlServidorMeta);
    }
    if (data.containsKey('logo_url')) {
      context.handle(
        _logoUrlMeta,
        logoUrl.isAcceptableOrUnknown(data['logo_url']!, _logoUrlMeta),
      );
    }
    if (data.containsKey('ultima_sync')) {
      context.handle(
        _ultimaSyncMeta,
        ultimaSync.isAcceptableOrUnknown(data['ultima_sync']!, _ultimaSyncMeta),
      );
    }
    if (data.containsKey('ativo')) {
      context.handle(
        _ativoMeta,
        ativo.isAcceptableOrUnknown(data['ativo']!, _ativoMeta),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  Tenant map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return Tenant(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      nome: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}nome'],
      )!,
      tokenJwt: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}token_jwt'],
      )!,
      urlServidor: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}url_servidor'],
      )!,
      logoUrl: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}logo_url'],
      ),
      ultimaSync: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}ultima_sync'],
      ),
      ativo: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}ativo'],
      )!,
    );
  }

  @override
  $TenantsTable createAlias(String alias) {
    return $TenantsTable(attachedDatabase, alias);
  }
}

class Tenant extends DataClass implements Insertable<Tenant> {
  final String id;
  final String nome;
  final String tokenJwt;
  final String urlServidor;
  final String? logoUrl;
  final String? ultimaSync;
  final bool ativo;
  const Tenant({
    required this.id,
    required this.nome,
    required this.tokenJwt,
    required this.urlServidor,
    this.logoUrl,
    this.ultimaSync,
    required this.ativo,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['nome'] = Variable<String>(nome);
    map['token_jwt'] = Variable<String>(tokenJwt);
    map['url_servidor'] = Variable<String>(urlServidor);
    if (!nullToAbsent || logoUrl != null) {
      map['logo_url'] = Variable<String>(logoUrl);
    }
    if (!nullToAbsent || ultimaSync != null) {
      map['ultima_sync'] = Variable<String>(ultimaSync);
    }
    map['ativo'] = Variable<bool>(ativo);
    return map;
  }

  TenantsCompanion toCompanion(bool nullToAbsent) {
    return TenantsCompanion(
      id: Value(id),
      nome: Value(nome),
      tokenJwt: Value(tokenJwt),
      urlServidor: Value(urlServidor),
      logoUrl: logoUrl == null && nullToAbsent
          ? const Value.absent()
          : Value(logoUrl),
      ultimaSync: ultimaSync == null && nullToAbsent
          ? const Value.absent()
          : Value(ultimaSync),
      ativo: Value(ativo),
    );
  }

  factory Tenant.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return Tenant(
      id: serializer.fromJson<String>(json['id']),
      nome: serializer.fromJson<String>(json['nome']),
      tokenJwt: serializer.fromJson<String>(json['tokenJwt']),
      urlServidor: serializer.fromJson<String>(json['urlServidor']),
      logoUrl: serializer.fromJson<String?>(json['logoUrl']),
      ultimaSync: serializer.fromJson<String?>(json['ultimaSync']),
      ativo: serializer.fromJson<bool>(json['ativo']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'nome': serializer.toJson<String>(nome),
      'tokenJwt': serializer.toJson<String>(tokenJwt),
      'urlServidor': serializer.toJson<String>(urlServidor),
      'logoUrl': serializer.toJson<String?>(logoUrl),
      'ultimaSync': serializer.toJson<String?>(ultimaSync),
      'ativo': serializer.toJson<bool>(ativo),
    };
  }

  Tenant copyWith({
    String? id,
    String? nome,
    String? tokenJwt,
    String? urlServidor,
    Value<String?> logoUrl = const Value.absent(),
    Value<String?> ultimaSync = const Value.absent(),
    bool? ativo,
  }) => Tenant(
    id: id ?? this.id,
    nome: nome ?? this.nome,
    tokenJwt: tokenJwt ?? this.tokenJwt,
    urlServidor: urlServidor ?? this.urlServidor,
    logoUrl: logoUrl.present ? logoUrl.value : this.logoUrl,
    ultimaSync: ultimaSync.present ? ultimaSync.value : this.ultimaSync,
    ativo: ativo ?? this.ativo,
  );
  Tenant copyWithCompanion(TenantsCompanion data) {
    return Tenant(
      id: data.id.present ? data.id.value : this.id,
      nome: data.nome.present ? data.nome.value : this.nome,
      tokenJwt: data.tokenJwt.present ? data.tokenJwt.value : this.tokenJwt,
      urlServidor: data.urlServidor.present
          ? data.urlServidor.value
          : this.urlServidor,
      logoUrl: data.logoUrl.present ? data.logoUrl.value : this.logoUrl,
      ultimaSync: data.ultimaSync.present
          ? data.ultimaSync.value
          : this.ultimaSync,
      ativo: data.ativo.present ? data.ativo.value : this.ativo,
    );
  }

  @override
  String toString() {
    return (StringBuffer('Tenant(')
          ..write('id: $id, ')
          ..write('nome: $nome, ')
          ..write('tokenJwt: $tokenJwt, ')
          ..write('urlServidor: $urlServidor, ')
          ..write('logoUrl: $logoUrl, ')
          ..write('ultimaSync: $ultimaSync, ')
          ..write('ativo: $ativo')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode =>
      Object.hash(id, nome, tokenJwt, urlServidor, logoUrl, ultimaSync, ativo);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is Tenant &&
          other.id == this.id &&
          other.nome == this.nome &&
          other.tokenJwt == this.tokenJwt &&
          other.urlServidor == this.urlServidor &&
          other.logoUrl == this.logoUrl &&
          other.ultimaSync == this.ultimaSync &&
          other.ativo == this.ativo);
}

class TenantsCompanion extends UpdateCompanion<Tenant> {
  final Value<String> id;
  final Value<String> nome;
  final Value<String> tokenJwt;
  final Value<String> urlServidor;
  final Value<String?> logoUrl;
  final Value<String?> ultimaSync;
  final Value<bool> ativo;
  final Value<int> rowid;
  const TenantsCompanion({
    this.id = const Value.absent(),
    this.nome = const Value.absent(),
    this.tokenJwt = const Value.absent(),
    this.urlServidor = const Value.absent(),
    this.logoUrl = const Value.absent(),
    this.ultimaSync = const Value.absent(),
    this.ativo = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  TenantsCompanion.insert({
    required String id,
    required String nome,
    required String tokenJwt,
    required String urlServidor,
    this.logoUrl = const Value.absent(),
    this.ultimaSync = const Value.absent(),
    this.ativo = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       nome = Value(nome),
       tokenJwt = Value(tokenJwt),
       urlServidor = Value(urlServidor);
  static Insertable<Tenant> custom({
    Expression<String>? id,
    Expression<String>? nome,
    Expression<String>? tokenJwt,
    Expression<String>? urlServidor,
    Expression<String>? logoUrl,
    Expression<String>? ultimaSync,
    Expression<bool>? ativo,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (nome != null) 'nome': nome,
      if (tokenJwt != null) 'token_jwt': tokenJwt,
      if (urlServidor != null) 'url_servidor': urlServidor,
      if (logoUrl != null) 'logo_url': logoUrl,
      if (ultimaSync != null) 'ultima_sync': ultimaSync,
      if (ativo != null) 'ativo': ativo,
      if (rowid != null) 'rowid': rowid,
    });
  }

  TenantsCompanion copyWith({
    Value<String>? id,
    Value<String>? nome,
    Value<String>? tokenJwt,
    Value<String>? urlServidor,
    Value<String?>? logoUrl,
    Value<String?>? ultimaSync,
    Value<bool>? ativo,
    Value<int>? rowid,
  }) {
    return TenantsCompanion(
      id: id ?? this.id,
      nome: nome ?? this.nome,
      tokenJwt: tokenJwt ?? this.tokenJwt,
      urlServidor: urlServidor ?? this.urlServidor,
      logoUrl: logoUrl ?? this.logoUrl,
      ultimaSync: ultimaSync ?? this.ultimaSync,
      ativo: ativo ?? this.ativo,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (nome.present) {
      map['nome'] = Variable<String>(nome.value);
    }
    if (tokenJwt.present) {
      map['token_jwt'] = Variable<String>(tokenJwt.value);
    }
    if (urlServidor.present) {
      map['url_servidor'] = Variable<String>(urlServidor.value);
    }
    if (logoUrl.present) {
      map['logo_url'] = Variable<String>(logoUrl.value);
    }
    if (ultimaSync.present) {
      map['ultima_sync'] = Variable<String>(ultimaSync.value);
    }
    if (ativo.present) {
      map['ativo'] = Variable<bool>(ativo.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('TenantsCompanion(')
          ..write('id: $id, ')
          ..write('nome: $nome, ')
          ..write('tokenJwt: $tokenJwt, ')
          ..write('urlServidor: $urlServidor, ')
          ..write('logoUrl: $logoUrl, ')
          ..write('ultimaSync: $ultimaSync, ')
          ..write('ativo: $ativo, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $CategoriasTable extends Categorias
    with TableInfo<$CategoriasTable, Categoria> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $CategoriasTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _usuarioIdMeta = const VerificationMeta(
    'usuarioId',
  );
  @override
  late final GeneratedColumn<String> usuarioId = GeneratedColumn<String>(
    'usuario_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _nomeMeta = const VerificationMeta('nome');
  @override
  late final GeneratedColumn<String> nome = GeneratedColumn<String>(
    'nome',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _ativoMeta = const VerificationMeta('ativo');
  @override
  late final GeneratedColumn<bool> ativo = GeneratedColumn<bool>(
    'ativo',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("ativo" IN (0, 1))',
    ),
    defaultValue: const Constant(true),
  );
  static const VerificationMeta _dataAtualizacaoMeta = const VerificationMeta(
    'dataAtualizacao',
  );
  @override
  late final GeneratedColumn<String> dataAtualizacao = GeneratedColumn<String>(
    'data_atualizacao',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    usuarioId,
    nome,
    ativo,
    dataAtualizacao,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'categorias';
  @override
  VerificationContext validateIntegrity(
    Insertable<Categoria> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('usuario_id')) {
      context.handle(
        _usuarioIdMeta,
        usuarioId.isAcceptableOrUnknown(data['usuario_id']!, _usuarioIdMeta),
      );
    } else if (isInserting) {
      context.missing(_usuarioIdMeta);
    }
    if (data.containsKey('nome')) {
      context.handle(
        _nomeMeta,
        nome.isAcceptableOrUnknown(data['nome']!, _nomeMeta),
      );
    } else if (isInserting) {
      context.missing(_nomeMeta);
    }
    if (data.containsKey('ativo')) {
      context.handle(
        _ativoMeta,
        ativo.isAcceptableOrUnknown(data['ativo']!, _ativoMeta),
      );
    }
    if (data.containsKey('data_atualizacao')) {
      context.handle(
        _dataAtualizacaoMeta,
        dataAtualizacao.isAcceptableOrUnknown(
          data['data_atualizacao']!,
          _dataAtualizacaoMeta,
        ),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  Categoria map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return Categoria(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      usuarioId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}usuario_id'],
      )!,
      nome: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}nome'],
      )!,
      ativo: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}ativo'],
      )!,
      dataAtualizacao: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_atualizacao'],
      ),
    );
  }

  @override
  $CategoriasTable createAlias(String alias) {
    return $CategoriasTable(attachedDatabase, alias);
  }
}

class Categoria extends DataClass implements Insertable<Categoria> {
  final String id;
  final String usuarioId;
  final String nome;
  final bool ativo;
  final String? dataAtualizacao;
  const Categoria({
    required this.id,
    required this.usuarioId,
    required this.nome,
    required this.ativo,
    this.dataAtualizacao,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['usuario_id'] = Variable<String>(usuarioId);
    map['nome'] = Variable<String>(nome);
    map['ativo'] = Variable<bool>(ativo);
    if (!nullToAbsent || dataAtualizacao != null) {
      map['data_atualizacao'] = Variable<String>(dataAtualizacao);
    }
    return map;
  }

  CategoriasCompanion toCompanion(bool nullToAbsent) {
    return CategoriasCompanion(
      id: Value(id),
      usuarioId: Value(usuarioId),
      nome: Value(nome),
      ativo: Value(ativo),
      dataAtualizacao: dataAtualizacao == null && nullToAbsent
          ? const Value.absent()
          : Value(dataAtualizacao),
    );
  }

  factory Categoria.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return Categoria(
      id: serializer.fromJson<String>(json['id']),
      usuarioId: serializer.fromJson<String>(json['usuarioId']),
      nome: serializer.fromJson<String>(json['nome']),
      ativo: serializer.fromJson<bool>(json['ativo']),
      dataAtualizacao: serializer.fromJson<String?>(json['dataAtualizacao']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'usuarioId': serializer.toJson<String>(usuarioId),
      'nome': serializer.toJson<String>(nome),
      'ativo': serializer.toJson<bool>(ativo),
      'dataAtualizacao': serializer.toJson<String?>(dataAtualizacao),
    };
  }

  Categoria copyWith({
    String? id,
    String? usuarioId,
    String? nome,
    bool? ativo,
    Value<String?> dataAtualizacao = const Value.absent(),
  }) => Categoria(
    id: id ?? this.id,
    usuarioId: usuarioId ?? this.usuarioId,
    nome: nome ?? this.nome,
    ativo: ativo ?? this.ativo,
    dataAtualizacao: dataAtualizacao.present
        ? dataAtualizacao.value
        : this.dataAtualizacao,
  );
  Categoria copyWithCompanion(CategoriasCompanion data) {
    return Categoria(
      id: data.id.present ? data.id.value : this.id,
      usuarioId: data.usuarioId.present ? data.usuarioId.value : this.usuarioId,
      nome: data.nome.present ? data.nome.value : this.nome,
      ativo: data.ativo.present ? data.ativo.value : this.ativo,
      dataAtualizacao: data.dataAtualizacao.present
          ? data.dataAtualizacao.value
          : this.dataAtualizacao,
    );
  }

  @override
  String toString() {
    return (StringBuffer('Categoria(')
          ..write('id: $id, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('nome: $nome, ')
          ..write('ativo: $ativo, ')
          ..write('dataAtualizacao: $dataAtualizacao')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(id, usuarioId, nome, ativo, dataAtualizacao);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is Categoria &&
          other.id == this.id &&
          other.usuarioId == this.usuarioId &&
          other.nome == this.nome &&
          other.ativo == this.ativo &&
          other.dataAtualizacao == this.dataAtualizacao);
}

class CategoriasCompanion extends UpdateCompanion<Categoria> {
  final Value<String> id;
  final Value<String> usuarioId;
  final Value<String> nome;
  final Value<bool> ativo;
  final Value<String?> dataAtualizacao;
  final Value<int> rowid;
  const CategoriasCompanion({
    this.id = const Value.absent(),
    this.usuarioId = const Value.absent(),
    this.nome = const Value.absent(),
    this.ativo = const Value.absent(),
    this.dataAtualizacao = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  CategoriasCompanion.insert({
    required String id,
    required String usuarioId,
    required String nome,
    this.ativo = const Value.absent(),
    this.dataAtualizacao = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       usuarioId = Value(usuarioId),
       nome = Value(nome);
  static Insertable<Categoria> custom({
    Expression<String>? id,
    Expression<String>? usuarioId,
    Expression<String>? nome,
    Expression<bool>? ativo,
    Expression<String>? dataAtualizacao,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (usuarioId != null) 'usuario_id': usuarioId,
      if (nome != null) 'nome': nome,
      if (ativo != null) 'ativo': ativo,
      if (dataAtualizacao != null) 'data_atualizacao': dataAtualizacao,
      if (rowid != null) 'rowid': rowid,
    });
  }

  CategoriasCompanion copyWith({
    Value<String>? id,
    Value<String>? usuarioId,
    Value<String>? nome,
    Value<bool>? ativo,
    Value<String?>? dataAtualizacao,
    Value<int>? rowid,
  }) {
    return CategoriasCompanion(
      id: id ?? this.id,
      usuarioId: usuarioId ?? this.usuarioId,
      nome: nome ?? this.nome,
      ativo: ativo ?? this.ativo,
      dataAtualizacao: dataAtualizacao ?? this.dataAtualizacao,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (usuarioId.present) {
      map['usuario_id'] = Variable<String>(usuarioId.value);
    }
    if (nome.present) {
      map['nome'] = Variable<String>(nome.value);
    }
    if (ativo.present) {
      map['ativo'] = Variable<bool>(ativo.value);
    }
    if (dataAtualizacao.present) {
      map['data_atualizacao'] = Variable<String>(dataAtualizacao.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('CategoriasCompanion(')
          ..write('id: $id, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('nome: $nome, ')
          ..write('ativo: $ativo, ')
          ..write('dataAtualizacao: $dataAtualizacao, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $ProdutosTable extends Produtos with TableInfo<$ProdutosTable, Produto> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $ProdutosTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _usuarioIdMeta = const VerificationMeta(
    'usuarioId',
  );
  @override
  late final GeneratedColumn<String> usuarioId = GeneratedColumn<String>(
    'usuario_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _categoriaIdMeta = const VerificationMeta(
    'categoriaId',
  );
  @override
  late final GeneratedColumn<String> categoriaId = GeneratedColumn<String>(
    'categoria_id',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _nomeMeta = const VerificationMeta('nome');
  @override
  late final GeneratedColumn<String> nome = GeneratedColumn<String>(
    'nome',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _codigoBarrasMeta = const VerificationMeta(
    'codigoBarras',
  );
  @override
  late final GeneratedColumn<String> codigoBarras = GeneratedColumn<String>(
    'codigo_barras',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _codigoReferenciaMeta = const VerificationMeta(
    'codigoReferencia',
  );
  @override
  late final GeneratedColumn<String> codigoReferencia = GeneratedColumn<String>(
    'codigo_referencia',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _precoVendaSugeridoMeta =
      const VerificationMeta('precoVendaSugerido');
  @override
  late final GeneratedColumn<double> precoVendaSugerido =
      GeneratedColumn<double>(
        'preco_venda_sugerido',
        aliasedName,
        false,
        type: DriftSqlType.double,
        requiredDuringInsert: false,
        defaultValue: const Constant(0.0),
      );
  static const VerificationMeta _precoCustoMeta = const VerificationMeta(
    'precoCusto',
  );
  @override
  late final GeneratedColumn<double> precoCusto = GeneratedColumn<double>(
    'preco_custo',
    aliasedName,
    false,
    type: DriftSqlType.double,
    requiredDuringInsert: false,
    defaultValue: const Constant(0.0),
  );
  static const VerificationMeta _precoPromocionalMeta = const VerificationMeta(
    'precoPromocional',
  );
  @override
  late final GeneratedColumn<double> precoPromocional = GeneratedColumn<double>(
    'preco_promocional',
    aliasedName,
    false,
    type: DriftSqlType.double,
    requiredDuringInsert: false,
    defaultValue: const Constant(0.0),
  );
  static const VerificationMeta _dataInicioPromocaoMeta =
      const VerificationMeta('dataInicioPromocao');
  @override
  late final GeneratedColumn<String> dataInicioPromocao =
      GeneratedColumn<String>(
        'data_inicio_promocao',
        aliasedName,
        true,
        type: DriftSqlType.string,
        requiredDuringInsert: false,
      );
  static const VerificationMeta _dataFimPromocaoMeta = const VerificationMeta(
    'dataFimPromocao',
  );
  @override
  late final GeneratedColumn<String> dataFimPromocao = GeneratedColumn<String>(
    'data_fim_promocao',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _precoVigenteMeta = const VerificationMeta(
    'precoVigente',
  );
  @override
  late final GeneratedColumn<double> precoVigente = GeneratedColumn<double>(
    'preco_vigente',
    aliasedName,
    false,
    type: DriftSqlType.double,
    requiredDuringInsert: false,
    defaultValue: const Constant(0.0),
  );
  static const VerificationMeta _emPromocaoMeta = const VerificationMeta(
    'emPromocao',
  );
  @override
  late final GeneratedColumn<bool> emPromocao = GeneratedColumn<bool>(
    'em_promocao',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("em_promocao" IN (0, 1))',
    ),
    defaultValue: const Constant(false),
  );
  static const VerificationMeta _estoqueAtualMeta = const VerificationMeta(
    'estoqueAtual',
  );
  @override
  late final GeneratedColumn<int> estoqueAtual = GeneratedColumn<int>(
    'estoque_atual',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  static const VerificationMeta _estoqueMinimoMeta = const VerificationMeta(
    'estoqueMinimo',
  );
  @override
  late final GeneratedColumn<int> estoqueMinimo = GeneratedColumn<int>(
    'estoque_minimo',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  static const VerificationMeta _unidadeMedidaMeta = const VerificationMeta(
    'unidadeMedida',
  );
  @override
  late final GeneratedColumn<String> unidadeMedida = GeneratedColumn<String>(
    'unidade_medida',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
    defaultValue: const Constant('UN'),
  );
  static const VerificationMeta _vendaFracionadaMeta = const VerificationMeta(
    'vendaFracionada',
  );
  @override
  late final GeneratedColumn<bool> vendaFracionada = GeneratedColumn<bool>(
    'venda_fracionada',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("venda_fracionada" IN (0, 1))',
    ),
    defaultValue: const Constant(false),
  );
  static const VerificationMeta _marcaMeta = const VerificationMeta('marca');
  @override
  late final GeneratedColumn<String> marca = GeneratedColumn<String>(
    'marca',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _fotoUrlMeta = const VerificationMeta(
    'fotoUrl',
  );
  @override
  late final GeneratedColumn<String> fotoUrl = GeneratedColumn<String>(
    'foto_url',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _fotoLocalPathMeta = const VerificationMeta(
    'fotoLocalPath',
  );
  @override
  late final GeneratedColumn<String> fotoLocalPath = GeneratedColumn<String>(
    'foto_local_path',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _ativoMeta = const VerificationMeta('ativo');
  @override
  late final GeneratedColumn<bool> ativo = GeneratedColumn<bool>(
    'ativo',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("ativo" IN (0, 1))',
    ),
    defaultValue: const Constant(true),
  );
  static const VerificationMeta _dataAtualizacaoMeta = const VerificationMeta(
    'dataAtualizacao',
  );
  @override
  late final GeneratedColumn<String> dataAtualizacao = GeneratedColumn<String>(
    'data_atualizacao',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _variantesJsonMeta = const VerificationMeta(
    'variantesJson',
  );
  @override
  late final GeneratedColumn<String> variantesJson = GeneratedColumn<String>(
    'variantes_json',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    usuarioId,
    categoriaId,
    nome,
    codigoBarras,
    codigoReferencia,
    precoVendaSugerido,
    precoCusto,
    precoPromocional,
    dataInicioPromocao,
    dataFimPromocao,
    precoVigente,
    emPromocao,
    estoqueAtual,
    estoqueMinimo,
    unidadeMedida,
    vendaFracionada,
    marca,
    fotoUrl,
    fotoLocalPath,
    ativo,
    dataAtualizacao,
    variantesJson,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'produtos';
  @override
  VerificationContext validateIntegrity(
    Insertable<Produto> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('usuario_id')) {
      context.handle(
        _usuarioIdMeta,
        usuarioId.isAcceptableOrUnknown(data['usuario_id']!, _usuarioIdMeta),
      );
    } else if (isInserting) {
      context.missing(_usuarioIdMeta);
    }
    if (data.containsKey('categoria_id')) {
      context.handle(
        _categoriaIdMeta,
        categoriaId.isAcceptableOrUnknown(
          data['categoria_id']!,
          _categoriaIdMeta,
        ),
      );
    }
    if (data.containsKey('nome')) {
      context.handle(
        _nomeMeta,
        nome.isAcceptableOrUnknown(data['nome']!, _nomeMeta),
      );
    } else if (isInserting) {
      context.missing(_nomeMeta);
    }
    if (data.containsKey('codigo_barras')) {
      context.handle(
        _codigoBarrasMeta,
        codigoBarras.isAcceptableOrUnknown(
          data['codigo_barras']!,
          _codigoBarrasMeta,
        ),
      );
    }
    if (data.containsKey('codigo_referencia')) {
      context.handle(
        _codigoReferenciaMeta,
        codigoReferencia.isAcceptableOrUnknown(
          data['codigo_referencia']!,
          _codigoReferenciaMeta,
        ),
      );
    }
    if (data.containsKey('preco_venda_sugerido')) {
      context.handle(
        _precoVendaSugeridoMeta,
        precoVendaSugerido.isAcceptableOrUnknown(
          data['preco_venda_sugerido']!,
          _precoVendaSugeridoMeta,
        ),
      );
    }
    if (data.containsKey('preco_custo')) {
      context.handle(
        _precoCustoMeta,
        precoCusto.isAcceptableOrUnknown(data['preco_custo']!, _precoCustoMeta),
      );
    }
    if (data.containsKey('preco_promocional')) {
      context.handle(
        _precoPromocionalMeta,
        precoPromocional.isAcceptableOrUnknown(
          data['preco_promocional']!,
          _precoPromocionalMeta,
        ),
      );
    }
    if (data.containsKey('data_inicio_promocao')) {
      context.handle(
        _dataInicioPromocaoMeta,
        dataInicioPromocao.isAcceptableOrUnknown(
          data['data_inicio_promocao']!,
          _dataInicioPromocaoMeta,
        ),
      );
    }
    if (data.containsKey('data_fim_promocao')) {
      context.handle(
        _dataFimPromocaoMeta,
        dataFimPromocao.isAcceptableOrUnknown(
          data['data_fim_promocao']!,
          _dataFimPromocaoMeta,
        ),
      );
    }
    if (data.containsKey('preco_vigente')) {
      context.handle(
        _precoVigenteMeta,
        precoVigente.isAcceptableOrUnknown(
          data['preco_vigente']!,
          _precoVigenteMeta,
        ),
      );
    }
    if (data.containsKey('em_promocao')) {
      context.handle(
        _emPromocaoMeta,
        emPromocao.isAcceptableOrUnknown(data['em_promocao']!, _emPromocaoMeta),
      );
    }
    if (data.containsKey('estoque_atual')) {
      context.handle(
        _estoqueAtualMeta,
        estoqueAtual.isAcceptableOrUnknown(
          data['estoque_atual']!,
          _estoqueAtualMeta,
        ),
      );
    }
    if (data.containsKey('estoque_minimo')) {
      context.handle(
        _estoqueMinimoMeta,
        estoqueMinimo.isAcceptableOrUnknown(
          data['estoque_minimo']!,
          _estoqueMinimoMeta,
        ),
      );
    }
    if (data.containsKey('unidade_medida')) {
      context.handle(
        _unidadeMedidaMeta,
        unidadeMedida.isAcceptableOrUnknown(
          data['unidade_medida']!,
          _unidadeMedidaMeta,
        ),
      );
    }
    if (data.containsKey('venda_fracionada')) {
      context.handle(
        _vendaFracionadaMeta,
        vendaFracionada.isAcceptableOrUnknown(
          data['venda_fracionada']!,
          _vendaFracionadaMeta,
        ),
      );
    }
    if (data.containsKey('marca')) {
      context.handle(
        _marcaMeta,
        marca.isAcceptableOrUnknown(data['marca']!, _marcaMeta),
      );
    }
    if (data.containsKey('foto_url')) {
      context.handle(
        _fotoUrlMeta,
        fotoUrl.isAcceptableOrUnknown(data['foto_url']!, _fotoUrlMeta),
      );
    }
    if (data.containsKey('foto_local_path')) {
      context.handle(
        _fotoLocalPathMeta,
        fotoLocalPath.isAcceptableOrUnknown(
          data['foto_local_path']!,
          _fotoLocalPathMeta,
        ),
      );
    }
    if (data.containsKey('ativo')) {
      context.handle(
        _ativoMeta,
        ativo.isAcceptableOrUnknown(data['ativo']!, _ativoMeta),
      );
    }
    if (data.containsKey('data_atualizacao')) {
      context.handle(
        _dataAtualizacaoMeta,
        dataAtualizacao.isAcceptableOrUnknown(
          data['data_atualizacao']!,
          _dataAtualizacaoMeta,
        ),
      );
    }
    if (data.containsKey('variantes_json')) {
      context.handle(
        _variantesJsonMeta,
        variantesJson.isAcceptableOrUnknown(
          data['variantes_json']!,
          _variantesJsonMeta,
        ),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  Produto map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return Produto(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      usuarioId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}usuario_id'],
      )!,
      categoriaId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}categoria_id'],
      ),
      nome: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}nome'],
      )!,
      codigoBarras: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}codigo_barras'],
      ),
      codigoReferencia: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}codigo_referencia'],
      ),
      precoVendaSugerido: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}preco_venda_sugerido'],
      )!,
      precoCusto: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}preco_custo'],
      )!,
      precoPromocional: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}preco_promocional'],
      )!,
      dataInicioPromocao: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_inicio_promocao'],
      ),
      dataFimPromocao: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_fim_promocao'],
      ),
      precoVigente: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}preco_vigente'],
      )!,
      emPromocao: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}em_promocao'],
      )!,
      estoqueAtual: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}estoque_atual'],
      )!,
      estoqueMinimo: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}estoque_minimo'],
      )!,
      unidadeMedida: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}unidade_medida'],
      )!,
      vendaFracionada: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}venda_fracionada'],
      )!,
      marca: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}marca'],
      ),
      fotoUrl: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}foto_url'],
      ),
      fotoLocalPath: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}foto_local_path'],
      ),
      ativo: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}ativo'],
      )!,
      dataAtualizacao: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_atualizacao'],
      ),
      variantesJson: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}variantes_json'],
      ),
    );
  }

  @override
  $ProdutosTable createAlias(String alias) {
    return $ProdutosTable(attachedDatabase, alias);
  }
}

class Produto extends DataClass implements Insertable<Produto> {
  final String id;
  final String usuarioId;
  final String? categoriaId;
  final String nome;
  final String? codigoBarras;
  final String? codigoReferencia;
  final double precoVendaSugerido;
  final double precoCusto;
  final double precoPromocional;
  final String? dataInicioPromocao;
  final String? dataFimPromocao;
  final double precoVigente;
  final bool emPromocao;
  final int estoqueAtual;
  final int estoqueMinimo;
  final String unidadeMedida;
  final bool vendaFracionada;
  final String? marca;
  final String? fotoUrl;
  final String? fotoLocalPath;
  final bool ativo;
  final String? dataAtualizacao;
  final String? variantesJson;
  const Produto({
    required this.id,
    required this.usuarioId,
    this.categoriaId,
    required this.nome,
    this.codigoBarras,
    this.codigoReferencia,
    required this.precoVendaSugerido,
    required this.precoCusto,
    required this.precoPromocional,
    this.dataInicioPromocao,
    this.dataFimPromocao,
    required this.precoVigente,
    required this.emPromocao,
    required this.estoqueAtual,
    required this.estoqueMinimo,
    required this.unidadeMedida,
    required this.vendaFracionada,
    this.marca,
    this.fotoUrl,
    this.fotoLocalPath,
    required this.ativo,
    this.dataAtualizacao,
    this.variantesJson,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['usuario_id'] = Variable<String>(usuarioId);
    if (!nullToAbsent || categoriaId != null) {
      map['categoria_id'] = Variable<String>(categoriaId);
    }
    map['nome'] = Variable<String>(nome);
    if (!nullToAbsent || codigoBarras != null) {
      map['codigo_barras'] = Variable<String>(codigoBarras);
    }
    if (!nullToAbsent || codigoReferencia != null) {
      map['codigo_referencia'] = Variable<String>(codigoReferencia);
    }
    map['preco_venda_sugerido'] = Variable<double>(precoVendaSugerido);
    map['preco_custo'] = Variable<double>(precoCusto);
    map['preco_promocional'] = Variable<double>(precoPromocional);
    if (!nullToAbsent || dataInicioPromocao != null) {
      map['data_inicio_promocao'] = Variable<String>(dataInicioPromocao);
    }
    if (!nullToAbsent || dataFimPromocao != null) {
      map['data_fim_promocao'] = Variable<String>(dataFimPromocao);
    }
    map['preco_vigente'] = Variable<double>(precoVigente);
    map['em_promocao'] = Variable<bool>(emPromocao);
    map['estoque_atual'] = Variable<int>(estoqueAtual);
    map['estoque_minimo'] = Variable<int>(estoqueMinimo);
    map['unidade_medida'] = Variable<String>(unidadeMedida);
    map['venda_fracionada'] = Variable<bool>(vendaFracionada);
    if (!nullToAbsent || marca != null) {
      map['marca'] = Variable<String>(marca);
    }
    if (!nullToAbsent || fotoUrl != null) {
      map['foto_url'] = Variable<String>(fotoUrl);
    }
    if (!nullToAbsent || fotoLocalPath != null) {
      map['foto_local_path'] = Variable<String>(fotoLocalPath);
    }
    map['ativo'] = Variable<bool>(ativo);
    if (!nullToAbsent || dataAtualizacao != null) {
      map['data_atualizacao'] = Variable<String>(dataAtualizacao);
    }
    if (!nullToAbsent || variantesJson != null) {
      map['variantes_json'] = Variable<String>(variantesJson);
    }
    return map;
  }

  ProdutosCompanion toCompanion(bool nullToAbsent) {
    return ProdutosCompanion(
      id: Value(id),
      usuarioId: Value(usuarioId),
      categoriaId: categoriaId == null && nullToAbsent
          ? const Value.absent()
          : Value(categoriaId),
      nome: Value(nome),
      codigoBarras: codigoBarras == null && nullToAbsent
          ? const Value.absent()
          : Value(codigoBarras),
      codigoReferencia: codigoReferencia == null && nullToAbsent
          ? const Value.absent()
          : Value(codigoReferencia),
      precoVendaSugerido: Value(precoVendaSugerido),
      precoCusto: Value(precoCusto),
      precoPromocional: Value(precoPromocional),
      dataInicioPromocao: dataInicioPromocao == null && nullToAbsent
          ? const Value.absent()
          : Value(dataInicioPromocao),
      dataFimPromocao: dataFimPromocao == null && nullToAbsent
          ? const Value.absent()
          : Value(dataFimPromocao),
      precoVigente: Value(precoVigente),
      emPromocao: Value(emPromocao),
      estoqueAtual: Value(estoqueAtual),
      estoqueMinimo: Value(estoqueMinimo),
      unidadeMedida: Value(unidadeMedida),
      vendaFracionada: Value(vendaFracionada),
      marca: marca == null && nullToAbsent
          ? const Value.absent()
          : Value(marca),
      fotoUrl: fotoUrl == null && nullToAbsent
          ? const Value.absent()
          : Value(fotoUrl),
      fotoLocalPath: fotoLocalPath == null && nullToAbsent
          ? const Value.absent()
          : Value(fotoLocalPath),
      ativo: Value(ativo),
      dataAtualizacao: dataAtualizacao == null && nullToAbsent
          ? const Value.absent()
          : Value(dataAtualizacao),
      variantesJson: variantesJson == null && nullToAbsent
          ? const Value.absent()
          : Value(variantesJson),
    );
  }

  factory Produto.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return Produto(
      id: serializer.fromJson<String>(json['id']),
      usuarioId: serializer.fromJson<String>(json['usuarioId']),
      categoriaId: serializer.fromJson<String?>(json['categoriaId']),
      nome: serializer.fromJson<String>(json['nome']),
      codigoBarras: serializer.fromJson<String?>(json['codigoBarras']),
      codigoReferencia: serializer.fromJson<String?>(json['codigoReferencia']),
      precoVendaSugerido: serializer.fromJson<double>(
        json['precoVendaSugerido'],
      ),
      precoCusto: serializer.fromJson<double>(json['precoCusto']),
      precoPromocional: serializer.fromJson<double>(json['precoPromocional']),
      dataInicioPromocao: serializer.fromJson<String?>(
        json['dataInicioPromocao'],
      ),
      dataFimPromocao: serializer.fromJson<String?>(json['dataFimPromocao']),
      precoVigente: serializer.fromJson<double>(json['precoVigente']),
      emPromocao: serializer.fromJson<bool>(json['emPromocao']),
      estoqueAtual: serializer.fromJson<int>(json['estoqueAtual']),
      estoqueMinimo: serializer.fromJson<int>(json['estoqueMinimo']),
      unidadeMedida: serializer.fromJson<String>(json['unidadeMedida']),
      vendaFracionada: serializer.fromJson<bool>(json['vendaFracionada']),
      marca: serializer.fromJson<String?>(json['marca']),
      fotoUrl: serializer.fromJson<String?>(json['fotoUrl']),
      fotoLocalPath: serializer.fromJson<String?>(json['fotoLocalPath']),
      ativo: serializer.fromJson<bool>(json['ativo']),
      dataAtualizacao: serializer.fromJson<String?>(json['dataAtualizacao']),
      variantesJson: serializer.fromJson<String?>(json['variantesJson']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'usuarioId': serializer.toJson<String>(usuarioId),
      'categoriaId': serializer.toJson<String?>(categoriaId),
      'nome': serializer.toJson<String>(nome),
      'codigoBarras': serializer.toJson<String?>(codigoBarras),
      'codigoReferencia': serializer.toJson<String?>(codigoReferencia),
      'precoVendaSugerido': serializer.toJson<double>(precoVendaSugerido),
      'precoCusto': serializer.toJson<double>(precoCusto),
      'precoPromocional': serializer.toJson<double>(precoPromocional),
      'dataInicioPromocao': serializer.toJson<String?>(dataInicioPromocao),
      'dataFimPromocao': serializer.toJson<String?>(dataFimPromocao),
      'precoVigente': serializer.toJson<double>(precoVigente),
      'emPromocao': serializer.toJson<bool>(emPromocao),
      'estoqueAtual': serializer.toJson<int>(estoqueAtual),
      'estoqueMinimo': serializer.toJson<int>(estoqueMinimo),
      'unidadeMedida': serializer.toJson<String>(unidadeMedida),
      'vendaFracionada': serializer.toJson<bool>(vendaFracionada),
      'marca': serializer.toJson<String?>(marca),
      'fotoUrl': serializer.toJson<String?>(fotoUrl),
      'fotoLocalPath': serializer.toJson<String?>(fotoLocalPath),
      'ativo': serializer.toJson<bool>(ativo),
      'dataAtualizacao': serializer.toJson<String?>(dataAtualizacao),
      'variantesJson': serializer.toJson<String?>(variantesJson),
    };
  }

  Produto copyWith({
    String? id,
    String? usuarioId,
    Value<String?> categoriaId = const Value.absent(),
    String? nome,
    Value<String?> codigoBarras = const Value.absent(),
    Value<String?> codigoReferencia = const Value.absent(),
    double? precoVendaSugerido,
    double? precoCusto,
    double? precoPromocional,
    Value<String?> dataInicioPromocao = const Value.absent(),
    Value<String?> dataFimPromocao = const Value.absent(),
    double? precoVigente,
    bool? emPromocao,
    int? estoqueAtual,
    int? estoqueMinimo,
    String? unidadeMedida,
    bool? vendaFracionada,
    Value<String?> marca = const Value.absent(),
    Value<String?> fotoUrl = const Value.absent(),
    Value<String?> fotoLocalPath = const Value.absent(),
    bool? ativo,
    Value<String?> dataAtualizacao = const Value.absent(),
    Value<String?> variantesJson = const Value.absent(),
  }) => Produto(
    id: id ?? this.id,
    usuarioId: usuarioId ?? this.usuarioId,
    categoriaId: categoriaId.present ? categoriaId.value : this.categoriaId,
    nome: nome ?? this.nome,
    codigoBarras: codigoBarras.present ? codigoBarras.value : this.codigoBarras,
    codigoReferencia: codigoReferencia.present
        ? codigoReferencia.value
        : this.codigoReferencia,
    precoVendaSugerido: precoVendaSugerido ?? this.precoVendaSugerido,
    precoCusto: precoCusto ?? this.precoCusto,
    precoPromocional: precoPromocional ?? this.precoPromocional,
    dataInicioPromocao: dataInicioPromocao.present
        ? dataInicioPromocao.value
        : this.dataInicioPromocao,
    dataFimPromocao: dataFimPromocao.present
        ? dataFimPromocao.value
        : this.dataFimPromocao,
    precoVigente: precoVigente ?? this.precoVigente,
    emPromocao: emPromocao ?? this.emPromocao,
    estoqueAtual: estoqueAtual ?? this.estoqueAtual,
    estoqueMinimo: estoqueMinimo ?? this.estoqueMinimo,
    unidadeMedida: unidadeMedida ?? this.unidadeMedida,
    vendaFracionada: vendaFracionada ?? this.vendaFracionada,
    marca: marca.present ? marca.value : this.marca,
    fotoUrl: fotoUrl.present ? fotoUrl.value : this.fotoUrl,
    fotoLocalPath: fotoLocalPath.present
        ? fotoLocalPath.value
        : this.fotoLocalPath,
    ativo: ativo ?? this.ativo,
    dataAtualizacao: dataAtualizacao.present
        ? dataAtualizacao.value
        : this.dataAtualizacao,
    variantesJson: variantesJson.present
        ? variantesJson.value
        : this.variantesJson,
  );
  Produto copyWithCompanion(ProdutosCompanion data) {
    return Produto(
      id: data.id.present ? data.id.value : this.id,
      usuarioId: data.usuarioId.present ? data.usuarioId.value : this.usuarioId,
      categoriaId: data.categoriaId.present
          ? data.categoriaId.value
          : this.categoriaId,
      nome: data.nome.present ? data.nome.value : this.nome,
      codigoBarras: data.codigoBarras.present
          ? data.codigoBarras.value
          : this.codigoBarras,
      codigoReferencia: data.codigoReferencia.present
          ? data.codigoReferencia.value
          : this.codigoReferencia,
      precoVendaSugerido: data.precoVendaSugerido.present
          ? data.precoVendaSugerido.value
          : this.precoVendaSugerido,
      precoCusto: data.precoCusto.present
          ? data.precoCusto.value
          : this.precoCusto,
      precoPromocional: data.precoPromocional.present
          ? data.precoPromocional.value
          : this.precoPromocional,
      dataInicioPromocao: data.dataInicioPromocao.present
          ? data.dataInicioPromocao.value
          : this.dataInicioPromocao,
      dataFimPromocao: data.dataFimPromocao.present
          ? data.dataFimPromocao.value
          : this.dataFimPromocao,
      precoVigente: data.precoVigente.present
          ? data.precoVigente.value
          : this.precoVigente,
      emPromocao: data.emPromocao.present
          ? data.emPromocao.value
          : this.emPromocao,
      estoqueAtual: data.estoqueAtual.present
          ? data.estoqueAtual.value
          : this.estoqueAtual,
      estoqueMinimo: data.estoqueMinimo.present
          ? data.estoqueMinimo.value
          : this.estoqueMinimo,
      unidadeMedida: data.unidadeMedida.present
          ? data.unidadeMedida.value
          : this.unidadeMedida,
      vendaFracionada: data.vendaFracionada.present
          ? data.vendaFracionada.value
          : this.vendaFracionada,
      marca: data.marca.present ? data.marca.value : this.marca,
      fotoUrl: data.fotoUrl.present ? data.fotoUrl.value : this.fotoUrl,
      fotoLocalPath: data.fotoLocalPath.present
          ? data.fotoLocalPath.value
          : this.fotoLocalPath,
      ativo: data.ativo.present ? data.ativo.value : this.ativo,
      dataAtualizacao: data.dataAtualizacao.present
          ? data.dataAtualizacao.value
          : this.dataAtualizacao,
      variantesJson: data.variantesJson.present
          ? data.variantesJson.value
          : this.variantesJson,
    );
  }

  @override
  String toString() {
    return (StringBuffer('Produto(')
          ..write('id: $id, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('categoriaId: $categoriaId, ')
          ..write('nome: $nome, ')
          ..write('codigoBarras: $codigoBarras, ')
          ..write('codigoReferencia: $codigoReferencia, ')
          ..write('precoVendaSugerido: $precoVendaSugerido, ')
          ..write('precoCusto: $precoCusto, ')
          ..write('precoPromocional: $precoPromocional, ')
          ..write('dataInicioPromocao: $dataInicioPromocao, ')
          ..write('dataFimPromocao: $dataFimPromocao, ')
          ..write('precoVigente: $precoVigente, ')
          ..write('emPromocao: $emPromocao, ')
          ..write('estoqueAtual: $estoqueAtual, ')
          ..write('estoqueMinimo: $estoqueMinimo, ')
          ..write('unidadeMedida: $unidadeMedida, ')
          ..write('vendaFracionada: $vendaFracionada, ')
          ..write('marca: $marca, ')
          ..write('fotoUrl: $fotoUrl, ')
          ..write('fotoLocalPath: $fotoLocalPath, ')
          ..write('ativo: $ativo, ')
          ..write('dataAtualizacao: $dataAtualizacao, ')
          ..write('variantesJson: $variantesJson')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hashAll([
    id,
    usuarioId,
    categoriaId,
    nome,
    codigoBarras,
    codigoReferencia,
    precoVendaSugerido,
    precoCusto,
    precoPromocional,
    dataInicioPromocao,
    dataFimPromocao,
    precoVigente,
    emPromocao,
    estoqueAtual,
    estoqueMinimo,
    unidadeMedida,
    vendaFracionada,
    marca,
    fotoUrl,
    fotoLocalPath,
    ativo,
    dataAtualizacao,
    variantesJson,
  ]);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is Produto &&
          other.id == this.id &&
          other.usuarioId == this.usuarioId &&
          other.categoriaId == this.categoriaId &&
          other.nome == this.nome &&
          other.codigoBarras == this.codigoBarras &&
          other.codigoReferencia == this.codigoReferencia &&
          other.precoVendaSugerido == this.precoVendaSugerido &&
          other.precoCusto == this.precoCusto &&
          other.precoPromocional == this.precoPromocional &&
          other.dataInicioPromocao == this.dataInicioPromocao &&
          other.dataFimPromocao == this.dataFimPromocao &&
          other.precoVigente == this.precoVigente &&
          other.emPromocao == this.emPromocao &&
          other.estoqueAtual == this.estoqueAtual &&
          other.estoqueMinimo == this.estoqueMinimo &&
          other.unidadeMedida == this.unidadeMedida &&
          other.vendaFracionada == this.vendaFracionada &&
          other.marca == this.marca &&
          other.fotoUrl == this.fotoUrl &&
          other.fotoLocalPath == this.fotoLocalPath &&
          other.ativo == this.ativo &&
          other.dataAtualizacao == this.dataAtualizacao &&
          other.variantesJson == this.variantesJson);
}

class ProdutosCompanion extends UpdateCompanion<Produto> {
  final Value<String> id;
  final Value<String> usuarioId;
  final Value<String?> categoriaId;
  final Value<String> nome;
  final Value<String?> codigoBarras;
  final Value<String?> codigoReferencia;
  final Value<double> precoVendaSugerido;
  final Value<double> precoCusto;
  final Value<double> precoPromocional;
  final Value<String?> dataInicioPromocao;
  final Value<String?> dataFimPromocao;
  final Value<double> precoVigente;
  final Value<bool> emPromocao;
  final Value<int> estoqueAtual;
  final Value<int> estoqueMinimo;
  final Value<String> unidadeMedida;
  final Value<bool> vendaFracionada;
  final Value<String?> marca;
  final Value<String?> fotoUrl;
  final Value<String?> fotoLocalPath;
  final Value<bool> ativo;
  final Value<String?> dataAtualizacao;
  final Value<String?> variantesJson;
  final Value<int> rowid;
  const ProdutosCompanion({
    this.id = const Value.absent(),
    this.usuarioId = const Value.absent(),
    this.categoriaId = const Value.absent(),
    this.nome = const Value.absent(),
    this.codigoBarras = const Value.absent(),
    this.codigoReferencia = const Value.absent(),
    this.precoVendaSugerido = const Value.absent(),
    this.precoCusto = const Value.absent(),
    this.precoPromocional = const Value.absent(),
    this.dataInicioPromocao = const Value.absent(),
    this.dataFimPromocao = const Value.absent(),
    this.precoVigente = const Value.absent(),
    this.emPromocao = const Value.absent(),
    this.estoqueAtual = const Value.absent(),
    this.estoqueMinimo = const Value.absent(),
    this.unidadeMedida = const Value.absent(),
    this.vendaFracionada = const Value.absent(),
    this.marca = const Value.absent(),
    this.fotoUrl = const Value.absent(),
    this.fotoLocalPath = const Value.absent(),
    this.ativo = const Value.absent(),
    this.dataAtualizacao = const Value.absent(),
    this.variantesJson = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  ProdutosCompanion.insert({
    required String id,
    required String usuarioId,
    this.categoriaId = const Value.absent(),
    required String nome,
    this.codigoBarras = const Value.absent(),
    this.codigoReferencia = const Value.absent(),
    this.precoVendaSugerido = const Value.absent(),
    this.precoCusto = const Value.absent(),
    this.precoPromocional = const Value.absent(),
    this.dataInicioPromocao = const Value.absent(),
    this.dataFimPromocao = const Value.absent(),
    this.precoVigente = const Value.absent(),
    this.emPromocao = const Value.absent(),
    this.estoqueAtual = const Value.absent(),
    this.estoqueMinimo = const Value.absent(),
    this.unidadeMedida = const Value.absent(),
    this.vendaFracionada = const Value.absent(),
    this.marca = const Value.absent(),
    this.fotoUrl = const Value.absent(),
    this.fotoLocalPath = const Value.absent(),
    this.ativo = const Value.absent(),
    this.dataAtualizacao = const Value.absent(),
    this.variantesJson = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       usuarioId = Value(usuarioId),
       nome = Value(nome);
  static Insertable<Produto> custom({
    Expression<String>? id,
    Expression<String>? usuarioId,
    Expression<String>? categoriaId,
    Expression<String>? nome,
    Expression<String>? codigoBarras,
    Expression<String>? codigoReferencia,
    Expression<double>? precoVendaSugerido,
    Expression<double>? precoCusto,
    Expression<double>? precoPromocional,
    Expression<String>? dataInicioPromocao,
    Expression<String>? dataFimPromocao,
    Expression<double>? precoVigente,
    Expression<bool>? emPromocao,
    Expression<int>? estoqueAtual,
    Expression<int>? estoqueMinimo,
    Expression<String>? unidadeMedida,
    Expression<bool>? vendaFracionada,
    Expression<String>? marca,
    Expression<String>? fotoUrl,
    Expression<String>? fotoLocalPath,
    Expression<bool>? ativo,
    Expression<String>? dataAtualizacao,
    Expression<String>? variantesJson,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (usuarioId != null) 'usuario_id': usuarioId,
      if (categoriaId != null) 'categoria_id': categoriaId,
      if (nome != null) 'nome': nome,
      if (codigoBarras != null) 'codigo_barras': codigoBarras,
      if (codigoReferencia != null) 'codigo_referencia': codigoReferencia,
      if (precoVendaSugerido != null)
        'preco_venda_sugerido': precoVendaSugerido,
      if (precoCusto != null) 'preco_custo': precoCusto,
      if (precoPromocional != null) 'preco_promocional': precoPromocional,
      if (dataInicioPromocao != null)
        'data_inicio_promocao': dataInicioPromocao,
      if (dataFimPromocao != null) 'data_fim_promocao': dataFimPromocao,
      if (precoVigente != null) 'preco_vigente': precoVigente,
      if (emPromocao != null) 'em_promocao': emPromocao,
      if (estoqueAtual != null) 'estoque_atual': estoqueAtual,
      if (estoqueMinimo != null) 'estoque_minimo': estoqueMinimo,
      if (unidadeMedida != null) 'unidade_medida': unidadeMedida,
      if (vendaFracionada != null) 'venda_fracionada': vendaFracionada,
      if (marca != null) 'marca': marca,
      if (fotoUrl != null) 'foto_url': fotoUrl,
      if (fotoLocalPath != null) 'foto_local_path': fotoLocalPath,
      if (ativo != null) 'ativo': ativo,
      if (dataAtualizacao != null) 'data_atualizacao': dataAtualizacao,
      if (variantesJson != null) 'variantes_json': variantesJson,
      if (rowid != null) 'rowid': rowid,
    });
  }

  ProdutosCompanion copyWith({
    Value<String>? id,
    Value<String>? usuarioId,
    Value<String?>? categoriaId,
    Value<String>? nome,
    Value<String?>? codigoBarras,
    Value<String?>? codigoReferencia,
    Value<double>? precoVendaSugerido,
    Value<double>? precoCusto,
    Value<double>? precoPromocional,
    Value<String?>? dataInicioPromocao,
    Value<String?>? dataFimPromocao,
    Value<double>? precoVigente,
    Value<bool>? emPromocao,
    Value<int>? estoqueAtual,
    Value<int>? estoqueMinimo,
    Value<String>? unidadeMedida,
    Value<bool>? vendaFracionada,
    Value<String?>? marca,
    Value<String?>? fotoUrl,
    Value<String?>? fotoLocalPath,
    Value<bool>? ativo,
    Value<String?>? dataAtualizacao,
    Value<String?>? variantesJson,
    Value<int>? rowid,
  }) {
    return ProdutosCompanion(
      id: id ?? this.id,
      usuarioId: usuarioId ?? this.usuarioId,
      categoriaId: categoriaId ?? this.categoriaId,
      nome: nome ?? this.nome,
      codigoBarras: codigoBarras ?? this.codigoBarras,
      codigoReferencia: codigoReferencia ?? this.codigoReferencia,
      precoVendaSugerido: precoVendaSugerido ?? this.precoVendaSugerido,
      precoCusto: precoCusto ?? this.precoCusto,
      precoPromocional: precoPromocional ?? this.precoPromocional,
      dataInicioPromocao: dataInicioPromocao ?? this.dataInicioPromocao,
      dataFimPromocao: dataFimPromocao ?? this.dataFimPromocao,
      precoVigente: precoVigente ?? this.precoVigente,
      emPromocao: emPromocao ?? this.emPromocao,
      estoqueAtual: estoqueAtual ?? this.estoqueAtual,
      estoqueMinimo: estoqueMinimo ?? this.estoqueMinimo,
      unidadeMedida: unidadeMedida ?? this.unidadeMedida,
      vendaFracionada: vendaFracionada ?? this.vendaFracionada,
      marca: marca ?? this.marca,
      fotoUrl: fotoUrl ?? this.fotoUrl,
      fotoLocalPath: fotoLocalPath ?? this.fotoLocalPath,
      ativo: ativo ?? this.ativo,
      dataAtualizacao: dataAtualizacao ?? this.dataAtualizacao,
      variantesJson: variantesJson ?? this.variantesJson,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (usuarioId.present) {
      map['usuario_id'] = Variable<String>(usuarioId.value);
    }
    if (categoriaId.present) {
      map['categoria_id'] = Variable<String>(categoriaId.value);
    }
    if (nome.present) {
      map['nome'] = Variable<String>(nome.value);
    }
    if (codigoBarras.present) {
      map['codigo_barras'] = Variable<String>(codigoBarras.value);
    }
    if (codigoReferencia.present) {
      map['codigo_referencia'] = Variable<String>(codigoReferencia.value);
    }
    if (precoVendaSugerido.present) {
      map['preco_venda_sugerido'] = Variable<double>(precoVendaSugerido.value);
    }
    if (precoCusto.present) {
      map['preco_custo'] = Variable<double>(precoCusto.value);
    }
    if (precoPromocional.present) {
      map['preco_promocional'] = Variable<double>(precoPromocional.value);
    }
    if (dataInicioPromocao.present) {
      map['data_inicio_promocao'] = Variable<String>(dataInicioPromocao.value);
    }
    if (dataFimPromocao.present) {
      map['data_fim_promocao'] = Variable<String>(dataFimPromocao.value);
    }
    if (precoVigente.present) {
      map['preco_vigente'] = Variable<double>(precoVigente.value);
    }
    if (emPromocao.present) {
      map['em_promocao'] = Variable<bool>(emPromocao.value);
    }
    if (estoqueAtual.present) {
      map['estoque_atual'] = Variable<int>(estoqueAtual.value);
    }
    if (estoqueMinimo.present) {
      map['estoque_minimo'] = Variable<int>(estoqueMinimo.value);
    }
    if (unidadeMedida.present) {
      map['unidade_medida'] = Variable<String>(unidadeMedida.value);
    }
    if (vendaFracionada.present) {
      map['venda_fracionada'] = Variable<bool>(vendaFracionada.value);
    }
    if (marca.present) {
      map['marca'] = Variable<String>(marca.value);
    }
    if (fotoUrl.present) {
      map['foto_url'] = Variable<String>(fotoUrl.value);
    }
    if (fotoLocalPath.present) {
      map['foto_local_path'] = Variable<String>(fotoLocalPath.value);
    }
    if (ativo.present) {
      map['ativo'] = Variable<bool>(ativo.value);
    }
    if (dataAtualizacao.present) {
      map['data_atualizacao'] = Variable<String>(dataAtualizacao.value);
    }
    if (variantesJson.present) {
      map['variantes_json'] = Variable<String>(variantesJson.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('ProdutosCompanion(')
          ..write('id: $id, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('categoriaId: $categoriaId, ')
          ..write('nome: $nome, ')
          ..write('codigoBarras: $codigoBarras, ')
          ..write('codigoReferencia: $codigoReferencia, ')
          ..write('precoVendaSugerido: $precoVendaSugerido, ')
          ..write('precoCusto: $precoCusto, ')
          ..write('precoPromocional: $precoPromocional, ')
          ..write('dataInicioPromocao: $dataInicioPromocao, ')
          ..write('dataFimPromocao: $dataFimPromocao, ')
          ..write('precoVigente: $precoVigente, ')
          ..write('emPromocao: $emPromocao, ')
          ..write('estoqueAtual: $estoqueAtual, ')
          ..write('estoqueMinimo: $estoqueMinimo, ')
          ..write('unidadeMedida: $unidadeMedida, ')
          ..write('vendaFracionada: $vendaFracionada, ')
          ..write('marca: $marca, ')
          ..write('fotoUrl: $fotoUrl, ')
          ..write('fotoLocalPath: $fotoLocalPath, ')
          ..write('ativo: $ativo, ')
          ..write('dataAtualizacao: $dataAtualizacao, ')
          ..write('variantesJson: $variantesJson, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $ClientesTable extends Clientes with TableInfo<$ClientesTable, Cliente> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $ClientesTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _usuarioIdMeta = const VerificationMeta(
    'usuarioId',
  );
  @override
  late final GeneratedColumn<String> usuarioId = GeneratedColumn<String>(
    'usuario_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _nomeCompletoMeta = const VerificationMeta(
    'nomeCompleto',
  );
  @override
  late final GeneratedColumn<String> nomeCompleto = GeneratedColumn<String>(
    'nome_completo',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _cpfMeta = const VerificationMeta('cpf');
  @override
  late final GeneratedColumn<String> cpf = GeneratedColumn<String>(
    'cpf',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _telefoneMeta = const VerificationMeta(
    'telefone',
  );
  @override
  late final GeneratedColumn<String> telefone = GeneratedColumn<String>(
    'telefone',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _emailMeta = const VerificationMeta('email');
  @override
  late final GeneratedColumn<String> email = GeneratedColumn<String>(
    'email',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _enderecoLogradouroMeta =
      const VerificationMeta('enderecoLogradouro');
  @override
  late final GeneratedColumn<String> enderecoLogradouro =
      GeneratedColumn<String>(
        'endereco_logradouro',
        aliasedName,
        true,
        type: DriftSqlType.string,
        requiredDuringInsert: false,
      );
  static const VerificationMeta _enderecoNumeroMeta = const VerificationMeta(
    'enderecoNumero',
  );
  @override
  late final GeneratedColumn<String> enderecoNumero = GeneratedColumn<String>(
    'endereco_numero',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _enderecoBairroMeta = const VerificationMeta(
    'enderecoBairro',
  );
  @override
  late final GeneratedColumn<String> enderecoBairro = GeneratedColumn<String>(
    'endereco_bairro',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _enderecoCidadeMeta = const VerificationMeta(
    'enderecoCidade',
  );
  @override
  late final GeneratedColumn<String> enderecoCidade = GeneratedColumn<String>(
    'endereco_cidade',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _enderecoEstadoMeta = const VerificationMeta(
    'enderecoEstado',
  );
  @override
  late final GeneratedColumn<String> enderecoEstado = GeneratedColumn<String>(
    'endereco_estado',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _enderecoCepMeta = const VerificationMeta(
    'enderecoCep',
  );
  @override
  late final GeneratedColumn<String> enderecoCep = GeneratedColumn<String>(
    'endereco_cep',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _ativoMeta = const VerificationMeta('ativo');
  @override
  late final GeneratedColumn<bool> ativo = GeneratedColumn<bool>(
    'ativo',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("ativo" IN (0, 1))',
    ),
    defaultValue: const Constant(true),
  );
  static const VerificationMeta _dataCriacaoMeta = const VerificationMeta(
    'dataCriacao',
  );
  @override
  late final GeneratedColumn<String> dataCriacao = GeneratedColumn<String>(
    'data_criacao',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _dataAtualizacaoMeta = const VerificationMeta(
    'dataAtualizacao',
  );
  @override
  late final GeneratedColumn<String> dataAtualizacao = GeneratedColumn<String>(
    'data_atualizacao',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _syncStatusMeta = const VerificationMeta(
    'syncStatus',
  );
  @override
  late final GeneratedColumn<String> syncStatus = GeneratedColumn<String>(
    'sync_status',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
    defaultValue: const Constant('synced'),
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    usuarioId,
    nomeCompleto,
    cpf,
    telefone,
    email,
    enderecoLogradouro,
    enderecoNumero,
    enderecoBairro,
    enderecoCidade,
    enderecoEstado,
    enderecoCep,
    ativo,
    dataCriacao,
    dataAtualizacao,
    syncStatus,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'clientes';
  @override
  VerificationContext validateIntegrity(
    Insertable<Cliente> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('usuario_id')) {
      context.handle(
        _usuarioIdMeta,
        usuarioId.isAcceptableOrUnknown(data['usuario_id']!, _usuarioIdMeta),
      );
    } else if (isInserting) {
      context.missing(_usuarioIdMeta);
    }
    if (data.containsKey('nome_completo')) {
      context.handle(
        _nomeCompletoMeta,
        nomeCompleto.isAcceptableOrUnknown(
          data['nome_completo']!,
          _nomeCompletoMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_nomeCompletoMeta);
    }
    if (data.containsKey('cpf')) {
      context.handle(
        _cpfMeta,
        cpf.isAcceptableOrUnknown(data['cpf']!, _cpfMeta),
      );
    }
    if (data.containsKey('telefone')) {
      context.handle(
        _telefoneMeta,
        telefone.isAcceptableOrUnknown(data['telefone']!, _telefoneMeta),
      );
    }
    if (data.containsKey('email')) {
      context.handle(
        _emailMeta,
        email.isAcceptableOrUnknown(data['email']!, _emailMeta),
      );
    }
    if (data.containsKey('endereco_logradouro')) {
      context.handle(
        _enderecoLogradouroMeta,
        enderecoLogradouro.isAcceptableOrUnknown(
          data['endereco_logradouro']!,
          _enderecoLogradouroMeta,
        ),
      );
    }
    if (data.containsKey('endereco_numero')) {
      context.handle(
        _enderecoNumeroMeta,
        enderecoNumero.isAcceptableOrUnknown(
          data['endereco_numero']!,
          _enderecoNumeroMeta,
        ),
      );
    }
    if (data.containsKey('endereco_bairro')) {
      context.handle(
        _enderecoBairroMeta,
        enderecoBairro.isAcceptableOrUnknown(
          data['endereco_bairro']!,
          _enderecoBairroMeta,
        ),
      );
    }
    if (data.containsKey('endereco_cidade')) {
      context.handle(
        _enderecoCidadeMeta,
        enderecoCidade.isAcceptableOrUnknown(
          data['endereco_cidade']!,
          _enderecoCidadeMeta,
        ),
      );
    }
    if (data.containsKey('endereco_estado')) {
      context.handle(
        _enderecoEstadoMeta,
        enderecoEstado.isAcceptableOrUnknown(
          data['endereco_estado']!,
          _enderecoEstadoMeta,
        ),
      );
    }
    if (data.containsKey('endereco_cep')) {
      context.handle(
        _enderecoCepMeta,
        enderecoCep.isAcceptableOrUnknown(
          data['endereco_cep']!,
          _enderecoCepMeta,
        ),
      );
    }
    if (data.containsKey('ativo')) {
      context.handle(
        _ativoMeta,
        ativo.isAcceptableOrUnknown(data['ativo']!, _ativoMeta),
      );
    }
    if (data.containsKey('data_criacao')) {
      context.handle(
        _dataCriacaoMeta,
        dataCriacao.isAcceptableOrUnknown(
          data['data_criacao']!,
          _dataCriacaoMeta,
        ),
      );
    }
    if (data.containsKey('data_atualizacao')) {
      context.handle(
        _dataAtualizacaoMeta,
        dataAtualizacao.isAcceptableOrUnknown(
          data['data_atualizacao']!,
          _dataAtualizacaoMeta,
        ),
      );
    }
    if (data.containsKey('sync_status')) {
      context.handle(
        _syncStatusMeta,
        syncStatus.isAcceptableOrUnknown(data['sync_status']!, _syncStatusMeta),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  Cliente map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return Cliente(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      usuarioId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}usuario_id'],
      )!,
      nomeCompleto: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}nome_completo'],
      )!,
      cpf: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}cpf'],
      ),
      telefone: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}telefone'],
      ),
      email: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}email'],
      ),
      enderecoLogradouro: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}endereco_logradouro'],
      ),
      enderecoNumero: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}endereco_numero'],
      ),
      enderecoBairro: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}endereco_bairro'],
      ),
      enderecoCidade: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}endereco_cidade'],
      ),
      enderecoEstado: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}endereco_estado'],
      ),
      enderecoCep: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}endereco_cep'],
      ),
      ativo: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}ativo'],
      )!,
      dataCriacao: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_criacao'],
      ),
      dataAtualizacao: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_atualizacao'],
      ),
      syncStatus: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}sync_status'],
      )!,
    );
  }

  @override
  $ClientesTable createAlias(String alias) {
    return $ClientesTable(attachedDatabase, alias);
  }
}

class Cliente extends DataClass implements Insertable<Cliente> {
  final String id;
  final String usuarioId;
  final String nomeCompleto;
  final String? cpf;
  final String? telefone;
  final String? email;
  final String? enderecoLogradouro;
  final String? enderecoNumero;
  final String? enderecoBairro;
  final String? enderecoCidade;
  final String? enderecoEstado;
  final String? enderecoCep;
  final bool ativo;
  final String? dataCriacao;
  final String? dataAtualizacao;
  final String syncStatus;
  const Cliente({
    required this.id,
    required this.usuarioId,
    required this.nomeCompleto,
    this.cpf,
    this.telefone,
    this.email,
    this.enderecoLogradouro,
    this.enderecoNumero,
    this.enderecoBairro,
    this.enderecoCidade,
    this.enderecoEstado,
    this.enderecoCep,
    required this.ativo,
    this.dataCriacao,
    this.dataAtualizacao,
    required this.syncStatus,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['usuario_id'] = Variable<String>(usuarioId);
    map['nome_completo'] = Variable<String>(nomeCompleto);
    if (!nullToAbsent || cpf != null) {
      map['cpf'] = Variable<String>(cpf);
    }
    if (!nullToAbsent || telefone != null) {
      map['telefone'] = Variable<String>(telefone);
    }
    if (!nullToAbsent || email != null) {
      map['email'] = Variable<String>(email);
    }
    if (!nullToAbsent || enderecoLogradouro != null) {
      map['endereco_logradouro'] = Variable<String>(enderecoLogradouro);
    }
    if (!nullToAbsent || enderecoNumero != null) {
      map['endereco_numero'] = Variable<String>(enderecoNumero);
    }
    if (!nullToAbsent || enderecoBairro != null) {
      map['endereco_bairro'] = Variable<String>(enderecoBairro);
    }
    if (!nullToAbsent || enderecoCidade != null) {
      map['endereco_cidade'] = Variable<String>(enderecoCidade);
    }
    if (!nullToAbsent || enderecoEstado != null) {
      map['endereco_estado'] = Variable<String>(enderecoEstado);
    }
    if (!nullToAbsent || enderecoCep != null) {
      map['endereco_cep'] = Variable<String>(enderecoCep);
    }
    map['ativo'] = Variable<bool>(ativo);
    if (!nullToAbsent || dataCriacao != null) {
      map['data_criacao'] = Variable<String>(dataCriacao);
    }
    if (!nullToAbsent || dataAtualizacao != null) {
      map['data_atualizacao'] = Variable<String>(dataAtualizacao);
    }
    map['sync_status'] = Variable<String>(syncStatus);
    return map;
  }

  ClientesCompanion toCompanion(bool nullToAbsent) {
    return ClientesCompanion(
      id: Value(id),
      usuarioId: Value(usuarioId),
      nomeCompleto: Value(nomeCompleto),
      cpf: cpf == null && nullToAbsent ? const Value.absent() : Value(cpf),
      telefone: telefone == null && nullToAbsent
          ? const Value.absent()
          : Value(telefone),
      email: email == null && nullToAbsent
          ? const Value.absent()
          : Value(email),
      enderecoLogradouro: enderecoLogradouro == null && nullToAbsent
          ? const Value.absent()
          : Value(enderecoLogradouro),
      enderecoNumero: enderecoNumero == null && nullToAbsent
          ? const Value.absent()
          : Value(enderecoNumero),
      enderecoBairro: enderecoBairro == null && nullToAbsent
          ? const Value.absent()
          : Value(enderecoBairro),
      enderecoCidade: enderecoCidade == null && nullToAbsent
          ? const Value.absent()
          : Value(enderecoCidade),
      enderecoEstado: enderecoEstado == null && nullToAbsent
          ? const Value.absent()
          : Value(enderecoEstado),
      enderecoCep: enderecoCep == null && nullToAbsent
          ? const Value.absent()
          : Value(enderecoCep),
      ativo: Value(ativo),
      dataCriacao: dataCriacao == null && nullToAbsent
          ? const Value.absent()
          : Value(dataCriacao),
      dataAtualizacao: dataAtualizacao == null && nullToAbsent
          ? const Value.absent()
          : Value(dataAtualizacao),
      syncStatus: Value(syncStatus),
    );
  }

  factory Cliente.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return Cliente(
      id: serializer.fromJson<String>(json['id']),
      usuarioId: serializer.fromJson<String>(json['usuarioId']),
      nomeCompleto: serializer.fromJson<String>(json['nomeCompleto']),
      cpf: serializer.fromJson<String?>(json['cpf']),
      telefone: serializer.fromJson<String?>(json['telefone']),
      email: serializer.fromJson<String?>(json['email']),
      enderecoLogradouro: serializer.fromJson<String?>(
        json['enderecoLogradouro'],
      ),
      enderecoNumero: serializer.fromJson<String?>(json['enderecoNumero']),
      enderecoBairro: serializer.fromJson<String?>(json['enderecoBairro']),
      enderecoCidade: serializer.fromJson<String?>(json['enderecoCidade']),
      enderecoEstado: serializer.fromJson<String?>(json['enderecoEstado']),
      enderecoCep: serializer.fromJson<String?>(json['enderecoCep']),
      ativo: serializer.fromJson<bool>(json['ativo']),
      dataCriacao: serializer.fromJson<String?>(json['dataCriacao']),
      dataAtualizacao: serializer.fromJson<String?>(json['dataAtualizacao']),
      syncStatus: serializer.fromJson<String>(json['syncStatus']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'usuarioId': serializer.toJson<String>(usuarioId),
      'nomeCompleto': serializer.toJson<String>(nomeCompleto),
      'cpf': serializer.toJson<String?>(cpf),
      'telefone': serializer.toJson<String?>(telefone),
      'email': serializer.toJson<String?>(email),
      'enderecoLogradouro': serializer.toJson<String?>(enderecoLogradouro),
      'enderecoNumero': serializer.toJson<String?>(enderecoNumero),
      'enderecoBairro': serializer.toJson<String?>(enderecoBairro),
      'enderecoCidade': serializer.toJson<String?>(enderecoCidade),
      'enderecoEstado': serializer.toJson<String?>(enderecoEstado),
      'enderecoCep': serializer.toJson<String?>(enderecoCep),
      'ativo': serializer.toJson<bool>(ativo),
      'dataCriacao': serializer.toJson<String?>(dataCriacao),
      'dataAtualizacao': serializer.toJson<String?>(dataAtualizacao),
      'syncStatus': serializer.toJson<String>(syncStatus),
    };
  }

  Cliente copyWith({
    String? id,
    String? usuarioId,
    String? nomeCompleto,
    Value<String?> cpf = const Value.absent(),
    Value<String?> telefone = const Value.absent(),
    Value<String?> email = const Value.absent(),
    Value<String?> enderecoLogradouro = const Value.absent(),
    Value<String?> enderecoNumero = const Value.absent(),
    Value<String?> enderecoBairro = const Value.absent(),
    Value<String?> enderecoCidade = const Value.absent(),
    Value<String?> enderecoEstado = const Value.absent(),
    Value<String?> enderecoCep = const Value.absent(),
    bool? ativo,
    Value<String?> dataCriacao = const Value.absent(),
    Value<String?> dataAtualizacao = const Value.absent(),
    String? syncStatus,
  }) => Cliente(
    id: id ?? this.id,
    usuarioId: usuarioId ?? this.usuarioId,
    nomeCompleto: nomeCompleto ?? this.nomeCompleto,
    cpf: cpf.present ? cpf.value : this.cpf,
    telefone: telefone.present ? telefone.value : this.telefone,
    email: email.present ? email.value : this.email,
    enderecoLogradouro: enderecoLogradouro.present
        ? enderecoLogradouro.value
        : this.enderecoLogradouro,
    enderecoNumero: enderecoNumero.present
        ? enderecoNumero.value
        : this.enderecoNumero,
    enderecoBairro: enderecoBairro.present
        ? enderecoBairro.value
        : this.enderecoBairro,
    enderecoCidade: enderecoCidade.present
        ? enderecoCidade.value
        : this.enderecoCidade,
    enderecoEstado: enderecoEstado.present
        ? enderecoEstado.value
        : this.enderecoEstado,
    enderecoCep: enderecoCep.present ? enderecoCep.value : this.enderecoCep,
    ativo: ativo ?? this.ativo,
    dataCriacao: dataCriacao.present ? dataCriacao.value : this.dataCriacao,
    dataAtualizacao: dataAtualizacao.present
        ? dataAtualizacao.value
        : this.dataAtualizacao,
    syncStatus: syncStatus ?? this.syncStatus,
  );
  Cliente copyWithCompanion(ClientesCompanion data) {
    return Cliente(
      id: data.id.present ? data.id.value : this.id,
      usuarioId: data.usuarioId.present ? data.usuarioId.value : this.usuarioId,
      nomeCompleto: data.nomeCompleto.present
          ? data.nomeCompleto.value
          : this.nomeCompleto,
      cpf: data.cpf.present ? data.cpf.value : this.cpf,
      telefone: data.telefone.present ? data.telefone.value : this.telefone,
      email: data.email.present ? data.email.value : this.email,
      enderecoLogradouro: data.enderecoLogradouro.present
          ? data.enderecoLogradouro.value
          : this.enderecoLogradouro,
      enderecoNumero: data.enderecoNumero.present
          ? data.enderecoNumero.value
          : this.enderecoNumero,
      enderecoBairro: data.enderecoBairro.present
          ? data.enderecoBairro.value
          : this.enderecoBairro,
      enderecoCidade: data.enderecoCidade.present
          ? data.enderecoCidade.value
          : this.enderecoCidade,
      enderecoEstado: data.enderecoEstado.present
          ? data.enderecoEstado.value
          : this.enderecoEstado,
      enderecoCep: data.enderecoCep.present
          ? data.enderecoCep.value
          : this.enderecoCep,
      ativo: data.ativo.present ? data.ativo.value : this.ativo,
      dataCriacao: data.dataCriacao.present
          ? data.dataCriacao.value
          : this.dataCriacao,
      dataAtualizacao: data.dataAtualizacao.present
          ? data.dataAtualizacao.value
          : this.dataAtualizacao,
      syncStatus: data.syncStatus.present
          ? data.syncStatus.value
          : this.syncStatus,
    );
  }

  @override
  String toString() {
    return (StringBuffer('Cliente(')
          ..write('id: $id, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('nomeCompleto: $nomeCompleto, ')
          ..write('cpf: $cpf, ')
          ..write('telefone: $telefone, ')
          ..write('email: $email, ')
          ..write('enderecoLogradouro: $enderecoLogradouro, ')
          ..write('enderecoNumero: $enderecoNumero, ')
          ..write('enderecoBairro: $enderecoBairro, ')
          ..write('enderecoCidade: $enderecoCidade, ')
          ..write('enderecoEstado: $enderecoEstado, ')
          ..write('enderecoCep: $enderecoCep, ')
          ..write('ativo: $ativo, ')
          ..write('dataCriacao: $dataCriacao, ')
          ..write('dataAtualizacao: $dataAtualizacao, ')
          ..write('syncStatus: $syncStatus')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    id,
    usuarioId,
    nomeCompleto,
    cpf,
    telefone,
    email,
    enderecoLogradouro,
    enderecoNumero,
    enderecoBairro,
    enderecoCidade,
    enderecoEstado,
    enderecoCep,
    ativo,
    dataCriacao,
    dataAtualizacao,
    syncStatus,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is Cliente &&
          other.id == this.id &&
          other.usuarioId == this.usuarioId &&
          other.nomeCompleto == this.nomeCompleto &&
          other.cpf == this.cpf &&
          other.telefone == this.telefone &&
          other.email == this.email &&
          other.enderecoLogradouro == this.enderecoLogradouro &&
          other.enderecoNumero == this.enderecoNumero &&
          other.enderecoBairro == this.enderecoBairro &&
          other.enderecoCidade == this.enderecoCidade &&
          other.enderecoEstado == this.enderecoEstado &&
          other.enderecoCep == this.enderecoCep &&
          other.ativo == this.ativo &&
          other.dataCriacao == this.dataCriacao &&
          other.dataAtualizacao == this.dataAtualizacao &&
          other.syncStatus == this.syncStatus);
}

class ClientesCompanion extends UpdateCompanion<Cliente> {
  final Value<String> id;
  final Value<String> usuarioId;
  final Value<String> nomeCompleto;
  final Value<String?> cpf;
  final Value<String?> telefone;
  final Value<String?> email;
  final Value<String?> enderecoLogradouro;
  final Value<String?> enderecoNumero;
  final Value<String?> enderecoBairro;
  final Value<String?> enderecoCidade;
  final Value<String?> enderecoEstado;
  final Value<String?> enderecoCep;
  final Value<bool> ativo;
  final Value<String?> dataCriacao;
  final Value<String?> dataAtualizacao;
  final Value<String> syncStatus;
  final Value<int> rowid;
  const ClientesCompanion({
    this.id = const Value.absent(),
    this.usuarioId = const Value.absent(),
    this.nomeCompleto = const Value.absent(),
    this.cpf = const Value.absent(),
    this.telefone = const Value.absent(),
    this.email = const Value.absent(),
    this.enderecoLogradouro = const Value.absent(),
    this.enderecoNumero = const Value.absent(),
    this.enderecoBairro = const Value.absent(),
    this.enderecoCidade = const Value.absent(),
    this.enderecoEstado = const Value.absent(),
    this.enderecoCep = const Value.absent(),
    this.ativo = const Value.absent(),
    this.dataCriacao = const Value.absent(),
    this.dataAtualizacao = const Value.absent(),
    this.syncStatus = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  ClientesCompanion.insert({
    required String id,
    required String usuarioId,
    required String nomeCompleto,
    this.cpf = const Value.absent(),
    this.telefone = const Value.absent(),
    this.email = const Value.absent(),
    this.enderecoLogradouro = const Value.absent(),
    this.enderecoNumero = const Value.absent(),
    this.enderecoBairro = const Value.absent(),
    this.enderecoCidade = const Value.absent(),
    this.enderecoEstado = const Value.absent(),
    this.enderecoCep = const Value.absent(),
    this.ativo = const Value.absent(),
    this.dataCriacao = const Value.absent(),
    this.dataAtualizacao = const Value.absent(),
    this.syncStatus = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       usuarioId = Value(usuarioId),
       nomeCompleto = Value(nomeCompleto);
  static Insertable<Cliente> custom({
    Expression<String>? id,
    Expression<String>? usuarioId,
    Expression<String>? nomeCompleto,
    Expression<String>? cpf,
    Expression<String>? telefone,
    Expression<String>? email,
    Expression<String>? enderecoLogradouro,
    Expression<String>? enderecoNumero,
    Expression<String>? enderecoBairro,
    Expression<String>? enderecoCidade,
    Expression<String>? enderecoEstado,
    Expression<String>? enderecoCep,
    Expression<bool>? ativo,
    Expression<String>? dataCriacao,
    Expression<String>? dataAtualizacao,
    Expression<String>? syncStatus,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (usuarioId != null) 'usuario_id': usuarioId,
      if (nomeCompleto != null) 'nome_completo': nomeCompleto,
      if (cpf != null) 'cpf': cpf,
      if (telefone != null) 'telefone': telefone,
      if (email != null) 'email': email,
      if (enderecoLogradouro != null) 'endereco_logradouro': enderecoLogradouro,
      if (enderecoNumero != null) 'endereco_numero': enderecoNumero,
      if (enderecoBairro != null) 'endereco_bairro': enderecoBairro,
      if (enderecoCidade != null) 'endereco_cidade': enderecoCidade,
      if (enderecoEstado != null) 'endereco_estado': enderecoEstado,
      if (enderecoCep != null) 'endereco_cep': enderecoCep,
      if (ativo != null) 'ativo': ativo,
      if (dataCriacao != null) 'data_criacao': dataCriacao,
      if (dataAtualizacao != null) 'data_atualizacao': dataAtualizacao,
      if (syncStatus != null) 'sync_status': syncStatus,
      if (rowid != null) 'rowid': rowid,
    });
  }

  ClientesCompanion copyWith({
    Value<String>? id,
    Value<String>? usuarioId,
    Value<String>? nomeCompleto,
    Value<String?>? cpf,
    Value<String?>? telefone,
    Value<String?>? email,
    Value<String?>? enderecoLogradouro,
    Value<String?>? enderecoNumero,
    Value<String?>? enderecoBairro,
    Value<String?>? enderecoCidade,
    Value<String?>? enderecoEstado,
    Value<String?>? enderecoCep,
    Value<bool>? ativo,
    Value<String?>? dataCriacao,
    Value<String?>? dataAtualizacao,
    Value<String>? syncStatus,
    Value<int>? rowid,
  }) {
    return ClientesCompanion(
      id: id ?? this.id,
      usuarioId: usuarioId ?? this.usuarioId,
      nomeCompleto: nomeCompleto ?? this.nomeCompleto,
      cpf: cpf ?? this.cpf,
      telefone: telefone ?? this.telefone,
      email: email ?? this.email,
      enderecoLogradouro: enderecoLogradouro ?? this.enderecoLogradouro,
      enderecoNumero: enderecoNumero ?? this.enderecoNumero,
      enderecoBairro: enderecoBairro ?? this.enderecoBairro,
      enderecoCidade: enderecoCidade ?? this.enderecoCidade,
      enderecoEstado: enderecoEstado ?? this.enderecoEstado,
      enderecoCep: enderecoCep ?? this.enderecoCep,
      ativo: ativo ?? this.ativo,
      dataCriacao: dataCriacao ?? this.dataCriacao,
      dataAtualizacao: dataAtualizacao ?? this.dataAtualizacao,
      syncStatus: syncStatus ?? this.syncStatus,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (usuarioId.present) {
      map['usuario_id'] = Variable<String>(usuarioId.value);
    }
    if (nomeCompleto.present) {
      map['nome_completo'] = Variable<String>(nomeCompleto.value);
    }
    if (cpf.present) {
      map['cpf'] = Variable<String>(cpf.value);
    }
    if (telefone.present) {
      map['telefone'] = Variable<String>(telefone.value);
    }
    if (email.present) {
      map['email'] = Variable<String>(email.value);
    }
    if (enderecoLogradouro.present) {
      map['endereco_logradouro'] = Variable<String>(enderecoLogradouro.value);
    }
    if (enderecoNumero.present) {
      map['endereco_numero'] = Variable<String>(enderecoNumero.value);
    }
    if (enderecoBairro.present) {
      map['endereco_bairro'] = Variable<String>(enderecoBairro.value);
    }
    if (enderecoCidade.present) {
      map['endereco_cidade'] = Variable<String>(enderecoCidade.value);
    }
    if (enderecoEstado.present) {
      map['endereco_estado'] = Variable<String>(enderecoEstado.value);
    }
    if (enderecoCep.present) {
      map['endereco_cep'] = Variable<String>(enderecoCep.value);
    }
    if (ativo.present) {
      map['ativo'] = Variable<bool>(ativo.value);
    }
    if (dataCriacao.present) {
      map['data_criacao'] = Variable<String>(dataCriacao.value);
    }
    if (dataAtualizacao.present) {
      map['data_atualizacao'] = Variable<String>(dataAtualizacao.value);
    }
    if (syncStatus.present) {
      map['sync_status'] = Variable<String>(syncStatus.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('ClientesCompanion(')
          ..write('id: $id, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('nomeCompleto: $nomeCompleto, ')
          ..write('cpf: $cpf, ')
          ..write('telefone: $telefone, ')
          ..write('email: $email, ')
          ..write('enderecoLogradouro: $enderecoLogradouro, ')
          ..write('enderecoNumero: $enderecoNumero, ')
          ..write('enderecoBairro: $enderecoBairro, ')
          ..write('enderecoCidade: $enderecoCidade, ')
          ..write('enderecoEstado: $enderecoEstado, ')
          ..write('enderecoCep: $enderecoCep, ')
          ..write('ativo: $ativo, ')
          ..write('dataCriacao: $dataCriacao, ')
          ..write('dataAtualizacao: $dataAtualizacao, ')
          ..write('syncStatus: $syncStatus, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $FormasPagamentoTable extends FormasPagamento
    with TableInfo<$FormasPagamentoTable, FormaPagamento> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $FormasPagamentoTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _usuarioIdMeta = const VerificationMeta(
    'usuarioId',
  );
  @override
  late final GeneratedColumn<String> usuarioId = GeneratedColumn<String>(
    'usuario_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _nomeMeta = const VerificationMeta('nome');
  @override
  late final GeneratedColumn<String> nome = GeneratedColumn<String>(
    'nome',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _tipoMeta = const VerificationMeta('tipo');
  @override
  late final GeneratedColumn<String> tipo = GeneratedColumn<String>(
    'tipo',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _ativoMeta = const VerificationMeta('ativo');
  @override
  late final GeneratedColumn<bool> ativo = GeneratedColumn<bool>(
    'ativo',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("ativo" IN (0, 1))',
    ),
    defaultValue: const Constant(true),
  );
  static const VerificationMeta _aceitaParcelamentoMeta =
      const VerificationMeta('aceitaParcelamento');
  @override
  late final GeneratedColumn<bool> aceitaParcelamento = GeneratedColumn<bool>(
    'aceita_parcelamento',
    aliasedName,
    false,
    type: DriftSqlType.bool,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'CHECK ("aceita_parcelamento" IN (0, 1))',
    ),
    defaultValue: const Constant(false),
  );
  static const VerificationMeta _dataAtualizacaoMeta = const VerificationMeta(
    'dataAtualizacao',
  );
  @override
  late final GeneratedColumn<String> dataAtualizacao = GeneratedColumn<String>(
    'data_atualizacao',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    usuarioId,
    nome,
    tipo,
    ativo,
    aceitaParcelamento,
    dataAtualizacao,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'formas_pagamento';
  @override
  VerificationContext validateIntegrity(
    Insertable<FormaPagamento> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('usuario_id')) {
      context.handle(
        _usuarioIdMeta,
        usuarioId.isAcceptableOrUnknown(data['usuario_id']!, _usuarioIdMeta),
      );
    } else if (isInserting) {
      context.missing(_usuarioIdMeta);
    }
    if (data.containsKey('nome')) {
      context.handle(
        _nomeMeta,
        nome.isAcceptableOrUnknown(data['nome']!, _nomeMeta),
      );
    } else if (isInserting) {
      context.missing(_nomeMeta);
    }
    if (data.containsKey('tipo')) {
      context.handle(
        _tipoMeta,
        tipo.isAcceptableOrUnknown(data['tipo']!, _tipoMeta),
      );
    } else if (isInserting) {
      context.missing(_tipoMeta);
    }
    if (data.containsKey('ativo')) {
      context.handle(
        _ativoMeta,
        ativo.isAcceptableOrUnknown(data['ativo']!, _ativoMeta),
      );
    }
    if (data.containsKey('aceita_parcelamento')) {
      context.handle(
        _aceitaParcelamentoMeta,
        aceitaParcelamento.isAcceptableOrUnknown(
          data['aceita_parcelamento']!,
          _aceitaParcelamentoMeta,
        ),
      );
    }
    if (data.containsKey('data_atualizacao')) {
      context.handle(
        _dataAtualizacaoMeta,
        dataAtualizacao.isAcceptableOrUnknown(
          data['data_atualizacao']!,
          _dataAtualizacaoMeta,
        ),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  FormaPagamento map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return FormaPagamento(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      usuarioId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}usuario_id'],
      )!,
      nome: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}nome'],
      )!,
      tipo: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}tipo'],
      )!,
      ativo: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}ativo'],
      )!,
      aceitaParcelamento: attachedDatabase.typeMapping.read(
        DriftSqlType.bool,
        data['${effectivePrefix}aceita_parcelamento'],
      )!,
      dataAtualizacao: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_atualizacao'],
      ),
    );
  }

  @override
  $FormasPagamentoTable createAlias(String alias) {
    return $FormasPagamentoTable(attachedDatabase, alias);
  }
}

class FormaPagamento extends DataClass implements Insertable<FormaPagamento> {
  final String id;
  final String usuarioId;
  final String nome;
  final String tipo;
  final bool ativo;
  final bool aceitaParcelamento;
  final String? dataAtualizacao;
  const FormaPagamento({
    required this.id,
    required this.usuarioId,
    required this.nome,
    required this.tipo,
    required this.ativo,
    required this.aceitaParcelamento,
    this.dataAtualizacao,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['usuario_id'] = Variable<String>(usuarioId);
    map['nome'] = Variable<String>(nome);
    map['tipo'] = Variable<String>(tipo);
    map['ativo'] = Variable<bool>(ativo);
    map['aceita_parcelamento'] = Variable<bool>(aceitaParcelamento);
    if (!nullToAbsent || dataAtualizacao != null) {
      map['data_atualizacao'] = Variable<String>(dataAtualizacao);
    }
    return map;
  }

  FormasPagamentoCompanion toCompanion(bool nullToAbsent) {
    return FormasPagamentoCompanion(
      id: Value(id),
      usuarioId: Value(usuarioId),
      nome: Value(nome),
      tipo: Value(tipo),
      ativo: Value(ativo),
      aceitaParcelamento: Value(aceitaParcelamento),
      dataAtualizacao: dataAtualizacao == null && nullToAbsent
          ? const Value.absent()
          : Value(dataAtualizacao),
    );
  }

  factory FormaPagamento.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return FormaPagamento(
      id: serializer.fromJson<String>(json['id']),
      usuarioId: serializer.fromJson<String>(json['usuarioId']),
      nome: serializer.fromJson<String>(json['nome']),
      tipo: serializer.fromJson<String>(json['tipo']),
      ativo: serializer.fromJson<bool>(json['ativo']),
      aceitaParcelamento: serializer.fromJson<bool>(json['aceitaParcelamento']),
      dataAtualizacao: serializer.fromJson<String?>(json['dataAtualizacao']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'usuarioId': serializer.toJson<String>(usuarioId),
      'nome': serializer.toJson<String>(nome),
      'tipo': serializer.toJson<String>(tipo),
      'ativo': serializer.toJson<bool>(ativo),
      'aceitaParcelamento': serializer.toJson<bool>(aceitaParcelamento),
      'dataAtualizacao': serializer.toJson<String?>(dataAtualizacao),
    };
  }

  FormaPagamento copyWith({
    String? id,
    String? usuarioId,
    String? nome,
    String? tipo,
    bool? ativo,
    bool? aceitaParcelamento,
    Value<String?> dataAtualizacao = const Value.absent(),
  }) => FormaPagamento(
    id: id ?? this.id,
    usuarioId: usuarioId ?? this.usuarioId,
    nome: nome ?? this.nome,
    tipo: tipo ?? this.tipo,
    ativo: ativo ?? this.ativo,
    aceitaParcelamento: aceitaParcelamento ?? this.aceitaParcelamento,
    dataAtualizacao: dataAtualizacao.present
        ? dataAtualizacao.value
        : this.dataAtualizacao,
  );
  FormaPagamento copyWithCompanion(FormasPagamentoCompanion data) {
    return FormaPagamento(
      id: data.id.present ? data.id.value : this.id,
      usuarioId: data.usuarioId.present ? data.usuarioId.value : this.usuarioId,
      nome: data.nome.present ? data.nome.value : this.nome,
      tipo: data.tipo.present ? data.tipo.value : this.tipo,
      ativo: data.ativo.present ? data.ativo.value : this.ativo,
      aceitaParcelamento: data.aceitaParcelamento.present
          ? data.aceitaParcelamento.value
          : this.aceitaParcelamento,
      dataAtualizacao: data.dataAtualizacao.present
          ? data.dataAtualizacao.value
          : this.dataAtualizacao,
    );
  }

  @override
  String toString() {
    return (StringBuffer('FormaPagamento(')
          ..write('id: $id, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('nome: $nome, ')
          ..write('tipo: $tipo, ')
          ..write('ativo: $ativo, ')
          ..write('aceitaParcelamento: $aceitaParcelamento, ')
          ..write('dataAtualizacao: $dataAtualizacao')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    id,
    usuarioId,
    nome,
    tipo,
    ativo,
    aceitaParcelamento,
    dataAtualizacao,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is FormaPagamento &&
          other.id == this.id &&
          other.usuarioId == this.usuarioId &&
          other.nome == this.nome &&
          other.tipo == this.tipo &&
          other.ativo == this.ativo &&
          other.aceitaParcelamento == this.aceitaParcelamento &&
          other.dataAtualizacao == this.dataAtualizacao);
}

class FormasPagamentoCompanion extends UpdateCompanion<FormaPagamento> {
  final Value<String> id;
  final Value<String> usuarioId;
  final Value<String> nome;
  final Value<String> tipo;
  final Value<bool> ativo;
  final Value<bool> aceitaParcelamento;
  final Value<String?> dataAtualizacao;
  final Value<int> rowid;
  const FormasPagamentoCompanion({
    this.id = const Value.absent(),
    this.usuarioId = const Value.absent(),
    this.nome = const Value.absent(),
    this.tipo = const Value.absent(),
    this.ativo = const Value.absent(),
    this.aceitaParcelamento = const Value.absent(),
    this.dataAtualizacao = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  FormasPagamentoCompanion.insert({
    required String id,
    required String usuarioId,
    required String nome,
    required String tipo,
    this.ativo = const Value.absent(),
    this.aceitaParcelamento = const Value.absent(),
    this.dataAtualizacao = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       usuarioId = Value(usuarioId),
       nome = Value(nome),
       tipo = Value(tipo);
  static Insertable<FormaPagamento> custom({
    Expression<String>? id,
    Expression<String>? usuarioId,
    Expression<String>? nome,
    Expression<String>? tipo,
    Expression<bool>? ativo,
    Expression<bool>? aceitaParcelamento,
    Expression<String>? dataAtualizacao,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (usuarioId != null) 'usuario_id': usuarioId,
      if (nome != null) 'nome': nome,
      if (tipo != null) 'tipo': tipo,
      if (ativo != null) 'ativo': ativo,
      if (aceitaParcelamento != null) 'aceita_parcelamento': aceitaParcelamento,
      if (dataAtualizacao != null) 'data_atualizacao': dataAtualizacao,
      if (rowid != null) 'rowid': rowid,
    });
  }

  FormasPagamentoCompanion copyWith({
    Value<String>? id,
    Value<String>? usuarioId,
    Value<String>? nome,
    Value<String>? tipo,
    Value<bool>? ativo,
    Value<bool>? aceitaParcelamento,
    Value<String?>? dataAtualizacao,
    Value<int>? rowid,
  }) {
    return FormasPagamentoCompanion(
      id: id ?? this.id,
      usuarioId: usuarioId ?? this.usuarioId,
      nome: nome ?? this.nome,
      tipo: tipo ?? this.tipo,
      ativo: ativo ?? this.ativo,
      aceitaParcelamento: aceitaParcelamento ?? this.aceitaParcelamento,
      dataAtualizacao: dataAtualizacao ?? this.dataAtualizacao,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (usuarioId.present) {
      map['usuario_id'] = Variable<String>(usuarioId.value);
    }
    if (nome.present) {
      map['nome'] = Variable<String>(nome.value);
    }
    if (tipo.present) {
      map['tipo'] = Variable<String>(tipo.value);
    }
    if (ativo.present) {
      map['ativo'] = Variable<bool>(ativo.value);
    }
    if (aceitaParcelamento.present) {
      map['aceita_parcelamento'] = Variable<bool>(aceitaParcelamento.value);
    }
    if (dataAtualizacao.present) {
      map['data_atualizacao'] = Variable<String>(dataAtualizacao.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('FormasPagamentoCompanion(')
          ..write('id: $id, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('nome: $nome, ')
          ..write('tipo: $tipo, ')
          ..write('ativo: $ativo, ')
          ..write('aceitaParcelamento: $aceitaParcelamento, ')
          ..write('dataAtualizacao: $dataAtualizacao, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $VendasTable extends Vendas with TableInfo<$VendasTable, Venda> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $VendasTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _usuarioIdMeta = const VerificationMeta(
    'usuarioId',
  );
  @override
  late final GeneratedColumn<String> usuarioId = GeneratedColumn<String>(
    'usuario_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _clienteIdMeta = const VerificationMeta(
    'clienteId',
  );
  @override
  late final GeneratedColumn<String> clienteId = GeneratedColumn<String>(
    'cliente_id',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _colaboradorVendedorIdMeta =
      const VerificationMeta('colaboradorVendedorId');
  @override
  late final GeneratedColumn<String> colaboradorVendedorId =
      GeneratedColumn<String>(
        'colaborador_vendedor_id',
        aliasedName,
        true,
        type: DriftSqlType.string,
        requiredDuringInsert: false,
      );
  static const VerificationMeta _formaPagamentoIdMeta = const VerificationMeta(
    'formaPagamentoId',
  );
  @override
  late final GeneratedColumn<String> formaPagamentoId = GeneratedColumn<String>(
    'forma_pagamento_id',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _dataVendaMeta = const VerificationMeta(
    'dataVenda',
  );
  @override
  late final GeneratedColumn<String> dataVenda = GeneratedColumn<String>(
    'data_venda',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _valorTotalMeta = const VerificationMeta(
    'valorTotal',
  );
  @override
  late final GeneratedColumn<double> valorTotal = GeneratedColumn<double>(
    'valor_total',
    aliasedName,
    false,
    type: DriftSqlType.double,
    requiredDuringInsert: false,
    defaultValue: const Constant(0.0),
  );
  static const VerificationMeta _numeroParcelasMeta = const VerificationMeta(
    'numeroParcelas',
  );
  @override
  late final GeneratedColumn<int> numeroParcelas = GeneratedColumn<int>(
    'numero_parcelas',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(1),
  );
  static const VerificationMeta _statusVendaCodigoMeta = const VerificationMeta(
    'statusVendaCodigo',
  );
  @override
  late final GeneratedColumn<String> statusVendaCodigo =
      GeneratedColumn<String>(
        'status_venda_codigo',
        aliasedName,
        false,
        type: DriftSqlType.string,
        requiredDuringInsert: false,
        defaultValue: const Constant('QUITADA'),
      );
  static const VerificationMeta _observacoesMeta = const VerificationMeta(
    'observacoes',
  );
  @override
  late final GeneratedColumn<String> observacoes = GeneratedColumn<String>(
    'observacoes',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _tipoVendaMeta = const VerificationMeta(
    'tipoVenda',
  );
  @override
  late final GeneratedColumn<String> tipoVenda = GeneratedColumn<String>(
    'tipo_venda',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
    defaultValue: const Constant('BALCAO'),
  );
  static const VerificationMeta _acrescimoValorMeta = const VerificationMeta(
    'acrescimoValor',
  );
  @override
  late final GeneratedColumn<double> acrescimoValor = GeneratedColumn<double>(
    'acrescimo_valor',
    aliasedName,
    false,
    type: DriftSqlType.double,
    requiredDuringInsert: false,
    defaultValue: const Constant(0.0),
  );
  static const VerificationMeta _acrescimoTipoMeta = const VerificationMeta(
    'acrescimoTipo',
  );
  @override
  late final GeneratedColumn<String> acrescimoTipo = GeneratedColumn<String>(
    'acrescimo_tipo',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _descontoGlobalValorMeta =
      const VerificationMeta('descontoGlobalValor');
  @override
  late final GeneratedColumn<double> descontoGlobalValor =
      GeneratedColumn<double>(
        'desconto_global_valor',
        aliasedName,
        false,
        type: DriftSqlType.double,
        requiredDuringInsert: false,
        defaultValue: const Constant(0.0),
      );
  static const VerificationMeta _descontoGlobalTipoMeta =
      const VerificationMeta('descontoGlobalTipo');
  @override
  late final GeneratedColumn<String> descontoGlobalTipo =
      GeneratedColumn<String>(
        'desconto_global_tipo',
        aliasedName,
        true,
        type: DriftSqlType.string,
        requiredDuringInsert: false,
      );
  static const VerificationMeta _cpfConsumidorMeta = const VerificationMeta(
    'cpfConsumidor',
  );
  @override
  late final GeneratedColumn<String> cpfConsumidor = GeneratedColumn<String>(
    'cpf_consumidor',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _dataPrimeiroVencimentoMeta =
      const VerificationMeta('dataPrimeiroVencimento');
  @override
  late final GeneratedColumn<String> dataPrimeiroVencimento =
      GeneratedColumn<String>(
        'data_primeiro_vencimento',
        aliasedName,
        true,
        type: DriftSqlType.string,
        requiredDuringInsert: false,
      );
  static const VerificationMeta _dataCriacaoMeta = const VerificationMeta(
    'dataCriacao',
  );
  @override
  late final GeneratedColumn<String> dataCriacao = GeneratedColumn<String>(
    'data_criacao',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _dataAtualizacaoMeta = const VerificationMeta(
    'dataAtualizacao',
  );
  @override
  late final GeneratedColumn<String> dataAtualizacao = GeneratedColumn<String>(
    'data_atualizacao',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _syncStatusMeta = const VerificationMeta(
    'syncStatus',
  );
  @override
  late final GeneratedColumn<String> syncStatus = GeneratedColumn<String>(
    'sync_status',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
    defaultValue: const Constant('pending'),
  );
  static const VerificationMeta _syncErrorMeta = const VerificationMeta(
    'syncError',
  );
  @override
  late final GeneratedColumn<String> syncError = GeneratedColumn<String>(
    'sync_error',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _syncTentativasMeta = const VerificationMeta(
    'syncTentativas',
  );
  @override
  late final GeneratedColumn<int> syncTentativas = GeneratedColumn<int>(
    'sync_tentativas',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    usuarioId,
    clienteId,
    colaboradorVendedorId,
    formaPagamentoId,
    dataVenda,
    valorTotal,
    numeroParcelas,
    statusVendaCodigo,
    observacoes,
    tipoVenda,
    acrescimoValor,
    acrescimoTipo,
    descontoGlobalValor,
    descontoGlobalTipo,
    cpfConsumidor,
    dataPrimeiroVencimento,
    dataCriacao,
    dataAtualizacao,
    syncStatus,
    syncError,
    syncTentativas,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'vendas';
  @override
  VerificationContext validateIntegrity(
    Insertable<Venda> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('usuario_id')) {
      context.handle(
        _usuarioIdMeta,
        usuarioId.isAcceptableOrUnknown(data['usuario_id']!, _usuarioIdMeta),
      );
    } else if (isInserting) {
      context.missing(_usuarioIdMeta);
    }
    if (data.containsKey('cliente_id')) {
      context.handle(
        _clienteIdMeta,
        clienteId.isAcceptableOrUnknown(data['cliente_id']!, _clienteIdMeta),
      );
    }
    if (data.containsKey('colaborador_vendedor_id')) {
      context.handle(
        _colaboradorVendedorIdMeta,
        colaboradorVendedorId.isAcceptableOrUnknown(
          data['colaborador_vendedor_id']!,
          _colaboradorVendedorIdMeta,
        ),
      );
    }
    if (data.containsKey('forma_pagamento_id')) {
      context.handle(
        _formaPagamentoIdMeta,
        formaPagamentoId.isAcceptableOrUnknown(
          data['forma_pagamento_id']!,
          _formaPagamentoIdMeta,
        ),
      );
    }
    if (data.containsKey('data_venda')) {
      context.handle(
        _dataVendaMeta,
        dataVenda.isAcceptableOrUnknown(data['data_venda']!, _dataVendaMeta),
      );
    } else if (isInserting) {
      context.missing(_dataVendaMeta);
    }
    if (data.containsKey('valor_total')) {
      context.handle(
        _valorTotalMeta,
        valorTotal.isAcceptableOrUnknown(data['valor_total']!, _valorTotalMeta),
      );
    }
    if (data.containsKey('numero_parcelas')) {
      context.handle(
        _numeroParcelasMeta,
        numeroParcelas.isAcceptableOrUnknown(
          data['numero_parcelas']!,
          _numeroParcelasMeta,
        ),
      );
    }
    if (data.containsKey('status_venda_codigo')) {
      context.handle(
        _statusVendaCodigoMeta,
        statusVendaCodigo.isAcceptableOrUnknown(
          data['status_venda_codigo']!,
          _statusVendaCodigoMeta,
        ),
      );
    }
    if (data.containsKey('observacoes')) {
      context.handle(
        _observacoesMeta,
        observacoes.isAcceptableOrUnknown(
          data['observacoes']!,
          _observacoesMeta,
        ),
      );
    }
    if (data.containsKey('tipo_venda')) {
      context.handle(
        _tipoVendaMeta,
        tipoVenda.isAcceptableOrUnknown(data['tipo_venda']!, _tipoVendaMeta),
      );
    }
    if (data.containsKey('acrescimo_valor')) {
      context.handle(
        _acrescimoValorMeta,
        acrescimoValor.isAcceptableOrUnknown(
          data['acrescimo_valor']!,
          _acrescimoValorMeta,
        ),
      );
    }
    if (data.containsKey('acrescimo_tipo')) {
      context.handle(
        _acrescimoTipoMeta,
        acrescimoTipo.isAcceptableOrUnknown(
          data['acrescimo_tipo']!,
          _acrescimoTipoMeta,
        ),
      );
    }
    if (data.containsKey('desconto_global_valor')) {
      context.handle(
        _descontoGlobalValorMeta,
        descontoGlobalValor.isAcceptableOrUnknown(
          data['desconto_global_valor']!,
          _descontoGlobalValorMeta,
        ),
      );
    }
    if (data.containsKey('desconto_global_tipo')) {
      context.handle(
        _descontoGlobalTipoMeta,
        descontoGlobalTipo.isAcceptableOrUnknown(
          data['desconto_global_tipo']!,
          _descontoGlobalTipoMeta,
        ),
      );
    }
    if (data.containsKey('cpf_consumidor')) {
      context.handle(
        _cpfConsumidorMeta,
        cpfConsumidor.isAcceptableOrUnknown(
          data['cpf_consumidor']!,
          _cpfConsumidorMeta,
        ),
      );
    }
    if (data.containsKey('data_primeiro_vencimento')) {
      context.handle(
        _dataPrimeiroVencimentoMeta,
        dataPrimeiroVencimento.isAcceptableOrUnknown(
          data['data_primeiro_vencimento']!,
          _dataPrimeiroVencimentoMeta,
        ),
      );
    }
    if (data.containsKey('data_criacao')) {
      context.handle(
        _dataCriacaoMeta,
        dataCriacao.isAcceptableOrUnknown(
          data['data_criacao']!,
          _dataCriacaoMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_dataCriacaoMeta);
    }
    if (data.containsKey('data_atualizacao')) {
      context.handle(
        _dataAtualizacaoMeta,
        dataAtualizacao.isAcceptableOrUnknown(
          data['data_atualizacao']!,
          _dataAtualizacaoMeta,
        ),
      );
    }
    if (data.containsKey('sync_status')) {
      context.handle(
        _syncStatusMeta,
        syncStatus.isAcceptableOrUnknown(data['sync_status']!, _syncStatusMeta),
      );
    }
    if (data.containsKey('sync_error')) {
      context.handle(
        _syncErrorMeta,
        syncError.isAcceptableOrUnknown(data['sync_error']!, _syncErrorMeta),
      );
    }
    if (data.containsKey('sync_tentativas')) {
      context.handle(
        _syncTentativasMeta,
        syncTentativas.isAcceptableOrUnknown(
          data['sync_tentativas']!,
          _syncTentativasMeta,
        ),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  Venda map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return Venda(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      usuarioId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}usuario_id'],
      )!,
      clienteId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}cliente_id'],
      ),
      colaboradorVendedorId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}colaborador_vendedor_id'],
      ),
      formaPagamentoId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}forma_pagamento_id'],
      ),
      dataVenda: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_venda'],
      )!,
      valorTotal: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}valor_total'],
      )!,
      numeroParcelas: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}numero_parcelas'],
      )!,
      statusVendaCodigo: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status_venda_codigo'],
      )!,
      observacoes: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}observacoes'],
      ),
      tipoVenda: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}tipo_venda'],
      )!,
      acrescimoValor: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}acrescimo_valor'],
      )!,
      acrescimoTipo: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}acrescimo_tipo'],
      ),
      descontoGlobalValor: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}desconto_global_valor'],
      )!,
      descontoGlobalTipo: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}desconto_global_tipo'],
      ),
      cpfConsumidor: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}cpf_consumidor'],
      ),
      dataPrimeiroVencimento: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_primeiro_vencimento'],
      ),
      dataCriacao: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_criacao'],
      )!,
      dataAtualizacao: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_atualizacao'],
      ),
      syncStatus: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}sync_status'],
      )!,
      syncError: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}sync_error'],
      ),
      syncTentativas: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}sync_tentativas'],
      )!,
    );
  }

  @override
  $VendasTable createAlias(String alias) {
    return $VendasTable(attachedDatabase, alias);
  }
}

class Venda extends DataClass implements Insertable<Venda> {
  final String id;
  final String usuarioId;
  final String? clienteId;
  final String? colaboradorVendedorId;
  final String? formaPagamentoId;
  final String dataVenda;
  final double valorTotal;
  final int numeroParcelas;
  final String statusVendaCodigo;
  final String? observacoes;
  final String tipoVenda;
  final double acrescimoValor;
  final String? acrescimoTipo;
  final double descontoGlobalValor;
  final String? descontoGlobalTipo;
  final String? cpfConsumidor;
  final String? dataPrimeiroVencimento;
  final String dataCriacao;
  final String? dataAtualizacao;
  final String syncStatus;
  final String? syncError;
  final int syncTentativas;
  const Venda({
    required this.id,
    required this.usuarioId,
    this.clienteId,
    this.colaboradorVendedorId,
    this.formaPagamentoId,
    required this.dataVenda,
    required this.valorTotal,
    required this.numeroParcelas,
    required this.statusVendaCodigo,
    this.observacoes,
    required this.tipoVenda,
    required this.acrescimoValor,
    this.acrescimoTipo,
    required this.descontoGlobalValor,
    this.descontoGlobalTipo,
    this.cpfConsumidor,
    this.dataPrimeiroVencimento,
    required this.dataCriacao,
    this.dataAtualizacao,
    required this.syncStatus,
    this.syncError,
    required this.syncTentativas,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['usuario_id'] = Variable<String>(usuarioId);
    if (!nullToAbsent || clienteId != null) {
      map['cliente_id'] = Variable<String>(clienteId);
    }
    if (!nullToAbsent || colaboradorVendedorId != null) {
      map['colaborador_vendedor_id'] = Variable<String>(colaboradorVendedorId);
    }
    if (!nullToAbsent || formaPagamentoId != null) {
      map['forma_pagamento_id'] = Variable<String>(formaPagamentoId);
    }
    map['data_venda'] = Variable<String>(dataVenda);
    map['valor_total'] = Variable<double>(valorTotal);
    map['numero_parcelas'] = Variable<int>(numeroParcelas);
    map['status_venda_codigo'] = Variable<String>(statusVendaCodigo);
    if (!nullToAbsent || observacoes != null) {
      map['observacoes'] = Variable<String>(observacoes);
    }
    map['tipo_venda'] = Variable<String>(tipoVenda);
    map['acrescimo_valor'] = Variable<double>(acrescimoValor);
    if (!nullToAbsent || acrescimoTipo != null) {
      map['acrescimo_tipo'] = Variable<String>(acrescimoTipo);
    }
    map['desconto_global_valor'] = Variable<double>(descontoGlobalValor);
    if (!nullToAbsent || descontoGlobalTipo != null) {
      map['desconto_global_tipo'] = Variable<String>(descontoGlobalTipo);
    }
    if (!nullToAbsent || cpfConsumidor != null) {
      map['cpf_consumidor'] = Variable<String>(cpfConsumidor);
    }
    if (!nullToAbsent || dataPrimeiroVencimento != null) {
      map['data_primeiro_vencimento'] = Variable<String>(
        dataPrimeiroVencimento,
      );
    }
    map['data_criacao'] = Variable<String>(dataCriacao);
    if (!nullToAbsent || dataAtualizacao != null) {
      map['data_atualizacao'] = Variable<String>(dataAtualizacao);
    }
    map['sync_status'] = Variable<String>(syncStatus);
    if (!nullToAbsent || syncError != null) {
      map['sync_error'] = Variable<String>(syncError);
    }
    map['sync_tentativas'] = Variable<int>(syncTentativas);
    return map;
  }

  VendasCompanion toCompanion(bool nullToAbsent) {
    return VendasCompanion(
      id: Value(id),
      usuarioId: Value(usuarioId),
      clienteId: clienteId == null && nullToAbsent
          ? const Value.absent()
          : Value(clienteId),
      colaboradorVendedorId: colaboradorVendedorId == null && nullToAbsent
          ? const Value.absent()
          : Value(colaboradorVendedorId),
      formaPagamentoId: formaPagamentoId == null && nullToAbsent
          ? const Value.absent()
          : Value(formaPagamentoId),
      dataVenda: Value(dataVenda),
      valorTotal: Value(valorTotal),
      numeroParcelas: Value(numeroParcelas),
      statusVendaCodigo: Value(statusVendaCodigo),
      observacoes: observacoes == null && nullToAbsent
          ? const Value.absent()
          : Value(observacoes),
      tipoVenda: Value(tipoVenda),
      acrescimoValor: Value(acrescimoValor),
      acrescimoTipo: acrescimoTipo == null && nullToAbsent
          ? const Value.absent()
          : Value(acrescimoTipo),
      descontoGlobalValor: Value(descontoGlobalValor),
      descontoGlobalTipo: descontoGlobalTipo == null && nullToAbsent
          ? const Value.absent()
          : Value(descontoGlobalTipo),
      cpfConsumidor: cpfConsumidor == null && nullToAbsent
          ? const Value.absent()
          : Value(cpfConsumidor),
      dataPrimeiroVencimento: dataPrimeiroVencimento == null && nullToAbsent
          ? const Value.absent()
          : Value(dataPrimeiroVencimento),
      dataCriacao: Value(dataCriacao),
      dataAtualizacao: dataAtualizacao == null && nullToAbsent
          ? const Value.absent()
          : Value(dataAtualizacao),
      syncStatus: Value(syncStatus),
      syncError: syncError == null && nullToAbsent
          ? const Value.absent()
          : Value(syncError),
      syncTentativas: Value(syncTentativas),
    );
  }

  factory Venda.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return Venda(
      id: serializer.fromJson<String>(json['id']),
      usuarioId: serializer.fromJson<String>(json['usuarioId']),
      clienteId: serializer.fromJson<String?>(json['clienteId']),
      colaboradorVendedorId: serializer.fromJson<String?>(
        json['colaboradorVendedorId'],
      ),
      formaPagamentoId: serializer.fromJson<String?>(json['formaPagamentoId']),
      dataVenda: serializer.fromJson<String>(json['dataVenda']),
      valorTotal: serializer.fromJson<double>(json['valorTotal']),
      numeroParcelas: serializer.fromJson<int>(json['numeroParcelas']),
      statusVendaCodigo: serializer.fromJson<String>(json['statusVendaCodigo']),
      observacoes: serializer.fromJson<String?>(json['observacoes']),
      tipoVenda: serializer.fromJson<String>(json['tipoVenda']),
      acrescimoValor: serializer.fromJson<double>(json['acrescimoValor']),
      acrescimoTipo: serializer.fromJson<String?>(json['acrescimoTipo']),
      descontoGlobalValor: serializer.fromJson<double>(
        json['descontoGlobalValor'],
      ),
      descontoGlobalTipo: serializer.fromJson<String?>(
        json['descontoGlobalTipo'],
      ),
      cpfConsumidor: serializer.fromJson<String?>(json['cpfConsumidor']),
      dataPrimeiroVencimento: serializer.fromJson<String?>(
        json['dataPrimeiroVencimento'],
      ),
      dataCriacao: serializer.fromJson<String>(json['dataCriacao']),
      dataAtualizacao: serializer.fromJson<String?>(json['dataAtualizacao']),
      syncStatus: serializer.fromJson<String>(json['syncStatus']),
      syncError: serializer.fromJson<String?>(json['syncError']),
      syncTentativas: serializer.fromJson<int>(json['syncTentativas']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'usuarioId': serializer.toJson<String>(usuarioId),
      'clienteId': serializer.toJson<String?>(clienteId),
      'colaboradorVendedorId': serializer.toJson<String?>(
        colaboradorVendedorId,
      ),
      'formaPagamentoId': serializer.toJson<String?>(formaPagamentoId),
      'dataVenda': serializer.toJson<String>(dataVenda),
      'valorTotal': serializer.toJson<double>(valorTotal),
      'numeroParcelas': serializer.toJson<int>(numeroParcelas),
      'statusVendaCodigo': serializer.toJson<String>(statusVendaCodigo),
      'observacoes': serializer.toJson<String?>(observacoes),
      'tipoVenda': serializer.toJson<String>(tipoVenda),
      'acrescimoValor': serializer.toJson<double>(acrescimoValor),
      'acrescimoTipo': serializer.toJson<String?>(acrescimoTipo),
      'descontoGlobalValor': serializer.toJson<double>(descontoGlobalValor),
      'descontoGlobalTipo': serializer.toJson<String?>(descontoGlobalTipo),
      'cpfConsumidor': serializer.toJson<String?>(cpfConsumidor),
      'dataPrimeiroVencimento': serializer.toJson<String?>(
        dataPrimeiroVencimento,
      ),
      'dataCriacao': serializer.toJson<String>(dataCriacao),
      'dataAtualizacao': serializer.toJson<String?>(dataAtualizacao),
      'syncStatus': serializer.toJson<String>(syncStatus),
      'syncError': serializer.toJson<String?>(syncError),
      'syncTentativas': serializer.toJson<int>(syncTentativas),
    };
  }

  Venda copyWith({
    String? id,
    String? usuarioId,
    Value<String?> clienteId = const Value.absent(),
    Value<String?> colaboradorVendedorId = const Value.absent(),
    Value<String?> formaPagamentoId = const Value.absent(),
    String? dataVenda,
    double? valorTotal,
    int? numeroParcelas,
    String? statusVendaCodigo,
    Value<String?> observacoes = const Value.absent(),
    String? tipoVenda,
    double? acrescimoValor,
    Value<String?> acrescimoTipo = const Value.absent(),
    double? descontoGlobalValor,
    Value<String?> descontoGlobalTipo = const Value.absent(),
    Value<String?> cpfConsumidor = const Value.absent(),
    Value<String?> dataPrimeiroVencimento = const Value.absent(),
    String? dataCriacao,
    Value<String?> dataAtualizacao = const Value.absent(),
    String? syncStatus,
    Value<String?> syncError = const Value.absent(),
    int? syncTentativas,
  }) => Venda(
    id: id ?? this.id,
    usuarioId: usuarioId ?? this.usuarioId,
    clienteId: clienteId.present ? clienteId.value : this.clienteId,
    colaboradorVendedorId: colaboradorVendedorId.present
        ? colaboradorVendedorId.value
        : this.colaboradorVendedorId,
    formaPagamentoId: formaPagamentoId.present
        ? formaPagamentoId.value
        : this.formaPagamentoId,
    dataVenda: dataVenda ?? this.dataVenda,
    valorTotal: valorTotal ?? this.valorTotal,
    numeroParcelas: numeroParcelas ?? this.numeroParcelas,
    statusVendaCodigo: statusVendaCodigo ?? this.statusVendaCodigo,
    observacoes: observacoes.present ? observacoes.value : this.observacoes,
    tipoVenda: tipoVenda ?? this.tipoVenda,
    acrescimoValor: acrescimoValor ?? this.acrescimoValor,
    acrescimoTipo: acrescimoTipo.present
        ? acrescimoTipo.value
        : this.acrescimoTipo,
    descontoGlobalValor: descontoGlobalValor ?? this.descontoGlobalValor,
    descontoGlobalTipo: descontoGlobalTipo.present
        ? descontoGlobalTipo.value
        : this.descontoGlobalTipo,
    cpfConsumidor: cpfConsumidor.present
        ? cpfConsumidor.value
        : this.cpfConsumidor,
    dataPrimeiroVencimento: dataPrimeiroVencimento.present
        ? dataPrimeiroVencimento.value
        : this.dataPrimeiroVencimento,
    dataCriacao: dataCriacao ?? this.dataCriacao,
    dataAtualizacao: dataAtualizacao.present
        ? dataAtualizacao.value
        : this.dataAtualizacao,
    syncStatus: syncStatus ?? this.syncStatus,
    syncError: syncError.present ? syncError.value : this.syncError,
    syncTentativas: syncTentativas ?? this.syncTentativas,
  );
  Venda copyWithCompanion(VendasCompanion data) {
    return Venda(
      id: data.id.present ? data.id.value : this.id,
      usuarioId: data.usuarioId.present ? data.usuarioId.value : this.usuarioId,
      clienteId: data.clienteId.present ? data.clienteId.value : this.clienteId,
      colaboradorVendedorId: data.colaboradorVendedorId.present
          ? data.colaboradorVendedorId.value
          : this.colaboradorVendedorId,
      formaPagamentoId: data.formaPagamentoId.present
          ? data.formaPagamentoId.value
          : this.formaPagamentoId,
      dataVenda: data.dataVenda.present ? data.dataVenda.value : this.dataVenda,
      valorTotal: data.valorTotal.present
          ? data.valorTotal.value
          : this.valorTotal,
      numeroParcelas: data.numeroParcelas.present
          ? data.numeroParcelas.value
          : this.numeroParcelas,
      statusVendaCodigo: data.statusVendaCodigo.present
          ? data.statusVendaCodigo.value
          : this.statusVendaCodigo,
      observacoes: data.observacoes.present
          ? data.observacoes.value
          : this.observacoes,
      tipoVenda: data.tipoVenda.present ? data.tipoVenda.value : this.tipoVenda,
      acrescimoValor: data.acrescimoValor.present
          ? data.acrescimoValor.value
          : this.acrescimoValor,
      acrescimoTipo: data.acrescimoTipo.present
          ? data.acrescimoTipo.value
          : this.acrescimoTipo,
      descontoGlobalValor: data.descontoGlobalValor.present
          ? data.descontoGlobalValor.value
          : this.descontoGlobalValor,
      descontoGlobalTipo: data.descontoGlobalTipo.present
          ? data.descontoGlobalTipo.value
          : this.descontoGlobalTipo,
      cpfConsumidor: data.cpfConsumidor.present
          ? data.cpfConsumidor.value
          : this.cpfConsumidor,
      dataPrimeiroVencimento: data.dataPrimeiroVencimento.present
          ? data.dataPrimeiroVencimento.value
          : this.dataPrimeiroVencimento,
      dataCriacao: data.dataCriacao.present
          ? data.dataCriacao.value
          : this.dataCriacao,
      dataAtualizacao: data.dataAtualizacao.present
          ? data.dataAtualizacao.value
          : this.dataAtualizacao,
      syncStatus: data.syncStatus.present
          ? data.syncStatus.value
          : this.syncStatus,
      syncError: data.syncError.present ? data.syncError.value : this.syncError,
      syncTentativas: data.syncTentativas.present
          ? data.syncTentativas.value
          : this.syncTentativas,
    );
  }

  @override
  String toString() {
    return (StringBuffer('Venda(')
          ..write('id: $id, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('clienteId: $clienteId, ')
          ..write('colaboradorVendedorId: $colaboradorVendedorId, ')
          ..write('formaPagamentoId: $formaPagamentoId, ')
          ..write('dataVenda: $dataVenda, ')
          ..write('valorTotal: $valorTotal, ')
          ..write('numeroParcelas: $numeroParcelas, ')
          ..write('statusVendaCodigo: $statusVendaCodigo, ')
          ..write('observacoes: $observacoes, ')
          ..write('tipoVenda: $tipoVenda, ')
          ..write('acrescimoValor: $acrescimoValor, ')
          ..write('acrescimoTipo: $acrescimoTipo, ')
          ..write('descontoGlobalValor: $descontoGlobalValor, ')
          ..write('descontoGlobalTipo: $descontoGlobalTipo, ')
          ..write('cpfConsumidor: $cpfConsumidor, ')
          ..write('dataPrimeiroVencimento: $dataPrimeiroVencimento, ')
          ..write('dataCriacao: $dataCriacao, ')
          ..write('dataAtualizacao: $dataAtualizacao, ')
          ..write('syncStatus: $syncStatus, ')
          ..write('syncError: $syncError, ')
          ..write('syncTentativas: $syncTentativas')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hashAll([
    id,
    usuarioId,
    clienteId,
    colaboradorVendedorId,
    formaPagamentoId,
    dataVenda,
    valorTotal,
    numeroParcelas,
    statusVendaCodigo,
    observacoes,
    tipoVenda,
    acrescimoValor,
    acrescimoTipo,
    descontoGlobalValor,
    descontoGlobalTipo,
    cpfConsumidor,
    dataPrimeiroVencimento,
    dataCriacao,
    dataAtualizacao,
    syncStatus,
    syncError,
    syncTentativas,
  ]);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is Venda &&
          other.id == this.id &&
          other.usuarioId == this.usuarioId &&
          other.clienteId == this.clienteId &&
          other.colaboradorVendedorId == this.colaboradorVendedorId &&
          other.formaPagamentoId == this.formaPagamentoId &&
          other.dataVenda == this.dataVenda &&
          other.valorTotal == this.valorTotal &&
          other.numeroParcelas == this.numeroParcelas &&
          other.statusVendaCodigo == this.statusVendaCodigo &&
          other.observacoes == this.observacoes &&
          other.tipoVenda == this.tipoVenda &&
          other.acrescimoValor == this.acrescimoValor &&
          other.acrescimoTipo == this.acrescimoTipo &&
          other.descontoGlobalValor == this.descontoGlobalValor &&
          other.descontoGlobalTipo == this.descontoGlobalTipo &&
          other.cpfConsumidor == this.cpfConsumidor &&
          other.dataPrimeiroVencimento == this.dataPrimeiroVencimento &&
          other.dataCriacao == this.dataCriacao &&
          other.dataAtualizacao == this.dataAtualizacao &&
          other.syncStatus == this.syncStatus &&
          other.syncError == this.syncError &&
          other.syncTentativas == this.syncTentativas);
}

class VendasCompanion extends UpdateCompanion<Venda> {
  final Value<String> id;
  final Value<String> usuarioId;
  final Value<String?> clienteId;
  final Value<String?> colaboradorVendedorId;
  final Value<String?> formaPagamentoId;
  final Value<String> dataVenda;
  final Value<double> valorTotal;
  final Value<int> numeroParcelas;
  final Value<String> statusVendaCodigo;
  final Value<String?> observacoes;
  final Value<String> tipoVenda;
  final Value<double> acrescimoValor;
  final Value<String?> acrescimoTipo;
  final Value<double> descontoGlobalValor;
  final Value<String?> descontoGlobalTipo;
  final Value<String?> cpfConsumidor;
  final Value<String?> dataPrimeiroVencimento;
  final Value<String> dataCriacao;
  final Value<String?> dataAtualizacao;
  final Value<String> syncStatus;
  final Value<String?> syncError;
  final Value<int> syncTentativas;
  final Value<int> rowid;
  const VendasCompanion({
    this.id = const Value.absent(),
    this.usuarioId = const Value.absent(),
    this.clienteId = const Value.absent(),
    this.colaboradorVendedorId = const Value.absent(),
    this.formaPagamentoId = const Value.absent(),
    this.dataVenda = const Value.absent(),
    this.valorTotal = const Value.absent(),
    this.numeroParcelas = const Value.absent(),
    this.statusVendaCodigo = const Value.absent(),
    this.observacoes = const Value.absent(),
    this.tipoVenda = const Value.absent(),
    this.acrescimoValor = const Value.absent(),
    this.acrescimoTipo = const Value.absent(),
    this.descontoGlobalValor = const Value.absent(),
    this.descontoGlobalTipo = const Value.absent(),
    this.cpfConsumidor = const Value.absent(),
    this.dataPrimeiroVencimento = const Value.absent(),
    this.dataCriacao = const Value.absent(),
    this.dataAtualizacao = const Value.absent(),
    this.syncStatus = const Value.absent(),
    this.syncError = const Value.absent(),
    this.syncTentativas = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  VendasCompanion.insert({
    required String id,
    required String usuarioId,
    this.clienteId = const Value.absent(),
    this.colaboradorVendedorId = const Value.absent(),
    this.formaPagamentoId = const Value.absent(),
    required String dataVenda,
    this.valorTotal = const Value.absent(),
    this.numeroParcelas = const Value.absent(),
    this.statusVendaCodigo = const Value.absent(),
    this.observacoes = const Value.absent(),
    this.tipoVenda = const Value.absent(),
    this.acrescimoValor = const Value.absent(),
    this.acrescimoTipo = const Value.absent(),
    this.descontoGlobalValor = const Value.absent(),
    this.descontoGlobalTipo = const Value.absent(),
    this.cpfConsumidor = const Value.absent(),
    this.dataPrimeiroVencimento = const Value.absent(),
    required String dataCriacao,
    this.dataAtualizacao = const Value.absent(),
    this.syncStatus = const Value.absent(),
    this.syncError = const Value.absent(),
    this.syncTentativas = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       usuarioId = Value(usuarioId),
       dataVenda = Value(dataVenda),
       dataCriacao = Value(dataCriacao);
  static Insertable<Venda> custom({
    Expression<String>? id,
    Expression<String>? usuarioId,
    Expression<String>? clienteId,
    Expression<String>? colaboradorVendedorId,
    Expression<String>? formaPagamentoId,
    Expression<String>? dataVenda,
    Expression<double>? valorTotal,
    Expression<int>? numeroParcelas,
    Expression<String>? statusVendaCodigo,
    Expression<String>? observacoes,
    Expression<String>? tipoVenda,
    Expression<double>? acrescimoValor,
    Expression<String>? acrescimoTipo,
    Expression<double>? descontoGlobalValor,
    Expression<String>? descontoGlobalTipo,
    Expression<String>? cpfConsumidor,
    Expression<String>? dataPrimeiroVencimento,
    Expression<String>? dataCriacao,
    Expression<String>? dataAtualizacao,
    Expression<String>? syncStatus,
    Expression<String>? syncError,
    Expression<int>? syncTentativas,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (usuarioId != null) 'usuario_id': usuarioId,
      if (clienteId != null) 'cliente_id': clienteId,
      if (colaboradorVendedorId != null)
        'colaborador_vendedor_id': colaboradorVendedorId,
      if (formaPagamentoId != null) 'forma_pagamento_id': formaPagamentoId,
      if (dataVenda != null) 'data_venda': dataVenda,
      if (valorTotal != null) 'valor_total': valorTotal,
      if (numeroParcelas != null) 'numero_parcelas': numeroParcelas,
      if (statusVendaCodigo != null) 'status_venda_codigo': statusVendaCodigo,
      if (observacoes != null) 'observacoes': observacoes,
      if (tipoVenda != null) 'tipo_venda': tipoVenda,
      if (acrescimoValor != null) 'acrescimo_valor': acrescimoValor,
      if (acrescimoTipo != null) 'acrescimo_tipo': acrescimoTipo,
      if (descontoGlobalValor != null)
        'desconto_global_valor': descontoGlobalValor,
      if (descontoGlobalTipo != null)
        'desconto_global_tipo': descontoGlobalTipo,
      if (cpfConsumidor != null) 'cpf_consumidor': cpfConsumidor,
      if (dataPrimeiroVencimento != null)
        'data_primeiro_vencimento': dataPrimeiroVencimento,
      if (dataCriacao != null) 'data_criacao': dataCriacao,
      if (dataAtualizacao != null) 'data_atualizacao': dataAtualizacao,
      if (syncStatus != null) 'sync_status': syncStatus,
      if (syncError != null) 'sync_error': syncError,
      if (syncTentativas != null) 'sync_tentativas': syncTentativas,
      if (rowid != null) 'rowid': rowid,
    });
  }

  VendasCompanion copyWith({
    Value<String>? id,
    Value<String>? usuarioId,
    Value<String?>? clienteId,
    Value<String?>? colaboradorVendedorId,
    Value<String?>? formaPagamentoId,
    Value<String>? dataVenda,
    Value<double>? valorTotal,
    Value<int>? numeroParcelas,
    Value<String>? statusVendaCodigo,
    Value<String?>? observacoes,
    Value<String>? tipoVenda,
    Value<double>? acrescimoValor,
    Value<String?>? acrescimoTipo,
    Value<double>? descontoGlobalValor,
    Value<String?>? descontoGlobalTipo,
    Value<String?>? cpfConsumidor,
    Value<String?>? dataPrimeiroVencimento,
    Value<String>? dataCriacao,
    Value<String?>? dataAtualizacao,
    Value<String>? syncStatus,
    Value<String?>? syncError,
    Value<int>? syncTentativas,
    Value<int>? rowid,
  }) {
    return VendasCompanion(
      id: id ?? this.id,
      usuarioId: usuarioId ?? this.usuarioId,
      clienteId: clienteId ?? this.clienteId,
      colaboradorVendedorId:
          colaboradorVendedorId ?? this.colaboradorVendedorId,
      formaPagamentoId: formaPagamentoId ?? this.formaPagamentoId,
      dataVenda: dataVenda ?? this.dataVenda,
      valorTotal: valorTotal ?? this.valorTotal,
      numeroParcelas: numeroParcelas ?? this.numeroParcelas,
      statusVendaCodigo: statusVendaCodigo ?? this.statusVendaCodigo,
      observacoes: observacoes ?? this.observacoes,
      tipoVenda: tipoVenda ?? this.tipoVenda,
      acrescimoValor: acrescimoValor ?? this.acrescimoValor,
      acrescimoTipo: acrescimoTipo ?? this.acrescimoTipo,
      descontoGlobalValor: descontoGlobalValor ?? this.descontoGlobalValor,
      descontoGlobalTipo: descontoGlobalTipo ?? this.descontoGlobalTipo,
      cpfConsumidor: cpfConsumidor ?? this.cpfConsumidor,
      dataPrimeiroVencimento:
          dataPrimeiroVencimento ?? this.dataPrimeiroVencimento,
      dataCriacao: dataCriacao ?? this.dataCriacao,
      dataAtualizacao: dataAtualizacao ?? this.dataAtualizacao,
      syncStatus: syncStatus ?? this.syncStatus,
      syncError: syncError ?? this.syncError,
      syncTentativas: syncTentativas ?? this.syncTentativas,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (usuarioId.present) {
      map['usuario_id'] = Variable<String>(usuarioId.value);
    }
    if (clienteId.present) {
      map['cliente_id'] = Variable<String>(clienteId.value);
    }
    if (colaboradorVendedorId.present) {
      map['colaborador_vendedor_id'] = Variable<String>(
        colaboradorVendedorId.value,
      );
    }
    if (formaPagamentoId.present) {
      map['forma_pagamento_id'] = Variable<String>(formaPagamentoId.value);
    }
    if (dataVenda.present) {
      map['data_venda'] = Variable<String>(dataVenda.value);
    }
    if (valorTotal.present) {
      map['valor_total'] = Variable<double>(valorTotal.value);
    }
    if (numeroParcelas.present) {
      map['numero_parcelas'] = Variable<int>(numeroParcelas.value);
    }
    if (statusVendaCodigo.present) {
      map['status_venda_codigo'] = Variable<String>(statusVendaCodigo.value);
    }
    if (observacoes.present) {
      map['observacoes'] = Variable<String>(observacoes.value);
    }
    if (tipoVenda.present) {
      map['tipo_venda'] = Variable<String>(tipoVenda.value);
    }
    if (acrescimoValor.present) {
      map['acrescimo_valor'] = Variable<double>(acrescimoValor.value);
    }
    if (acrescimoTipo.present) {
      map['acrescimo_tipo'] = Variable<String>(acrescimoTipo.value);
    }
    if (descontoGlobalValor.present) {
      map['desconto_global_valor'] = Variable<double>(
        descontoGlobalValor.value,
      );
    }
    if (descontoGlobalTipo.present) {
      map['desconto_global_tipo'] = Variable<String>(descontoGlobalTipo.value);
    }
    if (cpfConsumidor.present) {
      map['cpf_consumidor'] = Variable<String>(cpfConsumidor.value);
    }
    if (dataPrimeiroVencimento.present) {
      map['data_primeiro_vencimento'] = Variable<String>(
        dataPrimeiroVencimento.value,
      );
    }
    if (dataCriacao.present) {
      map['data_criacao'] = Variable<String>(dataCriacao.value);
    }
    if (dataAtualizacao.present) {
      map['data_atualizacao'] = Variable<String>(dataAtualizacao.value);
    }
    if (syncStatus.present) {
      map['sync_status'] = Variable<String>(syncStatus.value);
    }
    if (syncError.present) {
      map['sync_error'] = Variable<String>(syncError.value);
    }
    if (syncTentativas.present) {
      map['sync_tentativas'] = Variable<int>(syncTentativas.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('VendasCompanion(')
          ..write('id: $id, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('clienteId: $clienteId, ')
          ..write('colaboradorVendedorId: $colaboradorVendedorId, ')
          ..write('formaPagamentoId: $formaPagamentoId, ')
          ..write('dataVenda: $dataVenda, ')
          ..write('valorTotal: $valorTotal, ')
          ..write('numeroParcelas: $numeroParcelas, ')
          ..write('statusVendaCodigo: $statusVendaCodigo, ')
          ..write('observacoes: $observacoes, ')
          ..write('tipoVenda: $tipoVenda, ')
          ..write('acrescimoValor: $acrescimoValor, ')
          ..write('acrescimoTipo: $acrescimoTipo, ')
          ..write('descontoGlobalValor: $descontoGlobalValor, ')
          ..write('descontoGlobalTipo: $descontoGlobalTipo, ')
          ..write('cpfConsumidor: $cpfConsumidor, ')
          ..write('dataPrimeiroVencimento: $dataPrimeiroVencimento, ')
          ..write('dataCriacao: $dataCriacao, ')
          ..write('dataAtualizacao: $dataAtualizacao, ')
          ..write('syncStatus: $syncStatus, ')
          ..write('syncError: $syncError, ')
          ..write('syncTentativas: $syncTentativas, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $VendaItensTable extends VendaItens
    with TableInfo<$VendaItensTable, VendaItem> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $VendaItensTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _vendaIdMeta = const VerificationMeta(
    'vendaId',
  );
  @override
  late final GeneratedColumn<String> vendaId = GeneratedColumn<String>(
    'venda_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _produtoIdMeta = const VerificationMeta(
    'produtoId',
  );
  @override
  late final GeneratedColumn<String> produtoId = GeneratedColumn<String>(
    'produto_id',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _varianteIdMeta = const VerificationMeta(
    'varianteId',
  );
  @override
  late final GeneratedColumn<String> varianteId = GeneratedColumn<String>(
    'variante_id',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _nomeItemManualMeta = const VerificationMeta(
    'nomeItemManual',
  );
  @override
  late final GeneratedColumn<String> nomeItemManual = GeneratedColumn<String>(
    'nome_item_manual',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _quantidadeMeta = const VerificationMeta(
    'quantidade',
  );
  @override
  late final GeneratedColumn<double> quantidade = GeneratedColumn<double>(
    'quantidade',
    aliasedName,
    false,
    type: DriftSqlType.double,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _precoUnitarioVendaMeta =
      const VerificationMeta('precoUnitarioVenda');
  @override
  late final GeneratedColumn<double> precoUnitarioVenda =
      GeneratedColumn<double>(
        'preco_unitario_venda',
        aliasedName,
        false,
        type: DriftSqlType.double,
        requiredDuringInsert: true,
      );
  static const VerificationMeta _descontoPercentualMeta =
      const VerificationMeta('descontoPercentual');
  @override
  late final GeneratedColumn<double> descontoPercentual =
      GeneratedColumn<double>(
        'desconto_percentual',
        aliasedName,
        false,
        type: DriftSqlType.double,
        requiredDuringInsert: false,
        defaultValue: const Constant(0.0),
      );
  static const VerificationMeta _descontoValorMeta = const VerificationMeta(
    'descontoValor',
  );
  @override
  late final GeneratedColumn<double> descontoValor = GeneratedColumn<double>(
    'desconto_valor',
    aliasedName,
    false,
    type: DriftSqlType.double,
    requiredDuringInsert: false,
    defaultValue: const Constant(0.0),
  );
  static const VerificationMeta _valorTotalItemMeta = const VerificationMeta(
    'valorTotalItem',
  );
  @override
  late final GeneratedColumn<double> valorTotalItem = GeneratedColumn<double>(
    'valor_total_item',
    aliasedName,
    false,
    type: DriftSqlType.double,
    requiredDuringInsert: false,
    defaultValue: const Constant(0.0),
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    vendaId,
    produtoId,
    varianteId,
    nomeItemManual,
    quantidade,
    precoUnitarioVenda,
    descontoPercentual,
    descontoValor,
    valorTotalItem,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'venda_itens';
  @override
  VerificationContext validateIntegrity(
    Insertable<VendaItem> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('venda_id')) {
      context.handle(
        _vendaIdMeta,
        vendaId.isAcceptableOrUnknown(data['venda_id']!, _vendaIdMeta),
      );
    } else if (isInserting) {
      context.missing(_vendaIdMeta);
    }
    if (data.containsKey('produto_id')) {
      context.handle(
        _produtoIdMeta,
        produtoId.isAcceptableOrUnknown(data['produto_id']!, _produtoIdMeta),
      );
    }
    if (data.containsKey('variante_id')) {
      context.handle(
        _varianteIdMeta,
        varianteId.isAcceptableOrUnknown(data['variante_id']!, _varianteIdMeta),
      );
    }
    if (data.containsKey('nome_item_manual')) {
      context.handle(
        _nomeItemManualMeta,
        nomeItemManual.isAcceptableOrUnknown(
          data['nome_item_manual']!,
          _nomeItemManualMeta,
        ),
      );
    }
    if (data.containsKey('quantidade')) {
      context.handle(
        _quantidadeMeta,
        quantidade.isAcceptableOrUnknown(data['quantidade']!, _quantidadeMeta),
      );
    } else if (isInserting) {
      context.missing(_quantidadeMeta);
    }
    if (data.containsKey('preco_unitario_venda')) {
      context.handle(
        _precoUnitarioVendaMeta,
        precoUnitarioVenda.isAcceptableOrUnknown(
          data['preco_unitario_venda']!,
          _precoUnitarioVendaMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_precoUnitarioVendaMeta);
    }
    if (data.containsKey('desconto_percentual')) {
      context.handle(
        _descontoPercentualMeta,
        descontoPercentual.isAcceptableOrUnknown(
          data['desconto_percentual']!,
          _descontoPercentualMeta,
        ),
      );
    }
    if (data.containsKey('desconto_valor')) {
      context.handle(
        _descontoValorMeta,
        descontoValor.isAcceptableOrUnknown(
          data['desconto_valor']!,
          _descontoValorMeta,
        ),
      );
    }
    if (data.containsKey('valor_total_item')) {
      context.handle(
        _valorTotalItemMeta,
        valorTotalItem.isAcceptableOrUnknown(
          data['valor_total_item']!,
          _valorTotalItemMeta,
        ),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  VendaItem map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return VendaItem(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      vendaId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}venda_id'],
      )!,
      produtoId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}produto_id'],
      ),
      varianteId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}variante_id'],
      ),
      nomeItemManual: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}nome_item_manual'],
      ),
      quantidade: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}quantidade'],
      )!,
      precoUnitarioVenda: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}preco_unitario_venda'],
      )!,
      descontoPercentual: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}desconto_percentual'],
      )!,
      descontoValor: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}desconto_valor'],
      )!,
      valorTotalItem: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}valor_total_item'],
      )!,
    );
  }

  @override
  $VendaItensTable createAlias(String alias) {
    return $VendaItensTable(attachedDatabase, alias);
  }
}

class VendaItem extends DataClass implements Insertable<VendaItem> {
  final String id;
  final String vendaId;
  final String? produtoId;
  final String? varianteId;
  final String? nomeItemManual;
  final double quantidade;
  final double precoUnitarioVenda;
  final double descontoPercentual;
  final double descontoValor;
  final double valorTotalItem;
  const VendaItem({
    required this.id,
    required this.vendaId,
    this.produtoId,
    this.varianteId,
    this.nomeItemManual,
    required this.quantidade,
    required this.precoUnitarioVenda,
    required this.descontoPercentual,
    required this.descontoValor,
    required this.valorTotalItem,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['venda_id'] = Variable<String>(vendaId);
    if (!nullToAbsent || produtoId != null) {
      map['produto_id'] = Variable<String>(produtoId);
    }
    if (!nullToAbsent || varianteId != null) {
      map['variante_id'] = Variable<String>(varianteId);
    }
    if (!nullToAbsent || nomeItemManual != null) {
      map['nome_item_manual'] = Variable<String>(nomeItemManual);
    }
    map['quantidade'] = Variable<double>(quantidade);
    map['preco_unitario_venda'] = Variable<double>(precoUnitarioVenda);
    map['desconto_percentual'] = Variable<double>(descontoPercentual);
    map['desconto_valor'] = Variable<double>(descontoValor);
    map['valor_total_item'] = Variable<double>(valorTotalItem);
    return map;
  }

  VendaItensCompanion toCompanion(bool nullToAbsent) {
    return VendaItensCompanion(
      id: Value(id),
      vendaId: Value(vendaId),
      produtoId: produtoId == null && nullToAbsent
          ? const Value.absent()
          : Value(produtoId),
      varianteId: varianteId == null && nullToAbsent
          ? const Value.absent()
          : Value(varianteId),
      nomeItemManual: nomeItemManual == null && nullToAbsent
          ? const Value.absent()
          : Value(nomeItemManual),
      quantidade: Value(quantidade),
      precoUnitarioVenda: Value(precoUnitarioVenda),
      descontoPercentual: Value(descontoPercentual),
      descontoValor: Value(descontoValor),
      valorTotalItem: Value(valorTotalItem),
    );
  }

  factory VendaItem.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return VendaItem(
      id: serializer.fromJson<String>(json['id']),
      vendaId: serializer.fromJson<String>(json['vendaId']),
      produtoId: serializer.fromJson<String?>(json['produtoId']),
      varianteId: serializer.fromJson<String?>(json['varianteId']),
      nomeItemManual: serializer.fromJson<String?>(json['nomeItemManual']),
      quantidade: serializer.fromJson<double>(json['quantidade']),
      precoUnitarioVenda: serializer.fromJson<double>(
        json['precoUnitarioVenda'],
      ),
      descontoPercentual: serializer.fromJson<double>(
        json['descontoPercentual'],
      ),
      descontoValor: serializer.fromJson<double>(json['descontoValor']),
      valorTotalItem: serializer.fromJson<double>(json['valorTotalItem']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'vendaId': serializer.toJson<String>(vendaId),
      'produtoId': serializer.toJson<String?>(produtoId),
      'varianteId': serializer.toJson<String?>(varianteId),
      'nomeItemManual': serializer.toJson<String?>(nomeItemManual),
      'quantidade': serializer.toJson<double>(quantidade),
      'precoUnitarioVenda': serializer.toJson<double>(precoUnitarioVenda),
      'descontoPercentual': serializer.toJson<double>(descontoPercentual),
      'descontoValor': serializer.toJson<double>(descontoValor),
      'valorTotalItem': serializer.toJson<double>(valorTotalItem),
    };
  }

  VendaItem copyWith({
    String? id,
    String? vendaId,
    Value<String?> produtoId = const Value.absent(),
    Value<String?> varianteId = const Value.absent(),
    Value<String?> nomeItemManual = const Value.absent(),
    double? quantidade,
    double? precoUnitarioVenda,
    double? descontoPercentual,
    double? descontoValor,
    double? valorTotalItem,
  }) => VendaItem(
    id: id ?? this.id,
    vendaId: vendaId ?? this.vendaId,
    produtoId: produtoId.present ? produtoId.value : this.produtoId,
    varianteId: varianteId.present ? varianteId.value : this.varianteId,
    nomeItemManual: nomeItemManual.present
        ? nomeItemManual.value
        : this.nomeItemManual,
    quantidade: quantidade ?? this.quantidade,
    precoUnitarioVenda: precoUnitarioVenda ?? this.precoUnitarioVenda,
    descontoPercentual: descontoPercentual ?? this.descontoPercentual,
    descontoValor: descontoValor ?? this.descontoValor,
    valorTotalItem: valorTotalItem ?? this.valorTotalItem,
  );
  VendaItem copyWithCompanion(VendaItensCompanion data) {
    return VendaItem(
      id: data.id.present ? data.id.value : this.id,
      vendaId: data.vendaId.present ? data.vendaId.value : this.vendaId,
      produtoId: data.produtoId.present ? data.produtoId.value : this.produtoId,
      varianteId: data.varianteId.present
          ? data.varianteId.value
          : this.varianteId,
      nomeItemManual: data.nomeItemManual.present
          ? data.nomeItemManual.value
          : this.nomeItemManual,
      quantidade: data.quantidade.present
          ? data.quantidade.value
          : this.quantidade,
      precoUnitarioVenda: data.precoUnitarioVenda.present
          ? data.precoUnitarioVenda.value
          : this.precoUnitarioVenda,
      descontoPercentual: data.descontoPercentual.present
          ? data.descontoPercentual.value
          : this.descontoPercentual,
      descontoValor: data.descontoValor.present
          ? data.descontoValor.value
          : this.descontoValor,
      valorTotalItem: data.valorTotalItem.present
          ? data.valorTotalItem.value
          : this.valorTotalItem,
    );
  }

  @override
  String toString() {
    return (StringBuffer('VendaItem(')
          ..write('id: $id, ')
          ..write('vendaId: $vendaId, ')
          ..write('produtoId: $produtoId, ')
          ..write('varianteId: $varianteId, ')
          ..write('nomeItemManual: $nomeItemManual, ')
          ..write('quantidade: $quantidade, ')
          ..write('precoUnitarioVenda: $precoUnitarioVenda, ')
          ..write('descontoPercentual: $descontoPercentual, ')
          ..write('descontoValor: $descontoValor, ')
          ..write('valorTotalItem: $valorTotalItem')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    id,
    vendaId,
    produtoId,
    varianteId,
    nomeItemManual,
    quantidade,
    precoUnitarioVenda,
    descontoPercentual,
    descontoValor,
    valorTotalItem,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is VendaItem &&
          other.id == this.id &&
          other.vendaId == this.vendaId &&
          other.produtoId == this.produtoId &&
          other.varianteId == this.varianteId &&
          other.nomeItemManual == this.nomeItemManual &&
          other.quantidade == this.quantidade &&
          other.precoUnitarioVenda == this.precoUnitarioVenda &&
          other.descontoPercentual == this.descontoPercentual &&
          other.descontoValor == this.descontoValor &&
          other.valorTotalItem == this.valorTotalItem);
}

class VendaItensCompanion extends UpdateCompanion<VendaItem> {
  final Value<String> id;
  final Value<String> vendaId;
  final Value<String?> produtoId;
  final Value<String?> varianteId;
  final Value<String?> nomeItemManual;
  final Value<double> quantidade;
  final Value<double> precoUnitarioVenda;
  final Value<double> descontoPercentual;
  final Value<double> descontoValor;
  final Value<double> valorTotalItem;
  final Value<int> rowid;
  const VendaItensCompanion({
    this.id = const Value.absent(),
    this.vendaId = const Value.absent(),
    this.produtoId = const Value.absent(),
    this.varianteId = const Value.absent(),
    this.nomeItemManual = const Value.absent(),
    this.quantidade = const Value.absent(),
    this.precoUnitarioVenda = const Value.absent(),
    this.descontoPercentual = const Value.absent(),
    this.descontoValor = const Value.absent(),
    this.valorTotalItem = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  VendaItensCompanion.insert({
    required String id,
    required String vendaId,
    this.produtoId = const Value.absent(),
    this.varianteId = const Value.absent(),
    this.nomeItemManual = const Value.absent(),
    required double quantidade,
    required double precoUnitarioVenda,
    this.descontoPercentual = const Value.absent(),
    this.descontoValor = const Value.absent(),
    this.valorTotalItem = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       vendaId = Value(vendaId),
       quantidade = Value(quantidade),
       precoUnitarioVenda = Value(precoUnitarioVenda);
  static Insertable<VendaItem> custom({
    Expression<String>? id,
    Expression<String>? vendaId,
    Expression<String>? produtoId,
    Expression<String>? varianteId,
    Expression<String>? nomeItemManual,
    Expression<double>? quantidade,
    Expression<double>? precoUnitarioVenda,
    Expression<double>? descontoPercentual,
    Expression<double>? descontoValor,
    Expression<double>? valorTotalItem,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (vendaId != null) 'venda_id': vendaId,
      if (produtoId != null) 'produto_id': produtoId,
      if (varianteId != null) 'variante_id': varianteId,
      if (nomeItemManual != null) 'nome_item_manual': nomeItemManual,
      if (quantidade != null) 'quantidade': quantidade,
      if (precoUnitarioVenda != null)
        'preco_unitario_venda': precoUnitarioVenda,
      if (descontoPercentual != null) 'desconto_percentual': descontoPercentual,
      if (descontoValor != null) 'desconto_valor': descontoValor,
      if (valorTotalItem != null) 'valor_total_item': valorTotalItem,
      if (rowid != null) 'rowid': rowid,
    });
  }

  VendaItensCompanion copyWith({
    Value<String>? id,
    Value<String>? vendaId,
    Value<String?>? produtoId,
    Value<String?>? varianteId,
    Value<String?>? nomeItemManual,
    Value<double>? quantidade,
    Value<double>? precoUnitarioVenda,
    Value<double>? descontoPercentual,
    Value<double>? descontoValor,
    Value<double>? valorTotalItem,
    Value<int>? rowid,
  }) {
    return VendaItensCompanion(
      id: id ?? this.id,
      vendaId: vendaId ?? this.vendaId,
      produtoId: produtoId ?? this.produtoId,
      varianteId: varianteId ?? this.varianteId,
      nomeItemManual: nomeItemManual ?? this.nomeItemManual,
      quantidade: quantidade ?? this.quantidade,
      precoUnitarioVenda: precoUnitarioVenda ?? this.precoUnitarioVenda,
      descontoPercentual: descontoPercentual ?? this.descontoPercentual,
      descontoValor: descontoValor ?? this.descontoValor,
      valorTotalItem: valorTotalItem ?? this.valorTotalItem,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (vendaId.present) {
      map['venda_id'] = Variable<String>(vendaId.value);
    }
    if (produtoId.present) {
      map['produto_id'] = Variable<String>(produtoId.value);
    }
    if (varianteId.present) {
      map['variante_id'] = Variable<String>(varianteId.value);
    }
    if (nomeItemManual.present) {
      map['nome_item_manual'] = Variable<String>(nomeItemManual.value);
    }
    if (quantidade.present) {
      map['quantidade'] = Variable<double>(quantidade.value);
    }
    if (precoUnitarioVenda.present) {
      map['preco_unitario_venda'] = Variable<double>(precoUnitarioVenda.value);
    }
    if (descontoPercentual.present) {
      map['desconto_percentual'] = Variable<double>(descontoPercentual.value);
    }
    if (descontoValor.present) {
      map['desconto_valor'] = Variable<double>(descontoValor.value);
    }
    if (valorTotalItem.present) {
      map['valor_total_item'] = Variable<double>(valorTotalItem.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('VendaItensCompanion(')
          ..write('id: $id, ')
          ..write('vendaId: $vendaId, ')
          ..write('produtoId: $produtoId, ')
          ..write('varianteId: $varianteId, ')
          ..write('nomeItemManual: $nomeItemManual, ')
          ..write('quantidade: $quantidade, ')
          ..write('precoUnitarioVenda: $precoUnitarioVenda, ')
          ..write('descontoPercentual: $descontoPercentual, ')
          ..write('descontoValor: $descontoValor, ')
          ..write('valorTotalItem: $valorTotalItem, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $ParcelasTable extends Parcelas with TableInfo<$ParcelasTable, Parcela> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $ParcelasTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<String> id = GeneratedColumn<String>(
    'id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _vendaIdMeta = const VerificationMeta(
    'vendaId',
  );
  @override
  late final GeneratedColumn<String> vendaId = GeneratedColumn<String>(
    'venda_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _usuarioIdMeta = const VerificationMeta(
    'usuarioId',
  );
  @override
  late final GeneratedColumn<String> usuarioId = GeneratedColumn<String>(
    'usuario_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _numeroParcelaMeta = const VerificationMeta(
    'numeroParcela',
  );
  @override
  late final GeneratedColumn<int> numeroParcela = GeneratedColumn<int>(
    'numero_parcela',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _valorParcelaMeta = const VerificationMeta(
    'valorParcela',
  );
  @override
  late final GeneratedColumn<double> valorParcela = GeneratedColumn<double>(
    'valor_parcela',
    aliasedName,
    false,
    type: DriftSqlType.double,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _dataVencimentoMeta = const VerificationMeta(
    'dataVencimento',
  );
  @override
  late final GeneratedColumn<String> dataVencimento = GeneratedColumn<String>(
    'data_vencimento',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _statusParcelaCodigoMeta =
      const VerificationMeta('statusParcelaCodigo');
  @override
  late final GeneratedColumn<String> statusParcelaCodigo =
      GeneratedColumn<String>(
        'status_parcela_codigo',
        aliasedName,
        false,
        type: DriftSqlType.string,
        requiredDuringInsert: false,
        defaultValue: const Constant('ABERTA'),
      );
  static const VerificationMeta _formaPagamentoIdMeta = const VerificationMeta(
    'formaPagamentoId',
  );
  @override
  late final GeneratedColumn<String> formaPagamentoId = GeneratedColumn<String>(
    'forma_pagamento_id',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _observacoesMeta = const VerificationMeta(
    'observacoes',
  );
  @override
  late final GeneratedColumn<String> observacoes = GeneratedColumn<String>(
    'observacoes',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    vendaId,
    usuarioId,
    numeroParcela,
    valorParcela,
    dataVencimento,
    statusParcelaCodigo,
    formaPagamentoId,
    observacoes,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'parcelas';
  @override
  VerificationContext validateIntegrity(
    Insertable<Parcela> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    } else if (isInserting) {
      context.missing(_idMeta);
    }
    if (data.containsKey('venda_id')) {
      context.handle(
        _vendaIdMeta,
        vendaId.isAcceptableOrUnknown(data['venda_id']!, _vendaIdMeta),
      );
    } else if (isInserting) {
      context.missing(_vendaIdMeta);
    }
    if (data.containsKey('usuario_id')) {
      context.handle(
        _usuarioIdMeta,
        usuarioId.isAcceptableOrUnknown(data['usuario_id']!, _usuarioIdMeta),
      );
    } else if (isInserting) {
      context.missing(_usuarioIdMeta);
    }
    if (data.containsKey('numero_parcela')) {
      context.handle(
        _numeroParcelaMeta,
        numeroParcela.isAcceptableOrUnknown(
          data['numero_parcela']!,
          _numeroParcelaMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_numeroParcelaMeta);
    }
    if (data.containsKey('valor_parcela')) {
      context.handle(
        _valorParcelaMeta,
        valorParcela.isAcceptableOrUnknown(
          data['valor_parcela']!,
          _valorParcelaMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_valorParcelaMeta);
    }
    if (data.containsKey('data_vencimento')) {
      context.handle(
        _dataVencimentoMeta,
        dataVencimento.isAcceptableOrUnknown(
          data['data_vencimento']!,
          _dataVencimentoMeta,
        ),
      );
    } else if (isInserting) {
      context.missing(_dataVencimentoMeta);
    }
    if (data.containsKey('status_parcela_codigo')) {
      context.handle(
        _statusParcelaCodigoMeta,
        statusParcelaCodigo.isAcceptableOrUnknown(
          data['status_parcela_codigo']!,
          _statusParcelaCodigoMeta,
        ),
      );
    }
    if (data.containsKey('forma_pagamento_id')) {
      context.handle(
        _formaPagamentoIdMeta,
        formaPagamentoId.isAcceptableOrUnknown(
          data['forma_pagamento_id']!,
          _formaPagamentoIdMeta,
        ),
      );
    }
    if (data.containsKey('observacoes')) {
      context.handle(
        _observacoesMeta,
        observacoes.isAcceptableOrUnknown(
          data['observacoes']!,
          _observacoesMeta,
        ),
      );
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  Parcela map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return Parcela(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}id'],
      )!,
      vendaId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}venda_id'],
      )!,
      usuarioId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}usuario_id'],
      )!,
      numeroParcela: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}numero_parcela'],
      )!,
      valorParcela: attachedDatabase.typeMapping.read(
        DriftSqlType.double,
        data['${effectivePrefix}valor_parcela'],
      )!,
      dataVencimento: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}data_vencimento'],
      )!,
      statusParcelaCodigo: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}status_parcela_codigo'],
      )!,
      formaPagamentoId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}forma_pagamento_id'],
      ),
      observacoes: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}observacoes'],
      ),
    );
  }

  @override
  $ParcelasTable createAlias(String alias) {
    return $ParcelasTable(attachedDatabase, alias);
  }
}

class Parcela extends DataClass implements Insertable<Parcela> {
  final String id;
  final String vendaId;
  final String usuarioId;
  final int numeroParcela;
  final double valorParcela;
  final String dataVencimento;
  final String statusParcelaCodigo;
  final String? formaPagamentoId;
  final String? observacoes;
  const Parcela({
    required this.id,
    required this.vendaId,
    required this.usuarioId,
    required this.numeroParcela,
    required this.valorParcela,
    required this.dataVencimento,
    required this.statusParcelaCodigo,
    this.formaPagamentoId,
    this.observacoes,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<String>(id);
    map['venda_id'] = Variable<String>(vendaId);
    map['usuario_id'] = Variable<String>(usuarioId);
    map['numero_parcela'] = Variable<int>(numeroParcela);
    map['valor_parcela'] = Variable<double>(valorParcela);
    map['data_vencimento'] = Variable<String>(dataVencimento);
    map['status_parcela_codigo'] = Variable<String>(statusParcelaCodigo);
    if (!nullToAbsent || formaPagamentoId != null) {
      map['forma_pagamento_id'] = Variable<String>(formaPagamentoId);
    }
    if (!nullToAbsent || observacoes != null) {
      map['observacoes'] = Variable<String>(observacoes);
    }
    return map;
  }

  ParcelasCompanion toCompanion(bool nullToAbsent) {
    return ParcelasCompanion(
      id: Value(id),
      vendaId: Value(vendaId),
      usuarioId: Value(usuarioId),
      numeroParcela: Value(numeroParcela),
      valorParcela: Value(valorParcela),
      dataVencimento: Value(dataVencimento),
      statusParcelaCodigo: Value(statusParcelaCodigo),
      formaPagamentoId: formaPagamentoId == null && nullToAbsent
          ? const Value.absent()
          : Value(formaPagamentoId),
      observacoes: observacoes == null && nullToAbsent
          ? const Value.absent()
          : Value(observacoes),
    );
  }

  factory Parcela.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return Parcela(
      id: serializer.fromJson<String>(json['id']),
      vendaId: serializer.fromJson<String>(json['vendaId']),
      usuarioId: serializer.fromJson<String>(json['usuarioId']),
      numeroParcela: serializer.fromJson<int>(json['numeroParcela']),
      valorParcela: serializer.fromJson<double>(json['valorParcela']),
      dataVencimento: serializer.fromJson<String>(json['dataVencimento']),
      statusParcelaCodigo: serializer.fromJson<String>(
        json['statusParcelaCodigo'],
      ),
      formaPagamentoId: serializer.fromJson<String?>(json['formaPagamentoId']),
      observacoes: serializer.fromJson<String?>(json['observacoes']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<String>(id),
      'vendaId': serializer.toJson<String>(vendaId),
      'usuarioId': serializer.toJson<String>(usuarioId),
      'numeroParcela': serializer.toJson<int>(numeroParcela),
      'valorParcela': serializer.toJson<double>(valorParcela),
      'dataVencimento': serializer.toJson<String>(dataVencimento),
      'statusParcelaCodigo': serializer.toJson<String>(statusParcelaCodigo),
      'formaPagamentoId': serializer.toJson<String?>(formaPagamentoId),
      'observacoes': serializer.toJson<String?>(observacoes),
    };
  }

  Parcela copyWith({
    String? id,
    String? vendaId,
    String? usuarioId,
    int? numeroParcela,
    double? valorParcela,
    String? dataVencimento,
    String? statusParcelaCodigo,
    Value<String?> formaPagamentoId = const Value.absent(),
    Value<String?> observacoes = const Value.absent(),
  }) => Parcela(
    id: id ?? this.id,
    vendaId: vendaId ?? this.vendaId,
    usuarioId: usuarioId ?? this.usuarioId,
    numeroParcela: numeroParcela ?? this.numeroParcela,
    valorParcela: valorParcela ?? this.valorParcela,
    dataVencimento: dataVencimento ?? this.dataVencimento,
    statusParcelaCodigo: statusParcelaCodigo ?? this.statusParcelaCodigo,
    formaPagamentoId: formaPagamentoId.present
        ? formaPagamentoId.value
        : this.formaPagamentoId,
    observacoes: observacoes.present ? observacoes.value : this.observacoes,
  );
  Parcela copyWithCompanion(ParcelasCompanion data) {
    return Parcela(
      id: data.id.present ? data.id.value : this.id,
      vendaId: data.vendaId.present ? data.vendaId.value : this.vendaId,
      usuarioId: data.usuarioId.present ? data.usuarioId.value : this.usuarioId,
      numeroParcela: data.numeroParcela.present
          ? data.numeroParcela.value
          : this.numeroParcela,
      valorParcela: data.valorParcela.present
          ? data.valorParcela.value
          : this.valorParcela,
      dataVencimento: data.dataVencimento.present
          ? data.dataVencimento.value
          : this.dataVencimento,
      statusParcelaCodigo: data.statusParcelaCodigo.present
          ? data.statusParcelaCodigo.value
          : this.statusParcelaCodigo,
      formaPagamentoId: data.formaPagamentoId.present
          ? data.formaPagamentoId.value
          : this.formaPagamentoId,
      observacoes: data.observacoes.present
          ? data.observacoes.value
          : this.observacoes,
    );
  }

  @override
  String toString() {
    return (StringBuffer('Parcela(')
          ..write('id: $id, ')
          ..write('vendaId: $vendaId, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('numeroParcela: $numeroParcela, ')
          ..write('valorParcela: $valorParcela, ')
          ..write('dataVencimento: $dataVencimento, ')
          ..write('statusParcelaCodigo: $statusParcelaCodigo, ')
          ..write('formaPagamentoId: $formaPagamentoId, ')
          ..write('observacoes: $observacoes')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    id,
    vendaId,
    usuarioId,
    numeroParcela,
    valorParcela,
    dataVencimento,
    statusParcelaCodigo,
    formaPagamentoId,
    observacoes,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is Parcela &&
          other.id == this.id &&
          other.vendaId == this.vendaId &&
          other.usuarioId == this.usuarioId &&
          other.numeroParcela == this.numeroParcela &&
          other.valorParcela == this.valorParcela &&
          other.dataVencimento == this.dataVencimento &&
          other.statusParcelaCodigo == this.statusParcelaCodigo &&
          other.formaPagamentoId == this.formaPagamentoId &&
          other.observacoes == this.observacoes);
}

class ParcelasCompanion extends UpdateCompanion<Parcela> {
  final Value<String> id;
  final Value<String> vendaId;
  final Value<String> usuarioId;
  final Value<int> numeroParcela;
  final Value<double> valorParcela;
  final Value<String> dataVencimento;
  final Value<String> statusParcelaCodigo;
  final Value<String?> formaPagamentoId;
  final Value<String?> observacoes;
  final Value<int> rowid;
  const ParcelasCompanion({
    this.id = const Value.absent(),
    this.vendaId = const Value.absent(),
    this.usuarioId = const Value.absent(),
    this.numeroParcela = const Value.absent(),
    this.valorParcela = const Value.absent(),
    this.dataVencimento = const Value.absent(),
    this.statusParcelaCodigo = const Value.absent(),
    this.formaPagamentoId = const Value.absent(),
    this.observacoes = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  ParcelasCompanion.insert({
    required String id,
    required String vendaId,
    required String usuarioId,
    required int numeroParcela,
    required double valorParcela,
    required String dataVencimento,
    this.statusParcelaCodigo = const Value.absent(),
    this.formaPagamentoId = const Value.absent(),
    this.observacoes = const Value.absent(),
    this.rowid = const Value.absent(),
  }) : id = Value(id),
       vendaId = Value(vendaId),
       usuarioId = Value(usuarioId),
       numeroParcela = Value(numeroParcela),
       valorParcela = Value(valorParcela),
       dataVencimento = Value(dataVencimento);
  static Insertable<Parcela> custom({
    Expression<String>? id,
    Expression<String>? vendaId,
    Expression<String>? usuarioId,
    Expression<int>? numeroParcela,
    Expression<double>? valorParcela,
    Expression<String>? dataVencimento,
    Expression<String>? statusParcelaCodigo,
    Expression<String>? formaPagamentoId,
    Expression<String>? observacoes,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (vendaId != null) 'venda_id': vendaId,
      if (usuarioId != null) 'usuario_id': usuarioId,
      if (numeroParcela != null) 'numero_parcela': numeroParcela,
      if (valorParcela != null) 'valor_parcela': valorParcela,
      if (dataVencimento != null) 'data_vencimento': dataVencimento,
      if (statusParcelaCodigo != null)
        'status_parcela_codigo': statusParcelaCodigo,
      if (formaPagamentoId != null) 'forma_pagamento_id': formaPagamentoId,
      if (observacoes != null) 'observacoes': observacoes,
      if (rowid != null) 'rowid': rowid,
    });
  }

  ParcelasCompanion copyWith({
    Value<String>? id,
    Value<String>? vendaId,
    Value<String>? usuarioId,
    Value<int>? numeroParcela,
    Value<double>? valorParcela,
    Value<String>? dataVencimento,
    Value<String>? statusParcelaCodigo,
    Value<String?>? formaPagamentoId,
    Value<String?>? observacoes,
    Value<int>? rowid,
  }) {
    return ParcelasCompanion(
      id: id ?? this.id,
      vendaId: vendaId ?? this.vendaId,
      usuarioId: usuarioId ?? this.usuarioId,
      numeroParcela: numeroParcela ?? this.numeroParcela,
      valorParcela: valorParcela ?? this.valorParcela,
      dataVencimento: dataVencimento ?? this.dataVencimento,
      statusParcelaCodigo: statusParcelaCodigo ?? this.statusParcelaCodigo,
      formaPagamentoId: formaPagamentoId ?? this.formaPagamentoId,
      observacoes: observacoes ?? this.observacoes,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<String>(id.value);
    }
    if (vendaId.present) {
      map['venda_id'] = Variable<String>(vendaId.value);
    }
    if (usuarioId.present) {
      map['usuario_id'] = Variable<String>(usuarioId.value);
    }
    if (numeroParcela.present) {
      map['numero_parcela'] = Variable<int>(numeroParcela.value);
    }
    if (valorParcela.present) {
      map['valor_parcela'] = Variable<double>(valorParcela.value);
    }
    if (dataVencimento.present) {
      map['data_vencimento'] = Variable<String>(dataVencimento.value);
    }
    if (statusParcelaCodigo.present) {
      map['status_parcela_codigo'] = Variable<String>(
        statusParcelaCodigo.value,
      );
    }
    if (formaPagamentoId.present) {
      map['forma_pagamento_id'] = Variable<String>(formaPagamentoId.value);
    }
    if (observacoes.present) {
      map['observacoes'] = Variable<String>(observacoes.value);
    }
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('ParcelasCompanion(')
          ..write('id: $id, ')
          ..write('vendaId: $vendaId, ')
          ..write('usuarioId: $usuarioId, ')
          ..write('numeroParcela: $numeroParcela, ')
          ..write('valorParcela: $valorParcela, ')
          ..write('dataVencimento: $dataVencimento, ')
          ..write('statusParcelaCodigo: $statusParcelaCodigo, ')
          ..write('formaPagamentoId: $formaPagamentoId, ')
          ..write('observacoes: $observacoes, ')
          ..write('rowid: $rowid')
          ..write(')'))
        .toString();
  }
}

class $SyncQueueTable extends SyncQueue
    with TableInfo<$SyncQueueTable, SyncQueueData> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $SyncQueueTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _idMeta = const VerificationMeta('id');
  @override
  late final GeneratedColumn<int> id = GeneratedColumn<int>(
    'id',
    aliasedName,
    false,
    hasAutoIncrement: true,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultConstraints: GeneratedColumn.constraintIsAlways(
      'PRIMARY KEY AUTOINCREMENT',
    ),
  );
  static const VerificationMeta _tabelaMeta = const VerificationMeta('tabela');
  @override
  late final GeneratedColumn<String> tabela = GeneratedColumn<String>(
    'tabela',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _registroIdMeta = const VerificationMeta(
    'registroId',
  );
  @override
  late final GeneratedColumn<String> registroId = GeneratedColumn<String>(
    'registro_id',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _operacaoMeta = const VerificationMeta(
    'operacao',
  );
  @override
  late final GeneratedColumn<String> operacao = GeneratedColumn<String>(
    'operacao',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _payloadMeta = const VerificationMeta(
    'payload',
  );
  @override
  late final GeneratedColumn<String> payload = GeneratedColumn<String>(
    'payload',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _tentativasMeta = const VerificationMeta(
    'tentativas',
  );
  @override
  late final GeneratedColumn<int> tentativas = GeneratedColumn<int>(
    'tentativas',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
    defaultValue: const Constant(0),
  );
  static const VerificationMeta _ultimoErroMeta = const VerificationMeta(
    'ultimoErro',
  );
  @override
  late final GeneratedColumn<String> ultimoErro = GeneratedColumn<String>(
    'ultimo_erro',
    aliasedName,
    true,
    type: DriftSqlType.string,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _criadoEmMeta = const VerificationMeta(
    'criadoEm',
  );
  @override
  late final GeneratedColumn<String> criadoEm = GeneratedColumn<String>(
    'criado_em',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    id,
    tabela,
    registroId,
    operacao,
    payload,
    tentativas,
    ultimoErro,
    criadoEm,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'sync_queue';
  @override
  VerificationContext validateIntegrity(
    Insertable<SyncQueueData> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('id')) {
      context.handle(_idMeta, id.isAcceptableOrUnknown(data['id']!, _idMeta));
    }
    if (data.containsKey('tabela')) {
      context.handle(
        _tabelaMeta,
        tabela.isAcceptableOrUnknown(data['tabela']!, _tabelaMeta),
      );
    } else if (isInserting) {
      context.missing(_tabelaMeta);
    }
    if (data.containsKey('registro_id')) {
      context.handle(
        _registroIdMeta,
        registroId.isAcceptableOrUnknown(data['registro_id']!, _registroIdMeta),
      );
    } else if (isInserting) {
      context.missing(_registroIdMeta);
    }
    if (data.containsKey('operacao')) {
      context.handle(
        _operacaoMeta,
        operacao.isAcceptableOrUnknown(data['operacao']!, _operacaoMeta),
      );
    } else if (isInserting) {
      context.missing(_operacaoMeta);
    }
    if (data.containsKey('payload')) {
      context.handle(
        _payloadMeta,
        payload.isAcceptableOrUnknown(data['payload']!, _payloadMeta),
      );
    } else if (isInserting) {
      context.missing(_payloadMeta);
    }
    if (data.containsKey('tentativas')) {
      context.handle(
        _tentativasMeta,
        tentativas.isAcceptableOrUnknown(data['tentativas']!, _tentativasMeta),
      );
    }
    if (data.containsKey('ultimo_erro')) {
      context.handle(
        _ultimoErroMeta,
        ultimoErro.isAcceptableOrUnknown(data['ultimo_erro']!, _ultimoErroMeta),
      );
    }
    if (data.containsKey('criado_em')) {
      context.handle(
        _criadoEmMeta,
        criadoEm.isAcceptableOrUnknown(data['criado_em']!, _criadoEmMeta),
      );
    } else if (isInserting) {
      context.missing(_criadoEmMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {id};
  @override
  SyncQueueData map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return SyncQueueData(
      id: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}id'],
      )!,
      tabela: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}tabela'],
      )!,
      registroId: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}registro_id'],
      )!,
      operacao: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}operacao'],
      )!,
      payload: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}payload'],
      )!,
      tentativas: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}tentativas'],
      )!,
      ultimoErro: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}ultimo_erro'],
      ),
      criadoEm: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}criado_em'],
      )!,
    );
  }

  @override
  $SyncQueueTable createAlias(String alias) {
    return $SyncQueueTable(attachedDatabase, alias);
  }
}

class SyncQueueData extends DataClass implements Insertable<SyncQueueData> {
  final int id;
  final String tabela;
  final String registroId;
  final String operacao;
  final String payload;
  final int tentativas;
  final String? ultimoErro;
  final String criadoEm;
  const SyncQueueData({
    required this.id,
    required this.tabela,
    required this.registroId,
    required this.operacao,
    required this.payload,
    required this.tentativas,
    this.ultimoErro,
    required this.criadoEm,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['id'] = Variable<int>(id);
    map['tabela'] = Variable<String>(tabela);
    map['registro_id'] = Variable<String>(registroId);
    map['operacao'] = Variable<String>(operacao);
    map['payload'] = Variable<String>(payload);
    map['tentativas'] = Variable<int>(tentativas);
    if (!nullToAbsent || ultimoErro != null) {
      map['ultimo_erro'] = Variable<String>(ultimoErro);
    }
    map['criado_em'] = Variable<String>(criadoEm);
    return map;
  }

  SyncQueueCompanion toCompanion(bool nullToAbsent) {
    return SyncQueueCompanion(
      id: Value(id),
      tabela: Value(tabela),
      registroId: Value(registroId),
      operacao: Value(operacao),
      payload: Value(payload),
      tentativas: Value(tentativas),
      ultimoErro: ultimoErro == null && nullToAbsent
          ? const Value.absent()
          : Value(ultimoErro),
      criadoEm: Value(criadoEm),
    );
  }

  factory SyncQueueData.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return SyncQueueData(
      id: serializer.fromJson<int>(json['id']),
      tabela: serializer.fromJson<String>(json['tabela']),
      registroId: serializer.fromJson<String>(json['registroId']),
      operacao: serializer.fromJson<String>(json['operacao']),
      payload: serializer.fromJson<String>(json['payload']),
      tentativas: serializer.fromJson<int>(json['tentativas']),
      ultimoErro: serializer.fromJson<String?>(json['ultimoErro']),
      criadoEm: serializer.fromJson<String>(json['criadoEm']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'id': serializer.toJson<int>(id),
      'tabela': serializer.toJson<String>(tabela),
      'registroId': serializer.toJson<String>(registroId),
      'operacao': serializer.toJson<String>(operacao),
      'payload': serializer.toJson<String>(payload),
      'tentativas': serializer.toJson<int>(tentativas),
      'ultimoErro': serializer.toJson<String?>(ultimoErro),
      'criadoEm': serializer.toJson<String>(criadoEm),
    };
  }

  SyncQueueData copyWith({
    int? id,
    String? tabela,
    String? registroId,
    String? operacao,
    String? payload,
    int? tentativas,
    Value<String?> ultimoErro = const Value.absent(),
    String? criadoEm,
  }) => SyncQueueData(
    id: id ?? this.id,
    tabela: tabela ?? this.tabela,
    registroId: registroId ?? this.registroId,
    operacao: operacao ?? this.operacao,
    payload: payload ?? this.payload,
    tentativas: tentativas ?? this.tentativas,
    ultimoErro: ultimoErro.present ? ultimoErro.value : this.ultimoErro,
    criadoEm: criadoEm ?? this.criadoEm,
  );
  SyncQueueData copyWithCompanion(SyncQueueCompanion data) {
    return SyncQueueData(
      id: data.id.present ? data.id.value : this.id,
      tabela: data.tabela.present ? data.tabela.value : this.tabela,
      registroId: data.registroId.present
          ? data.registroId.value
          : this.registroId,
      operacao: data.operacao.present ? data.operacao.value : this.operacao,
      payload: data.payload.present ? data.payload.value : this.payload,
      tentativas: data.tentativas.present
          ? data.tentativas.value
          : this.tentativas,
      ultimoErro: data.ultimoErro.present
          ? data.ultimoErro.value
          : this.ultimoErro,
      criadoEm: data.criadoEm.present ? data.criadoEm.value : this.criadoEm,
    );
  }

  @override
  String toString() {
    return (StringBuffer('SyncQueueData(')
          ..write('id: $id, ')
          ..write('tabela: $tabela, ')
          ..write('registroId: $registroId, ')
          ..write('operacao: $operacao, ')
          ..write('payload: $payload, ')
          ..write('tentativas: $tentativas, ')
          ..write('ultimoErro: $ultimoErro, ')
          ..write('criadoEm: $criadoEm')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode => Object.hash(
    id,
    tabela,
    registroId,
    operacao,
    payload,
    tentativas,
    ultimoErro,
    criadoEm,
  );
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is SyncQueueData &&
          other.id == this.id &&
          other.tabela == this.tabela &&
          other.registroId == this.registroId &&
          other.operacao == this.operacao &&
          other.payload == this.payload &&
          other.tentativas == this.tentativas &&
          other.ultimoErro == this.ultimoErro &&
          other.criadoEm == this.criadoEm);
}

class SyncQueueCompanion extends UpdateCompanion<SyncQueueData> {
  final Value<int> id;
  final Value<String> tabela;
  final Value<String> registroId;
  final Value<String> operacao;
  final Value<String> payload;
  final Value<int> tentativas;
  final Value<String?> ultimoErro;
  final Value<String> criadoEm;
  const SyncQueueCompanion({
    this.id = const Value.absent(),
    this.tabela = const Value.absent(),
    this.registroId = const Value.absent(),
    this.operacao = const Value.absent(),
    this.payload = const Value.absent(),
    this.tentativas = const Value.absent(),
    this.ultimoErro = const Value.absent(),
    this.criadoEm = const Value.absent(),
  });
  SyncQueueCompanion.insert({
    this.id = const Value.absent(),
    required String tabela,
    required String registroId,
    required String operacao,
    required String payload,
    this.tentativas = const Value.absent(),
    this.ultimoErro = const Value.absent(),
    required String criadoEm,
  }) : tabela = Value(tabela),
       registroId = Value(registroId),
       operacao = Value(operacao),
       payload = Value(payload),
       criadoEm = Value(criadoEm);
  static Insertable<SyncQueueData> custom({
    Expression<int>? id,
    Expression<String>? tabela,
    Expression<String>? registroId,
    Expression<String>? operacao,
    Expression<String>? payload,
    Expression<int>? tentativas,
    Expression<String>? ultimoErro,
    Expression<String>? criadoEm,
  }) {
    return RawValuesInsertable({
      if (id != null) 'id': id,
      if (tabela != null) 'tabela': tabela,
      if (registroId != null) 'registro_id': registroId,
      if (operacao != null) 'operacao': operacao,
      if (payload != null) 'payload': payload,
      if (tentativas != null) 'tentativas': tentativas,
      if (ultimoErro != null) 'ultimo_erro': ultimoErro,
      if (criadoEm != null) 'criado_em': criadoEm,
    });
  }

  SyncQueueCompanion copyWith({
    Value<int>? id,
    Value<String>? tabela,
    Value<String>? registroId,
    Value<String>? operacao,
    Value<String>? payload,
    Value<int>? tentativas,
    Value<String?>? ultimoErro,
    Value<String>? criadoEm,
  }) {
    return SyncQueueCompanion(
      id: id ?? this.id,
      tabela: tabela ?? this.tabela,
      registroId: registroId ?? this.registroId,
      operacao: operacao ?? this.operacao,
      payload: payload ?? this.payload,
      tentativas: tentativas ?? this.tentativas,
      ultimoErro: ultimoErro ?? this.ultimoErro,
      criadoEm: criadoEm ?? this.criadoEm,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (id.present) {
      map['id'] = Variable<int>(id.value);
    }
    if (tabela.present) {
      map['tabela'] = Variable<String>(tabela.value);
    }
    if (registroId.present) {
      map['registro_id'] = Variable<String>(registroId.value);
    }
    if (operacao.present) {
      map['operacao'] = Variable<String>(operacao.value);
    }
    if (payload.present) {
      map['payload'] = Variable<String>(payload.value);
    }
    if (tentativas.present) {
      map['tentativas'] = Variable<int>(tentativas.value);
    }
    if (ultimoErro.present) {
      map['ultimo_erro'] = Variable<String>(ultimoErro.value);
    }
    if (criadoEm.present) {
      map['criado_em'] = Variable<String>(criadoEm.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('SyncQueueCompanion(')
          ..write('id: $id, ')
          ..write('tabela: $tabela, ')
          ..write('registroId: $registroId, ')
          ..write('operacao: $operacao, ')
          ..write('payload: $payload, ')
          ..write('tentativas: $tentativas, ')
          ..write('ultimoErro: $ultimoErro, ')
          ..write('criadoEm: $criadoEm')
          ..write(')'))
        .toString();
  }
}

abstract class _$AppDatabase extends GeneratedDatabase {
  _$AppDatabase(QueryExecutor e) : super(e);
  $AppDatabaseManager get managers => $AppDatabaseManager(this);
  late final $TenantsTable tenants = $TenantsTable(this);
  late final $CategoriasTable categorias = $CategoriasTable(this);
  late final $ProdutosTable produtos = $ProdutosTable(this);
  late final $ClientesTable clientes = $ClientesTable(this);
  late final $FormasPagamentoTable formasPagamento = $FormasPagamentoTable(
    this,
  );
  late final $VendasTable vendas = $VendasTable(this);
  late final $VendaItensTable vendaItens = $VendaItensTable(this);
  late final $ParcelasTable parcelas = $ParcelasTable(this);
  late final $SyncQueueTable syncQueue = $SyncQueueTable(this);
  late final ProdutoDao produtoDao = ProdutoDao(this as AppDatabase);
  late final VendaDao vendaDao = VendaDao(this as AppDatabase);
  late final ClienteDao clienteDao = ClienteDao(this as AppDatabase);
  late final CatalogDao catalogDao = CatalogDao(this as AppDatabase);
  @override
  Iterable<TableInfo<Table, Object?>> get allTables =>
      allSchemaEntities.whereType<TableInfo<Table, Object?>>();
  @override
  List<DatabaseSchemaEntity> get allSchemaEntities => [
    tenants,
    categorias,
    produtos,
    clientes,
    formasPagamento,
    vendas,
    vendaItens,
    parcelas,
    syncQueue,
  ];
}

typedef $$TenantsTableCreateCompanionBuilder =
    TenantsCompanion Function({
      required String id,
      required String nome,
      required String tokenJwt,
      required String urlServidor,
      Value<String?> logoUrl,
      Value<String?> ultimaSync,
      Value<bool> ativo,
      Value<int> rowid,
    });
typedef $$TenantsTableUpdateCompanionBuilder =
    TenantsCompanion Function({
      Value<String> id,
      Value<String> nome,
      Value<String> tokenJwt,
      Value<String> urlServidor,
      Value<String?> logoUrl,
      Value<String?> ultimaSync,
      Value<bool> ativo,
      Value<int> rowid,
    });

class $$TenantsTableFilterComposer
    extends Composer<_$AppDatabase, $TenantsTable> {
  $$TenantsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get nome => $composableBuilder(
    column: $table.nome,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get tokenJwt => $composableBuilder(
    column: $table.tokenJwt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get urlServidor => $composableBuilder(
    column: $table.urlServidor,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get logoUrl => $composableBuilder(
    column: $table.logoUrl,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get ultimaSync => $composableBuilder(
    column: $table.ultimaSync,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get ativo => $composableBuilder(
    column: $table.ativo,
    builder: (column) => ColumnFilters(column),
  );
}

class $$TenantsTableOrderingComposer
    extends Composer<_$AppDatabase, $TenantsTable> {
  $$TenantsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get nome => $composableBuilder(
    column: $table.nome,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get tokenJwt => $composableBuilder(
    column: $table.tokenJwt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get urlServidor => $composableBuilder(
    column: $table.urlServidor,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get logoUrl => $composableBuilder(
    column: $table.logoUrl,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get ultimaSync => $composableBuilder(
    column: $table.ultimaSync,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get ativo => $composableBuilder(
    column: $table.ativo,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$TenantsTableAnnotationComposer
    extends Composer<_$AppDatabase, $TenantsTable> {
  $$TenantsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get nome =>
      $composableBuilder(column: $table.nome, builder: (column) => column);

  GeneratedColumn<String> get tokenJwt =>
      $composableBuilder(column: $table.tokenJwt, builder: (column) => column);

  GeneratedColumn<String> get urlServidor => $composableBuilder(
    column: $table.urlServidor,
    builder: (column) => column,
  );

  GeneratedColumn<String> get logoUrl =>
      $composableBuilder(column: $table.logoUrl, builder: (column) => column);

  GeneratedColumn<String> get ultimaSync => $composableBuilder(
    column: $table.ultimaSync,
    builder: (column) => column,
  );

  GeneratedColumn<bool> get ativo =>
      $composableBuilder(column: $table.ativo, builder: (column) => column);
}

class $$TenantsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $TenantsTable,
          Tenant,
          $$TenantsTableFilterComposer,
          $$TenantsTableOrderingComposer,
          $$TenantsTableAnnotationComposer,
          $$TenantsTableCreateCompanionBuilder,
          $$TenantsTableUpdateCompanionBuilder,
          (Tenant, BaseReferences<_$AppDatabase, $TenantsTable, Tenant>),
          Tenant,
          PrefetchHooks Function()
        > {
  $$TenantsTableTableManager(_$AppDatabase db, $TenantsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$TenantsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$TenantsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$TenantsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> nome = const Value.absent(),
                Value<String> tokenJwt = const Value.absent(),
                Value<String> urlServidor = const Value.absent(),
                Value<String?> logoUrl = const Value.absent(),
                Value<String?> ultimaSync = const Value.absent(),
                Value<bool> ativo = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => TenantsCompanion(
                id: id,
                nome: nome,
                tokenJwt: tokenJwt,
                urlServidor: urlServidor,
                logoUrl: logoUrl,
                ultimaSync: ultimaSync,
                ativo: ativo,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String nome,
                required String tokenJwt,
                required String urlServidor,
                Value<String?> logoUrl = const Value.absent(),
                Value<String?> ultimaSync = const Value.absent(),
                Value<bool> ativo = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => TenantsCompanion.insert(
                id: id,
                nome: nome,
                tokenJwt: tokenJwt,
                urlServidor: urlServidor,
                logoUrl: logoUrl,
                ultimaSync: ultimaSync,
                ativo: ativo,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$TenantsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $TenantsTable,
      Tenant,
      $$TenantsTableFilterComposer,
      $$TenantsTableOrderingComposer,
      $$TenantsTableAnnotationComposer,
      $$TenantsTableCreateCompanionBuilder,
      $$TenantsTableUpdateCompanionBuilder,
      (Tenant, BaseReferences<_$AppDatabase, $TenantsTable, Tenant>),
      Tenant,
      PrefetchHooks Function()
    >;
typedef $$CategoriasTableCreateCompanionBuilder =
    CategoriasCompanion Function({
      required String id,
      required String usuarioId,
      required String nome,
      Value<bool> ativo,
      Value<String?> dataAtualizacao,
      Value<int> rowid,
    });
typedef $$CategoriasTableUpdateCompanionBuilder =
    CategoriasCompanion Function({
      Value<String> id,
      Value<String> usuarioId,
      Value<String> nome,
      Value<bool> ativo,
      Value<String?> dataAtualizacao,
      Value<int> rowid,
    });

class $$CategoriasTableFilterComposer
    extends Composer<_$AppDatabase, $CategoriasTable> {
  $$CategoriasTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get nome => $composableBuilder(
    column: $table.nome,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get ativo => $composableBuilder(
    column: $table.ativo,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => ColumnFilters(column),
  );
}

class $$CategoriasTableOrderingComposer
    extends Composer<_$AppDatabase, $CategoriasTable> {
  $$CategoriasTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get nome => $composableBuilder(
    column: $table.nome,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get ativo => $composableBuilder(
    column: $table.ativo,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$CategoriasTableAnnotationComposer
    extends Composer<_$AppDatabase, $CategoriasTable> {
  $$CategoriasTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get usuarioId =>
      $composableBuilder(column: $table.usuarioId, builder: (column) => column);

  GeneratedColumn<String> get nome =>
      $composableBuilder(column: $table.nome, builder: (column) => column);

  GeneratedColumn<bool> get ativo =>
      $composableBuilder(column: $table.ativo, builder: (column) => column);

  GeneratedColumn<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => column,
  );
}

class $$CategoriasTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $CategoriasTable,
          Categoria,
          $$CategoriasTableFilterComposer,
          $$CategoriasTableOrderingComposer,
          $$CategoriasTableAnnotationComposer,
          $$CategoriasTableCreateCompanionBuilder,
          $$CategoriasTableUpdateCompanionBuilder,
          (
            Categoria,
            BaseReferences<_$AppDatabase, $CategoriasTable, Categoria>,
          ),
          Categoria,
          PrefetchHooks Function()
        > {
  $$CategoriasTableTableManager(_$AppDatabase db, $CategoriasTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$CategoriasTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$CategoriasTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$CategoriasTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> usuarioId = const Value.absent(),
                Value<String> nome = const Value.absent(),
                Value<bool> ativo = const Value.absent(),
                Value<String?> dataAtualizacao = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => CategoriasCompanion(
                id: id,
                usuarioId: usuarioId,
                nome: nome,
                ativo: ativo,
                dataAtualizacao: dataAtualizacao,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String usuarioId,
                required String nome,
                Value<bool> ativo = const Value.absent(),
                Value<String?> dataAtualizacao = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => CategoriasCompanion.insert(
                id: id,
                usuarioId: usuarioId,
                nome: nome,
                ativo: ativo,
                dataAtualizacao: dataAtualizacao,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$CategoriasTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $CategoriasTable,
      Categoria,
      $$CategoriasTableFilterComposer,
      $$CategoriasTableOrderingComposer,
      $$CategoriasTableAnnotationComposer,
      $$CategoriasTableCreateCompanionBuilder,
      $$CategoriasTableUpdateCompanionBuilder,
      (Categoria, BaseReferences<_$AppDatabase, $CategoriasTable, Categoria>),
      Categoria,
      PrefetchHooks Function()
    >;
typedef $$ProdutosTableCreateCompanionBuilder =
    ProdutosCompanion Function({
      required String id,
      required String usuarioId,
      Value<String?> categoriaId,
      required String nome,
      Value<String?> codigoBarras,
      Value<String?> codigoReferencia,
      Value<double> precoVendaSugerido,
      Value<double> precoCusto,
      Value<double> precoPromocional,
      Value<String?> dataInicioPromocao,
      Value<String?> dataFimPromocao,
      Value<double> precoVigente,
      Value<bool> emPromocao,
      Value<int> estoqueAtual,
      Value<int> estoqueMinimo,
      Value<String> unidadeMedida,
      Value<bool> vendaFracionada,
      Value<String?> marca,
      Value<String?> fotoUrl,
      Value<String?> fotoLocalPath,
      Value<bool> ativo,
      Value<String?> dataAtualizacao,
      Value<String?> variantesJson,
      Value<int> rowid,
    });
typedef $$ProdutosTableUpdateCompanionBuilder =
    ProdutosCompanion Function({
      Value<String> id,
      Value<String> usuarioId,
      Value<String?> categoriaId,
      Value<String> nome,
      Value<String?> codigoBarras,
      Value<String?> codigoReferencia,
      Value<double> precoVendaSugerido,
      Value<double> precoCusto,
      Value<double> precoPromocional,
      Value<String?> dataInicioPromocao,
      Value<String?> dataFimPromocao,
      Value<double> precoVigente,
      Value<bool> emPromocao,
      Value<int> estoqueAtual,
      Value<int> estoqueMinimo,
      Value<String> unidadeMedida,
      Value<bool> vendaFracionada,
      Value<String?> marca,
      Value<String?> fotoUrl,
      Value<String?> fotoLocalPath,
      Value<bool> ativo,
      Value<String?> dataAtualizacao,
      Value<String?> variantesJson,
      Value<int> rowid,
    });

class $$ProdutosTableFilterComposer
    extends Composer<_$AppDatabase, $ProdutosTable> {
  $$ProdutosTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get categoriaId => $composableBuilder(
    column: $table.categoriaId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get nome => $composableBuilder(
    column: $table.nome,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get codigoBarras => $composableBuilder(
    column: $table.codigoBarras,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get codigoReferencia => $composableBuilder(
    column: $table.codigoReferencia,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get precoVendaSugerido => $composableBuilder(
    column: $table.precoVendaSugerido,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get precoCusto => $composableBuilder(
    column: $table.precoCusto,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get precoPromocional => $composableBuilder(
    column: $table.precoPromocional,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataInicioPromocao => $composableBuilder(
    column: $table.dataInicioPromocao,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataFimPromocao => $composableBuilder(
    column: $table.dataFimPromocao,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get precoVigente => $composableBuilder(
    column: $table.precoVigente,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get emPromocao => $composableBuilder(
    column: $table.emPromocao,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get estoqueAtual => $composableBuilder(
    column: $table.estoqueAtual,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get estoqueMinimo => $composableBuilder(
    column: $table.estoqueMinimo,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get unidadeMedida => $composableBuilder(
    column: $table.unidadeMedida,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get vendaFracionada => $composableBuilder(
    column: $table.vendaFracionada,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get marca => $composableBuilder(
    column: $table.marca,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get fotoUrl => $composableBuilder(
    column: $table.fotoUrl,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get fotoLocalPath => $composableBuilder(
    column: $table.fotoLocalPath,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get ativo => $composableBuilder(
    column: $table.ativo,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get variantesJson => $composableBuilder(
    column: $table.variantesJson,
    builder: (column) => ColumnFilters(column),
  );
}

class $$ProdutosTableOrderingComposer
    extends Composer<_$AppDatabase, $ProdutosTable> {
  $$ProdutosTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get categoriaId => $composableBuilder(
    column: $table.categoriaId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get nome => $composableBuilder(
    column: $table.nome,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get codigoBarras => $composableBuilder(
    column: $table.codigoBarras,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get codigoReferencia => $composableBuilder(
    column: $table.codigoReferencia,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get precoVendaSugerido => $composableBuilder(
    column: $table.precoVendaSugerido,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get precoCusto => $composableBuilder(
    column: $table.precoCusto,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get precoPromocional => $composableBuilder(
    column: $table.precoPromocional,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataInicioPromocao => $composableBuilder(
    column: $table.dataInicioPromocao,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataFimPromocao => $composableBuilder(
    column: $table.dataFimPromocao,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get precoVigente => $composableBuilder(
    column: $table.precoVigente,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get emPromocao => $composableBuilder(
    column: $table.emPromocao,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get estoqueAtual => $composableBuilder(
    column: $table.estoqueAtual,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get estoqueMinimo => $composableBuilder(
    column: $table.estoqueMinimo,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get unidadeMedida => $composableBuilder(
    column: $table.unidadeMedida,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get vendaFracionada => $composableBuilder(
    column: $table.vendaFracionada,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get marca => $composableBuilder(
    column: $table.marca,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get fotoUrl => $composableBuilder(
    column: $table.fotoUrl,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get fotoLocalPath => $composableBuilder(
    column: $table.fotoLocalPath,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get ativo => $composableBuilder(
    column: $table.ativo,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get variantesJson => $composableBuilder(
    column: $table.variantesJson,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$ProdutosTableAnnotationComposer
    extends Composer<_$AppDatabase, $ProdutosTable> {
  $$ProdutosTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get usuarioId =>
      $composableBuilder(column: $table.usuarioId, builder: (column) => column);

  GeneratedColumn<String> get categoriaId => $composableBuilder(
    column: $table.categoriaId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get nome =>
      $composableBuilder(column: $table.nome, builder: (column) => column);

  GeneratedColumn<String> get codigoBarras => $composableBuilder(
    column: $table.codigoBarras,
    builder: (column) => column,
  );

  GeneratedColumn<String> get codigoReferencia => $composableBuilder(
    column: $table.codigoReferencia,
    builder: (column) => column,
  );

  GeneratedColumn<double> get precoVendaSugerido => $composableBuilder(
    column: $table.precoVendaSugerido,
    builder: (column) => column,
  );

  GeneratedColumn<double> get precoCusto => $composableBuilder(
    column: $table.precoCusto,
    builder: (column) => column,
  );

  GeneratedColumn<double> get precoPromocional => $composableBuilder(
    column: $table.precoPromocional,
    builder: (column) => column,
  );

  GeneratedColumn<String> get dataInicioPromocao => $composableBuilder(
    column: $table.dataInicioPromocao,
    builder: (column) => column,
  );

  GeneratedColumn<String> get dataFimPromocao => $composableBuilder(
    column: $table.dataFimPromocao,
    builder: (column) => column,
  );

  GeneratedColumn<double> get precoVigente => $composableBuilder(
    column: $table.precoVigente,
    builder: (column) => column,
  );

  GeneratedColumn<bool> get emPromocao => $composableBuilder(
    column: $table.emPromocao,
    builder: (column) => column,
  );

  GeneratedColumn<int> get estoqueAtual => $composableBuilder(
    column: $table.estoqueAtual,
    builder: (column) => column,
  );

  GeneratedColumn<int> get estoqueMinimo => $composableBuilder(
    column: $table.estoqueMinimo,
    builder: (column) => column,
  );

  GeneratedColumn<String> get unidadeMedida => $composableBuilder(
    column: $table.unidadeMedida,
    builder: (column) => column,
  );

  GeneratedColumn<bool> get vendaFracionada => $composableBuilder(
    column: $table.vendaFracionada,
    builder: (column) => column,
  );

  GeneratedColumn<String> get marca =>
      $composableBuilder(column: $table.marca, builder: (column) => column);

  GeneratedColumn<String> get fotoUrl =>
      $composableBuilder(column: $table.fotoUrl, builder: (column) => column);

  GeneratedColumn<String> get fotoLocalPath => $composableBuilder(
    column: $table.fotoLocalPath,
    builder: (column) => column,
  );

  GeneratedColumn<bool> get ativo =>
      $composableBuilder(column: $table.ativo, builder: (column) => column);

  GeneratedColumn<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => column,
  );

  GeneratedColumn<String> get variantesJson => $composableBuilder(
    column: $table.variantesJson,
    builder: (column) => column,
  );
}

class $$ProdutosTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $ProdutosTable,
          Produto,
          $$ProdutosTableFilterComposer,
          $$ProdutosTableOrderingComposer,
          $$ProdutosTableAnnotationComposer,
          $$ProdutosTableCreateCompanionBuilder,
          $$ProdutosTableUpdateCompanionBuilder,
          (Produto, BaseReferences<_$AppDatabase, $ProdutosTable, Produto>),
          Produto,
          PrefetchHooks Function()
        > {
  $$ProdutosTableTableManager(_$AppDatabase db, $ProdutosTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$ProdutosTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$ProdutosTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$ProdutosTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> usuarioId = const Value.absent(),
                Value<String?> categoriaId = const Value.absent(),
                Value<String> nome = const Value.absent(),
                Value<String?> codigoBarras = const Value.absent(),
                Value<String?> codigoReferencia = const Value.absent(),
                Value<double> precoVendaSugerido = const Value.absent(),
                Value<double> precoCusto = const Value.absent(),
                Value<double> precoPromocional = const Value.absent(),
                Value<String?> dataInicioPromocao = const Value.absent(),
                Value<String?> dataFimPromocao = const Value.absent(),
                Value<double> precoVigente = const Value.absent(),
                Value<bool> emPromocao = const Value.absent(),
                Value<int> estoqueAtual = const Value.absent(),
                Value<int> estoqueMinimo = const Value.absent(),
                Value<String> unidadeMedida = const Value.absent(),
                Value<bool> vendaFracionada = const Value.absent(),
                Value<String?> marca = const Value.absent(),
                Value<String?> fotoUrl = const Value.absent(),
                Value<String?> fotoLocalPath = const Value.absent(),
                Value<bool> ativo = const Value.absent(),
                Value<String?> dataAtualizacao = const Value.absent(),
                Value<String?> variantesJson = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => ProdutosCompanion(
                id: id,
                usuarioId: usuarioId,
                categoriaId: categoriaId,
                nome: nome,
                codigoBarras: codigoBarras,
                codigoReferencia: codigoReferencia,
                precoVendaSugerido: precoVendaSugerido,
                precoCusto: precoCusto,
                precoPromocional: precoPromocional,
                dataInicioPromocao: dataInicioPromocao,
                dataFimPromocao: dataFimPromocao,
                precoVigente: precoVigente,
                emPromocao: emPromocao,
                estoqueAtual: estoqueAtual,
                estoqueMinimo: estoqueMinimo,
                unidadeMedida: unidadeMedida,
                vendaFracionada: vendaFracionada,
                marca: marca,
                fotoUrl: fotoUrl,
                fotoLocalPath: fotoLocalPath,
                ativo: ativo,
                dataAtualizacao: dataAtualizacao,
                variantesJson: variantesJson,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String usuarioId,
                Value<String?> categoriaId = const Value.absent(),
                required String nome,
                Value<String?> codigoBarras = const Value.absent(),
                Value<String?> codigoReferencia = const Value.absent(),
                Value<double> precoVendaSugerido = const Value.absent(),
                Value<double> precoCusto = const Value.absent(),
                Value<double> precoPromocional = const Value.absent(),
                Value<String?> dataInicioPromocao = const Value.absent(),
                Value<String?> dataFimPromocao = const Value.absent(),
                Value<double> precoVigente = const Value.absent(),
                Value<bool> emPromocao = const Value.absent(),
                Value<int> estoqueAtual = const Value.absent(),
                Value<int> estoqueMinimo = const Value.absent(),
                Value<String> unidadeMedida = const Value.absent(),
                Value<bool> vendaFracionada = const Value.absent(),
                Value<String?> marca = const Value.absent(),
                Value<String?> fotoUrl = const Value.absent(),
                Value<String?> fotoLocalPath = const Value.absent(),
                Value<bool> ativo = const Value.absent(),
                Value<String?> dataAtualizacao = const Value.absent(),
                Value<String?> variantesJson = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => ProdutosCompanion.insert(
                id: id,
                usuarioId: usuarioId,
                categoriaId: categoriaId,
                nome: nome,
                codigoBarras: codigoBarras,
                codigoReferencia: codigoReferencia,
                precoVendaSugerido: precoVendaSugerido,
                precoCusto: precoCusto,
                precoPromocional: precoPromocional,
                dataInicioPromocao: dataInicioPromocao,
                dataFimPromocao: dataFimPromocao,
                precoVigente: precoVigente,
                emPromocao: emPromocao,
                estoqueAtual: estoqueAtual,
                estoqueMinimo: estoqueMinimo,
                unidadeMedida: unidadeMedida,
                vendaFracionada: vendaFracionada,
                marca: marca,
                fotoUrl: fotoUrl,
                fotoLocalPath: fotoLocalPath,
                ativo: ativo,
                dataAtualizacao: dataAtualizacao,
                variantesJson: variantesJson,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$ProdutosTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $ProdutosTable,
      Produto,
      $$ProdutosTableFilterComposer,
      $$ProdutosTableOrderingComposer,
      $$ProdutosTableAnnotationComposer,
      $$ProdutosTableCreateCompanionBuilder,
      $$ProdutosTableUpdateCompanionBuilder,
      (Produto, BaseReferences<_$AppDatabase, $ProdutosTable, Produto>),
      Produto,
      PrefetchHooks Function()
    >;
typedef $$ClientesTableCreateCompanionBuilder =
    ClientesCompanion Function({
      required String id,
      required String usuarioId,
      required String nomeCompleto,
      Value<String?> cpf,
      Value<String?> telefone,
      Value<String?> email,
      Value<String?> enderecoLogradouro,
      Value<String?> enderecoNumero,
      Value<String?> enderecoBairro,
      Value<String?> enderecoCidade,
      Value<String?> enderecoEstado,
      Value<String?> enderecoCep,
      Value<bool> ativo,
      Value<String?> dataCriacao,
      Value<String?> dataAtualizacao,
      Value<String> syncStatus,
      Value<int> rowid,
    });
typedef $$ClientesTableUpdateCompanionBuilder =
    ClientesCompanion Function({
      Value<String> id,
      Value<String> usuarioId,
      Value<String> nomeCompleto,
      Value<String?> cpf,
      Value<String?> telefone,
      Value<String?> email,
      Value<String?> enderecoLogradouro,
      Value<String?> enderecoNumero,
      Value<String?> enderecoBairro,
      Value<String?> enderecoCidade,
      Value<String?> enderecoEstado,
      Value<String?> enderecoCep,
      Value<bool> ativo,
      Value<String?> dataCriacao,
      Value<String?> dataAtualizacao,
      Value<String> syncStatus,
      Value<int> rowid,
    });

class $$ClientesTableFilterComposer
    extends Composer<_$AppDatabase, $ClientesTable> {
  $$ClientesTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get nomeCompleto => $composableBuilder(
    column: $table.nomeCompleto,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get cpf => $composableBuilder(
    column: $table.cpf,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get telefone => $composableBuilder(
    column: $table.telefone,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get email => $composableBuilder(
    column: $table.email,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get enderecoLogradouro => $composableBuilder(
    column: $table.enderecoLogradouro,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get enderecoNumero => $composableBuilder(
    column: $table.enderecoNumero,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get enderecoBairro => $composableBuilder(
    column: $table.enderecoBairro,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get enderecoCidade => $composableBuilder(
    column: $table.enderecoCidade,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get enderecoEstado => $composableBuilder(
    column: $table.enderecoEstado,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get enderecoCep => $composableBuilder(
    column: $table.enderecoCep,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get ativo => $composableBuilder(
    column: $table.ativo,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataCriacao => $composableBuilder(
    column: $table.dataCriacao,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get syncStatus => $composableBuilder(
    column: $table.syncStatus,
    builder: (column) => ColumnFilters(column),
  );
}

class $$ClientesTableOrderingComposer
    extends Composer<_$AppDatabase, $ClientesTable> {
  $$ClientesTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get nomeCompleto => $composableBuilder(
    column: $table.nomeCompleto,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get cpf => $composableBuilder(
    column: $table.cpf,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get telefone => $composableBuilder(
    column: $table.telefone,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get email => $composableBuilder(
    column: $table.email,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get enderecoLogradouro => $composableBuilder(
    column: $table.enderecoLogradouro,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get enderecoNumero => $composableBuilder(
    column: $table.enderecoNumero,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get enderecoBairro => $composableBuilder(
    column: $table.enderecoBairro,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get enderecoCidade => $composableBuilder(
    column: $table.enderecoCidade,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get enderecoEstado => $composableBuilder(
    column: $table.enderecoEstado,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get enderecoCep => $composableBuilder(
    column: $table.enderecoCep,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get ativo => $composableBuilder(
    column: $table.ativo,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataCriacao => $composableBuilder(
    column: $table.dataCriacao,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get syncStatus => $composableBuilder(
    column: $table.syncStatus,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$ClientesTableAnnotationComposer
    extends Composer<_$AppDatabase, $ClientesTable> {
  $$ClientesTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get usuarioId =>
      $composableBuilder(column: $table.usuarioId, builder: (column) => column);

  GeneratedColumn<String> get nomeCompleto => $composableBuilder(
    column: $table.nomeCompleto,
    builder: (column) => column,
  );

  GeneratedColumn<String> get cpf =>
      $composableBuilder(column: $table.cpf, builder: (column) => column);

  GeneratedColumn<String> get telefone =>
      $composableBuilder(column: $table.telefone, builder: (column) => column);

  GeneratedColumn<String> get email =>
      $composableBuilder(column: $table.email, builder: (column) => column);

  GeneratedColumn<String> get enderecoLogradouro => $composableBuilder(
    column: $table.enderecoLogradouro,
    builder: (column) => column,
  );

  GeneratedColumn<String> get enderecoNumero => $composableBuilder(
    column: $table.enderecoNumero,
    builder: (column) => column,
  );

  GeneratedColumn<String> get enderecoBairro => $composableBuilder(
    column: $table.enderecoBairro,
    builder: (column) => column,
  );

  GeneratedColumn<String> get enderecoCidade => $composableBuilder(
    column: $table.enderecoCidade,
    builder: (column) => column,
  );

  GeneratedColumn<String> get enderecoEstado => $composableBuilder(
    column: $table.enderecoEstado,
    builder: (column) => column,
  );

  GeneratedColumn<String> get enderecoCep => $composableBuilder(
    column: $table.enderecoCep,
    builder: (column) => column,
  );

  GeneratedColumn<bool> get ativo =>
      $composableBuilder(column: $table.ativo, builder: (column) => column);

  GeneratedColumn<String> get dataCriacao => $composableBuilder(
    column: $table.dataCriacao,
    builder: (column) => column,
  );

  GeneratedColumn<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => column,
  );

  GeneratedColumn<String> get syncStatus => $composableBuilder(
    column: $table.syncStatus,
    builder: (column) => column,
  );
}

class $$ClientesTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $ClientesTable,
          Cliente,
          $$ClientesTableFilterComposer,
          $$ClientesTableOrderingComposer,
          $$ClientesTableAnnotationComposer,
          $$ClientesTableCreateCompanionBuilder,
          $$ClientesTableUpdateCompanionBuilder,
          (Cliente, BaseReferences<_$AppDatabase, $ClientesTable, Cliente>),
          Cliente,
          PrefetchHooks Function()
        > {
  $$ClientesTableTableManager(_$AppDatabase db, $ClientesTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$ClientesTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$ClientesTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$ClientesTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> usuarioId = const Value.absent(),
                Value<String> nomeCompleto = const Value.absent(),
                Value<String?> cpf = const Value.absent(),
                Value<String?> telefone = const Value.absent(),
                Value<String?> email = const Value.absent(),
                Value<String?> enderecoLogradouro = const Value.absent(),
                Value<String?> enderecoNumero = const Value.absent(),
                Value<String?> enderecoBairro = const Value.absent(),
                Value<String?> enderecoCidade = const Value.absent(),
                Value<String?> enderecoEstado = const Value.absent(),
                Value<String?> enderecoCep = const Value.absent(),
                Value<bool> ativo = const Value.absent(),
                Value<String?> dataCriacao = const Value.absent(),
                Value<String?> dataAtualizacao = const Value.absent(),
                Value<String> syncStatus = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => ClientesCompanion(
                id: id,
                usuarioId: usuarioId,
                nomeCompleto: nomeCompleto,
                cpf: cpf,
                telefone: telefone,
                email: email,
                enderecoLogradouro: enderecoLogradouro,
                enderecoNumero: enderecoNumero,
                enderecoBairro: enderecoBairro,
                enderecoCidade: enderecoCidade,
                enderecoEstado: enderecoEstado,
                enderecoCep: enderecoCep,
                ativo: ativo,
                dataCriacao: dataCriacao,
                dataAtualizacao: dataAtualizacao,
                syncStatus: syncStatus,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String usuarioId,
                required String nomeCompleto,
                Value<String?> cpf = const Value.absent(),
                Value<String?> telefone = const Value.absent(),
                Value<String?> email = const Value.absent(),
                Value<String?> enderecoLogradouro = const Value.absent(),
                Value<String?> enderecoNumero = const Value.absent(),
                Value<String?> enderecoBairro = const Value.absent(),
                Value<String?> enderecoCidade = const Value.absent(),
                Value<String?> enderecoEstado = const Value.absent(),
                Value<String?> enderecoCep = const Value.absent(),
                Value<bool> ativo = const Value.absent(),
                Value<String?> dataCriacao = const Value.absent(),
                Value<String?> dataAtualizacao = const Value.absent(),
                Value<String> syncStatus = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => ClientesCompanion.insert(
                id: id,
                usuarioId: usuarioId,
                nomeCompleto: nomeCompleto,
                cpf: cpf,
                telefone: telefone,
                email: email,
                enderecoLogradouro: enderecoLogradouro,
                enderecoNumero: enderecoNumero,
                enderecoBairro: enderecoBairro,
                enderecoCidade: enderecoCidade,
                enderecoEstado: enderecoEstado,
                enderecoCep: enderecoCep,
                ativo: ativo,
                dataCriacao: dataCriacao,
                dataAtualizacao: dataAtualizacao,
                syncStatus: syncStatus,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$ClientesTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $ClientesTable,
      Cliente,
      $$ClientesTableFilterComposer,
      $$ClientesTableOrderingComposer,
      $$ClientesTableAnnotationComposer,
      $$ClientesTableCreateCompanionBuilder,
      $$ClientesTableUpdateCompanionBuilder,
      (Cliente, BaseReferences<_$AppDatabase, $ClientesTable, Cliente>),
      Cliente,
      PrefetchHooks Function()
    >;
typedef $$FormasPagamentoTableCreateCompanionBuilder =
    FormasPagamentoCompanion Function({
      required String id,
      required String usuarioId,
      required String nome,
      required String tipo,
      Value<bool> ativo,
      Value<bool> aceitaParcelamento,
      Value<String?> dataAtualizacao,
      Value<int> rowid,
    });
typedef $$FormasPagamentoTableUpdateCompanionBuilder =
    FormasPagamentoCompanion Function({
      Value<String> id,
      Value<String> usuarioId,
      Value<String> nome,
      Value<String> tipo,
      Value<bool> ativo,
      Value<bool> aceitaParcelamento,
      Value<String?> dataAtualizacao,
      Value<int> rowid,
    });

class $$FormasPagamentoTableFilterComposer
    extends Composer<_$AppDatabase, $FormasPagamentoTable> {
  $$FormasPagamentoTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get nome => $composableBuilder(
    column: $table.nome,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get tipo => $composableBuilder(
    column: $table.tipo,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get ativo => $composableBuilder(
    column: $table.ativo,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<bool> get aceitaParcelamento => $composableBuilder(
    column: $table.aceitaParcelamento,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => ColumnFilters(column),
  );
}

class $$FormasPagamentoTableOrderingComposer
    extends Composer<_$AppDatabase, $FormasPagamentoTable> {
  $$FormasPagamentoTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get nome => $composableBuilder(
    column: $table.nome,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get tipo => $composableBuilder(
    column: $table.tipo,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get ativo => $composableBuilder(
    column: $table.ativo,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<bool> get aceitaParcelamento => $composableBuilder(
    column: $table.aceitaParcelamento,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$FormasPagamentoTableAnnotationComposer
    extends Composer<_$AppDatabase, $FormasPagamentoTable> {
  $$FormasPagamentoTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get usuarioId =>
      $composableBuilder(column: $table.usuarioId, builder: (column) => column);

  GeneratedColumn<String> get nome =>
      $composableBuilder(column: $table.nome, builder: (column) => column);

  GeneratedColumn<String> get tipo =>
      $composableBuilder(column: $table.tipo, builder: (column) => column);

  GeneratedColumn<bool> get ativo =>
      $composableBuilder(column: $table.ativo, builder: (column) => column);

  GeneratedColumn<bool> get aceitaParcelamento => $composableBuilder(
    column: $table.aceitaParcelamento,
    builder: (column) => column,
  );

  GeneratedColumn<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => column,
  );
}

class $$FormasPagamentoTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $FormasPagamentoTable,
          FormaPagamento,
          $$FormasPagamentoTableFilterComposer,
          $$FormasPagamentoTableOrderingComposer,
          $$FormasPagamentoTableAnnotationComposer,
          $$FormasPagamentoTableCreateCompanionBuilder,
          $$FormasPagamentoTableUpdateCompanionBuilder,
          (
            FormaPagamento,
            BaseReferences<
              _$AppDatabase,
              $FormasPagamentoTable,
              FormaPagamento
            >,
          ),
          FormaPagamento,
          PrefetchHooks Function()
        > {
  $$FormasPagamentoTableTableManager(
    _$AppDatabase db,
    $FormasPagamentoTable table,
  ) : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$FormasPagamentoTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$FormasPagamentoTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$FormasPagamentoTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> usuarioId = const Value.absent(),
                Value<String> nome = const Value.absent(),
                Value<String> tipo = const Value.absent(),
                Value<bool> ativo = const Value.absent(),
                Value<bool> aceitaParcelamento = const Value.absent(),
                Value<String?> dataAtualizacao = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => FormasPagamentoCompanion(
                id: id,
                usuarioId: usuarioId,
                nome: nome,
                tipo: tipo,
                ativo: ativo,
                aceitaParcelamento: aceitaParcelamento,
                dataAtualizacao: dataAtualizacao,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String usuarioId,
                required String nome,
                required String tipo,
                Value<bool> ativo = const Value.absent(),
                Value<bool> aceitaParcelamento = const Value.absent(),
                Value<String?> dataAtualizacao = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => FormasPagamentoCompanion.insert(
                id: id,
                usuarioId: usuarioId,
                nome: nome,
                tipo: tipo,
                ativo: ativo,
                aceitaParcelamento: aceitaParcelamento,
                dataAtualizacao: dataAtualizacao,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$FormasPagamentoTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $FormasPagamentoTable,
      FormaPagamento,
      $$FormasPagamentoTableFilterComposer,
      $$FormasPagamentoTableOrderingComposer,
      $$FormasPagamentoTableAnnotationComposer,
      $$FormasPagamentoTableCreateCompanionBuilder,
      $$FormasPagamentoTableUpdateCompanionBuilder,
      (
        FormaPagamento,
        BaseReferences<_$AppDatabase, $FormasPagamentoTable, FormaPagamento>,
      ),
      FormaPagamento,
      PrefetchHooks Function()
    >;
typedef $$VendasTableCreateCompanionBuilder =
    VendasCompanion Function({
      required String id,
      required String usuarioId,
      Value<String?> clienteId,
      Value<String?> colaboradorVendedorId,
      Value<String?> formaPagamentoId,
      required String dataVenda,
      Value<double> valorTotal,
      Value<int> numeroParcelas,
      Value<String> statusVendaCodigo,
      Value<String?> observacoes,
      Value<String> tipoVenda,
      Value<double> acrescimoValor,
      Value<String?> acrescimoTipo,
      Value<double> descontoGlobalValor,
      Value<String?> descontoGlobalTipo,
      Value<String?> cpfConsumidor,
      Value<String?> dataPrimeiroVencimento,
      required String dataCriacao,
      Value<String?> dataAtualizacao,
      Value<String> syncStatus,
      Value<String?> syncError,
      Value<int> syncTentativas,
      Value<int> rowid,
    });
typedef $$VendasTableUpdateCompanionBuilder =
    VendasCompanion Function({
      Value<String> id,
      Value<String> usuarioId,
      Value<String?> clienteId,
      Value<String?> colaboradorVendedorId,
      Value<String?> formaPagamentoId,
      Value<String> dataVenda,
      Value<double> valorTotal,
      Value<int> numeroParcelas,
      Value<String> statusVendaCodigo,
      Value<String?> observacoes,
      Value<String> tipoVenda,
      Value<double> acrescimoValor,
      Value<String?> acrescimoTipo,
      Value<double> descontoGlobalValor,
      Value<String?> descontoGlobalTipo,
      Value<String?> cpfConsumidor,
      Value<String?> dataPrimeiroVencimento,
      Value<String> dataCriacao,
      Value<String?> dataAtualizacao,
      Value<String> syncStatus,
      Value<String?> syncError,
      Value<int> syncTentativas,
      Value<int> rowid,
    });

class $$VendasTableFilterComposer
    extends Composer<_$AppDatabase, $VendasTable> {
  $$VendasTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get clienteId => $composableBuilder(
    column: $table.clienteId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get colaboradorVendedorId => $composableBuilder(
    column: $table.colaboradorVendedorId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get formaPagamentoId => $composableBuilder(
    column: $table.formaPagamentoId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataVenda => $composableBuilder(
    column: $table.dataVenda,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get valorTotal => $composableBuilder(
    column: $table.valorTotal,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get numeroParcelas => $composableBuilder(
    column: $table.numeroParcelas,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get statusVendaCodigo => $composableBuilder(
    column: $table.statusVendaCodigo,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get observacoes => $composableBuilder(
    column: $table.observacoes,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get tipoVenda => $composableBuilder(
    column: $table.tipoVenda,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get acrescimoValor => $composableBuilder(
    column: $table.acrescimoValor,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get acrescimoTipo => $composableBuilder(
    column: $table.acrescimoTipo,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get descontoGlobalValor => $composableBuilder(
    column: $table.descontoGlobalValor,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get descontoGlobalTipo => $composableBuilder(
    column: $table.descontoGlobalTipo,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get cpfConsumidor => $composableBuilder(
    column: $table.cpfConsumidor,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataPrimeiroVencimento => $composableBuilder(
    column: $table.dataPrimeiroVencimento,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataCriacao => $composableBuilder(
    column: $table.dataCriacao,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get syncStatus => $composableBuilder(
    column: $table.syncStatus,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get syncError => $composableBuilder(
    column: $table.syncError,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get syncTentativas => $composableBuilder(
    column: $table.syncTentativas,
    builder: (column) => ColumnFilters(column),
  );
}

class $$VendasTableOrderingComposer
    extends Composer<_$AppDatabase, $VendasTable> {
  $$VendasTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get clienteId => $composableBuilder(
    column: $table.clienteId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get colaboradorVendedorId => $composableBuilder(
    column: $table.colaboradorVendedorId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get formaPagamentoId => $composableBuilder(
    column: $table.formaPagamentoId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataVenda => $composableBuilder(
    column: $table.dataVenda,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get valorTotal => $composableBuilder(
    column: $table.valorTotal,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get numeroParcelas => $composableBuilder(
    column: $table.numeroParcelas,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get statusVendaCodigo => $composableBuilder(
    column: $table.statusVendaCodigo,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get observacoes => $composableBuilder(
    column: $table.observacoes,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get tipoVenda => $composableBuilder(
    column: $table.tipoVenda,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get acrescimoValor => $composableBuilder(
    column: $table.acrescimoValor,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get acrescimoTipo => $composableBuilder(
    column: $table.acrescimoTipo,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get descontoGlobalValor => $composableBuilder(
    column: $table.descontoGlobalValor,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get descontoGlobalTipo => $composableBuilder(
    column: $table.descontoGlobalTipo,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get cpfConsumidor => $composableBuilder(
    column: $table.cpfConsumidor,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataPrimeiroVencimento => $composableBuilder(
    column: $table.dataPrimeiroVencimento,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataCriacao => $composableBuilder(
    column: $table.dataCriacao,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get syncStatus => $composableBuilder(
    column: $table.syncStatus,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get syncError => $composableBuilder(
    column: $table.syncError,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get syncTentativas => $composableBuilder(
    column: $table.syncTentativas,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$VendasTableAnnotationComposer
    extends Composer<_$AppDatabase, $VendasTable> {
  $$VendasTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get usuarioId =>
      $composableBuilder(column: $table.usuarioId, builder: (column) => column);

  GeneratedColumn<String> get clienteId =>
      $composableBuilder(column: $table.clienteId, builder: (column) => column);

  GeneratedColumn<String> get colaboradorVendedorId => $composableBuilder(
    column: $table.colaboradorVendedorId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get formaPagamentoId => $composableBuilder(
    column: $table.formaPagamentoId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get dataVenda =>
      $composableBuilder(column: $table.dataVenda, builder: (column) => column);

  GeneratedColumn<double> get valorTotal => $composableBuilder(
    column: $table.valorTotal,
    builder: (column) => column,
  );

  GeneratedColumn<int> get numeroParcelas => $composableBuilder(
    column: $table.numeroParcelas,
    builder: (column) => column,
  );

  GeneratedColumn<String> get statusVendaCodigo => $composableBuilder(
    column: $table.statusVendaCodigo,
    builder: (column) => column,
  );

  GeneratedColumn<String> get observacoes => $composableBuilder(
    column: $table.observacoes,
    builder: (column) => column,
  );

  GeneratedColumn<String> get tipoVenda =>
      $composableBuilder(column: $table.tipoVenda, builder: (column) => column);

  GeneratedColumn<double> get acrescimoValor => $composableBuilder(
    column: $table.acrescimoValor,
    builder: (column) => column,
  );

  GeneratedColumn<String> get acrescimoTipo => $composableBuilder(
    column: $table.acrescimoTipo,
    builder: (column) => column,
  );

  GeneratedColumn<double> get descontoGlobalValor => $composableBuilder(
    column: $table.descontoGlobalValor,
    builder: (column) => column,
  );

  GeneratedColumn<String> get descontoGlobalTipo => $composableBuilder(
    column: $table.descontoGlobalTipo,
    builder: (column) => column,
  );

  GeneratedColumn<String> get cpfConsumidor => $composableBuilder(
    column: $table.cpfConsumidor,
    builder: (column) => column,
  );

  GeneratedColumn<String> get dataPrimeiroVencimento => $composableBuilder(
    column: $table.dataPrimeiroVencimento,
    builder: (column) => column,
  );

  GeneratedColumn<String> get dataCriacao => $composableBuilder(
    column: $table.dataCriacao,
    builder: (column) => column,
  );

  GeneratedColumn<String> get dataAtualizacao => $composableBuilder(
    column: $table.dataAtualizacao,
    builder: (column) => column,
  );

  GeneratedColumn<String> get syncStatus => $composableBuilder(
    column: $table.syncStatus,
    builder: (column) => column,
  );

  GeneratedColumn<String> get syncError =>
      $composableBuilder(column: $table.syncError, builder: (column) => column);

  GeneratedColumn<int> get syncTentativas => $composableBuilder(
    column: $table.syncTentativas,
    builder: (column) => column,
  );
}

class $$VendasTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $VendasTable,
          Venda,
          $$VendasTableFilterComposer,
          $$VendasTableOrderingComposer,
          $$VendasTableAnnotationComposer,
          $$VendasTableCreateCompanionBuilder,
          $$VendasTableUpdateCompanionBuilder,
          (Venda, BaseReferences<_$AppDatabase, $VendasTable, Venda>),
          Venda,
          PrefetchHooks Function()
        > {
  $$VendasTableTableManager(_$AppDatabase db, $VendasTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$VendasTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$VendasTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$VendasTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> usuarioId = const Value.absent(),
                Value<String?> clienteId = const Value.absent(),
                Value<String?> colaboradorVendedorId = const Value.absent(),
                Value<String?> formaPagamentoId = const Value.absent(),
                Value<String> dataVenda = const Value.absent(),
                Value<double> valorTotal = const Value.absent(),
                Value<int> numeroParcelas = const Value.absent(),
                Value<String> statusVendaCodigo = const Value.absent(),
                Value<String?> observacoes = const Value.absent(),
                Value<String> tipoVenda = const Value.absent(),
                Value<double> acrescimoValor = const Value.absent(),
                Value<String?> acrescimoTipo = const Value.absent(),
                Value<double> descontoGlobalValor = const Value.absent(),
                Value<String?> descontoGlobalTipo = const Value.absent(),
                Value<String?> cpfConsumidor = const Value.absent(),
                Value<String?> dataPrimeiroVencimento = const Value.absent(),
                Value<String> dataCriacao = const Value.absent(),
                Value<String?> dataAtualizacao = const Value.absent(),
                Value<String> syncStatus = const Value.absent(),
                Value<String?> syncError = const Value.absent(),
                Value<int> syncTentativas = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => VendasCompanion(
                id: id,
                usuarioId: usuarioId,
                clienteId: clienteId,
                colaboradorVendedorId: colaboradorVendedorId,
                formaPagamentoId: formaPagamentoId,
                dataVenda: dataVenda,
                valorTotal: valorTotal,
                numeroParcelas: numeroParcelas,
                statusVendaCodigo: statusVendaCodigo,
                observacoes: observacoes,
                tipoVenda: tipoVenda,
                acrescimoValor: acrescimoValor,
                acrescimoTipo: acrescimoTipo,
                descontoGlobalValor: descontoGlobalValor,
                descontoGlobalTipo: descontoGlobalTipo,
                cpfConsumidor: cpfConsumidor,
                dataPrimeiroVencimento: dataPrimeiroVencimento,
                dataCriacao: dataCriacao,
                dataAtualizacao: dataAtualizacao,
                syncStatus: syncStatus,
                syncError: syncError,
                syncTentativas: syncTentativas,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String usuarioId,
                Value<String?> clienteId = const Value.absent(),
                Value<String?> colaboradorVendedorId = const Value.absent(),
                Value<String?> formaPagamentoId = const Value.absent(),
                required String dataVenda,
                Value<double> valorTotal = const Value.absent(),
                Value<int> numeroParcelas = const Value.absent(),
                Value<String> statusVendaCodigo = const Value.absent(),
                Value<String?> observacoes = const Value.absent(),
                Value<String> tipoVenda = const Value.absent(),
                Value<double> acrescimoValor = const Value.absent(),
                Value<String?> acrescimoTipo = const Value.absent(),
                Value<double> descontoGlobalValor = const Value.absent(),
                Value<String?> descontoGlobalTipo = const Value.absent(),
                Value<String?> cpfConsumidor = const Value.absent(),
                Value<String?> dataPrimeiroVencimento = const Value.absent(),
                required String dataCriacao,
                Value<String?> dataAtualizacao = const Value.absent(),
                Value<String> syncStatus = const Value.absent(),
                Value<String?> syncError = const Value.absent(),
                Value<int> syncTentativas = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => VendasCompanion.insert(
                id: id,
                usuarioId: usuarioId,
                clienteId: clienteId,
                colaboradorVendedorId: colaboradorVendedorId,
                formaPagamentoId: formaPagamentoId,
                dataVenda: dataVenda,
                valorTotal: valorTotal,
                numeroParcelas: numeroParcelas,
                statusVendaCodigo: statusVendaCodigo,
                observacoes: observacoes,
                tipoVenda: tipoVenda,
                acrescimoValor: acrescimoValor,
                acrescimoTipo: acrescimoTipo,
                descontoGlobalValor: descontoGlobalValor,
                descontoGlobalTipo: descontoGlobalTipo,
                cpfConsumidor: cpfConsumidor,
                dataPrimeiroVencimento: dataPrimeiroVencimento,
                dataCriacao: dataCriacao,
                dataAtualizacao: dataAtualizacao,
                syncStatus: syncStatus,
                syncError: syncError,
                syncTentativas: syncTentativas,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$VendasTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $VendasTable,
      Venda,
      $$VendasTableFilterComposer,
      $$VendasTableOrderingComposer,
      $$VendasTableAnnotationComposer,
      $$VendasTableCreateCompanionBuilder,
      $$VendasTableUpdateCompanionBuilder,
      (Venda, BaseReferences<_$AppDatabase, $VendasTable, Venda>),
      Venda,
      PrefetchHooks Function()
    >;
typedef $$VendaItensTableCreateCompanionBuilder =
    VendaItensCompanion Function({
      required String id,
      required String vendaId,
      Value<String?> produtoId,
      Value<String?> varianteId,
      Value<String?> nomeItemManual,
      required double quantidade,
      required double precoUnitarioVenda,
      Value<double> descontoPercentual,
      Value<double> descontoValor,
      Value<double> valorTotalItem,
      Value<int> rowid,
    });
typedef $$VendaItensTableUpdateCompanionBuilder =
    VendaItensCompanion Function({
      Value<String> id,
      Value<String> vendaId,
      Value<String?> produtoId,
      Value<String?> varianteId,
      Value<String?> nomeItemManual,
      Value<double> quantidade,
      Value<double> precoUnitarioVenda,
      Value<double> descontoPercentual,
      Value<double> descontoValor,
      Value<double> valorTotalItem,
      Value<int> rowid,
    });

class $$VendaItensTableFilterComposer
    extends Composer<_$AppDatabase, $VendaItensTable> {
  $$VendaItensTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get vendaId => $composableBuilder(
    column: $table.vendaId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get produtoId => $composableBuilder(
    column: $table.produtoId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get varianteId => $composableBuilder(
    column: $table.varianteId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get nomeItemManual => $composableBuilder(
    column: $table.nomeItemManual,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get quantidade => $composableBuilder(
    column: $table.quantidade,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get precoUnitarioVenda => $composableBuilder(
    column: $table.precoUnitarioVenda,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get descontoPercentual => $composableBuilder(
    column: $table.descontoPercentual,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get descontoValor => $composableBuilder(
    column: $table.descontoValor,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get valorTotalItem => $composableBuilder(
    column: $table.valorTotalItem,
    builder: (column) => ColumnFilters(column),
  );
}

class $$VendaItensTableOrderingComposer
    extends Composer<_$AppDatabase, $VendaItensTable> {
  $$VendaItensTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get vendaId => $composableBuilder(
    column: $table.vendaId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get produtoId => $composableBuilder(
    column: $table.produtoId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get varianteId => $composableBuilder(
    column: $table.varianteId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get nomeItemManual => $composableBuilder(
    column: $table.nomeItemManual,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get quantidade => $composableBuilder(
    column: $table.quantidade,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get precoUnitarioVenda => $composableBuilder(
    column: $table.precoUnitarioVenda,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get descontoPercentual => $composableBuilder(
    column: $table.descontoPercentual,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get descontoValor => $composableBuilder(
    column: $table.descontoValor,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get valorTotalItem => $composableBuilder(
    column: $table.valorTotalItem,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$VendaItensTableAnnotationComposer
    extends Composer<_$AppDatabase, $VendaItensTable> {
  $$VendaItensTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get vendaId =>
      $composableBuilder(column: $table.vendaId, builder: (column) => column);

  GeneratedColumn<String> get produtoId =>
      $composableBuilder(column: $table.produtoId, builder: (column) => column);

  GeneratedColumn<String> get varianteId => $composableBuilder(
    column: $table.varianteId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get nomeItemManual => $composableBuilder(
    column: $table.nomeItemManual,
    builder: (column) => column,
  );

  GeneratedColumn<double> get quantidade => $composableBuilder(
    column: $table.quantidade,
    builder: (column) => column,
  );

  GeneratedColumn<double> get precoUnitarioVenda => $composableBuilder(
    column: $table.precoUnitarioVenda,
    builder: (column) => column,
  );

  GeneratedColumn<double> get descontoPercentual => $composableBuilder(
    column: $table.descontoPercentual,
    builder: (column) => column,
  );

  GeneratedColumn<double> get descontoValor => $composableBuilder(
    column: $table.descontoValor,
    builder: (column) => column,
  );

  GeneratedColumn<double> get valorTotalItem => $composableBuilder(
    column: $table.valorTotalItem,
    builder: (column) => column,
  );
}

class $$VendaItensTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $VendaItensTable,
          VendaItem,
          $$VendaItensTableFilterComposer,
          $$VendaItensTableOrderingComposer,
          $$VendaItensTableAnnotationComposer,
          $$VendaItensTableCreateCompanionBuilder,
          $$VendaItensTableUpdateCompanionBuilder,
          (
            VendaItem,
            BaseReferences<_$AppDatabase, $VendaItensTable, VendaItem>,
          ),
          VendaItem,
          PrefetchHooks Function()
        > {
  $$VendaItensTableTableManager(_$AppDatabase db, $VendaItensTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$VendaItensTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$VendaItensTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$VendaItensTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> vendaId = const Value.absent(),
                Value<String?> produtoId = const Value.absent(),
                Value<String?> varianteId = const Value.absent(),
                Value<String?> nomeItemManual = const Value.absent(),
                Value<double> quantidade = const Value.absent(),
                Value<double> precoUnitarioVenda = const Value.absent(),
                Value<double> descontoPercentual = const Value.absent(),
                Value<double> descontoValor = const Value.absent(),
                Value<double> valorTotalItem = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => VendaItensCompanion(
                id: id,
                vendaId: vendaId,
                produtoId: produtoId,
                varianteId: varianteId,
                nomeItemManual: nomeItemManual,
                quantidade: quantidade,
                precoUnitarioVenda: precoUnitarioVenda,
                descontoPercentual: descontoPercentual,
                descontoValor: descontoValor,
                valorTotalItem: valorTotalItem,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String vendaId,
                Value<String?> produtoId = const Value.absent(),
                Value<String?> varianteId = const Value.absent(),
                Value<String?> nomeItemManual = const Value.absent(),
                required double quantidade,
                required double precoUnitarioVenda,
                Value<double> descontoPercentual = const Value.absent(),
                Value<double> descontoValor = const Value.absent(),
                Value<double> valorTotalItem = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => VendaItensCompanion.insert(
                id: id,
                vendaId: vendaId,
                produtoId: produtoId,
                varianteId: varianteId,
                nomeItemManual: nomeItemManual,
                quantidade: quantidade,
                precoUnitarioVenda: precoUnitarioVenda,
                descontoPercentual: descontoPercentual,
                descontoValor: descontoValor,
                valorTotalItem: valorTotalItem,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$VendaItensTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $VendaItensTable,
      VendaItem,
      $$VendaItensTableFilterComposer,
      $$VendaItensTableOrderingComposer,
      $$VendaItensTableAnnotationComposer,
      $$VendaItensTableCreateCompanionBuilder,
      $$VendaItensTableUpdateCompanionBuilder,
      (VendaItem, BaseReferences<_$AppDatabase, $VendaItensTable, VendaItem>),
      VendaItem,
      PrefetchHooks Function()
    >;
typedef $$ParcelasTableCreateCompanionBuilder =
    ParcelasCompanion Function({
      required String id,
      required String vendaId,
      required String usuarioId,
      required int numeroParcela,
      required double valorParcela,
      required String dataVencimento,
      Value<String> statusParcelaCodigo,
      Value<String?> formaPagamentoId,
      Value<String?> observacoes,
      Value<int> rowid,
    });
typedef $$ParcelasTableUpdateCompanionBuilder =
    ParcelasCompanion Function({
      Value<String> id,
      Value<String> vendaId,
      Value<String> usuarioId,
      Value<int> numeroParcela,
      Value<double> valorParcela,
      Value<String> dataVencimento,
      Value<String> statusParcelaCodigo,
      Value<String?> formaPagamentoId,
      Value<String?> observacoes,
      Value<int> rowid,
    });

class $$ParcelasTableFilterComposer
    extends Composer<_$AppDatabase, $ParcelasTable> {
  $$ParcelasTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get vendaId => $composableBuilder(
    column: $table.vendaId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get numeroParcela => $composableBuilder(
    column: $table.numeroParcela,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<double> get valorParcela => $composableBuilder(
    column: $table.valorParcela,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get dataVencimento => $composableBuilder(
    column: $table.dataVencimento,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get statusParcelaCodigo => $composableBuilder(
    column: $table.statusParcelaCodigo,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get formaPagamentoId => $composableBuilder(
    column: $table.formaPagamentoId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get observacoes => $composableBuilder(
    column: $table.observacoes,
    builder: (column) => ColumnFilters(column),
  );
}

class $$ParcelasTableOrderingComposer
    extends Composer<_$AppDatabase, $ParcelasTable> {
  $$ParcelasTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<String> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get vendaId => $composableBuilder(
    column: $table.vendaId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get usuarioId => $composableBuilder(
    column: $table.usuarioId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get numeroParcela => $composableBuilder(
    column: $table.numeroParcela,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<double> get valorParcela => $composableBuilder(
    column: $table.valorParcela,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get dataVencimento => $composableBuilder(
    column: $table.dataVencimento,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get statusParcelaCodigo => $composableBuilder(
    column: $table.statusParcelaCodigo,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get formaPagamentoId => $composableBuilder(
    column: $table.formaPagamentoId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get observacoes => $composableBuilder(
    column: $table.observacoes,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$ParcelasTableAnnotationComposer
    extends Composer<_$AppDatabase, $ParcelasTable> {
  $$ParcelasTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<String> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get vendaId =>
      $composableBuilder(column: $table.vendaId, builder: (column) => column);

  GeneratedColumn<String> get usuarioId =>
      $composableBuilder(column: $table.usuarioId, builder: (column) => column);

  GeneratedColumn<int> get numeroParcela => $composableBuilder(
    column: $table.numeroParcela,
    builder: (column) => column,
  );

  GeneratedColumn<double> get valorParcela => $composableBuilder(
    column: $table.valorParcela,
    builder: (column) => column,
  );

  GeneratedColumn<String> get dataVencimento => $composableBuilder(
    column: $table.dataVencimento,
    builder: (column) => column,
  );

  GeneratedColumn<String> get statusParcelaCodigo => $composableBuilder(
    column: $table.statusParcelaCodigo,
    builder: (column) => column,
  );

  GeneratedColumn<String> get formaPagamentoId => $composableBuilder(
    column: $table.formaPagamentoId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get observacoes => $composableBuilder(
    column: $table.observacoes,
    builder: (column) => column,
  );
}

class $$ParcelasTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $ParcelasTable,
          Parcela,
          $$ParcelasTableFilterComposer,
          $$ParcelasTableOrderingComposer,
          $$ParcelasTableAnnotationComposer,
          $$ParcelasTableCreateCompanionBuilder,
          $$ParcelasTableUpdateCompanionBuilder,
          (Parcela, BaseReferences<_$AppDatabase, $ParcelasTable, Parcela>),
          Parcela,
          PrefetchHooks Function()
        > {
  $$ParcelasTableTableManager(_$AppDatabase db, $ParcelasTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$ParcelasTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$ParcelasTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$ParcelasTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<String> id = const Value.absent(),
                Value<String> vendaId = const Value.absent(),
                Value<String> usuarioId = const Value.absent(),
                Value<int> numeroParcela = const Value.absent(),
                Value<double> valorParcela = const Value.absent(),
                Value<String> dataVencimento = const Value.absent(),
                Value<String> statusParcelaCodigo = const Value.absent(),
                Value<String?> formaPagamentoId = const Value.absent(),
                Value<String?> observacoes = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => ParcelasCompanion(
                id: id,
                vendaId: vendaId,
                usuarioId: usuarioId,
                numeroParcela: numeroParcela,
                valorParcela: valorParcela,
                dataVencimento: dataVencimento,
                statusParcelaCodigo: statusParcelaCodigo,
                formaPagamentoId: formaPagamentoId,
                observacoes: observacoes,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String id,
                required String vendaId,
                required String usuarioId,
                required int numeroParcela,
                required double valorParcela,
                required String dataVencimento,
                Value<String> statusParcelaCodigo = const Value.absent(),
                Value<String?> formaPagamentoId = const Value.absent(),
                Value<String?> observacoes = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => ParcelasCompanion.insert(
                id: id,
                vendaId: vendaId,
                usuarioId: usuarioId,
                numeroParcela: numeroParcela,
                valorParcela: valorParcela,
                dataVencimento: dataVencimento,
                statusParcelaCodigo: statusParcelaCodigo,
                formaPagamentoId: formaPagamentoId,
                observacoes: observacoes,
                rowid: rowid,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$ParcelasTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $ParcelasTable,
      Parcela,
      $$ParcelasTableFilterComposer,
      $$ParcelasTableOrderingComposer,
      $$ParcelasTableAnnotationComposer,
      $$ParcelasTableCreateCompanionBuilder,
      $$ParcelasTableUpdateCompanionBuilder,
      (Parcela, BaseReferences<_$AppDatabase, $ParcelasTable, Parcela>),
      Parcela,
      PrefetchHooks Function()
    >;
typedef $$SyncQueueTableCreateCompanionBuilder =
    SyncQueueCompanion Function({
      Value<int> id,
      required String tabela,
      required String registroId,
      required String operacao,
      required String payload,
      Value<int> tentativas,
      Value<String?> ultimoErro,
      required String criadoEm,
    });
typedef $$SyncQueueTableUpdateCompanionBuilder =
    SyncQueueCompanion Function({
      Value<int> id,
      Value<String> tabela,
      Value<String> registroId,
      Value<String> operacao,
      Value<String> payload,
      Value<int> tentativas,
      Value<String?> ultimoErro,
      Value<String> criadoEm,
    });

class $$SyncQueueTableFilterComposer
    extends Composer<_$AppDatabase, $SyncQueueTable> {
  $$SyncQueueTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get tabela => $composableBuilder(
    column: $table.tabela,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get registroId => $composableBuilder(
    column: $table.registroId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get operacao => $composableBuilder(
    column: $table.operacao,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get payload => $composableBuilder(
    column: $table.payload,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<int> get tentativas => $composableBuilder(
    column: $table.tentativas,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get ultimoErro => $composableBuilder(
    column: $table.ultimoErro,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get criadoEm => $composableBuilder(
    column: $table.criadoEm,
    builder: (column) => ColumnFilters(column),
  );
}

class $$SyncQueueTableOrderingComposer
    extends Composer<_$AppDatabase, $SyncQueueTable> {
  $$SyncQueueTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get id => $composableBuilder(
    column: $table.id,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get tabela => $composableBuilder(
    column: $table.tabela,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get registroId => $composableBuilder(
    column: $table.registroId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get operacao => $composableBuilder(
    column: $table.operacao,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get payload => $composableBuilder(
    column: $table.payload,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<int> get tentativas => $composableBuilder(
    column: $table.tentativas,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get ultimoErro => $composableBuilder(
    column: $table.ultimoErro,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get criadoEm => $composableBuilder(
    column: $table.criadoEm,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$SyncQueueTableAnnotationComposer
    extends Composer<_$AppDatabase, $SyncQueueTable> {
  $$SyncQueueTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get id =>
      $composableBuilder(column: $table.id, builder: (column) => column);

  GeneratedColumn<String> get tabela =>
      $composableBuilder(column: $table.tabela, builder: (column) => column);

  GeneratedColumn<String> get registroId => $composableBuilder(
    column: $table.registroId,
    builder: (column) => column,
  );

  GeneratedColumn<String> get operacao =>
      $composableBuilder(column: $table.operacao, builder: (column) => column);

  GeneratedColumn<String> get payload =>
      $composableBuilder(column: $table.payload, builder: (column) => column);

  GeneratedColumn<int> get tentativas => $composableBuilder(
    column: $table.tentativas,
    builder: (column) => column,
  );

  GeneratedColumn<String> get ultimoErro => $composableBuilder(
    column: $table.ultimoErro,
    builder: (column) => column,
  );

  GeneratedColumn<String> get criadoEm =>
      $composableBuilder(column: $table.criadoEm, builder: (column) => column);
}

class $$SyncQueueTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $SyncQueueTable,
          SyncQueueData,
          $$SyncQueueTableFilterComposer,
          $$SyncQueueTableOrderingComposer,
          $$SyncQueueTableAnnotationComposer,
          $$SyncQueueTableCreateCompanionBuilder,
          $$SyncQueueTableUpdateCompanionBuilder,
          (
            SyncQueueData,
            BaseReferences<_$AppDatabase, $SyncQueueTable, SyncQueueData>,
          ),
          SyncQueueData,
          PrefetchHooks Function()
        > {
  $$SyncQueueTableTableManager(_$AppDatabase db, $SyncQueueTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$SyncQueueTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$SyncQueueTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$SyncQueueTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                Value<String> tabela = const Value.absent(),
                Value<String> registroId = const Value.absent(),
                Value<String> operacao = const Value.absent(),
                Value<String> payload = const Value.absent(),
                Value<int> tentativas = const Value.absent(),
                Value<String?> ultimoErro = const Value.absent(),
                Value<String> criadoEm = const Value.absent(),
              }) => SyncQueueCompanion(
                id: id,
                tabela: tabela,
                registroId: registroId,
                operacao: operacao,
                payload: payload,
                tentativas: tentativas,
                ultimoErro: ultimoErro,
                criadoEm: criadoEm,
              ),
          createCompanionCallback:
              ({
                Value<int> id = const Value.absent(),
                required String tabela,
                required String registroId,
                required String operacao,
                required String payload,
                Value<int> tentativas = const Value.absent(),
                Value<String?> ultimoErro = const Value.absent(),
                required String criadoEm,
              }) => SyncQueueCompanion.insert(
                id: id,
                tabela: tabela,
                registroId: registroId,
                operacao: operacao,
                payload: payload,
                tentativas: tentativas,
                ultimoErro: ultimoErro,
                criadoEm: criadoEm,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$SyncQueueTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $SyncQueueTable,
      SyncQueueData,
      $$SyncQueueTableFilterComposer,
      $$SyncQueueTableOrderingComposer,
      $$SyncQueueTableAnnotationComposer,
      $$SyncQueueTableCreateCompanionBuilder,
      $$SyncQueueTableUpdateCompanionBuilder,
      (
        SyncQueueData,
        BaseReferences<_$AppDatabase, $SyncQueueTable, SyncQueueData>,
      ),
      SyncQueueData,
      PrefetchHooks Function()
    >;

class $AppDatabaseManager {
  final _$AppDatabase _db;
  $AppDatabaseManager(this._db);
  $$TenantsTableTableManager get tenants =>
      $$TenantsTableTableManager(_db, _db.tenants);
  $$CategoriasTableTableManager get categorias =>
      $$CategoriasTableTableManager(_db, _db.categorias);
  $$ProdutosTableTableManager get produtos =>
      $$ProdutosTableTableManager(_db, _db.produtos);
  $$ClientesTableTableManager get clientes =>
      $$ClientesTableTableManager(_db, _db.clientes);
  $$FormasPagamentoTableTableManager get formasPagamento =>
      $$FormasPagamentoTableTableManager(_db, _db.formasPagamento);
  $$VendasTableTableManager get vendas =>
      $$VendasTableTableManager(_db, _db.vendas);
  $$VendaItensTableTableManager get vendaItens =>
      $$VendaItensTableTableManager(_db, _db.vendaItens);
  $$ParcelasTableTableManager get parcelas =>
      $$ParcelasTableTableManager(_db, _db.parcelas);
  $$SyncQueueTableTableManager get syncQueue =>
      $$SyncQueueTableTableManager(_db, _db.syncQueue);
}
