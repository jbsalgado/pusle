// pix.js - Geração de QR Code PIX Estático (CORRIGIDO)
// Baseado na especificação EMV QR Code do Banco Central

// Cache de configuração PIX (carregado da API)
let PIX_CONFIG_CACHE = null;

/**
 * Carrega configuração PIX da API
 * @param {string} usuarioId - ID do usuário
 * @returns {Promise<{chave: string, nome: string, cidade: string}>}
 */
export async function carregarConfigPix(usuarioId) {
    // Se já tem cache, retorna
    if (PIX_CONFIG_CACHE) {
        return PIX_CONFIG_CACHE;
    }

    try {
        // Tenta usar window.CONFIG primeiro, depois importa se necessário
        let urlApi = window.CONFIG?.URL_API;
        if (!urlApi) {
            try {
                const { CONFIG: configModule } = await import('./config.js');
                urlApi = configModule.URL_API;
                // Disponibiliza no window para próximas chamadas
                if (!window.CONFIG) {
                    window.CONFIG = configModule;
                }
            } catch (e) {
                console.warn('[PIX] Erro ao importar CONFIG, usando fallback:', e);
                // Fallback: constrói URL baseada na origem atual
                const pathname = window.location.pathname;
                const basePath = pathname.replace(/\/venda-direta.*$/, '');
                urlApi = window.location.origin + basePath + '/index.php';
            }
        }
        
        const url = `${urlApi}/api/usuario/dados-loja?usuario_id=${usuarioId}`;
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`Erro ao carregar dados PIX: ${response.status}`);
        }

        const dados = await response.json();
        
        if (dados.erro) {
            throw new Error(dados.erro);
        }

        // Valida se tem dados PIX
        if (!dados.pix_chave || !dados.pix_nome || !dados.pix_cidade) {
            throw new Error('Configuração PIX não encontrada. Configure os dados PIX nas configurações da loja.');
        }

        // Armazena no cache
        PIX_CONFIG_CACHE = {
            chave: dados.pix_chave,
            nome: dados.pix_nome,
            cidade: dados.pix_cidade
        };

        return PIX_CONFIG_CACHE;
    } catch (error) {
        console.error('[PIX] Erro ao carregar configuração PIX:', error);
        throw error;
    }
}

/**
 * Limpa o cache de configuração PIX (útil após atualizações)
 */
export function limparCachePix() {
    PIX_CONFIG_CACHE = null;
}

/**
 * Gera o código PIX estático (EMV QR Code)
 * @param {string} chave - Chave PIX
 * @param {string} nome - Nome do recebedor
 * @param {string} cidade - Cidade do recebedor
 * @param {number} valor - Valor da transação (opcional)
 * @param {string} descricao - Identificador da transação (TxID)
 * @returns {string} Código PIX EMV
 */
