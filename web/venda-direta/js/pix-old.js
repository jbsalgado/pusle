// pix.js - Geração de QR Code PIX Estático
// Baseado na especificação EMV QR Code do Banco Central

// Configuração PIX Estática
// Definida diretamente aqui para evitar problemas de importação/cache
const PIX_CONFIG = {
    chave: '81992888872', // Chave PIX (celular)
    nome: 'JOSE BARBOSA DOS SANTOS',
    cidade: 'CARUARU'
};

/**
 * Gera o código PIX estático (EMV QR Code)
 * @param {string} chave - Chave PIX (celular, CPF, email, etc)
 * @param {string} nome - Nome do recebedor
 * @param {string} cidade - Cidade do recebedor
 * @param {number} valor - Valor da transação (opcional)
 * @param {string} descricao - Descrição da transação (opcional)
 * @returns {string} Código PIX EMV
 */
export function gerarCodigoPixEstatico(chave, nome, cidade, valor = null, descricao = null) {
    // Remove formatação da chave (pontos, traços, espaços)
    const chaveLimpa = chave.replace(/[^0-9a-zA-Z@.-]/g, '');
    
    // Determina tipo de chave PIX
    // 01 = Celular (apenas números, 10-11 dígitos)
    // 02 = CPF (11 dígitos)
    // 03 = CNPJ (14 dígitos)
    // 04 = Email
    // 05 = Chave aleatória (UUID)
    let tipoChave = '01'; // Default: celular
    
    if (/^[0-9]{11}$/.test(chaveLimpa)) {
        tipoChave = '02'; // CPF
    } else if (/^[0-9]{14}$/.test(chaveLimpa)) {
        tipoChave = '03'; // CNPJ
    } else if (/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(chaveLimpa)) {
        tipoChave = '04'; // Email
    } else if (/^[0-9]{10,11}$/.test(chaveLimpa)) {
        tipoChave = '01'; // Celular
    } else if (/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(chaveLimpa)) {
        tipoChave = '05'; // Chave aleatória
    }
    
    const payload = [];
    
    // CORREÇÃO: Todo código PIX DEVE começar com 000201
    // Campo 00: Point of Initiation Method (obrigatório)
    // 01 = único uso, 12 = múltiplo uso (estático)
    // Para PIX estático, usamos 01 (único uso)
    payload.push('00' + '02' + '01');
    
    // Campo 01: Payload Format Indicator (obrigatório)
    payload.push('01' + '02' + '01');
    
    // Merchant Account Information (obrigatório) - ID 26
    // GUI (00) - Identificador do registro br.gov.bcb.pix
    const gui = '00' + String(14).padStart(2, '0') + 'br.gov.bcb.pix';
    // CORREÇÃO 2: Chave PIX SEMPRE no sub-campo 01 (não usar tipoChave aqui)
    // O sub-campo 01 é sempre para a chave PIX, independente do tipo
    const chavePix = '01' + String(chaveLimpa.length).padStart(2, '0') + chaveLimpa;
    const merchantAccount = gui + chavePix;
    payload.push('26' + String(merchantAccount.length).padStart(2, '0') + merchantAccount);
    
    // Merchant Category Code (opcional) - ID 52, tamanho 04, valor 0000
    payload.push('52' + '04' + '0000');
    
    // Transaction Currency (obrigatório) - ID 53, tamanho 03, valor 986 (BRL)
    payload.push('53' + '03' + '986');
    
    // Transaction Amount (obrigatório se valor informado) - ID 54
    if (valor !== null && valor > 0) {
        // Formata valor com 2 casas decimais, sem separadores
        const valorStr = parseFloat(valor).toFixed(2);
        payload.push('54' + String(valorStr.length).padStart(2, '0') + valorStr);
    }
    
    // Country Code (obrigatório) - ID 58, tamanho 02, valor BR
    payload.push('58' + '02' + 'BR');
    
    // Merchant Name (obrigatório) - ID 59, máximo 25 caracteres
    const nomeLimitado = nome.substring(0, 25).trim();
    payload.push('59' + String(nomeLimitado.length).padStart(2, '0') + nomeLimitado);
    
    // Merchant City (obrigatório) - ID 60, máximo 15 caracteres
    const cidadeLimitada = cidade.substring(0, 15).trim();
    payload.push('60' + String(cidadeLimitada.length).padStart(2, '0') + cidadeLimitada);
    
    // Additional Data Field Template (opcional) - ID 62
    // Sub-campo 05 = Reference Label (TxID - Identificador da Transação)
    // CRÍTICO: TxID aceita APENAS letras (a-z, A-Z) e números (0-9)
    // NÃO aceita: espaços, barras, hífens, caracteres especiais
    if (descricao) {
        // Remove todos os caracteres não permitidos (mantém apenas letras e números)
        const descricaoLimpa = descricao.replace(/[^a-zA-Z0-9]/g, '');
        const descricaoLimitada = descricaoLimpa.substring(0, 25);
        
        if (descricaoLimitada.length > 0) {
            // Formato: 05 (tipo) + tamanho (2 dígitos) + valor
            const additionalData = '05' + String(descricaoLimitada.length).padStart(2, '0') + descricaoLimitada;
            payload.push('62' + String(additionalData.length).padStart(2, '0') + additionalData);
        }
    }
    
    // Monta payload sem CRC
    const payloadSemCRC = payload.join('');
    
    // Calcula CRC16
    const crc = calcularCRC16(payloadSemCRC + '6304');
    
    // Adiciona CRC16 - ID 63, tamanho 04
    payload.push('63' + '04' + crc);
    
    const codigoFinal = payload.join('');
    
    console.log('[PIX] ✅ Código PIX gerado (debug):', {
        chaveOriginal: chave,
        chaveLimpa: chaveLimpa,
        tipoChave: tipoChave,
        nome: nomeLimitado,
        cidade: cidadeLimitada,
        valor: valor,
        descricao: descricao ? descricao.replace(/[^a-zA-Z0-9]/g, '').substring(0, 25) : null,
        inicioCodigo: codigoFinal.substring(0, 6),
        payloadSemCRC: payloadSemCRC,
        dadosParaCRC: payloadSemCRC + '6304',
        crc: crc,
        codigoCompleto: codigoFinal,
        tamanhoCodigo: codigoFinal.length
    });
    
    // Validação: código deve começar com 000201
    if (!codigoFinal.startsWith('000201')) {
        console.error('[PIX] ❌ ERRO: Código não começa com 000201!', codigoFinal.substring(0, 10));
    } else {
        console.log('[PIX] ✅ Validação: Código começa corretamente com 000201');
    }
    
    return codigoFinal;
}

