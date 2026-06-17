# 🚀 Próximos Passos - Fluxo de Caixa

## 📊 Status Atual

### ✅ **Já Implementado:**
- ✅ Estrutura de dados completa (tabelas, models, controllers, views)
- ✅ **Integração Venda-Direta → Caixa** (funcionando)
- ✅ Validações de caixa único por loja
- ✅ Fechamento automático de caixas do dia anterior
- ✅ Tratamento de vendas com caixa fechado ou do dia anterior
- ✅ Cálculo automático de valor esperado
- ✅ Fechamento de caixa com conferência

### ⚠️ **Pendente (Prioritário):**
- ❌ **Integração Parcelas → Caixa** (quando parcela é paga)
- ❌ **Integração Contas a Pagar → Caixa** (quando conta é paga)
- ❌ Relatórios e dashboards
- ❌ Funcionalidades avançadas

---

## 🎯 Próximos Passos (Ordem de Prioridade)

### **1. Integração Parcelas → Caixa** ⭐⭐⭐ ALTA PRIORIDADE

**Descrição:** Registrar automaticamente entrada no caixa quando uma parcela é paga.

**Onde implementar:**
- `modules/api/controllers/CobrancaController.php` → `actionRegistrarAcao()` (quando `tipo_acao = PAGAMENTO`)
- `modules/vendas/models/Parcela.php` → `registrarPagamento()` (método existente)
- Possivelmente em webhooks de gateways (Mercado Pago, Asaas)

**O que fazer:**
1. Adicionar método `registrarEntradaParcela()` no `CaixaHelper`
2. Integrar no `CobrancaController` quando parcela é marcada como paga
3. Integrar no método `Parcela::registrarPagamento()` (se usado diretamente)
4. Testar com pagamento de parcela via cobrança
5. Testar com pagamento via gateway (webhook)

**Arquivos a modificar:**
- `modules/caixa/helpers/CaixaHelper.php` (adicionar método)
- `modules/api/controllers/CobrancaController.php` (integrar após marcar parcela como paga)
- `modules/vendas/models/Parcela.php` (integrar no método `registrarPagamento()`)

**Código exemplo:**
```php
// Em CaixaHelper.php
public static function registrarEntradaParcela($parcelaId, $valor, $formaPagamentoId = null, $usuarioId = null)
{
    try {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        if (!$usuarioId) {
            Yii::warning("Tentativa de registrar parcela no caixa sem usuário identificado", 'caixa');
            return false;
        }
        
        // Busca caixa aberto do dia atual
        $caixa = self::getCaixaAberto($usuarioId);
        
        if (!$caixa) {
            Yii::warning("⚠️ PARCELA PAGA COM CAIXA FECHADO. Parcela ID: {$parcelaId}, Usuário ID: {$usuarioId}, Valor: R$ {$valor}. A parcela foi marcada como paga, mas não foi registrada no caixa.", 'caixa');
            return false;
        }
        
        // Verifica se já existe movimentação para esta parcela (evita duplicação)
        $movimentacaoExistente = CaixaMovimentacao::find()
            ->where(['parcela_id' => $parcelaId])
            ->one();
        
        if ($movimentacaoExistente) {
            Yii::warning("Movimentação já existe para parcela {$parcelaId}. Evitando duplicação.", 'caixa');
            return $movimentacaoExistente;
        }
        
        // Cria a movimentação
        $movimentacao = new CaixaMovimentacao();
        $movimentacao->caixa_id = $caixa->id;
        $movimentacao->tipo = CaixaMovimentacao::TIPO_ENTRADA;
        $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_PAGAMENTO;
        $movimentacao->valor = $valor;
        $movimentacao->descricao = "Pagamento de parcela #" . substr($parcelaId, 0, 8);
        $movimentacao->parcela_id = $parcelaId;
        $movimentacao->forma_pagamento_id = $formaPagamentoId;
        $movimentacao->data_movimento = date('Y-m-d H:i:s');
        
        if (!$movimentacao->save()) {
            $erros = $movimentacao->getFirstErrors();
            Yii::error("Erro ao registrar movimentação de parcela no caixa: " . implode(', ', $erros), 'caixa');
            return false;
        }
        
        Yii::info("✅ Movimentação registrada no caixa: Parcela #{$parcelaId}, Valor: R$ {$valor}, Caixa: {$caixa->id}", 'caixa');
        
        return $movimentacao;
        
    } catch (\Exception $e) {
        Yii::error("Exceção ao registrar entrada de parcela no caixa: " . $e->getMessage(), 'caixa');
        return false;
    }
}
```

