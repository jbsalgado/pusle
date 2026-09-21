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
$catalogoUrl = Url::to('@web/catalogo/?slug=' . urlencode($slugLoja));

$isMesaAtiva = ($mesa !== null || $comanda !== null);
$defaultTab = $isMesaAtiva ? 'comanda' : 'feed';

$this->title = ($mesa ? "Mesa {$mesa->numero_mesa} — " : "") . $nomeLoja . " — Canal de Atendimento";
$isIdentificado = ($cliente !== null);

$initialMessages = [];
foreach ($inboxMessages as $m) {
    $isCliente = (isset($m->acoes_json['origem']) && $m->acoes_json['origem'] === 'cliente');
    $setorNome = $m->setor ? $m->setor->nome : ($m->acoes_json['setor_nome'] ?? null);
    $setorIcone = $m->setor ? $m->setor->icone : '💬';
    $autor = $m->acoes_json['atendente_nome'] ?? $m->acoes_json['autor'] ?? ($isCliente ? 'Você' : $nomeLoja);

    $initialMessages[] = [
        'id'             => $m->id,
        'tipo'           => $m->tipo,
        'titulo'         => $m->titulo,
        'autor'          => $autor,
        'conteudo_texto' => $m->conteudo_texto,
        'midia_url'      => $m->midia_url,
        'acoes_json'     => $m->acoes_json,
        'setor_nome'     => $setorNome,
        'setor_icone'    => $setorIcone,
        'hora'           => date('H:i', strtotime($m->created_at)),
        'created_at'     => Yii::$app->formatter->asRelativeTime($m->created_at),
        'is_cliente'     => $isCliente,
    ];
}
?>

