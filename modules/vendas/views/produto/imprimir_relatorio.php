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
        Estoque: <?= $filtros['estoque'] === 'com' ? 'Com Estoque' : ($filtros['estoque'] === 'zerado' ? 'Estoque Zerado' : ($filtros['estoque'] === 'corte' ? 'Abaixo do Ponto de Corte' : 'Todos')) ?> | 
        Status: <?= $filtros['ativo'] === '1' ? 'Ativos' : ($filtros['ativo'] === '0' ? 'Inativos' : 'Todos') ?>
    </div>

    <table class="tabela-produtos">
        <thead>
            <tr>
                <th width="16%">Produto</th>
                <th width="8%">Marca</th>
                <th width="6%">Ref.</th>
                <th width="8%">Cód. Barras</th>
                <th class="text-center" width="5%">Vend.</th>
                <th class="text-center" width="5%">Estoque</th>
                <th class="text-center" width="5%">Pto. Corte</th>
                <th class="text-center" width="4%">UND.</th>
                <th class="text-right" width="7%">Vl. Custo</th>
                <th class="text-right" width="7%">Vl. Venda</th>
                <th class="text-right" width="8%">T. Custo</th>
                <th class="text-right" width="8%">T. Vendas</th>
                <th class="text-right" width="9%">Margem</th>
                <th class="text-right" width="9%">Markup</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($produtos)): ?>
                <tr>
                    <td colspan="14" class="text-center">Nenhum produto encontrado.</td>
                </tr>
            <?php else: ?>
                <?php 
                $totalCustoEstoque = 0;
                $totalVendaEstoque = 0;
                ?>
                <?php foreach ($produtos as $produto): ?>
                    <?php
                    $vlCustoTotal = $produto->preco_custo * $produto->estoque_atual;
                    $vlVendasTotal = $produto->preco_venda_sugerido * $produto->estoque_atual;
                    
                    $totalCustoEstoque += $vlCustoTotal;
                    $totalVendaEstoque += $vlVendasTotal;
                    ?>
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
                        <td class="text-center"><?= Html::encode($produto->unidade_medida ?: 'UN') ?></td>
                        <td class="text-right">R$ <?= number_format($produto->preco_custo ?: 0, 2, ',', '.') ?></td>
                        <td class="text-right">R$ <?= number_format($produto->preco_venda_sugerido ?: 0, 2, ',', '.') ?></td>
                        <td class="text-right">R$ <?= number_format($vlCustoTotal, 2, ',', '.') ?></td>
                        <td class="text-right">R$ <?= number_format($vlVendasTotal, 2, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($produto->margemLucro ?: 0, 2, ',', '.') ?>%</td>
                        <td class="text-right"><?= number_format($produto->markup ?: 0, 2, ',', '.') ?>%</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer-resumo" style="margin-top: 15px; border-top: 2px solid #333; padding-top: 5px;">
        <table style="width: 100%; border: none; font-size: 9px; border-collapse: collapse;">
            <tr>
                <td style="border: none; width: 30%; padding: 0;"><strong>Total de registros:</strong> <?= count($produtos) ?></td>
                <?php if (!empty($produtos)): ?>
                    <td style="border: none; width: 35%; text-align: right; padding: 0;"><strong>Total Custo Estoque:</strong> R$ <?= number_format($totalCustoEstoque, 2, ',', '.') ?></td>
                    <td style="border: none; width: 35%; text-align: right; padding: 0;"><strong>Total Venda Estoque:</strong> R$ <?= number_format($totalVendaEstoque, 2, ',', '.') ?></td>
                <?php endif; ?>
            </tr>
        </table>
    </div>
</div>
