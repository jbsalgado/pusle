# Refatoração: Precificação Inteligente com Tabela Separada

## 📋 Resumo da Refatoração

A implementação inicial adicionava campos diretamente na tabela `prest_produtos`, o que causaria duplicação de dados em milhares de produtos. A refatoração criou uma **tabela separada** `prest_dados_financeiros` para armazenar as configurações financeiras de forma centralizada.

## ✅ Vantagens da Nova Abordagem

1. **Normalização**: Evita repetição de dados (5000 produtos não precisam repetir as mesmas taxas)
2. **Flexibilidade**: Permite configuração global por loja ou específica por produto
3. **Manutenibilidade**: Mudanças nas taxas globais não precisam atualizar milhares de registros
4. **Performance**: Tabela menor, queries mais rápidas
5. **Escalabilidade**: Facilita adicionar novos tipos de taxas no futuro

## 🗄️ Nova Estrutura do Banco de Dados

### Tabela: `prest_dados_financeiros`

```sql
CREATE TABLE prest_dados_financeiros (
    id SERIAL PRIMARY KEY,
    usuario_id UUID NOT NULL,              -- ID do usuário/loja
    produto_id UUID NULL,                  -- NULL = global, preenchido = específico
    taxa_fixa_percentual DECIMAL(5,2) DEFAULT 0.00,
    taxa_variavel_percentual DECIMAL(5,2) DEFAULT 0.00,
    lucro_liquido_percentual DECIMAL(5,2) DEFAULT 0.00,
    data_criacao TIMESTAMP DEFAULT NOW(),
    data_atualizacao TIMESTAMP DEFAULT NOW(),
    
    FOREIGN KEY (usuario_id) REFERENCES prest_usuarios(id),
    FOREIGN KEY (produto_id) REFERENCES prest_produtos(id),
    UNIQUE (usuario_id, produto_id)
);
```

### Estrutura de Dados

- **Configuração Global**: `produto_id = NULL` → Aplicada a todos os produtos da loja
- **Configuração Específica**: `produto_id = UUID` → Aplicada apenas ao produto específico

## 💻 Arquivos Criados/Modificados

### ✅ Criados

1. **`migrations/m250101_000007_create_prest_dados_financeiros.php`**
   - Cria a nova tabela `prest_dados_financeiros`
   - Define índices e foreign keys
   - Suporta configuração global e específica

2. **`modules/vendas/models/DadosFinanceiros.php`**
   - Model para a nova tabela
   - Métodos auxiliares:
     - `getConfiguracaoGlobal($usuarioId)` - Busca/cria configuração global
     - `getConfiguracaoParaProduto($produtoId, $usuarioId)` - Busca específica ou global
     - `calcularPrecoVendaSugerido($precoCusto)` - Calcula preço usando a configuração
     - `resultariaEmPrejuizo($precoVenda, $precoCusto)` - Valida prejuízo

### ✅ Modificados

1. **`modules/vendas/models/Produto.php`**
   - Removidos campos: `taxa_fixa_percentual`, `taxa_variavel_percentual`, `lucro_liquido_percentual`
   - Adicionada relação: `getDadosFinanceiros()` e `getDadosFinanceirosOuGlobal()`
   - Validação de prejuízo atualizada para usar `DadosFinanceiros`

2. **`modules/vendas/controllers/ProdutoController.php`**
   - `actionCreate()`: Carrega e salva dados financeiros
   - `actionUpdate()`: Carrega e salva dados financeiros
   - Suporta configuração específica por produto

3. **`modules/vendas/views/produto/_form.php`**
   - Campos agora usam `DadosFinanceiros` model
   - Checkbox para escolher entre configuração global ou específica
   - JavaScript atualizado para trabalhar com a nova estrutura

## 🔄 Como Funciona

### 1. Configuração Global (Padrão)

Quando um produto é criado, ele usa automaticamente a **configuração global** da loja:

```php
$dadosFinanceiros = DadosFinanceiros::getConfiguracaoGlobal($usuarioId);
```

- Se não existir, cria uma configuração padrão (todas as taxas = 0%)
- Aplicada a todos os produtos que não têm configuração específica

### 2. Configuração Específica (Opcional)

O usuário pode optar por criar uma configuração específica para um produto:

1. Marca o checkbox "Usar configuração específica para este produto"
2. Preenche as taxas desejadas
3. Ao salvar, cria um registro em `prest_dados_financeiros` com `produto_id` preenchido

### 3. Busca de Configuração

O sistema sempre busca primeiro a configuração específica, depois a global:

```php
$config = DadosFinanceiros::getConfiguracaoParaProduto($produtoId, $usuarioId);
```

## 📊 Exemplo de Uso

### Cenário 1: Loja com Taxas Padrão

```php
// Configuração Global (criada automaticamente)
usuario_id: "abc-123"
produto_id: NULL
taxa_fixa_percentual: 5.00
taxa_variavel_percentual: 3.00
lucro_liquido_percentual: 20.00

// Todos os produtos usam esta configuração
```

### Cenário 2: Produto com Taxas Especiais

```php
// Configuração Específica
usuario_id: "abc-123"
produto_id: "prod-456"
taxa_fixa_percentual: 8.00  // Taxa maior para este produto
taxa_variavel_percentual: 3.00
lucro_liquido_percentual: 25.00  // Lucro maior

// Apenas este produto usa esta configuração
```

## 🚀 Como Executar

1. **Execute a migration:**
```bash
php yii migrate
```

2. **Acesse o formulário de produto:**
   - A seção "Precificação Inteligente" carregará a configuração global
   - Em edição, você pode optar por usar configuração específica

3. **Configure taxas globais:**
   - Acesse qualquer produto
   - Configure as taxas (será salva como global se não marcar o checkbox)
   - Ou crie uma tela específica para configuração global (futuro)

## 🔮 Melhorias Futuras Sugeridas

1. **Tela de Configuração Global**
   - Criar `ConfiguracaoFinanceiraController`
   - Permitir configurar taxas globais sem precisar acessar um produto

2. **Histórico de Mudanças**
   - Adicionar tabela de histórico para rastrear mudanças nas taxas

3. **Categorias de Taxas**
   - Permitir diferentes taxas por categoria de produto

4. **Importação em Massa**
   - Permitir aplicar configuração específica para múltiplos produtos

## 📝 Notas Técnicas

- A tabela `prest_produtos` **não foi modificada** (mantém compatibilidade)
- A busca de configuração é otimizada com índices
- Validações garantem que não há prejuízo
- O sistema funciona mesmo se não houver configuração (usa valores padrão)

---

**Data da Refatoração:** Janeiro 2025  
**Versão:** 2.0.0 (Refatorada)

