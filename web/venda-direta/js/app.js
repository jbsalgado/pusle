// app.js - Aplicação principal do VENDA DIRETA PWA
import { CONFIG, API_ENDPOINTS, carregarConfigLoja } from './config.js';
import { 
    getCarrinho, setCarrinho, adicionarAoCarrinho, removerDoCarrinho,
    aumentarQuantidadeItem, diminuirQuantidadeItem, calcularTotalCarrinho,
    calcularTotalItens, limparCarrinho, atualizarIndicadoresCarrinho,
    atualizarBadgeProduto
} from './cart.js';
import { carregarCarrinho, limparDadosLocaisPosSinc } from './storage.js';
import { finalizarPedido } from './order.js';
import { carregarFormasPagamento } from './payment.js';
import { validarCPF, maskCPF, maskPhone, formatarMoeda, verificarElementosCriticos } from './utils.js';
import { ELEMENTOS_CRITICOS } from './config.js';
import { mostrarModalPixEstatico } from './pix.js'; // Importação do novo módulo
import { verificarAutenticacao, getColaboradorData } from './auth.js'; // Importação do módulo de autenticação

// Variáveis Globais
let produtos = [];
let colaboradorAtual = null;
let formasPagamento = [];
let usuarioData = null;

// Inicialização
async function init() {
    try {
        console.log('[App] 🚀 Iniciando aplicação VENDA DIRETA...');
        
        // Verificar autenticação primeiro
        usuarioData = await verificarAutenticacao();
        if (!usuarioData) {
            console.error('[App] ❌ Falha na autenticação');
            return;
        }
        
        verificarElementosCriticos(ELEMENTOS_CRITICOS);
        popularOpcoesParcelas();
        await carregarConfigLoja();
        await registrarServiceWorker();
        await carregarCarrinhoInicial();
        await carregarProdutos();
        inicializarEventListeners();
        configurarListenerServiceWorker();
        atualizarBadgeCarrinho();
        
        console.log('[App] ✅ Aplicação inicializada!');
    } catch (error) {
        console.error('[App] ❌ Erro na inicialização:', error);
    }
}

// Service Worker Registration
async function registrarServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register(`${CONFIG.URL_BASE_WEB}/venda-direta/sw.js`);
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        if (confirm('Nova versão disponível! Deseja atualizar agora?')) {
                            newWorker.postMessage({ type: 'SKIP_WAITING' });
                            window.location.reload();
                        }
                    }
                });
            });
        } catch (error) {
            console.warn('[SW] ⚠️ Erro ao registrar Service Worker:', error);
        }
    }
}

function configurarListenerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', async (event) => {
            const { type, pedido, error } = event.data;
            if (type === 'SYNC_SUCCESS') {
                await limparDadosLocaisPosSinc();
                await carregarCarrinhoInicial();
                atualizarBadgeCarrinho();
                renderizarCarrinho();
                alert('Venda offline enviada com sucesso!');
                fecharModal('modal-cliente-pedido');
            } else if (type === 'SYNC_ERROR') {
                alert(`Erro ao enviar venda: ${error}`);
            }
        });
    }
}

// Carrinho
async function carregarCarrinhoInicial() {
    try {
        const carrinhoSalvo = await carregarCarrinho();
        setCarrinho(carrinhoSalvo);
    } catch (error) {
        setCarrinho([]);
    }
}

function atualizarBadgeCarrinho() {
    const totalItens = calcularTotalItens();
    const badge = document.getElementById('contador-carrinho');
    const btnCarrinho = document.getElementById('btn-abrir-carrinho');
    if (badge) {
        badge.textContent = totalItens;
        badge.classList.toggle('hidden', totalItens === 0);
    }
    if (btnCarrinho) btnCarrinho.disabled = totalItens === 0;
}

