# Plano de Correção: Database Exception - Itens Avulsos

Este plano descreve as alterações necessárias para corrigir o erro de SQL que impede o carregamento do relatório de Itens Avulsos no módulo de vendas.

## Problema
Ao acessar a rota `/vendas/produto/itens-avulsos`, o sistema lança uma exceção informando que a relação `prest_venda` não existe. Isso ocorre devido a um erro de digitação na query de join no controlador.

## Mudanças Propostas

### Módulo de Vendas

#### [MODIFY] [ProdutoController.php](file:///srv/http/pulse/modules/vendas/controllers/ProdutoController.php)
- Corrigir a cláusula `innerJoin` no método `actionItensAvulsos`.
- Substituir o nome da tabela fixo `prest_venda` (singular) pelo correto `prest_vendas` (plural) ou preferencialmente usar `Venda::tableName()`.

```php
// Localizado na Action actionItensAvulsos (aproximadamente linha 934)
->innerJoin(\app\modules\vendas\models\Venda::tableName() . ' v', 'v.id = vi.venda_id')
```

## Plano de Verificação

### Verificação Manual
1. Acessar a página `http://localhost/vendas/produto/itens-avulsos`.
2. Validar se a lista de itens avulsos é exibida corretamente.
3. Testar o botão "Cadastrar Produto" para um dos itens da lista.

### Logs
- Monitorar `/srv/http/pulse/runtime/logs/app.log` para garantir que nenhuma nova exceção de banco de dados seja registrada durante a execução da query.
