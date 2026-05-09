<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $produtos app\modules\vendas\models\Produto[] */
/* @var $filtros array */
?>

<div class="relatorio-container">
    <div class="header-relatorio">
        <h1>Catálogo de Produtos</h1>
    </div>

    <div class="filtros-info">
        <strong>Filtros:</strong> 
        Busca: <?= Html::encode($filtros['busca'] ?: 'Nenhuma') ?> | 
        Categoria: <?= Html::encode($filtros['categoria']) ?> | 
        Estoque: <?= $filtros['estoque'] === 'sem' ? 'Sem Estoque' : ($filtros['estoque'] === 'com' ? 'Com Estoque' : 'Todos') ?> | 
        Status: <?= $filtros['ativo'] === '1' ? 'Ativos' : ($filtros['ativo'] === '0' ? 'Inativos' : 'Todos') ?>
    </div>

    <table class="tabela-produtos">
        <thead>
            <tr>
                <th width="35%">Produto</th>
                <th width="15%">Marca</th>
                <th width="10%">Ref.</th>
                <th width="15%">Cód. Barras</th>
                <th class="text-center" width="8%">Vend.</th>
                <th class="text-center" width="8%">Estoque</th>
                <th class="text-center" width="9%">Pto. Corte</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($produtos)): ?>
                <tr>
                    <td colspan="7" class="text-center">Nenhum produto encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($produtos as $produto): ?>
                    <tr>
                        <td><?= Html::encode($produto->nome) ?></td>
                        <td><?= Html::encode($produto->marca ?: '-') ?></td>
                        <td><?= Html::encode($produto->codigo_referencia ?: '-') ?></td>
                        <td><?= Html::encode($produto->codigo_barras ?: '-') ?></td>
                        <td class="text-center"><?= number_format($produto->quantidade_vendida ?: 0, $produto->venda_fracionada ? 3 : 0, ',', '.') ?></td>
                        <td class="text-center <?= $produto->estoque_atual <= $produto->ponto_corte ? 'estoque-baixo' : '' ?>">
                            <?= number_format($produto->estoque_atual, $produto->venda_fracionada ? 3 : 0, ',', '.') ?>
                        </td>
                        <td class="text-center"><?= number_format($produto->ponto_corte, $produto->venda_fracionada ? 3 : 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer-resumo">
        Total de registros: <?= count($produtos) ?>
    </div>
</div>
