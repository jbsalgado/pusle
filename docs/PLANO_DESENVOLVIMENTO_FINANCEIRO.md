# 📋 Plano de Desenvolvimento - Sistema Financeiro Integrado

## 🎯 Visão Geral

Este documento descreve as funcionalidades necessárias para implementar um sistema financeiro completo e integrado no THAUSZ-PULSE, incluindo emissão de cupom fiscal, fluxo de caixa, contas a pagar e integrações avançadas com gateways de pagamento.

---

## 📊 Análise do Estado Atual

### ✅ O que já existe:

1. **Módulo de Vendas**
   - Sistema de vendas com parcelamento (`prest_vendas`, `prest_parcelas`)
   - Formas de pagamento configuráveis (`prest_formas_pagamento`)
   - Status de vendas e parcelas
   - Integração básica com Mercado Pago e Asaas (sem split)

2. **Integrações de Pagamento**
   - Mercado Pago: Criação de preferências, processamento básico
   - Asaas: Criação de cobranças, PIX dinâmico básico
   - PIX estático via configuração da loja

3. **Estrutura de Dados**
   - Tabelas: `prest_vendas`, `prest_parcelas`, `prest_venda_itens`
   - Modelos: `Venda`, `Parcela`, `FormaPagamento`
   - Controllers API: `PedidoController`, `MercadoPagoController`, `AsaasController`

### ❌ O que falta:

1. **Emissão de Cupom Fiscal** - ⚠️ Biblioteca e service básico existem, falta integração completa
   - ✅ NFePHP instalada e `NFwService` criado
   - ❌ Falta montar XML da NFe a partir de vendas
   - ❌ Falta integração com sistema de vendas
   - ❌ Falta geração de DANFE (PDF)
2. **Fluxo de Caixa** - Não existe
3. **Contas a Pagar** - Estrutura básica existe no módulo `servicos` mas não integrada
4. **Split de Pagamentos** - Não implementado
5. **PIX Dinâmico Integrado** - Existe parcialmente mas não completo
6. **Cartão de Crédito/Débito Integrado** - Existe parcialmente mas não completo

---

## 🗂️ Tarefas por Ordem de Dificuldade Crescente

### 📌 NÍVEL 1: BÁSICO (Complexidade: ⭐)

#### 1.1. Estrutura de Dados - Fluxo de Caixa
**Descrição:** Criar tabelas e modelos para registrar movimentações de caixa

**Tarefas:**
- Criar tabela `prest_caixa` (abertura/fechamento de caixa)
- Criar tabela `prest_caixa_movimentacoes` (entradas/saídas)
- Criar Model `Caixa` e `CaixaMovimentacao`
- Criar migrations SQL
- Implementar validações básicas

**Dependências:** Nenhuma

**Estimativa:** 2-3 dias

---

#### 1.2. Estrutura de Dados - Contas a Pagar
**Descrição:** Expandir estrutura existente e integrar com módulo de vendas

**Tarefas:**
- Analisar tabelas existentes em `modules/servicos/models/FinanContasPagar.php`
- Criar/ajustar tabela `prest_contas_pagar` se necessário
- Criar Model `ContaPagar` no módulo vendas
- Criar relacionamento com `Fornecedor` e `Compra`
- Implementar validações básicas

**Dependências:** Nenhuma

**Estimativa:** 2-3 dias

---

#### 1.3. Estrutura de Dados - Cupom Fiscal
**Descrição:** Criar estrutura para armazenar dados de cupons fiscais

**Tarefas:**
- Criar tabela `prest_cupons_fiscais`
- Criar Model `CupomFiscal`
- Criar relacionamento com `Venda`
- Campos: número, série, chave de acesso, XML, PDF, status, data emissão

**Dependências:** Nenhuma

**Estimativa:** 1-2 dias

---

### 📌 NÍVEL 2: INTERMEDIÁRIO (Complexidade: ⭐⭐)

