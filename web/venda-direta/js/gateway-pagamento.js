// gateway-pagamento.js - Gateway de pagamento para VENDA-DIRETA
import { GATEWAY_CONFIG, API_ENDPOINTS, CONFIG } from './config.js';
import { fetchWithAuth } from './api.js';
import { getToken } from './storage.js';

/**
 * Processa pagamento via gateway externo ou fluxo interno
 */
export async function processarPagamento(dadosPedido, carrinho, cliente) {
    const gateway = GATEWAY_CONFIG.gateway;
    
    console.log('[Gateway] 💳 Processando pagamento via:', gateway);
    
    switch (gateway) {
        case 'mercadopago':
            return await processarMercadoPago(dadosPedido, carrinho, cliente);
            
        case 'asaas':
            return await processarAsaas(dadosPedido, carrinho, cliente);
            
        case 'nenhum':
        default:
            return await processarFluxoInterno(dadosPedido, carrinho);
    }
}

/**
 * MERCADO PAGO - VENDA DIRETA
 * Para venda direta, o pagamento via Mercado Pago funciona como:
 * - Cliente paga no checkout do MP
 * - Após pagamento confirmado, a venda é criada automaticamente via webhook
 * - O vendedor recebe confirmação na tela
 */
async function processarMercadoPago(dadosPedido, carrinho, cliente) {
    const formasPagamento = window.formasPagamento || [];
    const formaSelecionada = formasPagamento.find(f => f.id === dadosPedido.forma_pagamento_id);
    const tipo = dadosPedido.tipo_pagamento_selecionado || (formaSelecionada ? formaSelecionada.tipo : '');
    const nome = formaSelecionada ? (formaSelecionada.nome || '').toLowerCase() : '';

    console.log('[MP] 💳 Selecionado:', { tipo, nome, dadosPedido });

    // 1. Point Maquininha física
    if (tipo === 'MP_POINT') {
        return await processarMercadoPagoPoint(dadosPedido, carrinho, cliente);
    }

    // 2. Pix Dinâmico com Split e Baixa Automática
    if (tipo === 'PIX' || tipo === 'PIX_MERCADOPAGO' || nome.includes('pix')) {
        return await processarMercadoPagoPixDinamico(dadosPedido, carrinho, cliente);
    }

    // 3. Carteira Digital / Aproximação / Google Pay / Apple Pay / 1-Clique
    if (tipo === 'MP_WALLET' || tipo === 'CARTEIRA_DIGITAL' || nome.includes('carteira') || nome.includes('aproximação') || nome.includes('aproximacao')) {
        return await processarMercadoPagoCarteiraDigital(dadosPedido, carrinho, cliente);
    }

    // 4. Cartão Online ou Geral Mercado Pago
    return await processarMercadoPagoCartaoOnline(dadosPedido, carrinho, cliente);
}

/**
 * MERCADO PAGO - PIX DINÂMICO COM SPLIT E BAIXA AUTOMÁTICA
 */
async function processarMercadoPagoPixDinamico(dadosPedido, carrinho, cliente) {
    try {
        console.log('[MP Pix] ⚡ Iniciando fluxo de Pix Dinâmico transparente...');

        // 1. Criar pré-registro do pedido para obter UUID válido
        const { finalizarPedido } = await import('./order.js');
        const backupHabilitado = GATEWAY_CONFIG.habilitado;
        GATEWAY_CONFIG.habilitado = false; 

        const respPedido = await finalizarPedido(dadosPedido, carrinho);
        GATEWAY_CONFIG.habilitado = backupHabilitado;

        if (!respPedido.sucesso) {
            throw new Error('Falha ao registrar pedido antes de gerar Pix: ' + (respPedido.erro || 'Erro desconhecido'));
        }

        const pedidoId = respPedido.dados?.id || respPedido.dados?.venda?.id || respPedido.dados?.venda_id;
        const valorTotal = carrinho.reduce((t, i) => t + ((i.preco_final || i.preco_venda_sugerido) * i.quantidade), 0);

        // 2. Chamar endpoint de criação de Pix com Split
        console.log('[MP Pix] ⚡ Chamando criar-pagamento-pix-split para pedido:', pedidoId);
        const respPix = await fetchWithAuth(API_ENDPOINTS.MERCADOPAGO_CRIAR_PIX_SPLIT, {
            method: 'POST',
            body: JSON.stringify({
                tenant_id: CONFIG.ID_USUARIO_LOJA,
                order_id: pedidoId,
                amount: valorTotal,
                description: `Venda Direta #${(pedidoId || '').substring(0, 8)}`
            })
        });

        const dataPix = await respPix.json();
        if (!respPix.ok || !dataPix.sucesso) {
            throw new Error(dataPix.message || dataPix.erro || 'Falha ao gerar QR Code do Pix no Mercado Pago');
        }

        // 3. Exibir modal do Pix transparente
        mostrarModalPixMercadoPago(dataPix, pedidoId, dadosPedido, carrinho);

        // 4. Iniciar polling de status no backend
        iniciarPollingPixMercadoPago(dataPix.payment_id, pedidoId, dadosPedido, carrinho);

        return {
            sucesso: true,
            gateway: 'mercadopago_pix',
            pedido_id: pedidoId,
            payment_id: dataPix.payment_id
        };

    } catch (error) {
        console.error('[MP Pix] ❌ Erro:', error);
        alert('Erro ao gerar Pix Dinâmico: ' + error.message);
        throw error;
    }
}

