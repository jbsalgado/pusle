# 📊 Sistema Flexível de Comissões

## 🎯 Visão Geral

Sistema completo e flexível para gerenciar configurações de comissões por colaborador, permitindo múltiplas configurações com percentuais diferentes aplicadas por categoria ou para todas as categorias.

## 🏗️ Arquitetura

### Estrutura de Tabelas

#### 1. `prest_comissao_config` (Configurações de Comissão)
Tabela que armazena as configurações de comissões. Permite:

- ✅ Múltiplas configurações por colaborador
- ✅ Diferentes percentuais por tipo (Venda ou Cobrança)
- ✅ Configurações específicas por categoria
- ✅ Configurações gerais (todas as categorias)
- ✅ Controle de vigência (data início/fim)
- ✅ Ativação/desativação

**Campos principais:**
- `colaborador_id` - Colaborador que receberá a comissão
- `tipo_comissao` - VENDA ou COBRANCA
- `categoria_id` - NULL (todas) ou ID específico
- `percentual` - Percentual de comissão (0-100)
- `ativo` - Se está ativa
- `data_inicio` / `data_fim` - Vigência (opcional)

#### 2. `prest_comissoes` (Registros de Comissões Calculadas)
Tabela que armazena as comissões já calculadas. Agora possui:

- `comissao_config_id` - Referência à configuração usada
- Mantém todos os campos existentes para histórico

## 🔄 Fluxo de Funcionamento

### 1. Configuração de Comissões

1. Acesse `/vendas/comissao-config/index`
2. Crie configurações para cada colaborador:
   - Configuração geral: `categoria_id = NULL` (aplica para todas)
   - Configuração específica: `categoria_id = ID` (aplica apenas para aquela categoria)

**Exemplo:**
```
João Silva:
  - Venda Geral: 5% (categoria_id = NULL)
  - Venda Eletrônicos: 7% (categoria_id = 123)
  - Cobrança: 2% (categoria_id = NULL)
```

### 2. Cálculo de Comissões

Quando uma comissão precisa ser calculada:

1. Sistema busca a configuração mais específica:
   - Primeiro: Configuração para a categoria específica
   - Se não encontrar: Configuração geral (categoria_id = NULL)
2. Aplica o percentual encontrado
3. Registra na tabela `prest_comissoes` com referência à configuração

### 3. Prioridade de Configurações

O sistema busca na seguinte ordem:

1. **Configuração específica da categoria** (se existir)
2. **Configuração geral** (categoria_id = NULL)
3. Se nenhuma for encontrada, usa percentual padrão do colaborador (compatibilidade retroativa)

## 📋 Casos de Uso

### Caso 1: Vendedor com comissão diferente por categoria

```
Maria - Vendedora:
  - Venda Roupas: 10%
  - Venda Calçados: 8%
  - Venda Geral (outras): 5%
  - Cobrança: 1%
```

**Configuração:**
- Config 1: colaborador_id=Maria, tipo=VENDA, categoria_id=Roupas, percentual=10%
- Config 2: colaborador_id=Maria, tipo=VENDA, categoria_id=Calçados, percentual=8%
- Config 3: colaborador_id=Maria, tipo=VENDA, categoria_id=NULL, percentual=5%
- Config 4: colaborador_id=Maria, tipo=COBRANCA, categoria_id=NULL, percentual=1%

### Caso 2: Vendedor com comissão única

```
Pedro - Vendedor:
  - Venda Geral: 6%
  - Cobrança: 2%
```

**Configuração:**
- Config 1: colaborador_id=Pedro, tipo=VENDA, categoria_id=NULL, percentual=6%
- Config 2: colaborador_id=Pedro, tipo=COBRANCA, categoria_id=NULL, percentual=2%

### Caso 3: Vendedor com comissão temporária

```
Ana - Vendedora:
  - Venda Geral: 7% (01/01/2024 a 31/12/2024)
  - Venda Geral: 5% (após 01/01/2025)
```

**Configuração:**
- Config 1: colaborador_id=Ana, tipo=VENDA, categoria_id=NULL, percentual=7%, data_inicio=2024-01-01, data_fim=2024-12-31
- Config 2: colaborador_id=Ana, tipo=VENDA, categoria_id=NULL, percentual=5%, data_inicio=2025-01-01, data_fim=NULL

## 🚀 Implementação

### Passo 1: Executar Migrations

```bash
php yii migrate
```

Isso criará:
- Tabela `prest_comissao_config`
- Coluna `comissao_config_id` em `prest_comissoes`

### Passo 2: Migrar Dados Existentes (Opcional)

Se houver comissões já configuradas nos colaboradores, criar configurações padrão baseadas nos percentuais atuais.

### Passo 3: Usar o Sistema

1. Acesse `/vendas/comissao-config` para gerenciar configurações
2. O cálculo de comissões será atualizado para usar as configurações

## 📝 Notas Importantes

### Compatibilidade Retroativa

O sistema mantém compatibilidade com o sistema anterior:
- Se não houver configuração na nova tabela, usa os percentuais do colaborador
- Comissões já calculadas continuam válidas

### Validações

- Não permite configurações duplicadas (mesmo colaborador + tipo + categoria + vigência)
- Valida que data_fim > data_inicio
- Valida que percentual está entre 0-100

### Performance

- Índices criados para busca rápida
- Busca específica antes da geral (mais rápida)
- Cache de configurações pode ser implementado futuramente

## 🔧 Próximos Passos Sugeridos

1. ✅ Criar CRUD completo para ComissaoConfig
2. ⏳ Criar interface de migração de dados existentes
3. ⏳ Adicionar relatórios de comissões por configuração
4. ⏳ Implementar histórico de alterações de configurações
5. ⏳ Adicionar validação de sobreposição de configurações