export function gerarCodigoPixEstatico(chave, nome, cidade, valor = null, descricao = null) {
    // CORREÇÃO CRÍTICA: Chave PIX de telefone DEVE estar no formato E.164 internacional
    // Formato obrigatório: +55XXXXXXXXXXX (com + e código do país 55)
    
    // Detecta se é chave de telefone (apenas números, 10-11 dígitos sem código do país)
    const chaveSemFormatacao = chave.replace(/[^0-9+]/g, '');
    let chaveLimpa;
    
    // Se a chave tem apenas números (sem +) e tem 10-11 dígitos, é telefone brasileiro
    if (/^[0-9]{10,11}$/.test(chaveSemFormatacao)) {
        // Adiciona +55 para formato E.164 internacional (OBRIGATÓRIO)
        chaveLimpa = '+55' + chaveSemFormatacao;
        console.log('[PIX] Chave de telefone detectada, formatando para E.164:', chaveSemFormatacao, '→', chaveLimpa);
    } else if (chaveSemFormatacao.startsWith('+55')) {
        // Já está no formato correto
        chaveLimpa = chaveSemFormatacao;
    } else if (chaveSemFormatacao.startsWith('55') && chaveSemFormatacao.length > 11) {
        // Tem código do país mas sem o +
        chaveLimpa = '+' + chaveSemFormatacao;
    } else {
        // Para outras chaves (CPF, CNPJ, email, chave aleatória), mantém como está
        chaveLimpa = chave.replace(/[^0-9a-zA-Z@.+.-]/g, '');
    }
    
    // Início do Payload (Array para montagem)
    const payload = [];
    
    // 1. Point of Initiation Method (Obrigatório, ID 00)
    // 01 = único uso, 12 = múltiplo uso (estático)
    // Para PIX estático, usamos 01 (único uso)
    payload.push('00' + '02' + '01');
    
    // NOTA: Campo 01 (Payload Format Indicator) foi REMOVIDO conforme recomendação
    // da Gemini para PIX estático - garante máxima compatibilidade com bancos

    // 2. Merchant Account Information (Obrigatório, ID 26)
    const gui = '0014br.gov.bcb.pix';
    const chavePix = '01' + String(chaveLimpa.length).padStart(2, '0') + chaveLimpa;
    const merchantAccount = gui + chavePix;
    payload.push('26' + String(merchantAccount.length).padStart(2, '0') + merchantAccount);
    
    // 3. Merchant Category Code (Obrigatório, ID 52)
    // 0000 = Geral/Não definido
    payload.push('52040000');
    
    // 4. Transaction Currency (Obrigatório, ID 53)
    // 986 = BRL (Real Brasileiro)
    payload.push('5303986');
    
    // 5. Transaction Amount (Opcional, ID 54)
    if (valor !== null && parseFloat(valor) > 0) {
        const valorStr = parseFloat(valor).toFixed(2);
        payload.push('54' + String(valorStr.length).padStart(2, '0') + valorStr);
    }
    
    // 6. Country Code (Obrigatório, ID 58)
    payload.push('5802BR');
    
    // 7. Merchant Name (Obrigatório, ID 59)
    // Remove acentos e limita tamanho (Max 25)
    const nomeTratado = removerAcentos(nome).substring(0, 25).toUpperCase().trim();
    payload.push('59' + String(nomeTratado.length).padStart(2, '0') + nomeTratado);
    
    // 8. Merchant City (Obrigatório, ID 60)
    // Remove acentos e limita tamanho (Max 15)
    const cidadeTratada = removerAcentos(cidade).substring(0, 15).toUpperCase().trim();
    payload.push('60' + String(cidadeTratada.length).padStart(2, '0') + cidadeTratada);
    
    // 9. Additional Data Field Template (Opcional, ID 62) - TxID
    // ID 05 dentro do 62 é o Reference Label (TxID)
    let txIdValue = '***'; // Valor padrão exigido se não houver TxID
    
    if (descricao) {
        // Limpa TxID: Apenas letras e números, sem espaços, máx 25 chars
        // O Banco Central exige: [a-zA-Z0-9]
        txIdValue = descricao.replace(/[^a-zA-Z0-9]/g, '').substring(0, 25);
    }
    
    // Se ficou vazio após limpar, usa ***
    if (txIdValue.length === 0) txIdValue = '***';
    
    const txIdField = '05' + String(txIdValue.length).padStart(2, '0') + txIdValue;
    payload.push('62' + String(txIdField.length).padStart(2, '0') + txIdField);
    
    // 10. CRC16 (Obrigatório, ID 63)
    const payloadSemCRC = payload.join('');
    const dadosParaCRC = payloadSemCRC + '6304'; // Adiciona ID e tamanho do CRC
    const crc = calcularCRC16(dadosParaCRC);
    
    const codigoFinal = dadosParaCRC + crc;
    
    console.log('[PIX] ✅ Código PIX gerado:', {
        chaveOriginal: chave,
        chaveLimpa: chaveLimpa,
        nome: nomeTratado,
        cidade: cidadeTratada,
        valor: valor,
        txId: txIdValue,
        inicioCodigo: codigoFinal.substring(0, 6),
        crc: crc,
        tamanhoTotal: codigoFinal.length
    });
    
    return codigoFinal;
}

/**
 * Remove acentos para garantir compatibilidade bancária
 */
function removerAcentos(str) {
    return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}

/**
 * Calcula CRC16-CCITT (0x1021) conforme especificação ISO/IEC 13239
 */
function calcularCRC16(data) {
    let crc = 0xFFFF;
    const polynomial = 0x1021;
    
    for (let i = 0; i < data.length; i++) {
        crc ^= (data.charCodeAt(i) << 8);
        for (let bit = 0; bit < 8; bit++) {
            if ((crc & 0x8000)) {
                crc = ((crc << 1) ^ polynomial) & 0xFFFF;
            } else {
                crc = (crc << 1) & 0xFFFF;
            }
        }
    }
    return crc.toString(16).toUpperCase().padStart(4, '0');
}

// --- Funções Visuais (Modal) ---

/**
 * Gera QR Code visualmente no container
 * Usa múltiplas APIs como fallback para garantir funcionamento
 */
