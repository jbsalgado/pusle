# 🌐 Guia Operacional Multi-Tenant de Integrações: Pulse ERP & SaaS

Este manual consolida a operação técnica e comercial da plataforma **Pulse ERP (SaaS)** e dos **Lojistas (Tenants)**, detalhando como funcionam a arquitetura, as configurações, a segurança e a operação diária das integrações com **Marketplaces (Mercado Livre, Shopee, Magazine Luiza, Temu e iFood)** e **Gateway de Pagamentos (Mercado Pago)**.

---

## 🧭 1. Arquitetura Multi-Tenant e Modelo Operacional

O Pulse ERP adota um modelo de **Hub Central Integrador**, onde a separação entre a plataforma e os lojistas é rigorosamente respeitada:

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                          PULSE ERP - PLATAFORMA SAAS (ADMIN)                           │
│  • Cadastro único nos Portais de Desenvolvedor (App Mestre Integrador)                 │
│  • Gerenciamento das Credenciais Mestres (.env da VPS: Client IDs / Secrets)          │
│  • Endpoint Unificado de Webhooks com Fast-ACK (<100ms) e Fila Assíncrona Systemd     │
└───────────────────────────┬────────────────────────────────┬───────────────────────────┘
                            │                                │
             ┌──────────────┴─────────────┐    ┌─────────────┴──────────────┐
             │    LOJA 1 (Tenant Alex)    │    │    LOJA 2 (Tenant Moda)    │
             │ • CNPJ: 11.111.111/0001-11 │    │ • CNPJ: 22.222.222/0001-22 │
             │ • Conta Mercado Pago       │    │ • Conta Mercado Pago       │
             │ • Vendedor Mercado Livre   │    │ • Vendedor Mercado Livre   │
             │ • Loja Shopee Oficial      │    │ • Loja Shopee Oficial      │
             │ • Estoque & Preços com +16%│    │ • Estoque & Preços com +12%│
             └────────────────────────────┘    └────────────────────────────┘
```

### Princípios Fundamentais:
1. **Isolamento Total por `usuario_id` (UUID):** Todas as tabelas de integração (`prest_marketplace_config`, `prest_marketplace_produto`, `prest_marketplace_pedido`, `prest_marketplace_pedido_item`, `prest_marketplace_sync_log`) possuem a chave estrangeira do lojista. A Loja A **jamais** tem acesso a produtos, pedidos ou credenciais da Loja B.
2. **Contas Próprias por Lojista (Obrigatório por Lei):**
   - **Tributário / Fiscal:** Toda NF-e emitida para os pedidos deve conter o CNPJ e a Inscrição Estadual da respectiva loja.
   - **Financeiro:** O repasse dos valores das vendas cai diretamente na conta bancária/digital cadastrada pelo lojista no marketplace. O SaaS não faz custódia de valores transacionais de terceiros, eliminando riscos de bitributação ou exigências de instituição financeira (Bacen).
   - **Logística:** As etiquetas oficiais (Mercado Envios, Shopee Xpress, Magalu Entregas) utilizam o endereço de postagem do CD/loja do tenant.

---

## 🔑 2. Matriz de Credenciais e Responsabilidades

| Canal | Tipo de Conexão | O que o SaaS (Admin) configura | O que o Lojista (Tenant) faz |
| :--- | :--- | :--- | :--- |
| **Mercado Pago** | OAuth 2.0 / Gateway | `MP_APP_ID`, `MP_CLIENT_SECRET` no `.env` | Clica em *"Conectar com Mercado Pago"* e autoriza no popup oficial. |
| **Mercado Livre** | OAuth 2.0 (App Meli) | `App ID`, `Secret Key`, Redirect URI e Webhook URL | Clica em *"Conectar Mercado Livre"*, faz login e concede permissão à aplicação do Pulse. |
| **Shopee** | HMAC-SHA256 + OAuth v2 | `Partner ID`, `Partner Key` no Shopee Open Platform | Clica em *"Conectar Shopee"*, seleciona o país Brasil e autoriza a loja (`shop_id`). |
| **Magazine Luiza** | IntegraCommerce / LuizaLabs | Homologação de Parceiro Integrador ou API Key Direta | Gera o **Token de API / API Key** no painel Magalu Seller e cola no Pulse. |
| **Temu** | L2L Brasil (Open API MD5) | `App Key`, `App Secret` regional Brasil | Fornece o Token de Vendedor Local gerado no portal Temu Seller L2L. |
| **iFood** | Merchant API V2 | `Client ID`, `Client Secret` no Portal iFood Developer | Informa o seu `Merchant ID` e clica em autorizar o acesso da plataforma. |

---

## 📦 3. Especificações Detalhadas por Canal

### 3.1 Mercado Pago (Gateway de Pagamentos & PDV)
* **Objetivo:** Recebimento direto de vendas online, PIX instantâneo, boletos e maquininhas Point integradas ao caixa do PDV.
* **Rotas Técnicas:**
  - **Redirect URI OAuth:** `https://seusite.com.br/api/mercado-pago/oauth-callback`
  - **Webhook IPN:** `https://seusite.com.br/api/mercado-pago/webhook`
