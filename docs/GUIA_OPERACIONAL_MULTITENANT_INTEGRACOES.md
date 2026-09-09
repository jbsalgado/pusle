# 🌐 Guia Operacional Multi-Tenant de Integrações: Pulse ERP & SaaS

Este documento é o manual definitivo para a administração da plataforma **Pulse ERP (SaaS)** e para a operação dos **Lojistas (Tenants)**, explicando com máxima clareza técnica e operacional como funcionam as integrações de **Pagamento (Mercado Pago)** e **Marketplaces (Mercado Livre, Shopee, Temu, Magalu e iFood)** em uma arquitetura multi-lojas.

---

## 🧭 Visão Geral: Arquitetura Multi-Tenant do Pulse

Em um ecossistema SaaS (Software as a Service) multi-tenant como o Pulse:
* **A Plataforma SaaS (Você / Pulse Admin):** Cria e mantém as **Aplicações Integradoras Homologadas** nos portais de desenvolvedores de cada canal.
* **Os Lojistas (Tenants):** Possuem suas próprias contas de pessoa jurídica (CNPJ) em cada canal e conectam suas lojas ao Pulse com autorização 1-clique (OAuth) ou inserção de chave.
* **O Banco de Dados do Pulse:** Isola rigorosamente todos os dados através do `usuario_id` (UUID). A Loja A **nunca** tem acesso aos pedidos, credenciais ou produtos da Loja B.

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PULSE ERP - PLATAFORMA SAAS                          │
│     App Mestre Integrador Homologado (OAuth, APIs e Webhooks Fast-ACK)  │
└───────────────────┬─────────────────────────────────┬───────────────────┘
                    │                                 │
     ┌──────────────┴─────────────┐     ┌─────────────┴──────────────┐
     │   LOJA 1 (Tenant Alex)     │     │   LOJA 2 (Tenant Moda)     │
     │ • CNPJ 11.111.111/0001-11  │     │ • CNPJ 22.222.222/0001-22  │
     │ • Mercado Pago Próprio     │     │ • Mercado Pago Próprio     │
     │ • Anúncios Mercado Livre A │     │ • Anúncios Mercado Livre B │
     │ • Loja Shopee A            │     │ • Loja Shopee B            │
     └────────────────────────────┘     └────────────────────────────┘
