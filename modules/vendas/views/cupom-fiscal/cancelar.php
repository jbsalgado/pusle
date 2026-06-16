<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Cancelar NFe';
$this->params['breadcrumbs'][] = ['label' => 'Cupons Fiscais', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->numero, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Cancelar';
?>

<div class="cupom-fiscal-cancelar">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-warning">
        <strong>Atenção!</strong> O cancelamento de uma NFe é irreversível. Certifique-se de que realmente deseja cancelar esta nota fiscal.
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Dados da NFe</h3>
        </div>
        <div class="panel-body">
            <dl class="dl-horizontal">
                <dt>Número:</dt>
                <dd><?= $model->numero ?></dd>

                <dt>Série:</dt>
                <dd><?= $model->serie ?></dd>

                <dt>Chave de Acesso:</dt>
                <dd><code><?= $model->chave_acesso ?></code></dd>

                <dt>Protocolo:</dt>
                <dd><?= $model->protocolo ?></dd>

                <dt>Data Emissão:</dt>
                <dd><?= Yii::$app->formatter->asDatetime($model->data_emissao) ?></dd>
            </dl>
        </div>
    </div>

    <?php $form = ActiveForm::begin(); ?>

    <div class="form-group">
        <label for="justificativa">Justificativa do Cancelamento *</label>
        <textarea
            name="justificativa"
            id="justificativa"
            class="form-control"
            rows="4"
            required
            minlength="15"
            placeholder="Digite a justificativa do cancelamento (mínimo 15 caracteres)"></textarea>
        <p class="help-block">A justificativa deve ter no mínimo 15 caracteres.</p>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Confirmar Cancelamento', ['class' => 'btn btn-danger']) ?>
        <?= Html::a('Voltar', ['view', 'id' => $model->id], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>