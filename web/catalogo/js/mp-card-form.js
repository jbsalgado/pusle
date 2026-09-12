/**
 * mp-card-form.js
 * Módulo de Checkout Transparente — Cartão de Crédito/Débito via Mercado Pago
 *
 * Responsabilidades:
 *  1. Renderizar o formulário de cartão dentro de um container informado
 *  2. Inicializar o MP CardForm com a Public Key da loja
 *  3. Detectar a bandeira do cartão pelo BIN (6 primeiros dígitos)
 *  4. Buscar opções de parcelamento em tempo real
 *  5. Ao submeter, gerar o cardToken e atribuir a window.mpCardToken / window.mpInstallments
 *  6. Chamar um callback onTokenGenerated(token, installments, paymentMethodId, issuerId)
 */

import { API_ENDPOINTS, CONFIG } from './config.js';

let mpInstance        = null;   // instância MercadoPago()
let cardFormInstance  = null;   // instância cardForm
let _onTokenGenerated = null;   // callback externo
let _tipoCartao       = 'credit_card'; // 'debit_card' | 'credit_card'

/**
 * Inicializa o CardForm do Mercado Pago dentro de um elemento HTML.
 *
 * @param {string}   containerId        - ID do elemento onde o form será montado
 * @param {number}   valorTotal         - Valor total da compra (para cálculo de parcelas)
 * @param {Function} onTokenGenerated   - Callback chamado quando o token estiver pronto
 *                                        Recebe: (token, installments, paymentMethodId, issuerId, tipoCartao)
 * @param {string}   tipoCartao         - 'debit_card' ou 'credit_card'
 */
