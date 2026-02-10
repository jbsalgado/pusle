// order.js - Gerenciamento de pedidos VENDA DIRETA (sem cliente obrigatório)
// VERSÃO MODIFICADA PARA VENDA DIRETA

import { CONFIG, API_ENDPOINTS, GATEWAY_CONFIG } from './config.js';
import { adicionarPedidoAFila, obterFilaPedidos, removerPedidoDaFila, getToken } from './storage.js';
import { validarUUID } from './utils.js';
import { getAcrescimo } from './cart.js?v=surcharge_fix';

/**
 * Valida dados do pedido antes de enviar (VENDA DIRETA - cliente opcional)
 */
function validarDadosPedido(dadosPedido, carrinho) {
    console.log('[Order] 📋 Validando dados do pedido (Orçamento Direta)...', dadosPedido);
    
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
                throw new Error('Por favor, informe a data do primeiro pagamento para orçamentos parceladas.');
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
    console.log('[Order] 🔧 Preparando objeto do pedido (Orçamento Direta)...');
    
    // Obtém dados do acréscimo do carrinho
    const acrescimo = getAcrescimo();
    
    const pedido = {
        usuario_id: CONFIG.ID_USUARIO_LOJA,
        cliente_id: dadosPedido.cliente_id || null, // VENDA DIRETA: pode ser null
        observacoes: dadosPedido.observacoes || null,
        numero_parcelas: parseInt(dadosPedido.numero_parcelas, 10) || 1,
        forma_pagamento_id: dadosPedido.forma_pagamento_id,
        is_orcamento: true, // ✅ MARCADOR: Identifica que é um orçamento
        is_orçamento_direta: true, // Mantém para compatibilidade de rotas base
        itens: carrinho.map(item => ({
            produto_id: item.produto_id || item.id,
            quantidade: item.quantidade,
            // ✅ CORREÇÃO: Usar preço promocional se disponível (preco_final), senão usar preco_orçamento_sugerido
            preco_unitario: item.preco_final || item.preco_orçamento_sugerido,
            desconto_percentual: item.descontoPercentual || 0,
            desconto_valor: item.descontoValor || 0
        })),
        acrescimo_valor: parseFloat(acrescimo.valor) || 0,
        acrescimo_tipo: acrescimo.tipo || null,
        observacao_acrescimo: acrescimo.observacao || null,
        id_local: crypto.randomUUID() // ✅ GERA ID LOCAL ÚNICO PARA USO OFFLINE/IMPRESSÃO
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

    console.log('[Order] 📦 Pedido preparado (Orçamento Direta):', pedido);
    return pedido;
}

/**
 * Tenta enviar o pedido diretamente via fetch (fluxo interno)
 */
async function tentarEnvioDireto(pedido) {
    try {
        const token = await getToken();
        console.log('[Order] 🔑 Token obtido para envio:', token ? 'Sim (mascarado: ' + token.substring(0, 10) + '...)' : 'Não');
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
            console.log('[Order] 🛡️ Header Authorization adicionado');
        } else {
            console.warn('[Order] ⚠️ Token ausente! A requisição pode falhar com 401.');
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
                console.log('[Order] ✅ Orçamento enviada com sucesso!');
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
        console.log('[Order] 🌐 Conexão restaurada! Verificando fila de orçamentos pendentes...');
        
        try {
            const fila = await obterFilaPedidos();
            
            if (fila.length > 0) {
                console.log(`[Order] 📦 Encontrados ${fila.length} orçamentos pendentes. Iniciando sincronização...`);
                
                let sucessos = 0;
                let falhas = 0;

                for (const pedido of fila) {
                    const resultado = await tentarEnvioDireto(pedido);
                    if (resultado.sucesso) {
                        sucessos++;
                        await removerPedidoDaFila(pedido.id_local);
                    } else {
                        falhas++;
                        console.error(`[Order] ❌ Falha ao sincronizar pedido ${pedido.id_local}:`, resultado.erro);
                    }
                }
                
                if (sucessos > 0) {
                    const msg = falhas > 0 
                        ? `${sucessos} orçamentos sincronizados, ${falhas} falharam.`
                        : `Todos os ${sucessos} orçamentos pendentes foram sincronizados!`;
                    
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('Sincronização Concluída', { body: msg, icon: '/favicon.ico' });
                    } else {
                        alert(msg);
                    }
                    
                    if (falhas === 0) {
                        setTimeout(() => window.location.reload(), 2000);
                    }
                }
            } else {
                console.log('[Order] ℹ️ Fila de orçamentos vazia');
            }
        } catch (error) {
            console.error('[Order] ❌ Erro ao processar fila de sincronização:', error);
        }
    });
    
    console.log('[Order] 👂 Listener de reconexão configurado');
}

