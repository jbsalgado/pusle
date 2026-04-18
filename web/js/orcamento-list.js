/**
 * orcamento-list.js
 * Gerenciamento de visualizações Grid/Card e impressão de comprovantes de orçamento
 */

console.log('[Orçamento List] Script carregado');

// ============== TOGGLE DE VISUALIZAÇÃO ==============

document.addEventListener('DOMContentLoaded', function() {
    const btnGrid = document.getElementById('btn-view-grid');
    const btnCard = document.getElementById('btn-view-card');
    const viewGrid = document.getElementById('view-grid');
    const viewCard = document.getElementById('view-card');
    
    if (btnGrid && btnCard && viewGrid && viewCard) {
        // Toggle para Grid
        btnGrid.addEventListener('click', function() {
            viewGrid.classList.remove('hidden');
            viewCard.classList.add('hidden');
            btnGrid.classList.add('active');
            btnCard.classList.remove('active');
            
            // Salva preferência no localStorage
            localStorage.setItem('orcamento_view_preference', 'grid');
        });
        
        // Toggle para Card
        btnCard.addEventListener('click', function() {
            viewCard.classList.remove('hidden');
            viewGrid.classList.add('hidden');
            btnCard.classList.add('active');
            btnGrid.classList.remove('active');
            
            // Salva preferência no localStorage
            localStorage.setItem('orcamento_view_preference', 'card');
        });
        
        // Restaura preferência salva
        const savedView = localStorage.getItem('orcamento_view_preference');
        if (savedView === 'card') {
            btnCard.click();
        }
    }

    // Inicializa Dashboard
    if (typeof initDashboard === 'function') {
        initDashboard();
    }
});

// ============== BUSCA DE DADOS ==============

/**
 * Busca dados completos do orçamento via API
 * @param {number} orcamentoId
 * @returns {Promise<Object>}
 */
async function buscarDadosOrcamento(orcamentoId) {
    try {
        const url = `${window.BASE_URL}/vendas/orcamento/detalhes?id=${orcamentoId}`;
        console.log('[Orçamento] Buscando dados:', url);
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`Erro ao buscar orçamento: ${response.status}`);
        }
        
        const dados = await response.json();
        console.log('[Orçamento] Dados recebidos:', dados);
        
        return dados;
    } catch (error) {
        console.error('[Orçamento] Erro ao buscar dados:', error);
        throw error;
    }
}

/**
 * Busca dados da empresa/loja
 * @param {string|null} forcedUsuarioId - ID da loja forçado (ex: do orçamento)
 * @returns {Promise<Object>}
 */
async function buscarDadosEmpresa(forcedUsuarioId = null) {
    try {
        const cacheKey = forcedUsuarioId ? `dados_empresa_${forcedUsuarioId}` : 'dados_empresa';
        
        // Tenta buscar do sessionStorage primeiro (cache)
        const cached = sessionStorage.getItem(cacheKey);
        if (cached) {
            const parsed = JSON.parse(cached);
            if (parsed.nome_loja !== 'Loja') {
                console.log('[Orçamento] Usando dados da empresa do cache');
                return parsed;
            }
        }
        
        // Determina qual ID usar
        let idParaBusca = forcedUsuarioId;
        
        if (!idParaBusca) {
            // Se não forçado, busca dados do usuário atual e seu vínculo de colaborador
            const meResponse = await fetch(`${window.BASE_URL}/api/usuario/me`);
            if (meResponse.ok) {
                const meData = await meResponse.json();
                // Prioriza o ID do dono da loja se for colaborador
                idParaBusca = meData.colaborador ? meData.colaborador.usuario_id : meData.usuario.id;
            }
        }
        
        const url = `${window.BASE_URL}/api/usuario/dados-loja${idParaBusca ? `?usuario_id=${idParaBusca}` : ''}`;
        console.log('[Orçamento] Buscando dados da loja:', url);
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Erro ao buscar dados da empresa');
        }
        
        const dados = await response.json();
        console.log('[Orçamento] Dados da empresa recebidos:', dados);
        
        
        // Salva no cache
        sessionStorage.setItem(cacheKey, JSON.stringify(dados));
        
        return dados;
    } catch (error) {
        console.warn('[Orçamento] Erro ao buscar dados da empresa:', error);
        return { nome: 'Loja', nome_loja: 'Loja', cpf_cnpj: '', telefone: '', endereco: '' };
    }
}

