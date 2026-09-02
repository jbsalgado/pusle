<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Encarte[] $encartes */
/** @var array $metricas */
/** @var app\models\Usuario $loja */
/** @var app\modules\vendas\models\LojaConfiguracao|null $lojaConfig */

$this->title = 'Gestão de Encartes Digitais';
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/produto/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- Cabeçalho Principal com Ações -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 rounded-3xl shadow-xl border border-slate-700 text-white">
        <div>
            <div class="flex items-center gap-3">
                <div class="p-3 bg-red-600/30 border border-red-500/40 rounded-2xl text-2xl shadow-inner">
                    📑
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black font-montserrat tracking-tight text-white flex items-center gap-2">
                        Gestão de Encartes Digitais
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300 font-medium mt-0.5">
                        Tablóides de ofertas estilo Flipbook, controle de status, validade e disparo WhatsApp
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <button type="button" onclick="abrirModalGerarEncarte()" class="px-5 py-3.5 bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-black text-xs sm:text-sm rounded-2xl shadow-lg transition transform active:scale-95 flex items-center gap-2 cursor-pointer">
                <span>✨ + Criar Novo Encarte</span>
            </button>
            <a href="<?= Url::to(['/vendas/produto/index']) ?>" class="px-4 py-3.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs sm:text-sm rounded-2xl border border-slate-600 transition flex items-center gap-2">
                <span>📦 Catálogo de Produtos</span>
            </a>
        </div>
    </div>

    <!-- Cards de Métricas e Indicadores -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Card 1: Total de Encartes -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-2xl text-indigo-600 flex-shrink-0">
                📚
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total de Encartes</div>
                <div class="text-2xl font-black text-slate-900 mt-0.5"><?= $metricas['total'] ?></div>
            </div>
        </div>

        <!-- Card 2: Encartes Ativos -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-2xl text-emerald-600 flex-shrink-0">
                🟢
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Ativos (Vigentes)</div>
                <div class="text-2xl font-black text-emerald-600 mt-0.5"><?= $metricas['ativos'] ?></div>
            </div>
        </div>

        <!-- Card 3: Inativos / Expirados -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-2xl text-amber-600 flex-shrink-0">
                ⏳
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Inativos / Expirados</div>
                <div class="text-2xl font-black text-amber-600 mt-0.5"><?= $metricas['inativos'] ?></div>
            </div>
        </div>

        <!-- Card 4: Total de Visualizações -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-100 flex items-center justify-center text-2xl text-purple-600 flex-shrink-0">
                👁️
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Visualizações Totais</div>
                <div class="text-2xl font-black text-purple-700 mt-0.5"><?= number_format($metricas['visualizacoes'], 0, ',', '.') ?></div>
            </div>
        </div>
    </div>

    <!-- Lista de Encartes -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-extrabold text-slate-800 flex items-center gap-2">
                <span>Edições Geradas</span>
                <span class="text-xs font-bold text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full"><?= count($encartes) ?></span>
            </h2>
            <div class="text-xs text-slate-500">
                * Clientes em encartes inativos são automaticamente avisados e redirecionados para a edição ativa mais recente.
            </div>
        </div>

        <?php if (empty($encartes)): ?>
            <div class="bg-white rounded-3xl p-12 text-center border-2 border-dashed border-slate-300 space-y-4 shadow-sm">
                <div class="w-20 h-20 mx-auto rounded-3xl bg-red-50 text-red-600 flex items-center justify-center text-4xl shadow-inner">
                    📑
                </div>
                <h3 class="text-lg font-extrabold text-slate-800">Nenhum encarte gerado ainda</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Crie encartes digitais interativos estilo Flipsnack a partir de categorias, itens com foto ou todo o catálogo para divulgar suas ofertas no WhatsApp.
                </p>
                <button type="button" onclick="abrirModalGerarEncarte()" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-2xl shadow-lg transition">
                    + Criar Meu Primeiro Encarte
                </button>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($encartes as $enc): ?>
                    <?php
                    $isAtivo = ($enc->status === 'ativo');
                    $urlPublica = $enc->getUrlPublica();
                    $urlPdf = $enc->getUrlPdf();
                    $totalProds = count($enc->encarteProdutos);
                    ?>
                    <div id="card-encarte-<?= $enc->id ?>" class="bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between overflow-hidden relative">
                        
                        <!-- Topo com gradiente de capa -->
                        <div class="p-5 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white relative">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <span id="badge-status-<?= $enc->id ?>" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5 <?= $isAtivo ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-red-500/20 text-red-300 border border-red-500/40' ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $isAtivo ? 'bg-emerald-400 animate-pulse' : 'bg-red-400' ?>"></span>
                                    <span><?= $isAtivo ? 'Ativo (Vigente)' : 'Inativo (Expirado)' ?></span>
                                </span>

                                <span class="text-[10px] text-slate-400 font-semibold">
                                    <?= Yii::$app->formatter->asRelativeTime($enc->created_at) ?>
                                </span>
                            </div>

                            <h3 class="text-base font-black font-montserrat tracking-tight text-white leading-snug line-clamp-2">
                                <?= Html::encode($enc->titulo) ?>
                            </h3>
                            <?php if ($enc->subtitulo): ?>
                                <p class="text-xs text-slate-300 line-clamp-1 mt-0.5">
                                    <?= Html::encode($enc->subtitulo) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Corpo com miniaturas dos produtos e detalhes -->
                        <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                            <div class="space-y-3">
                                <!-- Informações de estatísticas -->
                                <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                    <div>
                                        <span class="text-[10px] text-slate-400 block uppercase font-bold">Produtos</span>
                                        <span class="font-extrabold text-slate-800">📦 <?= $totalProds ?> item(ns)</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 block uppercase font-bold">Visualizações</span>
                                        <span class="font-extrabold text-purple-700">👁️ <?= (int)$enc->visualizacoes_count ?></span>
                                    </div>
                                </div>

                                <!-- Mini-galeria de fotos de produtos -->
                                <?php if (!empty($enc->encarteProdutos)): ?>
                                    <div class="flex items-center gap-1.5 overflow-hidden">
                                        <?php 
                                        $itensPreview = array_slice($enc->encarteProdutos, 0, 4);
                                        foreach ($itensPreview as $itemProd):
                                            $prod = $itemProd->produto;
                                            $fotoUrl = ($prod && $prod->fotoPrincipal) ? (method_exists($prod->fotoPrincipal, 'getUrlCompleta') ? $prod->fotoPrincipal->getUrlCompleta() : $prod->fotoPrincipal->getUrl()) : null;
                                        ?>
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center text-[10px] text-slate-400" title="<?= $prod ? Html::encode($prod->nome) : '' ?>">
                                                <?php if ($fotoUrl): ?>
                                                    <img src="<?= Html::encode($fotoUrl) ?>" alt="Foto" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    🛍️
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if ($totalProds > 4): ?>
                                            <div class="w-10 h-10 rounded-xl bg-slate-200 text-slate-700 font-extrabold text-[10px] flex items-center justify-center flex-shrink-0">
                                                +<?= $totalProds - 4 ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Botões de Ação do Card -->
                            <div class="pt-3 border-t border-slate-100 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <a href="<?= Html::encode($urlPublica) ?>" target="_blank" class="py-2 px-3 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                                        <span>👁️ Flipbook</span>
                                    </a>
                                    <a href="<?= Html::encode($urlPdf) ?>" target="_blank" class="py-2 px-3 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5">
                                        <span>📥 PDF</span>
                                    </a>
                                </div>

                                <div class="grid grid-cols-3 gap-1.5 pt-1">
                                    <button type="button" onclick="copiarLinkPublico('<?= Html::encode($urlPublica) ?>')" class="py-1.5 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] rounded-lg transition flex items-center justify-center gap-1 cursor-pointer">
                                        <span>🔗 Copiar</span>
                                    </button>

                                    <button type="button" id="btn-toggle-<?= $enc->id ?>" onclick="alternarStatusEncarte('<?= $enc->id ?>')" class="py-1.5 px-2 font-bold text-[11px] rounded-lg transition flex items-center justify-center gap-1 cursor-pointer <?= $isAtivo ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200' ?>">
                                        <span><?= $isAtivo ? 'Inativar' : 'Ativar' ?></span>
                                    </button>

                                    <button type="button" onclick="excluirEncarte('<?= $enc->id ?>', '<?= Html::encode(addslashes($enc->titulo)) ?>')" class="py-1.5 px-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-bold text-[11px] rounded-lg transition flex items-center justify-center gap-1 cursor-pointer">
                                        <span>🗑️ Excluir</span>
                                    </button>
                                </div>
                            </div>

                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal de Gerar Encarte Embutido para Uso Direto -->
