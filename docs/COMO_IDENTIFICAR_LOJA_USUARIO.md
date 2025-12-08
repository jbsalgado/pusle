# 🏪 Como Identificar a Qual Loja Pertence um Registro

## 📋 Conceito Fundamental

**No sistema, NÃO existe uma tabela separada de "loja" ou "empresa".**

O próprio registro em `prest_usuarios` **representa o dono da loja/empresa**. Cada registro em `prest_usuarios` é uma loja diferente.

---

## 🔗 Estrutura de Relacionamento

### **Hierarquia:**

```
prest_usuarios (Dono da Loja)
    │
    ├── prest_clientes (Clientes da Loja)
    │   └── usuario_id → FK para prest_usuarios.id
    │
    ├── prest_produtos (Produtos da Loja)
    │   └── usuario_id → FK para prest_usuarios.id
    │
    ├── prest_vendas (Vendas da Loja)
    │   └── usuario_id → FK para prest_usuarios.id
    │
    ├── prest_colaboradores (Funcionários da Loja)
    │   └── usuario_id → FK para prest_usuarios.id
    │
    ├── prest_caixa (Caixas da Loja)
    │   └── usuario_id → FK para prest_usuarios.id
    │
    └── ... (todas as outras tabelas)
        └── usuario_id → FK para prest_usuarios.id
```

---

## 🎯 Como Identificar a Loja

### **1. Para um `prest_usuario` (Dono da Loja):**

A loja **É** o próprio registro em `prest_usuarios`:

```php
$usuario = Usuario::findOne($id);
// $usuario->id = ID da loja
// $usuario->nome = Nome do dono (representa a loja)
```

**Cada registro em `prest_usuarios` = Uma loja diferente**

---

### **2. Para qualquer outra entidade (Cliente, Produto, Venda, etc.):**

A loja é identificada pelo campo `usuario_id`:

```php
// Exemplo: Cliente
$cliente = Cliente::findOne($id);
$lojaId = $cliente->usuario_id; // ID da loja (prest_usuarios.id)

// Exemplo: Produto
$produto = Produto::findOne($id);
$lojaId = $produto->usuario_id; // ID da loja

// Exemplo: Venda
$venda = Venda::findOne($id);
$lojaId = $venda->usuario_id; // ID da loja
```

---

## 📊 Tabelas que Têm `usuario_id`

Todas as tabelas abaixo têm o campo `usuario_id` que referencia `prest_usuarios.id`:

| Tabela | Campo | Significado |
|--------|-------|-------------|
| `prest_clientes` | `usuario_id` | Cliente pertence à loja do usuário |
| `prest_produtos` | `usuario_id` | Produto pertence à loja do usuário |
| `prest_vendas` | `usuario_id` | Venda pertence à loja do usuário |
| `prest_parcelas` | `usuario_id` | Parcela pertence à loja do usuário |
| `prest_colaboradores` | `usuario_id` | Colaborador trabalha para a loja do usuário |
| `prest_caixa` | `usuario_id` | Caixa pertence à loja do usuário |
| `prest_contas_pagar` | `usuario_id` | Conta a pagar pertence à loja do usuário |
| `prest_categorias` | `usuario_id` | Categoria pertence à loja do usuário |
| `prest_formas_pagamento` | `usuario_id` | Forma de pagamento pertence à loja do usuário |
| `prest_fornecedores` | `usuario_id` | Fornecedor pertence à loja do usuário |
| `prest_compras` | `usuario_id` | Compra pertence à loja do usuário |
| `prest_configuracoes` | `usuario_id` | Configuração pertence à loja do usuário |
| `prest_rotas_cobranca` | `usuario_id` | Rota de cobrança pertence à loja do usuário |
| `prest_periodos_cobranca` | `usuario_id` | Período de cobrança pertence à loja do usuário |
| `prest_regioes` | `usuario_id` | Região pertence à loja do usuário |
| `prest_orcamentos` | `usuario_id` | Orçamento pertence à loja do usuário |
| `prest_comissoes` | `usuario_id` | Comissão pertence à loja do usuário |
| `prest_estoque_movimentacoes` | `usuario_id` | Movimentação de estoque pertence à loja do usuário |
| `prest_carteira_cobranca` | `usuario_id` | Carteira de cobrança pertence à loja do usuário |
| `prest_historico_cobranca` | `usuario_id` | Histórico de cobrança pertence à loja do usuário |
| `prest_regras_parcelamento` | `usuario_id` | Regra de parcelamento pertence à loja do usuário |
| `prest_comissao_config` | `usuario_id` | Configuração de comissão pertence à loja do usuário |
| `prest_vendedores` | `usuario_id` | Vendedor pertence à loja do usuário |

