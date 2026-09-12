// gateway-pagamento.js - VERSÃO COM BOTÃO DE TESTE SANDBOX
// CORREÇÃO: polling resiliente (UUID da venda OU payment_id do MP)
import { GATEWAY_CONFIG, API_ENDPOINTS, CONFIG } from './config.js';
import { validarUUID } from './utils.js';

let pollingIntervalId = null;
let pollingAttempts = 0;
const maxPollingAttempts = 60;
let currentPaymentId = null; // Guardar o payment_id atual


/**
 * CONSULTA STATUS GENÉRICO (PIX OU CARTÃO)
 */
async function verificarStatusVenda(referencia) {
    // Mostra status no modal se existir
    const statusText = document.getElementById('pix-status-text') || document.getElementById('mp-status-text');

    // Se a referência é um UUID, é o ID da venda no Pulse → consulta o endpoint de status do pedido
    if (validarUUID(referencia)) {
        return await verificarStatusVendaUUID(referencia, statusText);
    }

    // Caso contrário, é provavelmente o payment_id do Mercado Pago (numero)
    // → consulta o status diretamente na API do Mercado Pago
    return await verificarStatusPagamentoMP(referencia, statusText);
}

/**
 * Consulta status pelo UUID da venda no Pulse
 */
async function verificarStatusVendaUUID(vendaId, statusText) {
    console.log(`[Gateway] 🔍 Verificando status da venda ${vendaId}... (tentativa ${pollingAttempts})`);

    try {
        const url = `${API_ENDPOINTS.PEDIDO_STATUS}?venda_id=${vendaId}&usuario_id=${CONFIG.ID_USUARIO_LOJA}`;

        const response = await fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });

        if (!response.ok) {
            console.warn(`[Gateway] ⚠️ Endpoint de status respondeu HTTP ${response.status}.`,
                response.status === 500 ? 'Verifique logs do servidor.' : '');
            if (statusText) statusText.textContent = 'Verificando...';
            return { status: 'pendente' };
        }

        const res = await response.json();
        const data = res.data || res;

        // Status "QUITADA" significa sucesso no Pulse
        if (res.sucesso && (data.pago || data.status === 'QUITADA')) {
            return {
                status: 'pago',
                pedido_id: vendaId,
                dados: data
            };
        }

        if (statusText) {
            statusText.textContent = `Status: ${data.status || 'PROCESSANDO...'}`;
        }
        return { status: 'pendente' };

    } catch (error) {
        console.error('[Gateway] ❌ Erro no polling:', error);
        if (statusText) statusText.textContent = 'Erro de rede. Verificando...';
        return { status: 'pendente' };
    }
}

/**
 * Consulta status pelo payment_id do Mercado Pago (fallback quando não temos o UUID)
 */
async function verificarStatusPagamentoMP(paymentId, statusText) {
    console.log(`[Gateway] 🔍 Consultando status do PIX MP ${paymentId}... (tentativa ${pollingAttempts})`);

    try {
        const url = `${API_ENDPOINTS.MERCADOPAGO_CONSULTAR_STATUS_PIX}?payment_id=${paymentId}&tenant_id=${CONFIG.ID_USUARIO_LOJA}`;

        const response = await fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });

        if (!response.ok) {
            console.warn(`[Gateway] ⚠️ Consulta MP respondeu HTTP ${response.status}.`);
            if (statusText) statusText.textContent = 'Verificando...';
            return { status: 'pendente' };
        }

        const res = await response.json();
        console.log('[Gateway] 📡 Status MP:', res);

        // Status aprovado significa pagamento confirmado no MP
        if (res.sucesso && res.status === 'approved') {
            // O payment_id do MP foi salvo referenciado ao pedido (localStorage) ou em window
            const pedidoRealId = window.pedidoPreventivoId
                || localStorage.getItem('mp_external_ref')
                || paymentId;
            return {
                status: 'pago',
                pedido_id: pedidoRealId,
                dados: res
            };
        }

        if (statusText) {
            statusText.textContent = `Status: ${res.status || 'PROCESSANDO...'}`;
        }
        return { status: 'pendente' };

    } catch (error) {
        console.error('[Gateway] ❌ Erro na consulta MP:', error);
        if (statusText) statusText.textContent = 'Erro de rede. Verificando...';
        return { status: 'pendente' };
    }
}

function tratarPagamentoConfirmado(pedidoId, dados, gateway = 'asaas') {
    if (pollingIntervalId) {
        clearInterval(pollingIntervalId);
        pollingIntervalId = null;
    }

    // Fecha modais de aguardando (PIX ou MP)
    const modalPix = document.getElementById('modal-pix-asaas');
    if (modalPix) modalPix.remove();

    const overlayMP = document.getElementById('mp-verificando-overlay');
    if (overlayMP) overlayMP.remove();

    console.log(`[Gateway] ✅ Pagamento confirmado (${gateway})! Pedido:`, pedidoId);

    window.dispatchEvent(new CustomEvent('pagamentoConfirmado', {
        detail: {
            pedidoId: pedidoId,
            gateway: gateway,
            dados: dados
        }
    }));
}