<?= $this->render('@app/modules/vendas/views/produto/_modal_gerar_encarte') ?>

<script>
    function copiarLinkPublico(url) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                alert('✅ Link público do encarte copiado com sucesso!');
            });
        } else {
            prompt('Copie o link público:', url);
        }
    }

    function alternarStatusEncarte(id) {
        const btn = document.getElementById('btn-toggle-' + id);
        if (btn) btn.innerHTML = '⌛...';

        fetch('<?= Url::to(['/vendas/encarte/alternar-status']) ?>?id=' + encodeURIComponent(id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao alternar status.');
                if (btn) btn.innerHTML = 'Status';
            }
        })
        .catch(err => {
            alert('Erro de conexão: ' + err.message);
            if (btn) btn.innerHTML = 'Status';
        });
    }

    function excluirEncarte(id, titulo) {
        if (!confirm('Tem certeza que deseja excluir o encarte "' + titulo + '"?\nEsta ação não poderá ser desfeita.')) {
            return;
        }

        const formData = new FormData();
        formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

        fetch('<?= Url::to(['/vendas/encarte/excluir']) ?>?id=' + encodeURIComponent(id), {
            method: 'POST',
            headers: {
                'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const card = document.getElementById('card-encarte-<?= $enc->id ?? '' ?>') || document.getElementById('card-encarte-' + id);
                if (card) {
                    card.remove();
                }
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao excluir encarte.');
            }
        })
        .catch(err => alert('Erro de comunicação: ' + err.message));
    }
</script>
