# 🧪 Como Testar Recebimento de Parcelas

## 📋 Guia Completo de Testes

Este documento descreve como testar o recebimento de parcelas e a integração automática com o caixa.

---

## ✅ Pré-requisitos

Antes de começar os testes, certifique-se de ter:

1. **Parcela pendente** no sistema
2. **Caixa aberto** para o usuário da parcela
3. **Acesso ao sistema** (login)
4. **Ferramentas de teste:**
   - Navegador (para interface web)
   - Postman/Insomnia (para testes de API)
   - Acesso ao banco de dados (para verificação)

---

## 🎯 Métodos de Teste

### **1. Teste via Interface Web (Cobrança)**

#### **Passo 1: Verificar Parcela Pendente**

1. Acesse o sistema e vá para a área de **Cobranças** ou **Parcelas**
2. Localize uma parcela com status **PENDENTE**
3. Anote:
   - ID da parcela
   - Valor da parcela
   - ID do usuário
   - Data de vencimento

#### **Passo 2: Verificar Caixa Aberto**

1. Acesse `/index.php/caixa/caixa/index`
2. Verifique se há um caixa com status **ABERTO** para o usuário
3. Se não houver, abra um novo caixa:
   - Clique em "Abrir Caixa"
   - Defina um valor inicial (ex: R$ 100,00)
   - Salve

#### **Passo 3: Marcar Parcela como Paga**

1. Na interface de cobranças, localize a parcela
2. Clique em "Receber" ou "Marcar como Paga"
3. Preencha:
   - Valor recebido
   - Forma de pagamento (DINHEIRO, PIX, etc.)
   - Data de pagamento (opcional)
4. Confirme o pagamento

#### **Passo 4: Verificar Resultado**

1. **Verificar Parcela:**
   - Status deve estar como **PAGA**
   - Data de pagamento deve estar preenchida
   - Valor pago deve estar registrado

2. **Verificar Caixa:**
   - Acesse `/index.php/caixa/caixa/view?id=[CAIXA_ID]`
   - Verifique se há uma nova movimentação:
     - Tipo: **ENTRADA**
     - Categoria: **PAGAMENTO**
     - Valor: igual ao valor da parcela
     - Parcela ID: deve estar associada

3. **Verificar Logs:**
   - Procure por: `"✅ Entrada registrada no caixa para Parcela ID..."`

---

### **2. Teste via API (Recomendado)**

#### **Endpoint:**
```
POST /index.php/api/cobranca/registrar-acao
```

#### **Headers:**
```
Content-Type: application/json
Authorization: Bearer [TOKEN] (se necessário)
```

#### **Body (JSON):**
```json
{
    "tipo_acao": "PAGAMENTO",
    "parcela_id": "4de3e5c1-2696-4d92-9564-d6b026869e58",
    "valor_recebido": 150.00,
    "forma_pagamento": "DINHEIRO",
    "usuario_id": "a99a38a9-e368-4a47-a4bd-02ba3bacaa76",
    "cobrador_id": "cobrador-uuid-here",
    "cliente_id": "cliente-uuid-here",
    "data_acao": "2024-12-08 14:30:00",
    "observacao": "Pagamento recebido em dinheiro"
}
```

#### **Exemplo com cURL:**
```bash
curl -X POST http://localhost/pulse/basic/web/index.php/api/cobranca/registrar-acao \
  -H "Content-Type: application/json" \
  -d '{
    "tipo_acao": "PAGAMENTO",
    "parcela_id": "4de3e5c1-2696-4d92-9564-d6b026869e58",
    "valor_recebido": 150.00,
    "forma_pagamento": "DINHEIRO",
    "usuario_id": "a99a38a9-e368-4a47-a4bd-02ba3bacaa76",
    "cobrador_id": "cobrador-uuid-here",
    "cliente_id": "cliente-uuid-here",
    "observacao": "Teste de pagamento"
  }'
```

