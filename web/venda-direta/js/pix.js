// pix.js - Geração de QR Code PIX Estático (CORRIGIDO)
// Baseado na especificação EMV QR Code do Banco Central

// Configuração PIX Estática
const PIX_CONFIG = {
    chave: '+5581992888872', // Chave PIX (celular)
    nome: 'JOSE BARBOSA DOS SANTOS', // Sem acentos
    cidade: 'CARUARU' // Sem acentos
};

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

/**
 * Abre o Modal com os dados preenchidos
 */
export function mostrarModalPixEstatico(valor, txId) {
    console.log('[PIX] Abrindo modal para:', valor, txId);
    
    const modal = document.getElementById('modal-pix-estatico');
    if (!modal) {
        alert('Erro: Modal PIX não encontrado no HTML.');
        return;
    }

    // 1. Gera o código PIX string
    const codigoPix = gerarCodigoPixEstatico(
        PIX_CONFIG.chave,
        PIX_CONFIG.nome,
        PIX_CONFIG.cidade,
        valor,
        txId
    );

    // 2. Preenche os dados na tela
    const elValor = document.getElementById('pix-valor');
    const elCodigo = document.getElementById('pix-codigo-copia-cola');
    const elContainerQR = document.getElementById('pix-qrcode-container');

    if (elValor) elValor.textContent = `R$ ${parseFloat(valor).toFixed(2).replace('.', ',')}`;
    if (elCodigo) elCodigo.textContent = codigoPix;
    if (elContainerQR) gerarQRCodeVisual(codigoPix, elContainerQR);

    // 3. Mostra o modal e bloqueia rolagem
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
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

// Exporta para garantir acesso no app.js
window.mostrarModalPixEstatico = mostrarModalPixEstatico;

