<?php

use app\modules\indicadores\models\SysModulos;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\SysModulos */
/* @var $form yii\widgets\ActiveForm */
?>

<style>
.form-container {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    margin-top: 20px;
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.section-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 10px;
    color: #667eea;
}

.form-group {
    margin-bottom: 25px;
}

.form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 12px 15px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    transform: translateY(-1px);
}

.control-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    display: block;
}

.help-block {
    margin-top: 8px;
    font-size: 0.85rem;
    color: #6c757d;
}

.checkbox-wrapper {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.checkbox-wrapper:hover {
    border-color: #667eea;
    background: #f5f7ff;
}

.checkbox-wrapper input[type="checkbox"] {
    transform: scale(1.2);
    margin-right: 10px;
}

.checkbox-wrapper label {
    font-weight: 500;
    color: #495057;
    cursor: pointer;
    margin: 0;
    display: flex;
    align-items: center;
}

.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 25px;
    padding: 12px 30px;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    min-width: 120px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-cancel {
    background: #6c757d;
    border: none;
    border-radius: 25px;
    padding: 12px 30px;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
    margin-right: 15px;
}

.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-1px);
    color: white;
}

.select2-container--krajee .select2-selection--multiple {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    min-height: 45px;
    padding: 5px 10px;
}

.select2-container--krajee.select2-container--focus .select2-selection--multiple {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.select2-selection__choice {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    color: white !important;
    border-radius: 15px !important;
    padding: 3px 10px !important;
    font-size: 0.85rem !important;
}

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    text-align: center;
}

.required-indicator {
    color: #dc3545;
    font-weight: bold;
}

@media (max-width: 768px) {
    .form-container {
        padding: 20px 15px;
        border-radius: 10px;
    }
    
    .section-title {
        font-size: 1.1rem;
    }
    
    .form-control {
        padding: 10px 12px;
    }
    
    .btn-submit, .btn-cancel {
        width: 100%;
        margin-bottom: 10px;
        margin-right: 0;
    }
    
    .form-actions {
        text-align: stretch;
    }
}

.path-input {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.9rem;
}

.dimensions-help {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 15px;
    border-radius: 0 8px 8px 0;
    margin-top: 10px;
}

.dimensions-help i {
    color: #2196f3;
    margin-right: 8px;
}
</style>

<div class="sys-modulos-form">
    <div class="form-container">
        <?php $form = ActiveForm::begin([
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => '{label}{input}{error}{hint}',
                'labelOptions' => ['class' => 'control-label'],
                'inputOptions' => ['class' => 'form-control'],
                'errorOptions' => ['class' => 'help-block text-danger'],
                'hintOptions' => ['class' => 'help-block'],
            ],
        ]); ?>

        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-cube"></i>
                Informações do Módulo
            </div>

            <?= $form->field($model, 'modulo')->textInput([
                'maxlength' => true,
                'placeholder' => 'Ex: Dashboard, Relatórios, Configurações...'
            ])->label('Nome do Módulo <span class="required-indicator">*</span>') ?>

            <?= $form->field($model, 'path')->textInput([
                'maxlength' => true,
                'class' => 'form-control path-input',
                'placeholder' => 'Ex: /dashboard, /relatorios, /admin/config...'
            ])->label('Caminho/URL <span class="required-indicator">*</span>')
            ->hint('Caminho relativo para acessar o módulo (deve começar com /)') ?>
        </div>

        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-toggle-on"></i>
                Status do Módulo
            </div>

            <div class="checkbox-wrapper">
                <?= $form->field($model, 'status')->checkbox([
                    'custom' => true,
                    'label' => '<i class="fas fa-power-off"></i> Módulo Ativo'
                ], false)->label(false) ?>
                <div class="help-block">
                    Marque esta opção para ativar o módulo no sistema
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-sitemap"></i>
                Dimensões Associadas
            </div>

            <?= $form->field($model, 'dimensoes_selecionadas')->widget(Select2::class, [
                'data' =>SysModulos::getDimensoesForSelect2(),
                'options' => [
                    'placeholder' => 'Selecione as dimensões para associar...',
                    'multiple' => true,
                    'id' => 'dimensoes-select',
                ],
                'pluginOptions' => [
                    'allowClear' => true,
                    'minimumInputLength' => 0,
                    'language' => [
                        'noResults' => new \yii\web\JsExpression('function() { return "Nenhuma dimensão encontrada"; }'),
                        'searching' => new \yii\web\JsExpression('function() { return "Buscando..."; }'),
                        'loadingMore' => new \yii\web\JsExpression('function() { return "Carregando mais resultados..."; }'),
                        'inputTooShort' => new \yii\web\JsExpression('function(args) { 
                            return "Digite para buscar dimensões"; 
                        }'),
                    ],
                    'escapeMarkup' => new \yii\web\JsExpression('function (markup) { return markup; }'),
                    'templateResult' => new \yii\web\JsExpression('function(data) {
                        if (data.loading) return data.text;
                        return "<div style=\'padding: 8px; border-bottom: 1px solid #eee;\'>" + data.text + "</div>";
                    }'),
                    'templateSelection' => new \yii\web\JsExpression('function(data) {
                        return data.text || data.id;
                    }')
                ],
            ])->label('Dimensões do Módulo')
            ->hint('Selecione uma ou mais dimensões que este módulo deve gerenciar') ?>

            <div class="dimensions-help">
                <i class="fas fa-info-circle"></i>
                <strong>Dica:</strong> As dimensões associadas definem quais indicadores este módulo poderá gerenciar. 
                Você pode buscar por nome ou descrição da dimensão.
            </div>
        </div>

        <div class="form-actions">
            <?= Html::a('<i class="fas fa-arrow-left"></i> Cancelar', ['/index.php/metricas/sys-modulos/index'], [
                'class' => 'btn btn-cancel'
            ]) ?>

            <?= Html::submitButton('<i class="fas fa-save"></i> ' . ($model->isNewRecord ? 'Criar Módulo' : 'Atualizar Módulo'), [
                'class' => 'btn btn-submit'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-format path input
    $('#sysmodulos-path').on('blur', function() {
        let path = $(this).val().trim();
        if (path && !path.startsWith('/')) {
            $(this).val('/' + path);
        }
    });

    // Form validation feedback
    $('form').on('submit', function() {
        $('.btn-submit').html('<i class="fas fa-spinner fa-spin"></i> Salvando...')
                      .prop('disabled', true);
    });

    // Enhance checkbox interaction
    $('.checkbox-wrapper').on('click', function(e) {
        if (e.target.type !== 'checkbox') {
            $(this).find('input[type="checkbox"]').trigger('click');
        }
    });
});
</script>