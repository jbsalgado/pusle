<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SysModulos */

$this->title = 'Editar Módulo: ' . $model->modulo;
$this->params['breadcrumbs'][] = ['label' => 'Módulos do Sistema', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->modulo, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<style>
.page-header {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 2rem 1rem;
    margin: -15px -15px 0 -15px;
    border-radius: 0 0 20px 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.page-header h1 {
    margin: 0;
    font-weight: 300;
    font-size: 2rem;
    display: flex;
    align-items: center;
}

.page-header h1 i {
    margin-right: 15px;
    font-size: 1.8rem;
}

.page-header .subtitle {
    opacity: 0.9;
    font-size: 1rem;
    margin-top: 10px;
    font-weight: 300;
}

.module-info {
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 15px;
    margin-top: 15px;
    backdrop-filter: blur(10px);
}

.module-info-item {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.module-info-item:last-child {
    margin-bottom: 0;
}

.module-info-item i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.module-info-label {
    font-weight: 500;
    margin-right: 10px;
    min-width: 60px;
}

.update-container {
    max-width: 800px;
    margin: 0 auto;
}

@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem 1rem;
        margin: -15px -15px 0 -15px;
    }
    
    .page-header h1 {
        font-size: 1.4rem;
        flex-direction: column;
        text-align: center;
    }
    
    .page-header h1 i {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .module-info-item {
        flex-direction: column;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    
    .module-info-label {
        margin-bottom: 5px;
    }
    
    .update-container {
        padding: 0 10px;
    }
}
</style>

<div class="sys-modulos-update">
    <div class="page-header">
        <div class="container-fluid">
            <h1>
                <i class="fas fa-edit"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <div class="subtitle">
                Modifique as informações do módulo e suas associações
            </div>
            
            <div class="module-info">
                <div class="module-info-item">
                    <i class="fas fa-id-badge"></i>
                    <span class="module-info-label">ID:</span>
                    <span><?= $model->id ?></span>
                </div>
                <div class="module-info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="module-info-label">Status:</span>
                    <span>
                        <?php if ($model->status): ?>
                            <i class="fas fa-check-circle"></i> Ativo
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i> Inativo
                        <?php endif; ?>
                    </span>
                </div>
                <div class="module-info-item">
                    <i class="fas fa-sitemap"></i>
                    <span class="module-info-label">Dimensões:</span>
                    <span><?= count($model->dimensoesIndicadores) ?> associada(s)</span>
                </div>
            </div>
        </div>
    </div>

    <div class="update-container">
        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>