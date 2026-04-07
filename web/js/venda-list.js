/**
 * venda-list.js
 * Gerenciamento de visualizações Grid/Card e impressão de comprovantes de venda
 */

console.log('[Venda List] Script carregado');

// ============== TOGGLE DE VISUALIZAÇÃO ==============

document.addEventListener('DOMContentLoaded', function() {
    const btnGrid = document.getElementById('btn-view-grid');
    const btnCard = document.getElementById('btn-view-card');
    const viewGrid = document.getElementById('view-grid');
    const viewCard = document.getElementById('view-card');
    
    if (btnGrid && btnCard && viewGrid && viewCard) {
        // Toggle para Grid (Tabela)
        btnGrid.addEventListener('click', function() {
            viewGrid.classList.remove('hidden');
            viewCard.classList.add('hidden');
            btnGrid.classList.add('active');
            btnCard.classList.remove('active');
            
            // Salva preferência no localStorage
            localStorage.setItem('venda_view_preference', 'grid');
        });
        
        // Toggle para Card
        btnCard.addEventListener('click', function() {
            viewCard.classList.remove('hidden');
            viewGrid.classList.add('hidden');
            btnCard.classList.add('active');
            btnGrid.classList.remove('active');
            
            // Salva preferência no localStorage
            localStorage.setItem('venda_view_preference', 'card');
        });
        
        // Restaura preferência salva
        const savedView = localStorage.getItem('venda_view_preference');
        if (savedView === 'card') {
            btnCard.click();
        }
    }
});

// ============== BUSCA DE DADOS ==============

/**
 * Busca dados completos da venda via API
 * @param {string} vendaId
 * @returns {Promise<Object>}
 */
async function buscarDadosVenda(vendaId) {
    try {
        const baseUrl = window.BASE_URL || window.location.origin;
        const url = `${baseUrl}/vendas/venda/detalhes?id=${vendaId}`;
        console.log('[Venda] Buscando dados:', url);
        
        const response = await fetch(url);
        if (!response.ok) throw new Error(`Erro: ${response.status}`);
        
        return await response.json();
    } catch (error) {
        console.error('[Venda] Erro ao buscar dados:', error);
        throw error;
    }
}

/**
 * Busca dados da empresa (logica unificada)
 * @param {string|null} forcedUsuarioId 
 * @returns {Promise<Object>}
 */
async function buscarDadosEmpresa(forcedUsuarioId = null) {
    try {
        const cacheKey = forcedUsuarioId ? `dados_empresa_${forcedUsuarioId}` : 'dados_empresa';
        const cached = sessionStorage.getItem(cacheKey);
        
        if (cached) {
            const parsed = JSON.parse(cached);
            if (parsed.nome_loja !== 'Loja') return parsed;
        }
        
        let idParaBusca = forcedUsuarioId;
        if (!idParaBusca) {
            const baseUrl = window.BASE_URL || window.location.origin;
            const meResponse = await fetch(`${baseUrl}/api/usuario/me`);
            if (meResponse.ok) {
                const meData = await meResponse.json();
                idParaBusca = meData.colaborador ? meData.colaborador.usuario_id : meData.usuario.id;
            }
        }
        
        const baseUrl = window.BASE_URL || window.location.origin;
        const url = `${baseUrl}/api/usuario/dados-loja${idParaBusca ? `?usuario_id=${idParaBusca}` : ''}`;
        const response = await fetch(url);
        if (!response.ok) throw new Error('Erro ao buscar dados da empresa');
        
        const dados = await response.json();
        sessionStorage.setItem(cacheKey, JSON.stringify(dados));
        return dados;
    } catch (error) {
        console.warn('[Venda] Erro ao buscar dados da empresa:', error);
        return { nome: 'Loja', nome_loja: 'Loja', cpf_cnpj: '', telefone: '', endereco: '' };
    }
}

// ============== ALTERAÇÃO DE STATUS ==============

/**
 * Exibe um alerta temporário (Toast) na tela
 */
