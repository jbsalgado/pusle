// gateway-pagamento.js - Abstração para gateways de pagamento

import { GATEWAY_CONFIG, API_ENDPOINTS, CONFIG } from './config.js';

/**
 * Redireciona para o gateway apropriado ou usa fluxo interno
 */
export async function processarPagamento(dadosPedido, carrinho, cliente) {
    const gateway = GATEWAY_CONFIG.gateway;
    
    console.log('[Gateway] Processando pagamento via:', gateway);
    
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
 * MERCADO PAGO
 */
async function processarMercadoPago(dadosPedido, carrinho, cliente) {
    try {
        const payload = {
            usuario_id: CONFIG.ID_USUARIO_LOJA,
            cliente_id: dadosPedido.cliente_id,
            itens: carrinho.map(item => ({
                produto_id: item.produto_id,
                nome: item.nome,
                descricao: item.descricao || '',
                quantidade: item.quantidade,
                preco_unitario: item.preco_unitario
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
        
        // Salvar referência
        localStorage.setItem('mp_preference_id', resultado.preference_id);
        localStorage.setItem('mp_external_ref', resultado.external_reference);
        
        // Redirecionar
        window.location.href = resultado.init_point;
        
        return {
            sucesso: true,
            gateway: 'mercadopago',
            redirecionado: true
        };
        
    } catch (error) {
        console.error('[MP] Erro:', error);
        throw error;
    }
}

/**
 * ASAAS
 */
async function processarAsaas(dadosPedido, carrinho, cliente) {
    try {
        // Calcular total
        const valorTotal = carrinho.reduce((total, item) => 
            total + (item.preco_unitario * item.quantidade), 0
        );
        
        const payload = {
            usuario_id: CONFIG.ID_USUARIO_LOJA,
            cliente_id: dadosPedido.cliente_id,
            valor: valorTotal,
            descricao: `Pedido PWA - ${carrinho.length} item(ns)`,
            metodo_pagamento: 'PIX', // ou permitir escolha
            vencimento: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            itens: carrinho,
            cliente: {
                nome: cliente.nome || '',
                email: cliente.email || '',
                cpf: cliente.cpf_cnpj || '',
                telefone: cliente.telefone || '',
                cep: cliente.cep || '',
                endereco: cliente.logradouro || '',
                numero: cliente.numero || '',
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
        
        // Salvar referência
        localStorage.setItem('asaas_payment_id', resultado.payment_id);
        localStorage.setItem('asaas_external_ref', resultado.external_reference);
        
        // Se for PIX, mostrar QR Code
        if (resultado.pix) {
            mostrarModalPix(resultado.pix);
        }
        
        return {
            sucesso: true,
            gateway: 'asaas',
            ...resultado
        };
        
    } catch (error) {
        console.error('[Asaas] Erro:', error);
        throw error;
    }
}

/**
 * FLUXO INTERNO (atual)
 */
async function processarFluxoInterno(dadosPedido, carrinho) {
    // Importar dinamicamente para evitar dependência circular
    const { finalizarPedido } = await import('./order.js');
    return await finalizarPedido(dadosPedido, carrinho);
}

/**
 * Mostra modal com QR Code PIX (Asaas)
 */
function mostrarModalPix(pixData) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-md mx-4">
            <h2 class="text-2xl font-bold mb-4">Pagamento via PIX</h2>
            
            ${pixData.encoded_image ? `
                <img src="data:image/png;base64,${pixData.encoded_image}" 
                     alt="QR Code PIX" 
                     class="w-full mb-4">
            ` : ''}
            
            <div class="bg-gray-100 p-3 rounded mb-4">
                <p class="text-xs text-gray-600 mb-1">Código PIX Copia e Cola:</p>
                <p class="text-sm font-mono break-all">${pixData.payload}</p>
            </div>
            
            <button onclick="navigator.clipboard.writeText('${pixData.payload}')" 
                    class="w-full bg-blue-500 text-white py-3 rounded-lg mb-2">
                📋 Copiar Código PIX
            </button>
            
            <button onclick="this.closest('.fixed').remove()" 
                    class="w-full bg-gray-300 text-gray-700 py-3 rounded-lg">
                Fechar
            </button>
            
            <p class="text-xs text-gray-500 mt-4 text-center">
                Após o pagamento, você receberá uma confirmação
            </p>
        </div>
    `;
    
    document.body.appendChild(modal);
}