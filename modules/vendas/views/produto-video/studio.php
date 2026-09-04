<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $produtos app\modules\vendas\models\Produto[] */
/* @var $produtoSelecionado app\modules\vendas\models\Produto|null */
/* @var $videosRecentes app\modules\vendas\models\ProdutoVideo[] */

$this->title = 'Studio de Vídeos Promocionais 9:16';
$this->params['breadcrumbs'][] = ['label' => 'Produtos', 'url' => ['/vendas/produto/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.video-studio-container {
    background: #0f172a;
    color: #f8fafc;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    margin-bottom: 30px;
}

.studio-title {
    font-size: 1.75rem;
    font-weight: 700;
    background: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 6px;
}

.studio-subtitle {
    color: #94a3b8;
    font-size: 0.95rem;
    margin-bottom: 24px;
}

.glass-card {
    background: rgba(30, 41, 59, 0.7);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 24px;
    height: 100%;
}

.form-label-custom {
    font-weight: 600;
    font-size: 0.88rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #cbd5e1;
    margin-bottom: 8px;
    display: block;
}

.select-custom {
    background: #0f172a;
    border: 1px solid #334155;
    color: #f8fafc;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 0.95rem;
    width: 100%;
    transition: border-color 0.2s;
}

.select-custom:focus {
    border-color: #38bdf8;
    outline: none;
}

.duration-pills {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.duration-pill {
    flex: 1;
    background: #0f172a;
    border: 2px solid #334155;
    border-radius: 12px;
    padding: 14px 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
    user-select: none;
}

.duration-pill:hover {
    border-color: #38bdf8;
    transform: translateY(-2px);
}

.duration-pill.active {
    background: linear-gradient(135deg, #0284c7 0%, #4338ca 100%);
    border-color: #38bdf8;
    box-shadow: 0 8px 20px rgba(56, 189, 248, 0.25);
}

.duration-pill .time-val {
    font-size: 1.25rem;
    font-weight: 800;
    display: block;
    color: #ffffff;
}

.duration-pill .time-label {
    font-size: 0.75rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.duration-pill.active .time-label {
    color: #e0e7ff;
}

.btn-generate-video {
    background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
    border: none;
    color: #ffffff;
    font-weight: 700;
    font-size: 1.05rem;
    padding: 14px 24px;
    border-radius: 12px;
    width: 100%;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3);
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-generate-video:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 30px rgba(14, 165, 233, 0.45);
    background: linear-gradient(135deg, #0284c7 0%, #4f46e5 100%);
    color: #ffffff;
}

.btn-generate-video:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.preview-aspect-ratio {
    width: 100%;
    max-width: 320px;
    aspect-ratio: 9 / 16;
    background: #090d16;
    border-radius: 20px;
    border: 2px dashed #334155;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
    transition: all 0.3s ease;
}

.preview-aspect-ratio.is-feed {
    aspect-ratio: 1 / 1;
    max-width: 380px;
}

.preview-aspect-ratio video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 18px;
}

.placeholder-preview {
    text-align: center;
    padding: 30px;
    color: #64748b;
}

.placeholder-preview i {
    font-size: 3.5rem;
    margin-bottom: 16px;
    color: #475569;
}

.progress-box {
    display: none;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
    text-align: center;
}

.progress-bar-custom {
    height: 10px;
    background: #1e293b;
    border-radius: 5px;
    overflow: hidden;
    margin: 14px 0;
}

.progress-bar-inner {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #38bdf8, #818cf8);
    transition: width 0.4s ease;
    border-radius: 5px;
}

.action-buttons {
    display: none;
    margin-top: 16px;
    gap: 10px;
}

.btn-action-custom {
    flex: 1;
    padding: 10px 14px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.88rem;
    text-align: center;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-action-download {
    background: #10b981;
    color: #ffffff !important;
}

.btn-action-download:hover {
    background: #059669;
}

.btn-action-whatsapp {
    background: #25d366;
    color: #ffffff !important;
}

.btn-action-whatsapp:hover {
    background: #1da851;
}

.history-card {
    background: rgba(30, 41, 59, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: background 0.2s;
}

.history-card:hover {
    background: rgba(30, 41, 59, 0.8);
}

.option-radio-card {
    border: 2px solid rgba(255, 255, 255, 0.1);
    background: rgba(30, 41, 59, 0.6);
    border-radius: 12px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 12px;
}

.option-radio-card:hover {
    border-color: rgba(56, 189, 248, 0.5);
    background: rgba(30, 41, 59, 0.9);
}

input[type="radio"]:checked + .option-radio-card {
    border-color: #38bdf8 !important;
    background: rgba(56, 189, 248, 0.15) !important;
    box-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
}

.color-pill-card {
    border: 2px solid rgba(255, 255, 255, 0.1);
    background: rgba(30, 41, 59, 0.6);
    border-radius: 10px;
    padding: 8px 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #e2e8f0;
}

.color-pill-card:hover {
    border-color: rgba(56, 189, 248, 0.4);
}

input[type="radio"]:checked + .color-pill-card {
    border-color: #38bdf8 !important;
    background: rgba(56, 189, 248, 0.18) !important;
    color: #ffffff;
}

.product-select-list {
    background: rgba(15, 23, 42, 0.9);
    border: 1px solid #334155;
    border-radius: 12px;
    max-height: 250px;
    overflow-y: auto;
    padding: 8px;
}

.product-select-list::-webkit-scrollbar {
    width: 6px;
}
.product-select-list::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 3px;
}

.product-item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    margin-bottom: 6px;
    background: rgba(30, 41, 59, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.2s ease;
}

.product-item-row:hover {
    background: rgba(30, 41, 59, 0.8);
    border-color: rgba(56, 189, 248, 0.3);
}

.product-item-row.is-previewing {
    border-left: 3px solid #38bdf8;
    background: rgba(56, 189, 248, 0.08);
}

.product-item-row.disabled-item {
    opacity: 0.55;
    background: rgba(15, 23, 42, 0.4);
}

.trilha-card {
    background: rgba(30, 41, 59, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    transition: all 0.2s ease;
}

.trilha-card:hover {
    border-color: rgba(56, 189, 248, 0.4);
    background: rgba(30, 41, 59, 0.8);
}

.trilha-card.is-checked {
    border-color: #38bdf8;
    background: rgba(56, 189, 248, 0.1);
}

.decision-card-option {
    border: 2px solid rgba(255, 255, 255, 0.1);
    background: rgba(30, 41, 59, 0.7);
    border-radius: 14px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    height: 100%;
}

.decision-card-option:hover {
    border-color: rgba(56, 189, 248, 0.6);
    background: rgba(30, 41, 59, 0.95);
    transform: translateY(-2px);
}

.decision-card-option.active {
    border-color: #38bdf8 !important;
    background: rgba(56, 189, 248, 0.15) !important;
    box-shadow: 0 0 20px rgba(56, 189, 248, 0.25);
}
</style>

<div class="video-studio-container">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h1 class="studio-title">🎬 Studio de Vídeos Promocionais 9:16</h1>
            <p class="studio-subtitle">Gere vídeos curtos verticais de alta qualidade otimizados para Reels, TikTok, Stories e WhatsApp sem custos de API.</p>
        </div>
        <div class="col-md-4 text-end">
            <?= Html::a('<i class="glyphicon glyphicon-arrow-left"></i> Voltar aos Produtos', ['/vendas/produto/index'], ['class' => 'btn btn-outline-light btn-sm', 'style' => 'border-color:#475569; color:#cbd5e1;']) ?>
    </div>

    <!-- Card de Cota de Armazenamento Multi-Tenant -->
    <?php $storageStats = \app\modules\vendas\services\MediaStorageService::getEstatisticasVideos(); ?>
    <div id="container-cota-armazenamento" class="glass-card mb-4" style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 18px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
            <span style="font-weight: 700; color: #f8fafc; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span>📹 Armazenamento de Vídeos da Loja:</span>
                <span id="lbl-uso-mb" style="color: #38bdf8;"><?= $storageStats['usado_mb'] ?> MB</span> / <span id="lbl-limite-mb" style="color: #94a3b8;"><?= $storageStats['limite_mb'] ?> MB</span>
            </span>
            <span id="lbl-percentual" style="font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 12px; background: <?= $storageStats['excedido'] ? '#ef4444' : ($storageStats['percentual'] > 80 ? '#f59e0b' : '#10b981') ?>; color: white;">
                <?= $storageStats['percentual'] ?>% utilizado
            </span>
        </div>
        <div style="height: 10px; background-color: rgba(255, 255, 255, 0.1); border-radius: 6px; overflow: hidden; margin-bottom: 6px;">
            <div id="bar-cota-progresso" 
                 style="height: 100%; width: <?= $storageStats['percentual'] ?>%; background-color: <?= $storageStats['excedido'] ? '#ef4444' : ($storageStats['percentual'] > 80 ? '#f59e0b' : '#3b82f6') ?>; transition: width 0.4s ease, background-color 0.4s ease;">
            </div>
        </div>
        <div id="alerta-cota-excedida" style="display: <?= $storageStats['excedido'] ? 'block' : 'none' ?>; font-size: 12px; color: #fca5a5; margin-top: 6px; background: rgba(239, 68, 68, 0.15); padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">
            ⚠️ <strong>Limite de Armazenamento Excedido (<?= $storageStats['limite_mb'] ?> MB)!</strong> Para gerar novos vídeos, você deve excluir vídeos antigos no histórico abaixo para liberar espaço em disco.
        </div>
    </div>

    <div class="row">
        <!-- Coluna de Configuração -->
        <div class="col-lg-6 mb-4">
            <div class="glass-card">
                <form id="form-gerar-video" onsubmit="return false;">
                    <!-- 1. Seleção Inteligente de Produtos (Múltiplos com Filtro de Fotos) -->
                    <div class="mb-4">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                            <label class="form-label-custom" style="margin-bottom: 0;">1. Selecione o(s) Produto(s) para o Lote</label>
                            <span id="lbl-contagem-produtos" class="badge" style="background: #38bdf8; color: #0f172a; font-weight: 800; font-size: 11px; border-radius: 12px; padding: 4px 10px;">
                                0 selecionados
                            </span>
                        </div>

                        <!-- Barra de Pesquisa e Ações Rápidas -->
                        <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                            <input type="text" id="filtro-produtos-studio" class="select-custom" placeholder="🔍 Buscar por nome..." style="padding: 8px 12px; font-size: 0.88rem; flex: 1;">
                            <button type="button" id="btn-marcar-todos-fotos" class="btn btn-sm btn-outline-info" style="border-color:#38bdf8; color:#38bdf8; border-radius: 8px; font-weight: 600; white-space: nowrap; font-size: 0.8rem;" title="Marcar todos os produtos com fotos cadastradas">
                                ✅ Marcar c/ Fotos
                            </button>
                            <button type="button" id="btn-desmarcar-todos-produtos" class="btn btn-sm btn-outline-secondary" style="border-color:#475569; color:#94a3b8; border-radius: 8px; font-weight: 600; white-space: nowrap; font-size: 0.8rem;">
                                ✖ Limpar
                            </button>
                        </div>

                        <!-- Lista de Produtos Rolável -->
                        <div id="lista-produtos-studio" class="product-select-list">
                            <?php if (empty($produtos)): ?>
                                <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 0.88rem;">
                                    Nenhum produto cadastrado na loja.
                                </div>
                            <?php else: ?>
                                <?php foreach ($produtos as $prod): 
                                    $qtdFotos = $fotosCountMap[$prod->id] ?? 0;
                                    $infoMatriz = $matrizCountMap[$prod->id] ?? null;
                                    $qtdCores = $infoMatriz ? (int)$infoMatriz['total_cores'] : 0;
                                    $semFotos = ($qtdFotos === 0);
                                    $estaInicialmenteMarcado = in_array($prod->id, $produtosIdsIniciais) && !$semFotos;
                                    $ehProdutoPreview = ($produtoSelecionado && $produtoSelecionado->id === $prod->id);
                                ?>
                                    <div class="product-item-row <?= $semFotos ? 'disabled-item' : '' ?> <?= $ehProdutoPreview ? 'is-previewing' : '' ?>" id="prod-row-<?= $prod->id ?>" data-id="<?= $prod->id ?>" data-nome="<?= Html::encode(mb_strtolower($prod->nome)) ?>">
                                        <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                                            <input type="checkbox" 
                                                   name="chk_produto_item" 
                                                   class="chk-produto-item" 
                                                   value="<?= Html::encode($prod->id) ?>" 
                                                   data-nome="<?= Html::encode($prod->nome) ?>"
                                                   data-fotos="<?= $qtdFotos ?>"
                                                   data-cores="<?= $qtdCores ?>"
                                                   <?= $estaInicialmenteMarcado ? 'checked' : '' ?>
                                                   <?= $semFotos ? 'disabled' : '' ?>
                                                   style="width: 18px; height: 18px; cursor: <?= $semFotos ? 'not-allowed' : 'pointer' ?>; accent-color: #38bdf8;">
                                            
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                    <span style="font-weight: 700; font-size: 0.88rem; color: <?= $semFotos ? '#94a3b8' : '#f8fafc' ?>; word-break: break-word;">
                                                        <?= Html::encode($prod->nome) ?>
                                                    </span>
                                                    <span style="font-weight: 700; color: #10b981; font-size: 0.82rem;">
                                                        R$ <?= number_format((float)$prod->getPrecoFinal(), 2, ',', '.') ?>
                                                    </span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 3px;">
                                                    <?php if ($semFotos): ?>
                                                        <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 10px;">
                                                            ⚠️ 0 fotos (Bloqueado)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge" style="background: rgba(2, 132, 199, 0.25); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4); font-size: 10px;">
                                                            📸 <?= $qtdFotos ?> <?= $qtdFotos == 1 ? 'foto' : 'fotos' ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if ($qtdCores > 1): ?>
                                                        <span class="badge" style="background: rgba(139, 92, 246, 0.25); color: #c084fc; border: 1px solid rgba(192, 132, 252, 0.4); font-size: 10px;">
                                                            🎨 Matriz: <?= $qtdCores ?> cores
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (!$semFotos): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info btn-set-preview-prod" data-id="<?= $prod->id ?>" style="border-radius: 6px; font-size: 11px; padding: 3px 8px; border-color: #334155; color: #38bdf8;" title="Ver detalhes/vídeos deste produto no Studio">
                                                👁️ Prévia
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="produto_preview_ativo_id" value="<?= $produtoSelecionado ? $produtoSelecionado->id : '' ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">2. Escolha a Duração do Vídeo</label>
                        <div class="duration-pills" style="flex-wrap: wrap;">
                            <div class="duration-pill active" data-duracao="5">
                                <span class="time-val">5s</span>
                                <span class="time-label">Oferta Rápida</span>
                            </div>
                            <div class="duration-pill" data-duracao="10">
                                <span class="time-val">10s</span>
                                <span class="time-label">Carrossel Reel</span>
                            </div>
                            <div class="duration-pill" data-duracao="15">
                                <span class="time-val">15s</span>
                                <span class="time-label">Apresentação</span>
                            </div>
                            <div class="duration-pill" data-duracao="30">
                                <span class="time-val">30s</span>
                                <span class="time-label">Comercial 30s</span>
                            </div>
                            <div class="duration-pill" data-duracao="60">
                                <span class="time-val">60s</span>
                                <span class="time-label">Vídeo Completo</span>
                            </div>
                        </div>
                        <div id="lbl-ritmo-fotos" style="margin-top: 10px; font-size: 0.8rem; color: #38bdf8; font-weight: 600; display: flex; align-items: center; gap: 6px; background: rgba(56,189,248,0.08); padding: 6px 12px; border-radius: 8px; border: 1px solid rgba(56,189,248,0.2);">
                            ⏱️ <span id="lbl-ritmo-texto">Ritmo Confortável: ~3.75s por foto (Máx: 4 fotos para 15s)</span>
                        </div>
                    </div>

                    <!-- 1. Formato da Publicação -->
                    <div class="mb-4">
                        <label class="form-label-custom">1. Formato da Publicação</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <label class="mb-0">
                                <input type="radio" name="video_formato" value="feed" class="d-none">
                                <div class="color-pill-card">
                                    <span style="font-weight: 800; color: #38bdf8;">1:1</span> Feed / Post (1080×1080)
                                </div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_formato" value="stories" checked class="d-none">
                                <div class="color-pill-card">
                                    <span style="font-weight: 800; color: #38bdf8;">9:16</span> Stories / Reels (1080×1920)
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- 2. Modelo de Layout -->
                    <div class="mb-4">
                        <label class="form-label-custom">2. Modelo de Layout</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <label class="mb-0">
                                <input type="radio" name="video_template" value="modern_dark" checked class="d-none">
                                <div class="color-pill-card">💎 Modern Dark</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_template" value="vibrant_gradient" class="d-none">
                                <div class="color-pill-card">🌈 Vibrant Gradient</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_template" value="minimalist_light" class="d-none">
                                <div class="color-pill-card">✨ Minimalist Light</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_template" value="neon_promo" class="d-none">
                                <div class="color-pill-card">⚡ Neon Promo</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_template" value="full_bleed_banner" class="d-none">
                                <div class="color-pill-card">🖼️ Foto em Tela Cheia</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_template" value="bold_banner" class="d-none">
                                <div class="color-pill-card">📣 Bold Banner</div>
                            </label>
                        </div>
                    </div>

                    <!-- 3. Paleta de Cores -->
                    <div class="mb-4">
                        <label class="form-label-custom">3. Paleta de Cores</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <label class="mb-0">
                                <input type="radio" name="video_cor" value="dark" checked class="d-none">
                                <div class="color-pill-card">
                                    <span style="width: 14px; height: 14px; border-radius: 50%; background: #0f172a; border: 1px solid #475569; display: inline-block;"></span> Dark Slate
                                </div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_cor" value="ocean" class="d-none">
                                <div class="color-pill-card">
                                    <span style="width: 14px; height: 14px; border-radius: 50%; background: #0284c7; display: inline-block;"></span> Ocean Blue
                                </div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_cor" value="emerald" class="d-none">
                                <div class="color-pill-card">
                                    <span style="width: 14px; height: 14px; border-radius: 50%; background: #059669; display: inline-block;"></span> Emerald Green
                                </div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_cor" value="purple" class="d-none">
                                <div class="color-pill-card">
                                    <span style="width: 14px; height: 14px; border-radius: 50%; background: #7c3aed; display: inline-block;"></span> Purple Sunset
                                </div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_cor" value="sunset" class="d-none">
                                <div class="color-pill-card">
                                    <span style="width: 14px; height: 14px; border-radius: 50%; background: #ea580c; display: inline-block;"></span> Sunset Orange
                                </div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_cor" value="rose" class="d-none">
                                <div class="color-pill-card">
                                    <span style="width: 14px; height: 14px; border-radius: 50%; background: #e11d48; display: inline-block;"></span> Rose Pink
                                </div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_cor" value="gold" class="d-none">
                                <div class="color-pill-card">
                                    <span style="width: 14px; height: 14px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span> Premium Gold
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- 4. Estilo de Fundo -->
                    <div class="mb-4">
                        <label class="form-label-custom">4. Estilo de Fundo</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <label class="mb-0">
                                <input type="radio" name="video_fundo" value="gradient" checked class="d-none">
                                <div class="color-pill-card">Gradiente</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_fundo" value="mesh" class="d-none">
                                <div class="color-pill-card">Mesh Fluid</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_fundo" value="geometric" class="d-none">
                                <div class="color-pill-card">Geométrico</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_fundo" value="grid" class="d-none">
                                <div class="color-pill-card">Grid Pontos</div>
                            </label>
                        </div>
                    </div>

                    <!-- 5. Efeitos Especiais de Animação / Partículas -->
                    <div class="mb-4">
                        <label class="form-label-custom">5. Efeitos Especiais de Animação (Partículas & Overlays)</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="none" checked class="d-none">
                                <div class="color-pill-card">🚫 Sem Efeito</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="fireworks" class="d-none">
                                <div class="color-pill-card">🎆 Fogos Artifício</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="confetti" class="d-none">
                                <div class="color-pill-card">🎉 Confetes Festa</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="sparks" class="d-none">
                                <div class="color-pill-card">⚡ Faíscas & Neons</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="stars" class="d-none">
                                <div class="color-pill-card">✨ Estrelas & Glow</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="hearts" class="d-none">
                                <div class="color-pill-card">💖 Corações</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="baby_kids" class="d-none">
                                <div class="color-pill-card">👶 Bebê Risonho & Kids</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="flowers" class="d-none">
                                <div class="color-pill-card">🌼 Flores & Margaridas</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="paws" class="d-none">
                                <div class="color-pill-card">🐾 Patas de Pet</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="balloons" class="d-none">
                                <div class="color-pill-card">🎈 Balões Pastel</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="gifts" class="d-none">
                                <div class="color-pill-card">🎁 Caixas de Presente</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="christmas" class="d-none">
                                <div class="color-pill-card">🎄 Natal & Festas</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="birthday" class="d-none">
                                <div class="color-pill-card">🎂 Aniversário & Bolo</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="fashion" class="d-none">
                                <div class="color-pill-card">👗 Moda & Fashion</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="valentines" class="d-none">
                                <div class="color-pill-card">💘 Dia dos Namorados</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="shoes" class="d-none">
                                <div class="color-pill-card">👠 Sapatos & Calçados</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="handbags" class="d-none">
                                <div class="color-pill-card">👜 Bolsas & Acessórios</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="sweets" class="d-none">
                                <div class="color-pill-card">🍬 Doces & Confeitos</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="shirts" class="d-none">
                                <div class="color-pill-card">👔 Blusas & Camisas</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="jeans" class="d-none">
                                <div class="color-pill-card">👖 Calças Jeans</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="sneakers" class="d-none">
                                <div class="color-pill-card">👟 Tênis Sneakers</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="woman" class="d-none">
                                <div class="color-pill-card">👩 Mulher Kawaii</div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_efeito_visual" value="man" class="d-none">
                                <div class="color-pill-card">👨 Homem Kawaii</div>
                            </label>
                        </div>
                    </div>

                    <?php 
                    $musicas = \app\modules\vendas\services\VideoGeneratorService::getMusicasDisponiveis(); 
                    $faixasMusica = array_filter($musicas, function($item) {
                        return ($item['tipo_audio'] ?? 'musica') === \app\modules\vendas\models\TrilhaSonora::TIPO_MUSICA;
                    });
                    $faixasEfeito = array_filter($musicas, function($item) {
                        return ($item['tipo_audio'] ?? 'musica') === \app\modules\vendas\models\TrilhaSonora::TIPO_EFEITO;
                    });
                    ?>
                    <!-- 4. Escolha da(s) Trilha(s) Sonora(s) ou Efeitos Especiais -->
                    <div class="mb-4">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                            <label class="form-label-custom" style="margin-bottom: 0;">4. Escolha a(s) Trilha(s) Sonora(s) ou Efeitos</label>
                            <span id="lbl-contagem-trilhas" class="badge" style="background: #10b981; color: #fff; font-weight: 800; font-size: 11px; border-radius: 12px; padding: 4px 10px;">
                                1 selecionada
                            </span>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                            <div style="display: flex; gap: 6px;">
                                <button type="button" id="btn-marcar-todas-musicas" class="btn btn-sm btn-outline-info" style="border-color:#38bdf8; color:#38bdf8; border-radius: 8px; font-weight: 600; font-size: 0.78rem;">
                                    🎵 Marcar Músicas
                                </button>
                                <button type="button" id="btn-selecionar-uma-musica" class="btn btn-sm btn-outline-secondary" style="border-color:#475569; color:#94a3b8; border-radius: 8px; font-weight: 600; font-size: 0.78rem;">
                                    🎯 Apenas a 1ª
                                </button>
                            </div>
                            <div style="display: flex; gap: 6px;">
                                <button type="button" onclick="abrirModalStudioUpload()" class="btn btn-sm btn-outline-success" style="border-color:#10b981; color:#34d399; border-radius: 8px; font-weight: 600; font-size: 0.78rem;" title="Fazer Upload Rápido de Áudio">
                                    ➕ Upload Rápido
                                </button>
                                <a href="<?= \yii\helpers\Url::to(['/vendas/trilha-sonora']) ?>" class="btn btn-sm btn-outline-secondary" style="border-color:#475569; color:#c084fc; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 0.78rem;" title="Gerenciar Músicas de Fundo">
                                    🎵 Biblioteca
                                </a>
                            </div>
                        </div>

                        <div style="font-size: 0.8rem; color: #38bdf8; background: rgba(56,189,248,0.08); border: 1px solid rgba(56,189,248,0.2); border-radius: 8px; padding: 6px 12px; margin-bottom: 10px;">
                            💡 <strong>Regra de Músicas:</strong> Se selecionar <strong>1 música</strong>, ela será usada em todos os vídeos. Se selecionar <strong>várias</strong>, o sistema distribuirá as faixas ciclicamente (<em>Round-Robin</em>) entre os vídeos do lote!
                        </div>

                        <!-- Lista de Trilhas Rolável -->
                        <div id="lista-trilhas-studio" class="product-select-list" style="max-height: 220px;">
                            <?php $isFirst = true; ?>
                            <?php foreach ($faixasMusica as $m): ?>
                                <div class="trilha-card <?= $isFirst ? 'is-checked' : '' ?>" style="margin-bottom: 6px;">
                                    <label style="display: flex; align-items: center; gap: 10px; margin: 0; cursor: pointer; flex: 1; min-width: 0;">
                                        <input type="checkbox" 
                                               name="chk_trilha_item" 
                                               class="chk-trilha-item" 
                                               value="<?= Html::encode($m['arquivo']) ?>" 
                                               data-url="<?= Html::encode($m['url']) ?>" 
                                               data-nome="<?= Html::encode($m['nome']) ?>" 
                                               <?= $isFirst ? 'checked' : '' ?> 
                                               style="width: 17px; height: 17px; cursor: pointer; accent-color: #10b981;">
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="font-weight: 700; font-size: 0.88rem; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                🎵 <?= Html::encode($m['nome']) ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= Html::encode($m['descricao']) ?>
                                            </div>
                                        </div>
                                    </label>
                                    <button type="button" class="btn btn-xs btn-outline-info btn-preview-track" data-url="<?= Html::encode($m['url']) ?>" style="border-radius: 6px; font-size: 11px; padding: 3px 8px; border-color: #334155; color: #38bdf8; white-space: nowrap;">
                                        🔊 Ouvir
                                    </button>
                                </div>
                                <?php $isFirst = false; ?>
                            <?php endforeach; ?>

                            <?php if (!empty($faixasEfeito)): ?>
                                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin: 10px 0 6px 4px;">
                                    🔊 Efeitos Especiais & Vinhetas
                                </div>
                                <?php foreach ($faixasEfeito as $m): ?>
                                    <div class="trilha-card" style="margin-bottom: 6px;">
                                        <label style="display: flex; align-items: center; gap: 10px; margin: 0; cursor: pointer; flex: 1; min-width: 0;">
                                            <input type="checkbox" 
                                                   name="chk_trilha_item" 
                                                   class="chk-trilha-item" 
                                                   value="<?= Html::encode($m['arquivo']) ?>" 
                                                   data-url="<?= Html::encode($m['url']) ?>" 
                                                   data-nome="<?= Html::encode($m['nome']) ?>" 
                                                   style="width: 17px; height: 17px; cursor: pointer; accent-color: #10b981;">
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="font-weight: 700; font-size: 0.88rem; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    🔊 <?= Html::encode($m['nome']) ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    <?= Html::encode($m['descricao']) ?>
                                                </div>
                                            </div>
                                        </label>
                                        <button type="button" class="btn btn-xs btn-outline-info btn-preview-track" data-url="<?= Html::encode($m['url']) ?>" style="border-radius: 6px; font-size: 11px; padding: 3px 8px; border-color: #334155; color: #38bdf8; white-space: nowrap;">
                                            🔊 Ouvir
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <audio id="audio-preview-element" style="display:none;"></audio>
                    </div>

                    <!-- 6. Modo de Composição de Mídia & Origem dos Vídeos -->
                    <?php 
                        $videosCadastradosDoProduto = $produtoSelecionado ? $produtoSelecionado->videos : [];
                        $qtdVideosDoProduto = count($videosCadastradosDoProduto);
                    ?>
                    <div class="mb-4" id="box-modo-composicao">
                        <label class="form-label-custom">6. Origem de Mídia & Modo de Composição</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <label class="mb-0">
                                <input type="radio" name="video_modo_composicao" value="hibrido" <?= $qtdVideosDoProduto > 0 ? 'checked' : '' ?> class="d-none">
                                <div class="color-pill-card">
                                    🔀 Híbrido (Fotos + Vídeo Real)
                                </div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_modo_composicao" value="apenas_fotos" <?= $qtdVideosDoProduto == 0 ? 'checked' : '' ?> class="d-none">
                                <div class="color-pill-card">
                                    📸 Apenas Fotos da Galeria
                                </div>
                            </label>
                            <label class="mb-0">
                                <input type="radio" name="video_modo_composicao" value="video_real" class="d-none">
                                <div class="color-pill-card">
                                    🎥 Apenas Vídeo Real com Overlays
                                </div>
                            </label>
                        </div>
                        <?php if ($qtdVideosDoProduto > 0): ?>
                            <div style="margin-top: 8px; font-size: 0.8rem; color: #a855f7; font-weight: 600; background: rgba(168,85,247,0.1); padding: 6px 12px; border-radius: 8px; border: 1px solid rgba(168,85,247,0.2);">
                                📹 <strong><?= $qtdVideosDoProduto ?> vídeo(s) cadastrado(s)</strong> disponível(is) para este produto!
                            </div>
                        <?php else: ?>
                            <div style="margin-top: 8px; font-size: 0.78rem; color: #94a3b8; font-weight: 500;">
                                ℹ️ Este produto ainda não possui vídeos enviados na Aba Básico.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 7. Ajuste de Duração e Proporção para Vídeos do Produto -->
                    <div class="mb-4" id="box-ajuste-video">
                        <label class="form-label-custom">7. Ajuste Automático do Vídeo do Produto</label>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div>
                                <span style="font-size: 0.78rem; color: #94a3b8; font-weight: 600; display: block; margin-bottom: 4px;">Se o vídeo for maior que a duração selecionada:</span>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                    <label class="mb-0">
                                        <input type="radio" name="video_ajuste_duracao" value="trim" checked class="d-none">
                                        <div class="color-pill-card">✂️ Corte Inteligente (Trim)</div>
                                    </label>
                                    <label class="mb-0">
                                        <input type="radio" name="video_ajuste_duracao" value="speedup" class="d-none">
                                        <div class="color-pill-card">⚡ Acelerar Vídeo (Speedup)</div>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <span style="font-size: 0.78rem; color: #94a3b8; font-weight: 600; display: block; margin-bottom: 4px;">Se o formato do vídeo for diferente (ex: horizontal 16:9 ➔ Stories 9:16):</span>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                    <label class="mb-0">
                                        <input type="radio" name="video_ajuste_proporcao" value="smart_blur" checked class="d-none">
                                        <div class="color-pill-card">🌌 Fundo Desfocado (Smart Blur)</div>
                                    </label>
                                    <label class="mb-0">
                                        <input type="radio" name="video_ajuste_proporcao" value="cover" class="d-none">
                                        <div class="color-pill-card">🖼️ Preencher Tela (Cover Crop)</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="btn-gerar-video" class="btn-generate-video">
                        <span>🎬 Gerar Vídeo Promocional 9:16</span>
                    </button>
                </form>

                <!-- Box de Progresso e Polling -->
                <div id="progress-box" class="progress-box">
                    <div class="spinner-border text-info mb-2" role="status" style="width: 2rem; height: 2rem;">
                        <span class="sr-only">Carregando...</span>
                    </div>
                    <div id="progress-status-text" style="font-weight: 600; color: #38bdf8;">Iniciando solicitação de renderização...</div>
                    <div class="progress-bar-custom">
                        <div id="progress-bar-inner" class="progress-bar-inner"></div>
                    </div>
                    <small id="progress-detail-text" style="color: #94a3b8; font-size: 0.82rem;">O servidor está gerando os frames em segundo plano...</small>
                </div>
            </div>
        </div>

        <!-- Coluna de Prévia do Vídeo (Player 9:16 / 1:1) -->
        <div class="col-lg-6 mb-4">
            <div class="glass-card text-center">
                <label id="lbl-preview-title" class="form-label-custom mb-3">Prévia do Vídeo Promocional (1080x1920)</label>

                <div class="preview-aspect-ratio">
                    <div id="placeholder-preview" class="placeholder-preview">
                        <i class="glyphicon glyphicon-film"></i>
                        <p style="font-size: 0.9rem; margin-bottom: 0;">Selecione o produto e clique em <strong>Gerar Vídeo</strong> para visualizar o resultado final aqui.</p>
                    </div>

                    <video id="video-preview-player" controls autoplay loop style="display: none;">
                        <source src="" type="video/mp4">
                        Seu navegador não suporta a reprodução de vídeos HTML5.
                    </video>
                </div>

                <div id="action-buttons-box" class="action-buttons">
                    <a id="btn-download-video" href="#" download class="btn-action-custom btn-action-download">
                        <span>⬇️ Baixar MP4</span>
                    </a>
                    <button type="button" onclick="abrirDisparoVideoAtual()" class="btn-action-custom btn-action-whatsapp border-0 shadow" style="cursor: pointer;">
                        <span>📱 Disparar WhatsApp / Status</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico de Vídeos Gerados -->
    <div class="mt-4 pt-3 border-top border-secondary" id="container-historico-videos" style="border-color: rgba(255,255,255,0.08) !important;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #cbd5e1; margin: 0;">📹 Vídeos Recentes Gerados</h3>
            <button type="button" onclick="abrirDisparoVideosSelecionados()" class="btn btn-sm" style="border-radius: 10px; font-weight: 700; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border: none; padding: 6px 14px;">
                📱 Disparar Vídeos Selecionados
            </button>
        </div>
        
        <div id="empty-history-msg" style="<?= empty($videosRecentes) ? 'display:block;' : 'display:none;' ?> padding: 24px; text-align: center; color: #64748b; font-size: 0.9rem; background: rgba(30, 41, 59, 0.3); border-radius: 12px; border: 1px dashed #334155; margin-bottom: 15px;">
            <i class="glyphicon glyphicon-film" style="font-size: 1.8rem; display: block; margin-bottom: 8px; color: #475569;"></i>
            Nenhum vídeo gerado ainda para este produto. Selecione as opções acima e clique em <strong>Gerar Vídeos Promocionais</strong>!
        </div>

        <div class="row" id="grid-historico-videos">
            <?php if (!empty($videosRecentes)): ?>
                <?php foreach ($videosRecentes as $vid): ?>
                    <div class="col-md-6 col-lg-4" id="history-col-<?= $vid->id ?>">
                        <div class="history-card" id="history-card-<?= $vid->id ?>">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <?php if ($vid->status === 'concluido'): ?>
                                    <input type="checkbox" class="chk-video-item" value="<?= $vid->id ?>" data-url="<?= Html::encode($vid->getUrlCompleta()) ?>" style="width: 18px; height: 18px; cursor: pointer; accent-color: #38bdf8;">
                                <?php endif; ?>
                                <div>
                                    <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 4px; margin-bottom: 2px;">
                                        <span class="badge" style="background: #0284c7; color: #fff; font-weight: 700;"><?= $vid->duracao ?>s</span>
                                        <?php if ($vid->status === 'concluido'): ?>
                                            <span class="badge" style="background: #334155; color: #38bdf8; border: 1px solid #475569;">💾 <?= $vid->getTamanhoFormatado() ?></span>
                                        <?php endif; ?>
                                        <small style="color: #94a3b8; font-weight: 600;"><?= date('d/m/Y H:i', strtotime($vid->data_criacao)) ?></small>
                                    </div>
                                    <div style="font-size: 0.78rem; color: #38bdf8; font-weight: 600; margin-top: 3px; line-height: 1.35; word-break: break-word;">
                                        <?= Html::encode($vid->getResumoRecursosFormatted()) ?>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <?php if ($vid->status === 'concluido' && $vid->video_url): ?>
                                    <button type="button" class="btn btn-sm btn-outline-info btn-play-history" data-url="<?= Html::encode($vid->getUrlCompleta()) ?>" data-formato="<?= Html::encode($vid->formato ?? 'stories') ?>" style="border-radius: 8px;" title="Assistir Prévia">
                                        ▶
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success" onclick="abrirDisparoVideoUnico('<?= $vid->id ?>', '<?= Html::encode($vid->getUrlCompleta()) ?>')" style="border-radius: 8px; background: #25d366; border: none; font-weight: 700; color: #fff;" title="Disparar no WhatsApp / Status">
                                        📱 Enviar
                                    </button>
                                    <a href="<?= Url::to(['/vendas/produto-video/download', 'id' => $vid->id]) ?>" class="btn btn-sm btn-outline-success" style="border-radius: 8px;" title="Baixar Vídeo MP4">
                                        ⬇️ Baixar
                                    </a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-history" data-id="<?= $vid->id ?>" style="border-radius: 8px;" title="Excluir Vídeo e Liberar Disco">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Decisão de Matriz para Geração de Vídeos (Unico Carrossel vs Vídeo por Cor) -->
<div id="modalDecisaoMatrizVideo" style="display:none; position:fixed; inset:0; z-index:9998; background:rgba(15,23,42,0.85); backdrop-filter:blur(8px); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#1e293b; border:1px solid #334155; color:#fff; border-radius:18px; width:100%; max-width:650px; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px rgba(0,0,0,0.6);">
        <!-- Header -->
        <div style="background:linear-gradient(135deg, #0284c7 0%, #6366f1 100%); padding:18px 24px; display:flex; align-items:center; justify-content:space-between;">
            <div>
                <h3 style="margin:0; font-size:1.2rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:8px;">
                    <span>🎨 Produtos com Matriz Detectados!</span>
                </h3>
                <p style="margin:4px 0 0 0; font-size:0.85rem; color:#e0e7ff;">
                    Um ou mais produtos selecionados possuem variações de cores/modelos. Como deseja gerar?
                </p>
            </div>
            <button onclick="fecharModalDecisaoMatriz()" type="button" style="background:none; border:none; color:#fff; font-size:1.4rem; cursor:pointer; padding:0 6px;">
                ✕
            </button>
        </div>

        <!-- Body -->
        <div style="padding:20px 24px; overflow-y:auto; flex:1;">
            <!-- Opções de Escolha -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:20px;">
                <!-- Opção 1: Vídeo por Cor (Recomendado) -->
                <div class="decision-card-option active" id="card-modo-por-cor" onclick="selecionarModoMatrizModal('por_cor')">
                    <input type="radio" name="modo_matriz_modal" value="por_cor" checked style="margin-top:4px; accent-color:#38bdf8; width:18px; height:18px;">
                    <div>
                        <div style="font-weight:800; font-size:0.95rem; color:#fff; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                            <span>🎨 Vídeo por Cor</span>
                            <span class="badge" style="background:#10b981; color:#fff; font-size:10px;">Recomendado</span>
                        </div>
                        <p style="font-size:0.78rem; color:#94a3b8; margin:4px 0 8px 0; line-height:1.35;">
                            Gera um vídeo individual para cada cor cadastrada do produto (ex: Azul, Branco, Preto).
                        </p>
                        <span id="badge-modal-previsao-cores" class="badge" style="background:#0284c7; color:#fff; font-weight:700; font-size:11px;">
                            Calculando...
                        </span>
                    </div>
                </div>

                <!-- Opção 2: Vídeo Único Carrossel -->
                <div class="decision-card-option" id="card-modo-unico" onclick="selecionarModoMatrizModal('unico')">
                    <input type="radio" name="modo_matriz_modal" value="unico" style="margin-top:4px; accent-color:#38bdf8; width:18px; height:18px;">
                    <div>
                        <div style="font-weight:800; font-size:0.95rem; color:#fff;">
                            <span>🎴 Vídeo Único Carrossel</span>
                        </div>
                        <p style="font-size:0.78rem; color:#94a3b8; margin:4px 0 8px 0; line-height:1.35;">
                            Compila as fotos de todas as cores da coleção em 1 único vídeo por produto.
                        </p>
                        <span id="badge-modal-previsao-unico" class="badge" style="background:#334155; color:#38bdf8; border:1px solid #475569; font-weight:700; font-size:11px;">
                            Calculando...
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tabela de Prévia dos Vídeos e Músicas -->
            <div style="margin-top:14px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <span style="font-size:0.82rem; font-weight:700; text-transform:uppercase; color:#94a3b8; letter-spacing:0.05em;">
                        📋 Prévia do Lote a Ser Gerado (<span id="lbl-total-previa-itens">0</span> vídeos)
                    </span>
                    <span id="lbl-info-musicas-distribuicao" style="font-size:0.75rem; color:#38bdf8; font-weight:600;">
                        🎵 1 música atribuída
                    </span>
                </div>
                <div style="background:rgba(15,23,42,0.8); border:1px solid #334155; border-radius:10px; max-height:190px; overflow-y:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.8rem; color:#cbd5e1;">
                        <thead>
                            <tr style="border-bottom:1px solid #334155; background:rgba(30,41,59,0.8); color:#94a3b8; font-size:0.75rem;">
                                <th style="padding:8px 10px; text-align:left;">#</th>
                                <th style="padding:8px 10px; text-align:left;">Produto</th>
                                <th style="padding:8px 10px; text-align:left;">Cor / Modo</th>
                                <th style="padding:8px 10px; text-align:center;">Fotos</th>
                                <th style="padding:8px 10px; text-align:left;">Trilha Sonora</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-previa-lote-matriz">
                            <!-- Preenchido via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="background:rgba(15,23,42,0.9); border-top:1px solid #334155; padding:16px 24px; display:flex; justify-content:flex-end; gap:12px; align-items:center;">
            <button type="button" onclick="fecharModalDecisaoMatriz()" class="btn btn-outline-light btn-sm" style="border-color:#475569; color:#cbd5e1; border-radius:8px;">
                Cancelar
            </button>
            <button type="button" id="btn-confirmar-lote-matriz" class="btn btn-success btn-sm" style="background:linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%); border:none; font-weight:700; padding:8px 18px; border-radius:8px; display:flex; align-items:center; gap:8px;">
                <span>🚀 Confirmar e Iniciar Lote (<span id="lbl-btn-qtd-confirmar">0</span> Vídeos)</span>
            </button>
        </div>
    </div>
</div>

<?= $this->render('_modal_disparo_video') ?>

<script>
// =========================================================================
// VARIÁVEIS GLOBAIS DE ESTADO DO STUDIO DE VÍDEOS
// =========================================================================
let duracaoSelecionada = 5;
let videoRecemGeradoId = null;
let videoRecemGeradoUrl = null;
let currentPreviewAudioBtn = null;

// Estado para Lote & Decisão de Matriz
let dadosVerificacaoMatrizGlobal = null;
let loteProdIdsGlobal = [];
let loteTrilhasGlobal = [];
let modoMatrizEscolhido = 'por_cor';

document.addEventListener('DOMContentLoaded', function() {
    const btnGerar = document.getElementById('btn-gerar-video');
    const audioPreviewElem = document.getElementById('audio-preview-element');
    const progressBox = document.getElementById('progress-box');
    const progressBarInner = document.getElementById('progress-bar-inner');
    const progressStatusText = document.getElementById('progress-status-text');
    const progressDetailText = document.getElementById('progress-detail-text');
    const placeholderPreview = document.getElementById('placeholder-preview');
    const videoPlayer = document.getElementById('video-preview-player');
    const actionButtonsBox = document.getElementById('action-buttons-box');
    const btnDownload = document.getElementById('btn-download-video');

    // -------------------------------------------------------------
    // 1. GESTÃO DO SELETOR DE PRODUTOS
    // -------------------------------------------------------------
    const filtroProdutos = document.getElementById('filtro-produtos-studio');
    const btnMarcarFotos = document.getElementById('btn-marcar-todos-fotos');
    const btnDesmarcarProdutos = document.getElementById('btn-desmarcar-todos-produtos');
    const lblContagemProdutos = document.getElementById('lbl-contagem-produtos');

    window.atualizarContagemProdutos = function() {
        const marcados = document.querySelectorAll('.chk-produto-item:checked');
        const qtd = marcados.length;
        if (lblContagemProdutos) {
            lblContagemProdutos.innerText = qtd === 1 ? '1 selecionado' : qtd + ' selecionados';
        }
        if (btnGerar) {
            const spanTxt = btnGerar.querySelector('span');
            if (spanTxt) {
                spanTxt.innerText = qtd > 1 ? `🎬 Gerar Vídeos Promocionais (${qtd} Produtos)` : '🎬 Gerar Vídeo Promocional 9:16';
            }
        }
    };

    if (filtroProdutos) {
        filtroProdutos.addEventListener('input', function() {
            const termo = this.value.toLowerCase().trim();
            document.querySelectorAll('.product-item-row').forEach(row => {
                const nome = row.getAttribute('data-nome') || '';
                if (!termo || nome.includes(termo)) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    if (btnMarcarFotos) {
        btnMarcarFotos.addEventListener('click', function() {
            document.querySelectorAll('.chk-produto-item').forEach(chk => {
                const fotos = parseInt(chk.getAttribute('data-fotos') || '0');
                if (fotos > 0 && !chk.disabled) {
                    chk.checked = true;
                }
            });
            window.atualizarContagemProdutos();
        });
    }

    if (btnDesmarcarProdutos) {
        btnDesmarcarProdutos.addEventListener('click', function() {
            document.querySelectorAll('.chk-produto-item').forEach(chk => chk.checked = false);
            window.atualizarContagemProdutos();
        });
    }

    document.querySelectorAll('.chk-produto-item').forEach(chk => {
        chk.addEventListener('change', window.atualizarContagemProdutos);
    });
    document.querySelectorAll('.product-item-row').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.btn-set-preview-prod') || e.target.type === 'checkbox') return;
            const chk = this.querySelector('.chk-produto-item');
            if (chk && !chk.disabled) {
                chk.checked = !chk.checked;
                window.atualizarContagemProdutos();
            }
        });
    });
    window.atualizarContagemProdutos();

    // Botão de prévia rápida do produto (recarrega studio focando no produto selecionado)
    document.querySelectorAll('.btn-set-preview-prod').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const prodId = this.getAttribute('data-id');
            if (!prodId) return;

            // Mantém os produtos selecionados atuais na URL
            const marcados = Array.from(document.querySelectorAll('.chk-produto-item:checked')).map(c => c.value);
            if (!marcados.includes(prodId)) {
                marcados.unshift(prodId);
            }

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('produto_id', prodId);
            if (marcados.length > 0) {
                currentUrl.searchParams.set('produto_ids', marcados.join(','));
            }
            window.location.href = currentUrl.toString();
        });
    });

    // -------------------------------------------------------------
    // 2. GESTÃO DAS TRILHAS SONORAS
    // -------------------------------------------------------------
    const btnMarcarMusicas = document.getElementById('btn-marcar-todas-musicas');
    const btnApenasUmaMusica = document.getElementById('btn-selecionar-uma-musica');
    const lblContagemTrilhas = document.getElementById('lbl-contagem-trilhas');

    window.atualizarContagemTrilhas = function() {
        const marcadas = document.querySelectorAll('.chk-trilha-item:checked');
        const qtd = marcadas.length;
        if (lblContagemTrilhas) {
            lblContagemTrilhas.innerText = qtd === 1 ? '1 selecionada' : qtd + ' selecionadas';
        }
        document.querySelectorAll('.chk-trilha-item').forEach(chk => {
            const card = chk.closest('.trilha-card');
            if (card) {
                if (chk.checked) card.classList.add('is-checked');
                else card.classList.remove('is-checked');
            }
        });
    };

    if (btnMarcarMusicas) {
        btnMarcarMusicas.addEventListener('click', function() {
            document.querySelectorAll('.chk-trilha-item').forEach(chk => chk.checked = true);
            window.atualizarContagemTrilhas();
        });
    }

    if (btnApenasUmaMusica) {
        btnApenasUmaMusica.addEventListener('click', function() {
            let primeiraMarcada = false;
            document.querySelectorAll('.chk-trilha-item').forEach(chk => {
                if (!primeiraMarcada) {
                    chk.checked = true;
                    primeiraMarcada = true;
                } else {
                    chk.checked = false;
                }
            });
            window.atualizarContagemTrilhas();
        });
    }

    document.querySelectorAll('.chk-trilha-item').forEach(chk => {
        chk.addEventListener('change', window.atualizarContagemTrilhas);
    });
    document.querySelectorAll('.trilha-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.btn-preview-track') || e.target.type === 'checkbox') return;
            const chk = this.querySelector('.chk-trilha-item');
            if (chk) {
                chk.checked = !chk.checked;
                window.atualizarContagemTrilhas();
            }
        });
    });
    window.atualizarContagemTrilhas();

    // Botões de prévia de áudio
    function conectarBotoesAudioPreview() {
        document.querySelectorAll('.btn-preview-track').forEach(btn => {
            btn.onclick = function() {
                const audioUrl = this.getAttribute('data-url');
                if (!audioUrl || !audioPreviewElem) return;

                if (currentPreviewAudioBtn === this && !audioPreviewElem.paused) {
                    audioPreviewElem.pause();
                    this.innerHTML = '🔊 Ouvir';
                    currentPreviewAudioBtn = null;
                    return;
                }

                if (currentPreviewAudioBtn && currentPreviewAudioBtn !== this) {
                    currentPreviewAudioBtn.innerHTML = '🔊 Ouvir';
                }

                audioPreviewElem.src = audioUrl;
                audioPreviewElem.play().then(() => {
                    this.innerHTML = '⏸️ Pausar';
                    currentPreviewAudioBtn = this;
                }).catch(e => {
                    alert('Não foi possível reproduzir a prévia: ' + e.message);
                });
            };
        });
    }
    conectarBotoesAudioPreview();

    if (audioPreviewElem) {
        audioPreviewElem.addEventListener('ended', function() {
            if (currentPreviewAudioBtn) {
                currentPreviewAudioBtn.innerHTML = '🔊 Ouvir';
                currentPreviewAudioBtn = null;
            }
        });
    }

    // -------------------------------------------------------------
    // 3. SELEÇÃO DE DURAÇÃO & FORMATO
    // -------------------------------------------------------------
    document.querySelectorAll('.duration-pill').forEach(pill => {
        pill.addEventListener('click', function() {
            document.querySelectorAll('.duration-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            duracaoSelecionada = parseInt(this.getAttribute('data-duracao')) || 5;

            const lblRitmo = document.getElementById('lbl-ritmo-texto');
            if (lblRitmo) {
                const infoMap = {
                    5: 'Ritmo Confortável: ~2.5s por foto (Máx: 2 fotos para 5s)',
                    10: 'Ritmo Confortável: ~3.3s por foto (Máx: 3 fotos para 10s)',
                    15: 'Ritmo Confortável: ~3.75s por foto (Máx: 4 fotos para 15s)',
                    30: 'Ritmo Confortável: ~3.75s por foto (Máx: 8 fotos para 30s)',
                    60: 'Ritmo Confortável: ~5.0s por foto (Máx: 12 fotos para 60s)'
                };
                lblRitmo.innerText = infoMap[duracaoSelecionada] || 'Ritmo Confortável';
            }
        });
    });

    document.querySelectorAll('input[name="video_formato"]').forEach(radio => {
        radio.addEventListener('change', function() {
            ajustarTelaPreview(this.value);
        });
    });

    // -------------------------------------------------------------
    // 4. HISTÓRICO: PLAY & EXCLUIR
    // -------------------------------------------------------------
    conectarEventosCardsHistorico();

    // -------------------------------------------------------------
    // 5. CLIQUE NO BOTÃO GERAR VÍDEOS (FLUXO DO LOTE & MATRIZ)
    // -------------------------------------------------------------
    if (btnGerar) {
        btnGerar.addEventListener('click', function() {
            const marcados = Array.from(document.querySelectorAll('.chk-produto-item:checked'))
                .filter(chk => parseInt(chk.getAttribute('data-fotos') || '0') > 0);

            if (marcados.length === 0) {
                alert('Por favor, selecione ao menos um produto com fotos cadastradas para gerar os vídeos.');
                return;
            }

            const prodIds = marcados.map(c => c.value);

            // Coleta as trilhas sonoras selecionadas
            let trilhasSelecionadas = Array.from(document.querySelectorAll('.chk-trilha-item:checked'))
                .map(c => c.value);

            if (trilhasSelecionadas.length === 0) {
                const primeira = document.querySelector('.chk-trilha-item');
                if (primeira) {
                    primeira.checked = true;
                    trilhasSelecionadas = [primeira.value];
                    window.atualizarContagemTrilhas();
                }
            }

            btnGerar.disabled = true;
            const originalText = btnGerar.innerHTML;
            btnGerar.innerHTML = '<span>🔍 Analisando variações de matriz...</span>';

            // Chamada de verificação de matriz no backend
            fetch('<?= Url::to(['/vendas/produto-video/verificar-matriz']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    produto_ids: prodIds,
                    '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
                })
            })
            .then(res => res.json())
            .then(data => {
                btnGerar.disabled = false;
                btnGerar.innerHTML = originalText;

                if (!data.success) {
                    alert('Erro: ' + (data.message || 'Falha ao verificar matriz dos produtos.'));
                    return;
                }

                if (data.tem_matriz) {
                    // Abre o modal interativo de decisão
                    abrirModalDecisaoMatriz(data, prodIds, trilhasSelecionadas);
                } else {
                    // Nenhum produto possui matriz de variação: gera direto em modo 'unico'
                    iniciarExecucaoLote(prodIds, 'unico', trilhasSelecionadas);
                }
            })
            .catch(err => {
                btnGerar.disabled = false;
                btnGerar.innerHTML = originalText;
                alert('Erro de comunicação com o servidor: ' + err.message);
            });
        });
    }

    // Botão de confirmação dentro do modal de decisão da matriz
    const btnConfirmarMatriz = document.getElementById('btn-confirmar-lote-matriz');
    if (btnConfirmarMatriz) {
        btnConfirmarMatriz.addEventListener('click', function() {
            fecharModalDecisaoMatriz();
            iniciarExecucaoLote(loteProdIdsGlobal, modoMatrizEscolhido, loteTrilhasGlobal);
        });
    }
});

