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
            orcamentoId: orcamentoId, // Adiciona o ID para uso posterior
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
                        <tr>
                            <th class="text-left text-sm font-semibold text-gray-700 pb-2">Produto</th>
                            <th class="text-center text-sm font-semibold text-gray-700 pb-2">Qtd</th>
                            <th class="text-right text-sm font-semibold text-gray-700 pb-2">Preço</th>
                            <th class="text-right text-sm font-semibold text-gray-700 pb-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${dados.itens.map(item => `
                            <tr class="border-b border-gray-200">
                                <td class="py-2 text-sm text-gray-900">${item.nome}</td>
                                <td class="py-2 text-center text-sm text-gray-900">${item.quantidade}</td>
                                <td class="py-2 text-right text-sm text-gray-900">${formatarMoeda(item.preco)}</td>
                                <td class="py-2 text-right text-sm font-semibold text-gray-900">${formatarMoeda(item.subtotal)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            
            <!-- Totais -->
            <div class="border-t-2 border-gray-300 pt-3">
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-700">Subtotal:</span>
                    <span class="text-sm font-semibold text-gray-900">${formatarMoeda(subtotal)}</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-base font-bold text-gray-900">TOTAL:</span>
                    <span class="text-xl font-bold text-green-600">${formatarMoeda(dados.valor_total)}</span>
                </div>
            </div>
            
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
            
            <!-- Rodapé -->
            <div class="text-center mt-6 pt-4 border-t border-gray-300">
                <p class="text-sm text-gray-600">Obrigado pela preferência!</p>
                <p class="text-xs text-gray-500 mt-2">Este é um orçamento e não tem valor fiscal</p>
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
    window.print();
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
    carrinho.forEach(item => {
        const nome = removerAcentos(item.nome || 'Produto').substring(0, largura).toUpperCase();
        texto += nome + '\n';
        
        const qtd = parseFloat(item.quantidade || 0);
        const preco = parseFloat(item.preco || 0);
        const total = parseFloat(item.subtotal || 0);
        
        texto += row(`${qtd}x ${preco.toFixed(2)}`, `R$ ${total.toFixed(2)}`) + '\n';
    });
    
    texto += linhaSeparadora + '\n';
    texto += row('TOTAL', `R$ ${parseFloat(valorTotal).toFixed(2)}`) + '\n';
    texto += row('PAGAMENTO', removerAcentos(dadosOrcamento.forma_pagamento || 'A COMBINAR').toUpperCase()) + '\n';
    
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

console.log('[Orçamento List] Funções carregadas com sucesso');
