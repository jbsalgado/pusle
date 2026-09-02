# Plano de Correção — Erro 500 ao Finalizar Venda (Divergência de Pagamentos)

> Contexto do alerta:
> `Erro ao finalizar venda: Erro 500: {"name":"Internal Server Error","message":"Erro ao processar pedido: A soma das formas de pagamento (R$ 175,00) não corresponde ao total da venda (R$ 178,00). Subtotal itens: R$ 178,00 | Desconto global: R$ 0,00 | Acréscimo: R$ 0,00.","code":0,"status":500,...}`

---

## 1. Resumo executivo

O erro acontece **antes** de salvar a venda. O backend (`modules/api/controllers/PedidoController.php`) recalcula o total da venda a partir dos itens recebidos e valida se a soma de `pagamentos_multiplos` é igual a esse total recalculado.

Neste caso:

| Grandeza | Valor |
|---|---|
| Soma das formas de pagamento (enviada pelo front) | R$ 175,00 |
| Subtotal dos itens (recalculado pelo backend) | R$ 178,00 |
| Desconto global | R$ 0,00 |
| Acréscimo | R$ 0,00 |
| Total da venda (backend) | R$ 178,00 |

A diferença de **R$ 3,00** está **100% no subtotal dos itens** (desconto global e acréscimo são zero). Ou seja, o frontend calculou um total de carrinho de R$ 175,00 enquanto o backend, ao re-somar `preco_unitario * quantidade - desconto_item`, chegou a R$ 178,00. Como a validação usa a igualdade (com tolerância de R$ 0,01), o pedido é rejeitado com `500`.

**Por que não finaliza:** a validação ocorre dentro da transação, antes de `$venda->save()` e antes de `$transaction->commit()`. Qualquer `Exception` nesse ponto dispara `rollBack()` e é convertida em `ServerErrorHttpException` (`status 500`), devolvida ao frontend, que exibe o `alert("Erro ao finalizar venda: ...")`. Nenhuma venda é persistida.

---

## 2. Fluxo atual (diagnóstico)

### 2.1 Frontend (cadeia de cálculo do total)

1. `cart.js::calcularSubtotalBruto()` soma `(preco_final || preco_venda_sugerido) * quantidade`.
2. `cart.js::calcularTotalDescontosItens()` soma `descontoValor` (ou `descontoPercentual` convertido).
3. `cart.js::calcularSubtotalAposDescontoItens()` = bruto − descontos dos itens.
4. `cart.js::calcularValorDescontoGlobal()` aplica o desconto global.
5. `cart.js::calcularTotalCarrinho()` = subtotal − desconto global + acréscimo.
6. Em `app.js::confirmarPedido()`, a soma dos campos de pagamento múltiplo é comparada com `calcularTotalCarrinho()` (validação local).
7. `order.js::prepararObjetoPedido()` serializa os itens com:
   - `preco_unitario: item.preco_final || item.preco_venda_sugerido`
   - `desconto_valor: item.descontoValor || 0`
   - `desconto_percentual: item.descontoPercentual || 0`
   - `quantidade: item.quantidade`

### 2.2 Backend (cadeia de cálculo do total)

Arquivo: `modules/api/controllers/PedidoController.php`, método `actionCreate`.

- Loop 1 (linhas 283–367): para cada item, calcula `$subtotalItem = $quantidadePedida * $precoUnitario`, converte percentual em valor quando necessário e acumula `$valorTotalVenda += ($subtotalItem - $descontoValor)`.
- Linha 384: `$subtotalItensAposDescontoItem = $valorTotalVenda;`
- Linhas 387–399: aplica desconto global e acréscimo.
- Linhas 401–419: se houver `pagamentos_multiplos`, soma os valores e compara com o total.

```php
if (abs(round($somaPagamentos, 2) - round($valorTotalVenda, 2)) > 0.01) {
    $msg = "A soma das formas de pagamento (R$ ...) não corresponde ao total da venda (...)";
    throw new Exception($msg); // linha 417
}
```

### 2.3 Onde está o não-finalizar

