// products.js - Gerenciamento de produtos e catálogo

import { API_ENDPOINTS, CONFIG } from './config.js';
import { produtoEstaNoCarrinho } from './cart.js';

let idUsuarioLoja = null;

/**
 * Obtém ID da loja
 */
export function getIdUsuarioLoja() {
    return idUsuarioLoja;
}

/**
 * Define ID da loja
 */
export function setIdUsuarioLoja(id) {
    idUsuarioLoja = id;
}

/**
 * Carrega produtos da API
 */
export async function carregarProdutos(catalogoContainer) {
    if (!catalogoContainer) {
        console.error('[Products] Container do catálogo não encontrado');
        return;
    }

    try {
        const response = await fetch(API_ENDPOINTS.PRODUTO, { cache: 'no-cache' });
        if (!response.ok) throw new Error(`Erro: ${response.statusText}`);

        const data = await response.json();
        const produtos = data.items || data;

        if (!produtos || produtos.length === 0) {
            catalogoContainer.innerHTML = '<p class="col-span-full text-center text-gray-500">Nenhum produto disponível.</p>';
            return;
        }

        // Captura ID da loja do primeiro produto
        if (produtos[0] && produtos[0].usuario_id) {
            idUsuarioLoja = produtos[0].usuario_id;
            console.log('[Products] ID da loja:', idUsuarioLoja);
        }

        renderizarProdutos(produtos, catalogoContainer);
    } catch (error) {
        console.error('[Products] Erro ao carregar produtos:', error);
        catalogoContainer.innerHTML = '<p class="col-span-full text-center text-red-600">Erro ao carregar produtos.</p>';
    }
}

/**
 * Renderiza produtos no catálogo
 */
function renderizarProdutos(produtos, container) {
    container.innerHTML = '';
    
    produtos.forEach(produto => {
        const card = criarCardProduto(produto);
        container.appendChild(card);
    });
}

/**
 * Cria card de produto
 */
function criarCardProduto(produto) {
    let urlImagem = 'https://via.placeholder.com/300x300.png?text=Sem+Foto';
    if (produto.fotos && produto.fotos.length > 0 && produto.fotos[0].arquivo_path) {
        urlImagem = `${CONFIG.URL_BASE_WEB}/${produto.fotos[0].arquivo_path}`;
    }

    const estoque = parseInt(produto.estoque_atual || 0);
    const temEstoque = estoque > 0;
    const estaNoCarrinho = produtoEstaNoCarrinho(produto.id);

    const card = document.createElement('div');
    card.className = 'bg-white rounded-lg shadow-md overflow-hidden flex flex-col relative';
    card.setAttribute('data-produto-card', produto.id);
    
    card.innerHTML = `
        <div class="badge-no-carrinho absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg flex items-center gap-1 z-10 ${estaNoCarrinho ? '' : 'hidden'}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            No Carrinho
        </div>

        ${!temEstoque ? `
        <div class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg z-10">
            Sem Estoque
        </div>
        ` : ''}

        <div class="h-48 w-full overflow-hidden bg-gray-200 ${!temEstoque ? 'opacity-60' : ''}">
            <img src="${urlImagem}" alt="${produto.nome || 'Produto'}" class="w-full h-full object-cover" onerror="this.src='https://via.placeholder.com/300x300.png?text=Erro';">
        </div>
        <div class="p-4 flex flex-col flex-grow">
            <h3 class="text-lg font-semibold text-gray-800 truncate">${produto.nome || 'Produto'}</h3>
            <p class="text-sm text-gray-500 mb-2 truncate">${produto.descricao || 'Sem descrição'}</p>
            
            <p class="text-xs ${temEstoque ? 'text-green-600' : 'text-red-600'} mb-2">
                ${temEstoque ? `${estoque} unidade(s) disponível` : 'Indisponível'}
            </p>
            
            <p class="text-2xl font-bold text-blue-600 mb-4 mt-auto">R$ ${parseFloat(produto.preco_venda_sugerido || 0).toFixed(2)}</p>
            
            <div class="flex items-center gap-2">
                <input 
                    type="number" 
                    id="qty-produto-${produto.id}" 
                    class="w-16 p-2 border border-gray-300 rounded-lg text-center ${!temEstoque ? 'bg-gray-100 cursor-not-allowed' : ''}" 
                    value="1" 
                    min="1"
                    max="${estoque}"
                    aria-label="Quantidade"
                    ${!temEstoque ? 'disabled' : ''}
                >
                <button 
                    data-id="${produto.id}" 
                    data-nome="${produto.nome || 'Produto'}" 
                    data-preco="${produto.preco_venda_sugerido || 0}" 
                    data-img="${urlImagem}" 
                    data-estoque="${estoque}"
                    class="btn-adicionar-carrinho w-full p-2 rounded-lg font-semibold transition-colors
                        ${!temEstoque ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-blue-500 text-white hover:bg-blue-600'}"
                    ${!temEstoque ? 'disabled' : ''}
                >
                    ${!temEstoque ? 'Indisponível' : 'Adicionar'}
                </button>
            </div>
        </div>
    `;
    
    return card;
}