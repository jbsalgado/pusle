# 🧪 Documento de Teste - Integração Venda-Direta → Caixa

## 📋 Objetivo

Validar que vendas diretas realizadas na PWA `venda-direta` são automaticamente registradas como entradas no caixa quando há um caixa aberto.

---

## ✅ Pré-requisitos

### **1. Banco de Dados**
- ✅ Tabelas `prest_caixa` e `prest_caixa_movimentacoes` criadas
- ✅ Migrations executadas (`009_create_caixa_tables.sql`)

### **2. Usuário e Autenticação**
- ✅ Usuário logado no sistema
- ✅ Acesso à PWA venda-direta (`/venda-direta`)

### **3. Dados de Teste**
- ✅ Pelo menos 1 produto cadastrado
- ✅ Pelo menos 1 forma de pagamento cadastrada
- ✅ (Opcional) Colaborador vendedor cadastrado

---

## 🧪 Cenários de Teste

### **Cenário 1: Venda Direta com Caixa Aberto** ✅

**Objetivo:** Validar que a venda é registrada no caixa quando há caixa aberto.

#### **Passo a Passo:**

1. **Abrir um Caixa**
   - Acessar: `/caixa/caixa/create`
   - Preencher:
     - Valor Inicial: `R$ 100,00`
     - Colaborador: (opcional)
     - Observações: "Caixa de teste"
   - Clicar em "Abrir Caixa"
   - ✅ **Resultado Esperado:** Caixa criado com status ABERTO

2. **Verificar Caixa Aberto**
   - Acessar: `/caixa/caixa/index`
   - ✅ **Resultado Esperado:** Ver caixa aberto na lista
   - Anotar o ID do caixa (ou acessar via view)

3. **Verificar Saldo Inicial**
   - Acessar: `/caixa/caixa/view?id=[caixa_id]`
   - ✅ **Resultado Esperado:** 
     - Valor Inicial: R$ 100,00
     - Valor Esperado: R$ 100,00
     - Nenhuma movimentação registrada

4. **Realizar Venda Direta**
   - Acessar: `/venda-direta`
   - Adicionar produtos ao carrinho
   - Preencher dados da venda:
     - Cliente: (deixar vazio - venda direta)
     - Forma de Pagamento: Selecionar uma forma
     - Vendedor: (opcional)
   - Finalizar a venda
   - ✅ **Resultado Esperado:** 
     - Venda finalizada com sucesso
     - Mensagem de sucesso exibida

5. **Verificar Movimentação no Caixa**
   - Acessar: `/caixa/caixa/view?id=[caixa_id]`
   - ✅ **Resultado Esperado:**
     - Nova movimentação na lista
     - Tipo: **ENTRADA**
     - Categoria: **VENDA**
     - Valor: igual ao valor total da venda
     - Descrição: "Venda #[ID]"
     - Data/Hora: data e hora atual
     - Valor Esperado atualizado: R$ 100,00 + valor da venda

6. **Verificar Detalhes da Movimentação**
   - Clicar na movimentação ou verificar na tabela
   - ✅ **Resultado Esperado:**
     - `venda_id` preenchido com o ID da venda
     - `forma_pagamento_id` preenchido (se informado)
     - `tipo` = "ENTRADA"
     - `categoria` = "VENDA"

---

### **Cenário 2: Venda Direta sem Caixa Aberto** ⚠️

**Objetivo:** Validar que a venda é concluída mesmo sem caixa aberto (não deve falhar).

#### **Passo a Passo:**

1. **Verificar que não há caixa aberto**
   - Acessar: `/caixa/caixa/index`
   - ✅ **Resultado Esperado:** Nenhum caixa com status ABERTO

2. **Realizar Venda Direta**
   - Acessar: `/venda-direta`
   - Adicionar produtos e finalizar venda
   - ✅ **Resultado Esperado:** 
     - Venda finalizada com sucesso
     - **NÃO deve dar erro** por falta de caixa

