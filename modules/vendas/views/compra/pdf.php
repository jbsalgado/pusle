<?php
use yii\helpers\Html;

/**
 * @var \app\modules\vendas\models\Compra $model
 */
?>

<div class="pedido-container">
    <!-- Cabeçalho -->
    <table class="header-pedido" style="width: 100%;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div style="font-size: 20px; font-weight: bold; color: #1e3a8a; text-transform: uppercase;">
                    <?= Html::encode($model->usuario->nome) ?>
                </div>
                <div class="subtitle">
                    CPF/CNPJ: <?= Html::encode($model->usuario->cpf) ?><br>
                    Telefone: <?= Html::encode($model->usuario->telefone) ?> | E-mail: <?= Html::encode($model->usuario->email) ?><br>
                    Endereço: <?= Html::encode($model->usuario->endereco) ?>
                </div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <div style="font-size: 16px; font-weight: bold; color: #1e3a8a;">
                    PEDIDO DE COMPRA
                </div>
                <div style="font-size: 18px; font-weight: bold; color: #d9534f; margin-top: 5px;">
                    #<?= strtoupper(substr($model->id, 0, 8)) ?>
                </div>
                <div style="font-size: 11px; color: #555; margin-top: 5px;">
                    Data de Emissão: <?= Yii::$app->formatter->asDate($model->data_compra) ?>
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px;"></div>

    <!-- Dados do Fornecedor e da Compra -->
    <table class="dados-compra">
        <tr>
            <td style="width: 50%; vertical-align: top; border-right: 1px solid #ddd; padding-right: 15px;">
                <div style="font-weight: bold; color: #1e3a8a; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 5px;">FORNECEDOR</div>
                <table style="width: 100%;">
                    <tr>
                        <td class="label">Razão Social:</td>
                        <td><?= Html::encode($model->fornecedor->razao_social ?: $model->fornecedor->nome_fantasia) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Nome Fantasia:</td>
                        <td><?= Html::encode($model->fornecedor->nome_fantasia) ?></td>
                    </tr>
                    <tr>
                        <td class="label">CNPJ/CPF:</td>
                        <td><?= Html::encode($model->fornecedor->cnpj ?: $model->fornecedor->cpf) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Telefone:</td>
                        <td><?= Html::encode($model->fornecedor->telefone) ?></td>
                    </tr>
                    <tr>
                        <td class="label">E-mail:</td>
                        <td><?= Html::encode($model->fornecedor->email) ?></td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 15px;">
                <div style="font-weight: bold; color: #1e3a8a; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 5px;">DETALHES DA COMPRA</div>
                <table style="width: 100%;">
                    <tr>
                        <td class="label">Data de Compra:</td>
                        <td><?= Yii::$app->formatter->asDate($model->data_compra) ?></td>
                    </tr>
                    <?php if ($model->data_vencimento): ?>
                        <tr>
                            <td class="label">1º Vencimento:</td>
                            <td><?= Yii::$app->formatter->asDate($model->data_vencimento) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($model->numero_nota_fiscal): ?>
                        <tr>
                            <td class="label">NF / Série:</td>
                            <td><?= Html::encode($model->numero_nota_fiscal) ?> <?= $model->serie_nota_fiscal ? '/ ' . Html::encode($model->serie_nota_fiscal) : '' ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($model->forma_pagamento): ?>
                        <tr>
                            <td class="label">Forma de Pag.:</td>
                            <td><?= Html::encode($model->forma_pagamento) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="label">Status:</td>
                        <td><?= Html::encode($model->getStatusLabel()) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Tabela de Itens -->
    <table class="tabela-itens">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 10%;">Cód. Ref / EAN</th>
                <th style="width: 45%;">Produto</th>
                <th style="width: 10%; text-align: center;">Und</th>
                <th style="width: 10%; text-align: right;">Qtd</th>
                <th style="width: 10%; text-align: right;">Preço Unit.</th>
                <th style="width: 10%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($model->itens as $index => $item): ?>
                <tr>
                    <td style="text-align: center;"><?= $index + 1 ?></td>
                    <td><?= Html::encode($item->produto->codigo_referencia ?: $item->produto->codigo_barras ?: '-') ?></td>
                    <td>
                        <div style="font-weight: bold;"><?= Html::encode($item->produto->nome) ?></div>
                        <?php if ($item->marca): ?>
                            <span style="font-size: 9px; color: #666;">Marca: <?= Html::encode($item->marca) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;"><?= Html::encode($item->produto->unidade_medida ?: 'UN') ?></td>
                    <td style="text-align: right;"><?= number_format($item->quantidade, $item->produto->venda_fracionada ? 3 : 0, ',', '.') ?></td>
                    <td style="text-align: right;">R$ <?= number_format($item->preco_unitario, 2, ',', '.') ?></td>
                    <td style="text-align: right; font-weight: bold;">R$ <?= number_format($item->valor_total_item, 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Resumo Financeiro -->
    <table style="width: 100%; margin-top: 10px;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <?php if ($model->observacoes): ?>
                    <div style="font-weight: bold; color: #1e3a8a; margin-bottom: 5px;">OBSERVAÇÕES:</div>
                    <div style="font-size: 10px; color: #555; background-color: #f9f9f9; border: 1px solid #eee; padding: 8px; border-radius: 4px; min-height: 50px;">
                        <?= nl2br(Html::encode($model->observacoes)) ?>
                    </div>
                <?php endif; ?>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <table class="resumo-financeiro" style="width: 100%;">
                    <tr>
                        <td class="label">Subtotal:</td>
                        <td class="valor">R$ <?= number_format($model->valor_total, 2, ',', '.') ?></td>
                    </tr>
                    <?php if ($model->valor_desconto > 0): ?>
                        <tr>
                            <td class="label">Desconto:</td>
                            <td class="valor" style="color: #d9534f;">- R$ <?= number_format($model->valor_desconto, 2, ',', '.') ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($model->valor_frete > 0): ?>
                        <tr>
                            <td class="label">Frete:</td>
                            <td class="valor">+ R$ <?= number_format($model->valor_frete, 2, ',', '.') ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="label total-destaque" style="border-top: 2px solid #1e3a8a; padding-top: 8px;">TOTAL GERAL:</td>
                        <td class="valor total-destaque" style="border-top: 2px solid #1e3a8a; padding-top: 8px;">R$ <?= number_format($model->getValorLiquido(), 2, ',', '.') ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer-pedido">
        Este documento é um Pedido de Compra gerado pelo sistema Pulse.<br>
        Emitido por: <?= Html::encode($model->usuario->nome) ?> (CPF/CNPJ: <?= Html::encode($model->usuario->cpf) ?>)
    </div>
</div>