// ============== GERAÇÃO DE COMPROVANTE ==============

/**
 * Função global para imprimir orçamento (chamada pelo onclick dos botões)
 * @param {number} orcamentoId
 */
window.imprimirOrcamento = async function(orcamentoId) {
    try {
        console.log('[Orçamento] Iniciando impressão do orçamento:', orcamentoId);
        
        // Mostra loading
        const modal = document.getElementById('modal-comprovante');
        const container = document.getElementById('comprovante-container');
        
        if (!modal || !container) {
            alert('Erro: Modal não encontrado');
            return;
        }
        
        modal.classList.remove('hidden');
        container.innerHTML = '<div class="text-center py-12"><div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div><p class="mt-4 text-gray-600">Carregando comprovante...</p></div>';
        
        // Busca dados
        const dadosOrcamento = await buscarDadosOrcamento(orcamentoId);
        // Usa o usuario_id do PRÓPRIO ORÇAMENTO para buscar dados da empresa
        const dadosEmpresa = await buscarDadosEmpresa(dadosOrcamento.usuario_id);
        
        // Gera HTML do comprovante
        const html = _gerarHTMLComprovanteContent(dadosOrcamento, dadosEmpresa);
        
        // Exibe no modal
        container.innerHTML = html;
        
        // Armazena dados globalmente para as funções de impressão
        window.dadosComprovanteAtual = {
            carrinho: dadosOrcamento.itens,
            dadosPedido: dadosOrcamento,
            dadosEmpresa: dadosEmpresa,
            valorTotal: dadosOrcamento.valor_total,
            orcamentoId: orcamentoId,
            hash: dadosOrcamento.hash, // Adiciona o hash para acesso público
        };
        
        console.log('[Orçamento] Comprovante gerado com sucesso');
    } catch (error) {
        console.error('[Orçamento] Erro ao gerar comprovante:', error);
        alert('Erro ao gerar comprovante: ' + error.message);
        fecharModalComprovante();
    }
};

/**
 * Gera HTML do comprovante (função interna)
 * @param {Object} dados
 * @param {Object} dadosEmpresa
 * @returns {string}
 */
