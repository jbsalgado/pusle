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
    display: flex;
    gap: 12px;
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
    max-width: 340px;
    height: 604px; /* 9:16 ratio para 340px */
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
}

.preview-aspect-ratio video {
    width: 100%;
    height: 100%;
    object-fit: cover;
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
                    <div class="mb-4">
                        <label class="form-label-custom">1. Selecione o Produto</label>
                        <select id="select-produto" class="select-custom">
                            <?php if (empty($produtos)): ?>
                                <option value="">Nenhum produto cadastrado</option>
                            <?php else: ?>
                                <?php foreach ($produtos as $prod): ?>
                                    <option value="<?= Html::encode($prod->id) ?>" <?= ($produtoSelecionado && $produtoSelecionado->id === $prod->id) ? 'selected' : '' ?>>
                                        <?= Html::encode($prod->nome) ?> — R$ <?= number_format((float)$prod->getPrecoFinal(), 2, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">2. Escolha a Duração do Vídeo</label>
                        <div class="duration-pills">
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
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">3. Estilo e Tema Visual</label>
                        <select id="select-template" class="select-custom">
                            <option value="full_bleed_banner">Foto em Tela Cheia (Banners Topo & Rodapé Destaque)</option>
                            <option value="modern_dark">Dark Moderno (Gradientes Vibrantes & Neon)</option>
                            <option value="vibrant_gradient">Gradiente Vibrante (Laranja & Roxo)</option>
                            <option value="minimal_clean">Clean Minimalista (Fundo Claro Elegante)</option>
                        </select>
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
                    <div class="mb-4">
                        <label class="form-label-custom">4. Escolha a Trilha Sonora ou Efeito Especial</label>
                        <div class="input-group" style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <select id="select-trilha" class="select-custom" style="flex: 1; min-width: 220px;">
                                <optgroup label="🎵 Músicas de Fundo">
                                    <?php foreach ($faixasMusica as $m): ?>
                                        <option value="<?= Html::encode($m['arquivo']) ?>" data-url="<?= Html::encode($m['url']) ?>">
                                            <?= Html::encode($m['nome']) ?> — <?= Html::encode($m['descricao']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php if (!empty($faixasEfeito)): ?>
                                    <optgroup label="🔊 Efeitos Especiais & Vinhetas">
                                        <?php foreach ($faixasEfeito as $m): ?>
                                            <option value="<?= Html::encode($m['arquivo']) ?>" data-url="<?= Html::encode($m['url']) ?>">
                                                <?= Html::encode($m['nome']) ?> — <?= Html::encode($m['descricao']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                            <button type="button" id="btn-preview-audio" class="btn btn-outline-info" style="border-color:#334155; color:#38bdf8; border-radius: 10px; padding: 0 14px; font-weight: 600;" title="Ouvir Prévia do Áudio">
                                🔊 Ouvir
                            </button>
                            <button type="button" onclick="abrirModalStudioUpload()" class="btn btn-outline-success" style="border-color:#10b981; color:#34d399; border-radius: 10px; padding: 0 14px; font-weight: 600;" title="Fazer Upload Rápido de Áudio">
                                ➕ Upload Rápido
                            </button>
                            <a href="<?= \yii\helpers\Url::to(['/vendas/trilha-sonora']) ?>" class="btn btn-outline-secondary" style="border-color:#334155; color:#a855f7; border-radius: 10px; padding: 0 14px; font-weight: 600; text-decoration: none; display: flex; align-items: center; justify-content: center;" title="Gerenciar Músicas de Fundo">
                                🎵 Biblioteca
                            </a>
                        </div>
                        <audio id="audio-preview-element" style="display:none;"></audio>
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

        <!-- Coluna de Prévia do Vídeo (Player 9:16) -->
        <div class="col-lg-6 mb-4">
            <div class="glass-card text-center">
                <label class="form-label-custom mb-3">Prévia do Vídeo Promocional (1080x1920)</label>

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
                    <a id="btn-share-whatsapp" href="#" target="_blank" class="btn-action-custom btn-action-whatsapp">
                        <span>📱 Enviar WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico de Vídeos Gerados -->
    <?php if (!empty($videosRecentes)): ?>
        <div class="mt-4 pt-3 border-top border-secondary" style="border-color: rgba(255,255,255,0.08) !important;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #cbd5e1; margin-bottom: 16px;">📹 Vídeos Recentes Deste Produto</h3>
            <div class="row">
                <?php foreach ($videosRecentes as $vid): ?>
                    <div class="col-md-6 col-lg-4" id="history-col-<?= $vid->id ?>">
                        <div class="history-card" id="history-card-<?= $vid->id ?>">
                            <div>
                                <span class="badge" style="background: #0284c7; color: #fff; margin-right: 6px;"><?= $vid->duracao ?>s</span>
                                <small style="color: #94a3b8;"><?= date('d/m/Y H:i', strtotime($vid->data_criacao)) ?></small>
                                <div style="font-size: 0.85rem; color: #e2e8f0; margin-top: 4px;">
                                    Status: <strong style="color: <?= $vid->status === 'concluido' ? '#34d399' : ($vid->status === 'erro' ? '#f87171' : '#fbbf24') ?>;"><?= strtoupper($vid->status) ?></strong>
                                </div>
                            </div>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <?php if ($vid->status === 'concluido' && $vid->video_url): ?>
                                    <button type="button" class="btn btn-sm btn-outline-info btn-play-history" data-url="<?= Html::encode($vid->getUrlCompleta()) ?>" style="border-radius: 8px;" title="Assistir Prévia">
                                        ▶
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
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let duracaoSelecionada = 5;
    let currentVideoId = null;
    let pollingInterval = null;

    const selectProduto = document.getElementById('select-produto');
    const selectTemplate = document.getElementById('select-template');
    const btnGerar = document.getElementById('btn-gerar-video');
    const selectTrilha = document.getElementById('select-trilha');
    const btnPreviewAudio = document.getElementById('btn-preview-audio');
    const audioPreviewElem = document.getElementById('audio-preview-element');

    const progressBox = document.getElementById('progress-box');
    const progressBarInner = document.getElementById('progress-bar-inner');
    const progressStatusText = document.getElementById('progress-status-text');
    const progressDetailText = document.getElementById('progress-detail-text');
    const placeholderPreview = document.getElementById('placeholder-preview');
    const videoPlayer = document.getElementById('video-preview-player');
    const actionButtonsBox = document.getElementById('action-buttons-box');
    const btnDownload = document.getElementById('btn-download-video');
    const btnWhatsapp = document.getElementById('btn-share-whatsapp');

    // Prévia de Áudio da Trilha Sonora ou Efeito Especial
    if (btnPreviewAudio && selectTrilha && audioPreviewElem) {
        btnPreviewAudio.addEventListener('click', function() {
            const selectedOption = selectTrilha.options[selectTrilha.selectedIndex];
            if (!selectedOption) return;

            const audioUrl = selectedOption.getAttribute('data-url');
            if (!audioUrl) {
                alert('URL de prévia do áudio não encontrada.');
                return;
            }

            if (audioPreviewElem.src !== audioUrl) {
                audioPreviewElem.src = audioUrl;
            }

            if (audioPreviewElem.paused) {
                audioPreviewElem.play().then(() => {
                    btnPreviewAudio.innerHTML = '⏸️ Pausar';
                }).catch(e => {
                    alert('Não foi possível reproduzir a prévia: ' + e.message);
                });
            } else {
                audioPreviewElem.pause();
                btnPreviewAudio.innerHTML = '🔊 Ouvir';
            }
        });

        audioPreviewElem.addEventListener('ended', function() {
            btnPreviewAudio.innerHTML = '🔊 Ouvir';
        });

        selectTrilha.addEventListener('change', function() {
            if (!audioPreviewElem.paused) {
                audioPreviewElem.pause();
                btnPreviewAudio.innerHTML = '🔊 Ouvir';
            }
        });
    }

    // Troca de produto no dropdown
    selectProduto.addEventListener('change', function() {
        const prodId = this.value;
        if (prodId) {
            window.location.href = '<?= Url::to(['/vendas/produto-video/studio']) ?>?produto_id=' + prodId;
        }
    });

    // Seleção das Pills de Duração
    document.querySelectorAll('.duration-pill').forEach(pill => {
        pill.addEventListener('click', function() {
            document.querySelectorAll('.duration-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            duracaoSelecionada = parseInt(this.getAttribute('data-duracao')) || 5;
        });
    });

    // Botões de reproduzir histórico
    document.querySelectorAll('.btn-play-history').forEach(btn => {
        btn.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            if (url) {
                mostrarVideoConcluido(url);
            }
        });
    });

    // Botões de Excluir Histórico (Remoção física no servidor)
    document.querySelectorAll('.btn-delete-history').forEach(btn => {
        btn.addEventListener('click', function() {
            const videoId = this.getAttribute('data-id');
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
        });
    });

    function atualizarBarraCota(stats) {
        if (!stats) return;
        const usoElem = document.getElementById('lbl-uso-mb');
        const limiteElem = document.getElementById('lbl-limite-mb');
        const percElem = document.getElementById('lbl-percentual');
        const progressBar = document.getElementById('bar-cota-progresso');
        const alertaElem = document.getElementById('alerta-cota-excedida');

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

    // Clique no botão Gerar Vídeo
    btnGerar.addEventListener('click', function() {
        const produtoId = selectProduto.value;
        if (!produtoId) {
            alert('Por favor, selecione um produto.');
            return;
        }

        btnGerar.disabled = true;
        progressBox.style.display = 'block';
        actionButtonsBox.style.display = 'none';
        progressBarInner.style.width = '15%';
        progressStatusText.innerText = 'Solicitando geração de vídeo...';
        progressDetailText.innerText = 'Enviando parâmetros para o renderizador local...';

        fetch('<?= Url::to(['/vendas/produto-video/generate']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                produto_id: produtoId,
                duracao: duracaoSelecionada,
                template: selectTemplate.value,
                corTema: 'dark',
                trilhaSonora: selectTrilha ? selectTrilha.value : 'promo_bg.mp3'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.video_id) {
                currentVideoId = data.video_id;
                progressBarInner.style.width = '35%';
                progressStatusText.innerText = 'Vídeo enfileirado! Renderizando frames...';
                progressDetailText.innerText = 'Processando em segundo plano (Puppeteer + FFmpeg)...';

                iniciarPollingStatus(currentVideoId);
            } else {
                tratarErroGeracao(data.message || 'Falha ao enfileirar solicitação.');
            }
        })
        .catch(err => {
            tratarErroGeracao('Erro de comunicação com o servidor: ' + err.message);
        });
    });

    function iniciarPollingStatus(videoId) {
        if (pollingInterval) clearInterval(pollingInterval);

        let tentativas = 0;
        pollingInterval = setInterval(function() {
            tentativas++;

            // Simula progresso visual da barra
            const pctActual = Math.min(35 + (tentativas * 5), 92);
            progressBarInner.style.width = pctActual + '%';

            fetch('<?= Url::to(['/vendas/produto-video/status']) ?>?id=' + videoId)
            .then(res => res.json())
            .then(resData => {
                if (!resData.success) return;

                if (resData.status === 'concluido' && resData.video_url) {
                    clearInterval(pollingInterval);
                    progressBarInner.style.width = '100%';
                    progressStatusText.innerText = '✅ Renderização Concluída!';
                    progressDetailText.innerText = 'Seu vídeo foi gerado com sucesso!';

                    setTimeout(function() {
                        progressBox.style.display = 'none';
                        btnGerar.disabled = false;
                        mostrarVideoConcluido(resData.video_url);
                    }, 800);

                } else if (resData.status === 'erro') {
                    clearInterval(pollingInterval);
                    tratarErroGeracao(resData.erro_mensagem || 'Erro desconhecido durante a renderização.');
                }
            })
            .catch(e => console.error('Erro polling status:', e));
        }, 2000);
    }

    function mostrarVideoConcluido(url) {
        placeholderPreview.style.display = 'none';
        videoPlayer.style.display = 'block';
        videoPlayer.src = url;
        videoPlayer.load();
        videoPlayer.play().catch(e => console.log('Autoplay not allowed:', e));

        btnDownload.href = url;
        btnWhatsapp.href = 'https://api.whatsapp.com/send?text=' + encodeURIComponent('Confira este vídeo promocional incrível do nosso produto: ' + url);

        actionButtonsBox.style.display = 'flex';
    }

    function tratarErroGeracao(mensagem) {
        if (pollingInterval) clearInterval(pollingInterval);
        btnGerar.disabled = false;
        progressBox.style.display = 'none';
        alert('❌ Erro na Geração do Vídeo:\n' + mensagem);
    }
});

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
    const selectTrilha = document.getElementById('select-trilha');

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
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSubmit.disabled = false;
                if (data.success && data.trilha) {
                    msgDiv.style.color = '#34d399';
                    msgDiv.innerText = '✅ ' + data.message;

                    // Adiciona a nova opção ao select de trilhas e seleciona
                    const opt = document.createElement('option');
                    opt.value = data.trilha.arquivo;
                    opt.setAttribute('data-url', data.trilha.url);
                    const icone = data.trilha.tipo === 'efeito_especial' ? '🔊 ' : '✨ ';
                    opt.innerText = icone + data.trilha.titulo + ' — ' + data.trilha.tipo_label;
                    opt.selected = true;

                    if (selectTrilha) {
                        selectTrilha.appendChild(opt);
                        selectTrilha.value = data.trilha.arquivo;
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