// =========================================================================
// FUNÇÕES DO MODAL DE DECISÃO DA MATRIZ (CARROSSEL ÚNICO VS VÍDEOS POR COR)
// =========================================================================
function selecionarModoMatrizModal(modo) {
    modoMatrizEscolhido = modo;
    const cardPorCor = document.getElementById('card-modo-por-cor');
    const cardUnico = document.getElementById('card-modo-unico');

    if (modo === 'por_cor') {
        if (cardPorCor) cardPorCor.classList.add('active');
        if (cardUnico) cardUnico.classList.remove('active');
        const r1 = document.querySelector('input[name="modo_matriz_modal"][value="por_cor"]');
        if (r1) r1.checked = true;
    } else {
        if (cardPorCor) cardPorCor.classList.remove('active');
        if (cardUnico) cardUnico.classList.add('active');
        const r2 = document.querySelector('input[name="modo_matriz_modal"][value="unico"]');
        if (r2) r2.checked = true;
    }

    renderizarTabelaPreviaMatriz();
}

function abrirModalDecisaoMatriz(dados, prodIds, trilhas) {
    dadosVerificacaoMatrizGlobal = dados;
    loteProdIdsGlobal = prodIds;
    loteTrilhasGlobal = trilhas && trilhas.length > 0 ? trilhas : ['promo_bg.mp3'];

    const modal = document.getElementById('modalDecisaoMatrizVideo');
    if (!modal) return;

    // Atualiza badges com números reais previstos
    const bCores = document.getElementById('badge-modal-previsao-cores');
    if (bCores) bCores.innerText = `${dados.previsao_modo_cores} vídeos previstos`;

    const bUnico = document.getElementById('badge-modal-previsao-unico');
    if (bUnico) bUnico.innerText = `${dados.previsao_modo_unico} vídeos previstos`;

    selecionarModoMatrizModal('por_cor');
    modal.style.display = 'flex';
}

