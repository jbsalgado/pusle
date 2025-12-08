# 🚀 Próximas Implementações - Fluxo de Caixa

## 📊 Status Atual

### ✅ **Já Implementado:**
- ✅ Estrutura de dados (tabelas `prest_caixa` e `prest_caixa_movimentacoes`)
- ✅ Models completos (`Caixa` e `CaixaMovimentacao`)
- ✅ Controllers funcionais (CRUD completo)
- ✅ Views básicas (index, view, create, update, _form)
- ✅ Layout do módulo
- ✅ Acessos no dashboard de vendas
- ✅ Cálculo automático de valor esperado
- ✅ Fechamento de caixa com conferência

### ⚠️ **Pendente:**
- ❌ Integrações automáticas com vendas
- ❌ Integrações automáticas com pagamentos
- ❌ Relatórios e dashboards
- ❌ Funcionalidades avançadas
- ❌ Validações de múltiplos caixas

---

## 🎯 Próximas Implementações (Ordem de Prioridade)

### **1. Integração Automática - Vendas → Caixa** ⭐⭐⭐ ALTA PRIORIDADE

**Descrição:** Registrar automaticamente entrada no caixa quando uma venda é finalizada.

**O que fazer:**
- Criar helper/service `CaixaHelper` ou `CaixaService`
- Modificar controller de vendas para registrar movimentação após finalização
- Verificar se há caixa aberto antes de registrar
- Criar movimentação do tipo ENTRADA, categoria VENDA
- Associar movimentação à venda (`venda_id`)

**Arquivos a modificar:**
- `modules/vendas/controllers/VendaDiretaController.php` (ou controller que finaliza vendas)
- Criar `modules/caixa/helpers/CaixaHelper.php` ou `modules/caixa/services/CaixaService.php`

**Código exemplo:**
```php
// Em CaixaHelper.php
public static function registrarEntradaVenda($vendaId, $valor, $formaPagamentoId = null)
{
    // Busca caixa aberto do usuário
    $caixa = Caixa::find()
        ->where(['usuario_id' => Yii::$app->user->id, 'status' => Caixa::STATUS_ABERTO])
        ->one();
    
    if (!$caixa) {
        Yii::warning("Tentativa de registrar venda sem caixa aberto", 'caixa');
        return false;
    }
    
    $movimentacao = new CaixaMovimentacao();
    $movimentacao->caixa_id = $caixa->id;
    $movimentacao->tipo = CaixaMovimentacao::TIPO_ENTRADA;
    $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_VENDA;
    $movimentacao->valor = $valor;
    $movimentacao->descricao = "Venda #" . substr($vendaId, 0, 8);
    $movimentacao->venda_id = $vendaId;
    $movimentacao->forma_pagamento_id = $formaPagamentoId;
    
    return $movimentacao->save();
}
```

**Estimativa:** 2-3 dias

**Benefício:** Sistema totalmente integrado, sem necessidade de registro manual

---

### **2. Integração Automática - Pagamento de Parcelas → Caixa** ⭐⭐⭐ ALTA PRIORIDADE

**Descrição:** Registrar automaticamente entrada no caixa quando uma parcela é paga.

**O que fazer:**
- Modificar `ParcelaController::actionPagar()` ou método que marca parcela como paga
- Verificar se há caixa aberto
- Criar movimentação do tipo ENTRADA, categoria PAGAMENTO
- Associar movimentação à parcela (`parcela_id`)

**Arquivos a modificar:**
- `modules/vendas/controllers/ParcelaController.php`
- Usar o mesmo `CaixaHelper` criado acima

**Código exemplo:**
```php
// Em CaixaHelper.php
public static function registrarEntradaParcela($parcelaId, $valor, $formaPagamentoId = null)
{
    $caixa = Caixa::find()
        ->where(['usuario_id' => Yii::$app->user->id, 'status' => Caixa::STATUS_ABERTO])
        ->one();
    
    if (!$caixa) {
        return false; // Ou criar caixa automaticamente?
    }
    
    $movimentacao = new CaixaMovimentacao();
    $movimentacao->caixa_id = $caixa->id;
    $movimentacao->tipo = CaixaMovimentacao::TIPO_ENTRADA;
    $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_PAGAMENTO;
    $movimentacao->valor = $valor;
    $movimentacao->descricao = "Pagamento de parcela #" . substr($parcelaId, 0, 8);
    $movimentacao->parcela_id = $parcelaId;
    $movimentacao->forma_pagamento_id = $formaPagamentoId;
    
    return $movimentacao->save();
}
```

