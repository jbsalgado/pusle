// order.js - Gerenciamento de pedidos VENDA DIRETA (sem cliente obrigatório)
// VERSÃO MODIFICADA PARA VENDA DIRETA

import { CONFIG, API_ENDPOINTS, GATEWAY_CONFIG } from './config.js';
import { salvarPedidoPendente, getToken } from './storage.js';
import { validarUUID } from './utils.js';
import { getAcrescimo } from './cart.js?v=surcharge_fix';

/**
 * Valida dados do pedido antes de enviar (VENDA DIRETA - cliente opcional)
 */
function validarDadosPedido(dadosPedido, carrinho) {
    console.log('[Order] 📋 Validando dados do pedido (Venda Direta)...', dadosPedido);
    
    if (carrinho.length === 0) {
        throw new Error('Carrinho está vazio');
    }

    // VENDA DIRETA: cliente_id pode ser null
    // Não valida cliente_id aqui

    // Validações específicas para fluxo interno (quando não usa gateway)
    if (!GATEWAY_CONFIG.habilitado || GATEWAY_CONFIG.gateway === 'nenhum') {
        if (!dadosPedido.forma_pagamento_id) {
            throw new Error('Por favor, selecione a forma de pagamento.');
        }

        let numeroParcelas = parseInt(dadosPedido.numero_parcelas, 10) || 1;
        
        // ✅ VALIDAÇÃO: DINHEIRO e PIX não permitem parcelamento
        // Busca a forma de pagamento para verificar o tipo
        if (dadosPedido.forma_pagamento_id) {
            // Tenta buscar a forma de pagamento do array global (se disponível)
            const formasPagamento = window.formasPagamento || [];
            const formaSelecionada = formasPagamento.find(f => f.id === dadosPedido.forma_pagamento_id);
            
            if (formaSelecionada) {
                const tipo = formaSelecionada.tipo || '';
                if (tipo === 'DINHEIRO' || tipo === 'PIX') {
                    // Força para 1 parcela (à vista) se tentar parcelar
                    if (numeroParcelas > 1) {
                        console.warn('[Order] ⚠️ Tentativa de parcelar com', tipo, '- forçando para à vista');
                        numeroParcelas = 1;
                        dadosPedido.numero_parcelas = 1;
                    }
                }
            }
        }
        
        // Validar parcelas apenas quando número de parcelas > 1
        if (numeroParcelas > 1) {
            if (!dadosPedido.data_primeiro_pagamento) {
                throw new Error('Por favor, informe a data do primeiro pagamento para vendas parceladas.');
            }
            
            const intervaloDias = parseInt(dadosPedido.intervalo_dias_parcelas, 10);
            
            if (isNaN(intervaloDias) || intervaloDias < 1) {
                throw new Error('Por favor, informe um intervalo válido entre as parcelas (mínimo 1 dia).');
            }
            
            if (intervaloDias > 365) {
                throw new Error('O intervalo entre parcelas não pode ser maior que 365 dias.');
            }
        }
    }

    console.log('[Order] ✅ Validação concluída com sucesso');
    return true;
}

/**
 * Prepara objeto do pedido para fluxo interno (VENDA DIRETA)
 */
function prepararObjetoPedido(dadosPedido, carrinho) {
    console.log('[Order] 🔧 Preparando objeto do pedido (Venda Direta)...');
    
    // Obtém dados do acréscimo do carrinho
    const acrescimo = getAcrescimo();
    
    const pedido = {
        usuario_id: CONFIG.ID_USUARIO_LOJA,
        cliente_id: dadosPedido.cliente_id || null, // VENDA DIRETA: pode ser null
        observacoes: dadosPedido.observacoes || null,
        numero_parcelas: parseInt(dadosPedido.numero_parcelas, 10) || 1,
        forma_pagamento_id: dadosPedido.forma_pagamento_id,
        is_venda_direta: true, // ✅ MARCADOR: Identifica que é venda direta (loja física)
        itens: carrinho.map(item => ({
            produto_id: item.produto_id || item.id,
            quantidade: item.quantidade,
            // ✅ CORREÇÃO: Usar preço promocional se disponível (preco_final), senão usar preco_venda_sugerido
            preco_unitario: item.preco_final || item.preco_venda_sugerido,
            desconto_percentual: item.descontoPercentual || 0,
            desconto_valor: item.descontoValor || 0
        })),
        // Adiciona dados do acréscimo
        acrescimo_valor: parseFloat(acrescimo.valor) || 0,
        acrescimo_tipo: acrescimo.tipo || null,
        observacao_acrescimo: acrescimo.observacao || null
    };

    // Só incluir campos de parcelamento se realmente houver parcelas
    if (pedido.numero_parcelas > 1) {
        if (dadosPedido.data_primeiro_pagamento) {
            pedido.data_primeiro_pagamento = dadosPedido.data_primeiro_pagamento;
        }
        
        if (dadosPedido.intervalo_dias_parcelas) {
            pedido.intervalo_dias_parcelas = parseInt(dadosPedido.intervalo_dias_parcelas, 10);
        }
    }

    // Vendedor opcional (para comissão)
    if (dadosPedido.colaborador_vendedor_id) {
        pedido.colaborador_vendedor_id = dadosPedido.colaborador_vendedor_id;
    }

    console.log('[Order] 📦 Pedido preparado (Venda Direta):', pedido);
    return pedido;
}

