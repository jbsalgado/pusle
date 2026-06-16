# Como Definir Configurações Específicas por Produto

## 📍 Onde são Definidas

As **Configurações Específicas por Produto** podem ser criadas/definidas em **2 lugares**:

---

## 1️⃣ **No Formulário de Produto** (Método Principal)

### Localização:
- **URL**: `/vendas/produto/create` ou `/vendas/produto/update?id={produto_id}`
- **Arquivo**: `modules/vendas/views/produto/_form.php`
- **Controller**: `modules/vendas/controllers/ProdutoController.php`

### Como Funciona:

#### **Passo 1: Acesse o Formulário de Produto**
- Vá em **Produtos** → **Novo Produto** ou **Editar Produto**
- Role até a seção **"Precificação Inteligente (Markup Divisor)"**

#### **Passo 2: Marque o Checkbox (Apenas em Edição)**
```php
// No formulário (_form.php, linha ~184-205)
<?php if (!$model->isNewRecord): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
        <label class="flex items-center cursor-pointer">
            <input type="checkbox" 
                   name="DadosFinanceiros[usar_configuracao_especifica]" 
                   value="1"
                   id="usar-config-especifica"
                   <?= $temConfiguracaoEspecifica ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 border-gray-300 rounded">
            <span class="ml-2 text-sm font-medium text-gray-700">
                Usar configuração específica para este produto
            </span>
        </label>
    </div>
<?php endif; ?>
```

**⚠️ IMPORTANTE**: O checkbox só aparece quando você está **EDITANDO** um produto existente (não aparece na criação).

#### **Passo 3: Preencha as Taxas**
- **Taxas Fixas** (%)
- **Taxas Variáveis** (%)
- **Lucro Líquido Desejado** (%)

#### **Passo 4: Salve o Produto**
- Ao salvar, o sistema verifica se o checkbox está marcado
- Se estiver marcado, cria/atualiza a configuração específica

### Código no Controller:

```php
// ProdutoController::actionUpdate() (linha ~235-256)
$postDadosFinanceiros = Yii::$app->request->post('DadosFinanceiros', []);

// Verifica se deve criar configuração específica
$usarConfiguracaoEspecifica = !empty($postDadosFinanceiros['usar_configuracao_especifica']);

if ($usarConfiguracaoEspecifica) {
    // Busca configuração existente ou cria nova
    $dadosFinanceirosProduto = DadosFinanceiros::find()
        ->where(['produto_id' => $model->id, 'usuario_id' => $model->usuario_id])
        ->one();
    
    if (!$dadosFinanceirosProduto) {
        $dadosFinanceirosProduto = new DadosFinanceiros();
        $dadosFinanceirosProduto->usuario_id = $model->usuario_id;
        $dadosFinanceirosProduto->produto_id = $model->id;
    }
    
    // Salva as taxas
    $dadosFinanceirosProduto->taxa_fixa_percentual = $postDadosFinanceiros['taxa_fixa_percentual'] ?? 0;
    $dadosFinanceirosProduto->taxa_variavel_percentual = $postDadosFinanceiros['taxa_variavel_percentual'] ?? 0;
    $dadosFinanceirosProduto->lucro_liquido_percentual = $postDadosFinanceiros['lucro_liquido_percentual'] ?? 0;
    $dadosFinanceirosProduto->save();
}
```

---

## 2️⃣ **Pela Página de Precificação Inteligente** (Método Alternativo)

### Localização:
- **URL**: `/vendas/dados-financeiros/index`
- **Arquivo**: `modules/vendas/views/dados-financeiros/index.php`
- **Controller**: `modules/vendas/controllers/DadosFinanceirosController.php`

### Como Funciona:

#### **Passo 1: Acesse a Página de Precificação**
- Vá em **Precificação** (card no painel de vendas)
- Ou acesse diretamente: `/vendas/dados-financeiros/index`

#### **Passo 2: Visualize Configurações Específicas**
- Na seção **"Configurações Específicas por Produto"**
- Veja a lista de produtos que já têm configuração específica

#### **Passo 3: Edite ou Crie Nova**
- **Editar existente**: Clique em "Editar" na linha do produto
- **Criar nova**: Acesse um produto e use o método 1 (formulário)

### Código no Controller:

