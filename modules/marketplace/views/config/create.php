<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\marketplace\models\MarketplaceConfig;

$this->title = 'Nova Conexão de Marketplace';
?>

<div class="marketplace-config-create">
    <div class="page-header">
        <h1><i class="fa fa-plus"></i> <?= Html::encode($this->title) ?></h1>
    </div>

    <div class="box box-primary">
        <div class="box-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'marketplace')->dropDownList(
                        MarketplaceConfig::getMarketplacesDisponiveis(),
                        ['prompt' => 'Selecione um marketplace...']
                    ) ?>
                </div>

                <div class="col-md-4">
                    <?= $form->field($model, 'apelido_conta')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Ex: Loja Principal, Filial SP, Conta 02'
                    ]) ?>
                </div>

                <div class="col-md-4">
                    <?= $form->field($model, 'seller_id_externo')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'ID do Seller / Shop ID (opcional na criação)'
                    ]) ?>
                </div>
            </div>

            <hr>
            <h4><i class="fa fa-key"></i> Credenciais de API / App</h4>

            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                <strong>Importante:</strong> Informe o <b>Client ID / App Key / Partner ID</b> e o <b>Client Secret / Partner Key</b> da sua aplicação criada no portal do marketplace.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'client_id')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'App ID / Client ID / Partner ID'
                    ]) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'client_secret')->passwordInput([
                        'maxlength' => true,
                        'placeholder' => 'Client Secret / Partner Key'
                    ]) ?>
                </div>
            </div>

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
                <?= Html::submitButton('<i class="fa fa-save"></i> Salvar Conexão', [
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