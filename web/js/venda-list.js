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
                <p class="text-xs text-gray-500">${dataHora}</p>
            </div>
            
            <!-- Cliente -->
            <div class="mb-4 border-b border-gray-100 pb-2">
                <p class="text-sm"><strong>CLIENTE:</strong> ${venda.cliente ? venda.cliente.nome : 'VENDA DIRETA'}</p>
                ${venda.cliente && venda.cliente.cpf ? `<p class="text-sm"><strong>CPF:</strong> ${venda.cliente.cpf}</p>` : ''}
            </div>
            
            <!-- Itens -->
            <div class="mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="text-left py-1 font-bold">Item</th>
                            <th class="text-center py-1 font-bold">Qt</th>
                            <th class="text-right py-1 font-bold">Vlr</th>
                            <th class="text-right py-1 font-bold">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${venda.itens.map(i => `
                            <tr>
                                <td class="py-1">${i.nome}</td>
                                <td class="text-center py-1">${i.quantidade}</td>
                                <td class="text-right py-1">${i.preco.toFixed(2)}</td>
                                <td class="text-right py-1 font-bold">${i.subtotal.toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            
            <!-- Totais -->
            <div class="border-t border-gray-300 pt-2 space-y-1">
                <div class="flex justify-between text-base font-bold">
                    <span>TOTAL:</span>
                    <span>R$ ${venda.valor_total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>PAGAMENTO:</span>
                    <span class="font-medium uppercase">${venda.forma_pagamento}</span>
                </div>
            </div>
            
            <!-- Rodapé -->
            <div class="text-center mt-6 pt-4 border-t border-dashed border-gray-300">
                <p class="text-sm italic">Obrigado pela preferência!</p>
                <p class="text-[10px] text-gray-400 mt-2 uppercase">Recibo para fins de conferência</p>
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
            t += row(`${i.quantidade}x ${i.preco.toFixed(2)}`, i.subtotal.toFixed(2)) + '\n';
        });
        
        t += div + '\n';
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