// Configurar listener automaticamente
configurarSincronizacaoManual();

/**
 * FUNÇÃO PRINCIPAL: Finalizar Pedido (VENDA DIRETA)
 * VENDA DIRETA: Não usa gateway externo, sempre fluxo interno
 */
export async function finalizarPedido(dadosPedido, carrinho) {
    try {
        // 1️⃣ VALIDAR DADOS
        validarDadosPedido(dadosPedido, carrinho);
        
        console.log('[Order] 🚀 Iniciando finalização da orçamento direta...');
        console.log('[Order] 🏪 Loja (usuario_id):', CONFIG.ID_USUARIO_LOJA);
        console.log('[Order] 👤 Vendedor:', dadosPedido.colaborador_vendedor_id || 'Não informado (sem comissão)');
        
        // VENDA DIRETA: Sempre usa fluxo interno (sem gateway)
        console.log('[Order] 🟢 Usando fluxo interno (Orçamento Direta)');
        
        const pedido = prepararObjetoPedido(dadosPedido, carrinho);
        
        // Verificar se está online
        const estaOnline = navigator.onLine;
        console.log('[Order] 📶 Status da conexão:', estaOnline ? 'ONLINE' : 'OFFLINE');
        
        if (!estaOnline) {
            // OFFLINE: Salvar localmente
            console.log('[Order] 🔴 Offline detectado, salvando localmente...');
            
            const salvou = await adicionarPedidoAFila(pedido);
            if (!salvou) {
                throw new Error('Erro ao salvar orçamento localmente');
            }
            
            await registrarSyncPedido();
            
            return {
                sucesso: true,
                offline: true,
                mensagem: 'Você está offline. A orçamento foi salva localmente e será enviada automaticamente quando a conexão for restaurada.'
            };
        }
        
        // ONLINE: Tentar envio direto
        const resultadoDireto = await tentarEnvioDireto(pedido);
        
        if (resultadoDireto.sucesso) {
            // ✅ Enviado com sucesso!
            console.log('[Order] 🎉 Orçamento finalizado com sucesso via envio direto');
            
            const vendaData = resultadoDireto.dados?.data || resultadoDireto.dados;
            return {
                sucesso: true,
                dados: resultadoDireto.dados,
                mensagem: `Orçamento gerado com sucesso!\n\nNúmero: ${vendaData?.id || vendaData?.orçamento?.id || 'N/A'}\nValor Total: R$ ${vendaData?.valor_total || vendaData?.orçamento?.valor_total || '0.00'}`
            };
        }
        
        // Envio falhou - salvar localmente
        console.warn('[Order] ⚠️ Envio direto falhou, salvando para sincronização...');
        console.warn('[Order] Motivo:', resultadoDireto.erro);
        
        const salvou = await adicionarPedidoAFila(pedido);
        if (!salvou) {
            throw new Error('Erro ao salvar orçamento localmente');
        }
        
        console.log('[Order] 💾 Orçamento salva localmente');
        
        const syncRegistrado = await registrarSyncPedido();
        
        if (syncRegistrado) {
            return {
                sucesso: true,
                offline: true,
                mensagem: 'Conexão instável. Orçamento salva localmente e será enviada automaticamente assim que a conexão melhorar.'
            };
        } else {
            return {
                sucesso: true,
                offline: true,
                mensagem: 'Orçamento salva localmente. Será enviada automaticamente quando a conexão for restaurada. Mantenha esta aba aberta.'
            };
        }
        
    } catch (error) {
        console.error('[Order] ❌ Erro ao finalizar orçamento:', error);
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

