<?php

use yii\helpers\Html;

$this->title = 'Editar: ' . $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Formas de Pagamento', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nome, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center mb-4">
                <a href="<?= yii\helpers\Url::to(['view', 'id' => $model->id]) ?>" 
                   class="mr-4 text-gray-600 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div class="flex-1">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                        <?= Html::encode($this->title) ?>
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Atualize as informações da forma de pagamento
                    </p>
                </div>

                <!-- Botão Deletar -->
                <div class="hidden sm:block">
                    <?= Html::beginForm(['delete', 'id' => $model->id], 'post', ['id' => 'delete-form']) ?>
                    <?= Html::button('Excluir', [
                        'class' => 'inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-lg text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors',
                        'onclick' => 'return confirmDelete()'
                    ]) ?>
                    <?= Html::endForm() ?>
                </div>
            </div>

            <!-- Warning Banner se houver parcelas -->
            <?php if ($model->getParcelas()->count() > 0): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Atenção:</strong> Esta forma de pagamento possui parcelas associadas e não pode ser excluída.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Form -->
        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>

        <!-- Botão Deletar Mobile -->
        <div class="sm:hidden mt-6">
            <?= Html::beginForm(['delete', 'id' => $model->id], 'post', ['id' => 'delete-form-mobile']) ?>
            <?= Html::button('Excluir Forma de Pagamento', [
                'class' => 'w-full inline-flex justify-center items-center px-6 py-3 border border-red-300 text-base font-medium rounded-lg text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors',
                'onclick' => 'return confirmDelete()'
            ]) ?>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('Tem certeza que deseja excluir esta forma de pagamento? Esta ação não pode ser desfeita.')) {
        return true;
    }
    return false;
}
</script>