3. **Verificar Logs (Opcional)**
   - Verificar logs do sistema
   - ✅ **Resultado Esperado:**
     - Log de warning: "Não foi possível registrar entrada no caixa (caixa pode não estar aberto)"
     - Venda salva normalmente no banco

4. **Abrir Caixa e Registrar Manualmente (Opcional)**
   - Abrir um caixa
   - Acessar: `/caixa/movimentacao/create?caixa_id=[caixa_id]`
   - Registrar movimentação manualmente referenciando a venda
   - ✅ **Resultado Esperado:** Movimentação registrada manualmente

---

### **Cenário 3: Múltiplas Vendas Diretas** 🔄

**Objetivo:** Validar que múltiplas vendas são registradas corretamente.

#### **Passo a Passo:**

1. **Abrir Caixa**
   - Abrir caixa com valor inicial: R$ 200,00

2. **Realizar 3 Vendas Diretas**
   - Venda 1: R$ 50,00
   - Venda 2: R$ 75,00
   - Venda 3: R$ 100,00

3. **Verificar Caixa**
   - Acessar: `/caixa/caixa/view?id=[caixa_id]`
   - ✅ **Resultado Esperado:**
     - 3 movimentações registradas
     - Valor Esperado: R$ 200,00 + R$ 50,00 + R$ 75,00 + R$ 100,00 = **R$ 425,00**
     - Todas as movimentações com tipo ENTRADA e categoria VENDA

---

### **Cenário 4: Venda Direta com Forma de Pagamento** 💳

**Objetivo:** Validar que a forma de pagamento é associada à movimentação.

#### **Passo a Passo:**

1. **Abrir Caixa**
   - Abrir caixa normalmente

2. **Realizar Venda com Forma de Pagamento Específica**
   - Na venda direta, selecionar uma forma de pagamento (ex: "Dinheiro", "PIX", "Cartão")
   - Finalizar venda

3. **Verificar Movimentação**
   - Acessar: `/caixa/caixa/view?id=[caixa_id]`
   - Verificar a movimentação criada
   - ✅ **Resultado Esperado:**
     - `forma_pagamento_id` preenchido
     - Forma de pagamento visível na movimentação (se implementado na view)

---

### **Cenário 5: Venda Direta com Vendedor** 👤

**Objetivo:** Validar que vendas com vendedor são registradas corretamente.

#### **Passo a Passo:**

1. **Abrir Caixa**
   - Abrir caixa normalmente

2. **Realizar Venda com Vendedor**
   - Na venda direta, selecionar um colaborador vendedor
   - Finalizar venda

3. **Verificar Movimentação**
   - Acessar: `/caixa/caixa/view?id=[caixa_id]`
   - ✅ **Resultado Esperado:**
     - Movimentação registrada normalmente
     - Vendedor não precisa estar na movimentação (está na venda)

---

## 🔍 Validações Técnicas

### **1. Verificar Banco de Dados**

Execute a query para verificar movimentações:

```sql
SELECT 
    cm.id,
    cm.tipo,
    cm.categoria,
    cm.valor,
    cm.descricao,
    cm.venda_id,
    cm.forma_pagamento_id,
    cm.data_movimento,
    c.status as caixa_status
FROM prest_caixa_movimentacoes cm
JOIN prest_caixa c ON c.id = cm.caixa_id
WHERE cm.venda_id IS NOT NULL
ORDER BY cm.data_movimento DESC
LIMIT 10;
```

✅ **Resultado Esperado:** Movimentações com `venda_id` preenchido

---

### **2. Verificar Logs do Sistema**

Verifique os logs em `runtime/logs/app.log`:

```bash
grep -i "caixa" runtime/logs/app.log | tail -20
```

✅ **Resultado Esperado:**
- Logs de sucesso: "Entrada registrada no caixa"
- Logs de warning (se sem caixa): "Não foi possível registrar entrada no caixa"

---

### **3. Verificar Transação**

A movimentação deve ser criada dentro da mesma transação da venda:

✅ **Resultado Esperado:**
- Se a venda falhar, a movimentação não deve ser criada
- Se a movimentação falhar, a venda ainda deve ser concluída (não crítico)