function fecharModalDecisaoMatriz() {
    const modal = document.getElementById('modalDecisaoMatrizVideo');
    if (modal) modal.style.display = 'none';
}

function renderizarTabelaPreviaMatriz() {
    const tbody = document.getElementById('tbody-previa-lote-matriz');
    if (!tbody || !dadosVerificacaoMatrizGlobal) return;

    const itensPrevia = [];
    const prods = dadosVerificacaoMatrizGlobal.detalhes_produtos || [];

    prods.forEach(p => {
        if (modoMatrizEscolhido === 'por_cor' && p.cores && p.cores.length > 0) {
            p.cores.forEach(c => {
                itensPrevia.push({
                    produto_nome: p.nome,
                    modo_label: `Cor: ${c.cor}`,
                    fotos_qtd: c.fotos_count,
                    badge_cor: '#8b5cf6'
                });
            });
        } else {
            itensPrevia.push({
                produto_nome: p.nome,
                modo_label: p.tem_matriz ? 'Carrossel (Todas as Cores)' : 'Vídeo Único',
                fotos_qtd: p.total_fotos,
                badge_cor: '#0284c7'
            });
        }
    });

    const totalItens = itensPrevia.length;
    const totalTrilhas = loteTrilhasGlobal.length;

    // Atualiza contadores
    const lblTotal = document.getElementById('lbl-total-previa-itens');
    if (lblTotal) lblTotal.innerText = totalItens;

    const lblBtnQtd = document.getElementById('lbl-btn-qtd-confirmar');
    if (lblBtnQtd) lblBtnQtd.innerText = totalItens;

    const lblInfoMusica = document.getElementById('lbl-info-musicas-distribuicao');
    if (lblInfoMusica) {
        if (totalTrilhas <= 1) {
            lblInfoMusica.innerHTML = `🎵 <strong>1 música</strong> para todos os ${totalItens} vídeos`;
        } else {
            lblInfoMusica.innerHTML = `🔄 <strong>${totalTrilhas} músicas</strong> em rotação (Round-Robin)`;
        }
    }

    // Renderiza linhas da tabela
    tbody.innerHTML = itensPrevia.map((item, idx) => {
        // Regra Round-Robin de músicas
        const trilhaArquivo = totalTrilhas > 0 ? loteTrilhasGlobal[idx % totalTrilhas] : 'Padrão';
        // Tenta achar nome amigável
        const chk = document.querySelector(`.chk-trilha-item[value="${trilhaArquivo}"]`);
        const trilhaNome = chk ? chk.getAttribute('data-nome') : trilhaArquivo;

        return `
            <tr style="border-bottom:1px solid rgba(255,255,255,0.05); transition: background 0.15s;" onmouseover="this.style.background='rgba(30,41,59,0.5)'" onmouseout="this.style.background='transparent'">
                <td style="padding:7px 10px; color:#94a3b8; font-weight:700;">#${idx + 1}</td>
                <td style="padding:7px 10px; font-weight:600; color:#f8fafc; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    ${item.produto_nome}
                </td>
                <td style="padding:7px 10px;">
                    <span class="badge" style="background:${item.badge_cor}; color:#fff; font-size:10px; font-weight:700;">
                        ${item.modo_label}
                    </span>
                </td>
                <td style="padding:7px 10px; text-align:center; color:#38bdf8; font-weight:700;">
                    📸 ${item.fotos_qtd}
                </td>
                <td style="padding:7px 10px; color:#34d399; font-weight:600; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    🎵 ${trilhaNome}
                </td>
            </tr>
        `;
    }).join('');
}

