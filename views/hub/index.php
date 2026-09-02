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
/** @var app\modules\vendas\models\LojaConfiguracao|null $lojaConfig */

use yii\helpers\Html;
use yii\helpers\Url;

$nomeLoja = $lojaConfig ? ($lojaConfig->nome_fantasia ?: $lojaConfig->nome_loja) : ($usuario->nome ?? 'Loja Pulse');
$slugLoja = $usuario->catalogo_path ?: ($usuario->username ?: $usuario->id);

$this->title = ($mesa ? "Mesa {$mesa->numero_mesa} — " : "") . $nomeLoja;
$isIdentificado = ($cliente !== null);
$defaultTab = $mesa ? 'comanda' : 'feed';
?>

<script>
window.hubApp = function() {
    return {
        tab: '<?= $defaultTab ?>',
        showIdModal: <?= !$isIdentificado ? 'true' : 'false' ?>,
        nome: (function() {
            try { return localStorage.getItem('cliente_hub_nome') || localStorage.getItem('cliente_encarte_nome') || ''; } catch(e) { return ''; }
        })(),
        telefone: (function() {
            try { return localStorage.getItem('cliente_hub_whatsapp') || localStorage.getItem('cliente_encarte_whatsapp') || ''; } catch(e) { return ''; }
        })(),
        clienteId: <?= json_encode($cliente ? (string)$cliente->id : '') ?>,
        clienteNome: <?= json_encode($cliente ? (string)$cliente->nome_completo : '') ?>,
        isIdentificado: <?= $isIdentificado ? 'true' : 'false' ?>,
        loadingId: false,
        msgChamado: '',
        solicitandoGarcom: false,
        solicitandoConta: false,
        textoMensagem: '',
        enviandoMsg: false,
        mensagensChat: [],
        showEmojiPicker: false,
        fotoFile: null,
        fotoPreview: null,
        emojisList: ['👍', '❤️', '😊', '🔥', '👏', '🎉', '📦', '🍽️', '💬', '✅', '🛵', '📍', '⏳', '🙏', '🧾', '💳', '📸', '😋', '⭐', '🤝'],
        
        init() {
            window._hubInstance = this;
        },

        get primeiroNome() {
            if (!this.clienteNome) return 'Cliente';
            return this.clienteNome.trim().split(' ')[0] || 'Cliente';
        },
        
        fecharModal() {
            this.showIdModal = false;
            const el = document.getElementById('modal-bem-vindo');
            if (el) el.style.display = 'none';
        },
        
        mascaraWhatsapp(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            if (v.length > 10) {
                v = v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (v.length > 5) {
                v = v.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
            } else if (v.length > 2) {
                v = v.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
            }
            this.telefone = v;
        },
        
        identificarCliente() {
            const rawPhone = this.telefone ? this.telefone.replace(/\D/g, '') : '';
            if (rawPhone.length < 10) {
                alert('Por favor, informe seu WhatsApp com DDD para continuar.');
                return;
            }
            this.loadingId = true;

            try {
                if (this.nome) {
                    localStorage.setItem('cliente_hub_nome', this.nome);
                    localStorage.setItem('cliente_encarte_nome', this.nome);
                }
                localStorage.setItem('cliente_hub_whatsapp', this.telefone);
                localStorage.setItem('cliente_encarte_whatsapp', this.telefone);
            } catch(e) {}

            fetch('<?= Url::to(['/hub/identificar']) ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'usuario_id': <?= json_encode($usuario->id) ?>,
                    'nome': this.nome,
                    'telefone': this.telefone,
                    'mesa_id': <?= json_encode($mesa ? (string)$mesa->id : '') ?>
                })
            })
            .then(r => {
                if (!r.ok) {
                    return r.text().then(t => { throw new Error('Status ' + r.status + ': ' + t.substring(0, 100)); });
                }
                return r.json();
            })
            .then(data => {
                this.loadingId = false;
                if (data && data.success) {
                    this.isIdentificado = true;
                    this.clienteId = data.cliente ? data.cliente.id : '';
                    this.clienteNome = data.cliente ? data.cliente.nome : '';
                    this.fecharModal();
                    if (data.token) {
                        try {
                            window.history.replaceState({}, '', '?token=' + data.token);
                        } catch(e) {}
                    }
                } else {
                    alert((data && data.message) ? data.message : 'Erro ao identificar cliente.');
                }
            })
            .catch(err => {
                this.loadingId = false;
                console.error('Erro ao identificar:', err);
                alert('Não foi possível identificar: ' + (err.message || 'Erro de comunicação com o servidor.'));
            });
        },
        
        chamarGarcom(motivo) {
            this.solicitandoGarcom = true;
            fetch('<?= Url::to(['/hub/chamar-garcom']) ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'usuario_id': <?= json_encode($usuario->id) ?>,
                    'cliente_id': this.clienteId,
                    'mesa_id': <?= json_encode($mesa ? (string)$mesa->id : '') ?>,
                    'motivo': motivo
                })
            })
            .then(r => r.json())
            .then(d => {
                this.solicitandoGarcom = false;
                alert(d.message || 'Chamado registrado com sucesso.');
            })
            .catch(() => {
                this.solicitandoGarcom = false;
                alert('Erro ao enviar chamado ao garçom.');
            });
        },
        
        pedirConta() {
            if (!confirm('Deseja solicitar o fechamento da conta no caixa?')) return;
            this.solicitandoConta = true;
            fetch('<?= Url::to(['/hub/pedir-conta']) ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'usuario_id': <?= json_encode($usuario->id) ?>,
                    'cliente_id': this.clienteId,
                    'comanda_id': <?= json_encode($comanda ? (string)$comanda->id : '') ?>
                })
            })
            .then(r => r.json())
            .then(d => {
                this.solicitandoConta = false;
                alert(d.message || 'Solicitação de conta enviada ao caixa.');
            })
            .catch(() => {
                this.solicitandoConta = false;
                alert('Erro ao solicitar a conta.');
            });
        },
        
        adicionarEmoji(emoji) {
            this.textoMensagem += emoji;
        },

        selecionarFoto(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            this.fotoFile = file;
            this.fotoPreview = URL.createObjectURL(file);
        },

        removerFoto() {
            this.fotoFile = null;
            this.fotoPreview = null;
            if (this.$refs.inputFoto) this.$refs.inputFoto.value = '';
        },
        
        async enviarMensagemChat() {
            const txt = this.textoMensagem.trim();
            if (!txt && !this.fotoFile) return;

            if (!this.isIdentificado && !this.telefone) {
                this.showIdModal = true;
                return;
            }

            this.enviandoMsg = true;

            let midiaUrl = '';
            if (this.fotoFile) {
                try {
                    const formData = new FormData();
                    formData.append('foto', this.fotoFile);
                    const upResp = await fetch('<?= Url::to(['/hub/upload-midia']) ?>', {
                        method: 'POST',
                        body: formData
                    });
                    const upData = await upResp.json();
                    if (upData.success) {
                        midiaUrl = upData.url;
                    }
                } catch(e) {
                    console.error('Erro no upload da foto:', e);
                }
            }

            fetch('<?= Url::to(['/hub/enviar-mensagem']) ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'usuario_id': <?= json_encode($usuario->id) ?>,
                    'cliente_id': this.clienteId,
                    'mesa_id': <?= json_encode($mesa ? (string)$mesa->id : '') ?>,
                    'nome': this.nome,
                    'telefone': this.telefone,
                    'mensagem': txt,
                    'midia_url': midiaUrl
                })
            })
            .then(r => r.json())
            .then(d => {
                this.enviandoMsg = false;
                if (d.success) {
                    this.textoMensagem = '';
                    this.removerFoto();
                    this.showEmojiPicker = false;
                    this.mensagensChat.unshift(d.item);
                    if (d.cliente) {
                        this.isIdentificado = true;
                        this.clienteId = d.cliente.id;
                        this.clienteNome = d.cliente.nome;
                    }
                } else {
                    alert(d.message || 'Erro ao enviar mensagem.');
                }
            })
            .catch(err => {
                this.enviandoMsg = false;
                console.error('Erro ao enviar mensagem:', err);
                alert('Não foi possível enviar a mensagem no momento.');
            });
        }
    };
};

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', function() {
        if (typeof Alpine !== 'undefined' && window.hubApp) {
            Alpine.data('hubApp', window.hubApp);
        }
    });
}
</script>

