<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\marketplace\models\MarketplaceConfig;

$this->title = 'Editar Conexão: ' . $model->getMarketplaceNome() . ' (' . $model->apelido_conta . ')';
?>

<div class="marketplace-config-update">
    <div class="page-header">
        <h1><i class="fa fa-edit"></i> <?= Html::encode($this->title) ?></h1>
    </div>

    <div class="box box-primary">
        <div class="box-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'marketplace')->dropDownList(
                        MarketplaceConfig::getMarketplacesDisponiveis(),
                        ['disabled' => true]
                    ) ?>
                </div>

                <div class="col-md-4">
                    <?= $form->field($model, 'apelido_conta')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Ex: Loja Principal'
                    ]) ?>
                </div>

                <div class="col-md-4">
                    <?= $form->field($model, 'seller_id_externo')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'ID Externo (ex: User ID ML / Shop ID Shopee)'
                    ]) ?>
                </div>
            </div>

            <hr>
            <h4><i class="fa fa-key"></i> Credenciais de API</h4>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'client_id')->textInput(['maxlength' => true]) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'client_secret')->passwordInput([
                        'maxlength' => true,
                        'placeholder' => 'Deixe em branco para manter a chave atual'
                    ]) ?>
                </div>
            </div>

            <?php if ($model->token_expira_em): ?>
                <div class="alert alert-<?= $model->isTokenExpired() ? 'warning' : 'success' ?>">
                    <i class="fa fa-<?= $model->isTokenExpired() ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                    <strong>Status do Token:</strong>
                    <?php if ($model->isTokenExpired()): ?>
                        Expirado em <?= Yii::$app->formatter->asDatetime($model->token_expira_em) ?>.
                    <?php else: ?>
                        Válido até <?= Yii::$app->formatter->asDatetime($model->token_expira_em) ?>.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <hr>
            <h4><i class="fa fa-tag"></i> Regras de Precificação & Margem no Canal</h4>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'markup_percentual')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => 0,
                        'placeholder' => 'Ex: 18.00 (+18%)'
                    ])->label('Acréscimo Percentual (%)') ?>
                </div>

                <div class="col-md-4">
                    <?= $form->field($model, 'markup_valor_fixo')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => 0,
                        'placeholder' => 'Ex: 5.00 (+R$ 5)'
                    ])->label('Acréscimo Fixo por Produto (R$)') ?>
                </div>

                <div class="col-md-4" style="padding-top: 25px;">
                    <?= $form->field($model, 'arredondar_centavos_99')->checkbox([
                        'label' => 'Arredondar preço para R$ xx,99'
                    ]) ?>
                </div>
            </div>

            <hr>
            <h4><i class="fa fa-sync"></i> Configurações de Sincronização</h4>

            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'ativo')->checkbox() ?>
                </div>

                <div class="col-md-3">
                    <?= $form->field($model, 'sincronizar_estoque')->checkbox() ?>
                </div>

                <div class="col-md-3">
                    <?= $form->field($model, 'sincronizar_pedidos')->checkbox() ?>
                </div>

                <div class="col-md-3">
                    <?= $form->field($model, 'sincronizar_produtos')->checkbox() ?>
                </div>
            </div>

            <div class="form-group mt-3">
                <?= Html::submitButton('<i class="fa fa-save"></i> Salvar Alterações', [
                    'class' => 'btn btn-success'
                ]) ?>
                <?= Html::a('<i class="fa fa-eye"></i> Visualizar', ['view', 'id' => $model->id], [
                    'class' => 'btn btn-info'
                ]) ?>
                <?= Html::a('<i class="fa fa-sitemap"></i> Mapear Categorias', ['/marketplace/categoria-map/index', 'marketplace' => $model->marketplace], [
                    'class' => 'btn btn-warning'
                ]) ?>
                <?= Html::a('<i class="fa fa-times"></i> Cancelar', ['index'], [
                    'class' => 'btn btn-default'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>