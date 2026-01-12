# Plano de Implementação: Preço Promocional na Venda Direta

## 📋 Problema Identificado

O sistema de venda direta (`/venda-direta`) **não está considerando** o preço promocional dos produtos, mesmo quando:
- O produto tem preço promocional cadastrado
- A promoção está dentro do período de validade
- O backend já retorna os campos `preco_final` e `em_promocao` na API

### Impacto Atual

1. ❌ Produtos em promoção são exibidos com preço normal
2. ❌ Carrinho calcula total usando preço normal
3. ❌ Pedidos são criados com preço normal
4. ❌ Comprovantes mostram preço normal

## ✅ Situação Atual do Backend

O backend **já está preparado** para promoções:

### Model Produto (`modules/vendas/models/Produto.php`)

1. **Método `getEmPromocao()`** (linha 395-410)
   - Verifica se produto está em promoção ativa
   - Valida datas de início e fim
   - Retorna `true` se dentro do período

2. **Método `getPrecoFinal()`** (linha 415-418)
   - Retorna `preco_promocional` se em promoção
   - Retorna `preco_venda_sugerido` caso contrário

3. **Método `fields()`** (linha 324-340)
   - Já inclui `em_promocao` e `preco_final` na resposta da API
   - Campos são calculados automaticamente

### API de Produtos (`modules/api/controllers/ProdutoController.php`)

- ✅ Retorna produtos com `em_promocao` e `preco_final`
- ✅ Campos são calculados automaticamente pelo model

## 🔍 Análise do Frontend

### Arquivos que Precisam de Ajuste

#### 1. **`web/venda-direta/js/app.js`**

**Problemas encontrados:**

- **Linha 774**: Renderização do preço no card do produto
  ```javascript
  // ❌ ATUAL
  ${formatarMoeda(produto.preco_venda_sugerido)}
  
  // ✅ DEVE SER
  ${formatarMoeda(produto.preco_final || produto.preco_venda_sugerido)}
  ```

- **Linha 793**: Modal de quantidade
  ```javascript
  // ❌ ATUAL
  formatarMoeda(produto.preco_venda_sugerido)
  
  // ✅ DEVE SER
  formatarMoeda(produto.preco_final || produto.preco_venda_sugerido)
  ```

- **Linha 224**: Cálculo do subtotal no carrinho
  ```javascript
  // ❌ ATUAL
  const subtotal = item.preco_venda_sugerido * item.quantidade;
  
  // ✅ DEVE SER
  const preco = item.preco_final || item.preco_venda_sugerido;
  const subtotal = preco * item.quantidade;
  ```

- **Linha 240**: Exibição do preço unitário no carrinho
  ```javascript
  // ❌ ATUAL
  ${formatarMoeda(item.preco_venda_sugerido)} un.
  
  // ✅ DEVE SER
  ${formatarMoeda(item.preco_final || item.preco_venda_sugerido)} un.
  ```

**Ações necessárias:**
- Substituir `preco_venda_sugerido` por `preco_final` (com fallback)
- Adicionar indicador visual quando produto está em promoção
- Mostrar preço original riscado quando em promoção

#### 2. **`web/venda-direta/js/cart.js`**

**Problemas encontrados:**

- **Linha 134**: Cálculo do total do carrinho
  ```javascript
  // ❌ ATUAL
  const preco = parseFloat(item.preco_venda_sugerido || 0);
  
  // ✅ DEVE SER
  const preco = parseFloat(item.preco_final || item.preco_venda_sugerido || 0);
  ```

**Ações necessárias:**
- Atualizar função `calcularTotalCarrinho()` para usar `preco_final`

#### 3. **`web/venda-direta/js/order.js`**

**Problemas encontrados:**

- **Linha 91**: Envio do preço unitário no pedido
  ```javascript
  // ❌ ATUAL
  preco_unitario: item.preco_venda_sugerido,
  
  // ✅ DEVE SER
  preco_unitario: item.preco_final || item.preco_venda_sugerido,
  ```

