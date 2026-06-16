# 🔍 Diagnóstico - Venda não entrou no Caixa

## 📋 Checklist de Verificação

Use este checklist para identificar o problema:

### **1. Verificar se há Caixa Aberto**

Execute no banco de dados:

```sql
SELECT 
    id,
    usuario_id,
    status,
    valor_inicial,
    data_abertura,
    data_fechamento
FROM prest_caixa
WHERE status = 'ABERTO'
ORDER BY data_abertura DESC;
```

✅ **Resultado Esperado:** Pelo menos 1 caixa com status = 'ABERTO'

❌ **Se não houver caixa aberto:**
- **Problema:** Não há caixa aberto para registrar a movimentação
- **Solução:** Abrir um caixa em `/caixa/caixa/create`
- **Comportamento esperado:** A venda é concluída, mas não registra no caixa (apenas log de warning)

---

### **2. Verificar se a Venda foi Venda Direta**

Execute no banco de dados (substitua `[VENDA_ID]` pelo ID da venda):

```sql
SELECT 
    id,
    usuario_id,
    cliente_id,
    status_venda_codigo,
    valor_total,
    forma_pagamento_id,
    observacoes,
    data_venda
FROM prest_vendas
WHERE id = '[VENDA_ID]';
```

✅ **Para ser venda direta:**
- `cliente_id` deve ser **NULL**
- `status_venda_codigo` deve ser **'QUITADA'**

❌ **Se `cliente_id` não for NULL:**
- **Problema:** A venda não foi identificada como venda direta
- **Causa:** A venda foi criada com cliente associado
- **Solução:** Verificar como a venda foi criada (via venda-direta deve ter cliente_id null)

---

### **3. Verificar Movimentações no Caixa**

Execute no banco de dados:

```sql
SELECT 
    cm.id,
    cm.caixa_id,
    cm.tipo,
    cm.categoria,
    cm.valor,
    cm.descricao,
    cm.venda_id,
    cm.data_movimento,
    c.status as caixa_status
FROM prest_caixa_movimentacoes cm
JOIN prest_caixa c ON c.id = cm.caixa_id
WHERE cm.venda_id IS NOT NULL
ORDER BY cm.data_movimento DESC
LIMIT 10;
```

✅ **Resultado Esperado:** Movimentações com `venda_id` preenchido

❌ **Se não houver movimentações:**
- **Problema:** Nenhuma movimentação foi criada
- **Próximo passo:** Verificar logs

---

### **4. Verificar Logs do Sistema**

Verifique os logs em `runtime/logs/app.log`:

```bash
# Buscar logs relacionados ao caixa
grep -i "caixa" runtime/logs/app.log | tail -30

# Buscar logs da venda específica (substitua [VENDA_ID])
grep -i "[VENDA_ID]" runtime/logs/app.log | tail -30

# Buscar warnings sobre caixa
grep -i "não foi possível registrar" runtime/logs/app.log | tail -20
```

✅ **Logs esperados:**
- `"✅ Entrada registrada no caixa para Venda ID [ID]"` - Sucesso
- `"⚠️ Não foi possível registrar entrada no caixa"` - Warning (sem caixa aberto)
- `"Tentativa de registrar venda sem caixa aberto"` - Warning do CaixaHelper

---

### **5. Verificar se a Integração foi Chamada**

No código, a integração só é chamada se:
1. `$isVendaDireta === true` (cliente_id é null)
2. A venda foi salva com sucesso
3. As parcelas foram geradas

Verifique no log se aparece:
```
"Tipo de Venda: VENDA DIRETA (QUITADA)"
```

Se aparecer "VENDA NORMAL (EM_ABERTO)", a venda não foi identificada como venda direta.

---

## 🔧 Soluções por Problema

### **Problema 1: Não há Caixa Aberto**

**Sintomas:**
- Venda foi concluída
- Nenhuma movimentação no caixa
- Log mostra: "Não foi possível registrar entrada no caixa"

**Solução:**
1. Abrir um caixa: `/caixa/caixa/create`
2. Registrar movimentação manualmente para a venda:
   - Acessar: `/caixa/movimentacao/create?caixa_id=[caixa_id]`
   - Tipo: ENTRADA
   - Categoria: VENDA
   - Valor: valor da venda
   - Descrição: "Venda #[venda_id]"
   - Associar à venda (campo venda_id)

