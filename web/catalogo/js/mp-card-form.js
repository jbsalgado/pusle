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

/**
 * Inicializa o CardForm do Mercado Pago dentro de um elemento HTML.
 *
 * @param {string}   containerId        - ID do elemento onde o form será montado
 * @param {number}   valorTotal         - Valor total da compra (para cálculo de parcelas)
 * @param {Function} onTokenGenerated   - Callback chamado quando o token estiver pronto
 *                                        Recebe: (token, installments, paymentMethodId, issuerId)
 */
export async function inicializarCardForm(containerId, valorTotal, onTokenGenerated) {
    _onTokenGenerated = onTokenGenerated;

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
                    console.log('[MP CardForm] ✅ Formulário montado com sucesso');
                    // Dispara evento input nos campos preenchidos para sincronizar validação do SDK
                    ['form-checkout__cardholderName', 'form-checkout__identificationNumber', 'form-checkout__cardholderEmail'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el && el.value) {
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.dispatchEvent(new Event('change', { bubbles: true }));
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
                console.error('[MP CardForm] Erros de validação:', errors);
                const submitBtn = document.getElementById('btn-pagar-cartao');
                if (submitBtn && !submitBtn.textContent.includes('Processando')) {
                    submitBtn.disabled = false;
                }
            },
        },
    });

    return cardFormInstance;
}

/**
 * Manipula o submit do CardForm: gera token e chama o callback.
 */
async function _handleCardFormSubmit() {
    const submitBtn = document.getElementById('btn-pagar-cartao');
    const errorEl   = document.getElementById('mp-card-error');

    if (submitBtn) {
        submitBtn.disabled    = true;
        submitBtn.textContent = '🔄 Processando...';
    }
    if (errorEl) errorEl.textContent = '';

    try {
        const formData = cardFormInstance.getCardFormData();

        const token           = formData.token;
        const installments    = parseInt(formData.installments, 10) || 1;
        const paymentMethodId = formData.paymentMethodId;   // ex: 'visa', 'master'
        const issuerId        = formData.issuerId;

        if (!token) {
            throw new Error('Não foi possível gerar o token do cartão. Verifique os dados informados.');
        }

        // Salva no window para que gateway-pagamento.js consuma
        window.mpCardToken    = token;
        window.mpInstallments = installments;
        window.mpPaymentMethodId = paymentMethodId;
        window.mpIssuerId     = issuerId;

        console.log('[MP CardForm] ✅ Token gerado:', token, '| Parcelas:', installments, '| Bandeira:', paymentMethodId);

        if (typeof _onTokenGenerated === 'function') {
            await _onTokenGenerated(token, installments, paymentMethodId, issuerId);
        }
    } catch (error) {
        console.error('[MP CardForm] ❌ Erro:', error);
        if (errorEl) errorEl.textContent = error.message || 'Erro ao processar cartão. Tente novamente.';
    } finally {
        if (submitBtn) {
            submitBtn.disabled    = false;
            submitBtn.textContent = '💳 Pagar com Cartão';
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
 * @param {Object|null} cliente - Dados do cliente para pré-preenchimento
 */
export function gerarHtmlFormCartao(cliente = null) {
    const nomeVal = (cliente?.nome_completo || cliente?.nome || '').trim();
    const docVal = (cliente?.cpf_cnpj || cliente?.cpf || '').replace(/\D/g, '');
    const emailVal = (cliente?.email || '').trim();

    return `
<form id="form-checkout-mp-cartao" class="mp-card-form-container">

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

    <!-- Parcelas -->
    <div class="mp-field-group">
        <label class="mp-label" for="form-checkout__installments">Parcelas</label>
        <select id="form-checkout__installments" class="mp-select">
            <option value="">Selecione as parcelas</option>
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
        💳 Pagar com Cartão
    </button>

</form>`;
}