/**
 * INICIA MONITORAMENTO DE QUALQUER VENDA
 */
export function iniciarPollingStatusVenda(vendaId, gateway = 'asaas') {
    if (pollingIntervalId) {
        clearInterval(pollingIntervalId);
    }

    pollingAttempts = 0;

    pollingIntervalId = setInterval(async () => {
        pollingAttempts++;

        if (pollingAttempts > maxPollingAttempts) {
            clearInterval(pollingIntervalId);
            pollingIntervalId = null;
            return;
        }

        // 1️⃣ Tentativa primária: consulta pelo UUID da venda no Pulse
        const resultado = await verificarStatusVenda(vendaId);

        if (resultado.status === 'pago') {
            tratarPagamentoConfirmado(resultado.pedido_id || vendaId, resultado.dados, gateway);
            return;
        }

        // 2️⃣ ✅ FIX: Fallback — consulta diretamente o status no Mercado Pago pelo payment_id numérico
        // Isso garante detecção mesmo se o webhook atrasar ou a venda ainda não foi quitada no banco
        if (gateway === 'mercadopago' && window.mpPaymentIdNumerico) {
            const statusText = document.getElementById('pix-status-text');
            const resultadoMP = await verificarStatusPagamentoMP(
                window.mpPaymentIdNumerico,
                statusText
            );
            if (resultadoMP.status === 'pago') {
                console.log('[Gateway] ✅ Pagamento confirmado via consulta direta ao MP (fallback)');
                tratarPagamentoConfirmado(
                    resultadoMP.pedido_id || vendaId,
                    resultadoMP.dados,
                    gateway
                );
            }
        }

    }, 5000);
}

window.cancelarPollingPix = function () {
    if (pollingIntervalId) {
        clearInterval(pollingIntervalId);
        pollingIntervalId = null;
        console.log('[Gateway] 🛑 Polling cancelado pelo usuário.');
    }
    const modalPix = document.getElementById('modal-pix-asaas');
    if (modalPix) modalPix.remove();

    // Reabilita o botão de confirmação e garante sincronização do carrinho
    const btnConfirmar = document.getElementById('btn-confirmar-pedido');
    if (btnConfirmar) {
        btnConfirmar.disabled = false;
        btnConfirmar.textContent = '✅ Confirmar Pedido';
    }
    if (typeof window.atualizarBadgeCarrinho === 'function') {
        window.atualizarBadgeCarrinho();
    }
};

// ✅ FUNÇÃO PARA TESTE MANUAL NO SANDBOX
window.simularPagamentoSandbox = async function () {
    console.log('[Gateway] 🧪 TESTE SANDBOX: Forçando verificação manual...');

    const btnTeste = document.getElementById('btn-teste-sandbox');
    if (btnTeste) {
        btnTeste.disabled = true;
        btnTeste.textContent = '🔄 Verificando...';
    }

    if (!currentPaymentId) {
        alert('Erro: ID da venda não encontrado para verificação.');
        if (btnTeste) {
            btnTeste.disabled = false;
            btnTeste.textContent = '🧪 Testar Confirmação';
        }
        return;
    }

    // Força uma verificação imediata via status da venda/pedido
    const resultado = await verificarStatusVenda(currentPaymentId);

    if (resultado.status === 'pago') {
        console.log('[Gateway] ✅ TESTE SANDBOX: Pagamento confirmado!');
        tratarPagamentoConfirmado(resultado.pedido_id || currentPaymentId, resultado.dados, window.currentGateway || 'asaas');
    } else {
        alert(`Status atual no sistema: ${resultado.dados?.status || 'PENDENTE'}\n\nSe você já confirmou no sandbox, aguarde alguns segundos e clique novamente.`);
        if (btnTeste) {
            btnTeste.disabled = false;
            btnTeste.textContent = '🧪 Testar Confirmação';
        }
    }
}

