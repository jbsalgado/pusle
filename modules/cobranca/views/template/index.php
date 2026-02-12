<?php

use yii\helpers\Html;

/**
 * View: Lista de Templates
 * 
 * @var yii\web\View $this
 * @var app\modules\cobranca\models\CobrancaTemplate[] $templates
 */

$this->title = 'Templates de Mensagens';
$this->params['breadcrumbs'][] = ['label' => 'Cobranças', 'url' => ['/cobranca/configuracao/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cobranca-template-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="text-muted">Personalize as mensagens enviadas aos clientes</p>
    </div>

    <div class="row">
        <?php foreach ($templates as $template): ?>
            <div class="col-md-4">
                <div class="panel panel-<?= $template->ativo ? 'primary' : 'default' ?>">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <?= Html::encode($template->getTipoNome()) ?>
                            <?php if ($template->ativo): ?>
                                <span class="label label-success pull-right">Ativo</span>
                            <?php else: ?>
                                <span class="label label-default pull-right">Inativo</span>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <p><strong><?= Html::encode($template->titulo) ?></strong></p>
                        <div class="well well-sm" style="max-height: 150px; overflow-y: auto; font-size: 12px;">
                            <?= nl2br(Html::encode($template->mensagem)) ?>
                        </div>

                        <div class="text-muted" style="font-size: 11px; margin-top: 10px;">
                            <i class="fa fa-clock-o"></i>
                            Atualizado em <?= Yii::$app->formatter->asDatetime($template->data_atualizacao) ?>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <?= Html::a('<i class="fa fa-edit"></i> Editar', ['update', 'tipo' => $template->tipo], ['class' => 'btn btn-sm btn-primary']) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-info">
        <h4><i class="fa fa-info-circle"></i> Variáveis Disponíveis</h4>
        <p>Use as seguintes variáveis em seus templates:</p>
        <ul>
            <?php foreach (CobrancaTemplate::getVariaveisDisponiveis() as $variavel => $descricao): ?>
                <li><code><?= $variavel ?></code> - <?= $descricao ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>