**Ações necessárias:**
- Atualizar função `prepararObjetoPedido()` para usar `preco_final`

#### 4. **`web/venda-direta/js/pix.js`**

**Problemas encontrados:**

- **Linha 1044**: Comprovante de venda
  ```javascript
  // ❌ ATUAL
  const preco = parseFloat(item.preco || item.preco_venda_sugerido || item.preco_unitario || item.preco_unitario_venda || 0);
  
  // ✅ DEVE SER
  const preco = parseFloat(item.preco_final || item.preco || item.preco_venda_sugerido || item.preco_unitario || item.preco_unitario_venda || 0);
  ```

**Ações necessárias:**
- Atualizar função `gerarComprovanteVenda()` para usar `preco_final`

## 📝 Plano de Implementação

### Fase 1: Atualização do Frontend (Venda Direta)

#### 1.1 Atualizar `app.js`

**Arquivo:** `web/venda-direta/js/app.js`

**Mudanças:**

1. **Função `renderizarProdutos()`** (linha 731-785)
   - Usar `preco_final` ao invés de `preco_venda_sugerido`
   - Adicionar indicador visual de promoção
   - Mostrar preço original riscado quando em promoção

2. **Função `abrirModalQuantidade()`** (linha 787-805)
   - Usar `preco_final` no modal

3. **Função `renderizarCarrinho()`** (linha 197-296)
   - Usar `preco_final` no cálculo e exibição

**Código exemplo:**
```javascript
// Renderização do card do produto
const precoExibido = produto.preco_final || produto.preco_venda_sugerido;
const emPromocao = produto.em_promocao || false;
const precoOriginal = emPromocao ? produto.preco_venda_sugerido : null;

// No HTML
${emPromocao && precoOriginal ? `
  <div class="flex items-center gap-2">
    <span class="text-sm text-gray-500 line-through">${formatarMoeda(precoOriginal)}</span>
    <span class="text-2xl font-bold text-red-600">${formatarMoeda(precoExibido)}</span>
    <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">PROMOÇÃO</span>
  </div>
` : `
  <span class="text-2xl font-bold text-blue-600">${formatarMoeda(precoExibido)}</span>
`}
```

#### 1.2 Atualizar `cart.js`

**Arquivo:** `web/venda-direta/js/cart.js`

**Mudanças:**

1. **Função `calcularTotalCarrinho()`** (linha 131-156)
   ```javascript
   const preco = parseFloat(item.preco_final || item.preco_venda_sugerido || 0);
   ```

2. **Função `adicionarAoCarrinho()`** (linha 32-60)
   - Garantir que `preco_final` e `em_promocao` sejam preservados no item do carrinho

#### 1.3 Atualizar `order.js`

**Arquivo:** `web/venda-direta/js/order.js`

**Mudanças:**

1. **Função `prepararObjetoPedido()`** (linha 75-119)
   ```javascript
   preco_unitario: item.preco_final || item.preco_venda_sugerido,
   ```

#### 1.4 Atualizar `pix.js`

**Arquivo:** `web/venda-direta/js/pix.js`

**Mudanças:**

1. **Função `gerarComprovanteVenda()`** (linha 703-1278)
   ```javascript
   const preco = parseFloat(item.preco_final || item.preco || item.preco_venda_sugerido || item.preco_unitario || item.preco_unitario_venda || 0);
   ```

### Fase 2: Melhorias Visuais (Opcional mas Recomendado)

#### 2.1 Indicador de Promoção

- Badge "PROMOÇÃO" nos cards de produtos
- Preço original riscado
- Destaque visual (cor vermelha ou verde)

#### 2.2 Informações no Carrinho

- Mostrar se item está em promoção
- Exibir desconto percentual quando aplicável
- Destaque visual para itens promocionais

## 🎯 Viabilidade

### ✅ Viável - Baixo Risco

**Motivos:**