export async function inicializarCardForm(containerId, valorTotal, onTokenGenerated, tipoCartao = 'credit_card') {
    _onTokenGenerated = onTokenGenerated;
    _tipoCartao       = tipoCartao || 'credit_card';
    window.mpTipoCartao = _tipoCartao;

    // Garante que o SDK MP está carregado
    if (typeof window.MercadoPago === 'undefined') {
        throw new Error('SDK Mercado Pago não carregado. Verifique se o script https://sdk.mercadopago.com/js/v2 está na página.');
    }

    const publicKey = window.GATEWAY_CONFIG?.mercadopago_public_key;
    if (!publicKey) {
        throw new Error('Mercado Pago Public Key não configurada para esta loja.');
    }

    // Destrói instância anterior do cardForm se existir
    if (cardFormInstance) {
        try { cardFormInstance.unmount(); } catch (_) {}
        cardFormInstance = null;
    }

    // Cria nova instância MP para garantir que o cardForm monte os iframes sem usar cache antigo
    mpInstance = new window.MercadoPago(publicKey, {
        locale: 'pt-BR',
    });

    cardFormInstance = mpInstance.cardForm({
        amount:    String(valorTotal.toFixed(2)),
        iframe:    true,
        autoMount: true,
        form: {
            id:         containerId,
            cardNumber: {
                id:          'form-checkout__cardNumber',
                placeholder: 'Número do cartão',
                style: {
                    fontSize: '14px',
                    color: '#1e293b',
                    padding: '0 12px',
                    fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
                },
            },
            expirationDate: {
                id:          'form-checkout__expirationDate',
                placeholder: 'MM/AA',
                style: {
                    fontSize: '14px',
                    color: '#1e293b',
                    padding: '0 12px',
                    fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
                },
            },
            securityCode: {
                id:          'form-checkout__securityCode',
                placeholder: 'CVV',
                style: {
                    fontSize: '14px',
                    color: '#1e293b',
                    padding: '0 12px',
                    fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
                },
            },
            cardholderName: {
                id:          'form-checkout__cardholderName',
                placeholder: 'Nome como no cartão',
            },
            issuer: {
                id:    'form-checkout__issuer',
                placeholder: 'Banco emissor',
            },
            installments: {
                id:    'form-checkout__installments',
                placeholder: 'Parcelas',
            },
            identificationType: {
                id:    'form-checkout__identificationType',
                placeholder: 'Tipo de documento',
            },
            identificationNumber: {
                id:          'form-checkout__identificationNumber',
                placeholder: 'CPF',
            },
            cardholderEmail: {
                id:          'form-checkout__cardholderEmail',
                placeholder: 'E-mail',
            },
        },
        callbacks: {
            onFormMounted: (error) => {
                if (error) {
                    console.error('[MP CardForm] ❌ Erro ao montar formulário:', error);
                } else {
                    console.log('[MP CardForm] ✅ Formulário montado com sucesso (' + _tipoCartao + ')');
                    // Dispara evento input nos campos preenchidos para sincronizar validação do SDK
                    ['form-checkout__cardholderName', 'form-checkout__identificationNumber', 'form-checkout__cardholderEmail'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el && el.value) {
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (el) {
                            el.addEventListener('input', () => {
                                el.classList.remove('mp-field-error');
                                el.style.borderColor = '';
                                el.style.backgroundColor = '';
                            });
                        }
                    });

                    // Limpa erro ao clicar nos iframes do SDK
                    ['form-checkout__cardNumber', 'form-checkout__expirationDate', 'form-checkout__securityCode'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) {
                            el.addEventListener('click', () => {
                                el.classList.remove('mp-field-error');
                                el.style.borderColor = '';
                                el.style.backgroundColor = '';
                            });
                        }
                    });
                }
            },
            onSubmit: async (event) => {
                event.preventDefault();
                await _handleCardFormSubmit();
            },
            onFetching: (resource) => {
                // Mercado Pago SDK v2: onFetching é chamado ao consultar bandeira/banco/parcelas pelo BIN do cartão.
                // DEVE retornar uma função callback que o SDK executa quando o fetch assíncrono termina.
                return () => {
                    const submitBtn = document.getElementById('btn-pagar-cartao');
                    if (submitBtn && !submitBtn.textContent.includes('Processando')) {
                        submitBtn.disabled = false;
                    }
                };
            },
            onReady: () => {
                const submitBtn = document.getElementById('btn-pagar-cartao');
                if (submitBtn) submitBtn.disabled = false;
            },
            onError: (errors) => {
                console.error('[MP CardForm] Erros de validação capturados pelo SDK:', errors);
                const submitBtn = document.getElementById('btn-pagar-cartao');
                if (submitBtn && !submitBtn.textContent.includes('Processando')) {
                    submitBtn.disabled = false;
                }
                _exibirErrosValidacaoCardForm(errors);
            },
        },
    });

    return cardFormInstance;
}

/**
 * Remove destaque de erro visual dos campos do formulário
 */
function _limparEstilosErrosCardForm() {
    const ids = [
        'form-checkout__cardNumber',
        'form-checkout__expirationDate',
        'form-checkout__securityCode',
        'form-checkout__cardholderName',
        'form-checkout__identificationNumber',
        'form-checkout__cardholderEmail',
        'form-checkout__installments'
    ];
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('mp-field-error');
            el.style.borderColor = '';
            el.style.backgroundColor = '';
        }
    });
    const errorEl = document.getElementById('mp-card-error');
    if (errorEl) {
        errorEl.textContent = '';
    }
}

/**
 * Destaca visualmente um campo com erro
 */
function _marcarCampoComErro(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('mp-field-error');
        el.style.borderColor = '#ef4444';
        el.style.backgroundColor = '#fff1f2';
    }
}

/**
 * Mapeia erros do SDK v2 em mensagens claras em português e destaca campos
 */
