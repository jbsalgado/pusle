# Como Finalizar Cobranças e Marcar Visitas no Módulo Prestanista

## 📋 Visão Geral

O sistema de cobrança prestanista permite:
1. **Registrar pagamentos** de parcelas
2. **Marcar visitas sem pagamento** (cliente ausente, recusa, negociação)
3. **Finalizar cobranças** quando todas as parcelas estão pagas

---

## 🔄 Como Funciona Atualmente

### 1. **Registro de Pagamentos**

**Fluxo atual:**
- Cobrador clica em "PAGAR" em uma parcela
- Abre modal para registrar pagamento
- Seleciona forma de pagamento (DINHEIRO ou PIX)
- Confirma o pagamento
- Sistema registra no histórico com `tipo_acao = 'PAGAMENTO'`
- Parcela é marcada como PAGA

**O que acontece:**
- Parcela é atualizada: `status_parcela_codigo = 'PAGA'`
- Histórico de cobrança é criado em `prest_historico_cobranca` com `tipo_acao = 'PAGAMENTO'`
- **IMPORTANTE:** Quando há pagamento, isso já conta como uma visita registrada automaticamente
- Carteira de cobrança é atualizada automaticamente (parcelas_pagas, valor_recebido)

**Observação importante:**
- ✅ **NÃO é necessário marcar visita separadamente quando recebeu o pagamento**
- ✅ O pagamento já registra a visita automaticamente (tipo_acao = PAGAMENTO)
- ✅ O botão "MARCAR VISITA" só aparece quando há parcelas pendentes
- ✅ Se todas as parcelas estão pagas, o botão não aparece (mostra "✓ Todas as parcelas pagas")

---

### 2. **Finalizar Cobranças (Quando Não Há Mais Nada a Receber)**

**Como funciona:**
- A **Carteira de Cobrança** (`prest_carteira_cobranca`) tem um método `getStatusCobranca()` que retorna:
  - `'QUITADO'` quando `parcelas_pagas >= total_parcelas`
  - `'PARCIAL'` quando há parcelas pagas mas ainda há pendentes
  - `'PENDENTE'` quando nenhuma parcela foi paga

**Status automático:**
- Quando todas as parcelas de um cliente são pagas, a carteira automaticamente fica com status `QUITADO`
- Não é necessário fazer nada manualmente - o sistema calcula automaticamente

**Onde verificar:**
- Acesse: `http://localhost/pulse/basic/web/index.php/vendas/carteira-cobranca/index`
- Filtre por período e cobrador
- Veja o status de cada carteira (QUITADO, PARCIAL, PENDENTE)

**Observação:**
- Atualmente não há um botão específico no app para "finalizar" manualmente
- O sistema finaliza automaticamente quando todas as parcelas estão pagas
- A carteira pode ser desativada (`ativo = false`) manualmente no sistema web se necessário

---

### 3. **Marcar Visita Sem Pagamento (FUNCIONALIDADE FALTANTE)**

**Tipos de ação disponíveis no sistema:**
- `VISITA` - Cliente foi visitado (sem pagamento)
- `AUSENTE` - Cliente estava ausente
- `RECUSA` - Cliente recusou pagar
- `NEGOCIACAO` - Cliente negociou (sem pagamento na hora)
- `PAGAMENTO` - Pagamento realizado (já implementado)

**Status atual:**
- ❌ **NÃO IMPLEMENTADO** no app Prestanista
- O app só permite registrar `PAGAMENTO`
- Não há opção para marcar visita sem pagamento

**O que precisa ser implementado:**
1. Botões no card de cada cliente para marcar visita sem pagamento
2. Modal ou opções rápidas para selecionar tipo de visita (AUSENTE, RECUSA, NEGOCIACAO)
3. Registro no histórico de cobrança com `tipo_acao` apropriado
4. Atualização visual do card para mostrar que foi visitado

---

## 🛠️ Implementação Necessária

### **Funcionalidade 1: Marcar Visita Sem Pagamento**

**Onde adicionar:**
- No card de cada venda/cliente
- Botão "VISITADO" ou "MARCAR VISITA"

**Fluxo:**
1. Cobrador clica em "MARCAR VISITA"
2. Seleciona tipo: AUSENTE, RECUSA, ou NEGOCIACAO
3. Adiciona observação (opcional)
4. Sistema registra no histórico com `tipo_acao` escolhido
5. `valor_recebido = 0` (não houve pagamento)
6. Card é marcado visualmente como "visitado"

**API necessária:**
- Endpoint: `POST /api/cobranca/registrar-visita`
- Ou usar o mesmo endpoint atual com `tipo_acao` diferente de PAGAMENTO

### **Funcionalidade 2: Finalizar Cobrança Manualmente**

**Onde adicionar:**
- No card quando todas as parcelas estão pagas
- Botão "FINALIZAR COBRANÇA"

**Fluxo:**
1. Sistema verifica se todas as parcelas estão pagas
2. Se sim, mostra botão "FINALIZAR COBRANÇA"
3. Ao clicar, marca a carteira como `ativo = false`
4. Remove da rota do dia (não aparece mais na lista)

---

## 📊 Estrutura de Dados

### **Tabela: `prest_historico_cobranca`**

```sql
- id (UUID)
- parcela_id (UUID) - Parcela relacionada
- cobrador_id (UUID) - Cobrador que fez a ação
- cliente_id (UUID) - Cliente visitado
- usuario_id (UUID) - Loja/usuário
- tipo_acao (VARCHAR) - VISITA, PAGAMENTO, AUSENTE, RECUSA, NEGOCIACAO
- valor_recebido (DECIMAL) - 0 para visitas sem pagamento
- observacao (TEXT) - Observações da visita
- localizacao_lat (DECIMAL) - Latitude (opcional)
- localizacao_lng (DECIMAL) - Longitude (opcional)
- data_acao (TIMESTAMP) - Data/hora da ação
```

### **Tabela: `prest_carteira_cobranca`**

```sql
- id (UUID)
- periodo_id (UUID)
- cobrador_id (UUID)
- cliente_id (UUID)
- ativo (BOOLEAN) - true = em cobrança, false = finalizada
- total_parcelas (INTEGER)
- parcelas_pagas (INTEGER)
- valor_total (DECIMAL)
- valor_recebido (DECIMAL)
```

**Status automático:**
- `parcelas_pagas >= total_parcelas` → Status = QUITADO
- Pode ser desativado manualmente (`ativo = false`) para remover da rota

---

## ✅ Resumo

### **Finalizar Cobranças:**
- ✅ **Já funciona automaticamente** quando todas as parcelas são pagas
- ✅ Status da carteira muda para `QUITADO` automaticamente
- ⚠️ Pode ser desativado manualmente no sistema web se necessário

### **Marcar Visita Sem Pagamento:**
- ❌ **NÃO IMPLEMENTADO** no app Prestanista
- ✅ Sistema backend já suporta (tipos de ação existem)
- 🔧 **PRECISA SER IMPLEMENTADO** no frontend (app.js)

---

## 🎯 Próximos Passos

1. Adicionar botão "MARCAR VISITA" nos cards
2. Criar modal para selecionar tipo de visita (AUSENTE, RECUSA, NEGOCIACAO)
3. Implementar função para registrar visita sem pagamento
4. Atualizar visual dos cards para mostrar visitas realizadas
5. Adicionar botão "FINALIZAR COBRANÇA" quando todas as parcelas estão pagas