export async function processarPagamento(dadosPedido, carrinho, cliente, pedidoId = null) {
    // Prioridade: window.GATEWAY_CONFIG (carregado pelo app.js após carregarConfigLoja)
    // Fallback: GATEWAY_CONFIG importado do módulo (pode estar como 'nenhum' por padrão)
    const gateway = (window.GATEWAY_CONFIG?.gateway && window.GATEWAY_CONFIG.gateway !== 'nenhum')
        ? window.GATEWAY_CONFIG.gateway
        : GATEWAY_CONFIG.gateway;

    console.log('[Gateway] 💳 Processando pagamento via:', gateway, 'PedidoID:', pedidoId);
    console.log('[Gateway] 🔍 window.GATEWAY_CONFIG:', JSON.stringify(window.GATEWAY_CONFIG));
    console.log('[Gateway] 🔍 GATEWAY_CONFIG (módulo):', JSON.stringify(GATEWAY_CONFIG));

    // ✅ FIX #2: Cartão de crédito/débito NÃO usa o fluxo PIX do gateway.
    // Checkout transparente de cartão não está implementado — usa fluxo interno.
    const formaPagamentoId = dadosPedido?.forma_pagamento_id;
    const formasPagamento = window.formasPagamento || [];
    const formaSelecionada = formasPagamento.find(f => f.id === formaPagamentoId);
    const tipoFormaPagamento = (formaSelecionada?.tipo || '').toUpperCase().trim();
    const isCartao = ['CARTAO_CREDITO', 'CARTAO_DEBITO', 'CARTAO'].includes(tipoFormaPagamento);

    if (isCartao) {
        console.log('[Gateway] 💳 Forma de pagamento é CARTÃO — iniciando checkout transparente Mercado Pago.');
        return await processarCartaoMercadoPago(dadosPedido, carrinho, cliente, pedidoId);
    }

    switch (gateway) {
        case 'mercadopago':
            return await processarMercadoPago(dadosPedido, carrinho, cliente, pedidoId);

        case 'asaas':
            return await processarAsaas(dadosPedido, carrinho, cliente, pedidoId);

        case 'nenhum':
        default:
            return await processarFluxoInterno(dadosPedido, carrinho);
    }
}


async function processarMercadoPago(dadosPedido, carrinho, cliente, pedidoId = null) {
    try {
        const valorTotal = carrinho.reduce((total, item) =>
            total + ((item.preco_venda_sugerido || item.preco || 0) * (item.quantidade || 1)), 0
        );

        // ✅ CRÍTICO: o order.js garante que pedidoId é o UUID da venda preventiva.
        // Sem ele não há como vincular o pagamento aprovado à venda no back-end.
        if (!validarUUID(pedidoId)) {
            throw new Error('ID do pedido preventivo inválido ou ausente. Por favor, tente novamente.');
        }

        const payload = {
            tenant_id: CONFIG.ID_USUARIO_LOJA,
            order_id: pedidoId,
            amount: valorTotal,
            description: `Pedido ${pedidoId} - Catálogo`,
            cliente: {
                nome: cliente.nome || cliente.nome_completo || '',
                sobrenome: cliente.sobrenome || '',
                email: cliente.email || '',
                telefone: cliente.telefone || '',
                cpf: cliente.cpf_cnpj || cliente.cpf || '',
                cep: cliente.cep || '',
                logradouro: cliente.logradouro || '',
                numero: cliente.numero || ''
            }
        };

        console.log('[MP] 🚀 Enviando requisição de PIX Transparente para Mercado Pago:', payload);

        const response = await fetch(API_ENDPOINTS.MERCADOPAGO_CRIAR_PIX_SPLIT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            let erroMsg = 'Erro ao processar pagamento com o Mercado Pago. Por favor, tente novamente ou entre em contato com a loja.';
            try {
                const erro = await response.json();
                erroMsg = erro.erro || erro.mensagem || erroMsg;
            } catch (e) { }
            throw new Error(erroMsg);
        }

        const resultado = await response.json();
        console.log('[MP] 📡 Resposta recebida:', resultado);

        if (resultado.sucesso && resultado.qr_code) {
            // Salvar referências no localStorage
            localStorage.setItem('mp_payment_id', resultado.payment_id);
            localStorage.setItem('mp_external_ref', pedidoId);

            // Set variables for global sandbox use
            window.currentGateway = 'mercadopago';
            window.pedidoPreventivoId = pedidoId;
            currentPaymentId = pedidoId;
            // ✅ FIX: Salva o payment_id NUMÉRICO do MP para uso no polling de fallback
            window.mpPaymentIdNumerico = resultado.payment_id;

            // Adapt to the structure expected by mostrarModalPix
            const pixData = {
                encoded_image: resultado.qr_code_base64,
                payload: resultado.qr_code
            };

            mostrarModalPix(pixData, resultado.payment_id);

            // ✅ Inicia polling SEMPRE com o UUID da venda (pedidoId)
            // Se por algum motivo o UUID não estiver disponível, o fallback internamente
            // consulta o payment_id diretamente na API do Mercado Pago.
            const refPolling = pedidoId || resultado.external_reference || resultado.payment_id;
            iniciarPollingStatusVenda(refPolling, 'mercadopago');

            return {
                sucesso: true,
                gateway: 'mercadopago',
                mensagem: 'Modal PIX exibido. Aguardando pagamento.',
                ...resultado
            };
        } else {
            throw new Error(resultado.mensagem || resultado.erro || 'Não foi possível gerar o QR Code PIX.');
        }

    } catch (error) {
        console.error('[MP] ❌ Erro:', error);
        alert(`Falha no pagamento: ${error.message}`);
        throw error;
    }
}