function _exibirErrosValidacaoCardForm(errors) {
    _limparEstilosErrosCardForm();
    const errorEl = document.getElementById('mp-card-error');
    const mensagens = [];

    if (!Array.isArray(errors) || errors.length === 0) {
        if (errorEl) {
            errorEl.textContent = '⚠️ Por favor, revise os dados do cartão (número, validade MM/AA e CVV).';
        }
        return;
    }

    let primeiroCampoFoco = null;

    errors.forEach(err => {
        const field = (err.field || err.name || '').toLowerCase();
        const msg = (err.message || err.cause || '').toLowerCase();

        if (field.includes('cardnumber') || msg.includes('card_number') || msg.includes('cardnumber')) {
            _marcarCampoComErro('form-checkout__cardNumber');
            mensagens.push('Número do cartão inválido ou incompleto');
            if (!primeiroCampoFoco) primeiroCampoFoco = 'form-checkout__cardNumber';
        } else if (field.includes('expiration') || field.includes('date') || msg.includes('expiration') || msg.includes('date')) {
            _marcarCampoComErro('form-checkout__expirationDate');
            mensagens.push('Data de validade obrigatória (MM/AA)');
            if (!primeiroCampoFoco) primeiroCampoFoco = 'form-checkout__expirationDate';
        } else if (field.includes('security') || field.includes('cvv') || field.includes('code') || msg.includes('security_code')) {
            _marcarCampoComErro('form-checkout__securityCode');
            mensagens.push('Código de segurança (CVV) obrigatório');
            if (!primeiroCampoFoco) primeiroCampoFoco = 'form-checkout__securityCode';
        } else if (field.includes('cardholdername') || field.includes('name') || msg.includes('cardholder_name')) {
            _marcarCampoComErro('form-checkout__cardholderName');
            mensagens.push('Nome impresso no cartão obrigatório');
            if (!primeiroCampoFoco) primeiroCampoFoco = 'form-checkout__cardholderName';
        } else if (field.includes('identification') || field.includes('doc') || msg.includes('identification')) {
            _marcarCampoComErro('form-checkout__identificationNumber');
            mensagens.push('CPF/Documento inválido');
            if (!primeiroCampoFoco) primeiroCampoFoco = 'form-checkout__identificationNumber';
        } else if (field.includes('email') || msg.includes('email')) {
            _marcarCampoComErro('form-checkout__cardholderEmail');
            mensagens.push('E-mail inválido');
            if (!primeiroCampoFoco) primeiroCampoFoco = 'form-checkout__cardholderEmail';
        } else if (field.includes('installment') || msg.includes('installment')) {
            _marcarCampoComErro('form-checkout__installments');
            mensagens.push('Selecione as parcelas');
        } else {
            mensagens.push(err.message || 'Dados do cartão incompletos');
        }
    });

    if (errorEl) {
        const unicas = [...new Set(mensagens)];
        errorEl.textContent = '⚠️ ' + unicas.join(' • ');
    }

    if (primeiroCampoFoco) {
        const el = document.getElementById(primeiroCampoFoco);
        if (el && typeof el.focus === 'function') {
            try { el.focus(); } catch (_) {}
        }
    }
}

/**
 * Manipula o submit do CardForm: valida campos, gera token e chama o callback.
 */