---

### **Problema 2: Venda não foi identificada como Venda Direta**

**Sintomas:**
- `cliente_id` não é NULL na venda
- `status_venda_codigo` é 'EM_ABERTO' (não 'QUITADA')
- Log não mostra "VENDA DIRETA"

**Causa:**
- A venda foi criada com cliente associado
- Pode ter sido criada via catálogo ou prestanista (não venda-direta)

**Solução:**
- Verificar como a venda foi criada
- Se foi via venda-direta, verificar se o campo cliente foi enviado incorretamente

---

### **Problema 3: Erro na Integração**

**Sintomas:**
- Log mostra erro ao registrar no caixa
- Exceção capturada

**Solução:**
1. Verificar logs completos
2. Verificar se há erro de validação no model CaixaMovimentacao
3. Verificar se os dados estão corretos

---

## 🧪 Teste Rápido

Execute este teste para validar a integração:

### **Passo 1: Abrir Caixa**
```
1. Acessar: /caixa/caixa/create
2. Valor Inicial: R$ 100,00
3. Salvar
```

### **Passo 2: Verificar Caixa Aberto**
```sql
SELECT id, status FROM prest_caixa WHERE status = 'ABERTO';
```
Anotar o ID do caixa.

### **Passo 3: Realizar Venda Direta**
```
1. Acessar: /venda-direta
2. Adicionar produto
3. NÃO preencher cliente (deixar vazio)
4. Selecionar forma de pagamento
5. Finalizar venda
```

### **Passo 4: Verificar Movimentação**
```sql
SELECT * FROM prest_caixa_movimentacoes 
WHERE caixa_id = '[CAIXA_ID]' 
ORDER BY data_movimento DESC 
LIMIT 1;
```

---

## 📞 Informações para Diagnóstico

Para ajudar no diagnóstico, forneça:

1. **ID da Venda:** `[ID]`
2. **Data/Hora da Venda:** `[DATA]`
3. **Valor da Venda:** `R$ [VALOR]`
4. **Há caixa aberto?** `[SIM/NÃO]`
5. **Cliente ID na venda:** `[NULL ou ID]`
6. **Status da venda:** `[QUITADA/EM_ABERTO]`
7. **Logs encontrados:** `[COLE OS LOGS]`

---

## 🔍 Query de Diagnóstico Completa

Execute esta query para ver tudo de uma vez:

```sql
-- Substitua [VENDA_ID] pelo ID da venda
WITH venda_info AS (
    SELECT 
        id as venda_id,
        usuario_id,
        cliente_id,
        status_venda_codigo,
        valor_total,
        forma_pagamento_id,
        data_venda
    FROM prest_vendas
    WHERE id = '[VENDA_ID]'
),
caixa_info AS (
    SELECT 
        id as caixa_id,
        usuario_id,
        status,
        data_abertura
    FROM prest_caixa
    WHERE status = 'ABERTO'
    AND usuario_id = (SELECT usuario_id FROM venda_info)
    ORDER BY data_abertura DESC
    LIMIT 1
),
movimentacao_info AS (
    SELECT 
        id,
        caixa_id,
        venda_id,
        tipo,
        categoria,
        valor,
        data_movimento
    FROM prest_caixa_movimentacoes
    WHERE venda_id = '[VENDA_ID]'
)
SELECT 
    'VENDA' as tipo,
    v.venda_id::text as id,
    v.cliente_id::text as cliente_id,
    v.status_venda_codigo,
    v.valor_total,
    v.data_venda::text
FROM venda_info v
UNION ALL
SELECT 
    'CAIXA' as tipo,
    c.caixa_id::text,
    NULL,
    c.status,
    NULL,
    c.data_abertura::text
FROM caixa_info c
UNION ALL
SELECT 
    'MOVIMENTACAO' as tipo,
    m.id::text,
    m.caixa_id::text,
    m.tipo || '/' || m.categoria,
    m.valor,
    m.data_movimento::text
FROM movimentacao_info m;
```

Esta query mostra:
- Dados da venda
- Se há caixa aberto
- Se há movimentação criada

---

**Última atualização:** 2024-12-07

