# 💼 Plano de Monetização, Comissões e Gestão Financeira do SaaS (Pulse ERP)

**Ambiente de Produção:** `https://catalogos.oncode.app.br`  
**Data:** 29 de Agosto de 2026  
**Autor:** Equipe de Arquitetura SaaS Pulse ERP

---

## 1. Visão Geral dos Modelos de Monetização

O Pulse ERP adota um **Modelo Híbrido de Receita**, combinando cobrança de mensalidade base recorrente com participação percentual (Take Rate) e Split em tempo real sobre as vendas de cada lojista (*tenant*).

```
                             ┌──────────────────────────────────┐
                             │    PAINEL SUPERADMIN DO SAAS     │
                             │        (modules/admin)           │
                             │ • Dashboard de GMV Global        │
                             │ • Gestão de Planos e Comissões   │
                             │ • Controle de Faturas e Inadimpl.│
                             └─────────────────┬────────────────┘
                                               │
               ┌───────────────────────────────┴───────────────────────────────┐
               ▼                                                               ▼
┌───────────────────────────────┐                               ┌───────────────────────────────┐
│     CANAL 1: VENDA DIRETA     │                               │      CANAL 2: MARKETPLACES    │
│  (Catálogo Online / PDV Pulse)│                               │ (Mercado Livre, Shopee, Temu) │
└──────────────┬────────────────┘                               └──────────────┬────────────────┘
               │                                                               │
               ▼ Split em Tempo Real                                           ▼ Faturamento Pós-Pago
┌───────────────────────────────┐                               ┌───────────────────────────────┐
│ Mercado Pago Marketplace Split│                               │ Fechamento Mensal Automático  │
│ • R$ 194 ➔ Conta da Loja      │                               │ • Calcula: Total GMV x % taxa │
│ • R$ 6   ➔ Conta Master SaaS  │                               │ • Gera Fatura Pix/Boleto SaaS │
│ (Recebimento Instantâneo)     │                               │ (Cobrança Recorrente Mensal)  │
└───────────────────────────────┘                               └───────────────────────────────┘
```

---

## 2. Detalhamento dos 3 Mecanismos de Monetização

### 2.1 Split de Pagamento em Tempo Real (Vendas Diretas / Catálogo Online)
* **Gatilho:** Cliente final realiza um pagamento via Pix ou Cartão no Catálogo do Lojista.
* **Mecanismo:** A API do Mercado Pago / Gateway recebe o parâmetro `marketplace_fee` (ou `application_fee`).
* **Divisão:** 
  * Exemplo: Venda de R$ 200,00 com taxa de plataforma de 3%.
  * **R$ 194,00** caem diretamente na conta Mercado Pago do lojista.
  * **R$ 6,00** caem instantaneamente na **Conta Master do SaaS**.
* **Vantagem:** Liquidação imediata, zero inadimplência e isenção de bitributação (o SaaS tributa apenas sobre a sua taxa de serviço).

---

### 2.2 Take Rate Pós-Pago sobre GMV de Marketplaces (Mercado Livre, Shopee, etc.)
* **Gatilho:** Vendas realizadas dentro dos marketplaces e importadas automaticamente para o Pulse ERP.
* **Mecanismo:** O Job de fechamento mensal (`SaasBillingController::actionFecharMes`) calcula o GMV (*Gross Merchandise Value*) aprovado do mês e aplica a taxa do plano (ex: 1.0%).
* **Fórmula da Fatura:**
  $$\text{Valor da Fatura} = \text{Mensalidade Fixa} + (\text{GMV Marketplace} \times \%_{\text{mktp}}) + (\text{Pedidos Excedentes} \times \text{Tarifa Unitária})$$
* **Exemplo:**
  * Mensalidade: R$ 99,00
  * Vendas no Mercado Livre: R$ 60.000,00 (Taxa 1% = R$ 600,00)
  * **Total da Fatura do Lojista:** **R$ 699,00**

---

### 2.3 Micro-Fee / Franquia de Volume de Pedidos
* **Gatilho:** Pedidos faturados que ultrapassam a franquia do plano contratado (ex: R$ 0,50 por pedido acima de 300 pedidos/mês).

---

## 3. Estrutura de Banco de Dados (`015_create_saas_billing_and_commission_tables.sql`)

1. **`prest_saas_planos`**: Cadastro de planos comerciais (Start, Pro, Enterprise, etc.) com suas alíquotas e limites.
2. **`prest_saas_loja_config`**: Vínculo do tenant com o plano, dia de vencimento, carência de bloqueio e eventuais taxas customizadas.
3. **`prest_saas_faturas`**: Histórico completo de faturas geradas, GMV calculado por canal, QR Code Pix dinâmico e status de pagamento.
4. **`prest_saas_config_global`**: Credenciais da conta master do SaaS para recebimento de splits e taxas.

---

## 4. Política de Inadimplência e Bloqueio Automático

1. **Data de Fechamento:** Todo dia 1º do mês (ou na data de corte da loja).
2. **Data de Vencimento:** 10 dias após o fechamento.
3. **Prazo de Carência:** 5 dias após o vencimento com alertas diários via WhatsApp (Evolution API) e E-mail.
4. **Suspensão Automática:** Caso a fatura permaneça não paga após a carência, o acesso às sincronizações de catálogo e marketplaces é pausado até a liquidação da fatura.

---

## 5. Rotas do Painel Administrativo (`modules/admin`)

* **Dashboard Financeiro:** `https://catalogos.oncode.app.br/admin/financeiro/index`
* **Relatório de Faturas:** `https://catalogos.oncode.app.br/admin/financeiro/faturas`
* **Planos & Comissões:** `https://catalogos.oncode.app.br/admin/financeiro/planos`
* **Configurações Master:** `https://catalogos.oncode.app.br/admin/financeiro/config`