async function _handleCardFormSubmit() {
    const submitBtn = document.getElementById('btn-pagar-cartao');
    const errorEl   = document.getElementById('mp-card-error');
    const isDebito  = (_tipoCartao === 'debit_card');

    _limparEstilosErrosCardForm();

    // Validações rápidas pré-submit nos campos de texto visíveis
    const nomeEl  = document.getElementById('form-checkout__cardholderName');
    const docEl   = document.getElementById('form-checkout__identificationNumber');
    const emailEl = document.getElementById('form-checkout__cardholderEmail');

    if (nomeEl && !nomeEl.value.trim()) {
        _marcarCampoComErro('form-checkout__cardholderName');
        if (errorEl) errorEl.textContent = '⚠️ Por favor, informe o nome exatamente como impresso no cartão.';
        nomeEl.focus();
        return;
    }
    if (docEl && docEl.value.replace(/\D/g, '').length < 11) {
        _marcarCampoComErro('form-checkout__identificationNumber');
        if (errorEl) errorEl.textContent = '⚠️ Por favor, informe um CPF válido com 11 dígitos.';
        docEl.focus();
        return;
    }
    if (emailEl && (!emailEl.value.trim() || !emailEl.value.includes('@'))) {
        _marcarCampoComErro('form-checkout__cardholderEmail');
        if (errorEl) errorEl.textContent = '⚠️ Por favor, informe um e-mail válido para o comprovante.';
        emailEl.focus();
        return;
    }

    if (submitBtn) {
        submitBtn.disabled    = true;
        submitBtn.textContent = '🔄 Processando...';
    }
    if (errorEl) errorEl.textContent = '';

    try {
        const formData = cardFormInstance.getCardFormData();

        const token           = formData.token;
        const installments    = isDebito ? 1 : (parseInt(formData.installments, 10) || 1);
        const paymentMethodId = formData.paymentMethodId;   // ex: 'visa', 'master'
        const issuerId        = formData.issuerId;

        if (!token) {
            throw new Error('Não foi possível validar os dados do cartão. Por favor, confira o número, a data de validade (MM/AA) e o CVV.');
        }

        // Salva no window para que gateway-pagamento.js consuma
        window.mpCardToken       = token;
        window.mpInstallments    = installments;
        window.mpPaymentMethodId = paymentMethodId;
        window.mpIssuerId        = issuerId;
        window.mpTipoCartao      = _tipoCartao;

        console.log('[MP CardForm] ✅ Token gerado:', token, '| Tipo:', _tipoCartao, '| Parcelas:', installments, '| Bandeira:', paymentMethodId);

        if (typeof _onTokenGenerated === 'function') {
            await _onTokenGenerated(token, installments, paymentMethodId, issuerId, _tipoCartao);
        }
    } catch (error) {
        console.error('[MP CardForm] ❌ Erro:', error);
        if (errorEl) {
            let msg = error.message || 'Erro ao processar cartão. Tente novamente.';
            if (msg.includes('not_result_by_params') || msg.includes('No result found')) {
                msg = 'Este cartão não é aceito para compras no débito online. Por favor, tente na opção Crédito ou pague via PIX.';
            }
            errorEl.textContent = msg;
        }
    } finally {
        if (submitBtn) {
            submitBtn.disabled    = false;
            submitBtn.textContent = isDebito ? '💳 Pagar no Débito' : '💳 Pagar com Cartão';
        }
    }
}

/**
 * Desmonta o formulário (chamar ao fechar o modal).
 */
export function destruirCardForm() {
    if (cardFormInstance) {
        try { cardFormInstance.unmount(); } catch (_) {}
        cardFormInstance = null;
    }
    window.mpCardToken       = null;
    window.mpInstallments    = null;
    window.mpPaymentMethodId = null;
    window.mpIssuerId        = null;
    window.mpTipoCartao      = null;
    _tipoCartao              = 'credit_card';
}

/**
 * Busca as opções de parcelamento para exibição prévia (ex: ao mudar o BIN).
 * Chamada opcional — o CardForm já faz isso internamente, mas pode ser útil
 * para exibir uma prévia antes do submit.
 */
export async function buscarParcelasMP(valorTotal, bin = null, paymentMethodId = 'credit_card') {
    try {
        const params = new URLSearchParams({
            tenant_id:         CONFIG.ID_USUARIO_LOJA,
            amount:            valorTotal,
            payment_method_id: paymentMethodId,
        });
        if (bin) params.set('bin', bin);

        const resp = await fetch(`${API_ENDPOINTS.MERCADOPAGO_BUSCAR_PARCELAS}?${params}`, {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store',
        });

        if (!resp.ok) return [];

        const data = await resp.json();
        return data.sucesso ? (data.parcelas || []) : [];
    } catch (error) {
        console.error('[MP Parcelas] Erro ao buscar parcelas:', error);
        return [];
    }
}

/**
 * Gera o HTML do modal/container do formulário de cartão.
 * Pode ser inserido em qualquer ponto do DOM.
 *
 * @param {Object|null} cliente     - Dados do cliente para pré-preenchimento
 * @param {string}      tipoCartao  - 'debit_card' ou 'credit_card'
 */