function _gerarHTMLComprovanteContent(dados, dadosEmpresa) {
    const now = new Date();
    const dataHora = formatarDataHora(now);
    
    // Formata CPF/CNPJ
    const cpfCnpjFormatado = formatarCpfCnpj(dadosEmpresa.cpf_cnpj || '');
    const isCNPJ = (dadosEmpresa.cpf_cnpj || '').replace(/\D/g, '').length === 14;
    
    // Formata telefone
    const telefoneFormatado = formatarTelefone(dadosEmpresa.telefone || '');
    
    // Monta endereço
    let endereco = dadosEmpresa.endereco || '';
    let cidade = '';
    if (dadosEmpresa.bairro || dadosEmpresa.cidade || dadosEmpresa.estado) {
        const partes = [];
        if (dadosEmpresa.bairro) partes.push(dadosEmpresa.bairro);
        if (dadosEmpresa.cidade) partes.push(dadosEmpresa.cidade);
        if (dadosEmpresa.estado) partes.push(dadosEmpresa.estado);
        cidade = partes.join(', ');
    }
    
    // Calcula subtotal
    let subtotal = 0;
    dados.itens.forEach(item => {
        subtotal += parseFloat(item.subtotal || 0);
    });
    
    const html = `
        <div class="max-w-2xl mx-auto bg-white">
            <!-- Cabeçalho -->
            <div class="text-center border-b-2 border-gray-300 pb-4 mb-4">
                <h2 class="text-xl font-bold text-gray-900">${dadosEmpresa.nome_loja || dadosEmpresa.nome || 'Loja'}</h2>
                ${cpfCnpjFormatado ? `<p class="text-sm text-gray-600">${isCNPJ ? 'CNPJ' : 'CPF'}: ${cpfCnpjFormatado}</p>` : ''}
                ${endereco ? `<p class="text-sm text-gray-600">${endereco}</p>` : ''}
                ${cidade ? `<p class="text-sm text-gray-600">${cidade}</p>` : ''}
                ${telefoneFormatado ? `<p class="text-sm text-gray-600">Fone: ${telefoneFormatado}</p>` : ''}
            </div>
            
            <!-- Título -->
            <div class="text-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 uppercase">Orçamento</h3>
                <p class="text-sm text-gray-600">${dataHora}</p>
                <p class="text-base font-semibold text-gray-900 mt-2">Nº: ${dados.id}</p>
            </div>
            
            <!-- Dados do Cliente -->
            ${dados.cliente ? `
            <div class="border-t border-b border-gray-300 py-3 mb-4">
                <p class="text-sm font-semibold text-gray-700 mb-2">CLIENTE:</p>
                <p class="text-base font-medium text-gray-900">${dados.cliente.nome}</p>
                ${dados.cliente.cpf ? `<p class="text-sm text-gray-600">CPF: ${formatarCpfCnpj(dados.cliente.cpf)}</p>` : ''}
                ${dados.cliente.telefone ? `<p class="text-sm text-gray-600">Fone: ${formatarTelefone(dados.cliente.telefone)}</p>` : ''}
                ${dados.cliente.endereco ? `<p class="text-sm text-gray-600">${dados.cliente.endereco}${dados.cliente.numero ? ', ' + dados.cliente.numero : ''}${dados.cliente.complemento ? ' - ' + dados.cliente.complemento : ''}</p>` : ''}
                ${dados.cliente.bairro || dados.cliente.cidade ? `<p class="text-sm text-gray-600">${dados.cliente.bairro ? dados.cliente.bairro : ''}${dados.cliente.cidade ? ' - ' + dados.cliente.cidade : ''}${dados.cliente.estado ? '/' + dados.cliente.estado : ''}</p>` : ''}
            </div>
            ` : ''}
            
            <!-- Itens -->
            <div class="mb-4">
                <table class="w-full">
                    <thead class="border-b-2 border-gray-300">
                        <tr class="border-b-2 border-gray-300">
                            <th class="text-left text-sm font-bold text-gray-700 pb-2">Item</th>
                            <th class="text-center text-sm font-bold text-gray-700 pb-2">Qt</th>
                            <th class="text-right text-sm font-bold text-gray-700 pb-2">Vlr</th>
                            <th class="text-right text-sm font-bold text-gray-700 pb-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${dados.itens.map(item => {
                            const brutoItem = item.quantidade * item.preco;
                            const descontoItem = item.desconto_valor || 0;
                            return `
                            <tr class="border-b border-gray-200">
                                <td class="py-2 text-sm text-gray-900">${item.nome}</td>
                                <td class="py-2 text-center text-sm text-gray-900">${item.quantidade}</td>
                                <td class="py-2 text-right text-sm text-gray-900">${formatarMoeda(item.preco)}</td>
                                <td class="py-2 text-right text-sm font-semibold text-gray-900">${formatarMoeda(brutoItem)}</td>
                            </tr>
                            ${descontoItem > 0.01 ? `
                            <tr class="text-[10px] text-gray-500 italic">
                                <td colspan="3" class="py-0 pl-4">
                                    (-) Desconto ${item.desconto_percentual > 0 ? (parseFloat(item.desconto_percentual)).toFixed(2) + '%' : ''}
                                </td>
                                <td class="text-right py-0">- ${formatarMoeda(descontoItem)}</td>
                            </tr>
                            ` : ''}
                        `;}).join('')}
                    </tbody>
                </table>
            </div>
            
            <!-- Totais -->
            <div class="border-t-2 border-gray-300 pt-3">
                ${(() => {
                    const brutoTotal = dados.itens.reduce((acc, i) => acc + (i.quantidade * i.preco), 0);
                    const descontosTotal = dados.itens.reduce((acc, i) => acc + (i.desconto_valor || 0), 0);
                    const acrescimo = dados.acrescimo_valor || 0;
                    
                    const totalPecas = dados.itens.reduce((acc, i) => acc + (parseFloat(i.quantidade) || 0), 0);
                    const totalizerHtml = `
                        <div class="flex justify-between pt-1 border-t border-gray-100 text-[11px] text-gray-500 font-bold">
                            <span>TOTAL DE ITENS:</span>
                            <span>${totalPecas}</span>
                        </div>
                    `;

                    if (descontosTotal > 0.01 || acrescimo > 0.01) {
                        return `
                            <div class="flex justify-between mb-1 text-sm text-gray-600">
                                <span>SUBTOTAL BRUTO:</span>
                                <span>${formatarMoeda(brutoTotal)}</span>
                            </div>
                            ${descontosTotal > 0.01 ? `
                            <div class="flex justify-between mb-1 text-sm text-red-600 font-medium">
                                <span>TOTAL DESCONTOS:</span>
                                <span>- ${formatarMoeda(descontosTotal)}</span>
                            </div>` : ''}
                            ${acrescimo > 0.01 ? `
                            <div class="flex justify-between mb-1 text-sm text-blue-600">
                                <span>ACRÉSCIMO${dados.acrescimo_tipo ? ' (' + dados.acrescimo_tipo + ')' : ''}:</span>
                                <span>+ ${formatarMoeda(acrescimo)}</span>
                            </div>` : ''}
                            ${totalizerHtml}
                        `;
                    }
                    return totalizerHtml;
                })()}
                <div class="flex justify-between mt-2 pt-2 border-t border-gray-100">
                    <span class="text-base font-bold text-gray-900">TOTAL LÍQUIDO:</span>
                    <span class="text-xl font-bold text-green-600">${formatarMoeda(dados.valor_total)}</span>
                </div>
            </div>
            
            <!-- Data Validade -->
            ${dados.data_validade ? `
            <div class="mb-4 p-2 rounded-lg ${dados.esta_vencido ? 'bg-red-50 border border-red-200' : 'bg-blue-50 border border-blue-200'}">
                <p class="text-[10px] uppercase font-bold ${dados.esta_vencido ? 'text-red-600' : 'text-blue-600'}">
                    ${dados.esta_vencido ? '⚠️ Orçamento Vencido' : '📅 Válido Até'}
                </p>
                <p class="text-xs font-bold text-gray-900">${new Date(dados.data_validade + 'T12:00:00').toLocaleDateString('pt-BR')}</p>
            </div>
            ` : ''}

            <!-- Forma de Pagamento -->
            <div class="border-t border-gray-300 mt-4 pt-3">
                <p class="text-sm text-gray-700">Forma de Pagamento: <span class="font-semibold">${dados.forma_pagamento}</span></p>
            </div>
            
            <!-- Observações -->
            ${dados.observacoes ? `
            <div class="border-t border-gray-300 mt-4 pt-3">
                <p class="text-sm font-semibold text-gray-700 mb-1">Observações:</p>
                <p class="text-sm text-gray-600">${dados.observacoes}</p>
            </div>
            ` : ''}

            <!-- PIX Copia e Cola -->
            ${dados.pix && dados.pix.chave ? `
            <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-xl no-print">
                <div class="flex items-center mb-2">
                    <svg class="w-5 h-5 text-teal-600 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                    <span class="text-sm font-bold text-gray-900">PAGAMENTO VIA PIX</span>
                </div>
                <p class="text-[10px] text-gray-500 mb-2">Copie o código abaixo para pagar via PIX</p>
                <div class="relative">
                    <textarea readonly id="pix-copia-cola" class="w-full text-[10px] p-2 bg-white border border-gray-300 rounded font-mono break-all focus:outline-none" rows="3">${gerarPixCopiaCola(dados.pix.chave, dados.pix.nome, dados.pix.cidade, dados.valor_total, 'ORC' + dados.id)}</textarea>
                    <button onclick="copiarPix()" class="absolute right-2 bottom-2 p-1.5 bg-teal-600 text-white rounded hover:bg-teal-700 transition-colors tooltip" title="Copiar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m3 4h6m-6 4h6m-6 4h6" /></svg>
                    </button>
                </div>
            </div>
            ` : ''}
            
            <!-- Rodapé -->
            <div class="text-center mt-6 pt-4 border-t border-gray-300">
                <p class="text-sm text-gray-600">Obrigado pela preferência!</p>
                <p class="text-xs text-gray-500 mt-2">Este é um orçamento e não tem valor fiscal</p>
            </div>
            <!-- Botões -->
            <div class="flex flex-wrap gap-2 mt-6 no-print">
                <button onclick="imprimirNormal()" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-bold hover:bg-blue-700 transition-colors">Cupom 80mm</button>
                <button onclick="imprimirOrcamentoA4('${dados.id}', '${dados.hash}')" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-bold hover:bg-indigo-700 transition-colors">Relatório A4</button>
                
                <button onclick="baixarComprovanteImagem()" class="bg-orange-500 text-white p-2 rounded-lg hover:bg-orange-600 transition-colors" title="Baixar como Imagem">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </button>

                <button onclick="compartilharOrcamentoWhatsApp('${dados.id}', '${dados.cliente ? (dados.cliente.telefone || '') : ''}', '${dados.hash}')" class="bg-green-600 text-white p-2 rounded-lg hover:bg-green-700 transition-colors" title="Compartilhar WhatsApp">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.038 3.284l-.542 2.317 2.357-.546c1.021.575 1.914.911 3.127.912h.001c3.181 0 5.767-2.586 5.768-5.766 0-3.18-2.586-5.766-5.767-5.767zm3.391 8.221c-.142.405-.815.739-1.121.78-.306.04-.685.074-1.747-.356-1.207-.492-2.198-1.554-2.718-2.223-.06-.078-.105-.139-.136-.188-.28-.399-.497-.707-.497-1.112 0-.441.228-.68.32-.78l.069-.074c.056-.063.155-.121.274-.121.117 0 .225.011.302.012.077.001.144.02.212.182.107.258.351.854.382.918.031.064.051.139.011.22-.04.082-.06.139-.121.21-.061.071-.129.158-.184.214-.059.06-.121.124-.052.245.069.121.306.505.657.817.452.401.832.525.952.585.12.06.192.051.264-.03s.306-.356.387-.478c.081-.121.162-.102.274-.061.112.041.711.335.832.396.121.06.202.09.232.141.03.05.03.295-.112.699zM12 1c-6.075 0-11 4.925-11 11s4.925 11 11 11 11-4.925 11-11-4.925-11-11-11zm0 2c4.97 0 9 4.03 9 9s-4.03 9-9 9-9-4.03-9-9 4.03-9 9-9z"/></svg>
                </button>
                <button onclick="fecharModalComprovante()" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-bold hover:bg-gray-300 transition-colors">Fechar</button>
            </div>
        </div>
    `;
    
    return html;
}

