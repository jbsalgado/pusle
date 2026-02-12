<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Nova Configuração de Marketplace';
?>

<div class="marketplace-config-create">
    <div class="page-header">
        <h1><i class="fa fa-plus"></i> <?= Html::encode($this->title) ?></h1>
    </div>

    <div class="box box-primary">
        <div class="box-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'marketplace')->dropDownList(
                        MarketplaceConfig::getMarketplacesDisponiveis(),
                        ['prompt' => 'Selecione um marketplace...']
                    ) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'ativo')->checkbox() ?>
                </div>
            </div>

            <hr>
            <h4><i class="fa fa-key"></i> Credenciais de API</h4>

            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                <strong>Importante:</strong> As credenciais são necessárias para conectar com o marketplace.
                Consulte a documentação de cada marketplace para obter suas chaves de API.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'client_id')->textInput(['maxlength' => true]) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'client_secret')->passwordInput(['maxlength' => true]) ?>
                </div>
            </div>

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

            <div class="form-group">
                <?= Html::submitButton('<i class="fa fa-save"></i> Salvar Configuração', [
                    'class' => 'btn btn-success'
                ]) ?>
                <?= Html::a('<i class="fa fa-times"></i> Cancelar', ['index'], [
                    'class' => 'btn btn-default'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
// Adicionar use statement no topo do arquivo
use app\modules\marketplace\models\MarketplaceConfig;
?>