#### **Resposta Esperada:**
```json
{
    "success": true,
    "message": "Ação registrada com sucesso",
    "historico_id": "uuid-do-historico"
}
```

---

### **3. Teste via Model (PHP)**

#### **Código de Exemplo:**
```php
<?php
// Em um controller ou console command

use app\modules\vendas\models\Parcela;

// Busca a parcela
$parcela = Parcela::findOne('4de3e5c1-2696-4d92-9564-d6b026869e58');

if ($parcela && $parcela->status_parcela_codigo === 'PENDENTE') {
    // Marca como paga (isso automaticamente registra no caixa)
    $sucesso = $parcela->registrarPagamento(
        150.00,                    // Valor pago
        null,                       // Cobrador ID (opcional)
        'forma-pagamento-uuid'      // Forma de pagamento ID (opcional)
    );
    
    if ($sucesso) {
        echo "✅ Parcela marcada como paga e registrada no caixa!\n";
    } else {
        echo "❌ Erro ao marcar parcela como paga.\n";
    }
} else {
    echo "⚠️ Parcela não encontrada ou já está paga.\n";
}
```

---

## 🔍 Verificações SQL

### **1. Verificar Parcela**
```sql
-- Buscar parcela específica
SELECT 
    id,
    numero_parcela,
    valor_parcela,
    valor_pago,
    status_parcela_codigo,
    data_pagamento,
    forma_pagamento_id,
    usuario_id
FROM prest_parcelas
WHERE id = '4de3e5c1-2696-4d92-9564-d6b026869e58';
```

### **2. Verificar Movimentação no Caixa**
```sql
-- Buscar movimentação da parcela
SELECT 
    m.id,
    m.parcela_id,
    m.valor,
    m.tipo,
    m.categoria,
    m.descricao,
    m.data_movimento,
    m.forma_pagamento_id,
    c.id as caixa_id,
    c.status as caixa_status
FROM prest_caixa_movimentacoes m
LEFT JOIN prest_caixa c ON c.id = m.caixa_id
WHERE m.parcela_id = '4de3e5c1-2696-4d92-9564-d6b026869e58';
```

### **3. Verificar Parcelas Pagas sem Movimentação**
```sql
-- Parcelas pagas que não têm movimentação no caixa
SELECT 
    p.id,
    p.numero_parcela,
    p.valor_pago,
    p.data_pagamento,
    p.status_parcela_codigo,
    p.usuario_id
FROM prest_parcelas p
LEFT JOIN prest_caixa_movimentacoes m ON m.parcela_id = p.id
WHERE p.status_parcela_codigo = 'PAGA'
  AND m.id IS NULL
ORDER BY p.data_pagamento DESC;
```

### **4. Verificar Caixa e Saldo**
```sql
-- Verificar caixa aberto e saldo atual
SELECT 
    c.id,
    c.status,
    c.valor_inicial,
    c.data_abertura,
    c.calcular_valor_esperado() as valor_esperado,
    COUNT(m.id) as total_movimentacoes,
    SUM(CASE WHEN m.tipo = 'ENTRADA' THEN m.valor ELSE 0 END) as total_entradas,
    SUM(CASE WHEN m.tipo = 'SAIDA' THEN m.valor ELSE 0 END) as total_saidas
FROM prest_caixa c
LEFT JOIN prest_caixa_movimentacoes m ON m.caixa_id = c.id
WHERE c.usuario_id = 'a99a38a9-e368-4a47-a4bd-02ba3bacaa76'
  AND c.status = 'ABERTO'
GROUP BY c.id;
```

---

## 📊 Cenários de Teste

### **Cenário 1: Pagamento Normal com Caixa Aberto** ✅

**Objetivo:** Verificar que parcela é registrada no caixa quando há caixa aberto.

**Passos:**
1. Abrir um caixa
2. Marcar parcela como paga
3. Verificar movimentação criada

**Resultado Esperado:**
- ✅ Parcela marcada como PAGA
- ✅ Movimentação criada no caixa
- ✅ Log: `"✅ Entrada registrada no caixa para Parcela ID..."`

