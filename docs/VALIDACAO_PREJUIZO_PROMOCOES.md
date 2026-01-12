# Validação de Prejuízo em Promoções

## 📋 Visão Geral

Sistema implementado para **alertar** o usuário quando produtos em promoção ou preços de venda podem causar prejuízo, considerando custos, taxas fixas e variáveis.

**⚠️ IMPORTANTE:** Os alertas são **apenas informativos** e **NÃO bloqueiam** o cadastro. O usuário tem autonomia para decidir se deseja prosseguir com o cadastro mesmo quando há prejuízo detectado.

## ✅ Implementações

### 1. **Validação Backend (Model)**

**Arquivo:** `modules/vendas/models/Produto.php`

**Métodos:** `validatePrejuizo()` e `validatePromocao()`

**⚠️ ATUALIZAÇÃO:** As validações de prejuízo foram modificadas para **NÃO bloquear** o cadastro. Os métodos ainda existem para manter compatibilidade, mas não adicionam erros quando há prejuízo detectado.

**O que faz:**
- ✅ **NÃO bloqueia** o cadastro quando há prejuízo
- ✅ Os alertas visuais no frontend continuam funcionando
- ✅ O usuário tem autonomia para decidir se deseja prosseguir
- ✅ Considera custo total (custo + frete)
- ✅ Considera taxas fixas e variáveis
- ✅ Usa a mesma lógica da "Prova Real"

### 2. **Validação Frontend (JavaScript)**

**Arquivo:** `modules/vendas/views/produto/_form.php`

**Função:** `validarPrejuizoPromocao()`

```javascript
function validarPrejuizoPromocao() {
    const precoPromo = parseFloat(precoPromocionalInput.value) || 0;
    const custo = parseFloat(custoInput.value) || 0;
    const frete = parseFloat(freteInput.value) || 0;
    const custoTotal = custo + frete;
    
    const taxaFixa = parseFloat(taxaFixaInput?.value) || 0;
    const taxaVariavel = parseFloat(taxaVariavelInput?.value) || 0;
    
    if (precoPromo > 0 && custoTotal > 0) {
        const provaReal = calcularProvaReal(precoPromo, custoTotal, taxaFixa, taxaVariavel);
        
        if (provaReal.lucroReal < 0) {
            // Mostra alerta de prejuízo
            alertaPrejuizoPromocao.classList.remove('hidden');
            // Destaca campo
            precoPromocionalInput.classList.add('border-red-500', 'bg-red-50');
        }
    }
}
```

**O que faz:**
- ✅ Calcula "Prova Real" do preço promocional em tempo real
- ✅ Mostra alerta visual quando detecta prejuízo
- ✅ Destaca o campo de preço promocional
- ✅ Atualiza automaticamente quando custo, frete ou taxas mudam

### 3. **Alerta Visual**

**Componente HTML:**

```html
<div id="alerta-prejuizo-promocao" class="hidden bg-red-50 border-2 border-red-300 rounded-lg p-2.5 sm:p-3">
    <div class="flex items-start gap-2">
        <svg>⚠️</svg>
        <div>
            <p class="font-bold text-red-800">⚠️ ATENÇÃO: Prejuízo Detectado na Promoção!</p>
            <p id="mensagem-prejuizo-promocao"></p>
            <p class="text-red-600">💡 Dica: Ajuste o preço promocional ou reduza as taxas.</p>
        </div>
    </div>
</div>
```

**Características:**
- 🎨 Design mobile-first
- 🔴 Cores semânticas (vermelho para alerta)
- 📱 Responsivo
- 💬 Mensagem detalhada com valores

## 🔄 Fluxo de Validação

### 1. **Ao Digitar Preço Promocional**

```
Usuário digita preço promocional
    ↓
atualizarPreviewPromocao() é chamada
    ↓
validarPrejuizoPromocao() é chamada
    ↓
Calcula "Prova Real"
    ↓
Se lucro < 0:
    - Mostra alerta
    - Destaca campo
    - Exibe mensagem detalhada
```

### 2. **Ao Mudar Custo, Frete ou Taxas**

```
Usuário altera custo/frete/taxas
    ↓
Event listener detecta mudança
    ↓
validarPrejuizoPromocao() é chamada
    ↓
Recalcula validação
    ↓
Atualiza alerta se necessário
```