---

## 📊 Checklist de Teste

### **Funcionalidades Básicas**
- [ ] Venda direta é finalizada com sucesso
- [ ] Movimentação é criada quando há caixa aberto
- [ ] Movimentação tem tipo ENTRADA
- [ ] Movimentação tem categoria VENDA
- [ ] Valor da movimentação = valor total da venda
- [ ] `venda_id` está preenchido
- [ ] `forma_pagamento_id` está preenchido (se informado)
- [ ] Valor esperado do caixa é atualizado corretamente

### **Tratamento de Erros**
- [ ] Venda é concluída mesmo sem caixa aberto
- [ ] Log de warning é gerado quando não há caixa
- [ ] Erro no caixa não quebra a venda
- [ ] Múltiplas vendas são registradas corretamente

### **Interface**
- [ ] Movimentações aparecem na view do caixa
- [ ] Dados da movimentação estão corretos
- [ ] Data/hora está correta
- [ ] Descrição está clara

---

## 🐛 Problemas Conhecidos e Soluções

### **Problema 1: Movimentação não aparece**

**Possíveis causas:**
- Caixa não está aberto
- Venda não foi finalizada corretamente
- Erro na transação

**Solução:**
1. Verificar se há caixa aberto: `/caixa/caixa/index`
2. Verificar logs do sistema
3. Verificar se a venda foi salva: consultar tabela `prest_vendas`

---

### **Problema 2: Valor incorreto na movimentação**

**Possíveis causas:**
- Valor da venda calculado incorretamente
- Problema na integração

**Solução:**
1. Verificar valor total da venda na tabela `prest_vendas`
2. Comparar com valor da movimentação
3. Verificar logs para erros

---

### **Problema 3: Múltiplas movimentações para mesma venda**

**Possíveis causas:**
- Venda finalizada múltiplas vezes
- Problema na lógica de integração

**Solução:**
1. Verificar se a venda foi criada apenas uma vez
2. Verificar se há validação para evitar duplicatas
3. Implementar validação se necessário

---

## 📝 Relatório de Teste

### **Template de Relatório:**

```
Data do Teste: [DATA]
Testador: [NOME]
Ambiente: [PRODUÇÃO/DEV]

Cenários Testados:
- [ ] Cenário 1: Venda com caixa aberto
- [ ] Cenário 2: Venda sem caixa aberto
- [ ] Cenário 3: Múltiplas vendas
- [ ] Cenário 4: Venda com forma de pagamento
- [ ] Cenário 5: Venda com vendedor

Resultados:
- Total de testes: [X]
- Sucessos: [X]
- Falhas: [X]

Observações:
[ANOTAÇÕES]

Problemas Encontrados:
[LISTA DE PROBLEMAS]
```

---

## 🎯 Critérios de Aceitação

A integração é considerada **APROVADA** se:

1. ✅ Vendas diretas são registradas no caixa quando há caixa aberto
2. ✅ Vendas diretas são concluídas mesmo sem caixa aberto (não falha)
3. ✅ Movimentações têm todos os dados corretos
4. ✅ Valor esperado do caixa é atualizado corretamente
5. ✅ Múltiplas vendas são registradas sem problemas
6. ✅ Logs são gerados corretamente
7. ✅ Interface mostra as movimentações corretamente

---

## 🚀 Próximos Passos Após Teste

Se os testes forem aprovados:

1. ✅ Marcar integração como concluída
2. ✅ Documentar comportamento para usuários
3. ✅ Considerar implementar notificação quando não há caixa aberto
4. ✅ Planejar integração com pagamento de parcelas

---

## 📞 Suporte

Em caso de problemas durante os testes:

1. Verificar logs: `runtime/logs/app.log`
2. Verificar banco de dados: consultar tabelas diretamente
3. Verificar código: `modules/caixa/helpers/CaixaHelper.php`
4. Verificar integração: `modules/api/controllers/PedidoController.php`

---

**Última atualização:** 2024-12-07
**Versão:** 1.0

