<?php
// Esta view não é mais usada - o controller redireciona automaticamente para 'view'
// Mantida apenas para compatibilidade caso seja acessada diretamente
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Configurações';
$this->params['breadcrumbs'][] = $this->title;

// Fallback: se por algum motivo o redirect não funcionar, mostra link
?>
<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto text-center py-12">
        <p class="text-gray-600 mb-4">Redirecionando para configurações...</p>
        <?= Html::a('Clique aqui se não for redirecionado automaticamente', ['view'], ['class' => 'text-blue-600 hover:text-blue-800 underline']) ?>
    </div>
</div>
<script>
setTimeout(function() {
    window.location.href = '<?= Url::to(['view']) ?>';
}, 100);
</script>

