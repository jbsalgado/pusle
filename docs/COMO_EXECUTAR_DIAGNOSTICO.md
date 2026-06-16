# 🚀 Como Executar o Script de Diagnóstico

## 📋 Pré-requisitos

- ✅ Acesso ao terminal/SSH do servidor
- ✅ PHP CLI instalado (já verificado: PHP 8.4.12)
- ✅ Script existe e tem permissão de execução

---

## 🔍 Passo a Passo Completo

### **Passo 1: Ir para o Diretório do Projeto**

```bash
cd /srv/http/pulse/basic
```

---

### **Passo 2: Obter o ID da Venda**

Você tem 3 opções:

#### **Opção A: Listar Últimas Vendas (Mais Fácil)** ⭐

```bash
php scripts/listar_ultimas_vendas.php
```

Este comando mostra as últimas 5 vendas com seus IDs. Exemplo de saída:

```
📋 ÚLTIMAS VENDAS
================================================================================

ID                                   | Valor        | Status     | Cliente ID     | Data
--------------------------------------------------------------------------------
a99a38a9-e368-4a47-a4bd-02ba3bacaa76 | R$ 150,00    | QUITADA    | NULL (Direta)  | 07/12/2024 15:30
b88b27b8-d257-3b36-93ac-01ba2aabbb65 | R$ 200,00    | EM_ABERTO  | c77c16c7-...   | 07/12/2024 14:20
...
```

**Copie o ID da venda que não entrou no caixa.**

---

#### **Opção B: Via Banco de Dados**

```sql
SELECT 
    id,
    valor_total,
    status_venda_codigo,
    cliente_id,
    data_venda
FROM prest_vendas
ORDER BY data_venda DESC
LIMIT 5;
```

---

#### **Opção C: Via Interface Web**

Se houver interface de listagem de vendas, copie o ID de lá.

---

### **Passo 3: Executar o Diagnóstico**

```bash
php scripts/diagnostico_venda_caixa.php [VENDA_ID]
```

**Exemplo:**
```bash
php scripts/diagnostico_venda_caixa.php a99a38a9-e368-4a47-a4bd-02ba3bacaa76
```

---

## 📊 Exemplo Completo de Execução

```bash
# 1. Ir para o diretório
cd /srv/http/pulse/basic

# 2. Listar últimas vendas
php scripts/listar_ultimas_vendas.php

# 3. Executar diagnóstico (usando o ID encontrado)
php scripts/diagnostico_venda_caixa.php a99a38a9-e368-4a47-a4bd-02ba3bacaa76
```

---

## 📋 Exemplo de Saída do Diagnóstico

```
🔍 DIAGNÓSTICO - VENDA NÃO ENTROU NO CAIXA
============================================================

1️⃣ VERIFICANDO VENDA...
✅ Venda encontrada:
   - ID: a99a38a9-e368-4a47-a4bd-02ba3bacaa76
   - Usuário ID: a99a38a9-e368-4a47-a4bd-02ba3bacaa76
   - Cliente ID: NULL
   - Status: QUITADA
   - Valor Total: R$ 150,00
   - Data: 2024-12-07 15:30:00
   - Forma Pagamento ID: xxx-xxx-xxx

2️⃣ VERIFICANDO TIPO DE VENDA...
✅ É VENDA DIRETA (cliente_id é NULL)

3️⃣ VERIFICANDO CAIXA ABERTO...
❌ NENHUM CAIXA ABERTO encontrado para o usuário!
   ⚠️  Esta é a causa mais provável do problema.
   💡 Solução: Abrir um caixa em /caixa/caixa/create

4️⃣ VERIFICANDO MOVIMENTAÇÃO...
❌ NENHUMA MOVIMENTAÇÃO encontrada para esta venda!

📊 RESUMO E DIAGNÓSTICO:
------------------------------------------------------------
❌ PROBLEMAS ENCONTRADOS:
   1. Não há caixa aberto. A movimentação não pode ser registrada sem caixa aberto.

💡 SUGESTÕES:
------------------------------------------------------------
1. Abrir um caixa: /caixa/caixa/create
2. Registrar movimentação manualmente para esta venda:
   - Acessar: /caixa/movimentacao/create?caixa_id=[caixa_id]
   - Tipo: ENTRADA
   - Categoria: VENDA
   - Valor: R$ 150,00
   - Descrição: Venda #a99a38a9
   - Venda ID: a99a38a9-e368-4a47-a4bd-02ba3bacaa76
```

---

## 🐛 Problemas Comuns e Soluções

### **Erro: "Uso: php scripts/diagnostico_venda_caixa.php [VENDA_ID]"**

**Causa:** Você não passou o ID da venda como parâmetro.

**Solução:**
```bash
# Primeiro, liste as vendas para obter o ID
php scripts/listar_ultimas_vendas.php

# Depois, execute com o ID
php scripts/diagnostico_venda_caixa.php [ID_COPIADO]
```

---

### **Erro: "Class not found" ou "Cannot find class"**

**Solução:**
```bash
cd /srv/http/pulse/basic
composer dump-autoload
php scripts/diagnostico_venda_caixa.php [VENDA_ID]
```

---

### **Erro: "Config file not found"**

**Solução:**
```bash
# Verificar se está no diretório correto
pwd
# Deve mostrar: /srv/http/pulse/basic

# Se não estiver, ir para o diretório
cd /srv/http/pulse/basic
```

---

### **Erro: "Permission denied"**

**Solução:**
```bash
chmod +x scripts/diagnostico_venda_caixa.php
chmod +x scripts/listar_ultimas_vendas.php
```

---

## 🚀 Comandos Rápidos (Copiar e Colar)

### **Comando Completo em Uma Linha:**

```bash
cd /srv/http/pulse/basic && php scripts/listar_ultimas_vendas.php && echo "Agora execute: php scripts/diagnostico_venda_caixa.php [ID]"
```

### **Executar Diagnóstico Direto (se você já tem o ID):**

```bash
cd /srv/http/pulse/basic && php scripts/diagnostico_venda_caixa.php [VENDA_ID]
```

---

## 📝 Checklist Rápido

Antes de executar:

- [ ] Está no diretório: `/srv/http/pulse/basic`
- [ ] Tem o ID da venda (ou vai listar primeiro)
- [ ] PHP CLI está funcionando (`php --version`)

---

## 💡 Dica: Criar Alias (Opcional)

Para facilitar, você pode criar um alias no seu `.bashrc`:

```bash
# Adicionar ao ~/.bashrc
alias diagnostico-venda='cd /srv/http/pulse/basic && php scripts/diagnostico_venda_caixa.php'
alias listar-vendas='cd /srv/http/pulse/basic && php scripts/listar_ultimas_vendas.php'

# Depois executar:
source ~/.bashrc

# Uso:
listar-vendas
diagnostico-venda [VENDA_ID]
```

---

## 🔍 Alternativa: Query SQL Direta

Se preferir usar SQL diretamente:

```sql
-- Verificar venda
SELECT id, cliente_id, status_venda_codigo, valor_total 
FROM prest_vendas 
WHERE id = '[VENDA_ID]';

-- Verificar caixa aberto
SELECT id, status, valor_inicial 
FROM prest_caixa 
WHERE status = 'ABERTO';

-- Verificar movimentação
SELECT * 
FROM prest_caixa_movimentacoes 
WHERE venda_id = '[VENDA_ID]';
```

---

**Última atualização:** 2024-12-08