---

### **Cenário 2: Pagamento sem Caixa Aberto** ⚠️

**Objetivo:** Verificar que parcela é marcada como paga mesmo sem caixa aberto.

**Passos:**
1. Fechar todos os caixas (ou não abrir nenhum)
2. Marcar parcela como paga
3. Verificar que parcela foi marcada como paga
4. Verificar que NÃO há movimentação no caixa

**Resultado Esperado:**
- ✅ Parcela marcada como PAGA (não falha)
- ❌ NÃO há movimentação no caixa
- ⚠️ Log: `"⚠️ PARCELA PAGA COM CAIXA FECHADO..."`

---

### **Cenário 3: Prevenção de Duplicação** 🔒

**Objetivo:** Verificar que não é possível registrar a mesma parcela duas vezes.

**Passos:**
1. Marcar parcela como paga (primeira vez)
2. Verificar movimentação criada
3. Tentar marcar a mesma parcela como paga novamente
4. Verificar que não foi criada nova movimentação

**Resultado Esperado:**
- ✅ Apenas uma movimentação existe
- ℹ️ Log: `"Movimentação já existe para parcela... Evitando duplicação."`

---

### **Cenário 4: Pagamento com Forma de Pagamento** 💳

**Objetivo:** Verificar que forma de pagamento é associada corretamente.

**Passos:**
1. Marcar parcela como paga informando forma de pagamento
2. Verificar movimentação criada
3. Verificar que `forma_pagamento_id` está preenchido

**Resultado Esperado:**
- ✅ Movimentação tem `forma_pagamento_id` preenchido
- ✅ Parcela tem `forma_pagamento_id` preenchido

---

### **Cenário 5: Múltiplas Parcelas** 📦

**Objetivo:** Verificar que múltiplas parcelas são registradas corretamente.

**Passos:**
1. Marcar 3-5 parcelas como pagas
2. Verificar que cada parcela tem sua movimentação
3. Verificar saldo do caixa

**Resultado Esperado:**
- ✅ Cada parcela tem sua movimentação
- ✅ Saldo do caixa = valor inicial + soma de todas as parcelas

---

## 🛠️ Script de Teste Automatizado