```php
// DadosFinanceirosController::actionProduto() (linha ~48-75)
public function actionProduto($produto_id = null)
{
    $usuarioId = Yii::$app->user->id;
    
    if ($produto_id) {
        // Busca configuração existente ou cria nova
        $model = DadosFinanceiros::find()
            ->where(['produto_id' => $produto_id, 'usuario_id' => $usuarioId])
            ->one();
        
        if (!$model) {
            $model = new DadosFinanceiros();
            $model->usuario_id = $usuarioId;
            $model->produto_id = $produto_id;
            // Carrega valores da configuração global como padrão
            $global = DadosFinanceiros::getConfiguracaoGlobal($usuarioId);
            $model->taxa_fixa_percentual = $global->taxa_fixa_percentual;
            $model->taxa_variavel_percentual = $global->taxa_variavel_percentual;
            $model->lucro_liquido_percentual = $global->lucro_liquido_percentual;
        }
        
        // Salva se receber POST
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            // ...
        }
    }
}
```

---

## 🔄 Fluxo de Decisão

### Quando um Produto Usa Configuração Específica?

O sistema busca na seguinte ordem:

```php
// Model Produto::getDadosFinanceirosOuGlobal()
public function getDadosFinanceirosOuGlobal()
{
    return DadosFinanceiros::getConfiguracaoParaProduto($this->id, $this->usuario_id);
}

// DadosFinanceiros::getConfiguracaoParaProduto()
public static function getConfiguracaoParaProduto($produtoId, $usuarioId)
{
    // 1. Primeiro tenta buscar configuração específica do produto
    $config = self::find()
        ->where(['produto_id' => $produtoId, 'usuario_id' => $usuarioId])
        ->one();
    
    // 2. Se não encontrar, retorna a configuração global
    if (!$config) {
        $config = self::getConfiguracaoGlobal($usuarioId);
    }
    
    return $config;
}
```

**Lógica:**
1. ✅ **Busca específica** → Se existe registro com `produto_id` preenchido
2. ⬇️ **Se não encontrar** → Usa configuração global (`produto_id = NULL`)

---

## 📊 Estrutura no Banco de Dados

### Tabela: `prest_dados_financeiros`

| Campo | Configuração Global | Configuração Específica |
|-------|---------------------|-------------------------|
| `id` | 1 | 2, 3, 4... |
| `usuario_id` | `uuid-loja` | `uuid-loja` |
| `produto_id` | **NULL** ✅ | **UUID do produto** ✅ |
| `taxa_fixa_percentual` | 5.00 | 8.00 (exemplo) |
| `taxa_variavel_percentual` | 3.00 | 3.00 |
| `lucro_liquido_percentual` | 20.00 | 25.00 (exemplo) |

### Exemplo Real:

```sql
-- Configuração Global (aplicada a todos os produtos)
INSERT INTO prest_dados_financeiros 
(usuario_id, produto_id, taxa_fixa_percentual, taxa_variavel_percentual, lucro_liquido_percentual)
VALUES 
('abc-123', NULL, 5.00, 3.00, 20.00);

-- Configuração Específica (apenas para produto XYZ)
INSERT INTO prest_dados_financeiros 
(usuario_id, produto_id, taxa_fixa_percentual, taxa_variavel_percentual, lucro_liquido_percentual)
VALUES 
('abc-123', 'prod-xyz', 8.00, 3.00, 25.00);
```

---

## ✅ Resumo: Como Criar Configuração Específica

### **Método Recomendado (Mais Fácil):**

1. Acesse **Produtos** → **Editar** um produto existente
2. Role até **"Precificação Inteligente"**
3. **Marque o checkbox**: "Usar configuração específica para este produto"
4. Preencha as taxas desejadas
5. Clique em **Salvar**

### **Método Alternativo:**

1. Acesse **Precificação** → Ver lista de produtos
2. Clique em **Editar** na linha de um produto
3. Ajuste as taxas
4. Clique em **Salvar**

---

## 🗑️ Como Remover Configuração Específica

### Opção 1: Pela Página de Precificação
- Acesse `/vendas/dados-financeiros/index`
- Na lista de configurações específicas, clique em **"Remover"**
- O produto voltará a usar a configuração global

### Opção 2: Pelo Formulário de Produto
- Edite o produto
- **Desmarque** o checkbox "Usar configuração específica"
- Salve o produto
- A configuração específica será removida automaticamente

---

## 🔍 Verificação

Para verificar se um produto tem configuração específica:

```php
$produto = Produto::findOne($produtoId);
$temEspecifica = $produto->dadosFinanceiros !== null;

if ($temEspecifica) {
    echo "Produto tem configuração específica";
} else {
    echo "Produto usa configuração global";
}
```

---

**Data:** Janeiro 2025  
**Versão:** 1.0

