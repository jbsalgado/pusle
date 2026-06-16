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
                <th rowspan="2" width="14%">Produto</th>
                <th rowspan="2" width="7%">Marca</th>
                <th rowspan="2" width="5%">Ref.</th>
                <th rowspan="2" width="7%">Cód. Barras</th>
                <th rowspan="2" class="text-center" width="4%">Vendas</th>
                <th rowspan="2" class="text-center" width="4%">Estoque</th>
                <th rowspan="2" class="text-center" width="5%">Comprado</th>
                <th rowspan="2" class="text-center" width="4%">Pto. Corte</th>
                <th rowspan="2" class="text-center" width="3%">UND.</th>
                <th colspan="4" class="text-center header-grupo-destaque">VALOR ESTOQUE</th>
                <th colspan="2" class="text-center header-grupo-lucro">LUCRO</th>
                <th rowspan="2" class="text-right" width="7%">Margem</th>
                <th rowspan="2" class="text-right" width="7%">Markup</th>
            </tr>
            <tr>
                <th class="text-center header-destaque-valor" width="6%">Vl. Custo</th>
                <th class="text-center header-destaque-valor" width="6%">Vl. Venda</th>
                <th class="text-center header-destaque-valor" width="7%">T. Custo</th>
                <th class="text-center header-destaque-valor" width="7%">T. Vendas</th>
                <th class="text-center header-destaque-lucro" width="7%">Realizado</th>
                <th class="text-center header-destaque-lucro" width="7%">Previsto</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($produtos)): ?>
                <tr>
                    <td colspan="17" class="text-center">Nenhum produto encontrado.</td>
                </tr>
            <?php else: ?>
                <?php 
                $totalCustoEstoque = 0;
                $totalVendaEstoque = 0;
                $totalLucroRealizado = 0;
                $totalLucroPrevisto = 0;
                ?>
                <?php foreach ($produtos as $produto): ?>
                    <?php
                    $vlCustoTotal = $produto->preco_custo * $produto->estoque_atual;
                    $vlVendasTotal = $produto->preco_venda_sugerido * $produto->estoque_atual;
                    
                    $totalCustoEstoque += $vlCustoTotal;
                    $totalVendaEstoque += $vlVendasTotal;

                    $lucroUnitario = ($produto->preco_venda_sugerido ?: 0) - ($produto->preco_custo ?: 0);
                    $lucroRealizadoTotal = $lucroUnitario * ($produto->quantidade_vendida ?: 0);
                    $lucroPrevistoTotal = $lucroUnitario * $produto->estoque_atual;

                    $totalLucroRealizado += $lucroRealizadoTotal;
                    $totalLucroPrevisto += $lucroPrevistoTotal;
                    ?>
                    <tr>
                        <td><?= Html::encode($produto->nome) ?></td>
                        <td><?= Html::encode($produto->marca ?: '-') ?></td>
                        <td><?= Html::encode($produto->codigo_referencia ?: '-') ?></td>
                        <td><?= Html::encode($produto->codigo_barras ?: '-') ?></td>
                        <td align="right"><?= number_format($produto->quantidade_vendida ?: 0, $produto->venda_fracionada ? 3 : 0, ',', '.') ?></td>
                        <td align="right" class="<?= $produto->estoque_atual <= $produto->ponto_corte ? 'estoque-baixo' : '' ?>">
                            <?= number_format($produto->estoque_atual, $produto->venda_fracionada ? 3 : 0, ',', '.') ?>
                        </td>
                        <td align="right">
                            <?= number_format(($produto->quantidade_vendida ?: 0) + $produto->estoque_atual, $produto->venda_fracionada ? 3 : 0, ',', '.') ?>
                        </td>
                        <td align="right"><?= number_format($produto->ponto_corte, $produto->venda_fracionada ? 3 : 0, ',', '.') ?></td>
                        <td align="center"><?= Html::encode($produto->unidade_medida ?: 'UN') ?></td>
                        <td align="right" class="destaque-valor">R$ <?= number_format($produto->preco_custo ?: 0, 2, ',', '.') ?></td>
                        <td align="right" class="destaque-valor">R$ <?= number_format($produto->preco_venda_sugerido ?: 0, 2, ',', '.') ?></td>
                        <td align="right" class="destaque-valor">R$ <?= number_format($vlCustoTotal, 2, ',', '.') ?></td>
                        <td align="right" class="destaque-valor">R$ <?= number_format($vlVendasTotal, 2, ',', '.') ?></td>
                        <td align="right" class="destaque-lucro">R$ <?= number_format($lucroRealizadoTotal, 2, ',', '.') ?></td>
                        <td align="right" class="destaque-lucro">R$ <?= number_format($lucroPrevistoTotal, 2, ',', '.') ?></td>
                        <td align="right"><?= number_format($produto->margemLucro ?: 0, 2, ',', '.') ?>%</td>
                        <td align="right"><?= number_format($produto->markup ?: 0, 2, ',', '.') ?>%</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer-resumo" style="margin-top: 15px; border-top: 2px solid #333; padding-top: 5px;">
        <table style="width: 100%; border: none; font-size: 8px; border-collapse: collapse;">
            <tr>
                <td style="border: none; width: 14%; padding: 0;"><strong>Total de registros:</strong> <?= count($produtos) ?></td>
                <?php if (!empty($produtos)): ?>
                    <td style="border: none; width: 20%; text-align: right; padding: 0;"><strong>Total Custo Est.:</strong> R$ <?= number_format($totalCustoEstoque, 2, ',', '.') ?></td>
                    <td style="border: none; width: 20%; text-align: right; padding: 0;"><strong>Total Venda Est.:</strong> R$ <?= number_format($totalVendaEstoque, 2, ',', '.') ?></td>
                    <td style="border: none; width: 23%; text-align: right; padding: 0; color: #15803d;"><strong>Total Lucro Real.:</strong> R$ <?= number_format($totalLucroRealizado, 2, ',', '.') ?></td>
                    <td style="border: none; width: 23%; text-align: right; padding: 0; color: #15803d;"><strong>Total Lucro Prev.:</strong> R$ <?= number_format($totalLucroPrevisto, 2, ',', '.') ?></td>
                <?php endif; ?>
            </tr>
        </table>
    </div>
</div>