// =========================================================================
// EXECUÇÃO SEQUENCIAL EM LOTE (BATCH RENDERER)
// =========================================================================
function iniciarExecucaoLote(prodIds, modoMatriz, trilhasSelecionadas) {
    const btnGerar = document.getElementById('btn-gerar-video');
    const progressBox = document.getElementById('progress-box');
    const progressBarInner = document.getElementById('progress-bar-inner');
    const progressStatusText = document.getElementById('progress-status-text');
    const progressDetailText = document.getElementById('progress-detail-text');
    const actionButtonsBox = document.getElementById('action-buttons-box');

    btnGerar.disabled = true;
    progressBox.style.display = 'block';
    if (actionButtonsBox) actionButtonsBox.style.display = 'none';

    progressBarInner.style.width = '5%';
    progressStatusText.innerText = 'Preparando lote de renderização no servidor...';
    progressDetailText.innerText = 'Calculando ordem de distribuição e faixas de áudio...';

    const formatoVal = document.querySelector('input[name="video_formato"]:checked')?.value || 'stories';
    const templateVal = document.querySelector('input[name="video_template"]:checked')?.value || 'modern_dark';
    const corVal = document.querySelector('input[name="video_cor"]:checked')?.value || 'dark';
    const fundoVal = document.querySelector('input[name="video_fundo"]:checked')?.value || 'gradient';
    const efeitoVal = document.querySelector('input[name="video_efeito_visual"]:checked')?.value || 'none';
    const modoComposicaoVal = document.querySelector('input[name="video_modo_composicao"]:checked')?.value || 'hibrido';
    const ajusteDuracaoVal = document.querySelector('input[name="video_ajuste_duracao"]:checked')?.value || 'trim';
    const ajusteProporcaoVal = document.querySelector('input[name="video_ajuste_proporcao"]:checked')?.value || 'smart_blur';

    const payload = {
        produto_ids: prodIds,
        modo_matriz: modoMatriz,
        trilhas: trilhasSelecionadas,
        duracao: duracaoSelecionada,
        formato: formatoVal,
        template: templateVal,
        corTema: corVal,
        fundoEstilo: fundoVal,
        efeitoVisual: efeitoVal,
        modoComposicao: modoComposicaoVal,
        ajusteDuracao: ajusteDuracaoVal,
        ajusteProporcao: ajusteProporcaoVal,
        '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
    };

    fetch('<?= Url::to(['/vendas/produto-video/preparar-lote']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.fila || data.fila.length === 0) {
            btnGerar.disabled = false;
            progressBox.style.display = 'none';
            alert('Erro: ' + (data.message || 'Nenhum item válido para renderizar.'));
            return;
        }

        executarFilaLoteSequencial(data.fila);
    })
    .catch(err => {
        btnGerar.disabled = false;
        progressBox.style.display = 'none';
        alert('Erro ao preparar lote: ' + err.message);
    });
}