1. **Backend já preparado**: Não precisa de mudanças no backend
2. **API já retorna dados**: `preco_final` e `em_promocao` já estão disponíveis
3. **Mudanças isoladas**: Apenas frontend precisa ser atualizado
4. **Compatibilidade**: Fallback para `preco_venda_sugerido` garante retrocompatibilidade
5. **Sem breaking changes**: Produtos sem promoção continuam funcionando normalmente

### ⚠️ Pontos de Atenção

1. **Cache do navegador**: Pode ser necessário limpar cache após deploy
2. **Carrinho existente**: Itens já no carrinho podem ter `preco_venda_sugerido` antigo
   - **Solução**: Ao carregar carrinho, verificar se produto ainda está em promoção e atualizar preço

## 📊 Impacto no Código

### Arquivos Modificados

1. `web/venda-direta/js/app.js` - ~10 linhas modificadas
2. `web/venda-direta/js/cart.js` - ~3 linhas modificadas
3. `web/venda-direta/js/order.js` - ~1 linha modificada
4. `web/venda-direta/js/pix.js` - ~1 linha modificada

### Arquivos Não Modificados

- ✅ Backend (já está correto)
- ✅ API (já retorna dados corretos)
- ✅ Model Produto (já tem métodos necessários)

### Complexidade

- **Baixa**: Apenas substituições de campo
- **Tempo estimado**: 2-4 horas
- **Risco**: Baixo (mudanças isoladas com fallback)

## 🧪 Testes Necessários

### Testes Funcionais

1. ✅ Produto sem promoção → deve usar preço normal
2. ✅ Produto em promoção ativa → deve usar preço promocional
3. ✅ Produto com promoção expirada → deve usar preço normal
4. ✅ Produto com promoção futura → deve usar preço normal
5. ✅ Adicionar produto em promoção ao carrinho → deve usar preço promocional
6. ✅ Calcular total do carrinho → deve considerar preços promocionais
7. ✅ Finalizar pedido → deve enviar preço promocional correto
8. ✅ Comprovante → deve mostrar preço promocional

### Testes de Regressão

1. ✅ Produtos sem promoção continuam funcionando
2. ✅ Carrinho com itens antigos (sem `preco_final`) continua funcionando
3. ✅ Pedidos antigos não são afetados

## 🚀 Ordem de Implementação

1. **Fase 1.1**: Atualizar `app.js` (renderização e carrinho)
2. **Fase 1.2**: Atualizar `cart.js` (cálculo de total)
3. **Fase 1.3**: Atualizar `order.js` (envio do pedido)
4. **Fase 1.4**: Atualizar `pix.js` (comprovante)
5. **Fase 2**: Melhorias visuais (opcional)

## 📝 Notas Importantes

1. **Fallback obrigatório**: Sempre usar `preco_final || preco_venda_sugerido` para garantir compatibilidade
2. **Validação de promoção**: O backend já valida se promoção está ativa, frontend apenas usa o resultado
3. **Performance**: Não há impacto negativo, apenas leitura de campo adicional
4. **Compatibilidade**: Mudanças são retrocompatíveis com produtos sem promoção

## ✅ Checklist de Implementação

- [ ] Atualizar `app.js` - renderização de produtos
- [ ] Atualizar `app.js` - modal de quantidade
- [ ] Atualizar `app.js` - renderização do carrinho
- [ ] Atualizar `cart.js` - cálculo do total
- [ ] Atualizar `order.js` - envio do pedido
- [ ] Atualizar `pix.js` - comprovante
- [ ] Testar produto sem promoção
- [ ] Testar produto em promoção ativa
- [ ] Testar produto com promoção expirada
- [ ] Testar carrinho com múltiplos produtos
- [ ] Testar finalização de pedido
- [ ] Testar comprovante
- [ ] Verificar compatibilidade com carrinho antigo

---

**Data:** Janeiro 2025  
**Versão:** 1.0  
**Status:** 📋 Pronto para Implementação