<div class="max-w-md mx-auto min-h-screen bg-gray-50 flex flex-col" x-data="hubApp()">

    <!-- Top Bar / Header do Estabelecimento -->
    <header class="bg-white border-b border-gray-100 sticky top-0 z-30 px-4 py-3 shadow-xs">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-700 font-bold text-base shadow-inner">
                    <?= strtoupper(substr($nomeLoja, 0, 2)) ?>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-gray-900 leading-tight m-0 truncate max-w-[180px]">
                        <?= Html::encode($nomeLoja) ?>
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
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-100 text-emerald-800" x-text="'Olá, ' + primeiroNome"></span>
                    </template>
                    <template x-if="!isIdentificado">
                        <button type="button" @click="showIdModal = true" class="text-xs text-emerald-600 font-bold underline cursor-pointer">Identificar</button>
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
            <?php
            $reciboConta = null;
            if (!empty($inboxMessages)) {
                foreach ($inboxMessages as $m) {
                    if ($m->tipo === 'conta') {
                        $reciboConta = $m;
                        break;
                    }
                }
            }
            $isComandaFechada = ($comanda && $comanda->status === 'fechada');
            ?>

            <?php if ($isComandaFechada): ?>
                <!-- Banner de Alerta: Conta Fechada -->
                <div class="bg-amber-50 rounded-2xl p-4 border border-amber-200 shadow-xs flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">🔒</span>
                        <div>
                            <h4 class="text-xs font-bold text-amber-900 m-0">Conta Encerrada & Paga</h4>
                            <p class="text-[11px] text-amber-700 m-0 mt-0.5">Esta mesa foi finalizada pelo caixa do estabelecimento.</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 bg-amber-600 text-white font-extrabold text-[10px] rounded-full uppercase">Fechada</span>
                </div>
            <?php endif; ?>

            <?php if ($reciboConta): ?>
                <!-- Card de Comprovante / Recibo Digital -->
                <div class="bg-gradient-to-br from-emerald-950 via-slate-900 to-gray-900 text-white rounded-2xl p-4 sm:p-5 shadow-xl border border-emerald-800/50 space-y-3">
                    <div class="flex items-center justify-between border-b border-gray-700/80 pb-2.5">
                        <h3 class="text-xs font-black text-emerald-400 uppercase tracking-wider flex items-center gap-1.5 m-0">
                            <span>🧾</span> Recibo de Fechamento Digital
                        </h3>
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 text-[10px] font-extrabold rounded-full border border-emerald-500/30">Oficial</span>
                    </div>
                    <div class="text-xs font-mono bg-slate-950/80 p-3.5 rounded-xl border border-slate-800 text-emerald-100 whitespace-pre-wrap leading-relaxed shadow-inner overflow-x-auto">
                        <?= Html::encode($reciboConta->conteudo_texto) ?>
                    </div>
                </div>
            <?php endif; ?>

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
                <?php if (!$isComandaFechada): ?>
                    <div class="grid grid-cols-2 gap-2.5 pt-1">
                        <button type="button" @click="chamarGarcom('Atendimento na Mesa')" class="w-full flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 font-bold text-xs hover:bg-amber-100 transition-colors">
                            <span>👋</span> Chamar Garçom
                        </button>
                        <button type="button" @click="pedirConta()" class="w-full flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 shadow-sm transition-colors">
                            <span>💳</span> Pedir Conta / PIX
                        </button>
                    </div>
                <?php else: ?>
                    <p class="text-center text-xs text-gray-500 font-medium py-1 m-0">
                        Obrigado pela preferência e volte sempre! 😊🚀
                    </p>
                <?php endif; ?>
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

    <!-- ABA 2: CANAL DIRETO, FEED & CHAT DE ATENDIMENTO -->
    <section x-show="tab === 'feed'" class="p-4 space-y-4 flex-1" style="display: none;">
        
        <!-- Caixa de Envio de Mensagem / Chat Interativo do Cliente (Texto + Emojis + Fotos) -->
        <div class="bg-white p-3.5 rounded-2xl border border-gray-200/80 shadow-xs space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-800 flex items-center gap-1.5">
                    <span>💬</span> Enviar Mensagem para o Atendimento
                </span>
                <template x-if="isIdentificado">
                    <span class="text-[10px] text-emerald-700 font-bold bg-emerald-100/80 px-2 py-0.5 rounded-full" x-text="primeiroNome"></span>
                </template>
            </div>

            <!-- Preview de Imagem Anexada -->
            <template x-if="fotoPreview">
                <div class="relative inline-block border-2 border-emerald-500 rounded-xl overflow-hidden bg-gray-100 shadow-xs">
                    <img :src="fotoPreview" alt="Foto anexada" class="h-20 w-20 object-cover">
                    <button type="button" @click="removerFoto()" class="absolute top-1 right-1 bg-black/70 hover:bg-black text-white rounded-full p-1 text-[10px] leading-none transition cursor-pointer" title="Remover Foto">
                        &times;
                    </button>
                </div>
            </template>

            <!-- Seletor Rápido de Emojis -->
            <div x-show="showEmojiPicker" x-cloak class="p-2 bg-gray-50 border border-gray-200 rounded-xl flex items-center gap-1.5 overflow-x-auto scrollbar-thin">
                <template x-for="em in emojisList" :key="em">
                    <button type="button" @click="adicionarEmoji(em)" class="text-base p-1 hover:bg-white rounded-lg transition hover:scale-125 cursor-pointer flex-shrink-0" x-text="em"></button>
                </template>
            </div>

            <div class="flex items-center gap-1.5">
                <!-- Botão de Emoji -->
                <button type="button" @click="showEmojiPicker = !showEmojiPicker" :class="showEmojiPicker ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 border-gray-200'" class="p-2.5 border rounded-xl text-xs font-bold transition flex items-center justify-center cursor-pointer" title="Inserir Emoji">
                    <span>😀</span>
                </button>

                <!-- Botão de Anexo de Foto / Câmera -->
                <input type="file" x-ref="inputFoto" @change="selecionarFoto($event)" accept="image/*" class="hidden">
                <button type="button" @click="$refs.inputFoto.click()" class="p-2.5 bg-gray-100 hover:bg-gray-200 border border-gray-200 text-gray-600 rounded-xl text-xs font-bold transition flex items-center justify-center cursor-pointer" title="Anexar Foto">
                    <span>📷</span>
                </button>

                <!-- Input de Texto -->
                <input type="text" 
                       x-model="textoMensagem" 
                       @keydown.enter.prevent="enviarMensagemChat()"
                       placeholder="Digite sua dúvida, pedido ou recado..." 
                       class="flex-1 bg-gray-50 border border-gray-300 rounded-xl px-3 py-2.5 text-xs text-gray-900 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">

                <!-- Botão Enviar -->
                <button type="button" 
                        @click="enviarMensagemChat()" 
                        :disabled="enviandoMsg || (!textoMensagem.trim() && !fotoFile)"
                        class="px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1 cursor-pointer">
                    <template x-if="enviandoMsg">
                        <span class="animate-spin rounded-full h-3 w-3 border-2 border-white border-t-transparent"></span>
                    </template>
                    <span>Enviar</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
        </div>

        <!-- Mensagens Enviadas Recentemente no Chat (Reativo) -->
        <template x-for="item in mensagensChat" :key="item.id">
            <article class="bg-emerald-50 border border-emerald-200/80 rounded-2xl p-3.5 shadow-xs animate-in fade-in slide-in-from-top-2 duration-200">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[11px] font-bold text-emerald-900 flex items-center gap-1">
                        <span>👤</span> <span x-text="item.remetente || 'Você'"></span>
                    </span>
                    <span class="text-[10px] text-emerald-700 font-medium bg-emerald-200/60 px-1.5 py-0.5 rounded" x-text="item.created_at"></span>
                </div>
                <p class="text-xs text-emerald-950 font-medium m-0 leading-relaxed" x-text="item.conteudo_texto"></p>
                
                <template x-if="item.midia_url">
                    <div class="mt-2">
                        <img :src="item.midia_url" alt="Foto anexada" class="rounded-xl max-h-48 w-auto object-cover border border-emerald-200 shadow-xs cursor-pointer" @click="window.open(item.midia_url, '_blank')">
                    </div>
                </template>

                <div class="mt-2 pt-1.5 border-t border-emerald-200/50 flex items-center justify-between text-[10px] text-emerald-700">
                    <span>Enviado para a Central da Loja</span>
                    <span class="font-bold">✓ Entregue</span>
                </div>
            </article>
        </template>

        <div class="flex items-center justify-between pt-1">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider m-0">Novidades & Atendimento</h2>
            <span class="text-[10px] text-gray-400">Linha do Tempo</span>
        </div>

        <?php if (empty($inboxMessages) && empty($cardsDestaque)): ?>
            <div class="bg-white rounded-2xl p-6 text-center border border-gray-100 shadow-sm" x-show="mensagensChat.length === 0">
                <span class="text-4xl">🎬</span>
                <h3 class="text-sm font-bold text-gray-800 mt-2 mb-1">Canal de Comunicação Ativo</h3>
                <p class="text-xs text-gray-500 m-0">Envie uma mensagem acima ou acompanhe novidades da loja aqui.</p>
            </div>
        <?php else: ?>

            <!-- Mensagens e Vídeos da Inbox -->
            <?php foreach ($inboxMessages as $msg): ?>
                <?php 
                $isCliente = (isset($msg->acoes_json['origem']) && $msg->acoes_json['origem'] === 'cliente');
                ?>
                <?php if ($isCliente): ?>
                    <article class="bg-emerald-50 border border-emerald-200/80 rounded-2xl p-3.5 shadow-xs">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-bold text-emerald-900 flex items-center gap-1">
                                <span>👤</span> <?= Html::encode($msg->acoes_json['remetente'] ?? 'Você') ?>
                            </span>
                            <span class="text-[10px] text-emerald-700 font-medium bg-emerald-200/60 px-1.5 py-0.5 rounded"><?= Yii::$app->formatter->asRelativeTime($msg->created_at) ?></span>
                        </div>
                        <p class="text-xs text-emerald-950 font-medium m-0 leading-relaxed"><?= nl2br(Html::encode($msg->conteudo_texto)) ?></p>
                        
                        <?php if (!empty($msg->midia_url)): ?>
                            <div class="mt-2">
                                <img src="<?= Html::encode($msg->midia_url) ?>" alt="Foto anexada" class="rounded-xl max-h-48 w-auto object-cover border border-emerald-200 shadow-xs cursor-pointer" onclick="window.open(this.src, '_blank')">
                            </div>
                        <?php endif; ?>

                        <div class="mt-2 pt-1.5 border-t border-emerald-200/50 flex items-center justify-between text-[10px] text-emerald-700">
                            <span>Mensagem Direta</span>
                            <span class="font-bold">✓ Entregue</span>
                        </div>
                    </article>
                <?php else: ?>
                    <article class="bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm">
                        <?php if (!empty($msg->midia_url)): ?>
                            <?php if ($msg->tipo === 'video' || str_ends_with(strtolower($msg->midia_url), '.mp4')): ?>
                                <video src="<?= Html::encode($msg->midia_url) ?>" controls class="w-full h-48 object-cover bg-black"></video>
                            <?php else: ?>
                                <img src="<?= Html::encode($msg->midia_url) ?>" alt="" class="w-full h-48 object-cover cursor-pointer" onclick="window.open(this.src, '_blank')">
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="p-4">
                            <?php if (!empty($msg->titulo)): ?>
                                <h3 class="text-sm font-bold text-gray-900 mb-1"><?= Html::encode($msg->titulo) ?></h3>
                            <?php endif; ?>
                            <p class="text-xs text-gray-600 m-0 leading-relaxed"><?= nl2br(Html::encode($msg->conteudo_texto)) ?></p>
                            
                            <div class="mt-3 flex items-center justify-between text-[10px] text-gray-400 pt-2 border-t border-gray-50">
                                <span><?= Yii::$app->formatter->asRelativeTime($msg->created_at) ?></span>
                                <span class="text-emerald-600 font-semibold">&bull; <?= Html::encode($msg->acoes_json['autor'] ?? ('Oficial ' . $nomeLoja)) ?></span>
                            </div>
                        </div>
                    </article>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Cards Promocionais -->
            <?php foreach ($cardsDestaque as $card): ?>
                <?php 
                $imgUrl = null;
                if (!empty($card->card_path)) {
                    $caminhoFisico = Yii::getAlias('@app/web/' . ltrim($card->card_path, '/'));
                    if (file_exists($caminhoFisico)) {
                        $imgUrl = Url::to('@web/' . ltrim($card->card_path, '/'));
                    }
                } elseif (!empty($card->card_url) && !str_starts_with($card->card_url, 'http://localhost/uploads/')) {
                    $imgUrl = $card->card_url;
                }
                ?>
                <?php if ($imgUrl): ?>
                    <article class="bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm">
                        <img src="<?= Html::encode($imgUrl) ?>" alt="Oferta" class="w-full h-auto object-cover">
                        <?php if (!empty($card->produto)): ?>
                            <div class="p-3 flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-900 m-0"><?= Html::encode($card->produto->nome) ?></h4>
                                    <p class="text-xs font-extrabold text-emerald-600 m-0 mt-0.5">R$ <?= number_format((float)$card->produto->preco_venda, 2, ',', '.') ?></p>
                                </div>
                                <a href="<?= Url::to(['/catalogo/index', 'slug' => $slugLoja]) ?>" class="px-3 py-1.5 bg-emerald-600 text-white rounded-xl text-xs font-bold shadow-xs">
                                    Pedir
                                </a>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endif; ?>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>

    <!-- ABA 3: CARDÁPIO / CATÁLOGO ONLINE & PEDIDOS -->
    <section x-show="tab === 'cardapio'" class="p-4 space-y-3 flex-1" style="display: none;">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider m-0">Catálogo & Produtos</h2>
            <a href="<?= Url::to(['/catalogo/index', 'slug' => $slugLoja]) ?>" target="_blank" class="text-xs text-emerald-600 font-bold underline">
                Ver Catálogo Completo &rarr;
            </a>
        </div>

        <div class="bg-white rounded-2xl p-5 text-center border border-gray-100 shadow-sm">
            <span class="text-4xl">🛍️</span>
            <h3 class="text-sm font-bold text-gray-800 mt-2 mb-1">Acesse nosso Catálogo Digital</h3>
            <p class="text-xs text-gray-500 mb-4">Veja todos os produtos com preços, fotos e monte sua sacola online.</p>
            
            <a href="<?= Url::to(['/catalogo/index', 'slug' => $slugLoja]) ?>" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs shadow-sm hover:bg-emerald-700 transition-colors w-full">
                Abrir Catálogo Digital
            </a>
        </div>
    </section>

    <!-- MODAL DE IDENTIFICAÇÃO RÁPIDA (NOME + WHATSAPP) -->
    <div id="modal-bem-vindo" 
         x-show="showIdModal" 
         x-cloak 
         :class="showIdModal ? 'flex' : 'hidden'" 
         @keydown.escape.window="fecharModal()" 
         class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl max-w-sm w-full p-6 animate-in fade-in slide-in-from-bottom-5 duration-300" @click.away="fecharModal()">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl">👋</span>
                    <h3 class="text-base font-bold text-gray-900 m-0">Bem-vindo(a)!</h3>
                </div>
                <button type="button" 
                        @click="fecharModal()" 
                        onclick="document.getElementById('modal-bem-vindo').style.display='none'; if(window._hubInstance) window._hubInstance.showIdModal=false;" 
                        class="text-gray-400 hover:text-gray-600 text-2xl font-light leading-none p-1 cursor-pointer transition">&times;</button>
            </div>

            <p class="text-xs text-gray-500 mb-4">
                Informe seu nome e WhatsApp para abrir sua comanda digital, enviar mensagens e receber ofertas exclusivas.
            </p>

            <form @submit.prevent="identificarCliente()" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Seu Nome</label>
                    <input type="text" x-model="nome" placeholder="Ex: Lucas Silva" class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm font-medium outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-gray-50 transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Seu WhatsApp (com DDD) *</label>
                    <input type="tel" x-model="telefone" @input="mascaraWhatsapp($event)" required placeholder="Ex: (81) 98888-7777" class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm font-bold outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-gray-50 transition">
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="loadingId" class="w-full py-3 px-4 rounded-xl bg-emerald-600 text-white font-bold text-sm shadow-md hover:bg-emerald-700 transition transform active:scale-98 flex items-center justify-center gap-2 cursor-pointer">
                        <template x-if="loadingId">
                            <span class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                        </template>
                        <span>Acessar Atendimento / Hub</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- BOTTOM TAB BAR NAVEGAÇÃO -->
    <nav class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white border-t border-gray-200 px-6 py-2 flex items-center justify-around z-40 shadow-lg">
        <?php if ($mesa || $comanda): ?>
            <button type="button" @click="tab = 'comanda'" :class="tab === 'comanda' ? 'text-emerald-600 font-bold' : 'text-gray-400 font-medium'" class="flex flex-col items-center gap-1 text-[11px] transition-colors cursor-pointer">
                <span class="text-xl">🧾</span>
                <span>Comanda</span>
            </button>
        <?php endif; ?>

        <button type="button" @click="tab = 'feed'" :class="tab === 'feed' ? 'text-emerald-600 font-bold' : 'text-gray-400 font-medium'" class="flex flex-col items-center gap-1 text-[11px] transition-colors cursor-pointer">
            <span class="text-xl">💬</span>
            <span>Canal &amp; Mensagens</span>
        </button>

        <button type="button" @click="tab = 'cardapio'" :class="tab === 'cardapio' ? 'text-emerald-600 font-bold' : 'text-gray-400 font-medium'" class="flex flex-col items-center gap-1 text-[11px] transition-colors cursor-pointer">
            <span class="text-xl">🛍️</span>
            <span>Catálogo &amp; Ofertas</span>
        </button>
    </nav>

</div>
