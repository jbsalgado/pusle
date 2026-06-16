// gateway-pagamento.js - VERSÃO COM BOTÃO DE TESTE SANDBOX
import { GATEWAY_CONFIG, API_ENDPOINTS, CONFIG } from './config.js';

let pollingIntervalId = null;
let pollingAttempts = 0;
const maxPollingAttempts = 60;
let currentPaymentId = null; // Guardar o payment_id atual

// async function verificarStatusPagamento(paymentId) {
//     console.log(`[Gateway] 🔍 polling... (tentativa ${pollingAttempts})`);
    
//     const statusText = document.getElementById('pix-status-text');
//     if (statusText) {
//         statusText.textContent = `Aguardando confirmação... (Verificação ${pollingAttempts})`;
//     }

//     try {
//         const url = `${API_ENDPOINTS.ASAAS_CONSULTAR_STATUS}?payment_id=${paymentId}&usuario_id=${CONFIG.ID_USUARIO_LOJA}`;
        
//         const response = await fetch(url, {
//             method: 'GET',
//             headers: {
//                 'Accept': 'application/json',
//                 'Content-Type': 'application/json'
//             }
//         });
        
//         const contentType = response.headers.get('content-type');
//         if (!contentType || !contentType.includes('application/json')) {
//             console.error('[Gateway] ❌ Resposta não é JSON:', await response.text());
//             return { status: 'pendente' };
//         }
        
//         if (!response.ok) {
//             console.warn('[Gateway] ⚠️ Servidor respondeu com erro', response.status);
//             return { status: 'pendente' };
//         }
        
//         const resultado = await response.json();
//         console.log('[Gateway] 📡 Status recebido:', resultado);
        
//         const statusPago = ['pago', 'RECEIVED', 'CONFIRMED', 'received', 'confirmed'];
//         const statusAsaas = (resultado.status_asaas || '').toUpperCase();
//         const statusLocal = (resultado.status || '').toLowerCase();
        
//         if (resultado.sucesso && (statusPago.includes(statusLocal) || statusPago.includes(statusAsaas))) {
//             return { 
//                 status: 'pago', 
//                 pedido_id: resultado.pedido_id,
//                 dados: resultado
//             };
//         }
        
//         if (statusText) {
//             statusText.textContent = `Status: ${statusAsaas || statusLocal || 'PENDENTE'}`;
//         }
//         return { status: 'pendente' };

//     } catch (error) {
//         console.error('[Gateway] ❌ Erro no polling:', error);
//         if (statusText) statusText.textContent = 'Erro de rede. Verificando...';
//         return { status: 'pendente' };
//     }
// }


/**
 * CONSULTA STATUS GENÉRICO (PIX OU CARTÃO)
 */
async function verificarStatusVenda(vendaId) {
    console.log(`[Gateway] 🔍 Verificando status da venda ${vendaId}... (tentativa ${pollingAttempts})`);
    
    // Mostra status no modal se existir
    const statusText = document.getElementById('pix-status-text') || document.getElementById('mp-status-text');
    
    try {
        const url = `${API_ENDPOINTS.PEDIDO_STATUS}?venda_id=${vendaId}&usuario_id=${CONFIG.ID_USUARIO_LOJA}`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });
        
        if (!response.ok) return { status: 'pendente' };
        
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

        const resultado = await verificarStatusVenda(vendaId);
        
        if (resultado.status === 'pago') {
            tratarPagamentoConfirmado(vendaId, resultado.dados, gateway);
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

// ✅ FUNÇÃO PARA TESTE MANUAL NO SANDBOX
window.simularPagamentoSandbox = async function() {
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
        tratarPagamentoConfirmado(currentPaymentId, resultado.dados, window.currentGateway || 'asaas');
    } else {
        alert(`Status atual no sistema: ${resultado.dados?.status || 'PENDENTE'}\n\nSe você já confirmou no sandbox, aguarde alguns segundos e clique novamente.`);
        if (btnTeste) {
            btnTeste.disabled = false;
            btnTeste.textContent = '🧪 Testar Confirmação';
        }
    }
}

export async function processarPagamento(dadosPedido, carrinho, cliente, pedidoId = null) {
    const gateway = GATEWAY_CONFIG.gateway;
    
    console.log('[Gateway] 💳 Processando pagamento via:', gateway, 'PedidoID:', pedidoId);
    
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
            total + ((item.preco_venda_sugerido || 0) * (item.quantidade || 1)), 0
        );

        const payload = {
            tenant_id: CONFIG.ID_USUARIO_LOJA,
            order_id: pedidoId,
            amount: valorTotal,
            description: `Pedido ${pedidoId} - Catálogo`,
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
            } catch (e) {}
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
            currentPaymentId = pedidoId;
            
            // Adapt to the structure expected by mostrarModalPix
            const pixData = {
                encoded_image: resultado.qr_code_base64,
                payload: resultado.qr_code
            };
            
            mostrarModalPix(pixData, resultado.payment_id);
            iniciarPollingStatusVenda(pedidoId, 'mercadopago');
            
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
                    class="w-full bg-brand-500 text-white py-3 rounded-lg mb-4 hover:bg-brand-600">
                📋 Copiar Código PIX
            </button>
            
            <!-- ✅ BOTÃO DE TESTE SANDBOX -->
            <button id="btn-teste-sandbox" onclick="window.simularPagamentoSandbox()" 
                    class="w-full bg-orange-500 text-white py-3 rounded-lg mb-4 hover:bg-orange-600 border-2 border-orange-700">
                🧪 Testar Confirmação (Sandbox)
            </button>
            
            <div class="text-center bg-gray-50 p-3 rounded-lg">
                <p id="pix-status-text" class="text-sm text-gray-700 font-medium">
                    Aguardando confirmação...
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