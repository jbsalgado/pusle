# 🚀 Como Executar as Migrations de Caixa e Contas a Pagar

## 📋 Arquivos SQL Criados

1. `sql/postgres/009_create_caixa_tables.sql` - Tabelas de Fluxo de Caixa
2. `sql/postgres/010_create_contas_pagar_table.sql` - Tabela de Contas a Pagar

---

## ✅ Opções de Execução

### **Opção 1: Via Terminal (Recomendado)**

Execute diretamente do terminal usando `psql`:

```bash
# Ajuste os parâmetros conforme sua configuração
psql -U seu_usuario -d nome_do_banco -f sql/postgres/009_create_caixa_tables.sql
psql -U seu_usuario -d nome_do_banco -f sql/postgres/010_create_contas_pagar_table.sql
```

**Exemplo com configuração comum:**
```bash
psql -U postgres -d pulse_db -f sql/postgres/009_create_caixa_tables.sql
psql -U postgres -d pulse_db -f sql/postgres/010_create_contas_pagar_table.sql
```

**Ou executar ambos de uma vez:**
```bash
psql -U postgres -d pulse_db -f sql/postgres/009_create_caixa_tables.sql -f sql/postgres/010_create_contas_pagar_table.sql
```

---

### **Opção 2: Via psql Interativo**

Conecte-se ao PostgreSQL e execute os comandos `\i`:

```bash
# 1. Conecte ao banco
psql -U seu_usuario -d nome_do_banco

# 2. Dentro do psql, execute:
\i sql/postgres/009_create_caixa_tables.sql
\i sql/postgres/010_create_contas_pagar_table.sql

# 3. Saia do psql
\q
```

**⚠️ IMPORTANTE:** Os caminhos no `\i` são relativos ao diretório atual onde você executou o `psql`. 

**Se estiver no diretório `/srv/http/pulse/basic`:**
```bash
cd /srv/http/pulse/basic
psql -U postgres -d pulse_db
# Dentro do psql:
\i sql/postgres/009_create_caixa_tables.sql
\i sql/postgres/010_create_contas_pagar_table.sql
```

---

### **Opção 3: Via Caminho Absoluto**

Se preferir usar caminho absoluto:

```bash
psql -U postgres -d pulse_db -f /srv/http/pulse/basic/sql/postgres/009_create_caixa_tables.sql
psql -U postgres -d pulse_db -f /srv/http/pulse/basic/sql/postgres/010_create_contas_pagar_table.sql
```

---

## 🔍 Verificar Configuração do Banco

Para descobrir o nome do banco e usuário, verifique o arquivo `config/db.php`:

```bash
cat config/db.php
```

Ou execute:
```bash
grep -E "dsn|username" config/db.php
```

---

## ✅ Verificar se as Tabelas Foram Criadas

Após executar as migrations, verifique se as tabelas foram criadas:

```bash
psql -U postgres -d pulse_db -c "\dt prest_caixa*"
psql -U postgres -d pulse_db -c "\dt prest_contas_pagar"
```

Ou dentro do psql:
```sql
\dt prest_caixa*
\dt prest_contas_pagar
```

---

## ⚠️ Observações Importantes

1. **Ordem de Execução:** 
   - Primeiro execute `009_create_caixa_tables.sql`
   - Depois execute `010_create_contas_pagar_table.sql`
   - A ordem não é crítica neste caso, mas é recomendada

2. **Permissões:**
   - Certifique-se de ter permissões para criar tabelas no banco
   - O usuário precisa ter privilégios `CREATE` no schema `public`

3. **Backup:**
   - Recomenda-se fazer backup do banco antes de executar migrations em produção

4. **Erros:**
   - Se houver erro de "tabela já existe", isso é normal se executar novamente (usa `CREATE TABLE IF NOT EXISTS`)
   - Se houver erro de foreign key, verifique se as tabelas referenciadas existem

---

## 🧪 Teste Rápido

Após executar, teste criando um registro:

```sql
-- Teste Caixa
INSERT INTO prest_caixa (usuario_id, valor_inicial, status) 
VALUES ('seu-usuario-id-aqui', 100.00, 'ABERTO');

-- Teste Conta a Pagar
INSERT INTO prest_contas_pagar (usuario_id, descricao, valor, data_vencimento, status) 
VALUES ('seu-usuario-id-aqui', 'Teste', 50.00, '2025-12-31', 'PENDENTE');
```

---

## 📝 Resumo dos Comandos

**Forma mais simples (recomendada):**
```bash
cd /srv/http/pulse/basic
psql -U postgres -d pulse_db -f sql/postgres/009_create_caixa_tables.sql
psql -U postgres -d pulse_db -f sql/postgres/010_create_contas_pagar_table.sql
```

**Substitua:**
- `postgres` pelo seu usuário do PostgreSQL
- `pulse_db` pelo nome do seu banco de dados