export function gerarHtmlFormCartao(cliente = null, tipoCartao = 'credit_card') {
    const isDebito = (tipoCartao === 'debit_card');
    const nomeVal = (cliente?.nome_completo || cliente?.nome || '').trim();
    const docVal = (cliente?.cpf_cnpj || cliente?.cpf || '').replace(/\D/g, '');
    const emailVal = (cliente?.email || '').trim();

    return `
<form id="form-checkout-mp-cartao" class="mp-card-form-container">

    <!-- Seção de Pagamento Rápido: Carteiras Digitais (Google Pay / Apple Pay / 1-Clique) -->
    <div id="mp-wallet-quickpay-section" style="margin-bottom: 4px;">
        <div style="background: linear-gradient(135deg, #090d16, #1e1b4b); border: 1px solid rgba(99, 102, 241, 0.35); border-radius: 14px; padding: 14px 16px; text-align: center; color: white; box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
            <div style="display:flex; align-items:center; justify-content:center; gap: 8px; margin-bottom: 4px;">
                <span style="font-size: 15px;">⚡</span>
                <span style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #a5f3fc;">Aproximação & Carteiras Digitais</span>
            </div>
            <p style="font-size: 11px; color: #cbd5e1; margin: 0 0 10px 0; line-height: 1.3;">
                Pague em 1 clique com <strong>Google Pay</strong>, <strong>Apple Pay</strong> ou biometria.
            </p>
            <div id="wallet-brick-container" style="min-height: 48px; display: flex; align-items: center; justify-content: center;">
                <div id="wallet-brick-loading" style="font-size: 11px; color: #94a3b8; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <div style="width: 14px; height: 14px; border: 2px solid #818cf8; border-top-color: transparent; border-radius: 50%; animation: mp-spin 0.8s linear infinite;"></div>
                    Carregando carteira digital...
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; margin: 16px 0 12px 0;">
            <div style="flex: 1; border-bottom: 1px solid #e2e8f0;"></div>
            <span style="padding: 0 10px; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Ou preencha os dados do cartão</span>
            <div style="flex: 1; border-bottom: 1px solid #e2e8f0;"></div>
        </div>
    </div>

    ${isDebito ? `
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-left:4px solid #3b82f6;border-radius:8px;padding:12px 14px;display:flex;align-items:flex-start;gap:10px;">
        <span style="font-size:20px;line-height:1.2;">💳</span>
        <div>
            <div style="font-size:13px;font-weight:700;color:#1e40af;">Cartão de Débito (Cobrança à vista)</div>
            <div style="font-size:11px;color:#1e3a8a;line-height:1.4;margin-top:2px;">
                Cobrança à vista debitada na conta. Cartões múltiplos (débito/crédito) são aceitos e processados à vista (1x) sem qualquer acréscimo.
            </div>
        </div>
    </div>
    ` : ''}

    <!-- Número do cartão -->
    <div class="mp-field-group">
        <label class="mp-label" for="form-checkout__cardNumber">Número do Cartão</label>
        <div id="form-checkout__cardNumber" class="mp-sdk-field"></div>
    </div>

    <!-- Linha: validade + CVV -->
    <div class="mp-field-row">
        <div class="mp-field-group">
            <label class="mp-label" for="form-checkout__expirationDate">Validade</label>
            <div id="form-checkout__expirationDate" class="mp-sdk-field"></div>
        </div>
        <div class="mp-field-group">
            <label class="mp-label" for="form-checkout__securityCode">CVV</label>
            <div id="form-checkout__securityCode" class="mp-sdk-field"></div>
        </div>
    </div>

    <!-- Nome do titular -->
    <div class="mp-field-group">
        <label class="mp-label" for="form-checkout__cardholderName">Nome no Cartão</label>
        <input type="text" id="form-checkout__cardholderName" class="mp-input" placeholder="Nome como no cartão" autocomplete="cc-name" value="${nomeVal}" />
    </div>

    <!-- Banco emissor (preenchido automaticamente pelo SDK) -->
    <select id="form-checkout__issuer" class="mp-select" style="position:absolute;opacity:0;pointer-events:none;height:0;width:0;"></select>

    <!-- Parcelas (se Débito, campo oculto para atender o SDK mantendo 1x) -->
    <div class="mp-field-group" style="${isDebito ? 'display:none;' : ''}">
        <label class="mp-label" for="form-checkout__installments">Parcelas</label>
        <select id="form-checkout__installments" class="mp-select">
            <option value="1">1x à vista</option>
        </select>
    </div>

    <!-- CPF / Tipo de documento -->
    <div class="mp-field-row">
        <div class="mp-field-group" style="flex:0 0 110px;">
            <label class="mp-label" for="form-checkout__identificationType">Tipo Doc.</label>
            <select id="form-checkout__identificationType" class="mp-select"></select>
        </div>
        <div class="mp-field-group">
            <label class="mp-label" for="form-checkout__identificationNumber">CPF / Documento</label>
            <input type="text" id="form-checkout__identificationNumber" class="mp-input" placeholder="Número do documento" inputmode="numeric" value="${docVal}" />
        </div>
    </div>

    <!-- E-mail -->
    <div class="mp-field-group">
        <label class="mp-label" for="form-checkout__cardholderEmail">E-mail</label>
        <input type="email" id="form-checkout__cardholderEmail" class="mp-input" placeholder="E-mail" autocomplete="email" value="${emailVal}" />
    </div>

    <!-- Mensagem de erro -->
    <p id="mp-card-error" class="mp-error-msg" role="alert"></p>

    <!-- Botão submit -->
    <button id="btn-pagar-cartao" type="submit" class="mp-btn-pay">
        ${isDebito ? '💳 Pagar no Débito' : '💳 Pagar com Cartão'}
    </button>

</form>`;
}