export async function gerarQRCodeVisual(codigoPix, container) {
    try {
        container.innerHTML = '<div class="text-center p-4 text-gray-600">Gerando QR Code...</div>';
        
        // Lista de APIs QR Code como fallback (ordem de preferência)
        const qrCodeApis = [
            `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(codigoPix)}`,
            `https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=${encodeURIComponent(codigoPix)}`,
            `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(codigoPix)}`
        ];
        
        let currentApiIndex = 0;
        
        const img = document.createElement('img');
        img.alt = 'QR Code PIX';
        img.className = 'mx-auto border rounded-lg shadow-sm';
        img.style.maxWidth = '250px';
        img.style.width = '100%';
        img.style.height = 'auto';
        img.style.display = 'block';
        
        // Função para tentar próxima API em caso de erro
        const tryNextApi = () => {
            currentApiIndex++;
            if (currentApiIndex < qrCodeApis.length) {
                console.log(`[PIX] Tentando API ${currentApiIndex + 1}/${qrCodeApis.length}...`);
                img.src = qrCodeApis[currentApiIndex];
            } else {
                console.error('[PIX] Todas as APIs falharam');
                container.innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                        <p class="text-yellow-800 text-sm font-semibold mb-2">⚠️ QR Code não disponível</p>
                        <p class="text-yellow-700 text-xs">Use o código copia e cola abaixo para pagar</p>
                    </div>
                `;
            }
        };
        
        img.onload = () => { 
            console.log('[PIX] ✅ QR Code carregado com sucesso');
            container.innerHTML = ''; 
            container.appendChild(img); 
        };
        
        img.onerror = () => {
            console.warn(`[PIX] ⚠️ API ${currentApiIndex + 1} falhou, tentando próxima...`);
            tryNextApi();
        };
        
        // Inicia com a primeira API
        img.src = qrCodeApis[0];
        
    } catch (error) {
        console.error('[PIX] Erro ao gerar QR Code:', error);
        container.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                <p class="text-red-800 text-sm font-semibold mb-2">❌ Erro ao gerar QR Code</p>
                <p class="text-red-700 text-xs">Use o código copia e cola abaixo para pagar</p>
            </div>
        `;
    }
}

// Armazena dados do pedido para gerar comprovante
let dadosPedidoPix = null;

/**
 * Abre o Modal com os dados preenchidos
 * @param {number} valor - Valor da transação
 * @param {string} txId - ID da transação
 * @param {object} dadosPedido - Dados do pedido (opcional)
 * @param {string} usuarioId - ID do usuário (opcional, tenta buscar de window.CONFIG)
 */
export async function mostrarModalPixEstatico(valor, txId, dadosPedido = null, usuarioId = null) {
    console.log('[PIX] Abrindo modal para:', valor, txId);
    
    // Armazena dados do pedido para gerar comprovante depois
    dadosPedidoPix = dadosPedido || null;
    
    const modal = document.getElementById('modal-pix-estatico');
    if (!modal) {
        alert('Erro: Modal PIX não encontrado no HTML.');
        return;
    }

    // Mostra modal com loading
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Mostra indicador de carregamento
    const elContainerQR = document.getElementById('pix-qrcode-container');
    const elCodigo = document.getElementById('pix-codigo-copia-cola');
    if (elContainerQR) {
        elContainerQR.innerHTML = '<div class="text-center p-4"><p class="text-gray-600">Carregando configuração PIX...</p></div>';
    }
    if (elCodigo) {
        elCodigo.textContent = 'Carregando...';
    }

    try {
        // Busca usuarioId se não foi fornecido
        if (!usuarioId) {
            usuarioId = window.CONFIG?.ID_USUARIO_LOJA || null;
        }

        if (!usuarioId) {
            // Tenta buscar do CONFIG importado
            try {
                const { CONFIG } = await import('./config.js');
                usuarioId = CONFIG.ID_USUARIO_LOJA;
            } catch (e) {
                console.error('[PIX] Erro ao importar CONFIG:', e);
            }
        }
        
        if (!usuarioId) {
            throw new Error('ID do usuário não encontrado. Não é possível gerar QR Code PIX.');
        }

        // Carrega configuração PIX da API
        const pixConfig = await carregarConfigPix(usuarioId);

        // 1. Gera o código PIX string
        const codigoPix = gerarCodigoPixEstatico(
            pixConfig.chave,
            pixConfig.nome,
            pixConfig.cidade,
            valor,
            txId
        );

        // 2. Preenche os dados na tela
        const elValor = document.getElementById('pix-valor');
        
        if (elValor) elValor.textContent = `R$ ${parseFloat(valor).toFixed(2).replace('.', ',')}`;
        if (elCodigo) elCodigo.textContent = codigoPix;
        if (elContainerQR) gerarQRCodeVisual(codigoPix, elContainerQR);

    } catch (error) {
        console.error('[PIX] Erro ao carregar configuração PIX:', error);
        
        // Mostra mensagem de erro
        if (elContainerQR) {
            elContainerQR.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                    <p class="text-red-800 text-sm font-semibold mb-2">❌ Erro ao carregar PIX</p>
                    <p class="text-red-700 text-xs">${error.message}</p>
                    <p class="text-red-600 text-xs mt-2">Configure os dados PIX nas configurações da loja.</p>
                </div>
            `;
        }
        if (elCodigo) {
            elCodigo.textContent = 'Erro: Configure os dados PIX nas configurações da loja.';
        }
    }
}

// --- Funções Globais (Window) para os botões do HTML ---

window.copiarCodigoPix = function() {
    const elCodigo = document.getElementById('pix-codigo-copia-cola');
    if (!elCodigo) {
        console.error('[PIX] Elemento pix-codigo-copia-cola não encontrado');
        return;
    }
    
    const codigo = elCodigo.textContent.trim();
    
    if (!codigo) {
        alert('Código PIX não encontrado. Por favor, recarregue a página.');
        return;
    }
    
    // Tenta usar Clipboard API moderna (similar ao catalogo)
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(codigo)
            .then(() => {
                console.log('[PIX] ✅ Código copiado com sucesso');
                mostrarNotificacao('Código PIX copiado!');
            })
            .catch(err => {
                console.error('[PIX] Erro ao copiar:', err);
                copiarCodigoPixFallback(codigo);
            });
    } else {
        // Fallback para navegadores antigos
        copiarCodigoPixFallback(codigo);
    }
};

/**
 * Fallback para copiar código PIX (navegadores antigos)
 */
function copiarCodigoPixFallback(codigo) {
    const textarea = document.createElement('textarea');
    textarea.value = codigo;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.select();
    textarea.setSelectionRange(0, 99999); // Para mobile
    
    try {
        const sucesso = document.execCommand('copy');
        document.body.removeChild(textarea);
        
        if (sucesso) {
            mostrarNotificacao('Código PIX copiado!');
        } else {
            alert('Não foi possível copiar automaticamente. Por favor, selecione e copie manualmente.');
        }
    } catch (err) {
        document.body.removeChild(textarea);
        console.error('[PIX] Erro ao copiar (fallback):', err);
        alert('Por favor, copie o código manualmente.');
    }
}

/**
 * Mostra notificação temporária (similar ao catalogo)
 */
function mostrarNotificacao(mensagem) {
    // Remove notificação anterior se existir
    const notifAnterior = document.getElementById('pix-notificacao');
    if (notifAnterior) {
        notifAnterior.remove();
    }
    
    const notif = document.createElement('div');
    notif.id = 'pix-notificacao';
    notif.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    notif.style.transition = 'opacity 0.3s ease-in-out';
    notif.textContent = mensagem;
    
    document.body.appendChild(notif);
    
    // Animação de entrada
    setTimeout(() => {
        notif.style.opacity = '1';
    }, 10);
    
    // Remove após 2 segundos
    setTimeout(() => {
        notif.style.opacity = '0';
        setTimeout(() => {
            if (notif.parentNode) {
                notif.remove();
            }
        }, 300);
    }, 2000);
}

window.fecharModalPixEstatico = function() {
    const modal = document.getElementById('modal-pix-estatico');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = ''; // Restaura rolagem
    }
};

/**
 * Confirma recebimento do PIX e gera comprovante
 */
window.confirmarRecebimentoPix = async function() {
    if (!dadosPedidoPix) {
        alert('Erro: Dados do pedido não encontrados. Por favor, recarregue a página.');
        return;
    }
    
    // Importa funções necessárias dinamicamente
    const { getCarrinho, calcularTotalCarrinho } = await import('./cart.js');
    
    // Busca dados do carrinho (se ainda estiverem disponíveis)
    let carrinho = [];
    try {
        // Tenta buscar do localStorage ou da memória
        const carrinhoSalvo = localStorage.getItem('carrinho_venda_direta');
        if (carrinhoSalvo) {
            carrinho = JSON.parse(carrinhoSalvo);
        }
    } catch (e) {
        console.warn('[PIX] Não foi possível recuperar carrinho do localStorage');
    }
    
    // Se não tiver carrinho, usa dados do pedido
    if (carrinho.length === 0 && dadosPedidoPix.itens) {
        carrinho = dadosPedidoPix.itens;
    }
    
    if (carrinho.length === 0) {
        alert('Erro: Não foi possível recuperar os itens da venda.');
        return;
    }
    
    // Busca parcelas se houver
    let parcelas = null;
    if (dadosPedidoPix.numero_parcelas > 1 && dadosPedidoPix.venda_id) {
        try {
            const { CONFIG, API_ENDPOINTS } = await import('./config.js');
            const response = await fetch(`${API_ENDPOINTS.PEDIDO_PARCELAS}?venda_id=${dadosPedidoPix.venda_id}`);
            if (response.ok) {
                const dadosParcelas = await response.json();
                parcelas = dadosParcelas.parcelas || null;
            }
        } catch (error) {
            console.warn('[PIX] Erro ao buscar parcelas:', error);
        }
    }
    
    // Busca dados do cliente se houver (para vendas parceladas)
    let dadosCliente = null;
    if (dadosPedidoPix.cliente_id) {
        try {
            const { API_ENDPOINTS } = await import('./config.js');
            const response = await fetch(`${API_ENDPOINTS.CLIENTE}/${dadosPedidoPix.cliente_id}`);
            if (response.ok) {
                const cliente = await response.json();
                dadosCliente = {
                    nome: cliente.nome_completo || cliente.nome || '',
                    cpf: cliente.cpf || '',
                    telefone: cliente.telefone || '',
                    endereco: cliente.endereco_logradouro || cliente.logradouro || '',
                    numero: cliente.endereco_numero || cliente.numero || '',
                    complemento: cliente.endereco_complemento || cliente.complemento || '',
                    bairro: cliente.endereco_bairro || cliente.bairro || '',
                    cidade: cliente.endereco_cidade || cliente.cidade || '',
                    estado: cliente.endereco_estado || cliente.estado || '',
                    cep: cliente.endereco_cep || cliente.cep || ''
                };
            }
        } catch (error) {
            console.warn('[PIX] Erro ao buscar dados do cliente:', error);
        }
    }
    
    // Gera o comprovante (agora é async)
    await gerarComprovanteVenda(carrinho, {
        ...dadosPedidoPix,
        forma_pagamento: 'PIX',
        parcelas: parcelas,
        cliente: dadosCliente
    });
    
    // Fecha o modal
    fecharModalPixEstatico();
    
    // Limpa dados
    dadosPedidoPix = null;
};

/**
 * Gera comprovante de venda para impressora térmica
 */
async function gerarComprovanteVenda(carrinho, dadosPedido) {
    console.log('[PIX] 🧾 Gerando comprovante. Parcelas:', dadosPedido.parcelas?.length || 0);
    console.log('[PIX] 📊 Dados do pedido:', {
        numero_parcelas: dadosPedido.numero_parcelas,
        tem_parcelas: !!dadosPedido.parcelas,
        quantidade_parcelas: dadosPedido.parcelas?.length || 0,
        parcelas: dadosPedido.parcelas
    });
    
    const now = new Date();
    const dataHora = formatarDataHora(now);
    
    // Busca dados da loja da API
    let dadosEmpresa = {
        nome: 'Loja',
        cpf_cnpj: '',
        telefone: '',
        email: '',
        endereco_completo: '',
        nome_loja: 'Loja'
    };
    
    try {
        // Importa CONFIG dinamicamente
        const { CONFIG, API_ENDPOINTS } = await import('./config.js');
        
        const response = await fetch(`${API_ENDPOINTS.USUARIO_DADOS_LOJA}?usuario_id=${CONFIG.ID_USUARIO_LOJA}`);
        if (response.ok) {
            const dadosLoja = await response.json();
            dadosEmpresa = {
                nome: dadosLoja.nome_loja || dadosLoja.nome || 'Loja',
                cpf_cnpj: dadosLoja.cpf_cnpj || '',
                telefone: dadosLoja.telefone || '',
                email: dadosLoja.email || '',
                endereco: dadosLoja.endereco || '',
                bairro: dadosLoja.bairro || '',
                cidade: dadosLoja.cidade || '',
                estado: dadosLoja.estado || '',
                endereco_completo: dadosLoja.endereco_completo || '',
                logo_path: dadosLoja.logo_path || '',
                nome_loja: dadosLoja.nome_loja || dadosLoja.nome || 'Loja'
            };
        }
    } catch (error) {
        console.warn('[PIX] Erro ao buscar dados da loja, usando valores padrão:', error);
    }
    
    // Constrói URL da logo se houver
    let logoUrl = '';
    if (dadosEmpresa.logo_path) {
        if (dadosEmpresa.logo_path.match(/^(https?:\/\/|\/)/)) {
            // URL completa ou caminho absoluto
            logoUrl = dadosEmpresa.logo_path;
        } else {
            // Caminho relativo - precisa da URL base
            try {
                const { CONFIG } = await import('./config.js');
                logoUrl = CONFIG.URL_BASE_WEB + '/' + dadosEmpresa.logo_path.replace(/^\//, '');
            } catch (e) {
                // Fallback: usa window.location se não conseguir importar CONFIG
                const baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -2).join('/');
                logoUrl = baseUrl + '/' + dadosEmpresa.logo_path.replace(/^\//, '');
            }
        }
    }
    
    // Formata CPF/CNPJ
    const cpfCnpjLimpo = dadosEmpresa.cpf_cnpj ? dadosEmpresa.cpf_cnpj.replace(/[^\d]/g, '') : '';
    const cpfCnpjFormatado = formatarCpfCnpj(dadosEmpresa.cpf_cnpj);
    const isCNPJ = cpfCnpjLimpo.length === 14;
    
    // Formata telefone
    const telefoneFormatado = formatarTelefone(dadosEmpresa.telefone);
    
    // Monta endereço completo a partir dos campos individuais ou usa endereco_completo
    let endereco = '';
    let cidade = '';
    
    if (dadosEmpresa.endereco || dadosEmpresa.bairro || dadosEmpresa.cidade || dadosEmpresa.estado) {
        // Usa campos individuais
        endereco = dadosEmpresa.endereco || '';
        const partesCidade = [];
        if (dadosEmpresa.bairro) partesCidade.push(dadosEmpresa.bairro);
        if (dadosEmpresa.cidade) partesCidade.push(dadosEmpresa.cidade);
        if (dadosEmpresa.estado) partesCidade.push(dadosEmpresa.estado);
        cidade = partesCidade.join(', ');
    } else if (dadosEmpresa.endereco_completo) {
        // Fallback: usa endereco_completo e separa
        const enderecoPartes = dadosEmpresa.endereco_completo.split(',');
        endereco = enderecoPartes[0] || '';
        cidade = enderecoPartes.slice(1).join(', ').trim() || '';
    }
    
    // Calcula totais
    const valorTotal = carrinho.reduce((total, item) => {
        const preco = parseFloat(item.preco || item.preco_venda_sugerido || 0);
        const qtd = parseFloat(item.quantidade || 0);
        return total + (preco * qtd);
    }, 0);
    
    // Formata valor
    const valorFormatado = formatarMoeda(valorTotal);
    
    // Função auxiliar para formatar valores no template
    const formatarValor = (val) => {
        return `R$ ${parseFloat(val).toFixed(2).replace('.', ',')}`;
    };
    
    // Cria HTML do comprovante
    const html = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comprovante de Venda</title>
    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body {
                margin: 0;
                padding: 5mm;
            }
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 5px;
        }
        .logo-container img {
            max-width: 60mm;
            max-height: 30mm;
            height: auto;
            object-fit: contain;
        }
        .empresa-nome {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 3px;
        }
        .empresa-dados {
            font-size: 10px;
            margin: 2px 0;
        }
        .titulo {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            margin: 8px 0;
            text-transform: uppercase;
        }
        .linha {
            border-bottom: 1px dashed #000;
            margin: 5px 0;
            padding-bottom: 3px;
        }
        .item {
            margin: 4px 0;
        }
        .item-descricao {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .item-detalhes {
            font-size: 10px;
            display: flex;
            justify-content: space-between;
        }
        .total {
            text-align: right;
            font-weight: bold;
            font-size: 13px;
            margin-top: 8px;
            padding-top: 5px;
            border-top: 2px solid #000;
        }
        .pagamento {
            margin: 8px 0;
            padding: 5px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        .pagamento-tipo {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px dashed #000;
        }
        .data-hora {
            text-align: center;
            font-size: 10px;
            margin: 5px 0;
        }
        .separador {
            text-align: center;
            margin: 5px 0;
            font-size: 10px;
        }
        .tabela-parcelas {
            margin: 10px 0;
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .tabela-parcelas th,
        .tabela-parcelas td {
            border: 1px solid #000;
            padding: 3px;
            text-align: left;
        }
        .tabela-parcelas th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .tabela-parcelas .col-par {
            width: 15%;
            text-align: center;
        }
        .tabela-parcelas .col-data {
            width: 45%;
            text-align: center;
        }
        .tabela-parcelas .col-valor {
            width: 40%;
            text-align: right;
        }
        .parcela-paga {
            text-decoration: line-through;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        ${logoUrl ? `
        <div class="logo-container">
            <img src="${logoUrl}" alt="Logo" onerror="this.style.display='none';">
        </div>
        ` : ''}
        <div class="empresa-nome">${dadosEmpresa.nome_loja || dadosEmpresa.nome}</div>
        ${cpfCnpjFormatado ? `<div class="empresa-dados">${isCNPJ ? 'CNPJ' : 'CPF'}: ${cpfCnpjFormatado}</div>` : ''}
        ${endereco ? `<div class="empresa-dados">${endereco}</div>` : ''}
        ${cidade ? `<div class="empresa-dados">${cidade}</div>` : ''}
        ${telefoneFormatado ? `<div class="empresa-dados">Fone: ${telefoneFormatado}</div>` : ''}
    </div>
    
    <div class="titulo">COMPROVANTE DE VENDA</div>
    
    <div class="data-hora">
        ${dataHora}
    </div>
    
    ${dadosPedido.cliente ? `
    <div class="separador">--------------------------------</div>
    <div style="margin: 8px 0;">
        <div style="font-weight: bold; margin-bottom: 5px;">CLIENTE:</div>
        <div style="font-size: 11px; line-height: 1.4;">
            <div><strong>${dadosPedido.cliente.nome}</strong></div>
            ${dadosPedido.cliente.cpf ? `<div>CPF: ${formatarCpfCnpj(dadosPedido.cliente.cpf)}</div>` : ''}
            ${dadosPedido.cliente.telefone ? `<div>Fone: ${formatarTelefone(dadosPedido.cliente.telefone)}</div>` : ''}
            ${dadosPedido.cliente.endereco ? `<div>${dadosPedido.cliente.endereco}${dadosPedido.cliente.numero ? ', ' + dadosPedido.cliente.numero : ''}${dadosPedido.cliente.complemento ? ' - ' + dadosPedido.cliente.complemento : ''}</div>` : ''}
            ${dadosPedido.cliente.bairro ? `<div>${dadosPedido.cliente.bairro}` : ''}${dadosPedido.cliente.cidade ? ` - ${dadosPedido.cliente.cidade}` : ''}${dadosPedido.cliente.estado ? `/${dadosPedido.cliente.estado}` : ''}</div>
            ${dadosPedido.cliente.cep ? `<div>CEP: ${dadosPedido.cliente.cep.replace(/^(\d{5})(\d{3})$/, '$1-$2')}</div>` : ''}
        </div>
    </div>
    ` : ''}
    
    <div class="linha"></div>
    
    <div class="separador">--------------------------------</div>
    
    ${carrinho.map(item => {
        const preco = parseFloat(item.preco || item.preco_venda_sugerido || 0);
        const qtd = parseFloat(item.quantidade || 0);
        const subtotal = preco * qtd;
        const nomeProduto = item.nome || item.descricao || item.nome_produto || 'Produto';
        return `
        <div class="item">
            <div class="item-descricao">${nomeProduto}</div>
            <div class="item-detalhes">
                <span>${qtd.toFixed(2)} x ${formatarValor(preco)}</span>
                <span>${formatarValor(subtotal)}</span>
            </div>
        </div>
    `;
    }).join('')}
    
    <div class="separador">--------------------------------</div>
    
    <div class="total">
        TOTAL: ${valorFormatado}
    </div>
    
    <div class="pagamento">
        <div class="pagamento-tipo">FORMA DE PAGAMENTO: ${dadosPedido.forma_pagamento || 'Não informado'}</div>
        ${dadosPedido.numero_parcelas === 1 ? `<div>VALOR PAGO: ${valorFormatado}</div>` : `<div>${dadosPedido.numero_parcelas}x de ${formatarMoeda(valorTotal / dadosPedido.numero_parcelas)}</div>`}
    </div>
    
    ${(() => {
        const temParcelas = dadosPedido.parcelas && Array.isArray(dadosPedido.parcelas) && dadosPedido.parcelas.length > 0;
        const numeroParcelas = dadosPedido.numero_parcelas || 0;
        
        // ✅ Só mostra tabela se houver parcelas E a venda for parcelada (mais de 1 parcela)
        const deveMostrar = temParcelas && numeroParcelas > 1;
        
        console.log('[PIX] 🔍 Verificando se deve mostrar parcelas:', {
            temParcelas,
            numeroParcelas,
            deveMostrar,
            quantidade_parcelas_array: dadosPedido.parcelas?.length || 0
        });
        
        // Se não deve mostrar (venda à vista ou sem parcelas), retorna vazio
        if (!deveMostrar) {
            console.log('[PIX] ℹ️ Venda à vista ou sem parcelas. Tabela de parcelas não será exibida.');
            return '';
        }
        
        return `
    <div class="separador" style="margin-top: 10px;">--------------------------------</div>
    <div style="margin-top: 10px;">
        <div style="font-weight: bold; text-align: center; margin-bottom: 5px; font-size: 11px;">PARCELAS</div>
        <table class="tabela-parcelas">
            <thead>
                <tr>
                    <th style="width: 15%; text-align: center;">PAR.</th>
                    <th style="width: 45%; text-align: center;">DT VENCIMENTO</th>
                    <th style="width: 40%; text-align: right;">VALOR PREST.</th>
                </tr>
            </thead>
            <tbody>
                ${dadosPedido.parcelas.map((parcela, index) => {
                    try {
                        const dataVenc = new Date(parcela.data_vencimento);
                        if (isNaN(dataVenc.getTime())) {
                            console.error('[PIX] ❌ Data inválida para parcela', index + 1, ':', parcela.data_vencimento);
                            return '';
                        }
                        const dataFormatada = String(dataVenc.getDate()).padStart(2, '0') + '/' + 
                                            String(dataVenc.getMonth() + 1).padStart(2, '0') + '/' +
                                            String(dataVenc.getFullYear());
                        const valorParcela = parseFloat(parcela.valor_parcela || 0).toFixed(2).replace('.', ',');
                        const numeroParcela = String(index + 1).padStart(2, '0');
                        const isPaga = parcela.status_parcela_codigo === 'PAGA' || parcela.data_pagamento;
                        const classePaga = isPaga ? 'parcela-paga' : '';
                        
                        return `
                        <tr class="${classePaga}">
                            <td style="text-align: center;">${numeroParcela}</td>
                            <td style="text-align: center;">${dataFormatada}</td>
                            <td style="text-align: right;">${valorParcela}</td>
                        </tr>
                        `;
                    } catch (error) {
                        console.error('[PIX] ❌ Erro ao processar parcela', index + 1, ':', error, parcela);
                        return '';
                    }
                }).filter(row => row !== '').join('')}
            </tbody>
        </table>
    </div>
    `;
    })()}
    
    <div class="footer">
        <div>Obrigado pela preferência!</div>
        <div style="margin-top: 5px;">${dadosEmpresa.nome_loja || dadosEmpresa.nome}</div>
    </div>
    
    <div class="separador" style="margin-top: 10px;">================================</div>
</body>
</html>
    `;
    
    // Abre janela de impressão
    const janelaImpressao = window.open('', '_blank', 'width=300,height=600');
    if (!janelaImpressao) {
        console.error('[PIX] ❌ Não foi possível abrir janela de impressão (bloqueador de popup?)');
        alert('Não foi possível abrir a janela de impressão. Verifique se os popups estão habilitados.');
        return;
    }
    
    janelaImpressao.document.write(html);
    janelaImpressao.document.close();
    
        // Aguarda carregamento e imprime
        setTimeout(() => {
            try {
                janelaImpressao.focus();
                
                // Adiciona listener para fechar a janela após impressão ou cancelamento
                const fecharJanela = () => {
                    setTimeout(() => {
                        try {
                            if (janelaImpressao && !janelaImpressao.closed) {
                                janelaImpressao.close();
                                console.log('[PIX] ✅ Janela de impressão fechada');
                            }
                        } catch (e) {
                            console.warn('[PIX] ⚠️ Erro ao fechar janela:', e);
                        }
                    }, 500);
                };
                
                // Listener para quando a impressão for concluída ou cancelada
                if (janelaImpressao.matchMedia) {
                    const mediaQueryList = janelaImpressao.matchMedia('print');
                    const handler = (mql) => {
                        if (!mql.matches) {
                            // Impressão foi cancelada ou concluída
                            mediaQueryList.removeEventListener('change', handler);
                            fecharJanela();
                        }
                    };
                    mediaQueryList.addEventListener('change', handler);
                }
                
                // Fallback: fecha após 5 segundos se não houver interação
                const timeoutFechar = setTimeout(() => {
                    console.log('[PIX] ⏰ Timeout: fechando janela de impressão');
                    fecharJanela();
                }, 5000);
                
                // Limpa timeout se a janela for fechada manualmente
                janelaImpressao.addEventListener('beforeunload', () => {
                    clearTimeout(timeoutFechar);
                });
                
                // Tenta imprimir
                janelaImpressao.print();
                
                // Fecha automaticamente após um tempo se não houver interação do usuário
                setTimeout(() => {
                    if (!janelaImpressao.closed) {
                        clearTimeout(timeoutFechar);
                        fecharJanela();
                    }
                }, 3000);
                
            } catch (e) {
                console.error('[PIX] ❌ Erro ao imprimir:', e);
                if (janelaImpressao && !janelaImpressao.closed) {
                    janelaImpressao.close();
                }
            }
        }, 300);
}

/**
 * Formata data e hora para o comprovante
 */
function formatarDataHora(data) {
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    const hora = String(data.getHours()).padStart(2, '0');
    const minuto = String(data.getMinutes()).padStart(2, '0');
    const segundo = String(data.getSeconds()).padStart(2, '0');
    
    return `${dia}/${mes}/${ano} ${hora}:${minuto}:${segundo}`;
}

/**
 * Formata valor monetário
 */
function formatarMoeda(valor) {
    return `R$ ${parseFloat(valor).toFixed(2).replace('.', ',')}`;
}

/**
 * Formata CPF ou CNPJ
 */
function formatarCpfCnpj(cpfCnpj) {
    if (!cpfCnpj) return '';
    const limpo = cpfCnpj.replace(/[^\d]/g, '');
    
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
 */
function formatarTelefone(telefone) {
    if (!telefone) return '';
    const limpo = telefone.replace(/[^\d]/g, '');
    
    if (limpo.length === 11) {
        // (00) 00000-0000
        return limpo.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
    } else if (limpo.length === 10) {
        // (00) 0000-0000
        return limpo.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
    }
    
    return telefone;
}

// Exporta para garantir acesso no app.js
window.mostrarModalPixEstatico = mostrarModalPixEstatico;
export { gerarComprovanteVenda };

