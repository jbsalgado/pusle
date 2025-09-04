<?php

use app\modules\indicadores\models\IndDimensoesIndicadores;
use app\modules\indicadores\models\SysModulos;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Gerenciar Módulos do Sistema';
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.module-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 1rem;
    margin: -15px -15px 20px -15px;
    border-radius: 0 0 15px 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.module-header h1 {
    margin: 0;
    font-weight: 300;
    font-size: 1.8rem;
}

.module-header .subtitle {
    opacity: 0.9;
    font-size: 0.95rem;
    margin-top: 0.5rem;
}

.stats-row {
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border-left: 4px solid #667eea;
    margin-bottom: 15px;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    margin: 0;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
    margin-top: 5px;
}

.btn-create {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border: none;
    border-radius: 25px;
    padding: 12px 30px;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
}

.btn-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
    color: white;
}

.grid-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    overflow: hidden;
}

.table-responsive {
    border-radius: 10px;
    overflow: hidden;
}

.table {
    margin-bottom: 0;
}

.table thead th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    color: #495057;
    padding: 15px 12px;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fb;
    transform: scale(1.01);
}

.table tbody td {
    border: none;
    padding: 15px 12px;
    vertical-align: middle;
}

.status-badge {
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.status-active {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-inactive {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.status-badge:hover {
    transform: scale(1.05);
}

.btn-group-sm .btn {
    border-radius: 6px;
    margin: 0 2px;
    transition: all 0.2s ease;
}

.btn-info {
    background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
    border: none;
}

.btn-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border: none;
}

.btn-danger {
    background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
    border: none;
}

.dimensions-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.dimensions-list li {
    display: inline-block;
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 10px;
    margin: 2px;
    border-radius: 15px;
    font-size: 0.8rem;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-state i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .module-header {
        margin: -15px -15px 20px -15px;
        padding: 1.5rem 1rem;
    }
    
    .module-header h1 {
        font-size: 1.5rem;
    }
    
    .btn-create {
        width: 100%;
        margin-bottom: 15px;
    }
    
    .grid-container {
        padding: 15px;
        border-radius: 10px;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }
}
</style>

<div class="sys-modulos-index">
    <div class="module-header">
        <h1>
            <i class="fas fa-cubes"></i> 
            <?= Html::encode($this->title) ?>
        </h1>
        <div class="subtitle">
            Gerencie os módulos do sistema e suas dimensões associadas
        </div>
    </div>

    <div class="row stats-row">
        <div class="col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-number"><?= $dataProvider->getTotalCount() ?></div>
                <div class="stat-label">Total de Módulos</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-number">
                    <?= SysModulos::find()->where(['status' => true])->count() ?>
                </div>
                <div class="stat-label">Módulos Ativos</div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="stat-card">
                <div class="stat-number">
                    <?= IndDimensoesIndicadores::find()->count() ?>
                </div>
                <div class="stat-label">Dimensões Disponíveis</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-right" style="margin-bottom: 20px;">
            <?= Html::a('<i class="fas fa-plus-circle"></i> Novo Módulo', ['/index.php/metricas/sys-modulos/create'], [
                'class' => 'btn btn-create'
            ]) ?>
        </div>
    </div>

    <div class="grid-container">
        <?php Pjax::begin(['id' => 'modulos-pjax', 'enablePushState' => false]); ?>
        
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-hover'],
            'summary' => '<div class="text-muted" style="margin-bottom: 15px;">Exibindo {begin}-{end} de {totalCount} módulos</div>',
            'emptyText' => '<div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>Nenhum módulo encontrado</h4>
                <p>Clique no botão "Novo Módulo" para começar.</p>
            </div>',
            'columns' => [
                [
                    'attribute' => 'modulo',
                    'label' => 'Nome do Módulo',
                    'format' => 'html',
                    'value' => function($model) {
                        return Html::tag('strong', Html::encode($model->modulo));
                    }
                ],
                [
                    'attribute' => 'path',
                    'label' => 'Caminho',
                    'format' => 'html',
                    'value' => function($model) {
                        return Html::tag('code', Html::encode($model->path), [
                            'style' => 'background: #f8f9fa; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;'
                        ]);
                    }
                ],
                [
                    'attribute' => 'status',
                    'label' => 'Status',
                    'format' => 'html',
                    'value' => function($model) {
                        $class = $model->status ? 'status-active' : 'status-inactive';
                        $text = $model->status ? 'Ativo' : 'Inativo';
                        $icon = $model->status ? 'fas fa-check-circle' : 'fas fa-times-circle';
                        
                        return Html::tag('span', 
                            '<i class="' . $icon . '"></i> ' . $text,
                            [
                                'class' => 'status-badge ' . $class,
                                'onclick' => 'toggleStatus(' . $model->id . ')',
                                'data-id' => $model->id,
                                'title' => 'Clique para alterar status'
                            ]
                        );
                    }
                ],
                [
                    'label' => 'Dimensões Associadas',
                    'format' => 'html',
                    'value' => function($model) {
                        $dimensoes = $model->dimensoesIndicadores;
                        if (empty($dimensoes)) {
                            return '<span class="text-muted"><i>Nenhuma dimensão associada</i></span>';
                        }
                        
                        $html = '<ul class="dimensions-list">';
                        foreach ($dimensoes as $dimensao) {
                            $html .= '<li>' . Html::encode($dimensao->nome_dimensao) . '</li>';
                        }
                        $html .= '</ul>';
                        
                        return $html;
                    },
                    'contentOptions' => ['style' => 'max-width: 200px;']
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => 'Ações',
                    'template' => '{view} {update} {delete}',
                    'buttons' => [
                        'view' => function ($url, $model, $key) {
                            return Html::a('<i class="fas fa-eye"></i>', $url, [
                                'class' => 'btn btn-info btn-sm',
                                'title' => 'Visualizar',
                                'data-pjax' => '0'
                            ]);
                        },
                        'update' => function ($url, $model, $key) {
                            return Html::a('<i class="fas fa-edit"></i>', $url, [
                                'class' => 'btn btn-warning btn-sm',
                                'title' => 'Editar',
                                'data-pjax' => '0'
                            ]);
                        },
                        'delete' => function ($url, $model, $key) {
                            return Html::a('<i class="fas fa-trash"></i>', $url, [
                                'class' => 'btn btn-danger btn-sm',
                                'title' => 'Excluir',
                                'data-confirm' => 'Tem certeza que deseja excluir este módulo?',
                                'data-method' => 'post',
                                'data-pjax' => '0'
                            ]);
                        },
                    ],
                    'contentOptions' => ['class' => 'text-center', 'style' => 'width: 120px;']
                ],
            ],
        ]); ?>
        
        <?php Pjax::end(); ?>
    </div>
</div>

<script>
function toggleStatus(id) {
    $.ajax({
        url: '<?= \yii\helpers\Url::to(['toggle-status']) ?>',
        type: 'POST',
        data: {
            id: id,
            '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $.pjax.reload({
                    container: '#modulos-pjax',
                    timeout: 3000
                });
                
                // Show success message
                toastr.success(data.message);
            } else {
                toastr.error(data.message || 'Erro ao alterar status');
            }
        },
        error: function() {
            toastr.error('Erro na comunicação com o servidor');
        }
    });
}

// Initialize tooltips
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>