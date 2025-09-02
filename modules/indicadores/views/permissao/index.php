<?php

use app\assets\AppAsset;
use yii\helpers\Html;
use yii\widgets\Pjax;
use app\modules\indicadores\models\ManySysModulosHasManyUser;
use yii\helpers\ArrayHelper;
use yii\widgets\LinkPager;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 */

AppAsset::register($this);

$this->title = 'Permissões de Usuários';
$this->params['breadcrumbs'][] = $this->title;

// --- Bloco de Cálculo para os Cards de Estatísticas ---
$totalUsers = $dataProvider->getTotalCount();
$usersWithPermissions = Yii::$app->db->createCommand('SELECT COUNT(DISTINCT "id_user") FROM {{%many_sys_modulos_has_many_user}}')->queryScalar();
$usersWithoutPermissions = $totalUsers - $usersWithPermissions;

// Adiciona um bloco de CSS para refinamentos visuais
$this->registerCss("
    .user-card {
        transition: all 0.2s ease-in-out;
        border-left: 4px solid #e9ecef; /* Borda padrão mais sutil */
    }
    .user-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1); /* Sombra mais pronunciada */
        border-left-color: var(--bs-primary);
    }
    .user-actions {
        opacity: 0.6; /* Ações visíveis, mas discretas */
        transition: opacity 0.2s ease-in-out;
    }
    .user-card:hover .user-actions {
        opacity: 1;
    }
");

?>

<div class="permissao-index">

    <!-- Cabeçalho da Página -->
    <div class="mb-5">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <h1 class="h2 fw-bold mb-0"><?= Html::encode($this->title) ?></h1>
            <?= Html::a(
                '<i class="fas fa-plus me-2"></i>Vincular Usuário',
                ['/index.php/metricas/permissao/create'],
                ['class' => 'btn btn-primary shadow-sm rounded-pill']
            ) ?>
        </div>
        <div class="d-flex flex-wrap align-items-center text-muted small gap-3">
            <span><i class="fas fa-users me-1"></i> <?= $totalUsers ?> Total</span>
            <span class="text-success"><i class="fas fa-check-circle me-1"></i> <?= $usersWithPermissions ?> Com Permissão</span>
            <span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> <?= $usersWithoutPermissions ?> Sem Permissão</span>
        </div>
    </div>

    <?php Pjax::begin(['id' => 'permissao-grid-pjax', 'timeout' => 5000]); ?>

    <!-- Lista de Usuários Unificada -->
    <div class="list-container">
        <?php if ($dataProvider->getCount() > 0): ?>
            <?php foreach ($dataProvider->getModels() as $model): ?>
                <div class="card shadow-sm mb-3 border-0 user-card rounded-4">
                    <div class="card-body p-3">
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center">
                            
                            <!-- Avatar e Nome -->
                            <div class="d-flex align-items-center flex-grow-1 mb-3 mb-sm-0">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-secondary-subtle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                        <span class="fw-bold fs-6"><?= strtoupper(substr($model->username, 0, 2)) ?></span>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 fw-bold"><?= Html::encode($model->username) ?></h6>
                                    <small class="text-muted"><?= Html::encode($model->email) ?></small>
                                </div>
                            </div>

                            <!-- Módulos -->
                            <div class="mb-3 mb-sm-0 mx-sm-4">
                                <?php
                                $modulos = ManySysModulosHasManyUser::find()
                                    ->joinWith('sysModulos')
                                    ->where(['id_user' => $model->id])
                                    ->limit(4)
                                    ->all();
                                ?>
                                <?php if (empty($modulos)): ?>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal rounded-pill">Nenhum módulo</span>
                                <?php else: ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach (ArrayHelper::getColumn($modulos, 'sysModulos.modulo') as $name): ?>
                                            <span class="badge bg-info-subtle text-info-emphasis fw-normal rounded-pill"><?= Html::encode($name) ?></span>
                                        <?php endforeach; ?>
                                        <?php if(count($modulos) >= 4): ?>
                                            <span class="badge bg-light text-dark fw-normal rounded-pill">...</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Ações -->
                            <div class="ms-sm-auto user-actions">
                                <div class="btn-group" role="group">
                                    <?= Html::a('<i class="fas fa-eye"></i><span class="d-none d-sm-inline ms-1">Ver</span>', ['/index.php/metricas/permissao/view', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-secondary', 'data-pjax' => '0', 'title' => 'Ver Detalhes']) ?>
                                    <?= Html::a('<i class="fas fa-pencil-alt"></i><span class="d-none d-sm-inline ms-1">Editar</span>', ['/index.php/metricas/permissao/update', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary', 'data-pjax' => '0', 'title' => 'Gerenciar Permissões']) ?>
                                    <?= Html::a('<i class="fas fa-trash-alt"></i><span class="d-none d-sm-inline ms-1">Remover</span>', ['/index.php/metricas/permissao/delete', 'id' => $model->id], [
                                        'class' => 'btn btn-sm btn-outline-danger', 'data-pjax' => '0',
                                        'title' => 'Remover Permissões',
                                        'data-confirm' => 'Tem certeza que deseja remover todas as permissões deste usuário?',
                                        'data-method' => 'post',
                                    ]) ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-user-slash fa-3x mb-3"></i>
                <p>Nenhum usuário encontrado.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Paginação -->
    <?php if ($dataProvider->pagination->totalCount > $dataProvider->pagination->pageSize): ?>
        <div class="d-flex justify-content-center mt-4">
            <?= LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'pagination pagination-sm mb-0'],
                'linkContainerOptions' => ['class' => 'page-item'],
                'linkOptions' => ['class' => 'page-link'],
                'disabledListItemSubTagOptions' => ['tag' => 'a', 'class' => 'page-link'],
            ]); ?>
        </div>
    <?php endif; ?>

    <?php Pjax::end(); ?>
</div>
