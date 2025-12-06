# 📋 Guia: Sequência para Criar Registros de Cobrança Válidos

Este documento explica a ordem correta de configuração para ter registros válidos no sistema de cobrança.

## 🎯 Sequência Passo a Passo

### **PASSO 1: Criar Período de Cobrança** ⏰
**O que é:** Define o período (mês/ano) em que as cobranças serão realizadas.

**Como fazer:**
1. Acesse: `http://localhost/pulse/basic/web/index.php/vendas/periodo-cobranca/create`
2. Preencha:
   - **Mês de Referência:** Selecione o mês (ex: Janeiro, Fevereiro, etc.)
   - **Ano de Referência:** Digite o ano (ex: 2025)
   - **Data Início:** Primeiro dia do período (ex: 01/01/2025)
   - **Data Fim:** Último dia do período (ex: 31/01/2025)
   - **Status:** Deixe como "Aberto" inicialmente
   - **Descrição:** Pode deixar em branco (será gerada automaticamente)

**Importante:** 
- Cada período deve ser único por mês/ano
- O status pode ser alterado depois para "Em Cobrança" quando começar a distribuir clientes

---

### **PASSO 2: Cadastrar Colaboradores (Cobradores)** 👤
**O que é:** Pessoas que irão realizar as cobranças porta a porta.

**Como fazer:**
1. Acesse: `http://localhost/pulse/basic/web/index.php/vendas/colaborador/create`
2. Preencha os dados do colaborador
3. **Marque a opção "É Cobrador?"** ✅
4. Salve

**Importante:**
- O colaborador precisa estar marcado como "Cobrador" para aparecer nas listas de cobradores
- Pode ser também vendedor (marcar ambas as opções)

---

### **PASSO 3: Cadastrar Clientes** 👥
**O que é:** Clientes que terão vendas parceladas para cobrança.

**Como fazer:**
1. Acesse: `http://localhost/pulse/basic/web/index.php/vendas/clientes/create`
2. Preencha todos os dados do cliente:
   - Nome completo
   - CPF
   - Telefone
   - Endereço completo (rua, bairro, cidade, estado)
   - Ponto de referência (opcional, mas útil para cobradores)
3. Marque como "Ativo"
4. Salve

**Importante:**
- O endereço completo é essencial para os cobradores encontrarem o cliente
- Clientes inativos não aparecerão nas carteiras de cobrança

---

### **PASSO 4: Criar Vendas com Parcelas** 💰
**O que é:** Vendas parceladas que gerarão parcelas para cobrança.

**Como fazer:**
1. Realize uma venda através de:
   - **Venda Direta:** `http://localhost/pulse/basic/web/venda-direta/`
   - **Catálogo:** `http://localhost/pulse/basic/web/catalogo/`
   - **Sistema Administrativo:** Criar venda manualmente

2. **Configure a venda como PARCELADA:**
   - Selecione um cliente cadastrado
   - Escolha uma forma de pagamento que aceite parcelamento
   - Defina o número de parcelas (ex: 12x)
   - Informe a data do primeiro vencimento
   - Finalize a venda

**O que acontece automaticamente:**
- O sistema cria as parcelas na tabela `prest_parcelas`
- Cada parcela fica com status "PENDENTE" (exceto vendas diretas pagas na hora)
- As parcelas ficam vinculadas ao cliente e à venda

**Importante:**
- Apenas vendas parceladas geram parcelas para cobrança
- Vendas à vista não geram parcelas

---

### **PASSO 5: Criar Rotas de Cobrança** 🗺️
**O que é:** Define as rotas que os cobradores seguirão, organizando clientes por área/dia.

**Como fazer:**
1. Acesse: `http://localhost/pulse/basic/web/index.php/vendas/rota-cobranca/create`
2. Preencha:
   - **Nome da Rota:** Ex: "Rota Centro", "Rota Zona Norte"
   - **Período:** Selecione o período criado no Passo 1
   - **Cobrador:** Selecione o colaborador cobrador criado no Passo 2
   - **Dia da Semana:** Dia em que a rota será executada (ex: Segunda-feira)
   - **Ordem de Execução:** Número para ordenar as rotas (1, 2, 3...)
   - **Descrição:** Detalhes sobre a rota (opcional)
3. Salve

**Importante:**
- Uma rota pode ter vários clientes
- O mesmo cobrador pode ter múltiplas rotas
- A ordem de execução ajuda a organizar o trabalho do cobrador

---

### **PASSO 6: Distribuir Clientes na Carteira de Cobrança** 📦
**O que é:** Associa clientes com parcelas pendentes aos cobradores/rotas.

