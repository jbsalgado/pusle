// order.js - Gerenciamento de pedidos (COM SUPORTE OFFLINE COMPLETO)

import { CONFIG, API_ENDPOINTS } from './config.js';
import { salvarPedidoPendente } from './storage.js';
import { validarUUID } from './utils.js';
import { estaOnline } from './network.js';

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
        usuario_id: CONFIG.ID_USUARIO_LOJA, // ✅ ID da loja (catalogo, alexbird, etc.)
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
            body: JSON.stringify(pedido),
            // ✅ Timeout de 10 segundos para não travar muito se offline
            signal: AbortSignal.timeout(10000)
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
            erro: error.message,
            offline: error.name === 'TypeError' || error.name === 'TimeoutError'
        };
    }
}

/**
 * Tenta registrar Background Sync (se disponível)
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
 * (Funciona independente do Service Worker scope)
 */
function configurarSincronizacaoManual() {
    // ✅ Listener de conexão - funciona em qualquer path
    window.addEventListener('online', async () => {
        console.log('[Order] 🌐 Conexão restaurada! Verificando pedidos pendentes...');
        
        // Importar dinamicamente para evitar dependência circular
        const { idbKeyval } = await import('./utils.js');
        const { STORAGE_KEYS } = await import('./config.js');
        
        try {
            const pedidoPendente = await idbKeyval.get(STORAGE_KEYS.PEDIDO_PENDENTE);
            
            if (pedidoPendente) {
                console.log('[Order] 📦 Pedido pendente encontrado, tentando reenviar...');
                
                const resultado = await tentarEnvioDireto(pedidoPendente);
                
                if (resultado.sucesso) {
                    console.log('[Order] ✅ Pedido pendente enviado com sucesso!');
                    
                    // Remover pedido pendente
                    await idbKeyval.del(STORAGE_KEYS.PEDIDO_PENDENTE);
                    
                    // Notificar usuário
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('Pedido Enviado', {
                            body: 'Seu pedido offline foi enviado com sucesso!',
                            icon: '/favicon.ico'
                        });
                    } else {
                        alert('Pedido offline enviado com sucesso!');
                    }
                    
                    // Recarregar para limpar carrinho e atualizar UI
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

// ✅ Configurar listener automaticamente quando o módulo for carregado
configurarSincronizacaoManual();

export async function finalizarPedido(dadosPedido, carrinho) {
    try {
        validarDadosPedido(dadosPedido, carrinho);
        const pedido = prepararObjetoPedido(dadosPedido, carrinho);
        
        console.log('[Order] 🚀 Iniciando finalização do pedido...');
        console.log('[Order] 🏪 Loja (usuario_id):', pedido.usuario_id);
        console.log('[Order] 📶 Status da conexão:', estaOnline() ? 'ONLINE' : 'OFFLINE');
        
        // ESTRATÉGIA 1: Se claramente offline, pular tentativa de envio
        if (!estaOnline()) {
            console.log('[Order] 📴 Offline detectado, salvando localmente...');
            
            const salvou = await salvarPedidoPendente(pedido);
            if (!salvou) {
                throw new Error('Erro ao salvar pedido localmente');
            }
            
            // Tentar registrar Background Sync (pode funcionar em /catalogo/)
            await registrarSyncPedido();
            
            return {
                sucesso: true,
                offline: true,
                mensagem: 'Você está offline. O pedido foi salvo localmente e será enviado automaticamente quando a conexão for restaurada.'
            };
        }
        
        // ESTRATÉGIA 2: Tentar envio direto (funciona em qualquer path)
        const resultadoDireto = await tentarEnvioDireto(pedido);
        
        if (resultadoDireto.sucesso) {
            // ✅ Enviado com sucesso!
            console.log('[Order] 🎉 Pedido finalizado com sucesso via envio direto');
            
            return {
                sucesso: true,
                mensagem: `Pedido realizado com sucesso!\n\nNúmero: ${resultadoDireto.dados.venda?.id || 'N/A'}\nValor Total: R$ ${resultadoDireto.dados.venda?.valor_total || '0.00'}`
            };
        }
        
        // ESTRATÉGIA 3: Envio falhou - salvar localmente
        console.warn('[Order] ⚠️ Envio direto falhou, salvando para sincronização...');
        console.warn('[Order] Motivo:', resultadoDireto.erro);
        
        const salvou = await salvarPedidoPendente(pedido);
        if (!salvou) {
            throw new Error('Erro ao salvar pedido localmente');
        }
        
        console.log('[Order] 💾 Pedido salvo localmente');
        
        // Tentar Background Sync (funciona em /catalogo/)
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
        
    } catch (error) {
        console.error('[Order] ❌ Erro ao finalizar pedido:', error);
        throw error;
    }
}