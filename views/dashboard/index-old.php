<?php
/**
 * View: Dashboard Central - Seleção de Módulos
 * Localização: app/views/dashboard/index.php
 * 
 * @var yii\web\View $this
 * @var app\models\Usuario $usuario
 * @var array $modulosDisponiveis
 * @var app\models\Assinatura $assinatura
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Meus Módulos';

// Mapeamento de ícones SVG
$icones = [
    'shopping-cart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
    'game' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>',
    'chart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
    'cloud' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>',
];
?>

<div class="px-4 py-6 space-y-6 max-w-4xl mx-auto">
    
    <!-- Header -->
    <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900">
            Olá, <?= Html::encode($usuario->getPrimeiroNome()) ?>! 👋
        </h1>
        <p class="text-gray-600 mt-2">Escolha um módulo para começar</p>
    </div>

    <!-- Informações da Assinatura -->
    <?php if ($assinatura): ?>
        <div class="bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-100 mb-1">Seu Plano</p>
                    <h3 class="text-xl font-bold"><?= Html::encode($assinatura->plano->nome ?? 'N/A') ?></h3>
                    
                    <?php if ($assinatura->status === 'trial'): ?>
                        <p class="text-sm text-blue-100 mt-2">
                            ⏰ Trial expira em: 
                            <span class="font-semibold">
                                <?= date('d/m/Y', strtotime($assinatura->data_fim)) ?>
                            </span>
                        </p>
                    <?php elseif ($assinatura->data_fim): ?>
                        <p class="text-sm text-blue-100 mt-2">
                            Válido até: <?= date('d/m/Y', strtotime($assinatura->data_fim)) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-sm text-blue-100 mt-2">✨ Acesso Vitalício</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($assinatura->status === 'trial'): ?>
                    <?= Html::a(
                        'Fazer Upgrade',
                        ['/admin/plano/index'],
                        ['class' => 'bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition']
                    ) ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Módulos Disponíveis -->
    <div>
        <h2 class="text-xl font-bold text-gray-900 mb-4">Módulos Disponíveis</h2>
        
        <?php if (empty($modulosDisponiveis)): ?>
            <!-- Sem Módulos -->
            <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
                <div class="text-6xl mb-4">😔</div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Nenhum Módulo Disponível</h3>
                <p class="text-gray-600 mb-6">
                    Você não tem acesso a nenhum módulo no momento.
                </p>
                <?= Html::a(
                    'Ver Planos Disponíveis',
                    ['/admin/plano/index'],
                    ['class' => 'inline-block px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition']
                ) ?>
            </div>
        <?php else: ?>
            <!-- Grid de Módulos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($modulosDisponiveis as $modulo): ?>
                    <a href="<?= Url::to(['/' . $modulo['rota']]) ?>" 
                       class="card-touch block bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                        
                        <!-- Header do Card com Cor do Módulo -->
                        <div class="h-3" style="background-color: <?= Html::encode($modulo['cor'] ?? '#3B82F6') ?>"></div>
                        
                        <!-- Conteúdo do Card -->
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <!-- Ícone do Módulo -->
                                    <div class="rounded-xl p-3" style="background-color: <?= Html::encode($modulo['cor'] ?? '#3B82F6') ?>20;">
                                        <svg class="w-8 h-8" style="color: <?= Html::encode($modulo['cor'] ?? '#3B82F6') ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <?= $icones[$modulo['icone']] ?? $icones['cloud'] ?>
                                        </svg>
                                    </div>
                                    
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <?= Html::encode($modulo['modulo_nome']) ?>
                                        </h3>
                                        
                                        <!-- Badge de Tipo de Acesso -->
                                        <?php if ($modulo['tipo_acesso'] === 'trial'): ?>
                                            <span class="inline-block px-2 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded">
                                                Trial
                                            </span>
                                        <?php elseif ($modulo['tipo_acesso'] === 'acesso_direto'): ?>
                                            <span class="inline-block px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded">
                                                Acesso Direto
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Seta -->
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                            
                            <!-- Descrição -->
                            <p class="text-sm text-gray-600 mb-4">
                                <?= Html::encode($modulo['modulo_descricao']) ?>
                            </p>
                            
                            <!-- Data de Expiração (se houver) -->
                            <?php if ($modulo['data_expiracao']): ?>
                                <div class="flex items-center text-xs text-gray-500">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Acesso até <?= date('d/m/Y', strtotime($modulo['data_expiracao'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Botão de Gerenciar Assinatura -->
    <div class="text-center">
        <?= Html::a(
            '⚙️ Gerenciar Minha Assinatura',
            ['/admin/assinatura/minhas-assinaturas'],
            ['class' => 'inline-block px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition']
        ) ?>
    </div>

</div>

<style>
.card-touch {
    transition: transform 0.1s ease, box-shadow 0.1s ease;
}

.card-touch:active {
    transform: scale(0.98);
}
</style>