**Como fazer:**
1. Acesse: `http://localhost/pulse/basic/web/index.php/vendas/carteira-cobranca/create`
2. Para cada cliente com parcelas pendentes, preencha:
   - **Período:** Selecione o período criado no Passo 1
   - **Cobrador:** Selecione o cobrador responsável
   - **Cliente:** Selecione o cliente que tem parcelas pendentes
   - **Rota:** (Opcional) Selecione a rota se já foi criada
   - **Valor Total:** Soma de todas as parcelas pendentes do cliente
   - **Total de Parcelas:** Quantidade de parcelas pendentes
   - **Parcelas Pagas:** Inicie com 0
   - **Valor Recebido:** Inicie com 0
   - **Data de Distribuição:** Data atual
   - **Ativo:** Marque como ativo
3. Salve

**Dica:** 
- Você pode consultar as parcelas pendentes de um cliente em: `http://localhost/pulse/basic/web/index.php/vendas/parcela/index`
- Filtre por cliente para ver quantas parcelas estão pendentes

**Importante:**
- Cada cliente só pode ter uma carteira ativa por período
- O sistema valida isso automaticamente

---

### **PASSO 7: Registrar Pagamentos (Histórico de Cobrança)** 📝
**O que é:** Quando os cobradores recebem pagamentos, eles são registrados no histórico.

**Como fazer:**
1. **Através do App Prestanista (PWA):**
   - O cobrador acessa o app no celular
   - Visualiza a rota do dia
   - Ao receber um pagamento, registra:
     - Valor recebido
     - Forma de pagamento (Dinheiro ou PIX)
     - Observações (opcional)
   - O sistema registra automaticamente no histórico

2. **Manual (se necessário):**
   - O histórico é criado automaticamente quando pagamentos são registrados
   - Pode ser visualizado em: `http://localhost/pulse/basic/web/index.php/vendas/historico-cobranca/index`

**O que acontece automaticamente:**
- A parcela é marcada como paga (ou parcialmente paga)
- O histórico de cobrança é criado
- A carteira de cobrança é atualizada (parcelas pagas, valor recebido)
- A geolocalização é capturada (se o app tiver permissão)

---

## 🔄 Fluxo Completo Visual

```
1. Período de Cobrança
   ↓
2. Colaboradores (Cobradores)
   ↓
3. Clientes
   ↓
4. Vendas Parceladas → Gera Parcelas
   ↓
5. Rotas de Cobrança
   ↓
6. Carteira de Cobrança (Distribui Clientes para Cobradores)
   ↓
7. Cobradores executam rotas e registram pagamentos
   ↓
8. Histórico de Cobrança é gerado automaticamente
```

---

## ✅ Checklist de Validação

Antes de começar a cobrança, verifique:

- [ ] Período de cobrança criado e com status "Aberto" ou "Em Cobrança"
- [ ] Pelo menos um colaborador cadastrado como "Cobrador"
- [ ] Clientes cadastrados com endereços completos
- [ ] Vendas parceladas criadas (verificar em "Parcelas" se existem parcelas pendentes)
- [ ] Rotas de cobrança criadas e vinculadas aos cobradores
- [ ] Carteiras de cobrança criadas (clientes distribuídos para cobradores)
- [ ] Valores e quantidades de parcelas corretos na carteira

---

## 🚨 Problemas Comuns e Soluções

### **Problema:** Não consigo selecionar um período no formulário
**Solução:** Certifique-se de que criou um período no Passo 1 e que ele não está com status "Fechado"

### **Problema:** Não aparecem cobradores na lista
**Solução:** Verifique se o colaborador está marcado como "É Cobrador?" no cadastro

### **Problema:** Cliente não aparece na lista de clientes
**Solução:** Verifique se o cliente está marcado como "Ativo"

### **Problema:** Não consigo criar carteira porque não há parcelas
**Solução:** Primeiro crie vendas parceladas. Verifique em "Parcelas" se existem parcelas pendentes para o cliente

### **Problema:** Carteira criada mas valores estão zerados
**Solução:** Verifique se o cliente realmente tem parcelas pendentes. Consulte a aba "Parcelas" e some os valores das parcelas pendentes do cliente

---

## 📊 Onde Verificar os Dados

- **Parcelas Pendentes:** `http://localhost/pulse/basic/web/index.php/vendas/parcela/index`
- **Carteiras Criadas:** `http://localhost/pulse/basic/web/index.php/vendas/carteira-cobranca/index`
- **Histórico de Pagamentos:** `http://localhost/pulse/basic/web/index.php/vendas/historico-cobranca/index`
- **Rotas Criadas:** `http://localhost/pulse/basic/web/index.php/vendas/rota-cobranca/index`

---

## 🎯 Resumo Rápido

1. **Período** → Define quando
2. **Cobradores** → Define quem
3. **Clientes** → Define para quem
4. **Vendas** → Gera o que cobrar
5. **Rotas** → Organiza onde
6. **Carteira** → Distribui quem cobra de quem
7. **Histórico** → Registra o que foi cobrado

---

**Última atualização:** Janeiro 2025

