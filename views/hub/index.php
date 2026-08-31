<?php

/** @var yii\web\View $this */
/** @var app\models\Usuarios $usuario */
/** @var app\modules\vendas\models\Clientes|null $cliente */
/** @var app\modules\vendas\models\Mesa|null $mesa */
/** @var app\modules\vendas\models\Comanda|null $comanda */
/** @var app\modules\vendas\models\ComandaItem[] $comandaItens */
/** @var float $totalComanda */
/** @var app\modules\vendas\models\ClienteInbox[] $inboxMessages */
/** @var app\modules\vendas\models\ProdutoCard[] $cardsDestaque */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = ($mesa ? "Mesa {$mesa->numero_mesa} — " : "") . ($usuario->nome_loja ?? 'Direct Hub');
$isIdentificado = ($cliente !== null);
$defaultTab = $mesa ? 'comanda' : 'feed';
?>

<div class="max-w-md mx-auto min-h-screen bg-gray-50 flex flex-col"
     x-data="{
         tab: '<?= $defaultTab ?>',
         showIdModal: <?= !$isIdentificado ? 'true' : 'false' ?>,
         nome: '',
         telefone: '',
         clienteId: '<?= $cliente ? $cliente->id : '' ?>',
         clienteNome: '<?= $cliente ? Html::encode($cliente->nome_completo) : '' ?>',
         isIdentificado: <?= $isIdentificado ? 'true' : 'false' ?>,
         loadingId: false,
         msgChamado: '',
         solicitandoGarcom: false,
         solicitandoConta: false,
         identificarCliente() {
             if (!this.telefone) {
                 alert('Por favor, informe seu WhatsApp para continuar.');
                 return;
             }
             this.loadingId = true;
             fetch('<?= Url::to(['/hub/identificar']) ?>', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                 body: new URLSearchParams({
                     'usuario_id': '<?= $usuario->id ?>',
                     'nome': this.nome,
                     'telefone': this.telefone,
                     'mesa_id': '<?= $mesa ? $mesa->id : '' ?>'
                 })
             })
             .then(r => r.json())
             .then(data => {
                 this.loadingId = false;
                 if (data.success) {
                     this.isIdentificado = true;
                     this.clienteId = data.cliente.id;
                     this.clienteNome = data.cliente.nome;
                     this.showIdModal = false;
                     // Atualiza a URL sem recarregar com o magic token
                     window.history.replaceState({}, '', '?token=' + data.token);
                 } else {
                     alert(data.message || 'Erro ao identificar');
                 }
             })
             .catch(() => {
                 this.loadingId = false;
                 alert('Erro de conexão ao servidor.');
             });
         },
         chamarGarcom(motivo) {
             this.solicitandoGarcom = true;
             fetch('<?= Url::to(['/hub/chamar-garcom']) ?>', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                 body: new URLSearchParams({
                     'usuario_id': '<?= $usuario->id ?>',
                     'cliente_id': this.clienteId,
                     'mesa_id': '<?= $mesa ? $mesa->id : '' ?>',
                     'motivo': motivo
                 })
             })
             .then(r => r.json())
             .then(d => {
                 this.solicitandoGarcom = false;
                 alert(d.message);
             });
         },
         pedirConta() {
             if (!confirm('Deseja solicitar o fechamento da conta no caixa?')) return;
             this.solicitandoConta = true;
             fetch('<?= Url::to(['/hub/pedir-conta']) ?>', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                 body: new URLSearchParams({
                     'usuario_id': '<?= $usuario->id ?>',
                     'cliente_id': this.clienteId,
                     'comanda_id': '<?= $comanda ? $comanda->id : '' ?>'
                 })
             })
             .then(r => r.json())
             .then(d => {
                 this.solicitandoConta = false;
                 alert(d.message);
             });
         }
     }">

    <!-- Top Bar / Header do Estabelecimento -->
    <header class="bg-white border-b border-gray-100 sticky top-0 z-30 px-4 py-3 shadow-xs">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-700 font-bold text-base shadow-inner">
                    <?= strtoupper(substr($usuario->nome_loja ?? 'P', 0, 2)) ?>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-gray-900 leading-tight m-0 truncate max-w-[180px]">
                        <?= Html::encode($usuario->nome_loja ?? 'Loja Pulse') ?>
                    </h1>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-[11px] text-gray-500 font-medium">Aberto &bull; Atendimento Online</span>
                    </div>
                </div>
            </div>

            <!-- Badge de Mesa ou Cliente -->
            <div class="text-right">
                <?php if ($mesa): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200 shadow-2xs">
                        🪑 Mesa <?= Html::encode($mesa->numero_mesa) ?>
                    </span>
                <?php else: ?>
                    <template x-if="isIdentificado">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-100 text-emerald-800" x-text="'Olá, ' + clienteNome.split(' ')[0]"></span>
                    </template>
                    <template x-if="!isIdentificado">
                        <button type="button" @click="showIdModal = true" class="text-xs text-emerald-600 font-bold underline">Identificar</button>
                    </template>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- BANNER DE BOAS-VINDAS / NOTIFICAÇÃO PUSH -->
    <div class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white px-4 py-3 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-bold m-0 flex items-center gap-1">
                <span>⚡</span> Canal Direto & Comanda Digital
            </p>
            <p class="text-[11px] text-emerald-100 m-0 mt-0.5">
                Receba novidades, vídeos e ofertas exclusivas no seu celular.
            </p>
        </div>
        <button type="button" onclick="Notification.requestPermission()" class="text-[11px] bg-white text-emerald-800 font-bold px-2.5 py-1.5 rounded-lg shadow-sm hover:bg-emerald-50 transition-colors whitespace-nowrap">
            🔔 Ativar Avisos
        </button>
    </div>

    <!-- CONTEÚDO PRINCIPAL (ABAS) -->

    <!-- ABA 1: COMANDA & CONTA DA MESA -->
    <section x-show="tab === 'comanda'" class="p-4 space-y-4 flex-1">
        <?php if ($mesa || $comanda): ?>
            <!-- Resumo da Conta -->
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-3">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Status da Comanda</span>
                        <h2 class="text-base font-bold text-gray-900 m-0">
                            <?= $comanda ? Html::encode($comanda->numero_comanda) : 'Mesa ' . Html::encode($mesa->numero_mesa) ?>
                        </h2>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Acumulado</span>
                        <p class="text-xl font-extrabold text-emerald-600 m-0">
                            R$ <?= number_format($totalComanda, 2, ',', '.') ?>
                        </p>
                    </div>
                </div>

                <!-- Ações Rápidas da Mesa -->
                <div class="grid grid-cols-2 gap-2.5 pt-1">
                    <button type="button" @click="chamarGarcom('Atendimento na Mesa')" class="w-full flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 font-bold text-xs hover:bg-amber-100 transition-colors">
                        <span>👋</span> Chamar Garçom
                    </button>
                    <button type="button" @click="pedirConta()" class="w-full flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 shadow-sm transition-colors">
                        <span>💳</span> Pedir Conta / PIX
                    </button>
                </div>
            </div>

            <!-- Lista de Itens Pedidos -->
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                <h3 class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Itens Pedidos</h3>
                <?php if (empty($comandaItens)): ?>
                    <p class="text-center text-xs text-gray-400 py-6 m-0">
                        Nenhum pedido lançado nesta comanda ainda.<br>
                        Veja nosso cardápio na aba abaixo para fazer seu pedido!
                    </p>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($comandaItens as $item): ?>
                            <?php $prod = $item->produto ?? null; ?>
                            <div class="py-2.5 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold text-gray-800 m-0">
                                        <?= (int)$item->quantidade ?>x <?= Html::encode($prod ? $prod->nome : 'Item') ?>
                                    </p>
                                    <span class="inline-block mt-0.5 text-[10px] px-2 py-0.5 rounded-full font-medium <?= $item->status_preparo === 'pronto' ? 'bg-green-100 text-green-800' : ($item->status_preparo === 'preparando' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600') ?>">
                                        <?= ucfirst(Html::encode($item->status_preparo ?? 'Pendente')) ?>
                                    </span>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-bold text-gray-900 m-0">
                                        R$ <?= number_format((float)$item->valor_unitario * (float)$item->quantidade, 2, ',', '.') ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl p-6 text-center border border-gray-100 shadow-sm">
                <span class="text-4xl">🧾</span>
                <h3 class="text-sm font-bold text-gray-800 mt-2 mb-1">Você não está em uma mesa ativa</h3>
                <p class="text-xs text-gray-500 m-0">Se você estiver no restaurante, aponte a câmera para o QR Code da sua mesa para abrir sua comanda.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- ABA 2: FEED DE OFERTAS & VÍDEOS -->
    <section x-show="tab === 'feed'" class="p-4 space-y-4 flex-1" style="display: none;">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Novidades & Pratos do Dia</h2>

        <?php if (empty($inboxMessages) && empty($cardsDestaque)): ?>
            <div class="bg-white rounded-2xl p-6 text-center border border-gray-100 shadow-sm">
                <span class="text-4xl">🎬</span>
                <h3 class="text-sm font-bold text-gray-800 mt-2 mb-1">Nenhuma novidade no momento</h3>
                <p class="text-xs text-gray-500 m-0">Fique atento! Novas ofertas e vídeos serão exibidos aqui.</p>
            </div>
        <?php else: ?>

            <!-- Mensagens e Vídeos da Inbox -->
            <?php foreach ($inboxMessages as $msg): ?>
                <article class="bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm">
                    <?php if (!empty($msg->midia_url)): ?>
                        <?php if ($msg->tipo === 'video' || str_ends_with(strtolower($msg->midia_url), '.mp4')): ?>
                            <video src="<?= Html::encode($msg->midia_url) ?>" controls class="w-full h-48 object-cover bg-black"></video>
                        <?php else: ?>
                            <img src="<?= Html::encode($msg->midia_url) ?>" alt="" class="w-full h-48 object-cover">
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="p-4">
                        <?php if (!empty($msg->titulo)): ?>
                            <h3 class="text-sm font-bold text-gray-900 mb-1"><?= Html::encode($msg->titulo) ?></h3>
                        <?php endif; ?>
                        <p class="text-xs text-gray-600 m-0 leading-relaxed"><?= nl2br(Html::encode($msg->conteudo_texto)) ?></p>
                        
                        <div class="mt-3 flex items-center justify-between text-[10px] text-gray-400 pt-2 border-t border-gray-50">
                            <span><?= Yii::$app->formatter->asRelativeTime($msg->created_at) ?></span>
                            <span class="text-emerald-600 font-semibold">&bull; Oficial <?= Html::encode($usuario->nome_loja) ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <!-- Cards Promocionais -->
            <?php foreach ($cardsDestaque as $card): ?>
                <?php if (!empty($card->card_url) || !empty($card->card_path)): ?>
                    <?php $imgUrl = !empty($card->card_url) ? $card->card_url : Url::to('@web/' . ltrim($card->card_path, '/')); ?>
                    <article class="bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm">
                        <img src="<?= Html::encode($imgUrl) ?>" alt="Oferta" class="w-full h-auto object-cover">
                        <?php if (!empty($card->produto)): ?>
                            <div class="p-3 flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-900 m-0"><?= Html::encode($card->produto->nome) ?></h4>
                                    <p class="text-xs font-extrabold text-emerald-600 m-0 mt-0.5">R$ <?= number_format((float)$card->produto->preco_venda, 2, ',', '.') ?></p>
                                </div>
                                <a href="<?= Url::to(['/catalogo/index', 'slug' => $usuario->slug ?? $usuario->id]) ?>" class="px-3 py-1.5 bg-emerald-600 text-white rounded-xl text-xs font-bold shadow-xs">
                                    Pedir
                                </a>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endif; ?>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>

    <!-- ABA 3: CARDÁPIO ONLINE & PEDIDOS -->
    <section x-show="tab === 'cardapio'" class="p-4 space-y-3 flex-1" style="display: none;">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider m-0">Cardápio & Produtos</h2>
            <a href="<?= Url::to(['/catalogo/index', 'slug' => $usuario->slug ?? $usuario->id]) ?>" target="_blank" class="text-xs text-emerald-600 font-bold underline">
                Ver Catálogo Completo &rarr;
            </a>
        </div>

        <div class="bg-white rounded-2xl p-5 text-center border border-gray-100 shadow-sm">
            <span class="text-4xl">📖</span>
            <h3 class="text-sm font-bold text-gray-800 mt-2 mb-1">Acesse nosso Catálogo Digital</h3>
            <p class="text-xs text-gray-500 mb-4">Escolha seus pratos e faça pedidos online sem precisar de garçom.</p>
            
            <a href="<?= Url::to(['/catalogo/index', 'slug' => $usuario->slug ?? $usuario->id]) ?>" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs shadow-sm hover:bg-emerald-700 transition-colors w-full">
                Abrir Cardápio Digital
            </a>
        </div>
    </section>

    <!-- MODAL DE IDENTIFICAÇÃO RÁPIDA (NOME + WHATSAPP) -->
    <div x-show="showIdModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl max-w-sm w-full p-6 animate-in fade-in slide-in-from-bottom-5 duration-300" @click.away="showIdModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl">👋</span>
                    <h3 class="text-base font-bold text-gray-900 m-0">Bem-vindo(a)!</h3>
                </div>
                <button type="button" @click="showIdModal = false" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
            </div>

            <p class="text-xs text-gray-500 mb-4">
                Informe seu nome e WhatsApp para abrir sua comanda digital e receber ofertas exclusivas.
            </p>

            <form @submit.prevent="identificarCliente()" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Seu Nome</label>
                    <input type="text" x-model="nome" placeholder="Ex: Lucas Silva" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 bg-gray-50">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Seu WhatsApp (com DDD) *</label>
                    <input type="tel" x-model="telefone" required placeholder="Ex: (81) 98888-7777" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 bg-gray-50">
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="loadingId" class="w-full py-3 px-4 rounded-xl bg-emerald-600 text-white font-bold text-sm shadow-md hover:bg-emerald-700 transition-colors flex items-center justify-center gap-2">
                        <template x-if="loadingId">
                            <span class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                        </template>
                        <span>Acessar Comanda / Hub</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- BOTTOM TAB BAR NAVEGAÇÃO -->
    <nav class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white border-t border-gray-200 px-6 py-2 flex items-center justify-around z-40 shadow-lg">
        <?php if ($mesa || $comanda): ?>
            <button type="button" @click="tab = 'comanda'" :class="tab === 'comanda' ? 'text-emerald-600 font-bold' : 'text-gray-400 font-medium'" class="flex flex-col items-center gap-1 text-[11px] transition-colors">
                <span class="text-xl">🧾</span>
                <span>Comanda</span>
            </button>
        <?php endif; ?>

        <button type="button" @click="tab = 'feed'" :class="tab === 'feed' ? 'text-emerald-600 font-bold' : 'text-gray-400 font-medium'" class="flex flex-col items-center gap-1 text-[11px] transition-colors">
            <span class="text-xl">🎬</span>
            <span>Vídeos & Feed</span>
        </button>

        <button type="button" @click="tab = 'cardapio'" :class="tab === 'cardapio' ? 'text-emerald-600 font-bold' : 'text-gray-400 font-medium'" class="flex flex-col items-center gap-1 text-[11px] transition-colors">
            <span class="text-xl">🍽️</span>
            <span>Cardápio</span>
        </button>
    </nav>

</div>