#### 2.1. Fluxo de Caixa - Funcionalidades Básicas
**Descrição:** Implementar abertura, fechamento e movimentações de caixa

**Tarefas:**
- Controller `CaixaController` com CRUD básico
- Action `abrirCaixa()` - Abertura de caixa com valor inicial
- Action `fecharCaixa()` - Fechamento com conferência
- Action `registrarMovimentacao()` - Entrada/saída de valores
- Views para gerenciamento de caixa
- Relatório básico de movimentações

**Dependências:** 1.1 (Estrutura de Dados - Fluxo de Caixa)

**Estimativa:** 4-5 dias

---

#### 2.2. Contas a Pagar - Funcionalidades Básicas
**Descrição:** CRUD completo de contas a pagar

**Tarefas:**
- Controller `ContaPagarController` com CRUD
- Integração com fornecedores
- Geração automática de contas a partir de compras
- Views para listagem e cadastro
- Filtros por status, vencimento, fornecedor
- Relatório básico

**Dependências:** 1.2 (Estrutura de Dados - Contas a Pagar)

**Estimativa:** 4-5 dias

---

#### 2.3. Integração PIX Dinâmico - Asaas
**Descrição:** Completar integração de PIX dinâmico via Asaas

**Tarefas:**
- Melhorar `AsaasController::actionGerarQrcodePix()`
- Implementar polling para verificar status do pagamento
- Criar webhook para receber confirmações do Asaas
- Atualizar status de parcelas automaticamente
- Integrar com fluxo de caixa (registro automático)

**Dependências:** 1.1 (Fluxo de Caixa), Integração Asaas existente

**Estimativa:** 3-4 dias

---

#### 2.4. Integração Cartão de Crédito/Débito - Mercado Pago
**Descrição:** Completar integração de pagamento com cartão via Mercado Pago

**Tarefas:**
- Melhorar `MercadoPagoController::actionCriarPreferencia()`
- Implementar processamento de pagamento com cartão
- Criar webhook para receber confirmações
- Atualizar status de parcelas automaticamente
- Integrar com fluxo de caixa

**Dependências:** 1.1 (Fluxo de Caixa), Integração Mercado Pago existente

**Estimativa:** 3-4 dias

---

### 📌 NÍVEL 3: AVANÇADO (Complexidade: ⭐⭐⭐)

#### 3.1. Emissão de Cupom Fiscal - NFe/NFCe (NFePHP)
**Descrição:** Completar integração de emissão de NFe/NFCe usando biblioteca NFePHP já instalada

**Estado Atual:**
- ✅ Biblioteca NFePHP instalada (`nfephp-org/sped-nfe`)
- ✅ Service básico `NFwService` criado (`components/NFwService.php`)
- ✅ Configuração de NFe em `config/params.php`
- ✅ Método `emitir()` implementado (assinatura, envio SEFAZ, salvamento XML)
- ✅ Suporte para NFe (modelo 55) e NFCe (modelo 65)

**Tarefas Necessárias:**
- Criar classe `NFeBuilder` para montar estrutura XML da NFe a partir de dados da venda
  - Montar dados do emitente (buscar de `prest_configuracoes` ou `prest_usuarios`)
  - Montar dados do destinatário (buscar de `prest_clientes`)
  - Montar itens da NFe (buscar de `prest_venda_itens`)
  - Calcular impostos (ICMS, IPI, PIS, COFINS)
  - Configurar forma de pagamento
- Melhorar `NFwService`:
  - Buscar dados do emitente do banco de dados (não hardcoded)
  - Implementar consulta de recibo (para produção)
  - Melhorar tratamento de erros
  - Adicionar logs detalhados
- Criar endpoint API para emissão de cupom:
  - `POST /api/cupom-fiscal/emitir` - Emitir NFe/NFCe para uma venda
  - `POST /api/cupom-fiscal/cancelar` - Cancelar NFe/NFCe
  - `GET /api/cupom-fiscal/consultar` - Consultar status