window.showToast = function(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-[100] animate-slide-in-right`;
    
    const bg = type === 'success' ? 'bg-green-500' : (type === 'error' ? 'bg-red-500' : 'bg-blue-500');
    
    toast.innerHTML = `
        <div class="${bg} text-white px-6 py-3 rounded-xl shadow-2xl flex items-center space-x-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-bold text-sm">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s, transform 0.5s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

/**
 * Altera o status da venda via AJAX
 * @param {string} vendaId 
 * @param {string} novoStatus 
 */
window.alterarStatusVenda = async function(vendaId, novoStatus) {
    if (!novoStatus) return;
    
    if (!confirm(`Deseja realmente alterar o status desta venda para ${novoStatus}?`)) {
        location.reload(); 
        return;
    }
    
    // Feedback visual imediato
    const select = document.querySelector(`select[data-venda-id="${vendaId}"]`);
    if (select) {
        select.disabled = true;
        select.classList.add('opacity-50', 'cursor-wait');
    }
    
    showToast('Processando alteração...', 'info');
    
    try {
        const baseUrl = window.BASE_URL || window.location.origin;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        const response = await fetch(`${baseUrl}/vendas/venda/alterar-status?id=${vendaId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken
            },
            body: `status=${encodeURIComponent(novoStatus)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Sucesso! Atualizando...', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('Erro: ' + result.message, 'error');
            setTimeout(() => location.reload(), 2000);
        }
    } catch (error) {
        console.error('[Venda] Erro ao alterar status:', error);
        showToast('Erro na conexão', 'error');
        setTimeout(() => location.reload(), 2000);
    }
}

// ============== GERAÇÃO DE COMPROVANTE ==============

/**
 * Abrir modal de impressão
 * @param {string} vendaId 
 */
window.imprimirVenda = async function(vendaId) {
    try {
        const modal = document.getElementById('modal-comprovante');
        const container = document.getElementById('comprovante-container');
        
        if (!modal || !container) return;
        
        modal.classList.remove('hidden');
        container.innerHTML = '<div class="text-center py-12"><div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div><p class="mt-4 text-gray-600 font-medium">Gerando recibo...</p></div>';
        
        const dadosVenda = await buscarDadosVenda(vendaId);
        const dadosEmpresa = await buscarDadosEmpresa(dadosVenda.usuario_id);
        
        // Renderiza o HTML
        container.innerHTML = _renderHTMLRecibo(dadosVenda, dadosEmpresa);
        
        // Armazena globalmente
        window.dadosVendaAtual = {
            id: vendaId,
            dadosVenda: dadosVenda,
            dadosEmpresa: dadosEmpresa
        };
        
    } catch (error) {
        alert('Erro ao gerar comprovante: ' + error.message);
        fecharModalComprovante();
    }
}

function _renderHTMLRecibo(venda, empresa) {
    const dataHora = new Date(venda.data_criacao).toLocaleString('pt-BR');
    
    return `
        <div class="recibo-body font-sans text-gray-900 px-2">
            <!-- Cabeçalho -->
            <div class="text-center border-b border-gray-200 pb-4 mb-4">
                <h2 class="text-xl font-bold uppercase">${empresa.nome_loja || empresa.nome}</h2>
                <p class="text-sm">${empresa.endereco || ''}</p>
                <p class="text-sm">${empresa.cidade || ''} - ${empresa.estado || ''}</p>
                <p class="text-sm">CNPJ: ${empresa.cpf_cnpj || ''}</p>
                <p class="text-sm">FONE: ${empresa.telefone || ''}</p>
            </div>
            
            <!-- Título -->
            <div class="text-center mb-4">
                <h3 class="text-lg font-bold">RECIBO DE VENDA</h3>
                <p class="text-sm">Nº: ${venda.id.substring(0, 8).toUpperCase()}</p>
                ${venda.orcamento_id ? `
                    <p class="text-[10px] italic text-blue-600">ORIGEM: ORÇAMENTO #${venda.orcamento_id}</p>
                    ${venda.orcamento_valor_original && Math.abs(venda.orcamento_valor_original - venda.valor_total) > 0.01 ? `
                        <div class="mt-1 px-2 py-1 bg-yellow-50 border border-yellow-200 rounded text-[10px] text-yellow-700 font-bold">
                            ⚠️ DIVERGÊNCIA: Valor alterado após conversão (Original: R$ ${venda.orcamento_valor_original.toLocaleString('pt-BR', {minimumFractionDigits: 2})})
                        </div>
                    ` : ''}
                ` : ''}
                <p class="text-xs text-gray-500 mt-1">${dataHora}</p>
            </div>
            
            <!-- Cliente -->
            <div class="mb-4 border-b border-gray-100 pb-2">
                <p class="text-sm"><strong>CLIENTE:</strong> ${venda.cliente ? venda.cliente.nome : 'VENDA DIRETA'}</p>
                ${venda.cliente && venda.cliente.cpf ? `<p class="text-sm"><strong>CPF:</strong> ${venda.cliente.cpf}</p>` : ''}
            </div>
            
            <!-- Itens -->
            <div class="mb-4">
                <table class="w-full text-sm">
                    <thead class="border-b-2 border-gray-300">
                        <tr>
                            <th class="text-left py-2 font-bold">Item</th>
                            <th class="text-center py-2 font-bold">Qt</th>
                            <th class="text-right py-2 font-bold">Vlr</th>
                            <th class="text-right py-2 font-bold">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${venda.itens.map(i => {
                            const subtotalBruto = i.quantidade * i.preco;
                            const descontoItem = i.desconto_valor || 0;
                            return `
                            <tr>
                                <td class="py-1">${i.nome}</td>
                                <td class="text-center py-1">${i.quantidade}</td>
                                <td class="text-right py-1">${i.preco.toFixed(2)}</td>
                                <td class="text-right py-1 font-bold">${subtotalBruto.toFixed(2)}</td>
                            </tr>
                            ${descontoItem > 0 ? `
                            <tr class="text-[10px] text-gray-500 italic">
                                <td colspan="3" class="py-0 pl-4">
                                    (-) Desconto ${i.desconto_percentual > 0 ? i.desconto_percentual.toFixed(2) + '%' : ''}
                                </td>
                                <td class="text-right py-0">- ${descontoItem.toFixed(2)}</td>
                            </tr>
                            ` : ''}
                        `;}).join('')}
                    </tbody>
                </table>
            </div>
            
            ${(() => {
                const somaBruta = venda.itens.reduce((acc, i) => acc + (i.quantidade * i.preco), 0);
                const totalDescontosItems = venda.itens.reduce((acc, i) => acc + (i.desconto_valor || 0), 0);
                const acrescimo = venda.acrescimo_valor || 0;
                
                const totalPecas = venda.itens.reduce((acc, i) => acc + (parseFloat(i.quantidade) || 0), 0);
                
                let html = '<div class="border-t border-gray-300 pt-2 space-y-1 pb-2">';
                
                // Sempre mostra subtotal se houver descontos ou acréscimos para clareza
                if (totalDescontosItems > 0 || acrescimo > 0) {
                    html += `
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>SUBTOTAL BRUTO:</span>
                            <span>R$ ${somaBruta.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                        </div>
                    `;
                }

                if (totalDescontosItems > 0) {
                    html += `
                        <div class="flex justify-between text-sm text-red-600 font-medium">
                            <span>TOTAL DESCONTOS:</span>
                            <span>-R$ ${totalDescontosItems.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                        </div>
                    `;
                }

                if (acrescimo > 0) {
                    html += `
                        <div class="flex justify-between text-sm text-blue-600 font-medium">
                            <span>ACRÉSCIMO${venda.acrescimo_tipo ? ' (' + venda.acrescimo_tipo + ')' : ''}:</span>
                            <span>+R$ ${acrescimo.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                        </div>
                    `;
                }

                html += `
                    <div class="flex justify-between pt-1 border-t border-gray-50 text-[11px] text-gray-500 font-bold">
                        <span>TOTAL DE ITENS:</span>
                        <span>${totalPecas}</span>
                    </div>
                `;

                html += '</div>';
                return html;
            })()}
            
            <!-- Totais -->
            <div class="border-t border-gray-300 pt-2 space-y-1">
                <div class="flex justify-between mt-2 pt-2 border-t border-gray-100">
                    <span class="text-base font-bold text-gray-900 uppercase">Total Líquido:</span>
                    <span class="text-xl font-bold text-green-600">R$ ${venda.valor_total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                </div>
            </div>

            <!-- Detalhamento de Pagamentos -->
            ${venda.pagamentos && venda.pagamentos.length > 0 ? `
            <div class="mt-4 border-t border-gray-200 pt-2 no-print">
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Detalhamento de Pagamentos:</p>
                ${venda.pagamentos.map(p => `
                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                        <span>${p.nome}:</span>
                        <span class="font-bold">${p.valor.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'})}</span>
                    </div>
                `).join('')}
            </div>
            ` : ''}

            <!-- Rodapé -->
            <div class="text-center mt-6 pt-4 border-t border-gray-300">
                <p class="text-sm text-gray-600">Obrigado pela preferência!</p>
            </div>
            
            <!-- Botões de Ação -->
            <div class="flex flex-wrap gap-2 mt-6 no-print">
                <button onclick="imprimirNormal()" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-bold hover:bg-blue-700 transition-colors">Cupom 80mm</button>
                <button onclick="imprimirCupomPresente('${venda.id}')" class="flex-1 bg-pink-600 text-white py-2 rounded-lg font-bold hover:bg-pink-700 transition-colors">Vale-Presente</button>
                <button onclick="imprimirVendaA4('${venda.id}')" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-bold hover:bg-indigo-700 transition-colors">Pedido A4</button>
                ${venda.cliente && venda.cliente.telefone ? `
                    <button onclick="compartilharWhatsApp('${venda.id}', '${venda.cliente.telefone}')" class="bg-green-600 text-white p-2 rounded-lg hover:bg-green-700 transition-colors" title="Compartilhar WhatsApp">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.038 3.284l-.542 2.317 2.357-.546c1.021.575 1.914.911 3.127.912h.001c3.181 0 5.767-2.586 5.768-5.766 0-3.18-2.586-5.766-5.767-5.767zm3.391 8.221c-.142.405-.815.739-1.121.78-.306.04-.685.074-1.747-.356-1.207-.492-2.198-1.554-2.718-2.223-.06-.078-.105-.139-.136-.188-.28-.399-.497-.707-.497-1.112 0-.441.228-.68.32-.78l.069-.074c.056-.063.155-.121.274-.121.117 0 .225.011.302.012.077.001.144.02.212.182.107.258.351.854.382.918.031.064.051.139.011.22-.04.082-.06.139-.121.21-.061.071-.129.158-.184.214-.059.06-.121.124-.052.245.069.121.306.505.657.817.452.401.832.525.952.585.12.06.192.051.264-.03s.306-.356.387-.478c.081-.121.162-.102.274-.061.112.041.711.335.832.396.121.06.202.09.232.141.03.05.03.295-.112.699zM12 1c-6.075 0-11 4.925-11 11s4.925 11 11 11 11-4.925 11-11-4.925-11-11-11zm0 2c4.97 0 9 4.03 9 9s-4.03 9-9 9-9-4.03-9-9 4.03-9 9-9z"/></svg>
                    </button>
                ` : ''}
                <button onclick="fecharModalComprovante()" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-bold hover:bg-gray-300 transition-colors">Fechar</button>
            </div>
        </div>
    `;
}

// ============== AÇÕES DO MODAL ==============

window.fecharModalComprovante = function() {
    document.getElementById('modal-comprovante').classList.add('hidden');
}

window.imprimirNormal = function() {
    if (!window.dadosVendaAtual) return;
    const vendaId = window.dadosVendaAtual.id;
    const baseUrl = window.BASE_URL || window.location.origin;
    const url = `${baseUrl}/vendas/venda/imprimir?id=${vendaId}`;
    window.open(url, '_blank');
}

window.imprimirTermica = async function() {
    try {
        if (!window.dadosVendaAtual) return;
        
        const { dadosVenda: v, dadosEmpresa: e } = window.dadosVendaAtual;
        const col = 32;
        const div = '-'.repeat(col);
        
        const center = s => {
            const spaces = Math.max(0, Math.floor((col - s.length) / 2));
            return ' '.repeat(spaces) + s;
        };
        
        const row = (l, r) => {
            const spaces = Math.max(1, col - l.length - r.length);
            return l + ' '.repeat(spaces) + r;
        };

        const rA = s => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();

        let t = '';
        t += center(rA(e.nome_loja || e.nome)) + '\n';
        if (e.cpf_cnpj) t += center(e.cpf_cnpj) + '\n';
        if (e.endereco) t += center(rA(e.endereco)) + '\n';
        t += div + '\n';
        t += center('RECIBO DE VENDA') + '\n';
        t += center('ID: ' + v.id.substring(0, 8).toUpperCase()) + '\n';
        t += center(new Date(v.data_criacao).toLocaleString('pt-BR')) + '\n';
        t += div + '\n';
        t += 'CLIENTE: ' + rA(v.cliente ? v.cliente.nome : 'VENDA DIRETA') + '\n';
        t += div + '\n';
        
        v.itens.forEach(i => {
            t += rA(i.nome).substring(0, col) + '\n';
            t += row(`${i.quantidade}x ${i.preco.toFixed(2)}`, (i.quantidade * i.preco).toFixed(2)) + '\n';
        });
        
        t += div + '\n';
        
        const somaBruta = v.itens.reduce((acc, i) => acc + (i.quantidade * i.preco), 0);
        const desc = somaBruta - v.valor_total;
        if (desc > 0.01) {
            t += row('SUBTOTAL', 'R$ ' + somaBruta.toFixed(2)) + '\n';
            t += row('DESCONTO', '-R$ ' + desc.toFixed(2)) + '\n';
        }
        
        t += row('TOTAL RECEBIDO', 'R$ ' + v.valor_total.toFixed(2)) + '\n';
        t += row('PAGAMENTO', rA(v.forma_pagamento)) + '\n';
        t += div + '\n';
        t += center('OBRIGADO PELA PREFERENCIA') + '\n\n\n\n';

        // Deep Link
        const deepLink = `printapp://print?data=${encodeURIComponent(t)}`;
        window.location.href = deepLink;
        
    } catch (error) {
        alert('Erro na impressão térmica: ' + error.message);
    }
}

// ============== EVENT LISTENERS (DELEGATION) ==============

document.addEventListener('DOMContentLoaded', function() {
    console.log('[Venda List] Inicializando listeners de eventos...');

    // Inicializa tooltips
    // Inicializa tooltips de forma segura
    if (typeof $.fn.tooltip === 'function') {
        $('.has-tooltip').tooltip();
    }
    
    // Inicializa Dashboard
    initDashboard();

    // Listener para Select de Status
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('js-venda-status-select')) {
            const vendaId = e.target.getAttribute('data-venda-id');
            const novoStatus = e.target.value;
            console.log('[Venda List] Alteração de status detectada:', vendaId, novoStatus);
            if (typeof window.alterarStatusVenda === 'function') {
                window.alterarStatusVenda(vendaId, novoStatus);
            }
        }
    });

    // Listener para Botão de Imprimir (Ações e Cards)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.js-venda-imprimir-btn');
        if (btn) {
            const vendaId = btn.getAttribute('data-venda-id');
            console.log('[Venda List] Solicitação de impressão:', vendaId);
            if (typeof window.imprimirVenda === 'function') {
                window.imprimirVenda(vendaId);
            }
        }
    });
});