async function executarFilaLoteSequencial(fila) {
    const btnGerar = document.getElementById('btn-gerar-video');
    const progressBox = document.getElementById('progress-box');
    const progressBarInner = document.getElementById('progress-bar-inner');
    const progressStatusText = document.getElementById('progress-status-text');
    const progressDetailText = document.getElementById('progress-detail-text');

    const total = fila.length;
    let concluidos = 0;
    let erros = 0;

    for (let i = 0; i < total; i++) {
        const item = fila[i];
        const numItem = i + 1;
        const perc = Math.round((i / total) * 100);

        progressBarInner.style.width = Math.max(perc, 10) + '%';
        progressStatusText.innerText = `🎬 Renderizando Vídeo ${numItem} de ${total}: ${item.titulo_preview} ${item.cor ? '(' + item.cor + ')' : ''}`;
        progressDetailText.innerText = `🎵 Trilha: ${item.trilha_sonora} | ⏱️ Renderizando frames via Puppeteer + FFmpeg...`;

        try {
            const res = await fetch('<?= Url::to(['/vendas/produto-video/gerar-item-lote']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    item: item,
                    '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
                })
            });

            const data = await res.json();

            if (data.success && data.video) {
                concluidos++;
                // Mostra no player de prévia
                mostrarVideoConcluido(data.video.url, data.video.formato);

                // Adiciona dinamicamente ao grid de histórico
                adicionarVideoAoHistoricoGrid(data.video);

                // Atualiza cota de armazenamento
                if (data.stats) {
                    atualizarBarraCota(data.stats);
                }
            } else {
                erros++;
                console.error(`Erro ao gerar item ${numItem}:`, data.message);
            }
        } catch (e) {
            erros++;
            console.error(`Falha de conexão no item ${numItem}:`, e);
        }
    }

    progressBarInner.style.width = '100%';
    if (erros === 0) {
        progressStatusText.innerText = `🎉 Lote de ${concluidos} vídeo(s) concluído com sucesso!`;
        progressDetailText.innerText = 'Todos os vídeos foram gerados e adicionados ao histórico abaixo.';
    } else {
        progressStatusText.innerText = `⚠️ Lote finalizado: ${concluidos} gerados, ${erros} falhas.`;
        progressDetailText.innerText = 'Verifique os vídeos concluídos no histórico abaixo.';
    }

    btnGerar.disabled = false;
    window.atualizarContagemProdutos();

    setTimeout(() => {
        progressBox.style.display = 'none';
    }, 4500);
}