/**
 * Calcula CRC16-CCITT (polinômio 0x1021)
 * @param {string} data - Dados para calcular CRC
 * @returns {string} CRC16 em hexadecimal (4 dígitos)
 */
function calcularCRC16(data) {
    // CRC16-CCITT (polinômio 0x1021, valor inicial 0xFFFF)
    // Implementação corrigida conforme especificação EMV
    let crc = 0xFFFF;
    const polynomial = 0x1021;
    
    // Converte string para array de bytes
    for (let i = 0; i < data.length; i++) {
        const byte = data.charCodeAt(i);
        crc ^= (byte << 8);
        
        for (let bit = 0; bit < 8; bit++) {
            if (crc & 0x8000) {
                crc = ((crc << 1) ^ polynomial) & 0xFFFF;
            } else {
                crc = (crc << 1) & 0xFFFF;
            }
        }
    }
    
    // Retorna CRC em hexadecimal maiúsculo com 4 dígitos
    const crcHex = crc.toString(16).toUpperCase().padStart(4, '0');
    
    return crcHex;
}

/**
 * Gera QR Code visual a partir do código PIX
 * @param {string} codigoPix - Código PIX EMV
 * @param {HTMLElement} container - Container onde o QR Code será renderizado
 * @returns {Promise<void>}
 */
