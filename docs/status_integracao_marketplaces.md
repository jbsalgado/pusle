# 📊 Status da Integração com Marketplaces

**Data da Análise:** 18 de Junho de 2026  
**Status Geral:** Estrutura base e banco de dados prontos (Fase de Mock/Groundwork concluída). Integrações reais com APIs em estado de Stub/Mock pendentes de desenvolvimento.

---

## 🏗️ 1. Arquitetura do Módulo

A integração de marketplaces foi organizada como um módulo do Yii2 localizado em [modules/marketplace](file:///srv/http/pulse/modules/marketplace). A estrutura segue o padrão MVC do framework:

```
modules/marketplace/
├── components/      # Services de integração, Handlers de Webhooks e orquestradores
├── controllers/     # Controllers da Web e endpoints de Webhook
├── models/          # Modelos ActiveRecord do banco de dados
└── views/           # Interface visual do dashboard e configurações
```

---

## 📂 2. O Que Já Está Implementado

### A. Banco de Dados e Migrations (100% Concluído)
A estrutura de banco de dados foi criada por meio do script SQL [013_create_marketplace_tables.sql](file:///srv/http/pulse/sql/postgres/013_create_marketplace_tables.sql). As seguintes tabelas estão criadas no PostgreSQL:
- **`prest_marketplace_config`:** Guarda as configurações e credenciais de integração por loja/tenant (Mercado Livre, Shopee, iFood, Magazine Luiza e Amazon).
- **`prest_marketplace_produto`:** Mapeia a relação entre o ID do produto local do Pulse e o ID correspondente do produto no marketplace, armazenando preços, títulos, SKUs e respostas brutas da API (`JSONB`).
- **`prest_marketplace_pedido`:** Armazena dados dos pedidos importados, dados do comprador, endereço de entrega, códigos de rastreamento e o vínculo com a venda gerada localmente no Pulse (`venda_id`).
- **`prest_marketplace_pedido_item`:** Itens que compõem cada pedido de marketplace, associados aos respectivos produtos locais.
- **`prest_marketplace_sync_log`:** Histórico de sincronizações manuais ou em lote (estoque, produtos, pedidos e webhooks) com contadores de sucesso e tempos de execução.

### B. Modelos ActiveRecord (100% Concluído)
Os modelos de dados correspondentes às tabelas estão declarados em [modules/marketplace/models](file:///srv/http/pulse/modules/marketplace/models) com as validações de regras de negócios, relações e helpers:
- [MarketplaceConfig.php](file:///srv/http/pulse/modules/marketplace/models/MarketplaceConfig.php)
- [MarketplaceProduto.php](file:///srv/http/pulse/modules/marketplace/models/MarketplaceProduto.php)
- [MarketplacePedido.php](file:///srv/http/pulse/modules/marketplace/models/MarketplacePedido.php)
- [MarketplacePedidoItem.php](file:///srv/http/pulse/modules/marketplace/models/MarketplacePedidoItem.php)
- [MarketplaceSyncLog.php](file:///srv/http/pulse/modules/marketplace/models/MarketplaceSyncLog.php)

### C. Controllers e Views Administrativas (Estrutura Pronta, Lógica Mockada)
- **Painel Central (Dashboard):** [DashboardController.php](file:///srv/http/pulse/modules/marketplace/controllers/DashboardController.php) coleta estatísticas gerais (pedidos, produtos vinculados e logs) e envia para a view do dashboard.
- **Gerenciador de Credenciais:** [ConfigController.php](file:///srv/http/pulse/modules/marketplace/controllers/ConfigController.php) possui o fluxo de criação, edição e ativação de integrações de marketplaces.
- **Sincronizador Manual:** [SyncController.php](file:///srv/http/pulse/modules/marketplace/controllers/SyncController.php) expõe ações para forçar manualmente a sincronização de produtos, estoque ou importação de pedidos. *Nota: Exibe apenas avisos de simulação.*
- **Orquestração Geral:** [MarketplaceSyncManager.php](file:///srv/http/pulse/modules/marketplace/components/MarketplaceSyncManager.php) mapeia os produtos ativos e invoca a sincronização correspondente ao marketplace cadastrado, disparando alertas via [TelegramHelper](file:///srv/http/pulse/components/TelegramHelper.php) se ocorrerem erros.

### D. Processamento de Webhooks (Fluxo Inicial e Escudo de Validação)
- **Controller Público:** [WebhookController.php](file:///srv/http/pulse/modules/marketplace/controllers/WebhookController.php) expõe a rota pública `/marketplace/webhook/receive` para receber payloads via POST e gerenciar assinaturas.
- **Validador de Assinatura:** [WebhookSignatureValidator.php](file:///srv/http/pulse/modules/marketplace/components/WebhookSignatureValidator.php) implementa o algoritmo HMAC SHA256/SHA1 para checar a autenticidade e integridade dos dados enviados pelas plataformas.
- **Handler de Pedidos:** [OrderEventProcessor.php](file:///srv/http/pulse/modules/marketplace/components/OrderEventProcessor.php) processa a estrutura de JSON para criar o pedido e seus itens no banco local.

---

## 🛠️ 3. O Que Falta Implementar (Próximas Fases)

Apesar de a estrutura arquitetural estar no lugar, os serviços de comunicação e APIs reais operam inteiramente em modo de **Mock (Simulação)**. Os seguintes componentes estão pendentes de desenvolvimento:

### A. Integração com APIs Reais (0% Concluído nos Services)
Os arquivos de serviço abaixo contêm apenas retornos fictícios stubs (`true`, `[]`, etc.). É necessário desenvolver a lógica de negócio real de comunicação HTTP com cada provedor:
1. **Mercado Livre ([MercadoLivreService.php](file:///srv/http/pulse/modules/marketplace/components/MercadoLivreService.php)):**
   - Implementar fluxo OAuth2 completo (trocar código de autorização por tokens e renovação via `refresh_token` na API de autenticação do Mercado Livre).
   - Comunicação real com a API `/items` para sincronizar e gerenciar anúncios de produtos.
   - Sincronização física de estoque e preço do produto local com a API de anúncios.
   - Chamadas reais para importar e listar pedidos.
2. **Shopee ([ShopeeService.php](file:///srv/http/pulse/modules/marketplace/components/ShopeeService.php)):**
   - Lógica de autenticação OAuth2 com assinatura baseada na API Open da Shopee.
   - Mapeamento e exportação de produtos e sincronização de quantidades de estoque.
3. **iFood ([IFoodService.php](file:///srv/http/pulse/modules/marketplace/components/IFoodService.php)):**
   - Fluxo de autenticação Client Credentials (API V2 iFood Merchant).
   - Lógica de controle de catálogo de cardápio.
   - Atualização de status do item local para `AVAILABLE` (Disponível) ou `UNAVAILABLE` (Indisponível) no iFood.
4. **Magazine Luiza & Amazon:**
   - Atualmente, não existem classes de Service criadas para essas plataformas no código, apenas definições de constantes no banco. É preciso criar as classes de comunicação HTTP.

### B. Integração de Webhooks Reais
- **Mapeamento do WebhookController:** O [WebhookController.php](file:///srv/http/pulse/modules/marketplace/controllers/WebhookController.php#L180-L202) atualmente **apenas** retorna o Handler do Mercado Livre (`MercadoLivreWebhookHandler`). Ele não ativa/retorna os Handlers da Shopee, iFood ou outros marketplaces. É necessário mapear esses retornos no `switch`.
- **Validação de Assinatura do iFood/Shopee:** O validador de assinatura de webhook (`WebhookSignatureValidator`) e o [IFoodWebhookHandler.php](file:///srv/http/pulse/modules/marketplace/components/IFoodWebhookHandler.php#L23) precisam ter a validação de assinatura Hmac real codificada.
- **Mocks no iFood/Mercado Livre:** O [IFoodWebhookHandler.php](file:///srv/http/pulse/modules/marketplace/components/IFoodWebhookHandler.php#L80) simula dados fixos no recebimento de webhooks. Deve ser implementada a busca real do pedido via API `/orders/{orderId}` do iFood.

### C. Fila de Sincronização Automática em Background
- Não há uma rotina automatizada de background (CLI Command / Cron Job / Fila do RabbitMQ) que varra periodicamente o banco `prest_marketplace_config` para executar a importação de novos pedidos e sincronização de estoques das lojas baseada no `intervalo_sync_minutos`.
- Deve ser criado um comando console (ex: `commands/MarketplaceSyncController.php`) para rodar em lote e atualizar estoques e pedidos automaticamente.

### D. Segurança das Credenciais (Token Criptografado)
- Os tokens (OAuth) atualmente são armazenados no banco ou recuperados de forma direta (Base64). É necessário implementar a criptografia simétrica `AES-256` utilizando o `Yii::$app->security` no [MarketplaceAuthManager.php](file:///srv/http/pulse/modules/marketplace/components/MarketplaceAuthManager.php) para evitar vazamento de credenciais na tabela `prest_marketplace_config`.

---

## 📈 Tabela de Status das Integrações

| Marketplace | Cadastro e Credenciais | Autenticação (OAuth) | Sincronização de Estoque | Importação de Pedidos | Webhook (Tempo Real) |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Mercado Livre** | ✅ Sim | ❌ Mock (Stub) | ❌ Mock (Stub) | ❌ Mock (Stub) | 🟡 Parcial (Falta Token Real) |
| **Shopee** | ✅ Sim | ❌ Mock (Stub) | ❌ Mock (Stub) | ❌ Mock (Stub) | ❌ Não Implementado |
| **iFood** | ✅ Sim | ❌ Mock (Stub) | ❌ Mock (Stub) | ❌ Mock (Stub) | 🟡 Parcial (Falta Token Real) |
| **Magazine Luiza**| ✅ Sim | ❌ Não Criado | ❌ Não Criado | ❌ Não Criado | ❌ Não Implementado |
| **Amazon** | ✅ Sim | ❌ Não Criado | ❌ Não Criado | ❌ Não Criado | ❌ Não Implementado |