// =========================================================================
// FUNÇÕES AUXILIARES DE HISTÓRICO, PLAYER E COTA
// =========================================================================
function adicionarVideoAoHistoricoGrid(video) {
    const grid = document.getElementById('grid-historico-videos');
    const msgEmpty = document.getElementById('empty-history-msg');
    if (msgEmpty) msgEmpty.style.display = 'none';
    if (!grid) return;

    // Cria elemento da coluna
    const col = document.createElement('div');
    col.className = 'col-md-6 col-lg-4';
    col.id = 'history-col-' + video.id;
    col.style.animation = 'fadeInUp 0.5s ease';

    col.innerHTML = `
        <div class="history-card" id="history-card-${video.id}">
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" class="chk-video-item" value="${video.id}" data-url="${video.url}" style="width: 18px; height: 18px; cursor: pointer; accent-color: #38bdf8;">
                <div>
                    <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 4px; margin-bottom: 2px;">
                        <span class="badge" style="background: #0284c7; color: #fff; font-weight: 700;">${video.duracao}s</span>
                        <span class="badge" style="background: #334155; color: #38bdf8; border: 1px solid #475569;">💾 ${video.tamanho_formatado}</span>
                        <small style="color: #34d399; font-weight: 700;">Recém-gerado</small>
                    </div>
                    <div style="font-size: 0.78rem; color: #38bdf8; font-weight: 600; margin-top: 3px; line-height: 1.35; word-break: break-word;">
                        ${video.resumo}
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 6px; align-items: center;">
                <button type="button" class="btn btn-sm btn-outline-info btn-play-history" data-url="${video.url}" data-formato="${video.formato || 'stories'}" style="border-radius: 8px;" title="Assistir Prévia">
                    ▶
                </button>
                <button type="button" class="btn btn-sm btn-success" onclick="abrirDisparoVideoUnico('${video.id}', '${video.url}')" style="border-radius: 8px; background: #25d366; border: none; font-weight: 700; color: #fff;" title="Disparar no WhatsApp / Status">
                    📱 Enviar
                </button>
                <a href="${video.download_url}" class="btn btn-sm btn-outline-success" style="border-radius: 8px;" title="Baixar Vídeo MP4">
                    ⬇️ Baixar
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-history" data-id="${video.id}" style="border-radius: 8px;" title="Excluir Vídeo e Liberar Disco">
                    🗑️
                </button>
            </div>
        </div>
    `;

    grid.insertBefore(col, grid.firstChild);

    // Conectar eventos do card recém-adicionado
    col.querySelector('.btn-play-history')?.addEventListener('click', function() {
        mostrarVideoConcluido(this.getAttribute('data-url'), this.getAttribute('data-formato') || 'stories');
    });
    col.querySelector('.btn-delete-history')?.addEventListener('click', function() {
        excluirVideoHistorico(this.getAttribute('data-id'));
    });
}

function conectarEventosCardsHistorico() {
    document.querySelectorAll('.btn-play-history').forEach(btn => {
        btn.onclick = function() {
            const url = this.getAttribute('data-url');
            const formato = this.getAttribute('data-formato') || 'stories';
            if (url) mostrarVideoConcluido(url, formato);
        };
    });

    document.querySelectorAll('.btn-delete-history').forEach(btn => {
        btn.onclick = function() {
            excluirVideoHistorico(this.getAttribute('data-id'));
        };
    });
}

function excluirVideoHistorico(videoId) {
    if (!videoId) return;
    if (!confirm('Deseja realmente excluir este vídeo? O arquivo MP4 será removido permanentemente do servidor para liberar espaço em disco.')) {
        return;
    }

    fetch('<?= Url::to(['/vendas/produto-video/delete']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'id=' + encodeURIComponent(videoId)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const col = document.getElementById('history-col-' + videoId);
            if (col) {
                col.style.transition = 'all 0.3s ease';
                col.style.opacity = '0';
                col.style.transform = 'scale(0.8)';
                setTimeout(() => col.remove(), 300);
            }
            if (data.stats) {
                atualizarBarraCota(data.stats);
            }
        } else {
            alert('Erro ao excluir vídeo: ' + (data.message || 'Erro desconhecido.'));
        }
    })
    .catch(err => alert('Erro de conexão ao excluir vídeo: ' + err.message));
}

