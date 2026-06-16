# 📊 Scripts PostgreSQL - Sistema de Configuração de Comissões

## 📋 Descrição

Scripts SQL PostgreSQL para criar e alterar as tabelas do sistema flexível de configuração de comissões.

## 📁 Arquivos

### 0. `000_all_in_one_simple.sql` ⭐ RECOMENDADO
Script consolidado simplificado que executa todas as alterações. Esta versão ignora erros de constraints duplicadas e é mais robusta.

### 1. `000_all_in_one.sql`
Script consolidado completo que executa todas as alterações em uma única transação.

### 2. `001_create_prest_comissao_config.sql`
Cria a tabela `prest_comissao_config` com todas as colunas, constraints, índices e triggers necessários.

**O que faz:**
- ✅ Cria a tabela de configurações
- ✅ Adiciona constraints (validações)
- ✅ Cria índices para performance
- ✅ Cria foreign keys
- ✅ Cria trigger para atualizar `data_atualizacao` automaticamente

### 3. `002_add_comissao_config_id_to_prest_comissoes.sql`
Adiciona a coluna `comissao_config_id` na tabela `prest_comissoes`.

**O que faz:**
- ✅ Adiciona a coluna `comissao_config_id`
- ✅ Cria índice
- ✅ Adiciona foreign key (se a tabela `prest_comissao_config` existir)

### 4. `003_rollback_scripts.sql`
Scripts para reverter todas as alterações (rollback).

**O que faz:**
- ✅ Remove a coluna de `prest_comissoes`
- ✅ Remove a tabela `prest_comissao_config`
- ✅ Remove todos os índices e triggers

## 🚀 Como Executar

### ⭐ Opção Recomendada: Script Simplificado

```bash
psql -U seu_usuario -d nome_do_banco -f sql/postgres/000_all_in_one_simple.sql
```

### Opção 1: Executar via psql

```bash
# Conectar ao banco
psql -U seu_usuario -d nome_do_banco

# Executar script 1
\i sql/postgres/001_create_prest_comissao_config.sql

# Executar script 2
\i sql/postgres/002_add_comissao_config_id_to_prest_comissoes.sql
```

### Opção 2: Executar via linha de comando

```bash
# Script 1
psql -U seu_usuario -d nome_do_banco -f sql/postgres/001_create_prest_comissao_config.sql

# Script 2
psql -U seu_usuario -d nome_do_banco -f sql/postgres/002_add_comissao_config_id_to_prest_comissoes.sql
```

### Opção 3: Executar todos de uma vez

```bash
psql -U seu_usuario -d nome_do_banco -f sql/postgres/001_create_prest_comissao_config.sql
psql -U seu_usuario -d nome_do_banco -f sql/postgres/002_add_comissao_config_id_to_prest_comissoes.sql
```

## ⚠️ Ordem de Execução

**IMPORTANTE:** Execute os scripts na seguinte ordem:

1. ✅ `001_create_prest_comissao_config.sql` (primeiro)
2. ✅ `002_add_comissao_config_id_to_prest_comissoes.sql` (depois)

O script 002 verifica se a tabela do script 001 existe antes de criar a foreign key.

## 🔄 Rollback

Se precisar reverter todas as alterações:

```bash
psql -U seu_usuario -d nome_do_banco -f sql/postgres/003_rollback_scripts.sql
```

## 📊 Estrutura Criada

### Tabela: `prest_comissao_config`

```
┌─────────────────────────────────────┐
│ prest_comissao_config               │
├─────────────────────────────────────┤
│ id (VARCHAR(36), PK)                │
│ usuario_id (VARCHAR(36), FK)        │
│ colaborador_id (VARCHAR(36), FK)    │
│ tipo_comissao (VARCHAR(20))         │
│ categoria_id (VARCHAR(36), FK, NULL)│
│ percentual (DECIMAL(5,2))           │
│ ativo (BOOLEAN)                     │
│ data_inicio (DATE, NULL)            │
│ data_fim (DATE, NULL)               │
│ observacoes (TEXT, NULL)            │
│ data_criacao (TIMESTAMP)            │
│ data_atualizacao (TIMESTAMP)        │
└─────────────────────────────────────┘
```

### Tabela: `prest_comissoes` (alterada)

Adiciona:
- `comissao_config_id (VARCHAR(36), FK, NULL)`

## ✅ Validações Incluídas

- ✅ `tipo_comissao` só aceita 'VENDA' ou 'COBRANCA'
- ✅ `percentual` deve estar entre 0 e 100
- ✅ `data_fim` deve ser >= `data_inicio` (se ambos preenchidos)
- ✅ Foreign keys com CASCADE apropriado

## 🔍 Verificações

Após executar os scripts, você pode verificar:

```sql
-- Verificar se a tabela foi criada
SELECT * FROM prest_comissao_config;

-- Verificar estrutura da tabela
\d prest_comissao_config

-- Verificar se a coluna foi adicionada
\d prest_comissoes

-- Verificar índices criados
SELECT indexname, indexdef 
FROM pg_indexes 
WHERE tablename IN ('prest_comissao_config', 'prest_comissoes');
```

## 📝 Notas

- Todos os scripts são idempotentes (podem ser executados múltiplas vezes)
- Usa `IF NOT EXISTS` e `IF EXISTS` para evitar erros
- Triggers criados automaticamente para atualizar timestamps
- Compatível com PostgreSQL 9.5+

## 🐛 Solução de Problemas

### Erro: "relation prest_comissao_config does not exist"
Execute primeiro o script `001_create_prest_comissao_config.sql`

### Erro: "relation prest_comissoes does not exist"
A tabela `prest_comissoes` deve existir antes de executar o script 002.

### Erro de foreign key
Verifique se as tabelas referenciadas existem:
- `prest_usuarios`
- `prest_colaboradores`
- `prest_categorias`

