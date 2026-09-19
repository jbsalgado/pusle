<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Central de Marketing Social & Automação | Pulse Plus';
?>

<div class="min-h-screen bg-slate-950 text-slate-100 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Header / Hero Section -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 p-8 shadow-2xl border border-purple-500/20">
            <div class="absolute -right-10 -bottom-10 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-10 -top-10 w-96 h-96 bg-pink-600/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold uppercase tracking-wider mb-3 border border-purple-500/30">
                        <span>✨ Automação de Marketing Multi-Canal</span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white">
                        Central de Marketing Social
                    </h1>
                    <p class="mt-2 text-sm sm:text-base text-purple-200 max-w-2xl">
                        Conecte suas contas do <strong>Instagram Business</strong>, <strong>Facebook Pages</strong> e <strong>TikTok</strong>. 
                        Publique cards e vídeos promocionais diretamente do SaaS para acelerar as vendas de lojistas e afiliados.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="<?= Url::to(['/vendas/produto-video/studio']) ?>" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-sky-500/20 transition flex items-center gap-2">
                        <span>🎬 Studio de Vídeos</span>
                    </a>
                    <a href="<?= Url::to(['/vendas/produto/index']) ?>" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs sm:text-sm border border-white/15 transition flex items-center gap-2">
                        <span>🖼️ Catálogo de Produtos</span>
                    </a>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-8 pt-6 border-t border-white/10">
                <div class="bg-black/30 backdrop-blur rounded-2xl p-4 border border-white/5">
                    <div class="text-xs font-semibold text-purple-300">Total de Postagens</div>
                    <div class="text-2xl font-black text-white mt-1"><?= number_format($stats['total_posts'], 0, ',', '.') ?></div>
                </div>
                <div class="bg-black/30 backdrop-blur rounded-2xl p-4 border border-white/5">
                    <div class="text-xs font-semibold text-emerald-400">Publicados com Sucesso</div>
                    <div class="text-2xl font-black text-emerald-400 mt-1"><?= number_format($stats['published'], 0, ',', '.') ?></div>
                </div>
                <div class="bg-black/30 backdrop-blur rounded-2xl p-4 border border-white/5">
                    <div class="text-xs font-semibold text-amber-300">Em Processamento (Fila)</div>
                    <div class="text-2xl font-black text-amber-300 mt-1"><?= number_format($stats['processing'], 0, ',', '.') ?></div>
                </div>
                <div class="bg-black/30 backdrop-blur rounded-2xl p-4 border border-white/5">
                    <div class="text-xs font-semibold text-red-400">Falhas / Erros</div>
                    <div class="text-2xl font-black text-red-400 mt-1"><?= number_format($stats['failed'], 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="p-4 rounded-2xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-200 flex items-center gap-3">
                <span class="text-xl">✅</span>
                <span class="font-medium"><?= Yii::$app->session->getFlash('success') ?></span>
            </div>
        <?php endif; ?>
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="p-4 rounded-2xl bg-red-950/80 border border-red-500/40 text-red-200 flex items-center gap-3">
                <span class="text-xl">⚠️</span>
                <span class="font-medium"><?= Yii::$app->session->getFlash('error') ?></span>
            </div>
        <?php endif; ?>

        <!-- Cards de Canais / Plataformas Sociais -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- CANAL 1: META (Facebook & Instagram) -->
            <div class="bg-slate-900/90 rounded-3xl p-6 border border-slate-800 shadow-xl flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 right-0 w-48 h-48 bg-blue-600/10 rounded-full blur-2xl pointer-events-none"></div>

                <div>
                    <div class="flex items-center justify-between gap-4 mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 via-pink-600 to-purple-600 flex items-center justify-center text-white text-2xl shadow-lg">
                                📸
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white">Meta: Instagram & Facebook</h2>
                                <p class="text-xs text-slate-400">Instagram Business, Reels, Feed e Páginas do Facebook</p>
                            </div>
                        </div>

                        <?php
                        $metaAccounts = array_filter($accounts, function($a) { return $a->provider !== 'TIKTOK'; });
                        $hasMetaActive = count($metaAccounts) > 0;
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $hasMetaActive ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">
                            <?= $hasMetaActive ? count($metaAccounts) . ' Conectada(s)' : 'Desconectado' ?>
                        </span>
                    </div>

                    <!-- Lista de Contas Meta Conectadas -->
                    <div class="space-y-3 mb-6">
                        <?php if (empty($metaAccounts)): ?>
                            <div class="p-4 rounded-2xl bg-slate-950/60 border border-dashed border-slate-800 text-center text-xs text-slate-400">
                                Nenhuma página do Facebook ou conta do Instagram vinculada ainda.<br>
                                Clique no botão abaixo para conectar seu perfil.
                            </div>
                        <?php else: ?>
                            <?php foreach ($metaAccounts as $acc): ?>
                                <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-600/20 text-blue-400 flex items-center justify-center font-bold text-sm border border-blue-500/30">
                                            FB
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-white flex items-center gap-2">
                                                <span><?= Html::encode($acc->page_name) ?></span>
                                                <?php if (!empty($acc->instagram_business_account_id)): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-pink-500/20 text-pink-300 border border-pink-500/30">IG Business</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                ID: <?= Html::encode($acc->facebook_page_id ?: $acc->instagram_business_account_id) ?>
                                                <?php if ($acc->token_expires_at): ?>
                                                    • Expira em <?= date('d/m/Y', strtotime($acc->token_expires_at)) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <button onclick="desconectarConta('<?= $acc->id ?>', '<?= Html::encode(addslashes($acc->page_name)) ?>')" class="text-xs text-red-400 hover:text-red-300 hover:bg-red-950/50 p-2 rounded-xl transition cursor-pointer" title="Desconectar Conta">
                                        Desconectar
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Botão de Conexão Meta -->
                <div class="pt-4 border-t border-slate-800/80">
                    <div class="mb-3 p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-[11px] text-slate-400">
                        🔒 <strong>Transparência &amp; Consentimento:</strong> Ao conectar, você autoriza o Pulse a publicar no Facebook e Instagram os cards e vídeos que você aprovar no painel. Você pode desconectar a qualquer momento.
                    </div>
                    <button type="button" onclick="iniciarConexaoMeta()" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-sm shadow-lg shadow-blue-600/20 transition flex items-center justify-center gap-2 cursor-pointer">
                        <span>🔗 Conectar Facebook &amp; Instagram Business</span>
                    </button>
                    <div class="flex items-center justify-center gap-2 mt-2 text-[11px] text-slate-500">
                        <a href="<?= Url::to(['/site/politica-privacidade']) ?>" target="_blank" class="hover:text-slate-300 underline">Privacidade</a> &bull;
                        <a href="<?= Url::to(['/site/termos-de-uso']) ?>" target="_blank" class="hover:text-slate-300 underline">Termos</a> &bull;
                        <a href="<?= Url::to(['/site/exclusao-dados']) ?>" target="_blank" class="hover:text-slate-300 underline">Exclusão de Dados</a>
                    </div>
                </div>
            </div>

            <!-- CANAL 2: TIKTOK (Content Posting API v2) -->
            <div class="bg-slate-900/90 rounded-3xl p-6 border border-slate-800 shadow-xl flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 right-0 w-48 h-48 bg-pink-600/10 rounded-full blur-2xl pointer-events-none"></div>

                <div>
                    <div class="flex items-center justify-between gap-4 mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-slate-950 via-pink-600 to-cyan-400 flex items-center justify-center text-white text-2xl shadow-lg">
                                🎵
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white">TikTok for Developers</h2>
                                <p class="text-xs text-slate-400">Content Posting API v2 (Vídeos 9:16 e Carrossel de Fotos)</p>
                            </div>
                        </div>

                        <?php
                        $tiktokAccounts = array_filter($accounts, function($a) { return $a->provider === 'TIKTOK'; });
                        $hasTikTokActive = count($tiktokAccounts) > 0;
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $hasTikTokActive ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">
                            <?= $hasTikTokActive ? count($tiktokAccounts) . ' Conectada(s)' : 'Desconectado' ?>
                        </span>
                    </div>

                    <!-- Lista de Contas TikTok Conectadas -->
                    <div class="space-y-3 mb-6">
                        <?php if (empty($tiktokAccounts)): ?>
                            <div class="p-4 rounded-2xl bg-slate-950/60 border border-dashed border-slate-800 text-center text-xs text-slate-400">
                                Nenhuma conta do TikTok conectada ainda.<br>
                                Clique no botão abaixo para autorizar com segurança via OAuth 2.0.
                            </div>
                        <?php else: ?>
                            <?php foreach ($tiktokAccounts as $acc): ?>
                                <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($acc->account_avatar_url)): ?>
                                            <img src="<?= Html::encode($acc->account_avatar_url) ?>" alt="Avatar" class="w-9 h-9 rounded-xl object-cover border border-cyan-500/30">
                                        <?php else: ?>
                                            <div class="w-9 h-9 rounded-xl bg-cyan-600/20 text-cyan-400 flex items-center justify-center font-bold text-sm border border-cyan-500/30">
                                                TT
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="text-sm font-bold text-white flex items-center gap-2">
                                                <span>@<?= Html::encode($acc->page_name) ?></span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">Oficial v2</span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                OpenID: <?= Html::encode(substr($acc->tiktok_open_id ?: '', 0, 16)) ?>...
                                                • Auto-renovação de 365 dias ativa
                                            </div>
                                        </div>
                                    </div>

                                    <button onclick="desconectarConta('<?= $acc->id ?>', '<?= Html::encode(addslashes($acc->page_name)) ?>')" class="text-xs text-red-400 hover:text-red-300 hover:bg-red-950/50 p-2 rounded-xl transition cursor-pointer" title="Desconectar Conta">
                                        Desconectar
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Botão de Conexão TikTok -->
                <div class="pt-4 border-t border-slate-800/80">
                    <a href="<?= Url::to(['/social-integration/tiktok-auth']) ?>" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-pink-600 via-purple-600 to-cyan-500 hover:opacity-95 text-white font-extrabold text-sm shadow-lg shadow-pink-600/20 transition flex items-center justify-center gap-2 text-center">
                        <span>🎵 Conectar com TikTok (OAuth 2.0)</span>
                    </a>
                    <p class="text-[11px] text-slate-500 text-center mt-2">
                        Autoriza a publicação direta de vídeos promocionais e carrosséis sem marca d'água de terceiros.
                    </p>
                </div>
            </div>

        </div>

        <!-- Histórico Recente de Publicações -->
        <div class="bg-slate-900/90 rounded-3xl p-6 border border-slate-800 shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span>📋 Histórico de Publicações e Status em Tempo Real</span>
                    </h2>
                    <p class="text-xs text-slate-400">Acompanhe a renderização, polling e confirmação de postagens nas redes</p>
                </div>

                <button onclick="window.location.reload()" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition flex items-center gap-1.5 self-start sm:self-auto cursor-pointer">
                    <span>🔄 Atualizar Status</span>
                </button>
            </div>

            <?php if (empty($recentPosts)): ?>
                <div class="p-8 rounded-2xl bg-slate-950/60 border border-dashed border-slate-800 text-center text-xs text-slate-400">
                    Nenhuma publicação realizada ainda.<br>
                    Gere um vídeo no <strong>Studio de Vídeos</strong> ou um card no <strong>Catálogo de Produtos</strong> e clique em "Publicar nas Redes Sociais"!
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Mídia</th>
                                <th class="py-3 px-4">Plataforma</th>
                                <th class="py-3 px-4">Formato</th>
                                <th class="py-3 px-4">Legenda</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Data</th>
                                <th class="py-3 px-4 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-medium">
                            <?php foreach ($recentPosts as $p): ?>
                                <?php
                                $statusBadge = 'bg-slate-800 text-slate-300 border-slate-700';
                                if ($p->status === 'PUBLISHED') $statusBadge = 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30';
                                elseif ($p->status === 'PROCESSING') $statusBadge = 'bg-amber-500/20 text-amber-300 border-amber-500/30 animate-pulse';
                                elseif ($p->status === 'FAILED') $statusBadge = 'bg-red-500/20 text-red-300 border-red-500/30';
                                ?>
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-4">
                                        <?php if (preg_match('/\.(mp4|mov|webm)$/i', $p->media_url)): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-sky-500/20 text-sky-300 text-[11px] font-bold">
                                                🎬 Vídeo
                                            </span>
                                        <?php else: ?>
                                            <img src="<?= Html::encode($p->media_url) ?>" alt="Thumb" class="w-10 h-10 rounded-lg object-cover border border-slate-700">
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-white">
                                        <?php if ($p->platform === 'TIKTOK'): ?>
                                            <span class="text-pink-400">🎵 TikTok</span>
                                        <?php elseif ($p->platform === 'INSTAGRAM'): ?>
                                            <span class="text-pink-500">📸 Instagram</span>
                                        <?php elseif ($p->platform === 'FACEBOOK'): ?>
                                            <span class="text-blue-400">📘 Facebook</span>
                                        <?php else: ?>
                                            <span class="text-purple-400">🌐 Todas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                            <?= Html::encode($p->media_type) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 max-w-xs truncate" title="<?= Html::encode($p->caption) ?>">
                                        <?= Html::encode($p->caption ?: 'Sem legenda') ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold border <?= $statusBadge ?>">
                                            <?= Html::encode($p->status) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-400 whitespace-nowrap">
                                        <?= date('d/m/Y H:i', strtotime($p->created_at)) ?>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <button onclick="verDetalhesPost('<?= $p->id ?>')" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 transition cursor-pointer" title="Ver Detalhes / Log">
                                            🔍 Detalhes
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal Detalhes do Post -->
<div id="modalDetalhesPost" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl text-slate-100">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>📋 Detalhes da Publicação</span>
            </h3>
            <button onclick="fecharModalDetalhes()" class="text-slate-400 hover:text-white p-1 rounded-lg">✕</button>
        </div>
        <div class="p-6 space-y-4 text-xs" id="corpoDetalhesPost">
            Carregando...
        </div>
        <div class="px-6 py-3 bg-slate-950/60 border-t border-slate-800 text-right">
            <button onclick="fecharModalDetalhes()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 font-bold text-white">Fechar</button>
        </div>
    </div>