**Estimativa:** 1-2 dias

**Benefício:** Controle automático de recebimentos

---

### **3. Integração Automática - Contas a Pagar → Caixa** ⭐⭐ MÉDIA PRIORIDADE

**Descrição:** Registrar automaticamente saída no caixa quando uma conta a pagar é paga.

**O que fazer:**
- Modificar `ContaPagarController::actionPagar()`
- Criar movimentação do tipo SAIDA, categoria CONTA_PAGAR
- Associar movimentação à conta (`conta_pagar_id`)

**Arquivos a modificar:**
- `modules/contas-pagar/controllers/ContaPagarController.php`
- Usar `CaixaHelper`

**Código exemplo:**
```php
// Em CaixaHelper.php
public static function registrarSaidaContaPagar($contaPagarId, $valor, $formaPagamentoId = null)
{
    $caixa = Caixa::find()
        ->where(['usuario_id' => Yii::$app->user->id, 'status' => Caixa::STATUS_ABERTO])
        ->one();
    
    if (!$caixa) {
        return false;
    }
    
    $movimentacao = new CaixaMovimentacao();
    $movimentacao->caixa_id = $caixa->id;
    $movimentacao->tipo = CaixaMovimentacao::TIPO_SAIDA;
    $movimentacao->categoria = CaixaMovimentacao::CATEGORIA_CONTA_PAGAR;
    $movimentacao->valor = $valor;
    $movimentacao->descricao = "Pagamento de conta #" . substr($contaPagarId, 0, 8);
    $movimentacao->conta_pagar_id = $contaPagarId;
    $movimentacao->forma_pagamento_id = $formaPagamentoId;
    
    return $movimentacao->save();
}
```

**Estimativa:** 1 dia

**Benefício:** Controle completo de entradas e saídas

---

### **4. Validações Avançadas** ⭐⭐ MÉDIA PRIORIDADE

**Descrição:** Implementar validações para melhorar a segurança e usabilidade.

**O que fazer:**
- Validar se há apenas um caixa aberto por usuário
- Validar saldo suficiente antes de registrar saídas
- Validar se caixa está aberto antes de registrar movimentações
- Criar método para buscar caixa aberto atual

**Arquivos a modificar:**
- `modules/caixa/models/Caixa.php` (adicionar métodos estáticos)
- `modules/caixa/controllers/CaixaController.php` (validações)

**Código exemplo:**
```php
// Em Caixa.php
public static function getCaixaAberto($usuarioId)
{
    return self::find()
        ->where(['usuario_id' => $usuarioId, 'status' => self::STATUS_ABERTO])
        ->one();
}

public static function verificarSaldoSuficiente($caixaId, $valor)
{
    $caixa = self::findOne($caixaId);
    if (!$caixa || !$caixa->isAberto()) {
        return false;
    }
    
    $saldoAtual = $caixa->calcularValorEsperado();
    return $saldoAtual >= $valor;
}
```

**Estimativa:** 1-2 dias

**Benefício:** Sistema mais robusto e seguro

---

### **5. Relatórios Básicos** ⭐ MÉDIA PRIORIDADE

**Descrição:** Criar relatórios de movimentações e fechamento de caixa.

**O que fazer:**
- Relatório de movimentações por período
- Relatório de fechamento de caixa (PDF)
- Dashboard com resumo de caixas
- Gráficos de entradas/saídas

**Arquivos a criar:**
- `modules/caixa/controllers/RelatorioController.php`
- `modules/caixa/views/relatorio/` (views de relatórios)

**Estimativa:** 3-4 dias

**Benefício:** Melhor visualização e análise dos dados

---

### **6. Integração com Gateways de Pagamento** ⭐⭐ ALTA PRIORIDADE (Futuro)

**Descrição:** Integrar registro automático quando pagamentos via gateway são confirmados.

**O que fazer:**
- Modificar webhooks do Asaas para registrar no caixa
- Modificar webhooks do Mercado Pago para registrar no caixa
- Associar movimentações a transações de gateway

**Arquivos a modificar:**
- Controllers de webhook (Asaas, Mercado Pago)
- Usar `CaixaHelper`

