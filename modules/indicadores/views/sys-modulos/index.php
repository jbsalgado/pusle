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
/* Mobile First - Base Styles */
.module-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem 1rem;
    margin: -15px -15px 20px -15px;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.module-header h1 {
    margin: 0;
    font-weight: 300;
    font-size: 1.4rem;
    line-height: 1.3;
}

.module-header .subtitle {
    opacity: 0.9;
    font-size: 0.85rem;
    margin-top: 0.5rem;
    line-height: 1.4;
}

.stats-row {
    margin-bottom: 15px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 1px 6px rgba(0,0,0,0.08);
    border-left: 3px solid #667eea;
    margin-bottom: 10px;
    text-align: center;
}

.stat-number {
    font-size: 1.6rem;
    font-weight: bold;
    color: #667eea;
    margin: 0;
}

.stat-label {
    color: #666;
    font-size: 0.8rem;
    margin-top: 5px;
    line-height: 1.3;
}

.btn-create {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border: none;
    border-radius: 20px;
    padding: 10px 20px;
    color: white;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(17, 153, 142, 0.3);
    width: 100%;
    margin-bottom: 15px;
}

.btn-create:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(17, 153, 142, 0.4);
    color: white;
}

.btn-create i {
    margin-right: 5px;
}

.grid-container {
    background: white;
    border-radius: 10px;
    padding: 10px;
    box-shadow: 0 1px 10px rgba(0,0,0,0.08);
    overflow: hidden;
}

/* Mobile Table Styles */
.table-responsive {
    border-radius: 8px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table {
    margin-bottom: 0;
    font-size: 0.8rem;
    min-width: 600px; /* Força scroll horizontal em mobile */
}

.table thead th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    color: #495057;
    padding: 10px 6px;
    font-size: 0.75rem;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fb;
}

.table tbody td {
    border: none;
    padding: 10px 6px;
    vertical-align: middle;
    font-size: 0.75rem;
}

/* Card-based layout for very small screens */
.mobile-card-view {
    display: none;
}

.module-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 1px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #667eea;
}

.module-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.module-card-title {
    font-weight: bold;
    color: #333;
    font-size: 0.9rem;
    flex: 1;
    margin-right: 10px;
}

.module-card-path {
    background: #f8f9fa;
    padding: 3px 6px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 0.7rem;
    color: #666;
    margin: 5px 0;
    display: inline-block;
}

.module-card-info {
    margin-bottom: 10px;
}

.module-card-dimensions {
    margin-bottom: 15px;
}

.module-card-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 3px;
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
    border-radius: 4px;
    margin: 0 1px;
    transition: all 0.2s ease;
    font-size: 0.7rem;
    padding: 4px 8px;
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
    padding: 2px 6px;
    margin: 1px;
    border-radius: 10px;
    font-size: 0.6rem;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-state i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 15px;
}

.empty-state h4 {
    font-size: 1.1rem;
    margin-bottom: 10px;
}

.empty-state p {
    font-size: 0.9rem;
    color: #999;
}

.grid-summary {
    color: #666;
    font-size: 0.8rem;
    margin-bottom: 10px;
    padding: 0 5px;
}

/* Actions column fixed width */
.actions-column {
    width: 100px !important;
    min-width: 100px !important;
    text-align: center !important;
}

/* Tablet Styles */
@media (min-width: 480px) {
    .module-header {
        padding: 1.8rem 1.5rem;
        border-radius: 0 0 12px 12px;
    }
    
    .module-header h1 {
        font-size: 1.6rem;
    }
    
    .stat-card {
        padding: 1.2rem;
    }
    
    .stat-number {
        font-size: 1.8rem;
    }
    
    .btn-create {
        width: auto;
        padding: 12px 25px;
        margin-bottom: 20px;
    }
    
    .grid-container {
        padding: 15px;
    }
    
    .table {
        font-size: 0.85rem;
    }
    
    .table thead th {
        padding: 12px 8px;
        font-size: 0.8rem;
    }
    
    .table tbody td {
        padding: 12px 8px;
        font-size: 0.8rem;
    }
}