/**
 * Tenta enviar o pedido diretamente via fetch (fluxo interno)
 */
async function tentarEnvioDireto(pedido) {
    try {
        console.log('[Order] 🌐 Tentando envio direto (Venda Direta)...');
        console.log('[Order] 📦 Pedido:', JSON.stringify(pedido, null, 2));
        console.log('[Order] 🎯 URL:', API_ENDPOINTS.PEDIDO_CREATE);
        
        const token = await getToken();
        console.log('[Order] 🔑 Token obtido para envio:', token ? 'Sim (mascarado: ' + token.substring(0, 10) + '...)' : 'Não');
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const response = await fetch(API_ENDPOINTS.PEDIDO_CREATE, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(pedido),
            signal: AbortSignal.timeout(10000)
        });

        console.log('[Order] 📡 Status:', response.status, response.statusText);
        
        // Tentar obter resposta como texto primeiro
        const responseText = await response.text();
        console.log('[Order] 📄 Resposta bruta:', responseText);

        if (response.ok) {
            try {
                const resultado = JSON.parse(responseText);
                console.log('[Order] ✅ Venda enviada com sucesso!');
                console.log('[Order] 📄 Resultado:', resultado);
                
                return {
                    sucesso: true,
                    dados: resultado
                };
            } catch (e) {
                console.error('[Order] ⚠️ Resposta OK mas não é JSON válido:', e);
                return {
                    sucesso: true,
                    dados: { message: responseText }
                };
            }
        } else {
            console.error('[Order] ❌ Erro no envio. Status:', response.status);
            console.error('[Order] ❌ Resposta:', responseText);
            
            return {
                sucesso: false,
                erro: `Erro ${response.status}: ${responseText}`
            };
        }
    } catch (error) {
        console.error('[Order] ❌ Falha na requisição:', error);
        console.error('[Order] Stack trace:', error.stack);
        
        return {
            sucesso: false,
            erro: error.message,
            offline: error.name === 'TypeError' || error.name === 'TimeoutError'
        };
    }
}

/**
 * Tenta registrar Background Sync
 */
async function registrarSyncPedido() {
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        try {
            const swReg = await navigator.serviceWorker.ready;
            await swReg.sync.register(CONFIG.SYNC_TAG);
            console.log('[Order] 🔄 Background Sync registrado');
            return true;
        } catch (err) {
            console.error('[Order] ⚠️ Falha ao registrar sync:', err);
            return false;
        }
    }
    console.log('[Order] ℹ️ Background Sync não disponível');
    return false;
}

/**
 * Configura sincronização manual quando voltar online
 */
function configurarSincronizacaoManual() {
    window.addEventListener('online', async () => {
        console.log('[Order] 🌐 Conexão restaurada! Verificando vendas pendentes...');
        
        const { idbKeyval } = await import('./utils.js');
        const { STORAGE_KEYS } = await import('./config.js');
        
        try {
            const pedidoPendente = await idbKeyval.get(STORAGE_KEYS.PEDIDO_PENDENTE);
            
            if (pedidoPendente) {
                console.log('[Order] 📦 Venda pendente encontrada, tentando reenviar...');
                
                const resultado = await tentarEnvioDireto(pedidoPendente);
                
                if (resultado.sucesso) {
                    console.log('[Order] ✅ Venda pendente enviada com sucesso!');
                    
                    await idbKeyval.del(STORAGE_KEYS.PEDIDO_PENDENTE);
                    
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('Venda Enviada', {
                            body: 'Sua venda offline foi enviada com sucesso!',
                            icon: '/favicon.ico'
                        });
                    } else {
                        alert('Venda offline enviada com sucesso!');
                    }
                    
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    console.error('[Order] ❌ Falha ao reenviar venda pendente:', resultado.erro);
                }
            } else {
                console.log('[Order] ℹ️ Nenhuma venda pendente para sincronizar');
            }
        } catch (error) {
            console.error('[Order] ❌ Erro ao verificar vendas pendentes:', error);
        }
    });
    
    console.log('[Order] 👂 Listener de reconexão configurado');
}

// Configurar listener automaticamente
configurarSincronizacaoManual();

/**
 * FUNÇÃO PRINCIPAL: Finalizar Pedido (VENDA DIRETA)
 * VENDA DIRETA: Pode usar gateway externo (Mercado Pago/Asaas) ou fluxo interno
 */