**Pontos de integração:**
1. **CobrancaController** (linha ~85-94): Após marcar parcela como paga
2. **Parcela::registrarPagamento()**: No final do método, antes de retornar
3. **Webhooks de gateways**: Quando pagamento é confirmado

**Estimativa:** 2-3 dias

**Benefício:** Controle automático de recebimentos de parcelas

---

### **2. Integração Contas a Pagar → Caixa** ⭐⭐ MÉDIA PRIORIDADE

**Descrição:** Registrar automaticamente saída no caixa quando uma conta a pagar é paga.

**Onde implementar:**
- `modules/contas-pagar/controllers/ContaPagarController.php` → `actionPagar()`

**O que fazer:**
1. Adicionar método `registrarSaidaContaPagar()` no `CaixaHelper`
2. Integrar no `ContaPagarController::actionPagar()`
3. Validar saldo suficiente antes de registrar
4. Testar com pagamento de conta

**Arquivos a modificar:**
- `modules/caixa/helpers/CaixaHelper.php` (adicionar método)
- `modules/contas-pagar/controllers/ContaPagarController.php` (integrar após marcar conta como paga)

**Código exemplo:**
```php
// Em CaixaHelper.php
public static function registrarSaidaContaPagar($contaPagarId, $valor, $formaPagamentoId = null, $usuarioId = null)
{
    try {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        if (!$usuarioId) {
            Yii::warning("Tentativa de registrar conta a pagar no caixa sem usuário identificado", 'caixa');
            return false;
        }
        
        // Busca caixa aberto do dia atual
        $caixa = self::getCaixaAberto($usuarioId);
        
        if (!$caixa) {
            Yii::warning("⚠️ CONTA A PAGAR PAGA COM CAIXA FECHADO. Conta ID: {$contaPagarId}, Usuário ID: {$usuarioId}, Valor: R$ {$valor}. A conta foi marcada como paga, mas não foi registrada no caixa.", 'caixa');
            return false;
        }
        
        // Valida saldo suficiente
        if (!self::verificarSaldoSuficiente($caixa->id, $valor)) {
            Yii::warning("⚠️ SALDO INSUFICIENTE NO CAIXA. Conta ID: {$contaPagarId}, Valor necessário: R$ {$valor}, Saldo atual: R$ " . $caixa->calcularValorEsperado(), 'caixa');
            // Não bloqueia o pagamento, apenas registra aviso
        }
        
        // Verifica se já existe movimentação para esta conta (evita duplicação)
        $movimentacaoExistente = CaixaMovimentacao::find()
            ->where(['conta_pagar_id' => $contaPagarId])
            ->one();
        
        if ($movimentacaoExistente) {
            Yii::warning("Movimentação já existe para conta a pagar {$contaPagarId}. Evitando duplicação.", 'caixa');
            return $movimentacaoExistente;
        }
        
        // Cria a movimentação
        $movimentacao = new CaixaMovimentacao();
        $movimentacao->caixa_id = $caixa->id;
        $movimentacao->tipo = CaixaMovimentacao::TIPO_SAIDA;
        $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_CONTA_PAGAR;
        $movimentacao->valor = $valor;
        $movimentacao->descricao = "Pagamento de conta a pagar #" . substr($contaPagarId, 0, 8);
        $movimentacao->conta_pagar_id = $contaPagarId;
        $movimentacao->forma_pagamento_id = $formaPagamentoId;
        $movimentacao->data_movimento = date('Y-m-d H:i:s');
        
        if (!$movimentacao->save()) {
            $erros = $movimentacao->getFirstErrors();
            Yii::error("Erro ao registrar movimentação de conta a pagar no caixa: " . implode(', ', $erros), 'caixa');
            return false;
        }
        
        Yii::info("✅ Movimentação registrada no caixa: Conta a Pagar #{$contaPagarId}, Valor: R$ {$valor}, Caixa: {$caixa->id}", 'caixa');
        
        return $movimentacao;
        
    } catch (\Exception $e) {
        Yii::error("Exceção ao registrar saída de conta a pagar no caixa: " . $e->getMessage(), 'caixa');
        return false;
    }
}
```

**Estimativa:** 1-2 dias

**Benefício:** Controle completo de entradas e saídas

---

### **3. Relatórios Básicos** ⭐ MÉDIA PRIORIDADE

**Descrição:** Criar relatórios de movimentações e fechamento de caixa.

**O que fazer:**
1. Relatório de movimentações por período
2. Relatório de fechamento de caixa (PDF)
3. Dashboard com resumo de caixas
4. Gráficos de entradas/saídas

