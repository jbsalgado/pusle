// order.js - Gerenciamento de pedidos com suporte a gateways de pagamento
// VERSÃO CORRIGIDA - Melhor tratamento de erros e debugging

import { CONFIG, API_ENDPOINTS, GATEWAY_CONFIG } from './config.js';
import { salvarPedidoPendente } from './storage.js';
import { validarUUID } from './utils.js';
import { processarPagamento } from './gateway-pagamento.js';

/**
 * Valida dados do pedido antes de enviar
 */
function validarDadosPedido(dadosPedido, carrinho) {
    console.log('[Order] 📋 Validando dados do pedido...', dadosPedido);
    
    if (carrinho.length === 0) {
        throw new Error('Carrinho está vazio');
    }

    if (!dadosPedido.cliente_id) {
        throw new Error('Cliente não identificado. Por favor, busque o CPF do cliente.');
    }

    if (!validarUUID(dadosPedido.cliente_id)) {
        console.error('[Order] ID cliente inválido:', dadosPedido.cliente_id);
        throw new Error('ID do cliente inválido. Por favor, busque o CPF novamente.');
    }

    // Validações específicas para fluxo interno (quando não usa gateway)
    if (!GATEWAY_CONFIG.habilitado || GATEWAY_CONFIG.gateway === 'nenhum') {
        if (!dadosPedido.forma_pagamento_id) {
            throw new Error('Por favor, selecione a forma de pagamento.');
        }

        const numeroParcelas = parseInt(dadosPedido.numero_parcelas, 10) || 1;
        
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
 * Prepara objeto do pedido para fluxo interno
 */
function prepararObjetoPedido(dadosPedido, carrinho) {
    console.log('[Order] 🔧 Preparando objeto do pedido...');
    
    const pedido = {
        usuario_id: CONFIG.ID_USUARIO_LOJA,
        cliente_id: dadosPedido.cliente_id,
        observacoes: dadosPedido.observacoes || null,
        numero_parcelas: parseInt(dadosPedido.numero_parcelas, 10) || 1,
        forma_pagamento_id: dadosPedido.forma_pagamento_id,
        itens: carrinho.map(item => ({
            produto_id: item.produto_id || item.id,
            quantidade: item.quantidade,
            preco_unitario: item.preco_venda_sugerido
        }))
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

    if (dadosPedido.colaborador_vendedor_id) {
        pedido.colaborador_vendedor_id = dadosPedido.colaborador_vendedor_id;
    }

    console.log('[Order] 📦 Pedido preparado:', pedido);
    return pedido;
}

/**
 * Tenta enviar o pedido diretamente via fetch (fluxo interno)
 */
async function tentarEnvioDireto(pedido) {
    try {
        console.log('[Order] 🌐 Tentando envio direto...');
        console.log('[Order] 📦 Pedido:', JSON.stringify(pedido, null, 2));
        console.log('[Order] 🎯 URL:', API_ENDPOINTS.PEDIDO);
        
        const response = await fetch(API_ENDPOINTS.PEDIDO, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
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
                console.log('[Order] ✅ Pedido enviado com sucesso!');
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
        console.log('[Order] 🌐 Conexão restaurada! Verificando pedidos pendentes...');
        
        const { idbKeyval } = await import('./utils.js');
        const { STORAGE_KEYS } = await import('./config.js');
        
        try {
            const pedidoPendente = await idbKeyval.get(STORAGE_KEYS.PEDIDO_PENDENTE);
            
            if (pedidoPendente) {
                console.log('[Order] 📦 Pedido pendente encontrado, tentando reenviar...');
                
                const resultado = await tentarEnvioDireto(pedidoPendente);
                
                if (resultado.sucesso) {
                    console.log('[Order] ✅ Pedido pendente enviado com sucesso!');
                    
                    await idbKeyval.del(STORAGE_KEYS.PEDIDO_PENDENTE);
                    
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('Pedido Enviado', {
                            body: 'Seu pedido offline foi enviado com sucesso!',
                            icon: '/favicon.ico'
                        });
                    } else {
                        alert('Pedido offline enviado com sucesso!');
                    }
                    
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    console.error('[Order] ❌ Falha ao reenviar pedido pendente:', resultado.erro);
                }
            } else {
                console.log('[Order] ℹ️ Nenhum pedido pendente para sincronizar');
            }
        } catch (error) {
            console.error('[Order] ❌ Erro ao verificar pedidos pendentes:', error);
        }
    });
    
    console.log('[Order] 👂 Listener de reconexão configurado');
}

// Configurar listener automaticamente
configurarSincronizacaoManual();

/**
 * Busca dados do cliente pela API - VERSÃO MELHORADA
 */
async function buscarDadosCliente(clienteId) {
    console.log('[Order] 🔍 Buscando dados do cliente:', clienteId);
    
    try {
        const url = `${API_ENDPOINTS.CLIENTE}/${clienteId}`;
        console.log('[Order] 📡 URL da API:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        console.log('[Order] 📡 Status da resposta:', response.status);
        
        // Obter resposta como texto primeiro
        const responseText = await response.text();
        console.log('[Order] 📄 Resposta bruta:', responseText);
        
        if (!response.ok) {
            // Tentar parsear erro JSON se possível
            let errorMessage = `Erro ao buscar dados do cliente (Status: ${response.status})`;
            try {
                const errorJson = JSON.parse(responseText);
                if (errorJson.message || errorJson.error) {
                    errorMessage += `: ${errorJson.message || errorJson.error}`;
                }
            } catch (e) {
                // Se não for JSON, usar texto bruto
                if (responseText) {
                    errorMessage += `: ${responseText}`;
                }
            }
            throw new Error(errorMessage);
        }
        
        // Tentar parsear resposta JSON
        try {
            const cliente = JSON.parse(responseText);
            console.log('[Order] ✅ Cliente encontrado:', cliente);
            
            // Validar dados mínimos do cliente
            if (!cliente || !cliente.id) {
                throw new Error('Dados do cliente incompletos ou inválidos');
            }
            
            return cliente;
        } catch (e) {
            console.error('[Order] ❌ Erro ao parsear JSON do cliente:', e);
            throw new Error('Resposta inválida do servidor ao buscar cliente');
        }
        
    } catch (error) {
        console.error('[Order] ❌ Erro ao buscar cliente:', error);
        console.error('[Order] Stack trace:', error.stack);
        throw error; // Re-lança o erro para que finalizarPedido possa capturá-lo
    }
}

/**
 * FUNÇÃO PRINCIPAL: Finalizar Pedido
 * Decide se usa gateway externo ou fluxo interno
 */
export async function finalizarPedido(dadosPedido, carrinho) {
    try {
        // 1️⃣ VALIDAR DADOS
        validarDadosPedido(dadosPedido, carrinho);
        
        console.log('[Order] 🚀 Iniciando finalização do pedido...');
        console.log('[Order] 🏪 Loja (usuario_id):', CONFIG.ID_USUARIO_LOJA);
        console.log('[Order] 💳 Gateway:', GATEWAY_CONFIG.gateway);
        console.log('[Order] 📊 Gateway habilitado:', GATEWAY_CONFIG.habilitado);
        
        // 2️⃣ DECIDIR FLUXO: Gateway Externo vs. Interno
        
        if (GATEWAY_CONFIG.habilitado && GATEWAY_CONFIG.gateway !== 'nenhum') {
            // ============================================
            // FLUXO COM GATEWAY EXTERNO (MP ou Asaas)
            // ============================================
            console.log('[Order] 🔵 Usando gateway externo:', GATEWAY_CONFIG.gateway);
            
            // Buscar dados do cliente com tratamento de erro melhorado
            let cliente = null;
            try {
                cliente = await buscarDadosCliente(dadosPedido.cliente_id);
            } catch (error) {
                console.error('[Order] ❌ Falha ao buscar dados do cliente:', error);
                
                // Oferecer opção de continuar sem gateway se falhar
                const continuarSemGateway = confirm(
                    'Erro ao buscar dados do cliente.\n\n' +
                    'Detalhes: ' + error.message + '\n\n' +
                    'Deseja tentar enviar o pedido sem usar o gateway de pagamento?'
                );
                
                if (continuarSemGateway) {
                    console.log('[Order] ⚠️ Continuando sem gateway por escolha do usuário');
                    // Mudar para fluxo interno temporariamente
                    GATEWAY_CONFIG.habilitado = false;
                    return await finalizarPedido(dadosPedido, carrinho);
                } else {
                    throw error;
                }
            }
            
            // Verificação extra de segurança
            if (!cliente || !cliente.id) {
                throw new Error('Dados do cliente não disponíveis para processamento do pagamento');
            }
            
            // Processar via gateway (redireciona ou mostra modal)
            return await processarPagamento(dadosPedido, carrinho, cliente);
            
        } else {
            // ============================================
            // FLUXO INTERNO (Atual - Sem Gateway)
            // ============================================
            console.log('[Order] 🟢 Usando fluxo interno (sem gateway)');
            
            const pedido = prepararObjetoPedido(dadosPedido, carrinho);
            
            // Verificar se está online
            const estaOnline = navigator.onLine;
            console.log('[Order] 📶 Status da conexão:', estaOnline ? 'ONLINE' : 'OFFLINE');
            
            if (!estaOnline) {
                // OFFLINE: Salvar localmente
                console.log('[Order] 🔴 Offline detectado, salvando localmente...');
                
                const salvou = await salvarPedidoPendente(pedido);
                if (!salvou) {
                    throw new Error('Erro ao salvar pedido localmente');
                }
                
                await registrarSyncPedido();
                
                return {
                    sucesso: true,
                    offline: true,
                    mensagem: 'Você está offline. O pedido foi salvo localmente e será enviado automaticamente quando a conexão for restaurada.'
                };
            }
            
            // ONLINE: Tentar envio direto
            const resultadoDireto = await tentarEnvioDireto(pedido);
            
            if (resultadoDireto.sucesso) {
                // ✅ Enviado com sucesso!
                console.log('[Order] 🎉 Pedido finalizado com sucesso via envio direto');
                
                return {
                    sucesso: true,
                    mensagem: `Pedido realizado com sucesso!\n\nNúmero: ${resultadoDireto.dados.venda?.id || 'N/A'}\nValor Total: R$ ${resultadoDireto.dados.venda?.valor_total || '0.00'}`
                };
            }
            
            // Envio falhou - salvar localmente
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
                    offline: true,
                    mensagem: 'Conexão instável. Pedido salvo localmente e será enviado automaticamente assim que a conexão melhorar.'
                };
            } else {
                return {
                    sucesso: true,
                    offline: true,
                    mensagem: 'Pedido salvo localmente. Será enviado automaticamente quando a conexão for restaurada. Mantenha esta aba aberta.'
                };
            }
        }
        
    } catch (error) {
        console.error('[Order] ❌ Erro ao finalizar pedido:', error);
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

// Exportar função de debug para testes
export async function testarConexaoCliente(clienteId) {
    try {
        console.log('[Order Debug] 🧪 Testando conexão com API de cliente...');
        const cliente = await buscarDadosCliente(clienteId);
        console.log('[Order Debug] ✅ Teste concluído com sucesso:', cliente);
        return cliente;
    } catch (error) {
        console.error('[Order Debug] ❌ Teste falhou:', error);
        throw error;
    }
}