export async function gerarQRCodeVisual(codigoPix, container) {
    try {
        // Limpa o container
        container.innerHTML = '';
        
        // Usa API online para gerar QR Code (mais confiável que CDN)
        const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(codigoPix)}`;
        
        // Cria imagem para o QR Code
        const img = document.createElement('img');
        img.src = qrCodeUrl;
        img.alt = 'QR Code PIX';
        img.className = 'mx-auto border rounded-lg';
        img.style.maxWidth = '300px';
        img.style.width = '100%';
        img.style.height = 'auto';
        
        // Adiciona evento de erro
        img.onerror = () => {
            console.error('[PIX] Erro ao carregar QR Code da API');
            container.innerHTML = `
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                    <p class="text-yellow-800 text-sm font-semibold mb-2">⚠️ QR Code não disponível</p>
                    <p class="text-yellow-700 text-xs">Use o código copia e cola abaixo para pagar</p>
                </div>
            `;
        };
        
        img.onload = () => {
            console.log('[PIX] ✅ QR Code carregado com sucesso');
        };
        
        container.appendChild(img);
        
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


/**
 * Mostra modal com QR Code PIX estático
 * @param {number} valor - Valor da venda
 * @param {string} descricao - Descrição opcional
 */
export function mostrarModalPixEstatico(valor, descricao = null) {
    console.log('[PIX] mostrarModalPixEstatico chamado com:', { valor, descricao });
    
    // Verifica se o modal existe
    const modal = document.getElementById('modal-pix-estatico');
    console.log('[PIX] Modal encontrado:', !!modal);
    
    if (!modal) {
        console.error('[PIX] ❌ Modal modal-pix-estatico não encontrado no DOM!');
        alert('Erro: Modal PIX não encontrado. Verifique o HTML.');
        return;
    }
    
    // Gera descrição/TxID sem caracteres especiais (apenas letras e números)
    let txId = descricao;
    if (!txId) {
        // Gera TxID padrão: VendaDireta + timestamp
        const timestamp = Date.now().toString();
        txId = `VendaDireta${timestamp}`;
    }
    
    // Remove caracteres não permitidos (mantém apenas letras e números)
    txId = txId.replace(/[^a-zA-Z0-9]/g, '');
    
    // Gera código PIX
    const codigoPix = gerarCodigoPixEstatico(
        PIX_CONFIG.chave,
        PIX_CONFIG.nome,
        PIX_CONFIG.cidade,
        valor,
        txId
    );
    
    console.log('[PIX] Código PIX gerado:', codigoPix);
    console.log('[PIX] PIX_CONFIG:', PIX_CONFIG);
    
    // Atualiza valores no modal
    const valorElement = document.getElementById('pix-valor');
    const codigoElement = document.getElementById('pix-codigo-copia-cola');
    const qrContainer = document.getElementById('pix-qrcode-container');
    
    console.log('[PIX] Elementos encontrados:', {
        valorElement: !!valorElement,
        codigoElement: !!codigoElement,
        qrContainer: !!qrContainer
    });
    
    if (valorElement) {
        valorElement.textContent = `R$ ${valor.toFixed(2).replace('.', ',')}`;
        console.log('[PIX] Valor atualizado no elemento');
    } else {
        console.error('[PIX] ❌ Elemento pix-valor não encontrado!');
    }
    
    if (codigoElement) {
        codigoElement.textContent = codigoPix;
        console.log('[PIX] Código atualizado no elemento');
    } else {
        console.error('[PIX] ❌ Elemento pix-codigo-copia-cola não encontrado!');
    }
    
    // Gera QR Code visual
    if (qrContainer) {
        console.log('[PIX] Gerando QR Code visual...');
        gerarQRCodeVisual(codigoPix, qrContainer);
    } else {
        console.error('[PIX] ❌ Container pix-qrcode-container não encontrado!');
    }
    
    // Abre modal
    console.log('[PIX] Abrindo modal...');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    console.log('[PIX] ✅ Modal aberto!');
    
    // Adiciona evento de fechar ao clicar fora (apenas uma vez)
    const handler = function(e) {
        if (e.target === modal) {
            window.fecharModalPixEstatico();
        }
    };
    modal.removeEventListener('click', handler); // Remove se já existir
    modal.addEventListener('click', handler);
}

/**
 * Copia código PIX para área de transferência
 */
window.copiarCodigoPix = function() {
    const codigoElement = document.getElementById('pix-codigo-copia-cola');
    if (!codigoElement) return;
    
    const codigo = codigoElement.textContent.trim();
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(codigo).then(() => {
            mostrarNotificacao('Código PIX copiado!');
        }).catch(err => {
            console.error('[PIX] Erro ao copiar:', err);
            // Fallback: selecionar texto
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
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        mostrarNotificacao('Código PIX copiado!');
    } catch (err) {
        console.error('[PIX] Erro ao copiar (fallback):', err);
        alert('Erro ao copiar. Por favor, copie manualmente o código acima.');
    }
    
    document.body.removeChild(textarea);
}

/**
 * Fecha o modal PIX estático
 */
window.fecharModalPixEstatico = function() {
    console.log('[PIX] Fechando modal PIX estático...');
    const modal = document.getElementById('modal-pix-estatico');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        console.log('[PIX] ✅ Modal fechado');
    } else {
        console.error('[PIX] ❌ Modal não encontrado para fechar');
    }
};

/**
 * Mostra notificação temporária
 */
function mostrarNotificacao(mensagem) {
    const notif = document.createElement('div');
    notif.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-bounce';
    notif.textContent = mensagem;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.remove();
    }, 3000);
}

// Exporta também para window para garantir acesso global
window.mostrarModalPixEstatico = mostrarModalPixEstatico;