// ============== FUNÇÕES DE IMPRESSÃO ==============

/**
 * Fecha o modal de comprovante
 */
window.fecharModalComprovante = function() {
    const modal = document.getElementById('modal-comprovante');
    if (modal) {
        modal.classList.add('hidden');
    }
};

/**
 * Impressão normal (window.print)
 */
window.imprimirNormal = function() {
    if (!window.dadosComprovanteAtual) return;
    const { orcamentoId, hash } = window.dadosComprovanteAtual;
    const url = hash ? `/vendas/orcamento/imprimir?hash=${hash}` : `/vendas/orcamento/imprimir?id=${orcamentoId}`;
    window.open(url, '_blank');
};

/**
 * Impressão térmica (deep link para app Flutter)
 */
window.imprimirTermica = async function() {
    try {
        if (!window.dadosComprovanteAtual || !window.dadosComprovanteAtual.orcamentoId) {
            alert('Erro: Dados do comprovante não encontrados ou incompletos');
            return;
        }
        
        const texto = await _gerarTextoComprovanteContent(window.dadosComprovanteAtual.orcamentoId);
        
        if (!texto) {
            alert('Erro ao gerar texto para impressão');
            return;
        }
        
        // Encode URL
        const encodedText = encodeURIComponent(texto);
        
        // Deep Link para o App Flutter
        const deepLink = `printapp://print?data=${encodedText}`;
        
        console.log('[Orçamento] Abrindo Deep Link para impressão térmica');
        
        // Tenta abrir o app
        window.location.href = deepLink;
        
    } catch (error) {
        console.error('[Orçamento] Erro ao processar impressão térmica:', error);
        alert('Erro ao processar impressão: ' + error.message);
    }
};