### **Script PHP para Teste:**
```php
<?php
// scripts/testar_recebimento_parcela.php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/console.php';
new yii\console\Application($config);

use app\modules\vendas\models\Parcela;
use app\modules\caixa\models\Caixa;
use app\modules\caixa\models\CaixaMovimentacao;

// ID da parcela para testar
$parcelaId = $argv[1] ?? null;

if (!$parcelaId) {
    echo "❌ Uso: php testar_recebimento_parcela.php [PARCELA_ID]\n";
    exit(1);
}

echo "🧪 Testando recebimento de parcela: {$parcelaId}\n\n";

// 1. Buscar parcela
$parcela = Parcela::findOne($parcelaId);
if (!$parcela) {
    echo "❌ Parcela não encontrada!\n";
    exit(1);
}

echo "📋 Parcela encontrada:\n";
echo "   - Número: {$parcela->numero_parcela}\n";
echo "   - Valor: R$ " . number_format($parcela->valor_parcela, 2, ',', '.') . "\n";
echo "   - Status: {$parcela->status_parcela_codigo}\n";
echo "   - Usuário: {$parcela->usuario_id}\n\n";

// 2. Verificar caixa aberto
$caixa = Caixa::find()
    ->where(['usuario_id' => $parcela->usuario_id, 'status' => Caixa::STATUS_ABERTO])
    ->one();

if (!$caixa) {
    echo "⚠️ Nenhum caixa aberto encontrado!\n";
    echo "   A parcela será marcada como paga, mas não será registrada no caixa.\n\n";
} else {
    echo "✅ Caixa aberto encontrado:\n";
    echo "   - ID: {$caixa->id}\n";
    echo "   - Valor Inicial: R$ " . number_format($caixa->valor_inicial, 2, ',', '.') . "\n";
    echo "   - Saldo Atual: R$ " . number_format($caixa->calcularValorEsperado(), 2, ',', '.') . "\n\n";
}

// 3. Verificar se já existe movimentação
$movimentacaoExistente = CaixaMovimentacao::find()
    ->where(['parcela_id' => $parcelaId])
    ->one();

if ($movimentacaoExistente) {
    echo "ℹ️ Movimentação já existe para esta parcela:\n";
    echo "   - ID: {$movimentacaoExistente->id}\n";
    echo "   - Valor: R$ " . number_format($movimentacaoExistente->valor, 2, ',', '.') . "\n";
    echo "   - Data: {$movimentacaoExistente->data_movimento}\n\n";
    echo "✅ Teste: Prevenção de duplicação funcionando!\n";
    exit(0);
}

// 4. Marcar parcela como paga
if ($parcela->status_parcela_codigo === 'PAGA') {
    echo "⚠️ Parcela já está paga!\n";
} else {
    echo "🔄 Marcando parcela como paga...\n";
    $sucesso = $parcela->registrarPagamento($parcela->valor_parcela);
    
    if ($sucesso) {
        echo "✅ Parcela marcada como paga!\n\n";
    } else {
        echo "❌ Erro ao marcar parcela como paga!\n";
        print_r($parcela->errors);
        exit(1);
    }
}

// 5. Verificar movimentação criada
$movimentacao = CaixaMovimentacao::find()
    ->where(['parcela_id' => $parcelaId])
    ->one();

if ($movimentacao) {
    echo "✅ Movimentação criada no caixa:\n";
    echo "   - ID: {$movimentacao->id}\n";
    echo "   - Tipo: {$movimentacao->tipo}\n";
    echo "   - Categoria: {$movimentacao->categoria}\n";
    echo "   - Valor: R$ " . number_format($movimentacao->valor, 2, ',', '.') . "\n";
    echo "   - Data: {$movimentacao->data_movimento}\n\n";
    
    if ($caixa) {
        $novoSaldo = $caixa->calcularValorEsperado();
        echo "💰 Novo saldo do caixa: R$ " . number_format($novoSaldo, 2, ',', '.') . "\n";
    }
    
    echo "\n✅ Teste concluído com sucesso!\n";
} else {
    echo "⚠️ Nenhuma movimentação foi criada.\n";
    if (!$caixa) {
        echo "   Motivo: Não há caixa aberto.\n";
    }
    echo "\n⚠️ Teste concluído com aviso.\n";
}
```

**Como usar:**
```bash
cd /srv/http/pulse/basic
php scripts/testar_recebimento_parcela.php [PARCELA_ID]
```

---

## 📝 Checklist de Teste

- [ ] Parcela pendente disponível
- [ ] Caixa aberto para o usuário
- [ ] Parcela marcada como paga
- [ ] Movimentação criada no caixa
- [ ] Tipo da movimentação: ENTRADA
- [ ] Categoria da movimentação: PAGAMENTO
- [ ] Valor da movimentação = valor da parcela
- [ ] Parcela ID associada corretamente
- [ ] Forma de pagamento associada (se informada)
- [ ] Log de sucesso gerado
- [ ] Saldo do caixa atualizado corretamente
- [ ] Prevenção de duplicação funcionando
- [ ] Teste sem caixa aberto (parcela paga, mas sem movimentação)

---

## 🎯 Resultado Esperado

Após os testes, você deve ter:

1. ✅ Parcelas sendo marcadas como pagas corretamente
2. ✅ Movimentações sendo criadas automaticamente no caixa
3. ✅ Logs detalhados para diagnóstico
4. ✅ Prevenção de duplicação funcionando
5. ✅ Sistema funcionando mesmo sem caixa aberto (apenas aviso)

---

**Data de Criação:** 2024-12-08
**Status:** ✅ Pronto para Testes

