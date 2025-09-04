<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SysModulos */

$this->title = 'Novo Módulo do Sistema';
$this->params['breadcrumbs'][] = ['label' => 'Módulos do Sistema', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.page-header {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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

.create-container {
    max-width: 800px;
    margin: 0 auto;
}

@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem 1rem;
        margin: -15px -15px 0 -15px;
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
    
    .create-container {
        padding: 0 10px;
    }
}
</style>

<div class="sys-modulos-create">
    <div class="page-header">
        <div class="container-fluid">
            <h1>
                <i class="fas fa-plus-circle"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <div class="subtitle">
                Adicione um novo módulo ao sistema e configure suas dimensões
            </div>
        </div>
    </div>

    <div class="create-container">
        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>