/**
 * Gera texto formatado para impressora térmica (32 colunas) (função interna)
 * @param {number} orcamentoId
 * @returns {string}
 */
async function _gerarTextoComprovanteContent(orcamentoId) {
    const dadosOrcamento = await buscarDadosOrcamento(orcamentoId);
    const dadosEmpresa = await buscarDadosEmpresa(dadosOrcamento.usuario_id);

    const { itens: carrinho, valor_total: valorTotal, forma_pagamento: formaPagamento } = dadosOrcamento;
    
    const largura = 32; // Colunas (padrão 58mm)
    const linhaSeparadora = '-'.repeat(largura);
    
    let texto = '';
    
    // Função center
    const center = (str) => {
        if (!str) return '';
        const spaces = Math.max(0, Math.floor((largura - str.length) / 2));
        return ' '.repeat(spaces) + str;
    };
    
    // Função para duas colunas (Esquerda direita)
    const row = (left, right) => {
        const lLen = left.length;
        const rLen = right.length;
        const spaces = Math.max(1, largura - lLen - rLen);
        return left + ' '.repeat(spaces) + right;
    };
    
    // Cabeçalho
    texto += center(removerAcentos(dadosEmpresa.nome_loja || 'LOJA').toUpperCase()) + '\n';
    if (dadosEmpresa.cpf_cnpj) texto += center(formatarCpfCnpj(dadosEmpresa.cpf_cnpj)) + '\n';
    
    // Endereço
    if (dadosEmpresa.endereco) {
        texto += center(removerAcentos(dadosEmpresa.endereco).toUpperCase()) + '\n';
        const bairroCidade = (dadosEmpresa.bairro ? dadosEmpresa.bairro + ', ' : '') + 
                             (dadosEmpresa.cidade ? dadosEmpresa.cidade : '');
        if (bairroCidade) {
            texto += center(removerAcentos(bairroCidade).toUpperCase()) + '\n';
        }
    }
    
    // Telefone
    if (dadosEmpresa.telefone) {
        texto += center(formatarTelefone(dadosEmpresa.telefone)) + '\n';
    }
    
    texto += linhaSeparadora + '\n';
    texto += center('ORCAMENTO') + '\n';
    texto += center(`Nº: ${dadosOrcamento.id}`) + '\n';
    const now = new Date();
    texto += center(formatarDataHora(now)) + '\n';
    texto += linhaSeparadora + '\n';
    
    // Cliente
    if (dadosOrcamento.cliente) {
        texto += 'CLIENTE:\n';
        texto += removerAcentos(dadosOrcamento.cliente.nome).substring(0, largura).toUpperCase() + '\n';
        if (dadosOrcamento.cliente.cpf) {
            texto += 'CPF: ' + formatarCpfCnpj(dadosOrcamento.cliente.cpf) + '\n';
        }
        texto += linhaSeparadora + '\n';
    }
    
    // Itens
    let brutoGeral = 0;
    let descontosGeral = 0;
    carrinho.forEach(item => {
        const nome = removerAcentos(item.nome || 'Produto').substring(0, largura).toUpperCase();
        texto += nome + '\n';
        
        const qtd = parseFloat(item.quantidade || 0);
        const preco = parseFloat(item.preco || 0);
        const brutoItem = qtd * preco;
        const desconto = parseFloat(item.desconto_valor || 0);
        
        brutoGeral += brutoItem;
        descontosGeral += desconto;
        
        texto += row(`${qtd.toFixed(1)}x ${preco.toFixed(2)}`, `R$ ${brutoItem.toFixed(2)}`) + '\n';
        
        if (desconto > 0.01) {
            const pct = item.desconto_percentual > 0 ? parseFloat(item.desconto_percentual).toFixed(0) + '%' : '';
            const labelDesc = `(-) DESC ${pct}`;
            texto += row('  ' + labelDesc.substring(0, 15), `-${desconto.toFixed(2)}`) + '\n';
        }

        if (item.observacoes) {
            const obs = removerAcentos(item.observacoes).toUpperCase();
            texto += ' Obs: ' + obs + '\n';
        }
    });
    
    texto += linhaSeparadora + '\n';
    
    const acrescimo = parseFloat(dadosOrcamento.acrescimo_valor || 0);
    
    if (descontosGeral > 0.01 || acrescimo > 0.01) {
        texto += row('SUBTOTAL BRUTO', brutoGeral.toFixed(2)) + '\n';
        if (descontosGeral > 0.01) {
            texto += row('TOTAL DESCONTOS', `-${descontosGeral.toFixed(2)}`) + '\n';
        }
        if (acrescimo > 0.01) {
            const labelAcr = `ACRESCIMO${dadosOrcamento.acrescimo_tipo ? '(' + dadosOrcamento.acrescimo_tipo + ')' : ''}`;
            texto += row(labelAcr.substring(0, 16), `+${acrescimo.toFixed(2)}`) + '\n';
        }
        texto += linhaSeparadora + '\n';
    }
    
    texto += row('TOTAL LIQUIDO', `R$ ${parseFloat(valorTotal).toFixed(2)}`) + '\n';
    texto += row('PAGAMENTO', removerAcentos(dadosOrcamento.forma_pagamento || 'A COMBINAR').toUpperCase()) + '\n';

    if (dadosOrcamento.observacoes) {
        texto += linhaSeparadora + '\n';
        texto += 'OBSERVACOES:\n';
        texto += removerAcentos(dadosOrcamento.observacoes).toUpperCase() + '\n';
    }
    
    // Rodapé
    texto += '\n\n' + center('OBRIGADO PELA PREFERENCIA!') + '\n';
    texto += center('ORCAMENTO SEM VALOR FISCAL') + '\n\n\n';
    
    return sanitizarParaImpressora(texto);
}