- Integrar com vendas:
  - Emissão automática após venda (se configurado)
  - Emissão manual via interface
  - Armazenar chave de acesso e status no banco
- Implementar geração de DANFE (PDF):
  - Usar biblioteca para gerar PDF do DANFE
  - Armazenar PDF no servidor
  - Disponibilizar download do PDF
- Implementar cancelamento de NFe:
  - Método para cancelar NFe autorizada
  - Justificativa obrigatória
  - Atualizar status no banco
- Melhorar configuração:
  - Buscar dados do emitente do banco (`prest_configuracoes`)
  - Configurar CSC (Código de Segurança do Contribuinte) para NFCe
  - Configurar token IBPT (se necessário)
- Criar views para:
  - Listagem de cupons fiscais emitidos
  - Visualização de DANFE
  - Emissão manual de cupom
  - Cancelamento de cupom

**Dependências:** 1.3 (Estrutura de Dados - Cupom Fiscal)

**Estimativa:** 6-8 dias (reduzida pois biblioteca já está instalada e service básico existe)

**Biblioteca em Uso:**
- **NFePHP** (`nfephp-org/sped-nfe`) - Biblioteca PHP para emissão de NFe/NFCe
- Documentação: https://github.com/nfephp-org/sped-nfe

**Notas Importantes:**
- O service atual já faz: assinatura, envio para SEFAZ e salvamento do XML
- Falta principalmente: montar a estrutura XML da NFe e integrar com vendas
- NFCe (Nota Fiscal de Consumidor Eletrônica) é mais simples que NFe e adequada para varejo
- Requer certificado digital A1 ou A3 válido
- Em homologação, pode usar certificado de teste da SEFAZ

---

#### 3.2. Fluxo de Caixa - Integração com Vendas
**Descrição:** Integrar movimentações de caixa com vendas e pagamentos

**Tarefas:**
- Registrar automaticamente entrada no caixa quando parcela é paga
- Registrar saída no caixa quando conta a pagar é quitada
- Criar dashboard de caixa com saldo atual
- Implementar conciliação bancária básica
- Criar relatórios de fechamento de caixa
- Implementar múltiplos caixas (por usuário/colaborador)

**Dependências:** 2.1 (Fluxo de Caixa Básico), Sistema de Vendas existente

**Estimativa:** 5-7 dias

---

#### 3.3. Contas a Pagar - Integração Completa
**Descrição:** Integrar contas a pagar com compras, fornecedores e fluxo de caixa

**Tarefas:**
- Geração automática de contas a partir de compras
- Integração com fluxo de caixa (saída automática)
- Sistema de aprovação de pagamentos
- Agendamento de pagamentos
- Relatórios de contas a vencer/vencidas
- Dashboard de contas a pagar

**Dependências:** 2.2 (Contas a Pagar Básico), 2.1 (Fluxo de Caixa)

**Estimativa:** 5-6 dias

---

#### 3.4. Split de Pagamentos - Mercado Pago
**Descrição:** Implementar divisão de recebimento entre loja e terceiros via Mercado Pago

**Tarefas:**
- Estudar API de Split do Mercado Pago
- Criar tabela `prest_split_pagamentos` para configurar splits
- Modificar `MercadoPagoController` para incluir split na preferência
- Implementar configuração de percentuais de split por vendedor/terceiro
- Processar split no webhook de confirmação
- Registrar valores divididos no fluxo de caixa
- Criar relatórios de splits processados

**Dependências:** 2.4 (Cartão Mercado Pago), 2.1 (Fluxo de Caixa)

**Estimativa:** 6-8 dias

**Documentação Mercado Pago:**
- API de Split: https://www.mercadopago.com.br/developers/pt/docs/marketplace/checkout-api/split-payments

---

#### 3.5. Split de Pagamentos - Asaas
**Descrição:** Implementar divisão de recebimento entre loja e terceiros via Asaas