let walletBrickInstance = null;

/**
 * Inicializa o componente oficial de Carteira Digital (Wallet Brick)
 * com suporte nativo a Google Pay, Apple Pay e 1-Clique Mercado Pago.
 *
 * @param {string} containerId - ID do container HTML
 * @param {string} preferenceId - ID da preferência gerada no backend com split
 * @param {Object} options - Callbacks opcionais (onReady, onSubmit, onError)
 */
export async function inicializarWalletBrick(containerId, preferenceId, options = {}) {
    if (typeof window.MercadoPago === 'undefined') {
        throw new Error('SDK Mercado Pago não carregado.');
    }
    const publicKey = window.GATEWAY_CONFIG?.mercadopago_public_key;
    if (!publicKey) {
        throw new Error('Mercado Pago Public Key não configurada.');
    }

    if (!mpInstance) {
        mpInstance = new window.MercadoPago(publicKey, { locale: 'pt-BR' });
    }

    const bricksBuilder = mpInstance.bricks();
    const container = document.getElementById(containerId);
    if (!container) return null;

    container.innerHTML = ''; // Limpa conteúdo anterior

    try {
        if (walletBrickInstance) {
            try { walletBrickInstance.unmount(); } catch (_) {}
            walletBrickInstance = null;
        }

        walletBrickInstance = await bricksBuilder.create('wallet', containerId, {
            initialization: {
                preferenceId: preferenceId,
                redirectMode: 'modal' // Abre modal integrado com autenticação biométrica
            },
            customization: {
                texts: {
                    action: 'pay',
                    valueProp: 'convenience_all'
                },
                visual: {
                    buttonBackground: 'black',
                    borderRadius: '12px'
                }
            },
            callbacks: {
                onReady: () => {
                    console.log('[Wallet Brick] ✅ Pronto para pagamento por aproximação/carteiras digitais');
                    if (options.onReady) options.onReady();
                },
                onSubmit: () => {
                    console.log('[Wallet Brick] ⚡ Iniciando pagamento com Carteira Digital...');
                    if (options.onSubmit) options.onSubmit();
                },
                onError: (error) => {
                    console.error('[Wallet Brick] ❌ Erro:', error);
                    if (options.onError) options.onError(error);
                }
            }
        });

        return walletBrickInstance;
    } catch (err) {
        console.error('[Wallet Brick] Falha ao renderizar:', err);
        throw err;
    }
}

/**
 * Destrói a instância do Wallet Brick
 */
export function destruirWalletBrick() {
    if (walletBrickInstance) {
        try { walletBrickInstance.unmount(); } catch (_) {}
        walletBrickInstance = null;
    }
}