* **Fluxo Operacional:**
  1. O admin cadastra a aplicação no [Mercado Pago Developers](https://www.mercadopago.com.br/developers/panel/app).
  2. O lojista acessa **Configurações > Pagamentos > Mercado Pago** e clica em conectar.
  3. Os tokens são armazenados com segurança em `prest_usuarios` (`mp_access_token`, `mp_refresh_token`).
  4. Para maquininhas físicas (Point Smart/Pro), os terminais são lidos via `/v1/devices` e selecionados no PDV.

---

### 3.2 Mercado Livre (Marketplace)
* **Objetivo:** Sincronização de catálogo, atualização de estoque físico, precificação com markup dinâmico, download de etiquetas Mercado Envios e transmissão de NF-e.
* **Rotas Técnicas:**
  - **Redirect URI:** `https://seusite.com.br/marketplace/config/oauth-callback?marketplace=MERCADO_LIVRE`
  - **Webhook URL:** `https://seusite.com.br/marketplace/webhook/receive?marketplace=mercado-livre`
  - **Tópicos Obrigatórios:** `orders_v2`, `items`, `shipments`
* **Implementação Técnica ([MercadoLivreService.php](file:///srv/http/pulse/modules/marketplace/components/MercadoLivreService.php)):**
  - **Autenticação:** Troca de código de autorização em `https://api.mercadolibre.com/oauth/token`.
  - **Renovação de Token:** Automatizada via refresh token e comando cron:
    ```bash
    php /srv/http/pulse/yii marketplace/refresh-tokens
    ```
  - **Estoque com Variações:** Atualização via `PUT /items/{itemId}` ou `PUT /items/{itemId}/variations/{variationId}` com payload `{"available_quantity": N}`.
  - **Faturamento Fiscal:** Envio automático da chave de 44 dígitos e XML da Danfe via `POST /orders/{orderId}/fiscal_documents`.
  - **Etiquetas de Envio:** Download direto do PDF de postagem via `GET /shipment_labels?shipment_ids={shipmentId}&response_type=pdf`.

---

### 3.3 Shopee (Marketplace)
* **Objetivo:** Gestão de pedidos e estoque com a Shopee Open Platform API v2.
* **Rotas Técnicas:**
  - **Webhook URL:** `https://seusite.com.br/marketplace/webhook/receive?marketplace=shopee`
* **Implementação Técnica ([ShopeeService.php](file:///srv/http/pulse/modules/marketplace/components/ShopeeService.php)):**
  - **Assinatura Criptográfica:** Todas as requisições geram hash HMAC-SHA256 unindo `partner_id + path + timestamp + access_token + shop_id`.
  - **Validação de Webhook:** O cabeçalho `Authorization` recebido nos webhooks é validado contra a `partner_key` antes de processar qualquer pedido.
  - **Sincronização de Estoque:** Chamadas para `/api/v2/product/update_stock`.
  - **Gestão de Pedidos:** Busca de detalhes via `/api/v2/order/get_order_detail` e normalização para o DTO canônico.

---

### 3.4 Magazine Luiza / Magalu (Marketplace)
* **Objetivo:** Conexão com o ecossistema Magalu Marketplace via API IntegraCommerce/LuizaLabs.
* **Rotas Técnicas:**
  - **Endpoint Base:** `https://api.magazineluiza.com.br/v1`
  - **Webhook URL:** `https://seusite.com.br/marketplace/webhook/receive?marketplace=magalu`
* **Implementação Técnica ([MagaluService.php](file:///srv/http/pulse/modules/marketplace/components/MagaluService.php)):**
  - **Autenticação:** Header `Authorization: Bearer {token}`.
  - **Atualização de Estoque:** `PUT /products/{sku}/stock` com `{"quantity": N}`.
  - **Faturamento:** `POST /orders/{order_id}/invoice` enviando número da nota, série, chave de acesso e data de emissão.

---

### 3.5 Temu (Marketplace - Local-to-Local Brasil)
* **Objetivo:** Integração com vendedores brasileiros no programa Temu L2L (produtos despachados a partir de território nacional com envio ágil).
* **Rotas Técnicas:**
  - **Endpoint Base:** `https://open-api.temu.com`
  - **Webhook URL:** `https://seusite.com.br/marketplace/webhook/receive?marketplace=temu`
* **Implementação Técnica ([TemuService.php](file:///srv/http/pulse/modules/marketplace/components/TemuService.php)):**
  - **Assinatura MD5 Dinâmica:** Ordenação de parâmetros e hash MD5 com `app_secret`.
  - **Estoque Local:** Chamada para `/bg/goods/local/inventory/update` com `sku_id` e `available_quantity`.
  - **Confirmação de Envio:** Confirmação com chave Danfe e código de rastreamento do operador local.

---

### 3.6 iFood (Marketplace - Delivery & Mercado)
* **Objetivo:** Recebimento e processamento de pedidos para comércios de conveniência, alimentação e mercados.
* **Rotas Técnicas:**
  - **Endpoint Base:** `https://merchant-api.ifood.com.br/v1.0`
  - **Webhook URL:** `https://seusite.com.br/marketplace/webhook/receive?marketplace=ifood`
* **Implementação Técnica ([IFoodService.php](file:///srv/http/pulse/modules/marketplace/components/IFoodService.php)):**
  - **Autenticação:** Client Credentials OAuth2 (`/authentication/v1.0/oauth/token`).
  - **Eventos de Pedidos:** Processamento de status `PLACED` (Colocado), `CONFIRMED` (Confirmado), `DISPATCHED` (Despachado) e `CANCELLED` (Cancelado).
  - **Estoque / Disponibilidade:** Atualização de status de itens no catálogo (`AVAILABLE` / `UNAVAILABLE`).

---

## ⚡ 4. Mecanismo de Ingestão de Webhooks (Fast-ACK) e Filas

Para garantir alta escalabilidade e nunca sofrer penalizações por timeout dos marketplaces:

1. **Recepção em < 100ms ([WebhookController.php](file:///srv/http/pulse/modules/marketplace/controllers/WebhookController.php)):**
   - O controlador recebe o JSON bruto e headers da requisição.
   - Identifica o `seller_id` ou `shop_id` e localiza a conta específica em `prest_marketplace_config`.
   - Valida a assinatura de autenticidade (HMAC-SHA256 ou token).
   - Enfileira a tarefa assíncrona no Yii2 Queue e responde imediatamente com `HTTP 200 OK`.
2. **Processamento em Segundo Plano ([ProcessarWebhookJob.php](file:///srv/http/pulse/modules/marketplace/jobs/ProcessarWebhookJob.php)):**
   - Executado pelo worker gerenciado pelo Systemd (`pulse-queue.service`).
   - Converte os dados do pedido no `MarketplaceOrderDTO`.
   - Invoca o [OrderEventProcessor.php](file:///srv/http/pulse/modules/marketplace/components/OrderEventProcessor.php).
   - Registra o cliente em `prest_clientes` se não existir.
   - Gera a venda em `prest_vendas` e os itens em `prest_venda_itens`.
   - Executa a baixa atômica de estoque local em `prest_produtos`.
   - Dispara o job [SyncEstoqueMarketplaceJob.php](file:///srv/http/pulse/modules/marketplace/jobs/SyncEstoqueMarketplaceJob.php) para atualizar imediatamente todos os outros canais conectados.

---

## 🛠️ 5. Checklist Operacional de Implantação

### 🔹 Para a Equipe do Pulse (SaaS Admin):
- [ ] Criar e homologar as aplicações nos portais de desenvolvedor:
  - [Mercado Pago Developers](https://www.mercadopago.com.br/developers/panel/app)
  - [Mercado Livre Developers](https://developers.mercadolibre.com.br/devcenter)
  - [Shopee Open Platform](https://open.shopee.com/)
  - [Magalu Seller](https://seller.magazineluiza.com.br)
  - [Temu Open Platform](https://open-api.temu.com)
  - [iFood Developer](https://developer.ifood.com.br)
- [ ] Inserir os `Client IDs` e `Client Secrets` mestres no arquivo `.env` da VPS.
- [ ] Configurar os endpoints oficiais de Webhook e Redirect URI em cada portal.
- [ ] Ativar e verificar os serviços do Systemd:
  ```bash
  systemctl status pulse-queue.service
  ```
- [ ] Configurar os comandos de rotina no Crontab do servidor:
  ```cron
  # Renovação preventiva de tokens OAuth (a cada 15 min)
  */15 * * * * php /srv/http/pulse/yii marketplace/refresh-tokens > /dev/null 2>&1

  # Sincronização periódica de pedidos de contingência (a cada 2 horas)
  0 */2 * * * php /srv/http/pulse/yii marketplace/sync-orders > /dev/null 2>&1
  ```

### 🔹 Para os Lojistas (Tenants):
- [ ] Ter conta jurídica ativa com CNPJ validado em cada marketplace desejado.
- [ ] Acessar o painel **Marketplaces** no Pulse ERP.
- [ ] Clicar no botão de conexão do marketplace desejado e autorizar a integração.
- [ ] Definir a margem de markup de preço para compensar as comissões de cada canal (ex: +16% no ML, +14% na Shopee).
- [ ] Realizar o vínculo de anúncios existentes ou publicar o catálogo do Pulse.
- [ ] Ativar a sincronização automática de estoque e pedidos.

---

## 📈 6. Resumo dos Ajustes Realizados e Próximos Passos

1. **Ajustes Concluídos:**
   - Padronização do isolamento estrito sem fallbacks inseguros entre lojas.
   - Suporte a variações de produtos (tamanhos, cores, voltagens) no estoque do Mercado Livre e Shopee.
   - Ingestão unificada de webhooks com processamento em fila resiliente.
   - Envio automático de dados fiscais (NF-e) para faturamento nos canais.
2. **Próximas Entregas Planejadas:**
   - Expansão do mapeador visual de atributos obrigatórios por categoria (NCM, Marca, Modelo).
   - Sincronização bidirecional de mensagens e perguntas do Mercado Livre e Shopee diretamente no módulo de atendimento do Pulse.