- A exceção é lançada na **linha 417**.
- O bloco `try` encerra com `$transaction->commit();` na **linha 608**.
- O `catch (Exception $e)` (linhas 630–633) faz `$transaction->rollBack();` e lança `ServerErrorHttpException('Erro ao processar pedido: ' . $e->getMessage())`.
- Resultado: `HTTP/1.1 500` → o frontend (`app.js::confirmarPedido`, bloco `catch`) exibe `alert("Erro ao finalizar venda: ...")`.

---

## 3. Causa raiz

Há **duas cadeias independentes de cálculo de total** (uma no frontend, outra no backend) e a validação de múltiplos pagamentos exige que elas coincidam. Quando divergem (mesmo em centavos), o pedido é rejeitado.

A divergência de **R$ 3,00** no subtotal de itens indica um (ou mais) dos seguintes fatores:

### Fator A — Desconto por escala/volume altera `preco_final` e `desconto_valor`
`cart.js::aplicarRegrasEscala()` reescreve `item.preco_final` e `item.descontoValor` conforme o motor de escala (`getPrecoVigente`). Esse motor não preserva o preço promocional (`em_promocao`/`preco_final` vindo da API) e pode deixar `descontoValor` vs `descontoPercentual` em estados que o total de tela (`calcularTotalCarrinho`) lê de forma diferente da serialização em `prepararObjetoPedido`.

### Fator B — Arredondamento diferente entre as cadeias
- Frontend arredonda `desconto` em `aplicarRegrasEscala` (`Math.round(x*100)/100`).
- Backend **não arredonda** a contribuição de cada item; acumula `float` cru e só compara no final com `round(..., 2)`.
- Preços fracionários × quantidades fracionárias (ex.: venda fracionada `0.1`) geram dízimas que acumulam resíduos.

### Fator C — Estado dessincronizado (DOM vs módulo do carrinho)
`app.js::confirmarPedido()` lê desconto global/acréscimo novamente do DOM e de `getDescontoGlobal()/getAcrescimo()`; o carrinho é carregado/re-sincronizado do IndexedDB (`storage.js::carregarCarrinho()`). Um item cujo `descontoValor`/`preco_final` ficou obsoleto entre renderizações pode produzir total de tela diferente do payload serializado.

### Fator D — Ausência de total explícito do frontend
O frontend **não envia um `valor_total`** autoritativo, apenas `itens` + `pagamentos_multiplos`. O backend é obrigado a **re-derivar** o total. Qualquer diferença de interpretação vira erro, em vez de ser reconciliada.

---

## 4. Plano de ajustes e implementação

As correções abaixo são independentes; recomenda-se aplicar todas, na ordem.

### Correção 1 — Unificar o arredondamento por item (BACKEND)
**Arquivo:** `modules/api/controllers/PedidoController.php`
**Local:** Loop 1 (próximo da linha 354).

Alterar a acumulação de
```php
$valorTotalVenda += ($subtotalItem - $descontoValor);
```
para arredondar cada item a 2 casas:
```php
$valorTotalVenda = round($valorTotalVenda + $subtotalItem - $descontoValor, 2);
```
E no cálculo do desconto percentual (linha 345):
```php
$descontoValor = round($subtotalItem * ($descontoPercentual / 100), 2);
```

**Objetivo:** eliminar resíduos de ponto flutuante para coincidir com o arredondamento que o frontend já faz.

### Correção 2 — Arredondar item no frontend (FRONTEND)
**Arquivo:** `web/venda-direta/js/cart.js`
**Local:** `calcularSubtotalBruto()` / `calcularTotalDescontosItens()`.

Arredondar a contribuição de cada item a 2 casas antes de acumular (e manter coerência com `prepararObjetoPedido`). Exemplo:
```js
return carrinho.reduce((total, item) => {
  const preco = parseFloat(item.preco_final || item.preco_venda_sugerido || 0);
  const qtd = parseFloat(item.quantidade || 0);
  const subtotal = Math.round(preco * qtd * 100) / 100;
  return total + subtotal;
}, 0);
```
Fazer o mesmo no cálculo dos descontos em `calcularTotalDescontosItens()`.