**Tarefas:**
- Estudar API de Split do Asaas (se disponível)
- Criar/ajustar tabela `prest_split_pagamentos` para Asaas
- Modificar `AsaasController` para incluir split na cobrança
- Implementar configuração de percentuais de split
- Processar split no webhook de confirmação
- Registrar valores divididos no fluxo de caixa
- Criar relatórios de splits processados

**Dependências:** 2.3 (PIX Asaas), 2.1 (Fluxo de Caixa)

**Estimativa:** 6-8 dias

**Nota:** Verificar se Asaas suporta split nativo ou se precisa usar múltiplas cobranças

---

### 📌 NÍVEL 4: MUITO AVANÇADO (Complexidade: ⭐⭐⭐⭐)

#### 4.1. Emissão de Cupom Fiscal - Funcionalidades Avançadas
**Descrição:** Implementar funcionalidades avançadas de NFe/NFCe

**Tarefas:**
- Implementar Carta de Correção Eletrônica (CCe)
- Implementar consulta de NFe por chave de acesso
- Implementar download de XML de terceiros
- Implementar geração de DANFE em modo contingência
- Implementar inutilização de numeração
- Criar relatórios de cupons fiscais emitidos
- Dashboard de emissões (quantidade, valores, status)
- Exportação de XMLs para backup
- Integração com sistema de backup automático
- Notificações de erros na emissão
- Sistema de retry automático para falhas temporárias

**Dependências:** 3.1 (Emissão de Cupom Fiscal - NFe/NFCe)

**Estimativa:** 5-7 dias

**Nota:** Funcionalidades complementares após a emissão básica estar funcionando

---

#### 4.2. Fluxo de Caixa - Funcionalidades Avançadas
**Descrição:** Implementar funcionalidades avançadas de gestão de caixa

**Tarefas:**
- Múltiplos caixas simultâneos (PDV, online, delivery)
- Conciliação bancária completa
- Transferências entre caixas
- Suprimentos e sangrias
- Relatórios gerenciais avançados
- Dashboard em tempo real
- Alertas de saldo baixo
- Histórico completo de movimentações
- Exportação para Excel/PDF

**Dependências:** 3.2 (Fluxo de Caixa Integrado)

**Estimativa:** 8-10 dias

---

#### 4.3. Sistema de Conciliação Financeira
**Descrição:** Sistema completo de conciliação entre caixa, bancos e gateways

**Tarefas:**
- Criar tabela `prest_conciliacoes`
- Implementar importação de extratos bancários (OFX, CSV)
- Algoritmo de matching automático de transações
- Interface para conciliação manual
- Relatórios de diferenças e pendências
- Dashboard de conciliação

**Dependências:** 3.2 (Fluxo de Caixa Integrado), Integrações de pagamento

**Estimativa:** 10-12 dias

---

## 🔄 Integrações Necessárias

### Integração com Vendas Existentes

**Pontos de Integração:**
1. **Ao finalizar venda:**
   - Registrar entrada no fluxo de caixa (se pagamento à vista)
   - Gerar cupom fiscal (se configurado)
   - Criar parcelas (se parcelado)

2. **Ao receber pagamento de parcela:**
   - Registrar entrada no fluxo de caixa
   - Atualizar status da parcela
   - Processar split (se configurado)

3. **Ao quitar conta a pagar:**
   - Registrar saída no fluxo de caixa
   - Atualizar status da conta

### Integração com Gateways

**Mercado Pago:**
- Webhook para confirmação de pagamento
- Processar split de pagamento
- Atualizar status de parcelas
- Registrar no fluxo de caixa

**Asaas:**
- Webhook para confirmação de pagamento PIX
- Processar split de pagamento
- Atualizar status de parcelas
- Registrar no fluxo de caixa

---

## 📊 Estrutura de Tabelas Necessárias

### Novas Tabelas

1. **`prest_caixa`**
   - `id`, `usuario_id`, `colaborador_id`, `data_abertura`, `data_fechamento`
   - `valor_inicial`, `valor_final`, `valor_esperado`, `diferenca`
   - `status` (ABERTO, FECHADO), `observacoes`

