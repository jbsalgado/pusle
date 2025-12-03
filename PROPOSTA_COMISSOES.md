# 💡 Proposta: Sistema Flexível de Configuração de Comissões

## 📋 Resumo da Proposta

Sistema completo para gerenciar configurações de comissões de forma flexível, permitindo que cada colaborador tenha múltiplas configurações com diferentes percentuais aplicados por categoria ou para todas as categorias.

## ✨ Características Principais

### 🎯 Flexibilidade Total
- ✅ Múltiplas configurações por colaborador
- ✅ Diferentes percentuais por tipo (Venda/Cobrança)
- ✅ Configurações específicas por categoria
- ✅ Configurações gerais (aplicam para todas as categorias)
- ✅ Controle de vigência (período de validade)
- ✅ Ativação/desativação

### 🏗️ Arquitetura Proposta

#### Nova Tabela: `prest_comissao_config`

```
┌─────────────────────────────────────┐
│ prest_comissao_config               │
├─────────────────────────────────────┤
│ id (PK)                             │
│ usuario_id (FK)                     │
│ colaborador_id (FK)                 │
│ tipo_comissao (VENDA/COBRANCA)      │
│ categoria_id (FK, nullable)         │
│   └─ NULL = todas as categorias     │
│ percentual (0-100)                  │
│ ativo (boolean)                     │
│ data_inicio (date, nullable)        │
│ data_fim (date, nullable)           │
│ observacoes (text)                  │
│ data_criacao                        │
│ data_atualizacao                    │
└─────────────────────────────────────┘
```

#### Atualização: `prest_comissoes`

Adicionar coluna `comissao_config_id` para referenciar qual configuração foi usada.

## 🔄 Como Funciona

### 1. Configuração

Crie configurações no módulo `/vendas/comissao-config`:

**Exemplo de configurações para João Silva:**

```
Configuração 1:
  Colaborador: João Silva
  Tipo: Venda
  Categoria: NULL (Todas)
  Percentual: 5%
  
Configuração 2:
  Colaborador: João Silva
  Tipo: Venda
  Categoria: Eletrônicos
  Percentual: 7%
  
Configuração 3:
  Colaborador: João Silva
  Tipo: Cobrança
  Categoria: NULL (Todas)
  Percentual: 2%
```

### 2. Cálculo de Comissão

Quando uma comissão precisa ser calculada:

1. Sistema busca configuração mais específica:
   - Primeiro: Busca configuração para categoria específica
   - Se não encontrar: Busca configuração geral (categoria_id = NULL)

2. Aplica o percentual encontrado

3. Registra na tabela `prest_comissoes` com referência à configuração

### 3. Prioridade de Busca

```
1. Configuração específica da categoria (se existir)
   ↓
2. Configuração geral (categoria_id = NULL)
   ↓
3. Percentual padrão do colaborador (compatibilidade retroativa)
```

## 📁 Arquivos Criados

### ✅ Migrations
- `migrations/m241212_000001_create_prest_comissao_config.php` - Cria a tabela de configurações
- `migrations/m241212_000002_add_comissao_config_id_to_prest_comissoes.php` - Adiciona FK na tabela de comissões

### ✅ Models
- `modules/vendas/models/ComissaoConfig.php` - Model completo com:
  - Validações
  - Relações
  - Método estático `buscarConfiguracao()` para encontrar a configuração aplicável
  - Método `isVigente()` para verificar se está em vigência

### ✅ Atualizações
- `modules/vendas/models/Comissao.php` - Adicionada relação com ComissaoConfig

### ⏳ Pendente
- Controller `ComissaoConfigController.php`
- Views CRUD (index, create, update, view, _form)
- Atualização da lógica de cálculo para usar as configurações

## 🎨 Interface Proposta

### Listagem de Configurações
- Cards/tabela mostrando:
  - Colaborador
  - Tipo de comissão
  - Categoria (ou "Todas")
  - Percentual
  - Status (ativo/inativo)
  - Vigência

### Formulário de Configuração
- Seleção de colaborador
- Tipo (Venda/Cobrança)
- Categoria (dropdown com opção "Todas as Categorias")
- Percentual (0-100)
- Datas de vigência (opcional)
- Ativo/Inativo
- Observações

## 🔧 Próximos Passos

1. ✅ Criar migrations
2. ✅ Criar model ComissaoConfig
3. ⏳ Criar controller e views CRUD
4. ⏳ Atualizar lógica de cálculo de comissões
5. ⏳ Criar script de migração de dados existentes (opcional)

## 💡 Casos de Uso Reais

### Caso 1: Loja com categorias premium
```
Vendedor: Maria
- Vendas Premium (Eletrônicos): 10%
- Vendas Normais: 5%
- Cobranças: 1%
```

### Caso 2: Promoção temporária
```
Vendedor: Pedro
- Vendas em dezembro: 8% (01/12/2024 - 31/12/2024)
- Vendas normais: 5%
```

### Caso 3: Comissão diferenciada
```
Vendedor: Ana
- Vendas Roupas: 12%
- Vendas Calçados: 8%
- Vendas Outras: 6%
- Cobranças: 2%
```

## 📝 Observações

- Sistema mantém compatibilidade retroativa (usa percentuais do colaborador se não houver configuração)
- Validações impedem configurações duplicadas conflitantes
- Histórico preservado (comissões calculadas referenciam a configuração usada)
- Fácil expansão futura (podemos adicionar regras mais complexas)

## 🚀 Benefícios

1. **Flexibilidade**: Configure diferentes comissões facilmente
2. **Organização**: Separa configuração de registros
3. **Rastreabilidade**: Saiba qual configuração foi usada em cada comissão
4. **Escalabilidade**: Fácil adicionar novas regras no futuro
5. **Manutenção**: Facilita gestão de comissões complexas

