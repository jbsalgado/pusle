# 🔒 Validações e Regras de Negócio - Módulo Caixa

## 📋 Resumo

Este documento descreve as validações e regras de negócio implementadas no módulo de Caixa para garantir a integridade dos dados e o funcionamento correto do sistema.

---

## ✅ Validações Implementadas

### **1. Apenas Um Caixa Aberto por Loja**

**Regra:** Cada loja (usuário) pode ter apenas **um caixa aberto por dia**.

**Implementação:**
- **Local:** `modules/caixa/controllers/CaixaController.php` → `actionCreate()`
- **Comportamento:**
  - Ao tentar abrir um novo caixa, o sistema verifica se já existe um caixa aberto
  - Se existir caixa aberto do **dia atual**, bloqueia a abertura e redireciona para o caixa existente
  - Se existir caixa aberto do **dia anterior**, fecha automaticamente e permite abrir o novo

**Código:**
```php
// Verifica se já existe caixa aberto
$caixaAberto = Caixa::find()
    ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
    ->one();

if ($caixaAberto) {
    if ($caixaAberto->isAbertoDiaAnterior()) {
        // Fecha automaticamente o caixa do dia anterior
        $caixaAberto->fecharAutomaticamente('Fechado automaticamente: caixa do dia anterior detectado.');
    } else {
        // Bloqueia abertura de novo caixa
        Yii::$app->session->setFlash('error', 'Já existe um caixa aberto para esta loja.');
        return $this->redirect(['view', 'id' => $caixaAberto->id]);
    }
}
```

---

### **2. Venda com Caixa Fechado**

**Regra:** Se uma venda for realizada sem caixa aberto, a venda é processada normalmente, mas **não é registrada no caixa**.

**Implementação:**
- **Local:** `modules/caixa/helpers/CaixaHelper.php` → `registrarEntradaVenda()`
- **Comportamento:**
  - Verifica se existe caixa aberto
  - Se não existir, registra aviso no log e retorna `false`
  - A venda é processada normalmente (não falha)
  - A movimentação pode ser registrada manualmente depois

**Log Gerado:**
```
⚠️ VENDA REALIZADA COM CAIXA FECHADO. Venda ID: {venda_id}, Usuário ID: {usuario_id}, Valor: R$ {valor}. 
A venda foi processada, mas não foi registrada no caixa. 
É necessário abrir um caixa e registrar a movimentação manualmente.
```

**Ação Recomendada:**
1. Abrir um caixa
2. Registrar a movimentação manualmente através da interface
3. Ou usar um script de sincronização para registrar vendas pendentes

---

### **3. Venda com Caixa do Dia Anterior**

**Regra:** Se uma venda for realizada com caixa aberto do dia anterior, o caixa é **fechado automaticamente** e a venda **não é registrada**.

**Implementação:**
- **Local:** `modules/caixa/helpers/CaixaHelper.php` → `registrarEntradaVenda()`
- **Comportamento:**
  - Verifica se o caixa aberto é do dia atual
  - Se for do dia anterior:
    1. Fecha automaticamente o caixa do dia anterior
    2. Registra aviso no log
    3. Retorna `false` (não registra a movimentação)
  - A venda é processada normalmente (não falha)

**Log Gerado:**
```
⚠️ VENDA REALIZADA COM CAIXA DO DIA ANTERIOR. 
O caixa foi fechado automaticamente. 
Venda ID: {venda_id}, Usuário ID: {usuario_id}, Valor: R$ {valor}. 
É necessário abrir um novo caixa para registrar esta e futuras vendas.
```

**Ação Recomendada:**
1. Abrir um novo caixa para o dia atual
2. Registrar a movimentação manualmente para a venda que não foi registrada
3. Verificar se há outras vendas do dia que precisam ser registradas

---

## 🔧 Métodos Auxiliares

### **Modelo Caixa**

#### `isAbertoHoje()`
Verifica se o caixa foi aberto hoje.

```php
public function isAbertoHoje()
{
    if (!$this->data_abertura) {
        return false;
    }
    
    $dataAbertura = new \DateTime($this->data_abertura);
    $hoje = new \DateTime('today');
    
    return $dataAbertura->format('Y-m-d') === $hoje->format('Y-m-d');
}
```

#### `isAbertoDiaAnterior()`
Verifica se o caixa foi aberto em data anterior (não é de hoje).

```php
public function isAbertoDiaAnterior()
{
    if (!$this->isAberto() || !$this->data_abertura) {
        return false;
    }
    
    return !$this->isAbertoHoje();
}
```

#### `fecharAutomaticamente($observacoes = null)`
Fecha o caixa automaticamente, calculando valores e adicionando observações.

