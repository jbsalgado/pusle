<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * View: Configuração de Cobranças
 * 
 * @var yii\web\View $this
 * @var app\modules\cobranca\models\CobrancaConfiguracao $model
 */

$this->title = 'Configuração de Cobranças';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cobranca-configuracao-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="text-muted">Configure a automação de cobranças via WhatsApp</p>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Configurações Gerais</h3>
                </div>
                <div class="panel-body">
                    <?php $form = ActiveForm::begin(['id' => 'form-configuracao']); ?>

                    <?= $form->field($model, 'ativo')->checkbox(['label' => 'Ativar automação de cobranças']) ?>

                    <hr>

                    <h4>Integração WhatsApp</h4>

                    <?= $form->field($model, 'whatsapp_provider')->dropDownList([
                        'zapi' => 'Z-API',
                        'twilio' => 'Twilio (em breve)',
                        'evolution' => 'Evolution API (em breve)',
                    ], ['disabled' => 'disabled', 'value' => 'zapi']) ?>

                    <?= $form->field($model, 'zapi_instance_id')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Ex: 3C9395CBFC6101247A3813673E4430E8'
                    ]) ?>

                    <?= $form->field($model, 'zapi_token')->passwordInput([
                        'maxlength' => true,
                        'placeholder' => 'Cole o token da Z-API aqui'
                    ]) ?>

                    <div class="form-group">
                        <button type="button" id="btn-testar-conexao" class="btn btn-info">
                            <i class="fa fa-plug"></i> Testar Conexão
                        </button>
                        <span id="status-conexao"></span>
                    </div>

                    <hr>

                    <h4>Configurações de Envio</h4>

                    <?= $form->field($model, 'dias_antes_vencimento')->input('number', [
                        'min' => 0,
                        'max' => 30,
                    ])->hint('Quantos dias antes do vencimento enviar lembrete') ?>

                    <?= $form->field($model, 'enviar_dia_vencimento')->checkbox() ?>

                    <?= $form->field($model, 'dias_apos_vencimento')->input('number', [
                        'min' => 0,
                        'max' => 30,
                    ])->hint('Quantos dias após o vencimento enviar cobrança') ?>

                    <?= $form->field($model, 'horario_envio')->input('time')->hint('Horário padrão para envio das mensagens') ?>

                    <div class="form-group">
                        <?= Html::submitButton('Salvar Configurações', ['class' => 'btn btn-success btn-lg']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">Como Configurar</h3>
                </div>
                <div class="panel-body">
                    <h5>1. Criar conta na Z-API</h5>
                    <p>Acesse <a href="https://www.z-api.io/" target="_blank">z-api.io</a> e crie uma conta.</p>

                    <h5>2. Obter credenciais</h5>
                    <p>No painel da Z-API, copie o <strong>Instance ID</strong> e o <strong>Token</strong>.</p>

                    <h5>3. Conectar WhatsApp</h5>
                    <p>Escaneie o QR Code na Z-API para conectar seu WhatsApp.</p>

                    <h5>4. Configurar aqui</h5>
                    <p>Cole as credenciais nos campos acima e teste a conexão.</p>

                    <h5>5. Ativar automação</h5>
                    <p>Marque "Ativar automação" e salve.</p>
                </div>
            </div>

            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h3 class="panel-title">Próximos Passos</h3>
                </div>
                <div class="panel-body">
                    <p>Após configurar:</p>
                    <ol>
                        <li><?= Html::a('Personalizar Templates', ['template/index']) ?></li>
                        <li><?= Html::a('Ver Histórico', ['historico/index']) ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs(
    <<<JS
$('#btn-testar-conexao').click(function() {
    var btn = $(this);
    var status = $('#status-conexao');
    
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Testando...');
    status.html('');
    
    $.ajax({
        url: '/cobranca/configuracao/testar-conexao',
        method: 'POST',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                status.html('<span class="text-success"><i class="fa fa-check"></i> ' + data.message + '</span>');
            } else {
                status.html('<span class="text-danger"><i class="fa fa-times"></i> ' + data.message + '</span>');
            }
        },
        error: function() {
            status.html('<span class="text-danger"><i class="fa fa-times"></i> Erro ao testar conexão</span>');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fa fa-plug"></i> Testar Conexão');
        }
    });
});
JS
);
?>