function mostrarVideoConcluido(url, formato) {
    const videoPlayer = document.getElementById('video-preview-player');
    const placeholderPreview = document.getElementById('placeholder-preview');
    const actionButtonsBox = document.getElementById('action-buttons-box');
    const btnDownload = document.getElementById('btn-download-video');

    if (!formato) {
        formato = document.querySelector('input[name="video_formato"]:checked')?.value || 'stories';
    }
    ajustarTelaPreview(formato);

    if (placeholderPreview) placeholderPreview.style.display = 'none';
    if (videoPlayer) {
        videoPlayer.style.display = 'block';
        videoPlayer.src = url;
        videoPlayer.load();
        videoPlayer.play().catch(e => console.log('Autoplay não permitido:', e));
    }

    if (btnDownload) btnDownload.href = url;
    videoRecemGeradoUrl = url;
    if (actionButtonsBox) actionButtonsBox.style.display = 'flex';
}

function ajustarTelaPreview(formato) {
    const titleElem = document.getElementById('lbl-preview-title');
    const aspectBox = document.querySelector('.preview-aspect-ratio');
    if (formato === 'feed' || formato === '1:1') {
        if (titleElem) titleElem.innerText = 'Prévia do Vídeo Promocional (1080x1080)';
        if (aspectBox) aspectBox.classList.add('is-feed');
    } else {
        if (titleElem) titleElem.innerText = 'Prévia do Vídeo Promocional (1080x1920)';
        if (aspectBox) aspectBox.classList.remove('is-feed');
    }
}

function atualizarBarraCota(stats) {
    if (!stats) return;
    const usoElem = document.getElementById('lbl-uso-mb');
    const limiteElem = document.getElementById('lbl-limite-mb');
    const percElem = document.getElementById('lbl-percentual');
    const progressBar = document.getElementById('bar-cota-progresso');
    const alertaElem = document.getElementById('alerta-cota-excedida');
    const btnGerar = document.getElementById('btn-gerar-video');

    if (usoElem) usoElem.innerText = stats.usado_mb + ' MB';
    if (limiteElem) limiteElem.innerText = stats.limite_mb + ' MB';
    if (percElem) {
        percElem.innerText = stats.percentual + '% utilizado';
        percElem.style.background = stats.excedido ? '#ef4444' : (stats.percentual > 80 ? '#f59e0b' : '#10b981');
    }
    if (progressBar) {
        progressBar.style.width = stats.percentual + '%';
        progressBar.style.backgroundColor = stats.excedido ? '#ef4444' : (stats.percentual > 80 ? '#f59e0b' : '#3b82f6');
    }
    if (alertaElem) {
        alertaElem.style.display = stats.excedido ? 'block' : 'none';
    }

    if (btnGerar) {
        if (stats.excedido) {
            btnGerar.disabled = true;
            btnGerar.style.opacity = '0.5';
            btnGerar.style.cursor = 'not-allowed';
        } else {
            btnGerar.disabled = false;
            btnGerar.style.opacity = '1';
            btnGerar.style.cursor = 'pointer';
        }
    }
}

function abrirModalStudioUpload() {
    document.getElementById('modalStudioUploadAudio').style.display = 'flex';
}
function fecharModalStudioUpload() {
    document.getElementById('modalStudioUploadAudio').style.display = 'none';
    document.getElementById('upload-studio-msg').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const formUpload = document.getElementById('formStudioUploadAudio');
    const msgDiv = document.getElementById('upload-studio-msg');
    const btnSubmit = document.getElementById('btn-submit-studio-upload');

    if (formUpload) {
        formUpload.addEventListener('submit', function(e) {
            e.preventDefault();
            btnSubmit.disabled = true;
            msgDiv.style.display = 'block';
            msgDiv.style.color = '#38bdf8';
            msgDiv.innerText = 'Enviando arquivo de áudio...';

            const formData = new FormData(formUpload);

            fetch('<?= Url::to(['/vendas/trilha-sonora/upload']) ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-Token': '<?= Yii::$app->request->getCsrfToken() ?>'
                },
                body: formData
            })
            .then(async res => {
                if (!res.ok) {
                    const textErr = await res.text();
                    console.error('Upload Error Server Response:', res.status, textErr);
                    throw new Error(res.status === 400 ? 'Validação de formulário inválida. Verifique o arquivo.' : ('Erro no servidor (' + res.status + ')'));
                }
                return res.json();
            })
            .then(data => {
                btnSubmit.disabled = false;
                if (data.success && data.trilha) {
                    msgDiv.style.color = '#34d399';
                    msgDiv.innerText = '✅ ' + data.message;

                    // Adiciona também o novo card à lista de trilhas do Studio
                    const listaTrilhas = document.getElementById('lista-trilhas-studio');
                    if (listaTrilhas) {
                        const card = document.createElement('div');
                        card.className = 'trilha-card is-checked';
                        card.style.marginBottom = '6px';
                        const icone = data.trilha.tipo === 'efeito_especial' ? '🔊 ' : '🎵 ';
                        card.innerHTML = `
                            <label style="display: flex; align-items: center; gap: 10px; margin: 0; cursor: pointer; flex: 1; min-width: 0;">
                                <input type="checkbox" name="chk_trilha_item" class="chk-trilha-item" value="${data.trilha.arquivo}" data-url="${data.trilha.url}" data-nome="${data.trilha.titulo}" checked style="width: 17px; height: 17px; cursor: pointer; accent-color: #10b981;">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 700; font-size: 0.88rem; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        ${icone} ${data.trilha.titulo}
                                    </div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        ${data.trilha.descricao || 'Áudio enviado recentemente'}
                                    </div>
                                </div>
                            </label>
                            <button type="button" class="btn btn-xs btn-outline-info btn-preview-track" data-url="${data.trilha.url}" style="border-radius: 6px; font-size: 11px; padding: 3px 8px; border-color: #334155; color: #38bdf8; white-space: nowrap;">
                                🔊 Ouvir
                            </button>
                        `;
                        listaTrilhas.insertBefore(card, listaTrilhas.firstChild);
                        card.querySelector('.chk-trilha-item').addEventListener('change', window.atualizarContagemTrilhas);
                        card.querySelector('.btn-preview-track').addEventListener('click', function() {
                            const audioUrl = this.getAttribute('data-url');
                            const audioPreviewElem = document.getElementById('audio-preview-element');
                            if (!audioUrl || !audioPreviewElem) return;
                            audioPreviewElem.src = audioUrl;
                            audioPreviewElem.play();
                        });
                        window.atualizarContagemTrilhas();
                    }

                    setTimeout(() => {
                        fecharModalStudioUpload();
                        formUpload.reset();
                    }, 1200);
                } else {
                    msgDiv.style.color = '#f87171';
                    msgDiv.innerText = '❌ ' + (data.message || 'Erro ao realizar upload.');
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                msgDiv.style.color = '#f87171';
                msgDiv.innerText = '❌ Erro de conexão: ' + err.message;
            });
        });
    }
});

// =========================================================================
// LÓGICA DE DISPARO DE VÍDEOS VIA WHATSAPP (EVOLUTION API + ANTI-BAN)
// =========================================================================
let videosSelecionadosParaDisparo = [];
let listaClientesVideoCache = [];
let whatsappVideoConectadoCache = false;
let intervalMonitoramentoVideo = null;

function abrirDisparoVideoAtual() {
    if (!videoRecemGeradoId && !videoRecemGeradoUrl) {
        alert('Gere um vídeo primeiro ou selecione um vídeo no histórico.');
        return;
    }
    abrirModalDisparoVideo([videoRecemGeradoId], [videoRecemGeradoUrl]);
}

function abrirDisparoVideoUnico(videoId, videoUrl) {
    abrirModalDisparoVideo([videoId], [videoUrl]);
}

function abrirDisparoVideosSelecionados() {
    const marcados = document.querySelectorAll('.chk-video-item:checked');
    const ids = Array.from(marcados).map(c => c.value);
    const urls = Array.from(marcados).map(c => c.getAttribute('data-url'));

    if (ids.length === 0) {
        alert('Selecione ao menos um vídeo no histórico.');
        return;
    }

    abrirModalDisparoVideo(ids, urls);
}

function abrirModalDisparoVideo(videoIds = [], videoUrls = []) {
    videosSelecionadosParaDisparo = videoIds;

    const modal = document.getElementById('modalDisparoVideo');
    if (!modal) return;

    document.getElementById('secaoDisparoWhatsappVideo').classList.remove('hidden');
    document.getElementById('secaoProgressoDisparoVideo').classList.add('hidden');
    document.getElementById('btnFecharDisparoVideoConcluido').classList.add('hidden');

    const lblResumo = document.getElementById('lblVideosDisparoResumo');
    if (lblResumo) {
        lblResumo.innerText = videoIds.length === 1 ? '1 vídeo selecionado para envio' : videoIds.length + ' vídeos selecionados para envio';
    }

    const containerThumbs = document.getElementById('containerThumbnailsDisparoVideo');
    if (containerThumbs) {
        containerThumbs.innerHTML = videoUrls.map(url => url ? `<div class="w-10 h-10 rounded-lg bg-slate-800 border border-sky-400/50 flex items-center justify-center text-sky-400 text-xs font-bold shadow">🎬 MP4</div>` : '').join('');
    }

    modal.classList.remove('hidden');

    verificarStatusWhatsappVideo();
    carregarListaClientesVideo();
    setTimeout(calcularResumoEnvioVideo, 200);
}

function fecharModalDisparoVideo() {
    const modal = document.getElementById('modalDisparoVideo');
    if (modal) modal.classList.add('hidden');
}

function verificarStatusWhatsappVideo() {
    const dot = document.getElementById('indicadorDotWhatsappVideo');
    const texto = document.getElementById('textoStatusWhatsappVideo');
    const subtexto = document.getElementById('subtextoStatusWhatsappVideo');
    const btnConectar = document.getElementById('btnConectarWhatsappVideo');

    dot.className = 'w-3.5 h-3.5 rounded-full bg-slate-500 animate-pulse inline-block';
    texto.textContent = 'Verificando Evolution API...';
    subtexto.textContent = 'Consultando status da instância da loja.';
    btnConectar.classList.add('hidden');

    fetch('<?= Url::to(['/vendas/disparo/status-whatsapp']) ?>')
    .then(r => r.json())
    .then(data => {
        if (data.success && data.connected) {
            whatsappVideoConectadoCache = true;
            dot.className = 'w-3.5 h-3.5 rounded-full bg-emerald-500 inline-block shadow';
            texto.textContent = '🟢 WhatsApp Conectado via Evolution API';
            subtexto.textContent = 'Instância: ' + (data.instance_name || 'Ativa') + ' (Pronto para disparos no Status e Mensagens)';
        } else {
            whatsappVideoConectadoCache = false;
            dot.className = 'w-3.5 h-3.5 rounded-full bg-red-500 inline-block shadow';
            texto.textContent = '🔴 WhatsApp Desconectado';
            subtexto.textContent = 'Conecte sua instância da Evolution API antes de disparar via WhatsApp.';
            btnConectar.classList.remove('hidden');
        }
    })
    .catch(err => {
        whatsappVideoConectadoCache = false;
        dot.className = 'w-3.5 h-3.5 rounded-full bg-amber-500 inline-block';
        texto.textContent = '⚠️ Falha ao verificar Evolution API';
        subtexto.textContent = 'Não foi possível consultar o status da conexão.';
    });
}

function carregarListaClientesVideo() {
    const container = document.getElementById('listaClientesVideoContainer');
    fetch('<?= Url::to(['/vendas/disparo/clientes']) ?>')
    .then(r => r.json())
    .then(data => {
        if (data.success && data.clientes) {
            listaClientesVideoCache = data.clientes;
            renderizarListaClientesVideo(listaClientesVideoCache);
        }
    })
    .catch(err => {
        container.innerHTML = '<div class="text-xs text-red-400 text-center py-3">Erro ao carregar clientes.</div>';
    });
}

