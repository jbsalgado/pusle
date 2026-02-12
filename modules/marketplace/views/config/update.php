<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\marketplace\models\MarketplaceConfig;

$this->title = 'Editar Configuração: ' . $model->getMarketplaceNome();
?>

<div class="marketplace-config-update">
    <div class="page-header">
        <h1><i class="fa fa-edit"></i> <?= Html::encode($this->title) ?></h1>
    </div>

    <div class="box box-primary">
        <div class="box-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'marketplace')->dropDownList(
                        MarketplaceConfig::getMarketplacesDisponiveis(),
                        ['disabled' => true] // Não permite mudar o marketplace
                    ) ?>
                    <p class="help-block">O marketplace não pode ser alterado após a criação.</p>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'ativo')->checkbox() ?>
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
                        'placeholder' => 'Deixe em branco para manter o atual'
                    ]) ?>
                </div>
            </div>

            <?php if ($model->token_expira_em): ?>
                <div class="alert alert-<?= $model->isTokenExpired() ? 'warning' : 'success' ?>">
                    <i class="fa fa-<?= $model->isTokenExpired() ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                    <strong>Token de Acesso:</strong>
                    <?php if ($model->isTokenExpired()): ?>
                        Expirado em <?= Yii::$app->formatter->asDatetime($model->token_expira_em) ?>. É necessário renovar.
                    <?php else: ?>
                        Válido até <?= Yii::$app->formatter->asDatetime($model->token_expira_em) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <hr>
            <h4><i class="fa fa-sync"></i> Configurações de Sincronização</h4>

            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'sincronizar_produtos')->checkbox() ?>
                </div>

                <div class="col-md-3">
                    <?= $form->field($model, 'sincronizar_estoque')->checkbox() ?>
                </div>

                <div class="col-md-3">
                    <?= $form->field($model, 'sincronizar_pedidos')->checkbox() ?>
                </div>

                <div class="col-md-3">
                    <?= $form->field($model, 'intervalo_sync_minutos')->textInput([
                        'type' => 'number',
                        'min' => 5,
                        'max' => 1440,
                    ]) ?>
                </div>
            </div>

            <?php if ($model->ultima_sync): ?>
                <div class="alert alert-info">
                    <i class="fa fa-clock-o"></i>
                    <strong>Última Sincronização:</strong> <?= Yii::$app->formatter->asDatetime($model->ultima_sync) ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <?= Html::submitButton('<i class="fa fa-save"></i> Salvar Alterações', [
                    'class' => 'btn btn-success'
                ]) ?>
                <?= Html::a('<i class="fa fa-eye"></i> Visualizar', ['view', 'id' => $model->id], [
                    'class' => 'btn btn-info'
                ]) ?>
                <?= Html::a('<i class="fa fa-times"></i> Cancelar', ['index'], [
                    'class' => 'btn btn-default'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>