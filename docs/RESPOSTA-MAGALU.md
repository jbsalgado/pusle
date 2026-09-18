# Resposta Técnica Oficial — Arquitetura de Fila de Pedidos, Produtos e Webhooks (API Magalu)

> **Documento de Referência:** Homologação Técnica e Integração com o Magazine Luiza (Magalu Marketplace / LuizaLabs)  
> **Sistema:** Pulse ERP (SaaS Multi-tenant)  
> **Data:** 18/09/2026  

---

### Pergunta da Equipe Magalu:
> *"Qual é a arquitetura atual do seu SaaS para a fila de pedidos e produtos (ex.: filas assíncronas, webhooks)? Assim podemos detalhar o fluxo ideal dos payloads para a API do Magalu."*

---

## ✉️ Resposta Estruturada Pronta para Envio

**Assunto:** Arquitetura do SaaS Pulse ERP — Fila de Pedidos, Produtos e Webhooks (Integração Magalu)

Olá, equipe técnica do Magalu!

Segue o descritivo detalhado da nossa arquitetura atual para ingestão de pedidos, atualização de catálogo/estoque e emissão de notas fiscais:

---

### 1. Arquitetura Geral & Tecnologias
* **Stack Principal:** PHP 8.x com framework corporativo Yii2 em ambiente Linux (Nginx + PostgreSQL).
* **Mecanismo de Filas:** `yii2-queue` persistido em banco de dados relacional (`DbQueue`), gerenciado e supervisionado continuamente por daemons em segundo plano via **Systemd** (`pulse-queue.service`).
* **Multi-tenancy:** Arquitetura multi-tenant lógica com isolamento estrito por `usuario_id` (UUID). Cada lojista (seller) possui suas próprias credenciais de API/Token registradas de forma isolada na tabela `prest_marketplace_config`.

---

### 2. Fluxo de Ingestão de Pedidos (Inbound / Webhooks)
Adotamos a arquitetura de **Fast-ACK (< 100ms)** para eliminar riscos de timeouts ou reenvios desnecessários por parte dos servidores do Magalu:

1. **Endpoint Receptor (`POST /marketplace/webhook/receive?marketplace=magalu`):**
   - Recebe o payload JSON bruto e os headers HTTP da notificação.
   - Valida a autenticidade da requisição (Token / Assinatura HMAC se configurado).
   - Identifica deterministicamente o lojista/seller correspondente através do identificador no payload ou token.
   - Enfileira a tarefa assíncrona (`ProcessarWebhookJob`) na fila e responde imediatamente com **HTTP 200 OK** em menos de 100ms.

2. **Worker de Fila em Segundo Plano (`ProcessarWebhookJob`):**
   - Executado pelos workers gerenciados pelo Systemd.
   - Converte os dados recebidos para o nosso DTO canônico (`MarketplaceOrderDTO`).
   - Aplica **idempotência estrita** baseada no `marketplace_order_id` (impedindo duplicidade de faturamento ou baixa dupla de estoque).
   - Registra ou atualiza o cliente em `prest_clientes`.
   - Cria o pedido em `prest_vendas` e os itens correspondentes em `prest_venda_itens`.
   - Executa a baixa atômica de estoque local em `prest_produtos`.
   - Despacha o job `SyncEstoqueMarketplaceJob` para rebalancear a disponibilidade nos outros canais conectados do lojista.

3. **Polling de Contingência:**
   - Mantemos uma rotina agendada (Cron job) periódica que consulta `GET /orders/status/approved` para conciliação preventiva, assegurando que nenhum pedido fique sem processamento caso ocorra qualquer instabilidade de rede ou webhook.

---

### 3. Fluxo de Catálogo, Preço e Estoque (Outbound)
* **Sincronização 100% Assíncrona:** Qualquer movimentação manual no ERP ou venda física realizada no PDV insere um `SyncEstoqueMarketplaceJob` na fila.
* **Resiliência & Retentativas:** O worker implementa política de retentativas automáticas com backoff exponencial para lidar com limites de requisição (*rate limiting* / HTTP 429) ou oscilações de rede.
* **Endpoints Magalu Mapeados:**
   - **Atualização de Estoque:** `PUT /products/{sku}/stock`
   - **Atualização de Preço:** `PUT /products/{sku}/price` (com aplicação de margem/markup configurada pelo lojista para compensar comissões).

---

### 4. Fluxo de Faturamento & NF-e
* A emissão de NF-e (Modelo 55) é realizada pelo nosso pipeline assíncrono junto à SEFAZ (`EmitirNFe55Job`).
* Assim que a nota é autorizada, os dados fiscais são automaticamente enviados ao endpoint de faturamento do Magalu (`POST /orders/{order_id}/invoice`), contendo a chave de acesso de 44 dígitos e o XML da nota codificado em Base64, liberando a etiqueta e a expedição.

---

Ficamos no aguardo das especificações e esquemas detalhados de payloads (eventos de pedidos, catálogo de produtos com variações e tracking de logística) para ajustarmos nossos DTOs aos contratos oficiais da API do Magalu!

---

### Informações Técnicas de Configuração (Para o Portal do Desenvolvedor)
* **Webhook Receiver:** `https://[DOMINIO_DA_LOJA]/marketplace/webhook/receive?marketplace=magalu`
* **Método Suportado:** `POST` (JSON / `application/json`)
* **Tempo de Resposta Médio do Fast-ACK:** `~35ms` (HTTP 200)
