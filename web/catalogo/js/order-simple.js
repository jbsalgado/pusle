// order.js - Gerenciamento de pedidos

import { CONFIG, API_ENDPOINTS } from './config.js';
import { salvarPedidoPendente } from './storage.js';
import { validarUUID } from './utils.js';

function validarDadosPedido(dadosPedido, carrinho) {
    if (carrinho.length === 0) {
        throw new Error('Carrinho está vazio');
    }

    if (!dadosPedido.cliente_id) {
        throw new Error('Cliente não identificado. Por favor, busque o CPF do cliente.');
    }

    if (!validarUUID(dadosPedido.cliente_id)) {
        throw new Error('ID do cliente inválido. Por favor, busque o CPF novamente.');
    }

    if (!dadosPedido.forma_pagamento_id) {
        throw new Error('Por favor, selecione a forma de pagamento.');
    }

    const numeroParcelas = parseInt(dadosPedido.numero_parcelas, 10) || 1;
    if (numeroParcelas > 1) {
        if (!dadosPedido.data_primeiro_pagamento) {
            throw new Error('Por favor, informe a data do primeiro pagamento para vendas parceladas.');
        }
        
        if (!dadosPedido.intervalo_dias_parcelas || dadosPedido.intervalo_dias_parcelas < 1) {
            throw new Error('Por favor, informe um intervalo válido entre as parcelas (mínimo 1 dia).');
        }
        
        if (dadosPedido.intervalo_dias_parcelas > 365) {
            throw new Error('O intervalo entre parcelas não pode ser maior que 365 dias.');
        }
    }

    return true;
}

function prepararObjetoPedido(dadosPedido, carrinho) {
    const pedido = {
        usuario_id: CONFIG.ID_USUARIO_LOJA, // ✅ CORREÇÃO: ID da loja (catalogo, alexbird, etc.)
        cliente_id: dadosPedido.cliente_id,
        observacoes: dadosPedido.observacoes || null,
        numero_parcelas: parseInt(dadosPedido.numero_parcelas, 10) || 1,
        forma_pagamento_id: dadosPedido.forma_pagamento_id,
        itens: carrinho.map(item => ({
            produto_id: item.produto_id,
            quantidade: item.quantidade,
            preco_unitario: item.preco_unitario
        }))
    };

    if (dadosPedido.data_primeiro_pagamento) {
        pedido.data_primeiro_pagamento = dadosPedido.data_primeiro_pagamento;
    }
    
    if (dadosPedido.intervalo_dias_parcelas) {
        pedido.intervalo_dias_parcelas = parseInt(dadosPedido.intervalo_dias_parcelas, 10);
    }

    if (dadosPedido.colaborador_vendedor_id) {
        pedido.colaborador_vendedor_id = dadosPedido.colaborador_vendedor_id;
    }

    return pedido;
}

/**
 * Tenta enviar o pedido diretamente via fetch
 * @returns {Promise<Object>} { sucesso: boolean, dados?: any, erro?: string }
 */
async function tentarEnvioDireto(pedido) {
    try {
        console.log('[Order] 🌐 Tentando envio direto...');
        console.log('[Order] 📦 Pedido:', pedido);
        console.log('[Order] 🎯 URL:', API_ENDPOINTS.PEDIDO);
        
        const response = await fetch(API_ENDPOINTS.PEDIDO, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(pedido)
        });

        console.log('[Order] 📡 Status:', response.status, response.statusText);

        if (response.ok) {
            const resultado = await response.json();
            console.log('[Order] ✅ Pedido enviado com sucesso (direto)!');
            console.log('[Order] 📄 Resposta:', resultado);
            
            return {
                sucesso: true,
                dados: resultado
            };
        } else {
            const erro = await response.text();
            console.error('[Order] ❌ Erro no envio:', erro);
            
            return {
                sucesso: false,
                erro: `Erro ${response.status}: ${erro}`
            };
        }
    } catch (error) {
        console.error('[Order] ❌ Falha na requisição:', error.message);
        
        return {
            sucesso: false,
            erro: error.message
        };
    }
}

async function registrarSyncPedido() {
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        try {
            const swReg = await navigator.serviceWorker.ready;
            await swReg.sync.register(CONFIG.SYNC_TAG);
            return true;
        } catch (err) {
            console.error('[Order] Falha ao registrar sync:', err);
            return false;
        }
    }
    return false;
}

export async function finalizarPedido(dadosPedido, carrinho) {
    try {
        validarDadosPedido(dadosPedido, carrinho);
        const pedido = prepararObjetoPedido(dadosPedido, carrinho);
        
        console.log('[Order] 🚀 Iniciando finalização do pedido...');
        console.log('[Order] 🏪 Loja (usuario_id):', pedido.usuario_id);
        
        // ESTRATÉGIA 1: Tentar envio direto primeiro (funciona independente do SW scope)
        const resultadoDireto = await tentarEnvioDireto(pedido);
        
        if (resultadoDireto.sucesso) {
            // ✅ Enviado com sucesso diretamente!
            console.log('[Order] 🎉 Pedido finalizado com sucesso via envio direto');
            
            return {
                sucesso: true,
                mensagem: `Pedido realizado com sucesso!\n\nNúmero: ${resultadoDireto.dados.venda?.id || 'N/A'}\nValor Total: R$ ${resultadoDireto.dados.venda?.valor_total || '0.00'}`
            };
        }
        
        // ESTRATÉGIA 2: Se falhou, salvar localmente e tentar Background Sync
        console.warn('[Order] ⚠️ Envio direto falhou, salvando para sincronização...');
        console.warn('[Order] Motivo:', resultadoDireto.erro);
        
        const salvou = await salvarPedidoPendente(pedido);
        if (!salvou) {
            throw new Error('Erro ao salvar pedido localmente');
        }
        
        console.log('[Order] 💾 Pedido salvo localmente');
        
        const syncRegistrado = await registrarSyncPedido();
        
        if (syncRegistrado) {
            return {
                sucesso: true,
                mensagem: 'Conexão instável. Pedido salvo localmente e será enviado automaticamente assim que houver conexão.'
            };
        } else {
            return {
                sucesso: true,
                mensagem: 'Pedido salvo localmente. Por favor, recarregue a página quando estiver online para sincronizar.'
            };
        }
        
    } catch (error) {
        console.error('[Order] ❌ Erro ao finalizar pedido:', error);
        throw error;
    }
}