function mostrarModalPixMercadoPago(pixData, pedidoId, dadosPedido, carrinho) {
    const modalExistente = document.getElementById('modal-pix-mercadopago');
    if (modalExistente) modalExistente.remove();

    const qrSrc = pixData.qr_code_base64 
        ? `data:image/png;base64,${pixData.qr_code_base64}` 
        : `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(pixData.qr_code || '')}`;

    const modal = document.createElement('div');
    modal.id = 'modal-pix-mercadopago';
    modal.className = 'fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-auto shadow-2xl text-center space-y-4">
            <div class="flex items-center justify-between border-b pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">⚡</span>
                    <div class="text-left">
                        <h3 class="text-lg font-black text-gray-900 leading-tight">PIX Mercado Pago</h3>
                        <p class="text-[11px] text-cyan-600 font-bold">QR Code Dinâmico com Baixa Automática</p>
                    </div>
                </div>
                <button type="button" id="btn-fechar-modal-pix-mp" class="text-gray-400 hover:text-gray-700 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 flex items-center justify-center min-h-[220px]">
                <img src="${qrSrc}" alt="QR Code PIX" class="w-48 h-48 rounded-lg shadow-sm border border-gray-200 mx-auto">
            </div>

            ${pixData.qr_code ? `
                <div class="space-y-1.5 text-left">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase">Pix Copia e Cola:</label>
                    <div class="flex gap-2">
                        <input type="text" readonly value="${pixData.qr_code}" id="input-pix-copiacola-mp" class="flex-1 bg-gray-100 border border-gray-200 text-xs font-mono p-2 rounded-lg truncate select-all focus:outline-none">
                        <button type="button" id="btn-copiar-pix-mp-direta" class="px-3 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-lg transition shrink-0">
                            Copiar
                        </button>
                    </div>
                </div>
            ` : ''}

            <div class="bg-emerald-50 border border-emerald-200 p-3 rounded-xl text-center space-y-1">
                <div class="flex items-center justify-center gap-2 text-emerald-700 font-bold text-xs" id="status-pix-mp-texto">
                    <span class="inline-block animate-spin">⏳</span>
                    <span>Aguardando o cliente efetuar o pagamento...</span>
                </div>
                <p class="text-[10px] text-emerald-600">A venda será confirmada automaticamente assim que o banco aprovar.</p>
            </div>

            <button type="button" id="btn-cancelar-pix-mp-direta" class="w-full py-2.5 text-gray-500 hover:text-red-600 text-xs font-bold transition">
                Cancelar Cobrança PIX
            </button>
        </div>
    `;

    document.body.appendChild(modal);

    const btnCopiar = modal.querySelector('#btn-copiar-pix-mp-direta');
    if (btnCopiar) {
        btnCopiar.onclick = () => {
            const input = modal.querySelector('#input-pix-copiacola-mp');
            if (input) {
                navigator.clipboard.writeText(input.value).then(() => {
                    btnCopiar.textContent = '✅ Copiado!';
                    setTimeout(() => { btnCopiar.textContent = 'Copiar'; }, 2000);
                });
            }
        };
    }

    const fechar = () => {
        if (pollingIntervalId) {
            clearInterval(pollingIntervalId);
            pollingIntervalId = null;
        }
        modal.remove();
    };

    modal.querySelector('#btn-fechar-modal-pix-mp').onclick = fechar;
    modal.querySelector('#btn-cancelar-pix-mp-direta').onclick = fechar;
}

function iniciarPollingPixMercadoPago(paymentId, pedidoId, dadosPedido, carrinho) {
    if (pollingIntervalId) clearInterval(pollingIntervalId);

    pollingAttempts = 0;
    console.log(`[MP Pix] 🔄 Iniciando polling para payment_id: ${paymentId}, pedidoId: ${pedidoId}`);

    pollingIntervalId = setInterval(async () => {
        pollingAttempts++;

        if (pollingAttempts > maxPollingAttempts) {
            clearInterval(pollingIntervalId);
            pollingIntervalId = null;
            const statusTexto = document.getElementById('status-pix-mp-texto');
            if (statusTexto) {
                statusTexto.innerHTML = '<span class="text-red-600 font-bold">Tempo esgotado. Verifique se o cliente pagou.</span>';
            }
            return;
        }

        try {
            const resp = await fetchWithAuth(`${API_ENDPOINTS.MERCADOPAGO_CONSULTAR_STATUS_PIX}?payment_id=${paymentId}&tenant_id=${CONFIG.ID_USUARIO_LOJA}`);
            const data = await resp.json();

            if (data.sucesso && data.status) {
                if (data.status === 'approved') {
                    console.log('[MP Pix] ✅ Pagamento PIX aprovado com sucesso!');
                    if (pollingIntervalId) {
                        clearInterval(pollingIntervalId);
                        pollingIntervalId = null;
                    }

                    const statusTexto = document.getElementById('status-pix-mp-texto');
                    if (statusTexto) {
                        statusTexto.innerHTML = '✅ <span class="text-emerald-700 font-bold">Pagamento Confirmado! Finalizando venda...</span>';
                    }

                    setTimeout(() => {
                        const modal = document.getElementById('modal-pix-mercadopago');
                        if (modal) modal.remove();

                        // Dispara evento de confirmação global
                        window.dispatchEvent(new CustomEvent('pagamentoConfirmado', {
                            detail: {
                                pedidoId: pedidoId,
                                gateway: 'mercadopago_pix',
                                dados: data,
                                originalDadosPedido: {
                                    ...dadosPedido,
                                    id: pedidoId,
                                    itens: carrinho,
                                    carrinho: carrinho
                                }
                            }
                        }));
                    }, 1000);
                }
            }
        } catch (err) {
            console.warn('[MP Pix] Erro ao consultar status Pix:', err);
        }
    }, 3000);
}

/**
 * MERCADO PAGO - CARTEIRA DIGITAL / APROXIMAÇÃO (Google Pay, Apple Pay & 1-Clique)
 */
async function processarMercadoPagoCarteiraDigital(dadosPedido, carrinho, cliente) {
    try {
        console.log('[MP Wallet] ⚡ Iniciando fluxo de Carteira Digital / Aproximação...');

        // 1. Criar pré-registro do pedido para obter UUID válido
        const { finalizarPedido } = await import('./order.js');
        const backupHabilitado = GATEWAY_CONFIG.habilitado;
        GATEWAY_CONFIG.habilitado = false; 

        const respPedido = await finalizarPedido(dadosPedido, carrinho);
        GATEWAY_CONFIG.habilitado = backupHabilitado;

        if (!respPedido.sucesso) {
            throw new Error('Falha ao registrar pedido antes de gerar Carteira Digital: ' + (respPedido.erro || 'Erro desconhecido'));
        }

        const pedidoId = respPedido.dados?.id || respPedido.dados?.venda?.id || respPedido.dados?.venda_id;
        const valorTotal = carrinho.reduce((t, i) => t + ((i.preco_final || i.preco_venda_sugerido) * i.quantidade), 0);

        // 2. Criar Preferência com Split de Carteira Digital
        console.log('[MP Wallet] ⚡ Criando preferência de Carteira Digital para pedido:', pedidoId);
        const respPref = await fetchWithAuth(API_ENDPOINTS.MERCADOPAGO_CRIAR_PREFERENCIA_CARTEIRA, {
            method: 'POST',
            body: JSON.stringify({
                tenant_id: CONFIG.ID_USUARIO_LOJA,
                venda_id: pedidoId,
                valor_total: valorTotal,
                cliente: {
                    nome: cliente.nome || 'Consumidor Balcão',
                    email: cliente.email || 'cliente@pdv.com',
                    cpf: cliente.cpf_cnpj || cliente.cpf || ''
                },
                itens: carrinho.map(item => ({
                    title: item.nome || 'Produto',
                    quantidade: item.quantidade || 1,
                    preco_unitario: item.preco_final || item.preco_venda_sugerido || 0
                }))
            })
        });

        const dataPref = await respPref.json();
        if (!respPref.ok || !dataPref.sucesso) {
            throw new Error(dataPref.message || dataPref.mensagem || 'Falha ao criar cobrança de carteira digital.');
        }

        // 3. Exibir Modal da Carteira Digital com Wallet Brick e QR Code
        mostrarModalCarteiraDigitalMercadoPago(dataPref, pedidoId, valorTotal, dadosPedido, carrinho);

        // 4. Iniciar Polling de Status
        iniciarPollingCarteiraDigitalMercadoPago(dataPref.external_reference, dataPref.preference_id, pedidoId, dadosPedido, carrinho);

        return {
            sucesso: true,
            gateway: 'mercadopago_wallet',
            pedido_id: pedidoId,
            preference_id: dataPref.preference_id
        };

    } catch (error) {
        console.error('[MP Wallet] ❌ Erro:', error);
        alert('Erro ao iniciar pagamento por Carteira Digital: ' + error.message);
        throw error;
    }
}

function mostrarModalCarteiraDigitalMercadoPago(dataPref, pedidoId, valorTotal, dadosPedido, carrinho) {
    const modalExistente = document.getElementById('modal-wallet-mercadopago');
    if (modalExistente) modalExistente.remove();

    const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(dataPref.init_point || '')}`;

    const modal = document.createElement('div');
    modal.id = 'modal-wallet-mercadopago';
    modal.className = 'fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-3xl p-6 max-w-md w-full mx-auto shadow-2xl text-center space-y-4">
            <div class="flex items-center justify-between border-b pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">📱</span>
                    <div class="text-left">
                        <h3 class="text-lg font-black text-gray-900 leading-tight">Carteira Digital & Aproximação</h3>
                        <p class="text-[11px] text-cyan-600 font-bold">Google Pay • Apple Pay • 1-Clique</p>
                    </div>
                </div>
                <button type="button" id="btn-fechar-modal-wallet-mp" class="text-gray-400 hover:text-gray-700 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Valor a Pagar -->
            <div class="bg-slate-900 text-white p-4 rounded-2xl shadow-inner">
                <span class="text-[11px] text-cyan-300 uppercase font-bold tracking-wider">Total a Cobrar</span>
                <div class="text-3xl font-black text-white">R$ ${valorTotal.toFixed(2).replace('.', ',')}</div>
            </div>

            <!-- Container do Wallet Brick (Pagamento 1-Clique na tela) -->
            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200 space-y-3">
                <div class="flex items-center justify-center gap-2 text-xs font-bold text-gray-700">
                    <span>⚡</span>
                    <span>Pagar diretamente neste dispositivo:</span>
                </div>
                <div id="vd-wallet-brick-container" style="min-height: 48px;" class="flex items-center justify-center">
                    <div class="text-xs text-gray-400 flex items-center gap-2 animate-pulse">
                        <span class="inline-block animate-spin">⏳</span> Carregando Carteiras Digitais...
                    </div>
                </div>
            </div>

            <!-- Divisor Ou no Celular -->
            <div class="relative flex py-1 items-center">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink mx-3 text-gray-400 text-[11px] font-bold uppercase">ou aproxime pelo celular do cliente</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>

            <!-- QR Code para Aproximação no Smartphone do Cliente -->
            <div class="bg-gray-50 p-3 rounded-2xl border border-gray-100 flex flex-col items-center justify-center">
                <img src="${qrCodeUrl}" alt="QR Code Aproximação" class="w-36 h-36 rounded-lg shadow-sm border border-gray-200 mx-auto mb-2">
                <p class="text-[11px] text-gray-500 font-medium">Cliente pode escanear com a câmera do celular para pagar por aproximação/biometria.</p>
            </div>

            <div class="bg-emerald-50 border border-emerald-200 p-3 rounded-xl text-center space-y-1">
                <div class="flex items-center justify-center gap-2 text-emerald-700 font-bold text-xs" id="status-wallet-mp-texto">
                    <span class="inline-block animate-spin">⏳</span>
                    <span>Aguardando aprovação da carteira digital...</span>
                </div>
                <p class="text-[10px] text-emerald-600">A venda será confirmada e liberada automaticamente com baixa de estoque.</p>
            </div>

            <button type="button" id="btn-cancelar-wallet-mp-direta" class="w-full py-2 text-gray-500 hover:text-red-600 text-xs font-bold transition">
                Cancelar Cobrança
            </button>
        </div>
    `;

    document.body.appendChild(modal);

    // Inicializa o Wallet Brick no container do PDV
    const publicKey = dataPref.public_key || window.GATEWAY_CONFIG?.mercadopago_public_key;
    if (typeof window.MercadoPago !== 'undefined' && publicKey) {
        try {
            const mp = new window.MercadoPago(publicKey, { locale: 'pt-BR' });
            const bricks = mp.bricks();
            const container = document.getElementById('vd-wallet-brick-container');
            if (container) container.innerHTML = '';
            bricks.create('wallet', 'vd-wallet-brick-container', {
                initialization: {
                    preferenceId: dataPref.preference_id,
                    redirectMode: 'modal'
                },
                customization: {
                    texts: { action: 'pay', valueProp: 'convenience_all' },
                    visual: { buttonBackground: 'black', borderRadius: '12px' }
                }
            }).catch(e => {
                console.warn('[MP Wallet] Falha no brick embutido:', e);
            });
        } catch (eMp) {
            console.warn('[MP Wallet] Erro ao instanciar Brick:', eMp);
        }
    }

    const fechar = () => {
        if (pollingIntervalId) {
            clearInterval(pollingIntervalId);
            pollingIntervalId = null;
        }
        modal.remove();
    };

    modal.querySelector('#btn-fechar-modal-wallet-mp').onclick = fechar;
    modal.querySelector('#btn-cancelar-wallet-mp-direta').onclick = fechar;
}

function iniciarPollingCarteiraDigitalMercadoPago(externalReference, preferenceId, pedidoId, dadosPedido, carrinho) {
    if (pollingIntervalId) clearInterval(pollingIntervalId);

    pollingAttempts = 0;
    console.log(`[MP Wallet] 🔄 Iniciando polling para external_reference: ${externalReference}, preferenceId: ${preferenceId}`);

    pollingIntervalId = setInterval(async () => {
        pollingAttempts++;

        if (pollingAttempts > maxPollingAttempts) {
            clearInterval(pollingIntervalId);
            pollingIntervalId = null;
            const statusTexto = document.getElementById('status-wallet-mp-texto');
            if (statusTexto) {
                statusTexto.innerHTML = '<span class="text-red-600 font-bold">Tempo esgotado. Verifique se o pagamento foi concluído.</span>';
            }
            return;
        }

        try {
            const url = `${API_ENDPOINTS.MERCADOPAGO_CONSULTAR_STATUS_PREFERENCIA}?external_reference=${encodeURIComponent(externalReference || pedidoId)}&preference_id=${encodeURIComponent(preferenceId || '')}&tenant_id=${encodeURIComponent(CONFIG.ID_USUARIO_LOJA)}`;
            const resp = await fetchWithAuth(url);
            const data = await resp.json();

            if (data.sucesso && data.status === 'approved') {
                console.log('[MP Wallet] ✅ Pagamento por Carteira Digital aprovado!');
                if (pollingIntervalId) {
                    clearInterval(pollingIntervalId);
                    pollingIntervalId = null;
                }

                const statusTexto = document.getElementById('status-wallet-mp-texto');
                if (statusTexto) {
                    statusTexto.innerHTML = '✅ <span class="text-emerald-700 font-bold">Pagamento Aprovado! Finalizando venda...</span>';
                }

                setTimeout(() => {
                    const modal = document.getElementById('modal-wallet-mercadopago');
                    if (modal) modal.remove();

                    // Dispara evento de confirmação global
                    window.dispatchEvent(new CustomEvent('pagamentoConfirmado', {
                        detail: {
                            pedidoId: pedidoId,
                            gateway: 'mercadopago_wallet',
                            dados: data,
                            originalDadosPedido: {
                                ...dadosPedido,
                                id: pedidoId,
                                itens: carrinho,
                                carrinho: carrinho
                            }
                        }
                    }));
                }, 1000);
            }
        } catch (err) {
            console.warn('[MP Wallet] Erro ao consultar status da preferência:', err);
        }
    }, 2500);
}

/**
 * MERCADO PAGO - CARTÃO ONLINE DIRETO NO PDV
 */
async function processarMercadoPagoCartaoOnline(dadosPedido, carrinho, cliente) {
    try {
        console.log('[MP Cartão] 💳 Iniciando fluxo de cartão online...');

        // 1. Criar pré-registro do pedido para obter UUID válido
        const { finalizarPedido } = await import('./order.js');
        const backupHabilitado = GATEWAY_CONFIG.habilitado;
        GATEWAY_CONFIG.habilitado = false; 

        const respPedido = await finalizarPedido(dadosPedido, carrinho);
        GATEWAY_CONFIG.habilitado = backupHabilitado;

        if (!respPedido.sucesso) {
            throw new Error('Falha ao registrar pedido antes de cobrar cartão: ' + (respPedido.erro || 'Erro desconhecido'));
        }

        const pedidoId = respPedido.dados?.id || respPedido.dados?.venda?.id || respPedido.dados?.venda_id;
        const valorTotal = carrinho.reduce((t, i) => t + ((i.preco_final || i.preco_venda_sugerido) * i.quantidade), 0);

        // 2. Exibir modal do Cartão transparente
        return await mostrarModalCartaoMercadoPago(pedidoId, valorTotal, dadosPedido, carrinho, cliente);

    } catch (error) {
        console.error('[MP Cartão] ❌ Erro:', error);
        alert('Erro ao iniciar cobrança no cartão: ' + error.message);
        throw error;
    }
}

function mostrarModalCartaoMercadoPago(pedidoId, valorTotal, dadosPedido, carrinho, cliente) {
    return new Promise((resolve) => {
        const modalExistente = document.getElementById('modal-cartao-mercadopago');
        if (modalExistente) modalExistente.remove();

        const modal = document.createElement('div');
        modal.id = 'modal-cartao-mercadopago';
        modal.className = 'fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-auto shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">💳</span>
                        <div class="text-left">
                            <h3 class="text-lg font-black text-gray-900 leading-tight">Mercado Pago Cartão</h3>
                            <p class="text-[11px] text-cyan-600 font-bold">Cobrança transparente no balcão</p>
                        </div>
                    </div>
                    <button type="button" id="btn-fechar-modal-cartao-mp" class="text-gray-400 hover:text-gray-700 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="bg-gray-50 border border-gray-200 p-3 rounded-xl text-center">
                    <span class="text-xs text-gray-500 uppercase font-bold">Total a Cobrar</span>
                    <div class="text-2xl font-black text-gray-900">R$ ${valorTotal.toFixed(2).replace('.', ',')}</div>
                </div>

                <form id="form-cartao-mp-direta" class="space-y-3 text-left">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Número do Cartão</label>
                        <input type="text" id="mp-direta-cartao-numero" maxlength="19" placeholder="0000 0000 0000 0000" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono font-bold text-gray-900 focus:outline-none focus:border-brand-500" required>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Validade (MM/AA)</label>
                            <input type="text" id="mp-direta-cartao-validade" maxlength="5" placeholder="MM/AA" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono font-bold text-gray-900 focus:outline-none focus:border-brand-500" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">CVV</label>
                            <input type="text" id="mp-direta-cartao-cvv" maxlength="4" placeholder="123" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono font-bold text-gray-900 focus:outline-none focus:border-brand-500" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Nome no Cartão</label>
                        <input type="text" id="mp-direta-cartao-nome" placeholder="NOME COMO NO CARTÃO" value="${(cliente?.nome || '').toUpperCase()}" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm font-bold uppercase text-gray-900 focus:outline-none focus:border-brand-500" required>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">CPF do Titular</label>
                            <input type="text" id="mp-direta-cartao-cpf" maxlength="14" placeholder="000.000.000-00" value="${cliente?.cpf_cnpj || ''}" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono font-bold text-gray-900 focus:outline-none focus:border-brand-500" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Parcelas</label>
                            <select id="mp-direta-cartao-parcelas" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm font-bold text-gray-900 focus:outline-none focus:border-brand-500">
                                <option value="1">1x à vista</option>
                                <option value="2">2x</option>
                                <option value="3">3x</option>
                                <option value="4">4x</option>
                                <option value="5">5x</option>
                                <option value="6">6x</option>
                                <option value="10">10x</option>
                                <option value="12">12x</option>
                            </select>
                        </div>
                    </div>

                    <div id="mp-direta-cartao-feedback" class="hidden p-2.5 rounded-xl text-xs font-bold text-center"></div>

                    <button type="submit" id="btn-cobrar-cartao-mp-direta" class="w-full py-3 bg-brand-600 hover:bg-brand-700 text-white font-black text-sm rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                        <span>💳 Cobrar Cartão via Mercado Pago</span>
                    </button>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        // Masks
        const inputNum = modal.querySelector('#mp-direta-cartao-numero');
        if (inputNum) {
            inputNum.addEventListener('input', (e) => {
                let v = e.target.value.replace(/\D/g, '').substring(0, 19);
                e.target.value = v.replace(/(\d{4})(?=\d)/g, '$1 ');
            });
        }
        const inputVal = modal.querySelector('#mp-direta-cartao-validade');
        if (inputVal) {
            inputVal.addEventListener('input', (e) => {
                let v = e.target.value.replace(/\D/g, '').substring(0, 4);
                if (v.length >= 3) e.target.value = v.substring(0, 2) + '/' + v.substring(2);
                else e.target.value = v;
            });
        }
        const inputCpf = modal.querySelector('#mp-direta-cartao-cpf');
        if (inputCpf) {
            inputCpf.addEventListener('input', (e) => {
                let v = e.target.value.replace(/\D/g, '').substring(0, 11);
                if (v.length > 9) e.target.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
                else if (v.length > 6) e.target.value = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
                else if (v.length > 3) e.target.value = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
                else e.target.value = v;
            });
        }

        const fechar = () => {
            modal.remove();
            resolve({ sucesso: false, cancelado: true });
        };
        modal.querySelector('#btn-fechar-modal-cartao-mp').onclick = fechar;

        const form = modal.querySelector('#form-cartao-mp-direta');
        form.onsubmit = async (e) => {
            e.preventDefault();
            const feedback = modal.querySelector('#mp-direta-cartao-feedback');
            const btn = modal.querySelector('#btn-cobrar-cartao-mp-direta');

            const numCartao = inputNum.value.replace(/\D/g, '');
            const val = inputVal.value.trim().split('/');
            const cvv = modal.querySelector('#mp-direta-cartao-cvv').value.trim();
            const nome = modal.querySelector('#mp-direta-cartao-nome').value.trim();
            const cpf = inputCpf.value.replace(/\D/g, '');
            const parcelas = parseInt(modal.querySelector('#mp-direta-cartao-parcelas').value, 10);

            if (val.length !== 2) {
                alert('Validade deve estar no formato MM/AA');
                return;
            }
            const mes = parseInt(val[0], 10);
            let ano = parseInt(val[1], 10);
            if (ano < 100) ano += 2000;

            btn.disabled = true;
            btn.textContent = '⏳ Processando cobrança...';
            feedback.className = 'p-2.5 rounded-xl text-xs font-bold text-center bg-cyan-50 text-cyan-800 border border-cyan-200';
            feedback.textContent = 'Enviando dados do cartão para o Mercado Pago...';
            feedback.classList.remove('hidden');

            try {
                const resp = await fetchWithAuth(API_ENDPOINTS.MERCADOPAGO_PAGAR_CARTAO, {
                    method: 'POST',
                    body: JSON.stringify({
                        tenant_id: CONFIG.ID_USUARIO_LOJA,
                        order_id: pedidoId,
                        amount: valorTotal,
                        installments: parcelas,
                        card_number: numCartao,
                        card_holder: nome,
                        expiration_month: mes,
                        expiration_year: ano,
                        security_code: cvv,
                        doc_number: cpf,
                        email: cliente?.email || 'cliente@pdv.com'
                    })
                });

                const data = await resp.json();
                if (data.status === 'requires_action' && data.three_ds_url) {
                    console.log('[MP Cartão 3DS] 🔐 Autenticação bancária 3DS necessária:', data.three_ds_url);
                    exibirDesafio3DsVendaDireta(modal, data.three_ds_url, data.payment_id, pedidoId, dadosPedido, carrinho, resolve);
                    return;
                }

                if (!resp.ok || !data.sucesso) {
                    throw new Error(data.message || data.mensagem || 'Cartão recusado pelo Mercado Pago.');
                }

                feedback.className = 'p-2.5 rounded-xl text-xs font-bold text-center bg-emerald-50 text-emerald-800 border border-emerald-200';
                feedback.textContent = '✅ Pagamento Aprovado com Sucesso!';

                setTimeout(() => {
                    modal.remove();
                    window.dispatchEvent(new CustomEvent('pagamentoConfirmado', {
                        detail: {
                            pedidoId: pedidoId,
                            gateway: 'mercadopago_cartao',
                            dados: data,
                            originalDadosPedido: {
                                ...dadosPedido,
                                id: pedidoId,
                                itens: carrinho,
                                carrinho: carrinho
                            }
                        }
                    }));
                    resolve({ sucesso: true, gateway: 'mercadopago_cartao', pedido_id: pedidoId });
                }, 1000);

            } catch (err) {
                feedback.className = 'p-2.5 rounded-xl text-xs font-bold text-center bg-red-50 text-red-800 border border-red-200';
                feedback.textContent = '❌ ' + err.message;
                alert('Falha ao processar cartão: ' + err.message);
                btn.disabled = false;
                btn.textContent = '💳 Cobrar Cartão via Mercado Pago';
            }
        };
    });
}

/**
 * Exibe o desafio 3DS (Three-D Secure) na Venda Direta com suporte a iframe e link externo seguro
 */
function exibirDesafio3DsVendaDireta(modal, threeDsUrl, paymentId, pedidoId, dadosPedido, carrinho, resolve) {
    const cardContent = modal.querySelector('.max-w-md');
    if (!cardContent) return;

    cardContent.innerHTML = `
        <div class="flex items-center justify-between border-b pb-3">
            <div class="flex items-center gap-2">
                <span class="text-2xl">🔐</span>
                <div class="text-left">
                    <h3 class="text-lg font-black text-gray-900 leading-tight">Autenticação do Banco (3DS)</h3>
                    <p class="text-[11px] text-cyan-600 font-bold">Confirmação de segurança do cartão</p>
                </div>
            </div>
            <button type="button" id="btn-fechar-3ds-mp-direta" class="text-gray-400 hover:text-gray-700 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="bg-amber-50 border border-amber-200 p-3 rounded-xl text-left space-y-1">
            <div class="flex items-center gap-2 text-amber-800 font-bold text-xs">
                <span>🛡️</span>
                <span>O banco emissor solicitou a autenticação da compra.</span>
            </div>
            <p class="text-[11px] text-amber-700">Conclua a verificação no quadro abaixo ou abra diretamente no aplicativo/site do banco.</p>
        </div>

        <div class="rounded-xl overflow-hidden border border-gray-200 bg-gray-50 min-h-[300px] relative">
            <iframe src="${threeDsUrl}" id="iframe-3ds-direta" class="w-full h-80 border-0 rounded-xl" allow="payment"></iframe>
        </div>

        <div class="flex flex-col gap-2">
            <a href="${threeDsUrl}" target="_blank" rel="noopener noreferrer" class="w-full py-2.5 px-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl text-center shadow transition flex items-center justify-center gap-2">
                <span>📲 Abrir tela do Banco em nova janela</span>
                <span>↗</span>
            </a>
            <div id="status-3ds-mp-texto" class="text-center text-xs font-bold text-gray-500 py-1 flex items-center justify-center gap-2">
                <span class="inline-block animate-spin">⏳</span>
                <span>Aguardando você confirmar no banco...</span>
            </div>
            <button type="button" id="btn-cancelar-3ds-direta" class="w-full py-2 text-gray-500 hover:text-red-600 text-xs font-bold transition">
                Cancelar Autenticação
            </button>
        </div>
    `;

    // Polling de verificação de aprovação
    let pollInterval = null;
    let attempts = 0;
    const maxAttempts = 80;

    const cleanup = () => {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = null;
    };

    const fecharBtn = cardContent.querySelector('#btn-fechar-3ds-mp-direta');
    if (fecharBtn) fecharBtn.onclick = () => { cleanup(); modal.remove(); };

    const cancelarBtn = cardContent.querySelector('#btn-cancelar-3ds-direta');
    if (cancelarBtn) cancelarBtn.onclick = () => { cleanup(); modal.remove(); };

    pollInterval = setInterval(async () => {
        attempts++;
        if (attempts > maxAttempts) {
            cleanup();
            const statusEl = cardContent.querySelector('#status-3ds-mp-texto');
            if (statusEl) statusEl.innerHTML = '<span class="text-red-600 font-bold">Tempo limite de autenticação excedido.</span>';
            return;
        }

        try {
            const resp = await fetchWithAuth(`${API_ENDPOINTS.MERCADOPAGO_CONSULTAR_STATUS_PIX}?payment_id=${paymentId}&tenant_id=${CONFIG.ID_USUARIO_LOJA}`);
            const data = await resp.json();

            if (data.sucesso && data.status === 'approved') {
                cleanup();
                const statusEl = cardContent.querySelector('#status-3ds-mp-texto');
                if (statusEl) {
                    statusEl.innerHTML = '✅ <span class="text-emerald-700 font-bold">Autenticação Aprovada! Finalizando venda...</span>';
                }

                setTimeout(() => {
                    modal.remove();
                    window.dispatchEvent(new CustomEvent('pagamentoConfirmado', {
                        detail: {
                            pedidoId: pedidoId,
                            gateway: 'mercadopago_cartao',
                            dados: data,
                            originalDadosPedido: {
                                ...dadosPedido,
                                id: pedidoId,
                                itens: carrinho,
                                carrinho: carrinho
                            }
                        }
                    }));
                    resolve({ sucesso: true, gateway: 'mercadopago_cartao', pedido_id: pedidoId });
                }, 1000);
            } else if (data.sucesso && (data.status === 'rejected' || data.status === 'cancelled')) {
                cleanup();
                const statusEl = cardContent.querySelector('#status-3ds-mp-texto');
                if (statusEl) {
                    statusEl.innerHTML = '❌ <span class="text-red-600 font-bold">Autenticação não autorizada pelo banco.</span>';
                }
            }
        } catch (err) {
            console.warn('[MP 3DS] Erro na consulta 3DS:', err);
        }
    }, 2500);
}

/**
 * ASAAS - VENDA DIRETA
 */
async function processarAsaas(dadosPedido, carrinho, cliente) {
    try {
        const valorTotal = carrinho.reduce((total, item) => 
            total + ((item.preco_venda_sugerido || 0) * (item.quantidade || 1)), 0
        );
        
        const pedidoAsaas = {
            usuario_id: CONFIG.ID_USUARIO_LOJA || null,
            cliente_id: dadosPedido.cliente_id || null,
            valor: valorTotal || 0,
            descricao: `Venda Direta - ${carrinho.length} item(ns)`,
            metodo_pagamento: 'PIX',
            vencimento: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            
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
            },
            
            // Informações específicas de venda direta
            colaborador_vendedor_id: dadosPedido.colaborador_vendedor_id || null,
            observacoes: dadosPedido.observacoes || null
        };
        
        console.log('[Gateway] Criando cobrança Asaas no backend...');
        
        const response = await fetchWithAuth(API_ENDPOINTS.ASAAS_CRIAR_COBRANCA, {
            method: 'POST',
            body: JSON.stringify(pedidoAsaas)
        });
        
        if (!response.ok) {
            const erro = await response.json();
            throw new Error(erro.erro || 'Erro ao criar cobrança');
        }
        
        const resultado = await response.json();
        
        localStorage.setItem('asaas_payment_id', resultado.payment_id);
        localStorage.setItem('asaas_external_ref', resultado.external_reference);
        
        if (resultado.pix) {
            // Mostrar modal PIX (similar ao do catálogo)
            mostrarModalPix(resultado.pix, resultado.payment_id);
            iniciarPollingStatusPagamento(resultado.payment_id);
            
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

/**
 * Fluxo interno (sem gateway)
 */
async function processarFluxoInterno(dadosPedido, carrinho) {
    const { finalizarPedido } = await import('./order.js');
    return await finalizarPedido(dadosPedido, carrinho);
}

/**
 * Funções auxiliares para polling e modais (similar ao catálogo)
 */
let pollingIntervalId = null;
let pollingAttempts = 0;
const maxPollingAttempts = 60;

async function verificarStatusPagamento(paymentId) {
    pollingAttempts++;
    
    try {
        const url = `${API_ENDPOINTS.ASAAS_CONSULTAR_STATUS}?payment_id=${paymentId}&usuario_id=${CONFIG.ID_USUARIO_LOJA}`;
        
        const response = await fetchWithAuth(url, {
            method: 'GET',
            cache: 'no-store'
        });
        
        if (!response.ok) {
            return { status: 'pendente' };
        }
        
        const resultado = await response.json();
        const statusPago = ['pago', 'RECEIVED', 'CONFIRMED', 'received', 'confirmed'];
        const statusAsaas = (resultado.status_asaas || '').toUpperCase();
        const statusLocal = (resultado.status || '').toLowerCase();
        
        if (resultado.sucesso && (statusPago.includes(statusLocal) || statusPago.includes(statusAsaas))) {
            return { 
                status: 'pago', 
                pedido_id: resultado.pedido_id,
                dados: resultado
            };
        }
        
        return { status: 'pendente' };
        
    } catch (error) {
        console.error('[Gateway] ❌ Erro no polling:', error);
        return { status: 'pendente' };
    }
}

function iniciarPollingStatusPagamento(paymentId) {
    if (pollingIntervalId) {
        clearInterval(pollingIntervalId);
    }
    
    pollingAttempts = 0;
    
    pollingIntervalId = setInterval(async () => {
        pollingAttempts++;
        
        if (pollingAttempts > maxPollingAttempts) {
            clearInterval(pollingIntervalId);
            pollingIntervalId = null;
            const statusText = document.getElementById('pix-status-text');
            if (statusText) {
                statusText.textContent = 'Tempo esgotado. Verifique seu pedido mais tarde.';
                statusText.classList.add('text-red-500');
            }
            return;
        }

        const resultado = await verificarStatusPagamento(paymentId);
        
        if (resultado.status === 'pago') {
            console.log('[Gateway] ✅ Pagamento confirmado via polling!');
            tratarPagamentoConfirmado(resultado.pedido_id, resultado.dados);
        }
        
    }, 5000);
}

function tratarPagamentoConfirmado(pedidoId, dados) {
    if (pollingIntervalId) {
        clearInterval(pollingIntervalId);
        pollingIntervalId = null;
    }
    
    const modalPix = document.getElementById('modal-pix-asaas');
    if (modalPix) modalPix.remove();
    
    console.log('[Gateway] ✅ Pagamento confirmado! Pedido:', pedidoId);
    
    // Disparar evento para atualizar a interface
    window.dispatchEvent(new CustomEvent('pagamentoConfirmado', {
        detail: {
            pedidoId: pedidoId,
            gateway: 'asaas',
            dados: dados
        }
    }));
}

function mostrarModalPix(pixData, paymentId) {
    const modal = document.createElement('div');
    modal.id = 'modal-pix-asaas';
    modal.className = 'fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4';
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
                    class="w-full bg-brand-600 text-white py-3 rounded-lg mb-4 hover:bg-brand-700">
                📋 Copiar Código PIX
            </button>
            
            <div class="text-center bg-gray-50 p-3 rounded-lg">
                <p id="pix-status-text" class="text-sm text-gray-700 font-medium">
                    Aguardando confirmação...
                </p>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                    <div class="bg-brand-500 h-1.5 rounded-full animate-pulse"></div>
                </div>
            </div>
            
            <button onclick="document.getElementById('modal-pix-asaas')?.remove(); window.cancelarPollingPix?.();" 
                    class="w-full text-gray-500 text-sm py-2 mt-3 hover:text-gray-700">
                Fechar
            </button>
        </div>
    `;
    
    document.body.appendChild(modal);
}

