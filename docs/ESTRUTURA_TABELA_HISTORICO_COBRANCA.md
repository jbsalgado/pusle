# Estrutura da Tabela `prest_historico_cobranca`

## 📋 Tabela: `prest_historico_cobranca`

Esta é a tabela onde **TODAS as visitas e pagamentos são registrados**, incluindo:
- ✅ Pagamentos recebidos
- ✅ Visitas sem pagamento (AUSENTE, RECUSA, NEGOCIACAO, VISITA)

---

## 🗄️ Estrutura da Tabela

### **Colunas:**

| Coluna | Tipo | Descrição | Obrigatório |
|--------|------|-----------|-------------|
| `id` | UUID | Identificador único do registro | ✅ Sim (PK) |
| `parcela_id` | UUID | Referência à parcela relacionada | ✅ Sim (FK) |
| `cobrador_id` | UUID | Cobrador que realizou a ação | ✅ Sim (FK) |
| `cliente_id` | UUID | Cliente visitado | ✅ Sim (FK) |
| `usuario_id` | UUID | Loja/usuário (dono da cobrança) | ✅ Sim (FK) |
| `tipo_acao` | VARCHAR(20) | Tipo de ação realizada | ✅ Sim |
| `valor_recebido` | DECIMAL(10,2) | Valor recebido (0 para visitas sem pagamento) | ✅ Sim |
| `observacao` | TEXT | Observações sobre a visita/pagamento | ❌ Não |
| `localizacao_lat` | DECIMAL(10,8) | Latitude da localização (GPS) | ❌ Não |
| `localizacao_lng` | DECIMAL(11,8) | Longitude da localização (GPS) | ❌ Não |
| `data_acao` | TIMESTAMP | Data e hora da ação | ✅ Sim |

### **Índices:**

- `idx_hist_cobranca_parcela_id` - Índice em `parcela_id`
- `idx_hist_cobranca_cobrador_id` - Índice em `cobrador_id`
- `idx_hist_cobranca_data` - Índice em `data_acao`

### **Foreign Keys:**

- `parcela_id` → `prest_parcelas(id)`
- `cobrador_id` → `prest_colaboradores(id)`
- `cliente_id` → `prest_clientes(id)`
- `usuario_id` → `prest_usuarios(id)`

---

## 📝 Tipos de Ação (`tipo_acao`)

A coluna `tipo_acao` aceita os seguintes valores:

| Valor | Descrição | Quando Usar |
|-------|-----------|-------------|
| `PAGAMENTO` | Pagamento recebido | Quando o cobrador recebe o pagamento de uma parcela |
| `VISITA` | Visita realizada | Quando visitou o cliente mas não houve pagamento |
| `AUSENTE` | Cliente ausente | Quando o cliente não estava em casa |
| `RECUSA` | Recusa de pagamento | Quando o cliente recusou pagar |
| `NEGOCIACAO` | Negociação | Quando houve negociação mas sem pagamento |

---

## 🔄 Como é Registrada a Visita

### **1. Quando há PAGAMENTO:**

```php
// No CobrancaController.php - actionRegistrarPagamento()
$historico = new HistoricoCobranca();
$historico->parcela_id = $data['parcela_id'];
$historico->cobrador_id = $data['cobrador_id'];
$historico->cliente_id = $data['cliente_id'];
$historico->usuario_id = $data['usuario_id'];
$historico->tipo_acao = 'PAGAMENTO'; // ✅ Já conta como visita
$historico->valor_recebido = $data['valor_recebido']; // Valor da parcela
$historico->observacao = $data['observacao'] ?? '';
$historico->localizacao_lat = $data['localizacao_lat'] ?? null;
$historico->localizacao_lng = $data['localizacao_lng'] ?? null;
$historico->data_acao = date('Y-m-d H:i:s');
$historico->save();
```

**O que acontece:**
- ✅ Parcela é atualizada: `status_parcela_codigo = 'PAGA'`
- ✅ Histórico é criado com `tipo_acao = 'PAGAMENTO'`
- ✅ Visita é registrada automaticamente (não precisa marcar separadamente)

### **2. Quando NÃO há pagamento (visita sem pagamento):**

```php
// No CobrancaController.php - actionRegistrarPagamento()
$historico = new HistoricoCobranca();
$historico->parcela_id = $data['parcela_id']; // Primeira parcela pendente
$historico->cobrador_id = $data['cobrador_id'];
$historico->cliente_id = $data['cliente_id'];
$historico->usuario_id = $data['usuario_id'];
$historico->tipo_acao = $data['tipo_acao']; // AUSENTE, RECUSA, NEGOCIACAO, VISITA
$historico->valor_recebido = 0; // Sem pagamento
$historico->observacao = $data['observacao'] ?? '';
$historico->localizacao_lat = $data['localizacao_lat'] ?? null;
$historico->localizacao_lng = $data['localizacao_lng'] ?? null;
$historico->data_acao = date('Y-m-d H:i:s');
$historico->save();
```

**O que acontece:**
- ❌ Parcela NÃO é atualizada (permanece PENDENTE)
- ✅ Histórico é criado com `tipo_acao` escolhido
- ✅ Visita é registrada sem pagamento

---

## ✅ A Estrutura da Tabela Está Completa?