function renderizarCarrinho() {
    const container = document.getElementById('itens-carrinho');
    const totalElement = document.getElementById('valor-total-carrinho');
    const totalItensFooter = document.getElementById('total-itens-footer');
    const btnFinalizar = document.getElementById('btn-finalizar-pedido');
    
    const carrinho = getCarrinho();
    
    if (carrinho.length === 0) {
        if (container) container.innerHTML = '<p id="carrinho-vazio-msg" class="text-center text-gray-500 py-8">Seu carrinho está vazio</p>';
        if (btnFinalizar) btnFinalizar.disabled = true;
        if (totalElement) totalElement.textContent = 'R$ 0,00';
        if (totalItensFooter) totalItensFooter.textContent = '0';
        return;
    }
    
    if (btnFinalizar) btnFinalizar.disabled = false;
    
    container.innerHTML = carrinho.map((item, index) => {
        let urlImagem = 'https://dummyimage.com/100x100/cccccc/ffffff.png&text=Sem+Imagem';
        if (item.fotos && item.fotos.length > 0 && item.fotos[0].arquivo_path) {
            urlImagem = `${CONFIG.URL_BASE_WEB}/${item.fotos[0].arquivo_path}`;
        }
        const subtotal = item.preco_venda_sugerido * item.quantidade;
        return `
        <div class="cart-item">
            <button onclick="removerItem(${index})" class="cart-item-remove" title="Remover item">✖</button>
            <div class="cart-item-container">
                <img src="${urlImagem}" alt="${item.nome}" class="cart-item-image">
                <div class="cart-item-info">
                    <h3 class="cart-item-name">${item.nome}</h3>
                    <p class="cart-item-price">${formatarMoeda(item.preco_venda_sugerido)} un.</p>
                    <div class="cart-item-controls">
                        <button onclick="diminuirQtd('${item.id}')" class="qty-btn">−</button>
                        <span class="qty-value">${item.quantidade}</span>
                        <button onclick="aumentarQtd('${item.id}')" class="qty-btn">+</button>
                    </div>
                    <div class="cart-item-total">
                        <p class="cart-item-subtotal">Subtotal</p>
                        <p class="cart-item-total-price">${formatarMoeda(subtotal)}</p>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
    
    if (totalElement) totalElement.textContent = formatarMoeda(calcularTotalCarrinho());
    if (totalItensFooter) totalItensFooter.textContent = calcularTotalItens();
}

window.aumentarQtd = function(produtoId) {
    if (aumentarQuantidadeItem(produtoId)) { renderizarCarrinho(); atualizarBadgeCarrinho(); }
};
window.diminuirQtd = function(produtoId) {
    if (diminuirQuantidadeItem(produtoId)) { renderizarCarrinho(); atualizarBadgeCarrinho(); }
};
window.removerItem = function(index) {
    const produtoId = removerDoCarrinho(index);
    if (produtoId) { atualizarBadgeProduto(produtoId, false); renderizarCarrinho(); atualizarBadgeCarrinho(); }
};

// Produtos
async function carregarProdutos() {
    try {
        const url = `${API_ENDPOINTS.PRODUTO}?usuario_id=${CONFIG.ID_USUARIO_LOJA}`;
        const response = await fetch(url);
        if (!response.ok) throw new Error(`Erro ${response.status}`);
        produtos = await response.json();
        renderizarProdutos(produtos);
    } catch (error) {
        console.error('Erro ao carregar produtos:', error);
    }
}

function renderizarProdutos(listaProdutos) {
    const container = document.getElementById('catalogo-produtos');
    if (!container) return;
    
    if (listaProdutos.length === 0) {
        container.innerHTML = `<div class="col-span-full text-center py-16"><p>Nenhum produto disponível.</p></div>`;
        return;
    }
    
    container.innerHTML = listaProdutos.map(produto => {
        let urlImagem = 'https://dummyimage.com/300x200/cccccc/ffffff.png&text=Sem+Imagem';
        if (produto.fotos && produto.fotos.length > 0 && produto.fotos[0].arquivo_path) {
            urlImagem = `${CONFIG.URL_BASE_WEB}/${produto.fotos[0].arquivo_path}`;
        }
        return `
        <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl relative" data-produto-card="${produto.id}">
            <div class="badge-no-carrinho hidden absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10">✓ No Carrinho</div>
            <img src="${urlImagem}" alt="${produto.nome}" class="w-full h-48 object-cover">
            <div class="p-4">
                <h3 class="text-lg font-bold text-gray-800 mb-2">${produto.nome}</h3>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-2xl font-bold text-blue-600">${formatarMoeda(produto.preco_venda_sugerido)}</span>
                    <span class="text-xs ${produto.estoque_atual > 0 ? 'text-green-600' : 'text-red-600'} font-semibold">
                        ${produto.estoque_atual > 0 ? `${produto.estoque_atual} em estoque` : 'Sem estoque'}
                    </span>
                </div>
                <button onclick="abrirModalQuantidade('${produto.id}')" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg" ${produto.estoque_atual <= 0 ? 'disabled opacity-50' : ''}>
                    🛒 Adicionar
                </button>
            </div>
        </div>`;
    }).join('');
}

window.abrirModalQuantidade = function(produtoId) {
    const produto = produtos.find(p => p.id === produtoId);
    if (!produto) return;
    
    const inputQtd = document.getElementById('input-quantidade');
    document.getElementById('nome-produto-modal').textContent = produto.nome;
    document.getElementById('preco-produto-modal').textContent = formatarMoeda(produto.preco_venda_sugerido);
    inputQtd.value = 1;
    inputQtd.max = produto.estoque_atual;
    
    document.getElementById('btn-confirmar-adicionar').onclick = () => {
        const quantidade = parseInt(inputQtd.value, 10);
        if (quantidade > 0 && quantidade <= produto.estoque_atual) {
            const produtoComImagem = { ...produto, imagem: produto.fotos?.[0]?.arquivo_path ? `${CONFIG.URL_BASE_WEB}/${produto.fotos[0].arquivo_path}` : null };
            if (adicionarAoCarrinho(produtoComImagem, quantidade)) {
                atualizarBadgeProduto(produtoId, true);
                atualizarBadgeCarrinho();
                fecharModal('modal-quantidade');
            }
        } else {
            alert(`Quantidade inválida. Máximo: ${produto.estoque_atual}`);
        }
    };
    abrirModal('modal-quantidade');
};

window.abrirCarrinho = function() { renderizarCarrinho(); abrirModal('modal-carrinho'); };

function popularFormasPagamento(formas) {
    const select = document.getElementById('forma-pagamento');
    if (!select) return;
    select.innerHTML = '<option value="">Selecione o pagamento...</option>';
    if (formas && formas.length > 0) {
        select.disabled = false;
        formas.forEach(forma => select.options[select.options.length] = new Option(forma.nome, forma.id));
        formasPagamento = formas;
    } else {
        select.options[0] = new Option('Nenhuma forma de pgto.', '');
        select.disabled = true;
    }
}

window.abrirModalPedido = async function() {
    fecharModal('modal-carrinho');
    document.getElementById('form-cliente-pedido').reset();
    colaboradorAtual = null;
    document.getElementById('info-vendedor').classList.add('hidden');
    popularOpcoesParcelas();
    abrirModal('modal-cliente-pedido');
    
    // Preencher automaticamente CPF do vendedor se o usuário logado for vendedor
    preencherDadosVendedor();
    
    try {
        const formas = await carregarFormasPagamento(CONFIG.ID_USUARIO_LOJA);
        popularFormasPagamento(formas);
    } catch (error) {
        popularFormasPagamento([]);
    }
};

/**
 * Preenche automaticamente os dados do vendedor se o usuário logado for colaborador/vendedor
 */
function preencherDadosVendedor() {
    const colaborador = getColaboradorData();
    if (colaborador && colaborador.cpf) {
        const cpfInput = document.getElementById('vendedor_cpf_busca');
        if (cpfInput) {
            // Formata o CPF com máscara (formato: 000.000.000-00)
            const cpfLimpo = colaborador.cpf.replace(/[^\d]/g, '');
            const cpfFormatado = cpfLimpo.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            cpfInput.value = cpfFormatado;
            
            // Define o colaborador atual
            colaboradorAtual = colaborador;
            const colaboradorIdInput = document.getElementById('colaborador_vendedor_id');
            if (colaboradorIdInput) {
                colaboradorIdInput.value = colaborador.id;
            }
            
            // Mostra informações do vendedor
            const nomeVendedorInfo = document.getElementById('nome-vendedor-info');
            const infoVendedor = document.getElementById('info-vendedor');
            if (nomeVendedorInfo) {
                nomeVendedorInfo.textContent = colaborador.nome_completo;
            }
            if (infoVendedor) {
                infoVendedor.classList.remove('hidden');
            }
            
            console.log('[App] ✅ CPF do vendedor preenchido automaticamente:', cpfFormatado);
        }
    }
}

function popularOpcoesParcelas() {
    const selectParcelas = document.getElementById('numero-parcelas');
    if (!selectParcelas) return;
    selectParcelas.innerHTML = '<option value="1">À vista</option>';
    for (let i = 2; i <= 24; i++) {
        const option = document.createElement('option');
        option.value = i; option.textContent = `${i}x`;
        selectParcelas.appendChild(option);
    }
}

window.buscarVendedor = async function() {
    const cpfInput = document.getElementById('vendedor_cpf_busca');
    const cpf = cpfInput.value.replace(/[^\d]/g, '');
    if (!cpf) { colaboradorAtual = null; document.getElementById('info-vendedor').classList.add('hidden'); return; }
    
    try {
        const response = await fetch(`${API_ENDPOINTS.COLABORADOR_BUSCA_CPF}?cpf=${cpf}&usuario_id=${CONFIG.ID_USUARIO_LOJA}`);
        if (response.ok) {
            const data = await response.json();
            if (data.existe && data.colaborador) {
                colaboradorAtual = data.colaborador;
                document.getElementById('nome-vendedor-info').textContent = colaboradorAtual.nome_completo;
                document.getElementById('info-vendedor').classList.remove('hidden');
                document.getElementById('colaborador_vendedor_id').value = colaboradorAtual.id;
            } else { alert('Vendedor não encontrado'); }
        }
    } catch (error) { alert('Erro ao buscar vendedor'); }
};

// 🔥 FUNÇÃO PRINCIPAL DE VENDA COM PIX ESTÁTICO INTEGRADO 🔥
window.confirmarPedido = async function() {
    const formaPagamentoId = document.getElementById('forma-pagamento')?.value;
    if (!formaPagamentoId) { alert('Selecione uma forma de pagamento.'); return; }
    
    const btnConfirmar = document.getElementById('btn-confirmar-pedido');
    btnConfirmar.disabled = true;
    btnConfirmar.textContent = 'Processando...';
    
    try {
        const dadosPedido = {
            cliente_id: null,
            observacoes: document.getElementById('observacoes-pedido').value || null,
            colaborador_vendedor_id: colaboradorAtual?.id || null,
            forma_pagamento_id: formaPagamentoId,
            numero_parcelas: parseInt(document.getElementById('numero-parcelas')?.value || 1, 10),
            data_primeiro_pagamento: document.getElementById('data-primeiro-pagamento')?.value || null,
            intervalo_dias_parcelas: parseInt(document.getElementById('intervalo-dias')?.value || 30, 10)
        };
        
        const carrinho = getCarrinho();
        const resultado = await finalizarPedido(dadosPedido, carrinho);
        
        if (resultado.sucesso) {
            // Verifica se é PIX À VISTA para mostrar o modal estático
            const formaPagamentoSelecionada = formasPagamento.find(fp => fp.id == formaPagamentoId);
            const isPix = formaPagamentoSelecionada && formaPagamentoSelecionada.tipo === 'PIX'; // Ajuste conforme seu backend retorna "tipo"
            const isVista = dadosPedido.numero_parcelas === 1;

            if (isPix && isVista && !resultado.offline) {
                console.log('[App] 🟢 Venda PIX à vista detectada. Gerando QR Code Estático...');
                
                const valorTotal = calcularTotalCarrinho();
                
                // Gera TxID limpo: VendaDireta + DDMMYYYY + HHMM (apenas letras e números)
                const now = new Date();
                const dia = String(now.getDate()).padStart(2, '0');
                const mes = String(now.getMonth() + 1).padStart(2, '0');
                const ano = String(now.getFullYear());
                const hora = String(now.getHours()).padStart(2, '0');
                const minuto = String(now.getMinutes()).padStart(2, '0');
                // Formato: VendaDireta + DDMMYYYY + HHMM (ex: VendaDireta031220251430)
                const txId = `VendaDireta${dia}${mes}${ano}${hora}${minuto}`;

                // Abre o Modal PIX
                mostrarModalPixEstatico(valorTotal, txId);
                
                // Limpa carrinho pois a venda foi registrada
                limparCarrinho();
                await carregarCarrinhoInicial();
                atualizarBadgeCarrinho();
                fecharModal('modal-cliente-pedido');
                
                // Restaura botão
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = '✅ Confirmar Venda';
                return; // Encerra aqui para manter o modal PIX aberto
            }

            // Fluxo normal (dinheiro, cartão, boleto, offline)
            alert(resultado.mensagem || 'Venda realizada com sucesso!');
            if (!resultado.offline) {
                limparCarrinho();
                await carregarCarrinhoInicial();
                atualizarBadgeCarrinho();
            }
            fecharModal('modal-cliente-pedido');
        } else {
            alert(`Erro: ${resultado.mensagem}`);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert(`Erro ao finalizar venda: ${error.message}`);
    } finally {
        // Só reabilita se não tiver aberto o modal PIX (que reabilita antes do return)
        if (document.getElementById('modal-pix-estatico').classList.contains('hidden')) {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = '✅ Confirmar Venda';
        }
    }
};

function abrirModal(id) { document.getElementById(id)?.classList.remove('hidden'); document.getElementById(id)?.classList.add('flex'); }
function fecharModal(id) { document.getElementById(id)?.classList.add('hidden'); document.getElementById(id)?.classList.remove('flex'); }

window.abrirModal = abrirModal;
window.fecharModal = fecharModal;

function inicializarEventListeners() {
    document.getElementById('btn-abrir-carrinho')?.addEventListener('click', window.abrirCarrinho);
    document.getElementById('btn-finalizar-pedido')?.addEventListener('click', window.abrirModalPedido);
    document.querySelectorAll('[data-mask="cpf"]').forEach(i => i.addEventListener('input', e => maskCPF(e.target)));
    document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if (e.target === m) fecharModal(m.id); }));
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
export { init, carregarProdutos, abrirModal, fecharModal };