// ============== FUNÇÕES AUXILIARES ==============

/**
 * Formata data e hora
 * @param {Date} data
 * @returns {string}
 */
function formatarDataHora(data) {
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    const hora = String(data.getHours()).padStart(2, '0');
    const minuto = String(data.getMinutes()).padStart(2, '0');
    
    return `${dia}/${mes}/${ano} ${hora}:${minuto}`;
}

/**
 * Formata valor monetário
 * @param {number} valor
 * @returns {string}
 */
function formatarMoeda(valor) {
    return `R$ ${parseFloat(valor).toFixed(2).replace('.', ',')}`;
}

/**
 * Formata CPF ou CNPJ
 * @param {string} cpfCnpj
 * @returns {string}
 */
function formatarCpfCnpj(cpfCnpj) {
    if (!cpfCnpj) return '';
    const limpo = cpfCnpj.replace(/\D/g, '');
    
    if (limpo.length === 11) {
        // CPF: 000.000.000-00
        return limpo.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
    } else if (limpo.length === 14) {
        // CNPJ: 00.000.000/0000-00
        return limpo.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
    }
    
    return cpfCnpj;
}

/**
 * Formata telefone
 * @param {string} telefone
 * @returns {string}
 */
function formatarTelefone(telefone) {
    if (!telefone) return '';
    const limpo = telefone.replace(/\D/g, '');
    
    if (limpo.length === 11) {
        // (00) 00000-0000
        return limpo.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
    } else if (limpo.length === 10) {
        // (00) 0000-0000
        return limpo.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
    }
    
    return telefone;
}

