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
    // Verificar se é Point (Maquineta física)
    const formasPagamento = window.formasPagamento || [];
    const formaSelecionada = formasPagamento.find(f => f.id === dadosPedido.forma_pagamento_id);
    const tipo = formaSelecionada ? formaSelecionada.tipo : '';

    if (tipo === 'MP_POINT') {
        return await processarMercadoPagoPoint(dadosPedido, carrinho, cliente);
    }

    try {
        const pedidoGateway = {
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
                numero: cliente.numero || '',
                cidade: cliente.endereco_cidade || cliente.cidade || '',
                estado: cliente.endereco_estado || cliente.estado || ''
            },
            // Informações específicas de venda direta
            colaborador_vendedor_id: dadosPedido.colaborador_vendedor_id || null,
            observacoes: dadosPedido.observacoes || null,
            numero_parcelas: dadosPedido.numero_parcelas || 1,
            data_primeiro_pagamento: dadosPedido.data_primeiro_pagamento || null,
            intervalo_dias_parcelas: dadosPedido.intervalo_dias_parcelas || 30
        };
        
        console.log('[Gateway] Criando preferência no backend...', JSON.stringify(pedidoGateway, null, 2));
        
        const response = await fetchWithAuth(API_ENDPOINTS.MERCADOPAGO_CRIAR_PREFERENCIA, {
            method: 'POST',
            body: JSON.stringify(pedidoGateway)
        });
        
        if (!response.ok) {
            const erro = await response.json();
            throw new Error(erro.erro || 'Erro ao criar preferência');
        }
        
        const resultado = await response.json();
        
        // Salvar referências para acompanhamento
        localStorage.setItem('mp_preference_id', resultado.preference_id);
        localStorage.setItem('mp_external_ref', resultado.external_reference);
        localStorage.setItem('mp_venda_direta', 'true');
        
        // Redirecionar para checkout do Mercado Pago
        // Usar sandbox_init_point se estiver em sandbox
        const checkoutUrl = resultado.sandbox_init_point || resultado.init_point;
        window.location.href = checkoutUrl;
        
        return {
            sucesso: true,
            gateway: 'mercadopago',
            redirecionado: true,
            preference_id: resultado.preference_id,
            external_reference: resultado.external_reference
        };
        
    } catch (error) {
        console.error('[MP] ❌ Erro:', error);
        throw error;
    }
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
                        <button class="w-full text-left p-4 border rounded-lg hover:bg-blue-50 hover:border-blue-500 transition-colors flex justify-between items-center" data-id="${d.id}">
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
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-credit-card text-3xl text-blue-600 animate-pulse"></i>
            </div>
            <h3 class="text-2xl font-bold mb-2">Aguardando na Maquineta</h3>
            <p class="text-gray-600 mb-6">Por favor, finalize o pagamento na <strong>${nomeMaquineta}</strong>.</p>
            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                <div class="bg-blue-600 h-2 rounded-full animate-progress" style="width: 100%"></div>
            </div>
            <p class="text-sm text-gray-500">O Pulse atualizará a venda automaticamente assim que for aprovado.</p>
            <button onclick="window.location.reload()" class="mt-8 text-blue-600 font-medium">Voltar para Início</button>
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
                    
                    // Limpar polling
                    if (pollingIntervalId) {
                        clearInterval(pollingIntervalId);
                        pollingIntervalId = null;
                    }

                    // Fechar modal de espera
                    const modal = document.getElementById('modal-status-point');
                    if (modal) modal.remove();

                    // Disparar evento de finalização (abre o comprovante)
                    window.dispatchEvent(new CustomEvent('pagamentoConfirmado', {
                        detail: {
                            pedidoId: pedidoId,
                            gateway: 'mercadopago_point',
                            dados: data,
                            originalDadosPedido: originalDadosPedido // Passa os dados para o comprovante
                        }
                    }));
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