export async function finalizarPedido(dadosPedido, carrinho) {
    try {
        // 1️⃣ VALIDAR DADOS
        validarDadosPedido(dadosPedido, carrinho);
        
        console.log('[Order] 🚀 Iniciando finalização da venda direta...');
        console.log('[Order] 🏪 Loja (usuario_id):', CONFIG.ID_USUARIO_LOJA);
        console.log('[Order] 👤 Vendedor:', dadosPedido.colaborador_vendedor_id || 'Não informado (sem comissão)');
        
        // 2️⃣ VERIFICAR SE DEVE USAR GATEWAY
        const formaPagamentoSelecionada = window.formasPagamento?.find(fp => fp.id === dadosPedido.forma_pagamento_id);
        const tipoFormaPagamento = formaPagamentoSelecionada?.tipo || '';
        const usaFluxoInterno = tipoFormaPagamento === 'PIX_ESTATICO' || tipoFormaPagamento === 'PAGAR_AO_ENTREGADOR' || tipoFormaPagamento === 'DINHEIRO';
        
        // Obter GATEWAY_CONFIG do window (carregado pelo app.js)
        const gatewayConfig = window.GATEWAY_CONFIG || { habilitado: false, gateway: 'nenhum' };
        
        // Se gateway está habilitado E a forma de pagamento requer gateway (MERCADOPAGO, PIX dinâmico, POINT etc)
        if (gatewayConfig.habilitado && !usaFluxoInterno && (tipoFormaPagamento === 'MERCADOPAGO' || tipoFormaPagamento === 'PIX' || tipoFormaPagamento === 'MP_POINT')) {
            console.log('[Order] 🔵 Usando gateway externo:', gatewayConfig.gateway);
            
            // Buscar dados do cliente
            let cliente = null;
            if (dadosPedido.cliente_id) {
                try {
                    const { buscarClientePorId } = await import('./customer.js');
                    cliente = await buscarClientePorId(dadosPedido.cliente_id);
                } catch (error) {
                    console.error('[Order] ❌ Falha ao buscar dados do cliente:', error);
                    throw new Error('Erro ao buscar dados do cliente para processamento do pagamento');
                }
            } else {
                throw new Error('Cliente é obrigatório para pagamento via gateway');
            }
            
            // Processar via gateway
            const { processarPagamento } = await import('./gateway-pagamento.js');
            return await processarPagamento(dadosPedido, carrinho, cliente);
        }
        
        // VENDA DIRETA: Usa fluxo interno (sem gateway)
        console.log('[Order] 🟢 Usando fluxo interno (Venda Direta)');
        
        const pedido = prepararObjetoPedido(dadosPedido, carrinho);
        
        // Verificar se está online
        const estaOnline = navigator.onLine;
        console.log('[Order] 📶 Status da conexão:', estaOnline ? 'ONLINE' : 'OFFLINE');
        
        if (!estaOnline) {
            // OFFLINE: Salvar localmente
            console.log('[Order] 🔴 Offline detectado, salvando localmente...');
            
            const salvou = await salvarPedidoPendente(pedido);
            if (!salvou) {
                throw new Error('Erro ao salvar venda localmente');
            }
            
            await registrarSyncPedido();
            
            return {
                sucesso: true,
                offline: true,
                mensagem: 'Você está offline. A venda foi salva localmente e será enviada automaticamente quando a conexão for restaurada.'
            };
        }
        
        // ONLINE: Tentar envio direto
        const resultadoDireto = await tentarEnvioDireto(pedido);
        
        if (resultadoDireto.sucesso) {
            // ✅ Enviado com sucesso!
            console.log('[Order] 🎉 Venda finalizada com sucesso via envio direto');
            
            return {
                sucesso: true,
                dados: resultadoDireto.dados, // ✅ CORREÇÃO: Retorna os dados completos incluindo parcelas
                mensagem: `Venda realizada com sucesso!\n\nNúmero: ${resultadoDireto.dados?.id || resultadoDireto.dados?.venda?.id || 'N/A'}\nValor Total: R$ ${resultadoDireto.dados?.valor_total || resultadoDireto.dados?.venda?.valor_total || '0.00'}`
            };
        }
        
        // Envio falhou - salvar localmente
        console.warn('[Order] ⚠️ Envio direto falhou, salvando para sincronização...');
        console.warn('[Order] Motivo:', resultadoDireto.erro);
        
        const salvou = await salvarPedidoPendente(pedido);
        if (!salvou) {
            throw new Error('Erro ao salvar venda localmente');
        }
        
        console.log('[Order] 💾 Venda salva localmente');
        
        const syncRegistrado = await registrarSyncPedido();
        
        if (syncRegistrado) {
            return {
                sucesso: true,
                offline: true,
                mensagem: 'Conexão instável. Venda salva localmente e será enviada automaticamente assim que a conexão melhorar.'
            };
        } else {
            return {
                sucesso: true,
                offline: true,
                mensagem: 'Venda salva localmente. Será enviada automaticamente quando a conexão for restaurada. Mantenha esta aba aberta.'
            };
        }
        
    } catch (error) {
        console.error('[Order] ❌ Erro ao finalizar venda:', error);
        console.error('[Order] Stack trace:', error.stack);
        throw error;
    }
}

/**
 * Cancela um pedido (se necessário)
 */
export async function cancelarPedido(pedidoId) {
    try {
        const response = await fetch(`${API_ENDPOINTS.PEDIDO}/${pedidoId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Erro ao cancelar pedido');
        }
        
        return await response.json();
    } catch (error) {
        console.error('[Order] Erro ao cancelar pedido:', error);
        throw error;
    }
}