/* Very small screens - show card layout */
@media (max-width: 400px) {
    .table-responsive {
        display: none;
    }
    
    .mobile-card-view {
        display: block;
    }
    
    .grid-summary {
        text-align: center;
        margin-bottom: 15px;
    }
}

/* Desktop Styles */
@media (min-width: 768px) {
    .module-header {
        padding: 2rem 1.5rem;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .module-header h1 {
        font-size: 1.8rem;
    }
    
    .module-header .subtitle {
        font-size: 0.95rem;
    }
    
    .stats-row {
        margin-bottom: 20px;
    }
    
    .stat-card {
        padding: 1.5rem;
        text-align: left;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .stat-label {
        font-size: 0.9rem;
    }
    
    .btn-create {
        border-radius: 25px;
        padding: 12px 30px;
        width: auto;
        box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
    }
    
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
    }
    
    .grid-container {
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    
    .table {
        font-size: 0.9rem;
        min-width: auto;
    }
    
    .table thead th {
        padding: 15px 12px;
        font-size: 0.85rem;
    }
    
    .table tbody td {
        padding: 15px 12px;
        font-size: 0.9rem;
    }
    
    .table tbody tr:hover {
        transform: scale(1.005);
    }
    
    .btn-group-sm .btn {
        margin: 0 2px;
        padding: 6px 10px;
        font-size: 0.8rem;
    }
    
    .dimensions-list li {
        padding: 4px 10px;
        font-size: 0.8rem;
    }
    
    .status-badge {
        padding: 6px 15px;
        font-size: 0.85rem;
    }
    
    .actions-column {
        width: 120px !important;
        min-width: 120px !important;
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
        <div class="col-sm-4 col-6">
            <div class="stat-card">
                <div class="stat-number"><?= $dataProvider->getTotalCount() ?></div>
                <div class="stat-label">Total de Módulos</div>
            </div>
        </div>
        <div class="col-sm-4 col-6">
            <div class="stat-card">
                <div class="stat-number">
                    <?= SysModulos::find()->where(['status' => true])->count() ?>
                </div>
                <div class="stat-label">Módulos Ativos</div>
            </div>
        </div>
        <div class="col-sm-4 col-12">
            <div class="stat-card">
                <div class="stat-number">
                    <?= IndDimensoesIndicadores::find()->count() ?>
                </div>
                <div class="stat-label">Dimensões Disponíveis</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 text-center text-md-right" style="margin-bottom: 20px;">
            <?= Html::a('<i class="fas fa-plus-circle"></i> Novo Módulo', ['/index.php/metricas/sys-modulos/create'], [
                'class' => 'btn btn-create'
            ]) ?>
        </div>
    </div>

    <div class="grid-container">
        <?php Pjax::begin(['id' => 'modulos-pjax', 'enablePushState' => false]); ?>
        
        <!-- Desktop/Tablet Table View -->
        <div class="table-responsive">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover'],
                'summary' => '<div class="grid-summary">Exibindo {begin}-{end} de {totalCount} módulos</div>',
                'emptyText' => '<div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h4>Nenhum módulo encontrado</h4>
                    <p>Clique no botão "Novo Módulo" para começar.</p>
                </div>',
                'columns' => [
                    [
                        'attribute' => 'modulo',
                        'label' => 'Módulo',
                        'format' => 'html',
                        'headerOptions' => ['style' => 'width: 20%; min-width: 120px;'],
                        'value' => function($model) {
                            return Html::tag('strong', Html::encode($model->modulo));
                        }
                    ],
                    [
                        'attribute' => 'path',
                        'label' => 'Caminho',
                        'format' => 'html',
                        'headerOptions' => ['style' => 'width: 25%; min-width: 150px;'],
                        'value' => function($model) {
                            return Html::tag('code', Html::encode($model->path), [
                                'style' => 'background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-size: 0.7rem; word-break: break-all;'
                            ]);
                        }
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Status',
                        'format' => 'html',
                        'headerOptions' => ['style' => 'width: 15%; min-width: 80px; text-align: center;'],
                        'contentOptions' => ['style' => 'text-align: center;'],
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
                        'label' => 'Dimensões',
                        'format' => 'html',
                        'headerOptions' => ['style' => 'width: 25%; min-width: 120px;'],
                        'value' => function($model) {
                            $dimensoes = $model->dimensoesIndicadores;
                            if (empty($dimensoes)) {
                                return '<span class="text-muted" style="font-size: 0.7rem;"><i>Nenhuma</i></span>';
                            }
                            
                            $html = '<ul class="dimensions-list">';
                            $count = 0;
                            foreach ($dimensoes as $dimensao) {
                                if ($count < 3) {
                                    $html .= '<li>' . Html::encode($dimensao->nome_dimensao) . '</li>';
                                    $count++;
                                }
                            }
                            if (count($dimensoes) > 3) {
                                $html .= '<li>+' . (count($dimensoes) - 3) . ' mais</li>';
                            }
                            $html .= '</ul>';
                            
                            return $html;
                        }
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Ações',
                        'headerOptions' => ['class' => 'actions-column'],
                        'contentOptions' => ['class' => 'actions-column'],
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
                        ]
                    ],
                ],
            ]); ?>
        </div>

        <!-- Mobile Card View -->
        <div class="mobile-card-view">
            <div class="grid-summary">
                <?php 
                $totalCount = $dataProvider->getTotalCount();
                echo "Total: {$totalCount} módulos";
                ?>
            </div>
            
            <?php if ($dataProvider->getModels()): ?>
                <?php foreach ($dataProvider->getModels() as $model): ?>
                    <div class="module-card">
                        <div class="module-card-header">
                            <div class="module-card-title"><?= Html::encode($model->modulo) ?></div>
                            <?php 
                                $class = $model->status ? 'status-active' : 'status-inactive';
                                $text = $model->status ? 'Ativo' : 'Inativo';
                                $icon = $model->status ? 'fas fa-check-circle' : 'fas fa-times-circle';
                            ?>
                            <span class="status-badge <?= $class ?>" 
                                  onclick="toggleStatus(<?= $model->id ?>)" 
                                  data-id="<?= $model->id ?>">
                                <i class="<?= $icon ?>"></i> <?= $text ?>
                            </span>
                        </div>
                        
                        <div class="module-card-info">
                            <div class="module-card-path"><?= Html::encode($model->path) ?></div>
                        </div>
                        
                        <div class="module-card-dimensions">
                            <strong style="font-size: 0.8rem; color: #666;">Dimensões:</strong><br>
                            <?php if (!empty($model->dimensoesIndicadores)): ?>
                                <ul class="dimensions-list" style="margin-top: 5px;">
                                    <?php foreach ($model->dimensoesIndicadores as $dimensao): ?>
                                        <li><?= Html::encode($dimensao->nome_dimensao) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 0.7rem;"><i>Nenhuma dimensão associada</i></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="module-card-actions">
                            <?= Html::a('<i class="fas fa-eye"></i>', ['view', 'id' => $model->id], [
                                'class' => 'btn btn-info btn-sm',
                                'title' => 'Visualizar'
                            ]) ?>
                            <?= Html::a('<i class="fas fa-edit"></i>', ['update', 'id' => $model->id], [
                                'class' => 'btn btn-warning btn-sm',
                                'title' => 'Editar'
                            ]) ?>
                            <?= Html::a('<i class="fas fa-trash"></i>', ['delete', 'id' => $model->id], [
                                'class' => 'btn btn-danger btn-sm',
                                'title' => 'Excluir',
                                'data-confirm' => 'Tem certeza que deseja excluir este módulo?',
                                'data-method' => 'post'
                            ]) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h4>Nenhum módulo encontrado</h4>
                    <p>Clique no botão "Novo Módulo" para começar.</p>
                </div>
            <?php endif; ?>
        </div>
        
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
                if (typeof toastr !== 'undefined') {
                    toastr.success(data.message);
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(data.message || 'Erro ao alterar status');
                } else {
                    alert(data.message || 'Erro ao alterar status');
                }
            }
        },
        error: function() {
            if (typeof toastr !== 'undefined') {
                toastr.error('Erro na comunicação com o servidor');
            } else {
                alert('Erro na comunicação com o servidor');
            }
        }
    });
}

// Initialize tooltips
$(document).ready(function() {
    if (typeof $.fn.tooltip !== 'undefined') {
        $('[data-toggle="tooltip"]').tooltip();
    }
});
</script>