function renderizarListaClientesVideo(clientes) {
    const container = document.getElementById('listaClientesVideoContainer');
    if (clientes.length === 0) {
        container.innerHTML = '<div class="text-xs text-slate-500 text-center py-3">Nenhum cliente cadastrado.</div>';
        return;
    }

    container.innerHTML = clientes.map(c => {
        const badgeWp = c.tem_whatsapp ? '<span class="px-1.5 py-0.5 bg-emerald-950 text-emerald-300 border border-emerald-800 text-[10px] font-bold rounded">📱 WhatsApp</span>' : '';
        return `
            <label class="flex items-center justify-between p-2 hover:bg-slate-900 rounded-lg transition cursor-pointer border border-transparent hover:border-slate-800">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="cliente_video_chk" value="${c.id}" checked onchange="calcularResumoEnvioVideo()" class="rounded text-sky-500 focus:ring-sky-400 accent-sky-500">
                    <div class="text-xs">
                        <span class="font-bold text-slate-200">${c.nome}</span>
                        <span class="text-slate-400 text-[11px]">(${c.celular || c.telefone || 'Sem tel'})</span>
                    </div>
                </div>
                <div>${badgeWp}</div>
            </label>
        `;
    }).join('');
    calcularResumoEnvioVideo();
}

function filtrarClientesNaTelaVideo(termo) {
    const termoLimpo = termo.toLowerCase().trim();
    if (!termoLimpo) {
        renderizarListaClientesVideo(listaClientesVideoCache);
        return;
    }
    const filtrados = listaClientesVideoCache.filter(c => 
        (c.nome && c.nome.toLowerCase().includes(termoLimpo)) ||
        (c.celular && c.celular.includes(termoLimpo)) ||
        (c.telefone && c.telefone.includes(termoLimpo))
    );
    renderizarListaClientesVideo(filtrados);
}

function alternarTodosClientesVideo() {
    const chks = document.querySelectorAll('input[name="cliente_video_chk"]');
    const algumDesmarcado = Array.from(chks).some(c => !c.checked);
    chks.forEach(c => c.checked = algumDesmarcado);
    document.getElementById('btnToggleTodosClientesVideo').textContent = algumDesmarcado ? 'Desmarcar Todos' : 'Marcar Todos';
    calcularResumoEnvioVideo();
}

function calcularResumoEnvioVideo() {
    const totalVideos = videosSelecionadosParaDisparo.length || 1;
    const qtdClientes = document.querySelectorAll('input[name="cliente_video_chk"]:checked').length;
    const telefonesManuais = document.getElementById('telefones_manuais_video')?.value || '';
    const qtdManuais = (telefonesManuais.match(/\d{10,13}/g) || []).length;
    const totalDestinatarios = qtdClientes + qtdManuais;
    
    const canalWp = document.getElementById('canal_video_whatsapp')?.checked ? 1 : 0;
    const canalStatus = document.getElementById('canal_video_status')?.checked ? 1 : 0;

    const enviosTotais = (totalVideos * totalDestinatarios * canalWp) + (totalVideos * canalStatus);
    
    const elemEstimativa = document.getElementById('lblEstimativaEnvioVideo');
    if (elemEstimativa) {
        elemEstimativa.innerHTML = `📊 <strong>Resumo do Lote:</strong> ${totalVideos} vídeo(s) × ${totalDestinatarios} destinatário(s) = <span class="text-sky-400 font-extrabold font-mono text-xs">${enviosTotais} envio(s)</span> agendados via Evolution API.`;
    }
}

function iniciarDisparoVideoWhatsappExec() {
    if (videosSelecionadosParaDisparo.length === 0) {
        alert('Nenhum vídeo foi selecionado para disparo.');
        return;
    }

    const canais = [];
    if (document.getElementById('canal_video_whatsapp').checked) canais.push('whatsapp');
    if (document.getElementById('canal_video_status').checked) canais.push('status');

    if (canais.length === 0) {
        alert('Selecione pelo menos um canal de envio.');
        return;
    }

    if (!whatsappVideoConectadoCache) {
        if (!confirm('⚠️ Atenção: A instância do WhatsApp da sua loja na Evolution API parece estar DESCONECTADA. Deseja tentar o envio mesmo assim?')) {
            return;
        }
    }

    const clientesIds = Array.from(document.querySelectorAll('input[name="cliente_video_chk"]:checked')).map(c => c.value);
    const telefonesManuais = document.getElementById('telefones_manuais_video').value;
    const mensagemTexto = document.getElementById('disparo_mensagem_texto_video').value;

    const delayVal = parseInt(document.getElementById('antiban_delay_video').value || '10');
    const loteVal = document.getElementById('antiban_lote_video').value || '10_60';
    const parts = loteVal.split('_');
    const loteTamanho = parseInt(parts[0] || '10');
    const pausaLote = parseInt(parts[1] || '60');
    const incluirOptout = document.getElementById('antiban_optout_video').checked;

    const payload = {
        videos_ids: videosSelecionadosParaDisparo,
        canais: canais,
        clientes_ids: clientesIds,
        telefones_manuais: telefonesManuais,
        mensagem_texto: mensagemTexto,
        delay_segundos: delayVal,
        lote_tamanho: loteTamanho,
        pausa_lote_segundos: pausaLote,
        incluir_optout: incluirOptout,
        '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
    };

    document.getElementById('secaoDisparoWhatsappVideo').classList.add('hidden');
    document.getElementById('secaoProgressoDisparoVideo').classList.remove('hidden');

    fetch('<?= Url::to(['/vendas/disparo/criar']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
    })
    .then(async r => {
        const text = await r.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error(text.replace(/<[^>]*>?/gm, '').trim().substring(0, 150) || 'Falha no servidor.');
        }
    })
    .then(data => {
        if (data.success && data.disparo_id) {
            monitorarProgressoVideoDisparo(data.disparo_id);
        } else {
            alert('Erro ao criar disparo de vídeo: ' + (data.message || 'Falha na requisição.'));
            document.getElementById('secaoDisparoWhatsappVideo').classList.remove('hidden');
            document.getElementById('secaoProgressoDisparoVideo').classList.add('hidden');
        }
    })
    .catch(err => {
        alert('Erro de comunicação: ' + err.message);
        document.getElementById('secaoDisparoWhatsappVideo').classList.remove('hidden');
        document.getElementById('secaoProgressoDisparoVideo').classList.add('hidden');
    });
}

function monitorarProgressoVideoDisparo(disparoId) {
    if (intervalMonitoramentoVideo) clearInterval(intervalMonitoramentoVideo);

    const titulo = document.getElementById('tituloStatusDisparoVideo');
    const subtitulo = document.getElementById('subtituloStatusDisparoVideo');
    const barra = document.getElementById('barraProgressoDisparoVideo');
    const lblItens = document.getElementById('lblProgressoItensVideo');
    const lblPerc = document.getElementById('lblProgressoPercentualVideo');
    const btnConcluir = document.getElementById('btnFecharDisparoVideoConcluido');

    function checarStatusVideo() {
        fetch('<?= Url::to(['/vendas/disparo/status']) ?>?id=' + disparoId)
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                const total = data.total_itens !== undefined ? data.total_itens : (data.disparo ? data.disparo.total_itens : 1);
                const enviados = data.itens_enviados !== undefined ? data.itens_enviados : (data.disparo ? data.disparo.total_enviados : 0);
                const erros = data.itens_erro !== undefined ? data.itens_erro : (data.disparo ? data.disparo.total_erros : 0);
                const processados = enviados + erros;
                const status = data.status || (data.disparo ? data.disparo.status : 'processando');
                const perc = data.progresso_percentual !== undefined ? data.progresso_percentual : Math.min(Math.round((processados / (total || 1)) * 100), 100);

                if (barra) barra.style.width = perc + '%';
                if (lblItens) lblItens.innerText = processados + ' / ' + total + ' enviados (' + erros + ' erros)';
                if (lblPerc) lblPerc.innerText = perc + '%';

                if (status === 'concluido' || processados >= total) {
                    if (intervalMonitoramentoVideo) {
                        clearInterval(intervalMonitoramentoVideo);
                        intervalMonitoramentoVideo = null;
                    }
                    if (titulo) titulo.innerText = (erros === 0) ? '✅ Disparo de Vídeos Concluído!' : '⚠️ Disparo Finalizado com Avisos';
                    if (subtitulo) subtitulo.innerText = 'Todos os envios foram processados com sucesso via Evolution API.';
                    if (btnConcluir) btnConcluir.classList.remove('hidden');
                } else if (status === 'erro' || status === 'cancelado') {
                    if (intervalMonitoramentoVideo) {
                        clearInterval(intervalMonitoramentoVideo);
                        intervalMonitoramentoVideo = null;
                    }
                    if (titulo) titulo.innerText = '⚠️ Disparo Finalizado com Erros';
                    if (subtitulo) subtitulo.innerText = 'Ocorreram falhas em alguns envios. Verifique a conexão com a Evolution API.';
                    if (btnConcluir) btnConcluir.classList.remove('hidden');
                }
            }
        })
        .catch(err => console.error('Erro no polling do disparo de vídeo:', err));
    }

    checarStatusVideo();
    intervalMonitoramentoVideo = setInterval(checarStatusVideo, 2000);
}
</script>

<!-- Modal Upload Rápido de Áudio no Studio -->
<div id="modalStudioUploadAudio" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,0.8); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#1e293b; border:1px solid #334155; color:#fff; border-radius:16px; width:100%; max-width:480px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.5);">
        <div style="background:linear-gradient(135deg, #059669 0%, #0d9488 100%); padding:16px 20px; display:flex; align-items:center; justify-content:space-between;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700; color:#fff;">
                🎵 Upload Rápido de Áudio / Efeito Especial
            </h3>
            <button onclick="fecharModalStudioUpload()" type="button" style="background:none; border:none; color:#fff; font-size:1.3rem; cursor:pointer; padding:0 4px;">
                ✕
            </button>
        </div>

        <form id="formStudioUploadAudio" enctype="multipart/form-data" style="padding:20px;">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
            <div style="margin-bottom:14px;">
                <label class="form-label-custom">1. Título do Áudio / Efeito</label>
                <input type="text" name="TrilhaSonora[titulo]" required class="select-custom" placeholder="Ex: Vinheta Promocional Verão">
            </div>

            <div style="margin-bottom:14px;">
                <label class="form-label-custom">2. Tipo de Áudio</label>
                <select name="TrilhaSonora[tipo]" class="select-custom">
                    <option value="musica">🎵 Música de Fundo</option>
                    <option value="efeito_especial">🔊 Efeito Especial / Vinheta (SFX)</option>
                </select>
            </div>

            <div style="margin-bottom:14px;">
                <label class="form-label-custom">3. Descrição / Estilo (Opcional)</label>
                <input type="text" name="TrilhaSonora[descricao]" class="select-custom" placeholder="Ex: Som de impacto ou batida alegre">
            </div>

            <div style="margin-bottom:16px;">
                <label class="form-label-custom">4. Selecione o Arquivo de Áudio</label>
                <input type="file" name="TrilhaSonora[audioFile]" required accept="audio/*,.mp3,.wav,.aac,.m4a,.ogg" class="select-custom" style="padding: 8px 12px;">
                <small style="color:#94a3b8; font-size:0.75rem; margin-top:4px; display:block;">Formatos: .MP3, .WAV, .AAC, .OGG (Máx. 15MB)</small>
            </div>

            <div id="upload-studio-msg" style="display:none; font-weight:600; margin-bottom:12px; font-size:0.85rem;"></div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #334155; padding-top:14px;">
                <button type="button" onclick="fecharModalStudioUpload()" class="btn btn-outline-light btn-sm" style="border-color:#475569; color:#cbd5e1;">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-studio-upload" class="btn btn-success btn-sm" style="background:#10b981; border:none; font-weight:700;">
                    <span>⬆️ Enviar e Selecionar</span>
                </button>
            </div>
        </form>
    </div>
</div>