/**
 * MERCADO PAGO POINT - MAQUINETA FÍSICA
 */
async function processarMercadoPagoPoint(dadosPedido, carrinho, cliente) {
    try {
        console.log('[Point] 📟 Iniciando fluxo de maquineta física...');
        
        // 1. Buscar dispositivos disponíveis
        console.log('[Gateway] 📦 Buscando maquinetas Point disponíveis...');
        const respDisp = await fetchWithAuth(`${API_ENDPOINTS.MERCADOPAGO_LISTAR_DISPOSITIVOS}?tenant_id=${CONFIG.ID_USUARIO_LOJA}`, {
            method: 'GET'
        });
        
        const dataDisp = await respDisp.json();
        const dispositivos = dataDisp.dispositivos || [];

        if (dispositivos.length === 0) {
            throw new Error('Nenhuma maquineta vinculada encontrada. Cadastre uma maquineta no sistema primeiro.');
        }

        // 2. Se houver apenas uma, seleciona direto. Se houver mais, mostra modal.
        let dispositivoSelecionado = dispositivos[0];
        
        if (dispositivos.length > 1) {
            dispositivoSelecionado = await mostrarModalSelecaoPoint(dispositivos);
        }

        if (!dispositivoSelecionado) {
            throw new Error('Operação cancelada: Nenhuma maquineta selecionada.');
        }

        // 3. Criar o pedido no banco primeiro para ter um ID de referência
        const { finalizarPedido } = await import('./order.js');
        // Temporariamente desabilitamos o gateway para criar o registro do pedido
        const backupHabilitado = GATEWAY_CONFIG.habilitado;
        GATEWAY_CONFIG.habilitado = false; 
        
        const respPedido = await finalizarPedido(dadosPedido, carrinho);
        GATEWAY_CONFIG.habilitado = backupHabilitado;

        if (!respPedido.sucesso) {
            throw new Error('Falha ao registrar pedido antes do pagamento: ' + (respPedido.erro || 'Erro desconhecido'));
        }

        const pedidoId = respPedido.dados?.id || respPedido.dados?.venda?.id;
        const valorTotal = carrinho.reduce((t, i) => t + ((i.preco_final || i.preco_venda_sugerido) * i.quantidade), 0);

        // 4. Enviar para a Maquineta
        const respPoint = await fetchWithAuth(API_ENDPOINTS.MERCADOPAGO_CRIAR_PAGAMENTO_POINT, {
            method: 'POST',
            body: JSON.stringify({
                tenant_id: CONFIG.ID_USUARIO_LOJA,
                device_id: dispositivoSelecionado.device_id,
                amount: valorTotal,
                order_id: pedidoId
            })
        });

        if (!respPoint.ok) {
            const erro = await respPoint.json();
            throw new Error(erro.erro || 'Erro ao enviar para a maquineta');
        }

        const resultadoPoint = await respPoint.json();
        
        // 5. Mostrar modal de aguardando na maquineta e iniciar polling
        mostrarModalAguardandoPoint(dispositivoSelecionado.nome);
        iniciarPollingStatusPoint(pedidoId, dadosPedido);

        return {
            sucesso: true,
            gateway: 'mercadopago_point',
            pedido_id: pedidoId,
            intent_id: resultadoPoint.data?.id
        };

    } catch (error) {
        console.error('[Point] ❌ Erro:', error);
        throw error;
    }
}

