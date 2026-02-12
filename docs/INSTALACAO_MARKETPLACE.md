# 🚀 Guia de Instalação: Módulo Marketplace

## 📋 Pré-requisitos

- ✅ Sistema Pulse instalado e funcionando
- ✅ PostgreSQL 12+
- ✅ PHP 7.4+
- ✅ Composer
- ✅ Acesso sudo ao banco de dados

---

## 🔧 Passo 1: Executar Migration

### Opção A: Via psql (Recomendado)

```bash
# Conectar ao PostgreSQL e executar migration
sudo -u postgres psql -d pulse -f /srv/http/pulse/sql/postgres/013_create_marketplace_tables.sql
```

### Opção B: Via linha de comando direta

```bash
# Se você tem as credenciais do banco
psql -U seu_usuario -d pulse -f /srv/http/pulse/sql/postgres/013_create_marketplace_tables.sql
```

### Verificar se as tabelas foram criadas

```bash
sudo -u postgres psql -d pulse -c "\dt prest_marketplace*"
```

**Saída esperada:**

```
                        Lista de relações
 Esquema |              Nome               | Tipo  |  Dono
---------+---------------------------------+-------+----------
 public  | prest_marketplace_config        | tabela| postgres
 public  | prest_marketplace_pedido        | tabela| postgres
 public  | prest_marketplace_pedido_item   | tabela| postgres
 public  | prest_marketplace_produto       | tabela| postgres
 public  | prest_marketplace_sync_log      | tabela| postgres
```

---

## 🔧 Passo 2: Registrar Módulo

Editar `config/web.php` e adicionar o módulo:

```php
'modules' => [
    // ... outros módulos ...

    'marketplace' => [
        'class' => 'app\modules\marketplace\Module',
    ],
],
```

---

## 🔧 Passo 3: Configurar Feature Flags

Editar `config/params.php` e adicionar:

```php
return [
    // ... outras configurações ...

    'marketplace' => [
        'enabled' => false, // Desabilitado por padrão
        'mercado_livre' => false,
        'shopee' => false,
        'magazine_luiza' => false,
        'amazon' => false,
    ],
];
```

---

## 🔧 Passo 4: Verificar Instalação

```bash
# Verificar se o módulo foi registrado
php yii

# Deve aparecer na lista de comandos disponíveis (quando implementarmos console commands)
```

---

## ✅ Checklist de Instalação

- [ ] Migration executada com sucesso
- [ ] 5 tabelas criadas no banco
- [ ] Módulo registrado em `config/web.php`
- [ ] Feature flags configuradas em `config/params.php`
- [ ] Sem erros ao executar `php yii`

---

## 🎯 Próximos Passos

Após a instalação:

1. **Habilitar módulo** (quando estiver pronto para usar):

   ```php
   // config/params.php
   'marketplace' => ['enabled' => true],
   ```

2. **Configurar credenciais** de cada marketplace via interface web

3. **Testar sincronização** com produtos de teste

---

## 🔄 Rollback (Se Necessário)

Para reverter a instalação:

```bash
# Remover tabelas
sudo -u postgres psql -d pulse -c "
DROP TABLE IF EXISTS prest_marketplace_sync_log CASCADE;
DROP TABLE IF EXISTS prest_marketplace_pedido_item CASCADE;
DROP TABLE IF EXISTS prest_marketplace_pedido CASCADE;
DROP TABLE IF EXISTS prest_marketplace_produto CASCADE;
DROP TABLE IF EXISTS prest_marketplace_config CASCADE;
"

# Remover módulo de config/web.php
# Remover feature flags de config/params.php
```

---

## 📞 Suporte

Em caso de problemas:

1. Verificar logs: `runtime/logs/app.log`
2. Verificar permissões do banco de dados
3. Verificar se todas as dependências estão instaladas

---

**Documento criado em:** 11/02/2026  
**Versão:** 1.0