2. **`prest_caixa_movimentacoes`**
   - `id`, `caixa_id`, `tipo` (ENTRADA, SAIDA), `categoria`
   - `valor`, `descricao`, `forma_pagamento_id`
   - `venda_id`, `parcela_id`, `conta_pagar_id`
   - `data_movimento`, `observacoes`

3. **`prest_contas_pagar`**
   - `id`, `usuario_id`, `fornecedor_id`, `compra_id`
   - `descricao`, `valor`, `data_vencimento`, `data_pagamento`
   - `status` (PENDENTE, PAGA, VENCIDA, CANCELADA)
   - `forma_pagamento_id`, `observacoes`

4. **`prest_cupons_fiscais`**
   - `id`, `venda_id`, `numero`, `serie`, `chave_acesso`
   - `xml_path`, `pdf_path`, `status` (PENDENTE, EMITIDO, CANCELADO)
   - `data_emissao`, `data_cancelamento`, `motivo_cancelamento`

5. **`prest_split_pagamentos`**
   - `id`, `venda_id`, `parcela_id`, `gateway` (MERCADOPAGO, ASAAS)
   - `transacao_id`, `valor_total`, `valor_loja`, `valor_terceiro`
   - `percentual_loja`, `percentual_terceiro`, `terceiro_id`
   - `status`, `data_processamento`

---

## 🎯 Priorização Sugerida

### Fase 1 (Fundação) - 2-3 semanas
1. Estrutura de Dados - Fluxo de Caixa (1.1)
2. Estrutura de Dados - Contas a Pagar (1.2)
3. Estrutura de Dados - Cupom Fiscal (1.3)
4. Fluxo de Caixa - Funcionalidades Básicas (2.1)

### Fase 2 (Integrações Básicas) - 2-3 semanas
5. Contas a Pagar - Funcionalidades Básicas (2.2)
6. Integração PIX Dinâmico - Asaas (2.3)
7. Integração Cartão - Mercado Pago (2.4)
8. Fluxo de Caixa - Integração com Vendas (3.2)

### Fase 3 (Funcionalidades Avançadas) - 3-4 semanas
9. Contas a Pagar - Integração Completa (3.3)
10. Split de Pagamentos - Mercado Pago (3.4)
11. Split de Pagamentos - Asaas (3.5)
12. Fluxo de Caixa - Funcionalidades Avançadas (4.2)

### Fase 4 (Cupom Fiscal) - 1-2 semanas
13. Emissão de Cupom Fiscal - NFe/NFCe (3.1) - ✅ Biblioteca já instalada
14. Emissão de Cupom Fiscal - Funcionalidades Avançadas (4.1) - Opcional

### Fase 5 (Opcional) - 2-3 semanas
15. Sistema de Conciliação Financeira (4.3)

---

## 📝 Observações Importantes

### Sobre Split de Pagamentos

**Mercado Pago:**
- Suporta split nativo via Marketplace API
- Permite dividir recebimento entre múltiplos vendedores
- Taxas são calculadas proporcionalmente

**Asaas:**
- Verificar se suporta split nativo
- Alternativa: criar múltiplas cobranças e dividir manualmente
- Pode ser necessário usar API de transferências

### Sobre Cupom Fiscal

**NFePHP (Biblioteca Já Instalada):**
- ✅ Biblioteca PHP nativa já instalada no projeto (`nfephp-org/sped-nfe`)
- ✅ Service básico `NFwService` já criado (`components/NFwService.php`)
- ✅ Suporta NFe (modelo 55) e NFCe (modelo 65)
- ✅ Comunicação direta com SEFAZ (sem intermediários)
- Requer certificado digital A1 ou A3
- NFCe é mais adequada para varejo (cupom fiscal eletrônico)
- NFe é mais completa (para empresas maiores)

**O que já está pronto:**
- Biblioteca instalada via Composer
- Service básico com método de emissão
- Configuração de certificado e ambiente
- Assinatura e envio para SEFAZ funcionando

