# 📋 Guia de Configuração: URIs e Webhooks do Mercado Livre (Pulse ERP)

**Ambiente de Produção:** `https://catalogos.oncode.app.br`  
**Data de Atualização:** 29 de Agosto de 2026  
**Documentação Oficial MELI:** [Mercado Livre Developers](https://developers.mercadolivre.com.br/)

---

## 1. Dados para Cadastro no Portal de Desenvolvedores

Ao criar ou editar a sua aplicação no portal do [Mercado Livre Developers](https://developers.mercadolivre.com.br/devcenter), preencha os campos exatamente com os valores abaixo:

### 🔗 1.1 URI de Redirecionamento (Redirect URI)
Utilizada no fluxo de autorização OAuth 2.0 para receber o `authorization_code` e gerar os tokens de acesso do seller.

```text
https://catalogos.oncode.app.br/marketplace/config/callback
```

---

### 🔔 1.2 URL de Notificações / Webhooks (Notification Callback URL)
Utilizada pelo Mercado Livre para enviar notificações em tempo real de novos pedidos, atualizações de status, envios e mensagens.

```text
https://catalogos.oncode.app.br/marketplace/webhook/receive?marketplace=mercado-livre
```

#### Tópicos Obrigatórios para Assinar no Painel do Mercado Livre:
* ☑️ **`orders_v2`** — Notificações de novos pedidos e atualizações de pagamento.
* ☑️ **`items`** — Sincronização de catálogo e anúncios.
* ☑️ **`shipments`** — Atualizações de etiquetas e logística (Mercado Envios).
* ☑️ **`messages`** — Mensagens pós-venda.
* ☑️ **`questions`** — Perguntas de clientes nos anúncios.

---

## 2. (Opcional) Integração Gateway Mercado Pago (Checkout / Pix / Point)

Caso você também esteja criando a aplicação para pagamentos no **Mercado Pago Developers**:

* **Redirect URI (Mercado Pago):**
  ```text
  https://catalogos.oncode.app.br/api/mercado-pago/callback
  ```
* **Notification URL (Mercado Pago IPN / Webhooks):**
  ```text
  https://catalogos.oncode.app.br/api/mercado-pago/webhook
  ```

---

## 3. Tabela Resumo para Copiar e Colar

| Campo no Portal | Valor Exato |
| :--- | :--- |
| **Nome da Aplicação** | `Pulse ERP Hub` (ou o nome da sua empresa) |
| **Redirect URI (OAuth)** | `https://catalogos.oncode.app.br/marketplace/config/callback` |
| **Notification Callback URL** | `https://catalogos.oncode.app.br/marketplace/webhook/receive?marketplace=mercado-livre` |
| **Tópicos de Notificação** | `orders_v2`, `items`, `shipments`, `questions`, `messages` |

---

## 4. Passo a Passo para Conectar no Pulse ERP

1. Após salvar a aplicação no portal do Mercado Livre, copie o **App ID (Client ID)** e a **Secret Key**.
2. Acesse o Pulse ERP em:
   👉 `https://catalogos.oncode.app.br/marketplace/config/create`
3. Selecione **Mercado Livre**, informe um apelido para a conta (ex: *"Loja Principal"*) e cole o **Client ID** e **Client Secret**.
4. Salve a configuração e, na tela de detalhes, clique no botão verde **"Conectar / Autorizar OAuth"**.
5. Faça login com a conta vendedora do Mercado Livre e clique em **"Permitir"**.
6. Pronto! A conta estará conectada e com sincronização ativa.