---

## 💻 Exemplos Práticos em Código

### **1. Obter o dono da loja de um cliente:**

```php
$cliente = Cliente::findOne($clienteId);
$donoLoja = Usuario::findOne($cliente->usuario_id);
echo "Cliente pertence à loja: " . $donoLoja->nome;
```

### **2. Listar todos os produtos de uma loja:**

```php
$usuarioId = Yii::$app->user->id; // ID do dono logado
$produtos = Produto::find()
    ->where(['usuario_id' => $usuarioId])
    ->all();
```

### **3. Verificar se um registro pertence à loja do usuário logado:**

```php
$usuarioLogado = Yii::$app->user->id;
$venda = Venda::findOne($vendaId);

if ($venda->usuario_id === $usuarioLogado) {
    echo "Esta venda pertence à sua loja";
} else {
    echo "Esta venda não pertence à sua loja";
}
```

### **4. Filtrar por loja em uma query:**

```php
$usuarioId = Yii::$app->user->id;

// Buscar todas as vendas da loja
$vendas = Venda::find()
    ->where(['usuario_id' => $usuarioId])
    ->all();

// Buscar todos os clientes da loja
$clientes = Cliente::find()
    ->where(['usuario_id' => $usuarioId])
    ->all();
```

### **5. Criar um novo registro vinculado à loja:**

```php
$usuarioId = Yii::$app->user->id;

// Criar novo produto
$produto = new Produto();
$produto->usuario_id = $usuarioId; // Vincula à loja do usuário logado
$produto->nome = "Produto Exemplo";
$produto->save();
```

---

## 🔐 Isolamento de Dados

### **Cada loja vê apenas seus próprios dados:**

```php
// No controller, sempre filtre por usuario_id
public function actionIndex()
{
    $usuarioId = Yii::$app->user->id;
    
    $dataProvider = new ActiveDataProvider([
        'query' => Produto::find()
            ->where(['usuario_id' => $usuarioId]), // Filtro obrigatório
    ]);
    
    return $this->render('index', [
        'dataProvider' => $dataProvider,
    ]);
}
```

### **Validação de acesso:**

```php
public function actionView($id)
{
    $model = Produto::findOne($id);
    $usuarioId = Yii::$app->user->id;
    
    // Verifica se o produto pertence à loja do usuário logado
    if ($model->usuario_id !== $usuarioId) {
        throw new ForbiddenHttpException('Você não tem permissão para acessar este registro.');
    }
    
    return $this->render('view', ['model' => $model]);
}
```

---

## 📝 Resumo

### **Para identificar a loja:**

1. **Se for um `prest_usuario`**: A loja **É** o próprio registro
   - `$usuario->id` = ID da loja

2. **Se for qualquer outra entidade**: A loja é identificada por `usuario_id`
   - `$entidade->usuario_id` = ID da loja (FK para `prest_usuarios.id`)

### **Regra de ouro:**

> **Cada registro em `prest_usuarios` representa uma loja diferente.**
> 
> **Todas as outras tabelas têm `usuario_id` que referencia `prest_usuarios.id`, indicando a qual loja pertencem.**

---

## ⚠️ Importante

- **NÃO existe tabela `loja` ou `empresa` separada**
- **`prest_usuarios` = Dono da Loja = A própria loja**
- **`usuario_id` em qualquer tabela = FK para identificar a loja**
- **Sempre filtre por `usuario_id` para garantir isolamento de dados**

---

**Data:** 2024-12-08
**Status:** ✅ Documentação Completa

