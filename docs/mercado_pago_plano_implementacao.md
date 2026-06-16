# Análise e Plano de Implementação: API do Mercado Pago

## 1. O que já foi implementado

### Frontend (`web/catalogo` e `web/venda-direta`)

- **Configuração Compartilhada**: Ambos carregam a configuração da API (`gateway_pagamento`, `mercadopago_public_key`, etc) através do endpoint `/api/usuario/config`.
- **Integração do Checkout Pro**: Ao finalizar a compra, se o gateway ativo for `mercadopago`, o sistema faz um POST para o endpoint `/api/mercado-pago/criar-preferencia`.
- **Redirecionamento**: Em caso de sucesso na geração da preferência, o frontend redireciona o usuário para a URL de checkout hospedada pelo Mercado Pago (`init_point` ou `sandbox_init_point`).

### Backend (`modules/api/controllers/MercadoPagoController.php`)

- **OAuth e Conexão de Contas**: Rotas `connect-url` e `oauth-callback` prontas para vincular a conta do lojista (Tenant) e salvar o `access_token` e `refresh_token` na tabela `prest_usuarios`.
- **Geração de Preferências (Checkout Pro)**: `actionCriarPreferencia` recebe os itens e cria a preferência no Mercado Pago. Ele gera uma `external_reference` e atrela os metadados (incluindo `usuario_id`). Também salva os dados da preferência localmente na tabela `mercadopago_preferencias`.
- **Webhooks**: O método `actionWebhook` já foi estruturado e processa notificações do tipo `payment`, realizando a baixa de estoque, criação de registro financeiro de fee (platform fee) e criação de um pedido.

---

## 2. O que falta (Lacunas Identificadas)

1.  **Resolução Dinâmica de Tokens no Webhook**:
    - No `actionWebhook`, existe uma falha de lógica descrita em comentários: a notificação envia o `paymentId`, mas o webhook não recebe de imediato o `external_reference` via request primário no evento de notificação. O sistema atual tenta usar os tokens de _todos_ os lojistas cadastrados com MP ativo iterativamente até encontrar o correto (`PaymentClient()->get($paymentId)`). Isso é ineficiente e será problemático em escala com dezenas de clientes ativos.

2.  **Repasse dos Metadados da Venda Direta**:
    - O frontend da Venda Direta possui parâmetros próprios cruciais: `colaborador_vendedor_id`, `numero_parcelas`, `intervalo_dias_parcelas`, que são enviados em `actionCriarPreferencia`.
    - Durante a aprovação de uma Venda Direta através do Webhook, o `actionWebhook` e o método `criarPedidoNoSistema` não estão utilizando esses `dados_request` da preferência para associar o Pedido final ao Vendedor de forma efetiva, nem mesmo controlando pagamentos parcelados e estipulando os vencimentos.

3.  **Páginas de Retorno do Mercado Pago (Frontend)**:
    - As URLs configuradas de retorno (`back_urls`) são processadas como `payment-success.html`, mas aparentemente estas páginas ainda não estão criadas no diretório respectivo para interagir com a API e limpar o carrinho baseada nesse retorno de sucesso do MP.

---

## 3. Plano de Implementação Ordenado por Prioridade

### 🔥 Prioridade 1 (Alta/Crítica): Correção do Fluxo de Webhooks (Backend)

**Justificativa**: Sem receber o webhook de maneira eficiente, as notificações terão delay ou sofrerão time-out rodando por todos os tokens, não baixando do estoque. O correto é injetar uma variável que indique quem é o usuário responsável.
**Plano**:

- Modificar o método `actionCriarPreferencia` para injetar o parâmetro `tenant_id` na URL padrão de notificação: `"notification_url" => "{$baseUrl}/index.php/api/mercado-pago/webhook?tenant_id={$usuario['id']}"`.
- Alterar `actionWebhook` para verificar primeiramente `$_GET['tenant_id']` antes de decidir iterar pela base global de lojistas, assegurando uma consulta leve e concisa (complexidade O(1)).

### ⭐ Prioridade 2 (Alta): Assinatura de Persistência na Venda Direta (Backend)

**Justificativa**: O rastreio entre Pedido de Venda e Colaborador/Vendedor precisa refletir na conversão do pagamento Mercado Pago em Pedido/Quitada.
**Plano**:

- Editar o método `criarPedidoNoSistema` no controller `MercadoPagoController.php`.
- Extrair dos `dados_request` (salvos em `mercadopago_preferencias`) os campos remanescentes (`colaborador_vendedor_id`, `numero_parcelas` e observações financeiras/de venda avulsa).
- Atualizar o método `criarPedido` (e os modelos pertinentes, se não aceitarem na query direta) para absorver o `colaborador_vendedor_id` e salvar junto ao pedido.

### 🟡 Prioridade 3 (Média): Criação das Páginas de Retorno no Frontend

**Justificativa**: Concluir o loop visual do Checkout e informar sucesso ou recusado.
**Plano**:

- Criar a página de confirmação em `web/catalogo/payment-success.html`.
- Criar a página de confirmação em `web/venda-direta/payment-success.html`.
- Garantir que nesses scripts ocorra a validação do `payment_id` via URL ou Web-Storage, e haja a chamada da função que deleta o carrinho local (e/ou avisa a API via `PEDIDO_CONFIRMAR_RECEBIMENTO`).

### 🔵 Prioridade 4 (Baixa): Polling opcional no Frontend e Trato de Falhas

**Plano**:

- Caso a notificação do Mercado Pago por algum motivo não chegue, fornecer uma tela de carregamento que verifique (polling) se a `preferencia_local_id` já mudou de status em consultas periódicas. Essa lógica já está iniciada via função no Gateway (se houver PIX), precisando checar retornos de cartões de crédito.