<script>
window.hubApp = function() {
    return {
        tab: '<?= $defaultTab ?>',
        isMesaAtiva: <?= $isMesaAtiva ? 'true' : 'false' ?>,
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
        mensagensChat: <?= json_encode($initialMessages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        modalImagemZoom: null,
        showEmojiPicker: false,
        fotoFile: null,
        fotoPreview: null,
        emojisList: ['👍', '❤️', '😊', '🔥', '👏', '🎉', '📦', '🛍️', '💬', '✅', '🛵', '📍', '⏳', '🙏', '🧾', '💳', '📸', '⭐', '🤝', '👋'],
        setorSelecionado: <?= json_encode(!empty($setores) ? $setores[0]->id : '') ?>,
        setoresLoja: <?= json_encode(array_map(function($s) { return ['id' => $s->id, 'nome' => $s->nome, 'icone' => $s->icone ?: '💬']; }, $setores ?? [])) ?>,
        
        ws: null,
        wsConectado: false,
        wsReconnectTimer: null,
        pollingTimer: null,
        
        init() {
            window._hubInstance = this;

            // Se o cliente já informou WhatsApp anteriormente e ainda não foi validado nesta sessão, tenta auto-identificar de forma silenciosa
            if (!this.isIdentificado && this.telefone && this.telefone.replace(/\D/g, '').length >= 10) {
                this.identificarCliente(true);
            }

            // Inicia conexão WebSocket em tempo real
            this.iniciarWebSocketHub();

            // Sincronização periódica de novas mensagens com intervalo adaptativo
            this.iniciarPollingMensagens();

            this.$nextTick(() => {
                this.rolarParaFimChat(true);
            });
        },

        iniciarWebSocketHub() {
            if (this.ws && (this.ws.readyState === WebSocket.OPEN || this.ws.readyState === WebSocket.CONNECTING)) {
                return;
            }

            try {
                const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                const wsHost = window.location.host;
                const lojaId = <?= json_encode((string)$usuario->id) ?>;
                const clienteParam = this.clienteId ? ('&cliente_id=' + encodeURIComponent(this.clienteId)) : '';
                const wsUrl = `${wsProtocol}//${wsHost}/ws/?loja_id=${encodeURIComponent(lojaId)}${clienteParam}`;

                this.ws = new WebSocket(wsUrl);

                this.ws.onopen = () => {
                    this.wsConectado = true;
                    this.iniciarPollingMensagens();
                };

                this.ws.onmessage = (event) => {
                    try {
                        const d = JSON.parse(event.data);
                        if (!d || !d.type) return;

                        const conversaIdEsperada = 'cliente_' + this.clienteId;

                        if (d.type === 'conversa_limpa' && d.conversa_id === conversaIdEsperada) {
                            this.mensagensChat = [];
                            return;
                        }

                        if (d.type === 'nova_mensagem' && d.conversa_id === conversaIdEsperada && d.item) {
                            const msgItem = Object.assign({}, d.item);
                            // Ajusta a perspectiva da bolha para a visão do cliente
                            if (msgItem.origem === 'atendente') {
                                msgItem.lado = 'esquerda';
                            } else if (msgItem.origem === 'cliente') {
                                msgItem.lado = 'direita';
                            }

                            const jaExiste = this.mensagensChat.some(m => String(m.id) === String(msgItem.id));
                            if (!jaExiste) {
                                this.mensagensChat.push(msgItem);
                                this.rolarParaFimChat();
                            }
                        }
                    } catch (e) {
                        console.error('Erro WS Hub:', e);
                    }
                };

                this.ws.onerror = () => {
                    this.wsConectado = false;
                };

                this.ws.onclose = () => {
                    this.wsConectado = false;
                    this.iniciarPollingMensagens();
                    if (this.wsReconnectTimer) clearTimeout(this.wsReconnectTimer);
                    this.wsReconnectTimer = setTimeout(() => {
                        this.iniciarWebSocketHub();
                    }, 5000);
                };
            } catch (e) {
                this.wsConectado = false;
            }
        },

        rolarParaFimChat(imediato = false) {
            this.$nextTick(() => {
                const container = document.getElementById('chatMessagesContainer');
                if (container) {
                    if (imediato) {
                        container.scrollTop = container.scrollHeight;
                    } else {
                        container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
                    }
                }
            });
        },

        mudarAba(aba) {
            this.tab = aba;
            if (aba === 'feed') {
                this.rolarParaFimChat(true);
            }
        },

        iniciarPollingMensagens() {
            if (this.pollingTimer) clearInterval(this.pollingTimer);
            const intervalo = this.wsConectado ? 30000 : 6000;
            this.pollingTimer = setInterval(() => {
                if (document.hidden) return;
                this.buscarMensagensServidor();
            }, intervalo);
        },

        buscarMensagensServidor() {
            if (!this.clienteId && !this.telefone) return;
            const params = new URLSearchParams({
                'usuario_id': <?= json_encode($usuario->id) ?>,
                'cliente_id': this.clienteId || '',
                'mesa_id': <?= json_encode($mesa ? (string)$mesa->id : '') ?>
            });
            fetch('<?= Url::to(['/hub/mensagens']) ?>?' + params.toString())
                .then(r => r.json())
                .then(d => {
                    if (d && d.success && Array.isArray(d.mensagens)) {
                        const container = document.getElementById('chatMessagesContainer');
                        const estavaNoFim = container ? (container.scrollHeight - container.scrollTop - container.clientHeight < 120) : true;
                        const qtdAnterior = this.mensagensChat.length;

                        this.mensagensChat = d.mensagens;

                        if (d.mensagens.length > qtdAnterior || estavaNoFim) {
                            this.rolarParaFimChat();
                        }
                    }
                })
                .catch(() => {});
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
        
        identificarCliente(silencioso = false) {
            const rawPhone = this.telefone ? this.telefone.replace(/\D/g, '') : '';
            if (rawPhone.length < 10) {
                if (!silencioso) alert('Por favor, informe seu WhatsApp com DDD para continuar.');
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
                    if (this.ws) {
                        try { this.ws.close(); } catch(e) {}
                        this.ws = null;
                    }
                    this.iniciarWebSocketHub();
                    this.buscarMensagensServidor();
                    if (data.token) {
                        try {
                            window.history.replaceState({}, '', '?token=' + data.token);
                        } catch(e) {}
                    }
                    // Foca no input do chat
                    setTimeout(() => {
                        const inputMsg = document.getElementById('inputTextoChat');
                        if (inputMsg) inputMsg.focus();
                    }, 300);
                } else if (!silencioso) {
                    alert((data && data.message) ? data.message : 'Erro ao identificar cliente.');
                }
            })
            .catch(err => {
                this.loadingId = false;
                if (!silencioso) {
                    console.error('Erro ao identificar:', err);
                    alert('Não foi possível identificar: ' + (err.message || 'Erro de comunicação com o servidor.'));
                }
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
            const el = document.getElementById('inputTextoChat');
            if (el) el.focus();
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
                    'midia_url': midiaUrl,
                    'setor_id': this.setorSelecionado || ''
                })
            })
            .then(r => r.json())
            .then(d => {
                this.enviandoMsg = false;
                if (d.success) {
                    this.textoMensagem = '';
                    this.removerFoto();
                    this.showEmojiPicker = false;
                    this.mensagensChat.push(d.item);
                    this.rolarParaFimChat();
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

function registerHubAlpine() {
    if (window.Alpine) {
        window.Alpine.data('hubApp', window.hubApp);
    }
}
if (typeof document !== 'undefined') {
    if (window.Alpine) {
        registerHubAlpine();
    } else {
        document.addEventListener('alpine:init', registerHubAlpine);
    }
}
</script>

<div class="max-w-md mx-auto min-h-screen bg-gray-50 flex flex-col" x-data="hubApp()">

    <!-- Top Bar / Header do Estabelecimento -->
    <header class="bg-white border-b border-gray-100 sticky top-0 z-30 px-4 py-3 shadow-xs">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-700 font-extrabold text-base shadow-inner">
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
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200" x-text="'Olá, ' + primeiroNome"></span>
                    </template>
                    <template x-if="!isIdentificado">
                        <button type="button" @click="showIdModal = true" class="text-xs text-emerald-600 font-bold underline cursor-pointer hover:text-emerald-700">Identificar</button>
                    </template>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- BANNER DE BOAS-VINDAS / NOTIFICAÇÃO PUSH -->
    <div class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white px-4 py-3 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-bold m-0 flex items-center gap-1.5">
                <span>⚡</span> <?= $isMesaAtiva ? 'Canal Direto & Comanda Digital' : 'Canal Oficial de Atendimento' ?>
            </p>
            <p class="text-[11px] text-emerald-100 m-0 mt-0.5">
                <?= $isMesaAtiva ? 'Faça pedidos na mesa e acompanhe sua conta em tempo real.' : 'Fale conosco, envie dúvidas, pedidos e receba ofertas exclusivas.' ?>
            </p>
        </div>
        <button type="button" onclick="Notification.requestPermission()" class="text-[11px] bg-white text-emerald-800 font-bold px-2.5 py-1.5 rounded-lg shadow-sm hover:bg-emerald-50 transition-colors whitespace-nowrap cursor-pointer">
            🔔 Ativar Avisos
        </button>
    </div>

    <!-- CONTEÚDO PRINCIPAL (ABAS) -->

    <?php if ($isMesaAtiva): ?>
    <!-- ABA 1: COMANDA & CONTA DA MESA (Somente exibida para mesas ativas / food service) -->
    <section x-show="tab === 'comanda'" class="p-4 space-y-4 flex-1">
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
    </section>
    <?php endif; ?>

    <!-- ABA 2: CANAL DIRETO & CHAT DE ATENDIMENTO ESTILO WHATSAPP -->
    <section x-show="tab === 'feed'" class="flex-1 flex flex-col min-h-[calc(100vh-140px)]" <?= $isMesaAtiva ? 'style="display: none;"' : '' ?>>
        
        <!-- HEADER INTERNO DO CHAT WHATSAPP -->
        <div class="bg-[#008069] text-white px-4 py-2.5 flex items-center justify-between shadow-xs select-none shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full bg-white/20 border border-white/30 flex items-center justify-center text-white font-extrabold text-sm shadow-inner">
                    <?= strtoupper(substr($nomeLoja, 0, 2)) ?>
                </div>
                <div>
                    <h2 class="text-xs sm:text-sm font-bold leading-tight m-0 text-white truncate max-w-[170px]">
                        <?= Html::encode($nomeLoja) ?>
                    </h2>
                    <p class="text-[10px] text-emerald-100 m-0 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full" :class="wsConectado ? 'bg-emerald-300 animate-pulse' : 'bg-amber-300'"></span>
                        <span x-text="wsConectado ? 'Canal Oficial • Tempo Real' : 'Canal Oficial • Online'"></span>
                    </p>
                </div>
            </div>
            <div>
                <template x-if="isIdentificado">
                    <span class="text-[10px] font-bold bg-white/15 text-emerald-100 border border-white/20 px-2 py-0.5 rounded-full" x-text="'Olá, ' + primeiroNome"></span>
                </template>
                <template x-if="!isIdentificado">
                    <button type="button" @click="showIdModal = true" class="text-[10px] font-bold bg-white text-[#008069] px-2 py-0.5 rounded-full shadow-xs hover:bg-emerald-50 cursor-pointer">
                        Identificar-se
                    </button>
                </template>
            </div>
        </div>

        <!-- ÁREA DE MENSAGENS COM FUNDO WHATSAPP CANVAS -->
        <div id="chatMessagesContainer" 
             class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-2.5 min-h-[380px] max-h-[calc(100vh-250px)]" 
             style="background-color: #efeae2; background-image: radial-gradient(#d1d7db 0.75px, transparent 0.75px); background-size: 16px 16px;">

            <!-- Aviso de Criptografia / Canal Direto Seguro -->
            <div class="flex justify-center my-2 select-none">
                <div class="bg-[#ffeecd] text-amber-900 border border-amber-200/60 rounded-xl px-3 py-1.5 text-[10px] text-center max-w-[90%] shadow-2xs leading-tight">
                    🔒 <strong>Canal Seguro de Atendimento Direto</strong><br>
                    Suas mensagens e pedidos são recebidos em tempo real pela nossa equipe.
                </div>
            </div>

            <!-- Estado Vazio (Sem mensagens) -->
            <template x-if="mensagensChat.length === 0">
                <div class="text-center py-12 text-slate-400 space-y-2">
                    <span class="text-3xl block">💬</span>
                    <p class="text-xs font-bold text-slate-600">Nenhuma mensagem ainda</p>
                    <p class="text-[11px] text-slate-500 max-w-xs mx-auto">
                        Envie uma mensagem abaixo para falar com nosso setor de atendimento ou tirar suas dúvidas!
                    </p>
                </div>
            </template>

            <!-- Loop Cronológico de Mensagens (ASC: do mais antigo para o mais recente) -->
            <template x-for="item in mensagensChat" :key="item.id">
                <div class="flex flex-col" :class="item.is_cliente ? 'items-end' : 'items-start'">
                    <div :class="item.is_cliente ? 'bg-[#d9fdd3] text-slate-900 ml-auto rounded-2xl rounded-tr-xs' : 'bg-white text-slate-900 mr-auto rounded-2xl rounded-tl-xs'" 
                         class="px-3 py-2 shadow-xs border border-black/5 space-y-1 relative max-w-[85%] sm:max-w-[75%] animate-in fade-in duration-150">
                        
                        <!-- Identificação do Remetente (se for da Loja/Atendente) -->
                        <template x-if="!item.is_cliente">
                            <div class="flex items-center gap-1.5 text-[11px] font-bold text-[#008069] select-none">
                                <span x-text="item.autor || '<?= Html::encode($nomeLoja) ?>'"></span>
                                <template x-if="item.setor_nome">
                                    <span class="text-[9px] font-extrabold bg-black/5 text-slate-600 px-1.5 py-0.2 rounded-md" x-text="(item.setor_icone || '💬') + ' ' + item.setor_nome"></span>
                                </template>
                            </div>
                        </template>

                        <!-- Foto / Imagem Anexada -->
                        <template x-if="item.midia_url">
                            <div class="pt-0.5">
                                <img :src="item.midia_url" 
                                     alt="Foto anexada" 
                                     @click="modalImagemZoom = item.midia_url" 
                                     class="rounded-xl max-h-60 w-auto object-contain border border-black/10 cursor-pointer shadow-xs hover:opacity-95 transition">
                            </div>
                        </template>

                        <!-- Texto da Mensagem -->
                        <template x-if="item.conteudo_texto">
                            <p class="text-xs sm:text-[13px] leading-relaxed break-words whitespace-pre-wrap m-0 font-normal select-text" x-text="item.conteudo_texto"></p>
                        </template>

                        <!-- Horário e Confirmação de Entrega Estilo WhatsApp -->
                        <div class="flex items-center justify-end gap-1 text-[10px] text-slate-400 select-none pt-0.5">
                            <span x-text="item.hora || item.created_at"></span>
                            <template x-if="item.is_cliente">
                                <span class="text-[#53bdeb] font-black" title="Mensagem entregue">✓✓</span>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- BARRA FIXA DE DIGITAÇÃO E SELEÇÃO DE SETORES (ESTILO WHATSAPP) -->
        <div class="bg-[#f0f2f5] border-t border-slate-300 shrink-0">
            
            <!-- Seletor de Setores (Horizontal sem barras de rolagem cinzas) -->
            <template x-if="setoresLoja && setoresLoja.length > 0">
                <div class="px-3 py-1.5 bg-[#eae6df] border-b border-slate-200/80 flex items-center gap-1.5 overflow-x-auto no-scrollbar select-none">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">Falar com:</span>
                    <template x-for="st in setoresLoja" :key="st.id">
                        <button type="button" 
                                @click="setorSelecionado = st.id" 
                                :class="setorSelecionado === st.id ? 'bg-[#008069] text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'" 
                                class="px-2.5 py-1 rounded-full text-xs font-bold transition flex items-center gap-1 whitespace-nowrap cursor-pointer">
                            <span x-text="st.icone"></span>
                            <span x-text="st.nome"></span>
                        </button>
                    </template>
                </div>
            </template>

            <!-- Barra de Preview de Foto Selecionada -->
            <template x-if="fotoPreview">
                <div class="px-3 py-2 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <img :src="fotoPreview" alt="Preview" class="w-12 h-12 object-cover rounded-xl border border-slate-300 shadow-xs">
                        <div>
                            <span class="text-xs font-bold text-slate-800 block">Foto selecionada</span>
                            <span class="text-[10px] text-slate-500">Clique em enviar para transmitir</span>
                        </div>
                    </div>
                    <button type="button" @click="removerFoto()" class="text-red-600 hover:text-red-800 text-xs font-bold px-2 py-1 bg-red-50 hover:bg-red-100 rounded-lg cursor-pointer">
                        Remover
                    </button>
                </div>
            </template>

            <!-- Seletor de Emojis Rápido -->
            <div x-show="showEmojiPicker" x-cloak class="p-2 bg-white border-b border-slate-200 flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                <template x-for="em in emojisList" :key="em">
                    <button type="button" @click="adicionarEmoji(em)" class="text-base p-1 hover:bg-slate-100 rounded-lg transition hover:scale-125 cursor-pointer flex-shrink-0" x-text="em"></button>
                </template>
            </div>

            <!-- Linha de Entrada de Mensagem (Emoji + Câmera + Input + Botão Redondo) -->
            <div class="p-2 sm:p-2.5 flex items-center gap-2">
                <!-- Botão Emoji -->
                <button type="button" 
                        @click="showEmojiPicker = !showEmojiPicker" 
                        :class="showEmojiPicker ? 'text-[#008069] bg-emerald-100' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200'" 
                        class="w-9 h-9 rounded-full flex items-center justify-center transition cursor-pointer text-base" 
                        title="Emojis">
                    😀
                </button>

                <!-- Input Oculto de Foto -->
                <input type="file" x-ref="inputFoto" @change="selecionarFoto($event)" accept="image/*" class="hidden">

                <!-- Botão Anexar Foto / Câmera -->
                <button type="button" 
                        @click="$refs.inputFoto.click()" 
                        class="w-9 h-9 rounded-full hover:bg-slate-200 text-slate-500 hover:text-slate-700 flex items-center justify-center transition cursor-pointer" 
                        title="Anexar Imagem">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                </button>

                <!-- Input de Mensagem de Texto -->
                <input type="text" 
                       id="inputTextoChat" 
                       x-model="textoMensagem" 
                       @keydown.enter.prevent="enviarMensagemChat()" 
                       placeholder="Mensagem..." 
                       class="flex-1 bg-white border border-slate-300 rounded-2xl px-4 py-2 text-xs sm:text-sm text-slate-800 placeholder-slate-400 outline-none focus:border-[#008069] focus:ring-1 focus:ring-[#008069] transition">

                <!-- Botão Enviar Mensagem (Redondo Verde WhatsApp) -->
                <button type="button" 
                        id="btnEnviarMensagemChatCliente" 
                        @click="enviarMensagemChat()" 
                        :disabled="enviandoMsg || (!textoMensagem.trim() && !fotoFile)" 
                        class="w-10 h-10 rounded-full bg-[#008069] hover:bg-[#006e5a] disabled:opacity-40 text-white flex items-center justify-center transition shadow-md cursor-pointer shrink-0" 
                        title="Enviar">
                    <template x-if="enviandoMsg">
                        <span class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                    </template>
                    <template x-if="!enviandoMsg">
                        <svg class="w-5 h-5 translate-x-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </template>
                </button>
            </div>
        </div>

    </section>

    <!-- ABA 3: CARDÁPIO / CATÁLOGO ONLINE & PEDIDOS -->
    <section x-show="tab === 'cardapio'" class="p-4 space-y-3 flex-1" style="display: none;">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider m-0">Catálogo & Produtos</h2>
            <a href="<?= Html::encode($catalogoUrl) ?>" target="_blank" class="text-xs text-emerald-600 font-bold underline">
                Ver Catálogo Completo &rarr;
            </a>
        </div>

        <div class="bg-white rounded-2xl p-5 text-center border border-gray-100 shadow-sm space-y-3">
            <span class="text-4xl">🛍️</span>
            <h3 class="text-sm font-bold text-gray-800 m-0">Acesse nosso Catálogo Digital</h3>
            <p class="text-xs text-gray-500 m-0">Consulte todos os produtos, preços e ofertas atualizadas para fazer seus pedidos.</p>
            
            <a href="<?= Html::encode($catalogoUrl) ?>" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs shadow-sm hover:bg-emerald-700 transition-colors w-full gap-2">
                <span>Abrir Catálogo Digital</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>

        <!-- Cards Promocionais (se houver) -->
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
                            <a href="<?= Html::encode($catalogoUrl) ?>" class="px-3 py-1.5 bg-emerald-600 text-white rounded-xl text-xs font-bold shadow-xs">
                                Pedir
                            </a>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>

    <!-- MODAL DE ZOOM DE IMAGEM -->
    <div x-show="modalImagemZoom" 
         x-cloak 
         class="fixed inset-0 z-50 bg-black/90 backdrop-blur-sm flex items-center justify-center p-4" 
         @click="modalImagemZoom = null">
        <div class="relative max-w-2xl max-h-[90vh] flex flex-col items-center">
            <button type="button" 
                    @click="modalImagemZoom = null" 
                    class="absolute -top-10 right-0 text-white text-3xl font-light hover:text-gray-300 cursor-pointer">&times;</button>
            <img :src="modalImagemZoom" class="max-h-[85vh] max-w-full rounded-2xl object-contain shadow-2xl" @click.stop>
        </div>
    </div>

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

            <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                Informe seu nome e WhatsApp para falar com nosso atendimento direto, fazer pedidos e receber ofertas exclusivas.
            </p>

            <form @submit.prevent="identificarCliente()" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Seu Nome</label>
                    <input type="text" x-model="nome" placeholder="Ex: Lucas Silva" class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm font-medium outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-gray-50 transition text-gray-900">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Seu WhatsApp (com DDD) *</label>
                    <input type="tel" x-model="telefone" @input="mascaraWhatsapp($event)" required placeholder="Ex: (81) 98888-7777" class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm font-bold outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-gray-50 transition text-gray-900">
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="loadingId" class="w-full py-3 px-4 rounded-xl bg-emerald-600 text-white font-bold text-sm shadow-md hover:bg-emerald-700 transition transform active:scale-98 flex items-center justify-center gap-2 cursor-pointer">
                        <template x-if="loadingId">
                            <span class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                        </template>
                        <span>Acessar Atendimento Oficial</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- BOTTOM TAB BAR NAVEGAÇÃO -->
    <nav class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white border-t border-gray-200 px-6 py-2.5 flex items-center justify-around z-40 shadow-lg">
        <?php if ($isMesaAtiva): ?>
            <button type="button" @click="mudarAba('comanda')" :class="tab === 'comanda' ? 'text-emerald-600 font-bold' : 'text-gray-400 font-medium'" class="flex flex-col items-center gap-1 text-[11px] transition-colors cursor-pointer">
                <span class="text-xl">🧾</span>
                <span>Comanda</span>
            </button>
        <?php endif; ?>

        <button type="button" @click="mudarAba('feed')" :class="tab === 'feed' ? 'text-emerald-600 font-bold' : 'text-gray-400 font-medium'" class="flex flex-col items-center gap-1 text-[11px] transition-colors cursor-pointer">
            <span class="text-xl">💬</span>
            <span>Atendimento &amp; Chat</span>
        </button>

        <button type="button" @click="mudarAba('cardapio')" :class="tab === 'cardapio' ? 'text-emerald-600 font-bold' : 'text-gray-400 font-medium'" class="flex flex-col items-center gap-1 text-[11px] transition-colors cursor-pointer">
            <span class="text-xl">🛍️</span>
            <span>Catálogo &amp; Ofertas</span>
        </button>
    </nav>

</div>
