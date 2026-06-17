# Plano de Implementação: Impressão de Observações no Comprovante de Orçamento (Impressora Térmica)

## Análise do Atual Cenário

Após analisar o fluxo de impressão de orçamentos no módulo `web/orcamento` do PULSE, identificou-se como o sistema lida com a geração do comprovante de orçamento atualmente. A interface de orçamentos permite duas formas de impressão: a impressão "comum" (gerada via HTML e convertida em A4/janela de impressão do navegador) e a impressão "térmica" (um texto puramente em formato de string, de 32 a 48 colunas, que é enviado para o aplicativo parceiro de impressão via _Deep Link_).

### O que já está implementado

- **Impressão Comum (HTML):**
  - Em `web/js/orcamento-list.js` (função `_gerarHTMLComprovanteContent`), o comprovante em HTML **já renderiza** corretamente as observações gerais do pedido usando a verificação `dados.observacoes`.
  - Da mesma forma, em `web/orcamento/js/pix.js` (função `gerarComprovanteOrcamento`), caso seja gerado um comprovante HTML (como a conversão para _canvas_/PNG), o bloco HTML possui um condicional para renderizar `dadosPedido.observacoes`.
- **Impressão Térmica (Texto Direto):**
  - Em `web/orcamento/js/pix.js`, o método que gera apenas o texto (função `gerarTextoComprovante`) **já possui** o código para adicionar `dadosPedido.observacoes` antes das saudações finais, traduzindo o campo para uma versão sem acentos e em caixa alta.

### O que pode e deve ser melhorado (Gaps Encontrados)

1. **Ausência Absoluta na Listagem Geral (`orcamento-list.js`):**
   A função `_gerarTextoComprovanteContent`, localizada em `web/js/orcamento-list.js` (que atende o dashboard de listagem de orçamentos e gera cupons para a maquininha/impressora bluetooth através de _Deep Link_), **não possui o bloco de inclusão das observações do orçamento**. O layout pula direto da Forma de Pagamento para o Rodapé.
2. **Falta de Inclusão das Observações Por Item:**
   O backend permite que cada item do orçamento tenha uma anotação (`item.observacoes` - tratado na API no arquivo `modules/api/controllers/OrcamentoController.php` na linha 90). No entanto, nem o recibo térmico nem o recibo comum estão exibindo essas observações descritivas de cada item de forma clara.

---

## Proposta de Mudanças

As seguintes atualizações serão feitas no código fonte:

### 1. `web/js/orcamento-list.js`

Inclusão do bloco de observações gerais no recibo térmico.

```javascript
// Localizar a função _gerarTextoComprovanteContent() e inserir antes da seção "Rodapé":

if (dadosOrcamento.observacoes) {
  texto += linhaSeparadora + "\n";
  texto += "OBSERVACOES:\n";
  texto += removerAcentos(dadosOrcamento.observacoes).toUpperCase() + "\n";
}
```

Recomendação de melhoria: Exibir as observações também dentro do loop dos `carrinho.forEach(item => ...)` no mesmo script para mostrar anotações específicas por item na térmica.

### 2. `web/orcamento/js/pix.js`

Como já possui a lógica de observação geral para térmica, precisaremos melhorar a observação de item, caso o cliente passe alguma customização por produto.

```javascript
// Localizar a varredura do carrinho em gerarTextoComprovante() e HTML principal:
// Adicionar logica de pular linha caso o campo "item.observacoes" ou "item.observacao" venha preenchido

if (item.observacoes) {
  const obs = removerAcentos(item.observacoes).toUpperCase();
  texto += " Obs: " + obs + "\n";
}
```

---

## Plano de Verificação

### Verificação Manual

1. **Ambiente Web:**
   - Acesse a interface web listagem de orçamentos (menu/Dashboard Web).
   - Inicie a criação de um novo orçamento contendo uma `observação geral` específica (ex: "Entregar pela porta dos fundos") e `observações de item` (ex: "Cor preta apenas").
   - Finalize e direcione para a impressão.
2. **Impressão Desktop/Comum:**
   - Clique em **Imprimir Normal** e verifique no preview do navegador se a observação geral ("Entregar pela porta...") consta logo abaixo da totalização.
3. **Impressão Térmica / Deep Link:**
   - Execute a ação "Imprimir Térmica".
   - (Se não houver app na máquina, pode-se interceptar o link `printapp://print?data=...` verificando a URL gerada e decodificando via `decodeURIComponent` em console para confirmar se `OBSERVACOES` estão presentes na string).