/**
 * Remove acentos
 * @param {string} str
 * @returns {string}
 */
function removerAcentos(str) {
    if (!str) return '';
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

/**
 * Sanitiza texto para impressora térmica
 * @param {string} str
 * @returns {string}
 */
function sanitizarParaImpressora(str) {
    if (!str) return '';
    return str
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Remove acentos
        .replace(/[^\x20-\x7E\n]/g, '') // Remove tudo que não for ASCII imprimível ou quebra de linha
        .replace(/\r\n/g, '\n') // Normaliza quebras de linha
        .replace(/\r/g, '\n'); // Normaliza quebras de linha
}

window.compartilharOrcamentoWhatsApp = function(id, telefone, hash) {
    let tel = telefone;
    if (!tel || tel === 'undefined' || tel === 'null') {
        tel = prompt("Informe o número do WhatsApp (ex: 82900000000):");
        if (!tel) return;
    }
    
    // Adiciona o domínio para o link externo
    const base = window.location.origin;
    const param = hash ? `hash=${hash}` : `id=${id}`;
    const url = `${base}/vendas/orcamento/imprimir?${param}`;
    const texto = encodeURIComponent(`Olá! Segue o seu orçamento:\n${url}`);
    
    const telLimpo = tel.replace(/\D/g, '');
    const whatsAppUrl = `https://wa.me/${telLimpo.startsWith('55') ? telLimpo : '55' + telLimpo}?text=${texto}`;
    
    window.open(whatsAppUrl, '_blank');
};

/**
 * Captura o conteúdo do comprovante e baixa como imagem PNG
 */
window.baixarComprovanteImagem = function() {
    const container = document.getElementById('comprovante-container');
    if (!container) return;

    // Feedback visual
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
    btn.disabled = true;

    // Oculta elementos que não devem sair na imagem
    const noPrint = container.querySelectorAll('.no-print');
    noPrint.forEach(el => el.style.display = 'none');

    // Usa html2canvas para gerar a imagem
    html2canvas(container, {
        scale: 2, // Aumenta a resolução
        useCORS: true,
        backgroundColor: '#ffffff',
        logging: false
    }).then(canvas => {
        // Restaura elementos ocultos
        noPrint.forEach(el => el.style.display = '');
        btn.innerHTML = originalHtml;
        btn.disabled = false;

        // Cria o link de download
        const id = window.dadosComprovanteAtual ? window.dadosComprovanteAtual.orcamentoId : 'Orcamento';
        const link = document.createElement('a');
        link.download = `Comprovante_${id}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }).catch(err => {
        console.error('Erro ao gerar imagem:', err);
        alert('Erro ao gerar imagem do comprovante.');
        noPrint.forEach(el => el.style.display = '');
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
};

function copiarPix() {
    const copyText = document.getElementById("pix-copia-cola");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Código PIX copiado com sucesso!");
}
async function initDashboard() {
    try {
        const response = await fetch('/vendas/orcamento/resumo');
        const data = await response.json();
        
        const valorHoje = document.getElementById('dash-hoje-valor');
        const qtdHoje = document.getElementById('dash-hoje-qtd');
        const valorPendente = document.getElementById('dash-pendente-valor');
        const qtdVencendo = document.getElementById('dash-vencendo-qtd');
        
        if (valorHoje) valorHoje.innerText = data.hoje_valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        if (qtdHoje) qtdHoje.innerText = `${data.hoje_qtd} orçamentos`;
        if (valorPendente) valorPendente.innerText = data.pendente_valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        if (qtdVencendo) qtdVencendo.innerText = data.vencendo_amanha_qtd;
    } catch (e) {
        console.error('Erro ao carregar dashboard:', e);
    }
}

function gerarPixCopiaCola(chave, nome, cidade, valor, identificador) {
    // Implementação simplificada do EMV PIX (Baseado no pix.js da venda-direta)
    const removerAcentos = (s) => s.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toUpperCase();
    const cleanChave = chave.replace(/[^a-zA-Z0-9@.+.-]/g, '');
    const merchantName = removerAcentos(nome).substring(0, 25);
    const merchantCity = removerAcentos(cidade).substring(0, 15);
    const txid = (identificador || '***').replace(/[^a-zA-Z0-9]/g, '').substring(0, 25);
    
    const parts = [
        '000201',
        '26' + String(22 + cleanChave.length).padStart(2, '0') + '0014br.gov.bcb.pix01' + String(cleanChave.length).padStart(2, '0') + cleanChave,
        '52040000',
        '5303986',
        '54' + String(valor.toFixed(2).length).padStart(2, '0') + valor.toFixed(2),
        '5802BR',
        '59' + String(merchantName.length).padStart(2, '0') + merchantName,
        '60' + String(merchantCity.length).padStart(2, '0') + merchantCity,
        '62' + String(txid.length + 4).padStart(2, '0') + '05' + String(txid.length).padStart(2, '0') + txid,
        '6304'
    ];
    
    const payload = parts.join('');
    
    // Cálculo CRC16
    let crc = 0xFFFF;
    for (let i = 0; i < payload.length; i++) {
        crc ^= (payload.charCodeAt(i) << 8);
        for (let j = 0; j < 8; j++) {
            crc = (crc & 0x8000) ? ((crc << 1) ^ 0x1021) : (crc << 1);
        }
    }
    const finalCrc = (crc & 0xFFFF).toString(16).toUpperCase().padStart(4, '0');
    return payload + finalCrc;
}
window.imprimirOrcamentoA4 = function(id, hash) {
    const param = hash ? `hash=${hash}` : `id=${id}`;
    window.open(`/vendas/orcamento/imprimir-a4?${param}`, '_blank');
}

console.log('[Orçamento List] Funções carregadas com sucesso');