### 3. **Ao Salvar (Backend)**

```
Usuário clica em Salvar
    ↓
Model valida todos os campos
    ↓
validatePrejuizo() e validatePromocao() são executadas
    ↓
Se prejuízo detectado:
    - ⚠️ NÃO bloqueia mais o cadastro
    - ✅ Formulário é salvo normalmente
    - ℹ️ Alertas visuais no frontend continuam informando
    - 👤 Usuário tem autonomia para decidir
```

## 📊 Cálculo da "Prova Real"

### Fórmula:

```
Taxas Fixas = Preço Promocional × (Taxa Fixa % / 100)
Taxas Variáveis = Preço Promocional × (Taxa Variável % / 100)
Lucro Real = Preço Promocional - Taxas Fixas - Taxas Variáveis - Custo Total
```

### Exemplo:

**Dados:**
- Preço Promocional: R$ 80,00
- Custo Total: R$ 50,00
- Taxa Fixa: 5%
- Taxa Variável: 3%

**Cálculo:**
```
Taxas Fixas = 80 × 0.05 = R$ 4,00
Taxas Variáveis = 80 × 0.03 = R$ 2,40
Lucro Real = 80 - 4 - 2.40 - 50 = R$ 23,60 ✅ (Lucro positivo)
```

**Se Preço Promocional fosse R$ 50,00:**
```
Taxas Fixas = 50 × 0.05 = R$ 2,50
Taxas Variáveis = 50 × 0.03 = R$ 1,50
Lucro Real = 50 - 2.50 - 1.50 - 50 = -R$ 4,00 ❌ (Prejuízo!)
```

## 🎯 Validações Implementadas

### Backend

1. ⚠️ **NÃO bloqueia** cadastro quando há prejuízo (apenas informativo)
2. ✅ Considera custo total (custo + frete)
3. ✅ Considera taxas fixas e variáveis
4. ✅ Usuário tem autonomia para decidir

### Frontend

1. ✅ Validação em tempo real
2. ✅ Alerta visual imediato (informativo, não bloqueia)
3. ✅ Campo destacado quando há prejuízo
4. ✅ Mensagem detalhada com valores
5. ✅ Atualização automática ao mudar custos/taxas
6. ✅ Mostra exatamente o valor do prejuízo (ex: R$ 6,66)

## 🔍 Event Listeners

### Campos Monitorados:

- `preco-promocional`: Preço promocional
- `preco-custo`: Custo do produto
- `valor-frete`: Valor do frete
- `taxa-fixa`: Taxa fixa percentual
- `taxa-variavel`: Taxa variável percentual

### Ações:

- `input`: Validação em tempo real
- `change`: Validação ao sair do campo

## 📱 Responsividade

### Mobile:
- Alerta compacto
- Texto menor
- Padding reduzido

### Desktop:
- Alerta expandido
- Texto maior
- Padding normal

## 🚀 Benefícios

1. **Informação Transparente**: Alerta o usuário sobre possíveis prejuízos
2. **Autonomia do Usuário**: Permite que o usuário decida se deseja prosseguir
3. **Feedback Imediato**: Usuário vê o problema em tempo real
4. **Transparência**: Mostra exatamente quanto será o prejuízo (ex: R$ 6,66)
5. **Facilidade**: Calcula automaticamente considerando todas as variáveis
6. **Consistência**: Usa a mesma lógica da "Prova Real" do preço normal
7. **Flexibilidade**: Permite promoções agressivas ou estratégicas mesmo com prejuízo

## 📝 Notas Importantes

1. **Alertas Informativos**: Os alertas são apenas informativos e NÃO bloqueiam o cadastro
2. **Autonomia do Usuário**: O usuário tem total controle para decidir se deseja vender com prejuízo
3. **Configuração Financeira**: Usa configuração específica do produto ou global
4. **Tempo Real**: Validação acontece enquanto o usuário digita
5. **Precisão**: Considera todas as taxas e custos
6. **Cálculo do Prejuízo**: O valor do prejuízo (ex: R$ 6,66) é calculado como: `Lucro Real = Preço de Venda - Taxas Fixas - Taxas Variáveis - Custo Total`. Se negativo, há prejuízo.

---

**Data:** Janeiro 2025  
**Versão:** 1.0