### Correção 3 — Preservar preço promocional e desconto consistente no motor de escala (FRONTEND)
**Arquivo:** `web/venda-direta/js/cart.js`
**Local:** `aplicarRegrasEscala()`.

- Não sobrescrever `item.preco_final` com `preco_venda_sugerido` sem antes considerar `preco_final` promocional.
- Garantir que, ao final, `preco_unitario` serializado e `desconto_valor` resultem exatamente no mesmo total que `calcularTotalCarrinho()`.
- Documentar/ajustar a regra para que `preco_final` sempre seja o valor efetivamente cobrado por unidade e `desconto_valor` o abatimento necessário para chegar ao preço escalonado/promocional.

### Correção 4 — Enviar `valor_total` autoritativo e reconciliar no backend (RECOMENDADA)
**Arquivo frontend:** `web/venda-direta/js/order.js` → `prepararObjetoPedido()`
**Arquivo backend:** `modules/api/controllers/PedidoController.php`

1. Frontend calcula o total a partir dos **itens já serializados** (mesma função usada para montar o payload) e envia `valor_total`.
2. Backend, quando `pagamentos_multiplos` estiver presente, passa a **confiar no total do frontend** como limite mínimo de referência ou usa `valor_total` para a comparação, em vez de apenas re-derivar:
   ```php
   $totalReferencia = isset($data['valor_total_front']) && $data['valor_total_front'] > 0
       ? (float)$data['valor_total_front']
       : $valorTotalVenda;
   ```
3. Manter a validação, mas comparar `somaPagamentos` com `$totalReferencia`.

> Isso reduz a classe de erro a zero, porque pagamento e total passam a usar **a mesma referência** gerada pelo frontend.

### Correção 5 — Melhorar resposta de erro para diagnóstico (BACKEND)
**Arquivo:** `modules/api/controllers/PedidoController.php`, linhas 411–418.

Incluir no log/payload o detalhamento item a item (preço, quantidade, desconto, subtotal) para que, se houver nova divergência, seja possível identificar o item exato. Exemplo:
```php
foreach ($itensFiltrados as $i => $it) {
    Yii::error("Item #{$i}: {$it['nome']} qtd={$it['quantidade']} preco={$it['preco_unitario']} desc={$it['desconto_valor']}", 'api');
}
```

### Correção 6 — Validação de pagamentos múltiplos com UX clara (FRONTEND)
**Arquivo:** `web/venda-direta/js/app.js` → `confirmarPedido()` (linha 2032).

- Antes de enviar, comparar a soma dos pagamentos com o **mesmo total que será serializado** (e não apenas com `calcularTotalCarrinho()`).
- Se divergir, exibir alerta com os dois valores para o operador corrigir no próprio modal.

---

## 5. Por que a venda não finaliza (resumo direto)

1. A validação de `pagamentos_multiplos` roda dentro da transação, antes de `save()`/`commit()`.
2. A soma enviada (R$ 175,00) difere do total recalculado pelo backend (R$ 178,00) em R$ 3,00 no subtotal de itens.
3. A diferença lança `Exception` → `rollBack()` → `ServerErrorHttpException` → `HTTP 500`.
4. O frontend intercepta o 500 e exibe o `alert`, sem persistir a venda.

---

## 6. Roteiro de implementação sugerido

- [ ] Aplicar Correção 1 (round por item no backend).
- [ ] Aplicar Correção 2 (round por item no frontend).
- [ ] Aplicar Correção 3 (coerência do motor de escala/promoção).
- [ ] Aplicar Correção 4 (enviar/referenciar `valor_total` do frontend).
- [ ] Aplicar Correção 5 (log detalhado de itens na divergência).
- [ ] Aplicar Correção 6 (validação de UX antes do envio).
- [ ] Testar cenários: venda com múltiplos pagamentos exata; com desconto por item; com desconto global; com acréscimo; e com venda fracionada.
- [ ] Confirmar que a soma de pagamentos aceita a igualdade e que a venda é salva/confirmada corretamente.