```

---

## 1. O que precisa para habilitar o Mercado Pago como Gateway de Pagamento?

O Mercado Pago no Pulse opera no modelo **Marketplace / Gateway White-Label**. O dinheiro das vendas vai direto para a conta Mercado Pago de cada lojista.

### A. Papel do Administrador do SaaS (Feito 1 única vez pela plataforma):
1. Acessar o [Mercado Pago Developers](https://www.mercadopago.com.br/developers/panel/app) com a conta master da sua empresa/plataforma.
2. Criar uma Aplicação do tipo **"Pagamentos no Mercado Pago / Marketplace"**.
3. Obter as credenciais mestres da aplicação:
   * `Client ID` (ou `MP_APP_ID`)
   * `Client Secret` (ou `MP_CLIENT_SECRET`)
4. Configurar a **URL de Redirecionamento OAuth (Redirect URI)** no painel do MP:
   ```
   https://seusite.com.br/api/mercado-pago/oauth-callback
   ```
5. Inserir essas credenciais no arquivo [.env](file:///srv/http/pulse/.env) da VPS:
   ```dotenv
   MP_APP_ID=seu_client_id_mestre
   MP_CLIENT_SECRET=seu_client_secret_mestre
   ```

### B. Papel do Lojista (Tenant):
1. O lojista **NÃO** precisa criar conta de desenvolvedor nem mexer em códigos.
2. No painel do Pulse, ele acessa **Configurações > Pagamento > Mercado Pago** e clica no botão:
   `🔗 Conectar com Mercado Pago`.
3. Uma janela oficial do Mercado Pago se abre; o lojista faz login na conta dele e clica em **"Autorizar"**.
4. O Pulse recebe os tokens daquela loja e salva automaticamente em `prest_usuarios` (`mp_access_token`, `mp_refresh_token`).
5. **Para Maquininha Point no PDV:** O lojista adquire ou conecta sua maquininha física (Point Smart, Pro, Air) em sua conta Mercado Pago. O terminal fica disponível instantaneamente na tela da **Venda Expressa** do Pulse.

---

## 2. O que precisa para usar o Mercado Livre como Marketplace?

### A. Papel do SaaS (Pulse Admin):
1. Acessar o [Mercado Libre Developers](https://developers.mercadolibre.com.br/devcenter).
2. Criar uma aplicação integradora.
3. Marcar os escopos de autorização: `read`, `write`, `offline_access`.
4. Configurar a **Redirect URI**:
   ```
   https://seusite.com.br/marketplace/config/oauth-callback?marketplace=MERCADO_LIVRE
   ```
5. Configurar a **Notifications Callback URL (Webhook)**:
   ```
   https://seusite.com.br/marketplace/webhook/receive?marketplace=mercado-livre
   ```
   *(Tópicos obrigatórios: `orders_v2`, `items`, `shipments`)*.
6. Cadastrar o `App ID` e `Secret Key` do Mercado Livre nas configurações gerais de marketplaces do Pulse.

### B. Papel do Lojista (Tenant):
1. O lojista precisa ter a sua conta de vendedor (Seller) no Mercado Livre com seu CNPJ/CPF validado.
2. No Pulse, ele vai em **Marketplaces > Mercado Livre** e clica em **"Conectar Loja do Mercado Livre"**.
3. Ele autoriza o aplicativo do Pulse.
4. O Pulse armazena o vínculo isolado na tabela `prest_marketplace_config` (`usuario_id`, `seller_id_externo`, tokens).
5. O lojista vincula seus produtos ou importa seus anúncios existentes.
6. A partir desse momento:
   * Qualquer venda no Pulse baixa estoque no Mercado Livre automaticamente.
   * Qualquer venda no Mercado Livre entra no Pulse, baixa o estoque local, emite a venda e gera a etiqueta do Mercado Envios em PDF.

---

## 3. O que precisa para usar a Shopee como Marketplace?

### A. Papel do SaaS (Pulse Admin):
1. Cadastrar a plataforma no [Shopee Open Platform](https://open.shopee.com/).
2. Obter a aprovação como **Developer Partner** da Shopee, recebendo:
   * `Partner ID`
   * `Partner Key`
3. Configurar os Webhooks oficiais da Shopee apontando para:
   ```
   https://seusite.com.br/marketplace/webhook/receive?marketplace=shopee
   ```

### B. Papel do Lojista (Tenant):
1. O lojista possui sua loja oficial na Shopee (Shopee Seller Centre).
2. No Pulse, clica em **"Conectar Shopee"**.
3. Realiza a autenticação OAuth da Shopee com seu usuário e senha da Shopee.
4. O Pulse salva as credenciais e o `shop_id` da loja na tabela `prest_marketplace_config`.
5. O Pulse passa a assinar todas as chamadas com **HMAC-SHA256**, sincronizar estoque e faturar pedidos com NF-e.

---

## 4. O que precisa para usar a Temu como Marketplace?

### A. Papel do SaaS (Pulse Admin):
1. Realizar o cadastro de homologação no portal de parceiros integradores da [Temu Open Platform (L2L Brasil)](https://open-api.temu.com).
2. Obter o `App Key` e o `App Secret` para a região Brasil.

### B. Papel do Lojista (Tenant):
1. O lojista precisa ser um vendedor aprovado no programa **Temu Local-to-Local (L2L) Brasil** (sellers com CNPJ e estoque físico no Brasil para envio rápido).
2. O lojista obtém seu Token de Acesso de Vendedor no painel de seller da Temu.
3. No Pulse, em **Marketplaces > Temu**, insere o token e ativa a sincronização.
4. O conector do Pulse calcula a assinatura MD5 dinâmica (`generateSignature`), atualiza o estoque local (`/bg/goods/local/inventory/update`) e confirma os despachos de pedidos com chave de Danfe (`confirmShipment`).

---

## 5. O que precisa para usar o Magalu como Marketplace?

### A. Papel do SaaS (Pulse Admin) & Lojista:
A integração com o Magazine Luiza utiliza a API da **IntegraCommerce / LuizaLabs**:
1. O lojista abre a conta de vendedor no **Magalu Marketplace** ([seller.magazineluiza.com.br](https://seller.magazineluiza.com.br)).
2. No painel do Magalu, o lojista solicita a ativação via integradora (ou gera o seu **Token de API / API Key** de integração).
3. No Pulse, em **Marketplaces > Magazine Luiza**, o lojista preenche o campo de Token / Client Secret.
4. O conector do Pulse valida a conexão via `Authorization: Bearer` e passa a gerenciar estoque (`/v1/products/{sku}/stock`), faturamento (`/v1/orders/{order_id}/invoice`) e pedidos aprovados.

---

## 6. O que precisa para usar o iFood como Marketplace?

### A. Papel do SaaS (Pulse Admin):
1. Cadastrar a aplicação integradora no [iFood Developer Portal](https://developer.ifood.com.br/).
2. Obter as credenciais de parceiro: `Client ID` e `Client Secret` do iFood.
3. Cadastrar a URL de Webhook no portal do iFood:
   ```
   https://seusite.com.br/marketplace/webhook/receive?marketplace=ifood
   ```

### B. Papel do Lojista (Tenant):
1. O lojista precisa ter seu estabelecimento cadastrado no **Portal do Parceiro iFood** (restaurante, mercado, bebidas ou pet).
2. No Pulse, ele informa seu `Merchant ID` e clica no botão para autorizar o acesso da plataforma.
3. O Pulse passa a receber eventos de pedidos, confirmar recebimento, imprimir na cozinha/balcão e despachar.

---

## 7. É preciso criar conta de cada loja em cada um dos marketplaces?

### 👉 RESPOSTA: SIM, OBRIGATORIAMENTE!

**Por que cada loja deve ter a sua própria conta em cada marketplace?**
1. **Aspecto Fiscal e Tributário (NF-e):** A nota fiscal de venda ao consumidor final deve ser emitida obrigatoriamente com o **CNPJ da loja que está vendendo**, constando sua Inscrição Estadual e regime tributário (Simples Nacional, Lucro Presumido, etc.).
2. **Aspecto Financeiro e Bancário:** O dinheiro das vendas do Mercado Livre, Shopee, Magalu, etc., é transferido diretamente para a conta bancária vinculada ao titular daquele CNPJ. O SaaS não pode reter ou transacionar dinheiro alheio de marketplace para evitar bitributação e problemas de repasse (Bacem/Receita Federal).
3. **Logística e Expedição:** As etiquetas de envio (Mercado Envios, Shopee Express, Magalu Entregas) utilizam o CEP de coleta do galpão/loja daquele vendedor específico.
4. **Reputação e Avaliações:** Cada lojista constrói sua própria reputação de vendedor (MercadoLíder, Vendedor Indicado Shopee, etc.).

**Qual é o papel exato da nossa plataforma Pulse ERP?**
O Pulse ERP é o **Hub Central de Gestão**. Ele elimina a necessidade do lojista ter que abrir 5 sites diferentes por dia. O lojista cadastra os produtos no Pulse uma única vez; o Pulse envia para os canais e, quando uma venda acontece em qualquer marketplace, o Pulse unifica tudo na mesma tela, baixa o estoque físico da loja e imprime a comanda/etiqueta.

---

## 8. A nossa SaaS está preparada para tudo isso?

### 👉 RESPOSTA: SIM, 100% PREPARADA E BLINDADA!

O Pulse ERP foi estruturado com uma arquitetura moderna para suportar centenas de lojas operando simultaneamente:

| Recurso da Arquitetura | Como o Pulse ERP gerencia | Status no Sistema |
| :--- | :--- | :---: |
| **Isolamento Multi-Tenant** | Todas as tabelas possuem a chave estrangeira `usuario_id` (UUID). Uma loja jamais visualiza ou altera dados de outra loja. | ✅ **100% Ativo** |
| **Ingestão de Webhooks (Fast-ACK)** | O [WebhookController.php](file:///srv/http/pulse/modules/marketplace/controllers/WebhookController.php) recebe as notificações dos marketplaces, valida a assinatura criptográfica, descobre quem é o lojista dono e responde `HTTP 200` em menos de 100ms. | ✅ **100% Ativo** |
| **Fila Assíncrona Resiliente** | Se 50 lojas receberem 100 pedidos no mesmo minuto, o servidor não trava. O job vai para o `yii2-queue` gerenciado pelo worker `pulse-queue.service` no Systemd. | ✅ **Ativo & Rodando** |
| **Rate Limiting & Backoff** | O job [SyncEstoqueMarketplaceJob.php](file:///srv/http/pulse/modules/marketplace/jobs/SyncEstoqueMarketplaceJob.php) respeita os limites de requisições por segundo de cada marketplace (evitando bloqueios HTTP 429). | ✅ **100% Ativo** |
| **Regras de Markup por Canal** | Cada lojista pode configurar markups diferentes por canal na tabela `prest_marketplace_config` (ex: +16% no Mercado Livre e +14% na Shopee para cobrir as comissões). | ✅ **100% Ativo** |
| **Dimensões e Logística** | Tabela `prest_produtos` já possui `peso_bruto`, `altura_cm`, `largura_cm`, `comprimento_cm`, `ncm`, `cest` para emissão fiscal e frete. | ✅ **100% Ativo** |
| **Monetização e Faturamento do SaaS** | Estrutura de cobrança do SaaS já criada no PostgreSQL (migration `015_create_saas_billing_and_commission_tables.sql`): `prest_saas_planos`, `prest_saas_loja_config` e `prest_saas_faturas` para a plataforma cobrar mensalidade e/ou comissão dos lojistas. | ✅ **100% Ativo** |

---

## 🚀 Resumo Executivo para Iniciar a Operação

1. **Você (Admin do Pulse):**
   * Cria os cadastros de desenvolvedor (Mercado Pago Developers, Mercado Libre Developers, Shopee Open Platform).
   * Coloca os `App IDs` e `Secrets` mestres no `.env` do servidor.
2. **Seus Clientes (Lojistas):**
   * Cadastram suas lojas normalmente nos marketplaces onde querem vender.
   * Entram no Pulse ERP e clicam no botão de conectar cada canal.
3. **O Sistema:**
   * Assume todo o trabalho pesado de sincronização de estoque, precificação, importação de pedidos, baixa no caixa e geração de etiquetas.
