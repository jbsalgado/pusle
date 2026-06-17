<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * View: Editar Template
 * 
 * @var yii\web\View $this
 * @var app\modules\cobranca\models\CobrancaTemplate $model
 */

$this->title = 'Editar Template: ' . $model->getTipoNome();
$this->params['breadcrumbs'][] = ['label' => 'Cobranças', 'url' => ['/cobranca/configuracao/index']];
$this->params['breadcrumbs'][] = ['label' => 'Templates', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="cobranca-template-update">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Editor de Template</h3>
                </div>
                <div class="panel-body">
                    <?php $form = ActiveForm::begin(['id' => 'form-template']); ?>

                    <?= $form->field($model, 'titulo')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'mensagem')->textarea([
                        'rows' => 10,
                        'id' => 'template-mensagem',
                        'placeholder' => 'Digite sua mensagem aqui...'
                    ])->hint('Use as variáveis disponíveis para personalizar a mensagem') ?>

                    <?= $form->field($model, 'ativo')->checkbox() ?>

                    <div class="form-group">
                        <?= Html::submitButton('<i class="fa fa-save"></i> Salvar Template', ['class' => 'btn btn-success btn-lg']) ?>
                        <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-default']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>

            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">Variáveis Disponíveis</h3>
                </div>
                <div class="panel-body">
                    <p>Clique para inserir no template:</p>
                    <?php foreach (\app\modules\cobranca\models\CobrancaTemplate::getVariaveisDisponiveis() as $variavel => $descricao): ?>
                        <button type="button" class="btn btn-xs btn-default btn-insert-var" data-var="<?= $variavel ?>" style="margin: 2px;">
                            <?= $variavel ?>
                        </button>
                    <?php endforeach; ?>

                    <hr>

                    <small class="text-muted">
                        <?php foreach (\app\modules\cobranca\models\CobrancaTemplate::getVariaveisDisponiveis() as $variavel => $descricao): ?>
                            <strong><?= $variavel ?></strong>: <?= $descricao ?><br>
                        <?php endforeach; ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title">Preview da Mensagem</h3>
                </div>
                <div class="panel-body">
                    <div class="well" id="preview-container" style="min-height: 200px; white-space: pre-wrap; font-family: monospace; background: #f5f5f5;">
                        <em class="text-muted">Digite no editor para ver o preview...</em>
                    </div>

                    <div class="alert alert-info" style="margin-top: 15px;">
                        <small>
                            <strong>Dados de exemplo:</strong><br>
                            Nome: João Silva<br>
                            Valor: R$ 150,00<br>
                            Vencimento: <?= date('d/m/Y', strtotime('+3 days')) ?><br>
                            Parcela: 1/12<br>
                            Empresa: <?= Yii::$app->name ?>
                        </small>
                    </div>
                </div>
            </div>

            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h3 class="panel-title">Dicas de Uso</h3>
                </div>
                <div class="panel-body">
                    <ul style="font-size: 13px;">
                        <li>Seja claro e objetivo na mensagem</li>
                        <li>Use emojis para tornar a mensagem mais amigável</li>
                        <li>Sempre inclua o valor e a data de vencimento</li>
                        <li>Evite mensagens muito longas</li>
                        <li>Teste antes de ativar</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs(
    <<<JS
// Inserir variável no cursor
$('.btn-insert-var').click(function() {
    var textarea = $('#template-mensagem')[0];
    var variavel = $(this).data('var');
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var text = textarea.value;
    
    textarea.value = text.substring(0, start) + variavel + text.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + variavel.length;
    textarea.focus();
    
    // Atualizar preview
    updatePreview();
});

// Atualizar preview em tempo real
$('#template-mensagem').on('input', function() {
    updatePreview();
});

function updatePreview() {
    var mensagem = $('#template-mensagem').val();
    
    $.ajax({
        url: '/cobranca/template/preview',
        method: 'POST',
        data: { mensagem: mensagem },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#preview-container').text(data.preview);
            }
        }
    });
}

// Preview inicial
updatePreview();
JS
);
?>