// Processa pagamento com cartão de crédito/débito via Mercado Pago Checkout Transparente
async function processarCartaoMercadoPago(dadosPedido, carrinho, cliente, pedidoId = null) {
    try {
        const valorTotal = carrinho.reduce((total, item) =>
            total + ((item.preco_venda_sugerido || item.preco || 0) * (item.quantidade || 1)), 0
        );

        if (!pedidoId) {
            throw new Error('ID do pedido preventivo ausente para pagamento com cartão.');
        }

        // ✅ Verifica se o token foi gerado pelo CardForm
        if (!window.mpCardToken) {
            throw new Error('Token do cartão não encontrado. Por favor, preencha os dados do cartão.');
        }

        const isDebito = (window.mpTipoCartao === 'debit_card') ||
                         (dadosPedido?.tipo_cartao === 'debit_card') ||
                         (dadosPedido?.forma_pagamento_tipo === 'CARTAO_DEBITO') ||
                         (dadosPedido?.forma_pagamento_nome?.toLowerCase()?.includes('débito')) ||
                         (dadosPedido?.forma_pagamento_nome?.toLowerCase()?.includes('debito'));
        const tipoCartao = isDebito ? 'debit_card' : 'credit_card';

        const payload = {
            tenant_id:         CONFIG.ID_USUARIO_LOJA,
            order_id:          pedidoId,
            amount:            valorTotal,
            token:             window.mpCardToken,
            installments:      isDebito ? 1 : (window.mpInstallments || 1),
            payment_method_id: window.mpPaymentMethodId || null,  // bandeira: 'visa', 'master', etc.
            issuer_id:         window.mpIssuerId        || null,  // banco emissor
            tipo_cartao:       tipoCartao,
            payment_type_id:   tipoCartao,
            description:       `Pedido ${pedidoId} - Cartão (${isDebito ? 'Débito' : 'Crédito'})`,
            cliente: {
                nome:      cliente.nome || cliente.nome_completo || '',
                sobrenome: cliente.sobrenome || '',
                email:     cliente.email || '',
                telefone:  cliente.telefone || '',
                cpf:       cliente.cpf_cnpj || cliente.cpf || '',
                cep:       cliente.cep || '',
                logradouro: cliente.logradouro || '',
                numero:    cliente.numero || ''
            }
        };

        console.log('[MP Cartão] 🚀 Enviando pagamento ao backend (' + tipoCartao + '):', {
            ...payload,
            token: payload.token ? `${payload.token.substring(0, 8)}...` : null
        });

        const response = await fetch(API_ENDPOINTS.MERCADOPAGO_PAGAR_CARTAO, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        let result = null;
        if (!response.ok) {
            let erroMsg = 'Pagamento com cartão não foi aprovado pela operadora.';
            try {
                const err = await response.json();
                erroMsg = err.mensagem || err.erro || erroMsg;
            } catch (_) {}

            if (typeof erroMsg === 'string' && (erroMsg.includes('not_result_by_params') || erroMsg.includes('No result found'))) {
                erroMsg = 'Este cartão não é aceito para compras no débito online. No Brasil, o Mercado Pago autoriza débito online direto apenas para cartões compatíveis (ex: Elo Débito). Por favor, selecione "Cartão de Crédito" ou finalize via PIX.';
            }

            result = { sucesso: false, status: 'rejected', mensagem: erroMsg };
        } else {
            result = await response.json();
        }

        console.log('[MP Cartão] 📡 Resposta:', result);

        // Limpar token e dados de cartão após processamento
        window.mpCardToken       = null;
        window.mpInstallments    = null;
        window.mpPaymentMethodId = null;
        window.mpIssuerId        = null;
        window.mpTipoCartao      = null;

        // 1️⃣ CASO APROVADO: Dispara confirmação da venda online
        if (result.sucesso && result.status === 'approved') {
            _mostrarMensagemCartao(
                '✅ Pagamento Aprovado!',
                'Seu pagamento com cartão foi confirmado com sucesso. O pedido está sendo preparado!',
                'success'
            );

            tratarPagamentoConfirmado(pedidoId, result, 'mercadopago');
            return {
                sucesso: true,
                gateway: 'mercadopago',
                status: 'approved',
                mensagem: 'Pagamento aprovado com sucesso!',
                dados: { ...result, id: pedidoId, venda_id: pedidoId }
            };
        }

        // 1.5️⃣ CASO 3DS CHALLENGE: Requer autenticação do banco emissor
        if (result.status === 'requires_action' && result.three_ds_url) {
            console.log('[MP Cartão] 🔐 Desafio 3DS detectado:', result.three_ds_url);
            return await exibirModalDesafio3DSCatalogo(result.three_ds_url, result.payment_id, pedidoId, dadosPedido, carrinho, cliente);
        }

        // 2️⃣ CASO EM ANÁLISE / PENDENTE (Anti-fraude)
        if (!result.sucesso && (result.status === 'in_process' || result.status === 'pending')) {
            _mostrarMensagemCartao(
                '⏳ Pagamento em Análise',
                result.mensagem || 'Seu pagamento está em análise pelo Mercado Pago. Você será notificado.',
                'warning'
            );
            return { sucesso: false, gateway: 'mercadopago', status: result.status, dados: result };
        }

        // 3️⃣ CASO RECUSADO / NÃO APROVADO: Oferece tentar outro cartão ou ir para o PIX
        const msgRecusa = result.mensagem || 'Pagamento não aprovado pelo cartão.';
        console.warn('[MP Cartão] ⚠️ Pagamento recusado:', msgRecusa);

        const decisao = await exibirModalDecisaoRecusa(msgRecusa);

        if (decisao === 'pix') {
            console.log('[MP Cartão] ⚡ Cliente optou por pagar com PIX após recusa do cartão.');
            // Redireciona para o fluxo PIX do Mercado Pago usando o mesmo pedido preventivo
            return await processarMercadoPago(dadosPedido, carrinho, cliente, pedidoId);
        } else {
            console.log('[MP Cartão] 💳 Cliente optou por tentar com outro cartão.');
            // Retorna insucesso mas com status rejected para permitir reabertura do formulário de cartão
            return {
                sucesso: false,
                gateway: 'mercadopago',
                status: 'rejected',
                mensagem: msgRecusa,
                acao: 'outro_cartao'
            };
        }

    } catch (error) {
        console.error('[MP Cartão] ❌ Erro:', error);
        window.mpCardToken       = null;
        window.mpInstallments    = null;
        window.mpPaymentMethodId = null;
        window.mpIssuerId        = null;

        // Fallback de segurança para PIX caso ocorra qualquer falha na comunicação de cartão
        console.log('[MP Cartão] 🔄 Tentando fallback para PIX devido a erro de conexão...');
        alert('Houve uma falha ao comunicar com a operadora do cartão. Redirecionando para pagamento via PIX...');
        return await processarMercadoPago(dadosPedido, carrinho, cliente, pedidoId);
    }
}

/**
 * Exibe o desafio 3DS (Three-D Secure) no Catálogo com iframe embutido e botão de abertura externa
 */
function exibirModalDesafio3DSCatalogo(threeDsUrl, paymentId, pedidoId, dadosPedido, carrinho, cliente) {
    return new Promise((resolve) => {
        const modalExistente = document.getElementById('modal-3ds-catalogo');
        if (modalExistente) modalExistente.remove();

        const modal = document.createElement('div');
        modal.id = 'modal-3ds-catalogo';
        modal.className = 'fixed inset-0 bg-black/85 backdrop-blur-md flex items-center justify-center z-[200] p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-2xl p-6 max-w-lg w-full mx-auto shadow-2xl space-y-4 text-center">
                <div class="flex items-center justify-between border-b pb-3 text-left">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">🔐</span>
                        <div>
                            <h3 class="text-lg font-black text-gray-900 leading-tight">Autenticação do Banco (3DS)</h3>
                            <p class="text-xs text-brand-600 font-bold">Confirmação de segurança do seu cartão</p>
                        </div>
                    </div>
                    <button type="button" id="btn-fechar-3ds-cat" class="text-gray-400 hover:text-gray-700 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="bg-amber-50 border border-amber-200 p-3 rounded-xl text-left space-y-1">
                    <div class="flex items-center gap-2 text-amber-800 font-bold text-xs">
                        <span>🛡️</span>
                        <span>Seu banco solicitou a confirmação desta compra.</span>
                    </div>
                    <p class="text-[11px] text-amber-700">Autorize no quadro abaixo ou clique no botão para abrir no app/site do seu banco.</p>
                </div>

                <div class="rounded-xl overflow-hidden border border-gray-200 bg-gray-50 min-h-[320px] relative">
                    <iframe src="${threeDsUrl}" id="iframe-3ds-cat" class="w-full h-80 border-0 rounded-xl" allow="payment"></iframe>
                </div>

                <div class="flex flex-col gap-2">
                    <a href="${threeDsUrl}" target="_blank" rel="noopener noreferrer" class="w-full py-3 px-4 bg-brand-600 hover:bg-brand-700 text-white text-xs font-black rounded-xl text-center shadow transition flex items-center justify-center gap-2">
                        <span>📲 Abrir tela do Banco em nova aba</span>
                        <span>↗</span>
                    </a>
                    <div id="status-3ds-cat-texto" class="text-center text-xs font-bold text-gray-500 py-1 flex items-center justify-center gap-2">
                        <span class="inline-block animate-spin">⏳</span>
                        <span>Aguardando você confirmar no banco...</span>
                    </div>
                    <button type="button" id="btn-cancelar-3ds-cat" class="w-full py-2 text-gray-500 hover:text-red-600 text-xs font-bold transition">
                        Cancelar e tentar outra forma de pagamento
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        let pollInterval = null;
        let attempts = 0;
        const maxAttempts = 80;

        const cleanup = () => {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = null;
        };

        const fecharModal = async () => {
            cleanup();
            modal.remove();
            const decisao = await exibirModalDecisaoRecusa('Autenticação no banco foi cancelada.');
            if (decisao === 'pix') {
                resolve(await processarMercadoPago(dadosPedido, carrinho, cliente, pedidoId));
            } else {
                resolve({ sucesso: false, gateway: 'mercadopago', status: 'cancelled' });
            }
        };

        const btnFechar = modal.querySelector('#btn-fechar-3ds-cat');
        if (btnFechar) btnFechar.onclick = fecharModal;

        const btnCancelar = modal.querySelector('#btn-cancelar-3ds-cat');
        if (btnCancelar) btnCancelar.onclick = fecharModal;

        pollInterval = setInterval(async () => {
            attempts++;
            if (attempts > maxAttempts) {
                cleanup();
                const statusEl = modal.querySelector('#status-3ds-cat-texto');
                if (statusEl) statusEl.innerHTML = '<span class="text-red-600 font-bold">Tempo limite esgotado. Verifique no seu banco.</span>';
                return;
            }

            try {
                const statusRes = await verificarStatusPagamentoMP(paymentId, null);
                if (statusRes.status === 'pago') {
                    cleanup();
                    const statusEl = modal.querySelector('#status-3ds-cat-texto');
                    if (statusEl) statusEl.innerHTML = '✅ <span class="text-emerald-700 font-bold">Pagamento Confirmado pelo Banco!</span>';

                    setTimeout(() => {
                        modal.remove();
                        tratarPagamentoConfirmado(pedidoId, statusRes.dados, 'mercadopago');
                        resolve({
                            sucesso: true,
                            gateway: 'mercadopago',
                            status: 'approved',
                            mensagem: 'Pagamento aprovado com sucesso!',
                            dados: { ...statusRes.dados, id: pedidoId, venda_id: pedidoId }
                        });
                    }, 1000);
                }
            } catch (err) {
                console.warn('[Catálogo 3DS] Erro no polling:', err);
            }
        }, 2500);
    });
}

/**
 * Exibe modal moderno e intuitivo de decisão quando o pagamento com cartão é recusado.
 * Permite que o cliente escolha entre '⚡ Pagar com PIX' ou '💳 Outro Cartão'.
 * Retorna uma Promise que resolve em 'pix' ou 'outro_cartao'.
 */
function exibirModalDecisaoRecusa(msgRecusa) {
    return new Promise((resolve) => {
        if (typeof msgRecusa === 'string' && (
            msgRecusa.includes('not_result_by_params') || 
            msgRecusa.includes('No result found') ||
            (msgRecusa.includes('débito') && (msgRecusa.includes('Número do cartão') || msgRecusa.includes('autorizou')))
        )) {
            msgRecusa = 'Este cartão não autoriza compras no débito online nesta operadora. Recomendamos selecionar a opção "Cartão de Crédito" (cobrança à vista no saldo/limite da conta sem juros) ou finalizar via PIX.';
        }

        // Remove modal anterior caso exista
        const modalExistente = document.getElementById('modal-decisao-recusa-cartao');
        if (modalExistente) modalExistente.remove();

        const modal = document.createElement('div');
        modal.id = 'modal-decisao-recusa-cartao';
        modal.style.cssText = `
            position: fixed;
            inset: 0;
            z-index: 999999;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            animation: mpFadeIn 0.2s ease-out;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;

        modal.innerHTML = `
            <style>
                @keyframes mpFadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes mpScaleIn {
                    from { opacity: 0; transform: scale(0.95) translateY(6px); }
                    to { opacity: 1; transform: scale(1) translateY(0); }
                }
                .modal-recusa-card {
                    background: #ffffff;
                    border-radius: 20px;
                    width: 100%;
                    max-width: 440px;
                    padding: 24px 20px 20px;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
                    animation: mpScaleIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
                    position: relative;
                    box-sizing: border-box;
                }
                .btn-recusa-action {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    padding: 13px 16px;
                    min-height: 48px;
                    border-radius: 12px;
                    font-size: 15px;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    flex: 1;
                    min-width: 140px;
                    text-align: center;
                    border: none;
                    box-sizing: border-box;
                    text-decoration: none;
                }
                .btn-recusa-outro-cartao {
                    background: #f1f5f9;
                    color: #334155;
                    border: 1.5px solid #cbd5e1;
                }
                .btn-recusa-outro-cartao:hover {
                    background: #e2e8f0;
                    border-color: #94a3b8;
                    color: #0f172a;
                }
                .btn-recusa-pagar-pix {
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    color: #ffffff;
                    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);
                }
                .btn-recusa-pagar-pix:hover {
                    filter: brightness(1.08);
                    transform: translateY(-1px);
                    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.5);
                }
                @media (max-width: 420px) {
                    .btn-recusa-actions-container {
                        flex-direction: column !important;
                    }
                    .btn-recusa-action {
                        width: 100% !important;
                        flex: none !important;
                    }
                }
            </style>
            <div class="modal-recusa-card">
                <button id="btn-recusa-fechar" style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:22px;color:#94a3b8;cursor:pointer;line-height:1;padding:4px;border-radius:6px;" title="Fechar">&times;</button>
                
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                    <div style="width:44px;height:44px;border-radius:12px;background:#fef3c7;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                        ⚠️
                    </div>
                    <div>
                        <h3 style="font-size:17px;font-weight:800;color:#0f172a;margin:0 0 2px;">Cartão Não Aprovado</h3>
                        <span style="font-size:12px;font-weight:600;color:#d97706;text-transform:uppercase;letter-spacing:0.5px;">Aviso da Operadora</span>
                    </div>
                </div>

                <div style="background:#fff1f2;border:1px solid #ffe4e6;border-left:4px solid #f43f5e;border-radius:10px;padding:12px 14px;margin-bottom:16px;">
                    <p style="margin:0;font-size:13.5px;line-height:1.45;color:#9f1239;font-weight:600;">
                        "${msgRecusa || 'Não foi possível autorizar o pagamento com este cartão.'}"
                    </p>
                </div>

                <p style="font-size:14px;line-height:1.5;color:#475569;margin:0 0 20px;">
                    Deseja pagar com <strong>PIX</strong> para concluir seu pedido imediatamente ou tentar com <strong>outro cartão</strong>?
                </p>

                <div class="btn-recusa-actions-container" style="display:flex;gap:12px;width:100%;">
                    <button type="button" id="btn-recusa-outro-cartao" class="btn-recusa-action btn-recusa-outro-cartao">
                        💳 Outro Cartão
                    </button>
                    <button type="button" id="btn-recusa-pagar-pix" class="btn-recusa-action btn-recusa-pagar-pix">
                        ⚡ Pagar com PIX
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        let finalizado = false;
        const fechar = (opcao) => {
            if (finalizado) return;
            finalizado = true;
            document.removeEventListener('keydown', onKeyDown);
            modal.remove();
            resolve(opcao);
        };

        const onKeyDown = (e) => {
            if (e.key === 'Escape') {
                fechar('outro_cartao');
            }
        };
        document.addEventListener('keydown', onKeyDown);

        // Clique no fundo (backdrop)
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                fechar('outro_cartao');
            }
        });

        // Botão Fechar X
        const btnFechar = modal.querySelector('#btn-recusa-fechar');
        if (btnFechar) {
            btnFechar.onclick = () => fechar('outro_cartao');
        }

        // Botão Outro Cartão
        const btnOutroCartao = modal.querySelector('#btn-recusa-outro-cartao');
        if (btnOutroCartao) {
            btnOutroCartao.onclick = () => fechar('outro_cartao');
        }

        // Botão Pagar com PIX
        const btnPagarPix = modal.querySelector('#btn-recusa-pagar-pix');
        if (btnPagarPix) {
            btnPagarPix.onclick = () => fechar('pix');
        }
    });
}