**O que falta:**
- Montar estrutura XML da NFe a partir dos dados da venda
- Integrar com sistema de vendas
- Gerar DANFE (PDF) usando `nfephp-org/sped-da`
- Implementar cancelamento
- Interface para gerenciamento

### Sobre PIX Dinâmico

- Já existe implementação básica no Asaas
- Precisa melhorar polling e webhook
- Integrar com fluxo de caixa

### Sobre Cartão de Crédito/Débito

- Já existe implementação básica no Mercado Pago
- Precisa melhorar processamento e webhook
- Integrar com fluxo de caixa

---

## 🔧 Tecnologias e Bibliotecas Sugeridas

### Para Cupom Fiscal:
- **NFePHP** (`nfephp-org/sped-nfe`) - ✅ JÁ INSTALADA
  - Biblioteca PHP nativa para NFe/NFCe
  - Suporta NFe (modelo 55) e NFCe (modelo 65)
  - Comunicação direta com SEFAZ
  - Documentação: https://github.com/nfephp-org/sped-nfe
- **NFePHP DA** (`nfephp-org/sped-da`) - ✅ JÁ INSTALADA
  - Geração de DANFE (Documento Auxiliar)
  - Geração de PDF

### Para Integrações:
- **Mercado Pago SDK PHP** - Já em uso
- **Guzzle HTTP** - Já em uso para Asaas
- **Webhooks** - Sistema de notificações já parcialmente implementado

### Para Relatórios:
- **TCPDF** - Geração de PDFs
- **PhpSpreadsheet** - Exportação Excel
- **Chart.js** - Gráficos no dashboard

---

## 📈 Métricas de Sucesso

### Funcionalidades Críticas:
- ✅ Vendas geram movimentações no caixa automaticamente
- ✅ Pagamentos via gateway atualizam status e caixa
- ✅ Split de pagamentos funciona corretamente
- ✅ Cupom fiscal é emitido automaticamente após venda
- ✅ Contas a pagar são geradas a partir de compras
- ✅ Relatórios financeiros são precisos

### Performance:
- Processamento de pagamento < 3 segundos
- Emissão de cupom fiscal < 5 segundos
- Relatórios gerados < 10 segundos

---

## ⚠️ Riscos e Desafios

1. **Certificado Digital (NFe/NFCe):** Requer investimento e renovação anual
2. **Complexidade de Impostos:** Cálculo correto de ICMS, IPI, PIS, COFINS requer conhecimento fiscal
3. **APIs de Terceiros:** Dependência de serviços externos (Mercado Pago, Asaas)
4. **Webhooks:** Requer servidor acessível publicamente (HTTPS)
5. **Split de Pagamentos:** Regras complexas de divisão e taxas
6. **Conciliação:** Algoritmos complexos de matching
7. **SEFAZ:** Mudanças na legislação podem exigir atualizações na biblioteca NFePHP

---

## 📚 Documentação de Referência

- **NFePHP (Biblioteca em Uso):**
  - GitHub: https://github.com/nfephp-org/sped-nfe
  - Documentação: https://github.com/nfephp-org/sped-nfe/wiki
  - Exemplos: https://github.com/nfephp-org/sped-nfe/tree/master/examples
  - NFePHP DA (DANFE): https://github.com/nfephp-org/sped-da
  
- **Mercado Pago Split Payments:**
  - https://www.mercadopago.com.br/developers/pt/docs/marketplace/checkout-api/split-payments
  
- **Asaas API:**
  - https://docs.asaas.com/
  
- **SEFAZ (Documentação Oficial):**
  - Manual de Integração NFe: http://www.nfe.fazenda.gov.br/
  - Manual de Integração NFCe: http://www.nfce.fazenda.gov.br/

---

**Documento criado em:** 2025-12-07  
**Versão:** 1.0  
**Autor:** Análise do Sistema THAUSZ-PULSE

