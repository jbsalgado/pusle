# Documento de Impacto e Viabilidade: Acréscimo na Venda Direta

**Data:** 24/12/2025
**Solicitante:** Usuário
**Objetivo:** Adicionar funcionalidade de acréscimo (Frete, Taxas, etc.) sobre o valor total da venda no modal "Meu Carrinho" da Venda Direta.

---

## 1. Visão Geral da Mudança

O usuário poderá,antes de finalizar a venda, informar um valor de acréscimo e selecionar o motivo. Esse valor será somado ao total da venda e registrado no pedido e no comprovante.

**Campos Requisitados:**

1.  **Valor do Acréscimo** (R$): Input numérico.
2.  **Motivo** (Select): FRETE, VENDA CARTÃO CREDITO, VENDA CARTÃO DEBIDO, VENDA BOLETO, VENDA CARNE, OUTROS.
3.  **Observação** (Texto): Descritivo do motivo (opcional ou obrigatório para "Outros").

---

## 2. Impacto Técnico

### 2.1. Banco de Dados (PostgreSQL)

A tabela `prest_vendas` precisará de novas colunas para armazenar esses dados estruturados, separando-os do valor dos itens.

- **Alteração Necessária:** Criar Migration.
- **Colunas Sugeridas:**
  - `acrescimo_valor` (Decimal 10,2) Default 0.
  - `acrescimo_tipo` (String) - Enum ou Texto (Ex: 'FRETE').
  - `observacao_acrescimo` (Text) - Detalhes específicos.

### 2.2. Backend (Yii2 / PHP)

- **Model `Venda`**: Adicionar regras de validação para os novos campos.
- **Controller `PedidoController::actionCreate`**:
  - Receber os campos `acrescimo` do JSON.
  - Validar se o valor é positivo.
  - **Cálculo do Total**: Atualizar a lógica `valor_total = soma(itens) + acrescimo_valor`.
  - Garantir que as parcelas e o caixa considerem esse novo total.

### 2.3. Frontend (Link/PWA)

- **Modal Carrinho (`index.html`)**:
  - Adicionar seção "Acréscimos" no rodapé do carrinho, antes do botão finalizar.
  - Campos: Input Valor, Select Motivo, Input Texto Observação.
- **Lógica (`app.js` / `cart.js`)**:
  - Atualizar o cálculo visual do total (`Total Itens + Acréscimo = Total Final`).
  - Validar input (não permitir negativo).
  - Incluir dados no payload enviado para `/api/pedido`.
- **Comprovante (`pix.js`)**:
  - Exibir linha de "Acréscimo" no resumo financeiro do recibo.
  - Exibir observação do acréscimo se houver.

---

## 3. Viabilidade e Riscos

- **Viabilidade**: **Alta**. A estrutura atual suporta a adição desses campos sem quebrar a lógica existente de itens.
- **Riscos**:
  - _Duplicidade de Taxas_: Se o usuário já aplica uma "taxa invisível" no preço do produto, pode haver duplicidade.
  - _Parcelamento_: O acréscimo será diluído nas parcelas. (Comportamento padrão aceitável).
  - _Comissões_: O acréscimo entra na base de cálculo de comissão? (Definir regra. Padrão: Sim, pois aumenta o faturamento, exceto Frete).

---

## 4. Plano de Execução

1.  **Migration**: Criar colunas na tabela `prest_vendas`.
2.  **Backend**: Ajustar `Venda.php` e `PedidoController.php`.
3.  **Frontend**: Modificar HTML do modal e JS de cálculo.
4.  **Teste**: Verificar fluxo completo (Venda -> Banco -> Caixa -> Recibo).

**Aprovação:** Aguardando confirmação do usuário para iniciar.