### **Resposta: SIM ✅**

A tabela `prest_historico_cobranca` já possui **TODOS os campos necessários** para registrar visitas:

1. ✅ **Identificação:**
   - `id` (UUID) - Identificador único
   - `parcela_id`, `cobrador_id`, `cliente_id`, `usuario_id` - Relacionamentos

2. ✅ **Tipo de Ação:**
   - `tipo_acao` (VARCHAR) - Suporta todos os tipos: PAGAMENTO, VISITA, AUSENTE, RECUSA, NEGOCIACAO

3. ✅ **Valor:**
   - `valor_recebido` (DECIMAL) - Pode ser 0 para visitas sem pagamento

4. ✅ **Informações Adicionais:**
   - `observacao` (TEXT) - Para anotações
   - `localizacao_lat`, `localizacao_lng` (DECIMAL) - GPS
   - `data_acao` (TIMESTAMP) - Data/hora da ação

### **Não precisa mudar nada na estrutura!**

A tabela já está preparada para:
- ✅ Registrar pagamentos
- ✅ Registrar visitas sem pagamento
- ✅ Armazenar geolocalização
- ✅ Armazenar observações
- ✅ Rastrear histórico completo de cobranças

---

## 📊 Exemplo de Registros

### **Exemplo 1: Pagamento Recebido**

```sql
INSERT INTO prest_historico_cobranca (
    id, parcela_id, cobrador_id, cliente_id, usuario_id,
    tipo_acao, valor_recebido, observacao,
    localizacao_lat, localizacao_lng, data_acao
) VALUES (
    gen_random_uuid(),
    'parcela-uuid-123',
    'cobrador-uuid-456',
    'cliente-uuid-789',
    'usuario-uuid-abc',
    'PAGAMENTO',
    150.00,
    'Pagamento recebido em dinheiro',
    -23.550520,
    -46.633308,
    NOW()
);
```

### **Exemplo 2: Cliente Ausente**

```sql
INSERT INTO prest_historico_cobranca (
    id, parcela_id, cobrador_id, cliente_id, usuario_id,
    tipo_acao, valor_recebido, observacao,
    localizacao_lat, localizacao_lng, data_acao
) VALUES (
    gen_random_uuid(),
    'parcela-uuid-123',
    'cobrador-uuid-456',
    'cliente-uuid-789',
    'usuario-uuid-abc',
    'AUSENTE',
    0.00,
    'Cliente não estava em casa, portão fechado',
    -23.550520,
    -46.633308,
    NOW()
);
```

### **Exemplo 3: Cliente Recusou Pagamento**

```sql
INSERT INTO prest_historico_cobranca (
    id, parcela_id, cobrador_id, cliente_id, usuario_id,
    tipo_acao, valor_recebido, observacao,
    localizacao_lat, localizacao_lng, data_acao
) VALUES (
    gen_random_uuid(),
    'parcela-uuid-123',
    'cobrador-uuid-456',
    'cliente-uuid-789',
    'usuario-uuid-abc',
    'RECUSA',
    0.00,
    'Cliente disse que não tem dinheiro agora, prometeu pagar na próxima semana',
    -23.550520,
    -46.633308,
    NOW()
);
```

---

## 🔍 Consultas Úteis

### **Ver todas as visitas de um cliente:**

```sql
SELECT 
    hc.data_acao,
    hc.tipo_acao,
    hc.valor_recebido,
    hc.observacao,
    c.nome_completo AS cobrador
FROM prest_historico_cobranca hc
JOIN prest_colaboradores c ON c.id = hc.cobrador_id
WHERE hc.cliente_id = 'cliente-uuid-789'
ORDER BY hc.data_acao DESC;
```

### **Ver visitas sem pagamento:**

```sql
SELECT 
    hc.data_acao,
    hc.tipo_acao,
    hc.observacao,
    cl.nome_completo AS cliente,
    c.nome_completo AS cobrador
FROM prest_historico_cobranca hc
JOIN prest_clientes cl ON cl.id = hc.cliente_id
JOIN prest_colaboradores c ON c.id = hc.cobrador_id
WHERE hc.tipo_acao IN ('VISITA', 'AUSENTE', 'RECUSA', 'NEGOCIACAO')
ORDER BY hc.data_acao DESC;
```

### **Ver histórico de um cobrador no dia:**

```sql
SELECT 
    hc.data_acao,
    hc.tipo_acao,
    hc.valor_recebido,
    cl.nome_completo AS cliente
FROM prest_historico_cobranca hc
JOIN prest_clientes cl ON cl.id = hc.cliente_id
WHERE hc.cobrador_id = 'cobrador-uuid-456'
  AND DATE(hc.data_acao) = CURRENT_DATE
ORDER BY hc.data_acao DESC;
```

---

## ✅ Conclusão

**A estrutura da tabela `prest_historico_cobranca` está completa e não precisa de alterações.**

Ela já suporta:
- ✅ Registro de pagamentos
- ✅ Registro de visitas sem pagamento
- ✅ Diferentes tipos de visita (AUSENTE, RECUSA, NEGOCIACAO, VISITA)
- ✅ Geolocalização
- ✅ Observações
- ✅ Histórico completo de todas as ações de cobrança

**Não é necessário criar novas tabelas ou adicionar novos campos.**