```php
public function fecharAutomaticamente($observacoes = null)
{
    if (!$this->isAberto()) {
        return false;
    }

    $this->valor_esperado = $this->calcularValorEsperado();
    $this->valor_final = $this->valor_esperado;
    $this->diferenca = 0;
    $this->data_fechamento = date('Y-m-d H:i:s');
    $this->status = self::STATUS_FECHADO;
    
    if ($observacoes) {
        $this->observacoes = ($this->observacoes ? $this->observacoes . "\n" : '') . $observacoes;
    }

    return $this->save(false);
}
```

---

### **CaixaHelper**

#### `getCaixaAberto($usuarioId = null, $fecharDiaAnterior = true)`
Busca o caixa aberto do dia atual, fechando automaticamente caixas do dia anterior se necessário.

```php
public static function getCaixaAberto($usuarioId = null, $fecharDiaAnterior = true)
{
    // Busca todos os caixas abertos
    $caixasAbertos = Caixa::find()
        ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
        ->orderBy(['data_abertura' => SORT_DESC])
        ->all();
    
    // Fecha caixas do dia anterior se necessário
    if (count($caixasAbertos) > 1 && $fecharDiaAnterior) {
        foreach ($caixasAbertos as $caixa) {
            if ($caixa->isAbertoDiaAnterior()) {
                $caixa->fecharAutomaticamente('Fechado automaticamente: múltiplos caixas abertos detectados.');
            }
        }
    }
    
    // Retorna o primeiro caixa do dia atual
    foreach ($caixasAbertos as $caixa) {
        if ($caixa->isAbertoHoje()) {
            return $caixa;
        }
    }
    
    return null;
}
```

#### `fecharCaixasDiaAnterior($usuarioId = null)`
Fecha automaticamente todos os caixas do dia anterior para um usuário.

```php
public static function fecharCaixasDiaAnterior($usuarioId = null)
{
    $caixasDiaAnterior = Caixa::find()
        ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
        ->all();
    
    $fechados = 0;
    foreach ($caixasDiaAnterior as $caixa) {
        if ($caixa->isAbertoDiaAnterior()) {
            if ($caixa->fecharAutomaticamente('Fechado automaticamente: limpeza de caixas do dia anterior.')) {
                $fechados++;
            }
        }
    }
    
    return $fechados;
}
```

---

## 📊 Fluxo de Validação

### **Abertura de Caixa**

```
1. Usuário tenta abrir caixa
   ↓
2. Sistema verifica se há caixa aberto
   ↓
3a. Se não há caixa aberto → Permite abrir
3b. Se há caixa do dia atual → Bloqueia e redireciona
3c. Se há caixa do dia anterior → Fecha automaticamente e permite abrir
```

### **Registro de Venda**

```
1. Venda é finalizada
   ↓
2. Sistema tenta registrar no caixa
   ↓
3a. Se não há caixa aberto → Log de aviso, venda processada normalmente
3b. Se há caixa do dia anterior → Fecha automaticamente, log de aviso, venda processada normalmente
3c. Se há caixa do dia atual → Registra movimentação normalmente
```

---

## 🎯 Benefícios

1. **Integridade de Dados:** Garante que apenas um caixa esteja aberto por loja por dia
2. **Automação:** Fecha automaticamente caixas do dia anterior
3. **Resiliência:** Vendas não falham se não houver caixa aberto
4. **Rastreabilidade:** Logs detalhados para diagnóstico
5. **Flexibilidade:** Permite registro manual posterior de movimentações

---

## 📝 Notas Importantes

- **Vendas não falham** se não houver caixa aberto (apenas não são registradas)
- **Caixas do dia anterior são fechados automaticamente** ao abrir novo caixa ou registrar venda
- **Logs são gerados** para todas as situações excepcionais
- **Avisos visuais** são exibidos na interface quando há problemas

---

## 🔍 Como Verificar

### **Verificar Caixas Abertos do Dia Anterior**

```sql
SELECT id, usuario_id, data_abertura, status
FROM prest_caixa
WHERE status = 'ABERTO'
  AND DATE(data_abertura) < CURRENT_DATE;
```

### **Verificar Vendas Não Registradas no Caixa**

```sql
SELECT v.id, v.valor_total, v.data_venda, v.usuario_id
FROM prest_vendas v
LEFT JOIN prest_caixa_movimentacoes m ON m.venda_id = v.id
WHERE v.cliente_id IS NULL  -- Venda direta
  AND m.id IS NULL  -- Sem movimentação
  AND v.status_venda_codigo = 'pago';
```

---

**Data de Implementação:** 2024-12-08
**Status:** ✅ Implementado e Funcionando

