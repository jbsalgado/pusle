# Padrão para Botão "Voltar para Produtos"

Este arquivo documenta o padrão usado para adicionar o botão "Voltar para Produtos" nas views do módulo vendas.

## Padrão do Botão

```php
<?= Html::a(
    '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>Produtos',
    ['/vendas/produto/index'],
    ['class' => 'inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg transition duration-300']
) ?>
```

## Localização

O botão deve ser adicionado:
- No header junto com outros botões de ação
- Ou nos botões de ação do formulário (junto com Cancelar)

## Views que já têm o botão

- categoria/view.php
- categoria/_form.php
- clientes/create.php
- clientes/view.php
- clientes/update.php

## Views que ainda precisam

- colaborador (view, create, update)
- forma-pagamento (view, create, update, _form)
- status-venda (view, create, update, _form)
- status-parcela (view, create, update, _form)
- categoria (create, update, index)
- clientes/index.php
- colaborador/index.php
- dashboard/index.php (se aplicável)
- inicio/index.php (se aplicável)