</div>

<!-- SDK do Facebook Login para Conexão Segura -->
<script>
    window.fbAsyncInit = function() {
        FB.init({
            appId      : '<?= Html::encode($metaAppId) ?>',
            cookie     : true,
            xfbml      : true,
            version    : 'v19.0'
        });
    };

    (function(d, s, id){
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {return;}
        js = d.createElement(s); js.id = id;
        js.src = "https://connect.facebook.net/pt_BR/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));

    function iniciarConexaoMeta() {
        if (typeof FB === 'undefined') {
            const tokenManual = prompt('Cole o User Access Token da Meta:');
            if (tokenManual) enviarTokenMetaParaBackend(tokenManual);
            return;
        }

        FB.login(function(response) {
            if (response.authResponse && response.authResponse.accessToken) {
                enviarTokenMetaParaBackend(response.authResponse.accessToken);
            } else {
                alert('Conexão com a Meta cancelada pelo usuário.');
            }
        }, {
            scope: 'pages_show_list,pages_read_engagement,pages_manage_posts,instagram_basic,instagram_content_publish'
        });
    }

    async function enviarTokenMetaParaBackend(token) {
        try {
            const resp = await fetch('<?= Url::to(['/social-integration/connect']) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ short_lived_token: token })
            });
            const data = await resp.json();
            if (data.success) {
                alert('🎉 ' + (data.message || 'Contas Meta conectadas com sucesso!'));
                window.location.reload();
            } else {
                alert('Erro ao conectar: ' + (data.error || 'Falha na resposta do servidor.'));
            }
        } catch (e) {
            alert('Erro de conexão: ' + e.message);
        }
    }

    async function desconectarConta(id, nome) {
        if (!confirm(`Deseja realmente desconectar a conta "${nome}"?`)) return;

        try {
            const resp = await fetch('<?= Url::to(['/social-integration/disconnect']) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await resp.json();
            if (data.success) {
                alert('Conta desconectada com sucesso.');
                window.location.reload();
            } else {
                alert('Erro ao desconectar: ' + (data.error || 'Erro desconhecido.'));
            }
        } catch (e) {
            alert('Erro de rede: ' + e.message);
        }
    }

    async function verDetalhesPost(id) {
        const modal = document.getElementById('modalDetalhesPost');
        const corpo = document.getElementById('corpoDetalhesPost');
        modal.classList.remove('hidden');
        corpo.innerHTML = '<div class="text-center py-4">Carregando detalhes...</div>';

        try {
            const resp = await fetch('<?= Url::to(['/social-integration/status']) ?>?id=' + encodeURIComponent(id));
            const data = await resp.json();
            if (data.success && data.post) {
                const p = data.post;
                corpo.innerHTML = `
                    <div class="space-y-2">
                        <div><strong>ID:</strong> <code class="text-purple-300">${p.id}</code></div>
                        <div><strong>Plataforma:</strong> ${p.platform}</div>
                        <div><strong>Tipo de Mídia:</strong> ${p.media_type}</div>
                        <div><strong>Status:</strong> <span class="font-bold text-emerald-400">${p.status}</span></div>
                        <div><strong>ID Publicado na Rede:</strong> <code class="text-sky-300">${p.published_media_id || 'N/A'}</code></div>
                        ${p.error_payload ? `<div class="p-3 bg-red-950/50 border border-red-500/30 rounded-xl text-red-300 mt-2"><strong>Erro Técnico:</strong><pre class="mt-1 text-[11px] overflow-x-auto">${JSON.stringify(p.error_payload, null, 2)}</pre></div>` : ''}
                    </div>
                `;
            } else {
                corpo.innerHTML = '<div class="text-red-400">Não foi possível carregar os detalhes do post.</div>';
            }
        } catch (e) {
            corpo.innerHTML = '<div class="text-red-400">Erro de rede: ' + e.message + '</div>';
        }
    }

    function fecharModalDetalhes() {
        document.getElementById('modalDetalhesPost').classList.add('hidden');
    }
</script>
