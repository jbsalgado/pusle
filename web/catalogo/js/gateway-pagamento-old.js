// gateway-pagamento.js - VERSÃO CORRIGIDA
import { GATEWAY_CONFIG, API_ENDPOINTS, CONFIG } from './config.js';

let pollingIntervalId = null;
let pollingAttempts = 0;
const maxPollingAttempts = 60;

async function verificarStatusPagamento(paymentId) {
    console.log(`[Gateway] 🔍 polling... (tentativa ${pollingAttempts})`);
    
    const statusText = document.getElementById('pix-status-text');
    if (statusText) {
        statusText.textContent = `Aguardando confirmação... (Verificação ${pollingAttempts})`;
    }

    try {
        const url = `${API_ENDPOINTS.ASAAS_CONSULTAR_STATUS}?payment_id=${paymentId}&usuario_id=${CONFIG.ID_USUARIO_LOJA}`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        // ✅ CORREÇÃO 1: Verifica resposta antes de parsear
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('[Gateway] ❌ Resposta não é JSON:', await response.text());
            return { status: 'pendente' };
        }
        
        if (!response.ok) {
            console.warn('[Gateway] ⚠️ Servidor respondeu com erro', response.status);
            return { status: 'pendente' };
        }
        
        const resultado = await response.json();
        console.log('[Gateway] 📡 Status recebido:', resultado);
        
        // ✅ CORREÇÃO 2: Aceita múltiplos status de sucesso
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
        
        // Atualiza status no modal
        if (statusText) {
            statusText.textContent = `Status: ${statusAsaas || statusLocal || 'PENDENTE'}`;
        }
        return { status: 'pendente' };

    } catch (error) {
        console.error('[Gateway] ❌ Erro no polling:', error);
        if (statusText) statusText.textContent = 'Erro de rede. Verificando...';
        return { status: 'pendente' };
    }
}

function tratarPagamentoConfirmado(pedidoId, dados) {
    if (pollingIntervalId) {
        clearInterval(pollingIntervalId);
        pollingIntervalId = null;
    }
    
    const modalPix = document.getElementById('modal-pix-asaas');
    if (modalPix) modalPix.remove();
    
    console.log('[Gateway] ✅ Pagamento confirmado! Pedido:', pedidoId);
    
    window.dispatchEvent(new CustomEvent('pagamentoConfirmado', {
        detail: {
            pedidoId: pedidoId,
            gateway: 'asaas',
            dados: dados
        }
    }));
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
            console.warn('[Gateway] ⏱️ Polling: Tempo máximo atingido.');
            return;
        }

        const resultado = await verificarStatusPagamento(paymentId);
        
        if (resultado.status === 'pago') {
            console.log('[Gateway] ✅ Pagamento confirmado via polling!');
            tratarPagamentoConfirmado(resultado.pedido_id, resultado.dados);
        }
        
    }, 5000);
}

window.cancelarPollingPix = function() {
    if (pollingIntervalId) {
        clearInterval(pollingIntervalId);
        pollingIntervalId = null;
        console.log('[Gateway] 🛑 Polling cancelado pelo usuário.');
    }
    const modalPix = document.getElementById('modal-pix-asaas');
    if (modalPix) modalPix.remove();
}

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

async function processarMercadoPago(dadosPedido, carrinho, cliente) {
    try {
        const payload = {
            usuario_id: CONFIG.ID_USUARIO_LOJA,
            cliente_id: dadosPedido.cliente_id,
            itens: carrinho.map(item => ({
                produto_id: item.produto_id || item.id || null,
                nome: item.nome || 'Produto',
                descricao: item.descricao || '',
                quantidade: item.quantidade || 1,
                preco_unitario: item.preco_venda_sugerido || 0
            })),
            cliente: {
                nome: cliente.nome || '',
                sobrenome: cliente.sobrenome || '',
                email: cliente.email || '',
                telefone: cliente.telefone || '',
                cpf: cliente.cpf_cnpj || '',
                cep: cliente.cep || '',
                logradouro: cliente.logradouro || '',
                numero: cliente.numero || ''
            }
        };
        
        const response = await fetch(API_ENDPOINTS.MERCADOPAGO_CRIAR_PREFERENCIA, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        if (!response.ok) {
            const erro = await response.json();
            throw new Error(erro.erro || 'Erro ao criar preferência');
        }
        
        const resultado = await response.json();
        
        localStorage.setItem('mp_preference_id', resultado.preference_id);
        localStorage.setItem('mp_external_ref', resultado.external_reference);
        
        window.location.href = resultado.init_point;
        
        return {
            sucesso: true,
            gateway: 'mercadopago',
            redirecionado: true
        };
        
    } catch (error) {
        console.error('[MP] ❌ Erro:', error);
        throw error;
    }
}

async function processarAsaas(dadosPedido, carrinho, cliente) {
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
        
        const resultado = await response.json();
        
        localStorage.setItem('asaas_payment_id', resultado.payment_id);
        localStorage.setItem('asaas_external_ref', resultado.external_reference);
        
        if (resultado.pix) {
            mostrarModalPix(resultado.pix);
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

async function processarFluxoInterno(dadosPedido, carrinho) {
    const { finalizarPedido } = await import('./order.js');
    return await finalizarPedido(dadosPedido, carrinho);
}

function mostrarModalPix(pixData) {
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
                    class="w-full bg-blue-500 text-white py-3 rounded-lg mb-4 hover:bg-blue-600">
                📋 Copiar Código PIX
            </button>
            
            <div class="text-center bg-gray-50 p-3 rounded-lg">
                <p id="pix-status-text" class="text-sm text-gray-700 font-medium">
                    Aguardando confirmação...
                </p>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                    <div class="bg-blue-500 h-1.5 rounded-full animate-pulse"></div>
                </div>
            </div>
            
            <button onclick="window.cancelarPollingPix()" 
                    class="w-full text-gray-500 text-sm py-2 mt-3 hover:text-gray-700">
                Fechar
            </button>
        </div>
    `;
    
    document.body.appendChild(modal);
}