function mostrarModalSelecaoPoint(dispositivos) {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-sm w-full">
                <h3 class="text-xl font-bold mb-4">Selecione a Maquineta</h3>
                <div class="space-y-3 mb-6">
                    ${dispositivos.map(d => `
                        <button class="w-full text-left p-4 border rounded-lg hover:bg-brand-50 hover:border-brand-500 transition-colors flex justify-between items-center" data-id="${d.id}">
                            <span>${d.nome}</span>
                            <span class="text-xs text-gray-400">${d.device_id}</span>
                        </button>
                    `).join('')}
                </div>
                <button id="btn-cancel-point" class="w-full py-2 text-gray-500 hover:text-gray-700">Cancelar</button>
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelectorAll('button[data-id]').forEach(btn => {
            btn.onclick = () => {
                const id = btn.getAttribute('data-id');
                const disp = dispositivos.find(d => d.id == id);
                modal.remove();
                resolve(disp);
            };
        });

        modal.querySelector('#btn-cancel-point').onclick = () => {
            modal.remove();
            resolve(null);
        };
    });
}

function mostrarModalAguardandoPoint(nomeMaquineta) {
    const modal = document.createElement('div');
    modal.id = 'modal-status-point';
    modal.className = 'fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-8 max-w-sm w-full text-center">
            <div class="w-20 h-20 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-credit-card text-3xl text-brand-600 animate-pulse"></i>
            </div>
            <h3 class="text-2xl font-bold mb-2">Aguardando na Maquineta</h3>
            <p class="text-gray-600 mb-6">Por favor, finalize o pagamento na <strong>${nomeMaquineta}</strong>.</p>
            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                <div class="bg-brand-600 h-2 rounded-full animate-progress" style="width: 100%"></div>
            </div>
            <p class="text-sm text-gray-500">O Pulse atualizará a venda automaticamente assim que for aprovado.</p>
            <button onclick="window.location.reload()" class="mt-8 text-brand-600 font-medium">Voltar para Início</button>
        </div>
    `;
    document.body.appendChild(modal);
}

/**
 * POLLING PARA POINT (MAQUINETA)
 */
async function iniciarPollingStatusPoint(pedidoId, originalDadosPedido = null) {
    if (pollingIntervalId) {
        clearInterval(pollingIntervalId);
    }
    
    pollingAttempts = 0;
    console.log(`[Point] 🔄 Iniciando polling para pedido: ${pedidoId}`);
    
    pollingIntervalId = setInterval(async () => {
        pollingAttempts++;
        
        if (pollingAttempts > maxPollingAttempts) {
            clearInterval(pollingIntervalId);
            pollingIntervalId = null;
            const statusText = document.querySelector('#modal-status-point p.text-gray-600');
            if (statusText) {
                statusText.innerHTML = '<span class="text-red-500 font-bold">Tempo esgotado.</span> Verifique o status na maquineta.';
            }
            return;
        }

        try {
            const url = `${API_ENDPOINTS.PEDIDO_STATUS}?venda_id=${pedidoId}`;
            const response = await fetchWithAuth(url, { }); // fetchWithAuth will handle headers
            
            if (response.ok) {
                const data = await response.json();
                if (data.sucesso && data.pago) {
                    console.log('[Point] ✅ Pagamento confirmado via polling!');
                    
                    if (pollingIntervalId) {
                        clearInterval(pollingIntervalId);
                        pollingIntervalId = null;
                    }

                    const modal = document.getElementById('modal-status-point');
                    if (modal) modal.remove();

                    window.dispatchEvent(new CustomEvent('pagamentoConfirmado', {
                        detail: {
                            pedidoId: pedidoId,
                            gateway: 'mercadopago_point',
                            dados: data,
                            originalDadosPedido: originalDadosPedido
                        }
                    }));
                } else if (data.sucesso && data.recusado) {
                    console.warn('[Point] ❌ Pagamento recusado ou cancelado na maquineta!');

                    if (pollingIntervalId) {
                        clearInterval(pollingIntervalId);
                        pollingIntervalId = null;
                    }

                    const modal = document.getElementById('modal-status-point');
                    if (modal) modal.remove();

                    alert('❌ Transação recusada ou cancelada na maquineta Point: ' + (data.motivo || 'Pagamento não autorizado'));
                }
            }
        } catch (error) {
            console.error('[Point] Erro no polling:', error);
        }
    }, 4000); // Polling a cada 4 segundos
}

window.cancelarPollingPix = function() {
    if (pollingIntervalId) {
        clearInterval(pollingIntervalId);
        pollingIntervalId = null;
    }
    const modalPix = document.getElementById('modal-pix-asaas');
    if (modalPix) modalPix.remove();
}
