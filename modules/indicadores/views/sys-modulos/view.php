<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\SysModulos */

$this->title = $model->modulo;
$this->params['breadcrumbs'][] = ['label' => 'Módulos do Sistema', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);
?>

<style>
.page-header {
    background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
    color: white;
    padding: 2rem 1rem;
    margin: -15px -15px 30px -15px;
    border-radius: 0 0 25px 25px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
}

.page-header h1 {
    margin: 0;
    font-weight: 300;
    font-size: 2.2rem;
    display: flex;
    align-items: center;
}

.page-header h1 i {
    margin-right: 15px;
    font-size: 2rem;
}

.module-path {
    background: rgba(255,255,255,0.15);
    padding: 10px 15px;
    border-radius: 20px;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.9rem;
    margin-top: 15px;
    backdrop-filter: blur(10px);
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 500;
    margin-top: 10px;
}

.status-active {
    background: rgba(40, 167, 69, 0.2);
    color: #155724;
}

.status-inactive {
    background: rgba(220, 53, 69, 0.2);
    color: #721c24;
}

.action-buttons {
    margin-top: 20px;
}

.btn-action {
    margin-right: 10px;
    margin-bottom: 10px;
    border-radius: 25px;
    padding: 10px 25px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
}

.btn-edit {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(240, 147, 251, 0.4);
    color: white;
}

.btn-back {
    background: #6c757d;
    color: white;
}

.btn-back:hover {
    background: #5a6268;
    transform: translateY(-1px);
    color: white;
}

.btn-delete {
    background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
    color: white;
}

.btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(252, 70, 107, 0.4);
    color: white;
}

.details-container {
    max-width: 900px;
    margin: 0 auto;
}

.details-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.details-section {
    margin-bottom: 30px;
    padding-bottom: 25px;
    border-bottom: 1px solid #eee;
}

.details-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.section-title {
    color: #495057;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 10px;
    color: #36d1dc;
}

.detail-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #36d1dc;
}

.detail-label {
    font-weight: 600;
    color: #495057;
    min-width: 150px;
    margin-right: 20px;
    flex-shrink: 0;
}

.detail-value {
    flex: 1;
    color: #6c757d;
}

.dimensions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.dimension-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
    transition: all 0.3s ease;
}

.dimension-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.dimension-name {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

.dimension-name i {
    margin-right: 8px;
}

.dimension-description {
    opacity: 0.9;
    font-size: 0.9rem;
    line-height: 1.4;
}

.no-dimensions {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
    background: #f8f9fa;
    border-radius: 10px;
    border: 2px dashed #dee2e6;
}

.no-dimensions i {
    font-size: 3rem;
    color: #dee2e6;
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem 1rem;
        margin: -15px -15px 20px -15px;
    }
    
    .page-header h1 {
        font-size: 1.6rem;
        flex-direction: column;
        text-align: center;
    }
    
    .page-header h1 i {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .details-container {
        padding: 0 10px;
    }
    
    .details-card {
        padding: 20px 15px;
        border-radius: 10px;
    }
    
    .detail-item {
        flex-direction: column;
        padding: 12px;
    }
    
    .detail-label {
        min-width: auto;
        margin-right: 0;
        margin-bottom: 8px;
    }
    
    .dimensions-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .btn-action {
        width: 100%;
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .action-buttons {
        margin-top: 15px;
    }
}
</style>

<div class="sys-modulos-view">
    <div class="page-header">
        <div class="container-fluid">
            <h1>
                <i class="fas fa-cube"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            
            <div class="module-path">
                <i class="fas fa-link"></i> <?= Html::encode($model->path) ?>
            </div>
            
            <div class="status-indicator <?= $model->status ? 'status-active' : 'status-inactive' ?>">
                <i class="fas <?= $model->status ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                <?= $model->status ? 'Módulo Ativo' : 'Módulo Inativo' ?>
            </div>
            
            <div class="action-buttons">
                <?= Html::a('<i class="fas fa-edit"></i> Editar', ['update', 'id' => $model->id], [
                    'class' => 'btn btn-action btn-edit'
                ]) ?>
                
                <?= Html::a('<i class="fas fa-trash"></i> Excluir', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-action btn-delete',
                    'data' => [
                        'confirm' => 'Tem certeza que deseja excluir este módulo?',
                        'method' => 'post',
                    ],
                ]) ?>
                
                <?= Html::a('<i class="fas fa-arrow-left"></i> Voltar', ['index'], [
                    'class' => 'btn btn-action btn-back'
                ]) ?>
            </div>
        </div>
    </div>

    <div class="details-container">
        <div class="details-card">
            <div class="details-section">
                <div class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Informações Gerais
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">ID do Módulo:</span>
                    <span class="detail-value"><?= Html::encode($model->id) ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Nome do Módulo:</span>
                    <span class="detail-value"><?= Html::encode($model->modulo) ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Caminho/URL:</span>
                    <span class="detail-value">
                        <code style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 0.9rem;">
                            <?= Html::encode($model->path) ?>
                        </code>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="badge <?= $model->status ? 'badge-success' : 'badge-danger' ?>" 
                              style="padding: 8px 15px; border-radius: 15px; font-size: 0.85rem;">
                            <i class="fas <?= $model->status ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                            <?= $model->status ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </span>
                </div>
            </div>

            <div class="details-section">
                <div class="section-title">
                    <i class="fas fa-sitemap"></i>
                    Dimensões Associadas (<?= count($model->dimensoesIndicadores) ?>)
                </div>
                
                <?php if (!empty($model->dimensoesIndicadores)): ?>
                    <div class="dimensions-grid">
                        <?php foreach ($model->dimensoesIndicadores as $dimensao): ?>
                            <div class="dimension-card">
                                <div class="dimension-name">
                                    <i class="fas fa-layer-group"></i>
                                    <?= Html::encode($dimensao->nome_dimensao) ?>
                                </div>
                                <?php if ($dimensao->descricao): ?>
                                    <div class="dimension-description">
                                        <?= Html::encode($dimensao->descricao) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="margin-top: 15px; font-size: 0.8rem; opacity: 0.8;">
                                    <i class="fas fa-hashtag"></i> ID: <?= $dimensao->id_dimensao ?>
                                    <?php if ($dimensao->id_dimensao_pai): ?>
                                        <br><i class="fas fa-arrow-up"></i> Pai: <?= $dimensao->id_dimensao_pai ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-dimensions">
                        <i class="fas fa-inbox"></i>
                        <h4>Nenhuma dimensão associada</h4>
                        <p>Este módulo ainda não possui dimensões vinculadas.</p>
                        <p>
                            <?= Html::a('<i class="fas fa-plus-circle"></i> Associar Dimensões', 
                                ['update', 'id' => $model->id], 
                                ['class' => 'btn btn-primary']) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add smooth animations
    $('.dimension-card').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's')
               .addClass('animated fadeInUp');
    });
    
    // Add tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>