async function initDashboard() {
    try {
        const baseUrl = window.BASE_URL || window.location.origin;
        const response = await fetch(`${baseUrl}/vendas/venda/resumo`);
        if (!response.ok) return;
        
        const data = await response.json();
        
        const valorHoje = document.getElementById('dash-hoje-valor');
        const qtdHoje = document.getElementById('dash-hoje-qtd');
        
        if (valorHoje && data.hoje_valor !== undefined) {
            valorHoje.innerText = data.hoje_valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }
        if (qtdHoje && data.hoje_qtd !== undefined) {
            qtdHoje.innerText = data.hoje_qtd;
        }
    } catch (e) {
        console.error('Erro ao carregar dashboard:', e);
    }
}

function compartilharWhatsApp(vendaId, telefone) {
    const url = `${window.location.origin}/vendas/venda/imprimir?id=${vendaId}`;
    const texto = encodeURIComponent(`Olá! Segue o recibo da sua compra:\n${url}`);
    
    const telLimpo = telefone.replace(/\D/g, '');
    const whatsAppUrl = `https://wa.me/${telLimpo.startsWith('55') ? telLimpo : '55' + telLimpo}?text=${texto}`;
    
    window.open(whatsAppUrl, '_blank');
}
function imprimirVendaA4(vendaId) {
    window.open(`/vendas/venda/imprimir-a4?id=${vendaId}`, '_blank');
}
function imprimirCupomPresente(vendaId) {
    window.open(`/vendas/venda/imprimir?id=${vendaId}&gift=1`, '_blank');
}