**Estimativa:** 2-3 dias (após implementar webhooks)

**Benefício:** Integração completa com pagamentos online

---

### **7. Funcionalidades Avançadas** ⭐ BAIXA PRIORIDADE

**Descrição:** Funcionalidades extras para melhorar a experiência.

**O que fazer:**
- Abertura automática de caixa no início do dia
- Fechamento automático no final do dia
- Notificações de caixa aberto há muito tempo
- Histórico de diferenças no fechamento
- Exportação de relatórios (Excel, PDF)

**Estimativa:** 4-5 dias

**Benefício:** Sistema mais completo e profissional

---

## 📋 Plano de Ação Recomendado

### **Fase 1: Integrações Básicas (1 semana)**
1. **Dia 1-2:** Criar `CaixaHelper` e integrar com vendas
2. **Dia 3:** Integrar com pagamento de parcelas
3. **Dia 4:** Integrar com contas a pagar
4. **Dia 5:** Testes e ajustes

### **Fase 2: Validações e Melhorias (3-4 dias)**
1. **Dia 1-2:** Implementar validações avançadas
2. **Dia 3:** Melhorar UX (mensagens, feedback)
3. **Dia 4:** Testes finais

### **Fase 3: Relatórios (3-4 dias)**
1. **Dia 1-2:** Criar relatórios básicos
2. **Dia 3:** Dashboard de caixa
3. **Dia 4:** Exportação de relatórios

---

## 🎯 Priorização Sugerida

**Ordem de implementação:**
1. ⭐⭐⭐ **Integração Vendas → Caixa** (mais importante)
2. ⭐⭐⭐ **Integração Parcelas → Caixa** (muito importante)
3. ⭐⭐ **Validações Avançadas** (segurança)
4. ⭐⭐ **Integração Contas a Pagar → Caixa** (completa o ciclo)
5. ⭐ **Relatórios Básicos** (análise)
6. ⭐⭐ **Integração Gateways** (quando webhooks estiverem prontos)
7. ⭐ **Funcionalidades Avançadas** (nice to have)

---

## 💡 Decisões Técnicas Importantes

### **1. Onde criar o helper/service?**
- **Opção A:** `modules/caixa/helpers/CaixaHelper.php` (static methods)
- **Opção B:** `modules/caixa/services/CaixaService.php` (classe com instância)
- **Recomendação:** Opção A (mais simples, suficiente para o caso)

### **2. O que fazer se não houver caixa aberto?**
- **Opção A:** Não registrar (apenas log)
- **Opção B:** Criar caixa automaticamente
- **Opção C:** Alertar usuário
- **Recomendação:** Opção A + C (não criar automaticamente, mas alertar)

### **3. Como lidar com múltiplos caixas abertos?**
- **Opção A:** Permitir apenas um caixa aberto por vez
- **Opção B:** Permitir múltiplos, usar o mais recente
- **Recomendação:** Opção A (mais seguro e simples)

---

## ✅ Checklist de Implementação

### **Integração Vendas → Caixa**
- [ ] Criar `CaixaHelper::registrarEntradaVenda()`
- [ ] Modificar controller de vendas
- [ ] Testar com venda finalizada
- [ ] Validar movimentação criada corretamente

### **Integração Parcelas → Caixa**
- [ ] Criar `CaixaHelper::registrarEntradaParcela()`
- [ ] Modificar `ParcelaController::actionPagar()`
- [ ] Testar com parcela paga
- [ ] Validar movimentação criada

### **Integração Contas a Pagar → Caixa**
- [ ] Criar `CaixaHelper::registrarSaidaContaPagar()`
- [ ] Modificar `ContaPagarController::actionPagar()`
- [ ] Testar com conta paga
- [ ] Validar movimentação criada

### **Validações**
- [ ] Validar caixa único aberto
- [ ] Validar saldo suficiente
- [ ] Validar caixa aberto antes de movimentar
- [ ] Criar métodos helper

---

## 🚀 Começar Agora

**Recomendação:** Começar pela **Integração Vendas → Caixa** porque:
1. É a funcionalidade mais usada
2. Tem maior impacto na usabilidade
3. Serve de base para as outras integrações
4. Valida toda a estrutura criada

**Próximo passo:** Criar `CaixaHelper` e integrar com o controller de vendas.