/**
 * Exibe mensagem informativa para o usuário sobre o status do pagamento de cartão.
 */
function _mostrarMensagemCartao(titulo, mensagem, tipo = 'info') {
    const cores = {
        info:    '#3B82F6',
        warning: '#F59E0B',
        error:   '#EF4444',
        success: '#10B981',
    };
    const cor = cores[tipo] || cores.info;

    const div = document.createElement('div');
    div.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 999999;
        background: white; border-left: 4px solid ${cor};
        padding: 16px 20px; border-radius: 8px; max-width: 360px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15); font-family: sans-serif;
    `;
    div.innerHTML = `
        <strong style="display:block;margin-bottom:6px;color:${cor}">${titulo}</strong>
        <span style="color:#374151;font-size:14px">${mensagem}</span>
    `;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 7000);
}


async function processarAsaas(dadosPedido, carrinho, cliente, pedidoId = null) {
    try {
        const valorTotal = carrinho.reduce((total, item) =>
            total + ((item.preco_venda_sugerido || 0) * (item.quantidade || 1)), 0
        );

        const payload = {
            usuario_id: CONFIG.ID_USUARIO_LOJA || null,
            cliente_id: dadosPedido.cliente_id || null,
            valor: valorTotal || 0,
            descricao: `Pedido PWA - ${carrinho.length} item(ns)`,
            metodo_pagamento: 'PIX',
            vencimento: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            colaborador_id: dadosPedido.colaborador_vendedor_id || null,
            external_reference: pedidoId, // ✅ Vincula ao pedido já criado no Pulse

            itens: carrinho.map(item => ({
                produto_id: item.produto_id || item.id || null,
                nome: item.nome || 'Produto',
                quantidade: item.quantidade || 1,
                preco_unitario: item.preco_venda_sugerido || 0
            })),

            cliente: {
                nome: cliente.nome || '',
                email: cliente.email || '',
                cpf: cliente.cpf_cnpj || '',
                telefone: cliente.telefone || '',
                cep: cliente.cep || '',
                endereco: cliente.logradouro || '',
                numero: cliente.numero || '',
                complemento: cliente.complemento || '',
                bairro: cliente.bairro || '',
                cidade: cliente.cidade || '',
                estado: cliente.estado || ''
            }
        };

        const response = await fetch(API_ENDPOINTS.ASAAS_CRIAR_COBRANCA, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            const erro = await response.json();
            throw new Error(erro.erro || 'Erro ao criar cobrança');
        }

        const responseJson = await response.json();
        const resultado = responseJson.data || responseJson;

        localStorage.setItem('asaas_payment_id', resultado.payment_id);
        localStorage.setItem('asaas_external_ref', resultado.external_reference);

        if (resultado.pix) {
            window.currentGateway = 'asaas';
            window.pedidoPreventivoId = pedidoId;
            currentPaymentId = pedidoId || resultado.payment_id;

            mostrarModalPix(resultado.pix, resultado.payment_id);
            // Inicia polling usando o pedidoId (external_reference) se disponível, senão usa payment_id
            iniciarPollingStatusVenda(pedidoId || resultado.payment_id, 'asaas');

            return {
                sucesso: true,
                gateway: 'asaas',
                mensagem: 'Modal PIX exibido. Aguardando pagamento.',
                ...resultado
            };
        }

        return {
            sucesso: true,
            gateway: 'asaas',
            mensagem: 'Cobrança gerada com sucesso!',
            ...resultado
        };

    } catch (error) {
        console.error('[Asaas] ❌ Erro:', error);
        throw error;
    }
}

async function processarFluxoInterno(dadosPedido, carrinho) {
    const { finalizarPedido } = await import('./order.js');
    return await finalizarPedido(dadosPedido, carrinho);
}

function mostrarModalPix(pixData, paymentId) {
    // Fechar modal de pedido para o PIX ficar na frente
    const modalPedido = document.getElementById('modal-cliente-pedido');
    if (modalPedido) {
        modalPedido.style.display = 'none';
    }

    // Remover modal anterior se existir (evitar duplicatas)
    const modalExistente = document.getElementById('modal-pix-asaas');
    if (modalExistente) modalExistente.remove();

    const isSandbox = (window.currentGateway === 'mercadopago' && window.GATEWAY_CONFIG?.mercadopago_sandbox) ||
        (window.currentGateway === 'asaas' && window.GATEWAY_CONFIG?.asaas_sandbox);

    const modal = document.createElement('div');
    modal.id = 'modal-pix-asaas';
    modal.className = 'fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center p-4';
    modal.style.cssText = 'z-index: 99999 !important; position: fixed !important; top: 0; left: 0; right: 0; bottom: 0;';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-auto">
            <h2 class="text-2xl font-bold mb-4 text-center">Pagamento via PIX</h2>
            
            ${pixData.encoded_image ? `
                <img src="data:image/png;base64,${pixData.encoded_image}" 
                     alt="QR Code PIX" 
                     class="w-full max-w-[250px] mx-auto mb-4 border rounded-lg">
            ` : ''}
            
            <div class="bg-gray-100 p-3 rounded mb-4">
                <p class="text-xs text-gray-600 mb-1">Código PIX Copia e Cola:</p>
                <p class="text-sm font-mono break-all">${pixData.payload}</p>
            </div>
            
            <button onclick="navigator.clipboard.writeText('${pixData.payload}')" 
                    class="w-full bg-brand-500 text-white py-3 rounded-lg mb-4 hover:bg-brand-600">
                📋 Copiar Código PIX
            </button>
            
            ${isSandbox ? `
            <!-- ✅ BOTÃO DE TESTE SANDBOX -->
            <button id="btn-teste-sandbox" onclick="window.simularPagamentoSandbox()" 
                    class="w-full bg-orange-500 text-white py-3 rounded-lg mb-4 hover:bg-orange-600 border-2 border-orange-700">
                🧪 Testar Confirmação (Sandbox)
            </button>
            ` : ''}
            
            <div class="text-center bg-gray-50 p-3 rounded-lg">
                <p id="pix-status-text" class="text-sm text-gray-700 font-medium">
                    Aguardando confirmação do pagamento...
                </p>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                    <div class="bg-brand-500 h-1.5 rounded-full animate-pulse"></div>
                </div>
            </div>
            
            <p class="text-xs text-gray-500 text-center mt-3 mb-2">
                Payment ID: ${paymentId}
            </p>
            
            <button onclick="window.cancelarPollingPix()" 
                    class="w-full text-gray-500 text-sm py-2 mt-3 hover:text-gray-700">
                Fechar
            </button>
        </div>
    `;

    document.body.appendChild(modal);
}