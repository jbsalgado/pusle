<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $customizadas app\modules\vendas\models\TrilhaSonora[] */
/* @var $padrao array */
/* @var $model app\modules\vendas\models\TrilhaSonora */

$this->title = 'Gerenciador de Trilhas Sonoras';
?>

<div class="container mx-auto px-4 py-6 max-w-7xl">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8 bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 p-6 rounded-2xl text-white shadow-xl">
        <div>
            <div class="flex items-center gap-2 text-purple-300 font-bold text-xs uppercase tracking-wider mb-1">
                <span>🎵 Estúdio de Vídeos 9:16</span> &bull; <span>Músicas de Fundo</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Biblioteca de Trilhas Sonoras</h1>
            <p class="text-sm text-purple-200 mt-1">Gerencie, ouça prévias e envie arquivos de áudio para os vídeos promocionais.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= Url::to(['/vendas/produto-video/studio']) ?>" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-semibold text-sm rounded-xl transition flex items-center gap-2 border border-white/20">
                <span>🎥 Voltar ao Studio</span>
            </a>
            <button onclick="abrirModalUpload()" class="px-5 py-2.5 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-bold text-sm rounded-xl shadow-lg shadow-purple-500/30 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Upload de Nova Música</span>
            </button>
        </div>
    </div>

    <!-- Guia de Formatos Permitidos -->
    <div class="bg-indigo-50/80 border border-indigo-200 rounded-2xl p-5 mb-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xl shrink-0 shadow-md">
                🎧
            </div>
            <div>
                <h3 class="font-bold text-gray-900 text-base">Formatos e Tipos de Áudio Permitidos</h3>
                <p class="text-xs text-gray-600 mt-0.5">
                    Aceitamos arquivos de áudio nos formatos <strong>.MP3, .WAV, .AAC, .M4A e .OGG</strong> com até <strong>15 MB</strong> de tamanho.
                </p>
                <div class="flex flex-wrap gap-2 mt-2">
                    <span class="px-2.5 py-1 bg-white border border-indigo-200 rounded-md text-[11px] font-bold text-indigo-700">✓ MP3 (Recomendado)</span>
                    <span class="px-2.5 py-1 bg-white border border-indigo-200 rounded-md text-[11px] font-bold text-indigo-700">✓ WAV</span>
                    <span class="px-2.5 py-1 bg-white border border-indigo-200 rounded-md text-[11px] font-bold text-indigo-700">✓ AAC / M4A</span>
                    <span class="px-2.5 py-1 bg-white border border-indigo-200 rounded-md text-[11px] font-bold text-indigo-700">✓ OGG</span>
                </div>
            </div>
        </div>
        <div class="text-xs text-indigo-900 bg-indigo-100/80 p-3 rounded-xl border border-indigo-200/80 shrink-0">
            <span class="font-bold block mb-1">💡 Dica do Gerador:</span>
            O sistema ajusta o corte do áudio e aplica <strong>Fade-Out automático</strong> no final do vídeo!
        </div>
    </div>

    <!-- Seção 1: Suas Trilhas Sonoras Customizadas -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <span>✨ Suas Músicas Enviadas</span>
                <span class="px-2.5 py-0.5 bg-purple-100 text-purple-700 text-xs font-bold rounded-full"><?= count($customizadas) ?></span>
            </h2>
        </div>

        <?php if (empty($customizadas)): ?>
            <div class="bg-white border-2 border-dashed border-gray-200 rounded-2xl p-10 text-center">
                <div class="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-3 text-purple-600">
                    🎶
                </div>
                <h3 class="font-bold text-gray-800 text-lg">Nenhuma música própria enviada ainda</h3>
                <p class="text-gray-500 text-sm max-w-md mx-auto mt-1 mb-4">Envie suas trilhas de áudio personalizadas em formato MP3 ou WAV para utilizar nos seus vídeos promocionais.</p>
                <button onclick="abrirModalUpload()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm rounded-xl transition inline-flex items-center gap-2">
                    <span>Fazer Upload Agora</span>
                </button>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($customizadas as $trilha): ?>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-base leading-snug line-clamp-1"><?= Html::encode($trilha->titulo) ?></h3>
                                    <p class="text-xs text-gray-500 line-clamp-1"><?= Html::encode($trilha->descricao ?: 'Música customizada do usuário') ?></p>
                                </div>
                                <div class="flex flex-col gap-1 items-end shrink-0">
                                    <span class="uppercase font-bold text-[10px] px-2 py-0.5 bg-purple-100 text-purple-800 rounded-md">
                                        <?= Html::encode($trilha->formato) ?>
                                    </span>
                                    <span class="font-bold text-[10px] px-2 py-0.5 <?= $trilha->tipo === 'efeito_especial' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800' ?> rounded-md">
                                        <?= $trilha->tipo === 'efeito_especial' ? '🔊 Efeito' : '🎵 Música' ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Audio Player HTML5 -->
                            <div class="my-4">
                                <audio controls class="w-full h-10 rounded-lg accent-purple-600">
                                    <source src="<?= $trilha->getUrl() ?>" type="audio/<?= $trilha->formato === 'mp3' ? 'mpeg' : $trilha->formato ?>">
                                    Seu navegador não suporta a prévia de áudio.
                                </audio>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                            <span>📦 <?= $trilha->getTamanhoFormatado() ?></span>
                            <?= Html::a('🗑️ Excluir', ['delete', 'id' => $trilha->id], [
                                'class' => 'text-red-600 hover:text-red-800 font-bold hover:underline',
                                'data' => [
                                    'confirm' => 'Tem certeza que deseja excluir esta trilha sonora?',
                                    'method' => 'post',
                                ],
                            ]) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Seção 2: Trilhas Sonoras Padrão do Sistema -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <span>🎼 Trilhas Padrão do Sistema</span>
                <span class="px-2.5 py-0.5 bg-gray-100 text-gray-600 text-xs font-bold rounded-full"><?= count($padrao) ?></span>
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($padrao as $key => $item): ?>
                <?php $urlPadrao = $item['url'] ?? (Yii::getAlias('@web', false) ? Url::to('@web/assets/audio/' . $item['arquivo'], true) : '/assets/audio/' . $item['arquivo']); ?>
                <div class="bg-gray-50/80 border border-gray-200 rounded-2xl p-4 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold">🎵</span>
                            <h3 class="font-bold text-gray-900 text-sm"><?= Html::encode($item['nome']) ?></h3>
                        </div>
                        <p class="text-xs text-gray-500 mb-3"><?= Html::encode($item['descricao']) ?></p>

                        <!-- Audio Player -->
                        <audio controls class="w-full h-8 rounded-lg accent-indigo-600">
                            <source src="<?= $urlPadrao ?>" type="audio/mpeg">
                            Seu navegador não suporta o áudio.
                        </audio>
                    </div>
                    <div class="mt-3 pt-2 border-t border-gray-200/60 text-[11px] font-bold text-indigo-700">
                        ✓ Disponível no Studio
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Modal Upload de Trilha Sonora -->
<div id="modalUploadTrilha" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden animate-scale-in">
        <div class="bg-gradient-to-r from-purple-700 to-indigo-800 px-6 py-4 text-white flex items-center justify-between">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span>🎵 Upload de Nova Trilha Sonora</span>
            </h3>
            <button onclick="fecharModalUpload()" class="text-purple-200 hover:text-white p-1 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <?php $form = ActiveForm::begin([
            'action' => ['upload'],
            'options' => ['enctype' => 'multipart/form-data', 'class' => 'p-6 space-y-4'],
        ]); ?>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">1. Título da Música / Efeito</label>
            <?= $form->field($model, 'titulo')->textInput([
                'class' => 'w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-semibold text-gray-900',
                'placeholder' => 'Ex: Pop Alegre Varejo 2026 ou Vinheta de Oferta',
            ])->label(false) ?>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">2. Tipo de Áudio</label>
            <?= $form->field($model, 'tipo')->dropDownList([
                \app\modules\vendas\models\TrilhaSonora::TIPO_MUSICA => '🎵 Música de Fundo',
                \app\modules\vendas\models\TrilhaSonora::TIPO_EFEITO => '🔊 Efeito Especial / Vinheta (SFX)',
            ], [
                'class' => 'w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-semibold text-gray-900 bg-white',
            ])->label(false) ?>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">3. Descrição ou Estilo (Opcional)</label>
            <?= $form->field($model, 'descricao')->textInput([
                'class' => 'w-full px-3.5 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm text-gray-900',
                'placeholder' => 'Ex: Batida animada ideal para produtos de verão',
            ])->label(false) ?>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">4. Selecione o Arquivo de Áudio</label>
            <div class="border-2 border-dashed border-gray-300 hover:border-purple-500 rounded-xl p-4 bg-gray-50 transition text-center">
                <?= $form->field($model, 'audioFile')->fileInput([
                    'class' => 'w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-purple-600 file:text-white hover:file:bg-purple-700 cursor-pointer',
                    'accept' => 'audio/mp3,audio/wav,audio/aac,audio/m4a,audio/ogg,.mp3,.wav,.aac,.m4a,.ogg',
                ])->label(false) ?>
                <p class="text-[11px] text-gray-400 mt-1">Extensões permitidas: .MP3, .WAV, .AAC, .M4A, .OGG (Máx. 15MB)</p>
            </div>
        </div>

        <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
            <button type="button" onclick="fecharModalUpload()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 font-bold text-xs rounded-xl transition">
                Cancelar
            </button>
            <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-lg transition">
                Enviar e Cadastrar Música
            </button>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<script>
    function abrirModalUpload() {
        document.getElementById('modalUploadTrilha').classList.remove('hidden');
    }
    function fecharModalUpload() {
        document.getElementById('modalUploadTrilha').classList.add('hidden');
    }
</script>
