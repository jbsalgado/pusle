<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\widgets\ActiveForm;

$this->title = 'Gerenciamento de Usuários';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
                    <p class="mt-1 text-sm text-gray-600">Gerencie usuários do sistema</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= Html::a('Voltar', ['/vendas/inicio/index'], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                    <?= Html::a('Novo Usuário', ['create'], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                    ]) ?>
                    <?= Html::a('Novo Usuário + Colaborador', ['create-completo'], [
                        'class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300',
                        'title' => 'Cria um novo usuário e já configura como colaborador'
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <?php $form = ActiveForm::begin([
                'method' => 'get',
                'action' => ['index'],
            ]); ?>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <?= Html::textInput('busca', Yii::$app->request->get('busca'), [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                        'placeholder' => 'Nome, usuário, email ou CPF'
                    ]) ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <?= Html::dropDownList('eh_dono_loja', Yii::$app->request->get('eh_dono_loja'), [
                        '' => 'Todos',
                        '1' => 'Dono da Loja',
                        '0' => 'Colaborador'
                    ], [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    ]) ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <?= Html::dropDownList('bloqueado', Yii::$app->request->get('bloqueado'), [
                        '' => 'Todos',
                        '0' => 'Ativo',
                        '1' => 'Bloqueado'
                    ], [
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500',
                    ]) ?>
                </div>
            </div>

            <div class="flex gap-2 pt-4">
                <?= Html::submitButton('Filtrar', [
                    'class' => 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300'
                ]) ?>
                <?= Html::a('Limpar', ['index'], [
                    'class' => 'px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-md transition duration-300'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
        
        <!-- Tabela de Usuários -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário / Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($dataProvider->totalCount > 0): ?>
                            <?php foreach ($dataProvider->getModels() as $usuario): ?>
                                <?php
                                // Busca colaborador associado (se for colaborador, não dono)
                                $colaborador = null;
                                if (!$usuario->eh_dono_loja) {
                                    $colaborador = \app\modules\vendas\models\Colaborador::find()
                                        ->where(['usuario_id' => $usuario->id])
                                        ->one();
                                }
                                $estaBloqueado = $usuario->isBlocked();
                                // Se é dono da loja, tem acesso completo
                                $ehAdmin = $usuario->eh_dono_loja ? true : ($colaborador ? $colaborador->eh_administrador : false);
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                                                <span class="text-white text-sm font-bold">
                                                    <?= strtoupper(substr($usuario->nome, 0, 1)) ?>
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= Html::encode($usuario->nome) ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?= $usuario->eh_dono_loja ? 'Dono da Loja' : 'Colaborador' ?>
                                                    <?php if ($ehAdmin): ?>
                                                        <span class="text-blue-600 font-semibold">• Administrador</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div><?= Html::encode($usuario->username ?? '-') ?></div>
                                        <div class="text-xs text-gray-400"><?= Html::encode($usuario->email ?? '-') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= Html::encode($usuario->cpf ?? '-') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= Html::encode($usuario->telefone ?? '-') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($estaBloqueado): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                Bloqueado
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Ativo
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!$usuario->isConfirmed()): ?>
                                            <span class="ml-1 px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800" title="Email não confirmado">
                                                Não confirmado
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex justify-center gap-2">
                                            <?= Html::a('Ver', ['view', 'id' => $usuario->id], [
                                                'class' => 'text-blue-600 hover:text-blue-900'
                                            ]) ?>
                                            <?= Html::a('Editar', ['update', 'id' => $usuario->id], [
                                                'class' => 'text-yellow-600 hover:text-yellow-900'
                                            ]) ?>
                                            <?= Html::a('Senha', ['mudar-senha', 'id' => $usuario->id], [
                                                'class' => 'text-purple-600 hover:text-purple-900'
                                            ]) ?>
                                            
                                            <?php if ($estaBloqueado): ?>
                                                <?= Html::beginForm(['ativar', 'id' => $usuario->id], 'post', [
                                                    'style' => 'display: inline-block;',
                                                    'onsubmit' => 'return confirm("Tem certeza que deseja ativar este usuário?");'
                                                ]) ?>
                                                    <?= Html::submitButton('Ativar', [
                                                        'class' => 'text-green-600 hover:text-green-900 bg-transparent border-0 p-0 cursor-pointer'
                                                    ]) ?>
                                                <?= Html::endForm() ?>
                                            <?php else: ?>
                                                <?= Html::beginForm(['bloquear', 'id' => $usuario->id], 'post', [
                                                    'style' => 'display: inline-block;',
                                                    'onsubmit' => 'return confirm("Tem certeza que deseja bloquear este usuário?");'
                                                ]) ?>
                                                    <?= Html::submitButton('Bloquear', [
                                                        'class' => 'text-red-600 hover:text-red-900 bg-transparent border-0 p-0 cursor-pointer'
                                                    ]) ?>
                                                <?= Html::endForm() ?>
                                            <?php endif; ?>
                                            
                                            <?php if (!$usuario->eh_dono_loja && !$colaborador): ?>
                                                <?= Html::a('Criar Colaborador', ['/vendas/colaborador/create', 'usuario_id' => $usuario->id], [
                                                    'class' => 'text-blue-600 hover:text-blue-900 font-semibold',
                                                    'title' => 'Criar colaborador para este usuário'
                                                ]) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    Nenhum usuário encontrado
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Paginação -->
        <?php if ($dataProvider->pagination->pageCount > 1): ?>
            <div class="mt-6">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'flex justify-center space-x-2'],
                    'linkOptions' => ['class' => 'px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50'],
                    'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
                    'prevPageLabel' => '←',
                    'nextPageLabel' => '→',
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