**Arquivos a criar:**
- `modules/caixa/controllers/RelatorioController.php`
- `modules/caixa/views/relatorio/` (views de relatórios)

**Estimativa:** 3-4 dias

**Benefício:** Melhor visualização e análise dos dados

---

### **4. Integração com Gateways de Pagamento** ⭐⭐ ALTA PRIORIDADE (Futuro)

**Descrição:** Integrar registro automático quando pagamentos via gateway são confirmados.

**O que fazer:**
- Modificar webhooks do Asaas para registrar no caixa
- Modificar webhooks do Mercado Pago para registrar no caixa
- Associar movimentações a transações de gateway

**Arquivos a modificar:**
- Controllers de webhook (Asaas, Mercado Pago)
- Usar `CaixaHelper::registrarEntradaParcela()`

**Estimativa:** 2-3 dias (após implementar webhooks)

**Benefício:** Integração completa com pagamentos online

---

## 📋 Plano de Ação Recomendado

### **Fase 1: Integrações Básicas (3-5 dias)**
1. **Dia 1-2:** Implementar `registrarEntradaParcela()` e integrar com `CobrancaController`
2. **Dia 3:** Integrar com `Parcela::registrarPagamento()` e testar
3. **Dia 4:** Implementar `registrarSaidaContaPagar()` e integrar com `ContaPagarController`
4. **Dia 5:** Testes completos e ajustes

### **Fase 2: Relatórios (3-4 dias)**
1. **Dia 1-2:** Criar relatórios básicos
2. **Dia 3:** Dashboard de caixa
3. **Dia 4:** Exportação de relatórios (PDF, Excel)

### **Fase 3: Integrações Avançadas (2-3 dias)**
1. **Dia 1-2:** Integrar webhooks de gateways
2. **Dia 3:** Testes e ajustes

---

## 🎯 Priorização Sugerida

**Ordem de implementação:**
1. ⭐⭐⭐ **Integração Parcelas → Caixa** (mais importante - recebimentos frequentes)
2. ⭐⭐ **Integração Contas a Pagar → Caixa** (completa o ciclo)
3. ⭐ **Relatórios Básicos** (análise e visualização)
4. ⭐⭐ **Integração Gateways** (quando webhooks estiverem prontos)

---

## ✅ Checklist de Implementação

### **Integração Parcelas → Caixa**
- [ ] Adicionar método `registrarEntradaParcela()` no `CaixaHelper`
- [ ] Integrar no `CobrancaController::actionRegistrarAcao()` (quando parcela é paga)
- [ ] Integrar no `Parcela::registrarPagamento()` (se usado diretamente)
- [ ] Adicionar verificação de duplicação (evitar registrar duas vezes)
- [ ] Testar com pagamento via cobrança
- [ ] Testar com pagamento via gateway (quando disponível)
- [ ] Validar movimentação criada corretamente

### **Integração Contas a Pagar → Caixa**
- [ ] Adicionar método `registrarSaidaContaPagar()` no `CaixaHelper`
- [ ] Integrar no `ContaPagarController::actionPagar()`
- [ ] Adicionar validação de saldo suficiente (aviso, não bloqueia)
- [ ] Adicionar verificação de duplicação
- [ ] Testar com pagamento de conta
- [ ] Validar movimentação criada corretamente

---

## 💡 Decisões Técnicas Importantes

### **1. Evitar Duplicação**
- Sempre verificar se já existe movimentação para a mesma parcela/conta antes de criar nova
- Usar `parcela_id` ou `conta_pagar_id` para verificação

### **2. O que fazer se não houver caixa aberto?**
- **Decisão:** Não registrar (apenas log de aviso)
- A parcela/conta é marcada como paga normalmente
- A movimentação pode ser registrada manualmente depois

### **3. Validação de Saldo (Contas a Pagar)**
- **Decisão:** Verificar saldo, mas não bloquear pagamento
- Registrar aviso no log se saldo insuficiente
- Permitir que o usuário pague mesmo com saldo negativo (pode ser transferência)

---

## 🚀 Começar Agora

**Recomendação:** Começar pela **Integração Parcelas → Caixa** porque:
1. É a funcionalidade mais usada (recebimentos frequentes)
2. Tem maior impacto na usabilidade
3. Serve de base para a integração de contas a pagar
4. Valida toda a estrutura criada

**Próximo passo:** Adicionar método `registrarEntradaParcela()` no `CaixaHelper` e integrar com `CobrancaController`.

---

**Data de Atualização:** 2024-12-08
**Status:** ✅ Pronto para implementação

