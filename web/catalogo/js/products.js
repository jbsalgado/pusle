// products.js - Gerenciamento de produtos e catálogo

import { API_ENDPOINTS, CONFIG } from './config.js';
import { produtoEstaNoCarrinho } from './cart.js';
import { abrirGaleria, produtoTemMultiplasFotos, contarFotosProduto } from './gallery.js';

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
 * Carrega produtos da API com suporte a paginação real (carrega apenas uma página por vez)
 * @param {HTMLElement} catalogoContainer - Container onde os produtos serão renderizados
 * @param {number} pagina - Número da página a carregar (padrão: 1)
 * @param {boolean} forcarRecarregar - Se true, ignora cache e recarrega
 */
export async function carregarProdutos(catalogoContainer, pagina = 1, forcarRecarregar = false) {
    if (!catalogoContainer) {
        console.error('[Products] Container do catálogo não encontrado');
        return;
    }

    try {
        // Usar ID da loja do CONFIG (detectado pelo path)
        idUsuarioLoja = CONFIG.ID_USUARIO_LOJA;
        console.log('[Products] Carregando produtos para loja:', idUsuarioLoja, '(página', pagina, ')');
        
        // Filtrar produtos por usuario_id com paginação (20 por página - padrão do backend)
        const url = `${API_ENDPOINTS.PRODUTO}?usuario_id=${idUsuarioLoja}&page=${pagina}&per-page=20`;
        const response = await fetch(url, { cache: 'no-cache' });
        
        if (!response.ok) throw new Error(`Erro: ${response.statusText}`);

        const data = await response.json();
        
        // A API do Yii2 retorna um objeto com items, _links e _meta quando usa ActiveDataProvider
        let produtosPagina = [];
        let metadados = null;
        
        if (data.items && Array.isArray(data.items)) {
            // Formato paginado do Yii2
            produtosPagina = data.items;
            metadados = {
                totalCount: data._meta?.totalCount || 0,
                pageCount: data._meta?.pageCount || 1,
                currentPage: data._meta?.currentPage || pagina,
                perPage: data._meta?.perPage || 20
            };
        } else if (Array.isArray(data)) {
            // Formato direto (array) - fallback
            produtosPagina = data;
            metadados = {
                totalCount: data.length,
                pageCount: 1,
                currentPage: 1,
                perPage: data.length
            };
        } else {
            console.warn('[Products] ⚠️ Formato de resposta inesperado:', data);
            produtosPagina = [];
            metadados = {
                totalCount: 0,
                pageCount: 1,
                currentPage: 1,
                perPage: 20
            };
        }

        if (!produtosPagina || produtosPagina.length === 0) {
            if (pagina === 1) {
                catalogoContainer.innerHTML = '<p class="col-span-full text-center text-gray-500">Nenhum produto disponível para esta loja.</p>';
                console.log('[Products] Nenhum produto encontrado para esta loja');
            }
            return;
        }

        console.log(`[Products] ✅ Página ${pagina} carregada: ${produtosPagina.length} produto(s) de ${metadados.totalCount} total`);
        
        // Limpa container e renderiza apenas produtos da página atual
        catalogoContainer.innerHTML = '';
        renderizarProdutos(produtosPagina, catalogoContainer, false);
        
        // Expõe metadados globalmente para controles de paginação
        window.paginacaoMetadados = metadados;
        
    } catch (error) {
        console.error('[Products] Erro ao carregar produtos:', error);
        catalogoContainer.innerHTML = '<p class="col-span-full text-center text-red-600">Erro ao carregar produtos.</p>';
    }
}

/**
 * Renderiza produtos no catálogo
 * @param {Array} produtos - Array de produtos a renderizar
 * @param {HTMLElement} container - Container onde os produtos serão renderizados
 * @param {boolean} limparAntes - Se true, limpa o container antes de adicionar (padrão: false para paginação)
 */
function renderizarProdutos(produtos, container, limparAntes = false) {
    if (limparAntes) {
        container.innerHTML = '';
    }
    
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
        const arquivoPath = produto.fotos[0].arquivo_path.replace(/^\//, '');
        urlImagem = `${CONFIG.URL_BASE_WEB}/${arquivoPath}`;
    }

    const estoque = parseInt(produto.estoque_atual || 0);
    const temEstoque = estoque > 0;
    const estaNoCarrinho = produtoEstaNoCarrinho(produto.id);
    const temMultiplasFotos = produtoTemMultiplasFotos(produto);
    const totalFotos = contarFotosProduto(produto);

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

        ${temMultiplasFotos ? `
        <div class="absolute bottom-2 left-2 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg flex items-center gap-1 z-10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
            </svg>
            ${totalFotos} fotos
        </div>
        ` : ''}

        <div class="h-48 w-full overflow-hidden bg-gray-200 ${!temEstoque ? 'opacity-60' : ''} relative group cursor-pointer produto-imagem-container" data-produto-id="${produto.id}">
            <img src="${urlImagem}" alt="${produto.nome || 'Produto'}" class="w-full h-full object-cover transition-transform group-hover:scale-105" onerror="this.src='https://via.placeholder.com/300x300.png?text=Erro';">
            ${temMultiplasFotos ? `
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white drop-shadow-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                    </svg>
                </div>
            </div>
            ` : ''}
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
    
    // Adicionar evento de clique na imagem para abrir galeria
    const imagemContainer = card.querySelector('.produto-imagem-container');
    if (imagemContainer && produto.fotos && produto.fotos.length > 0) {
        imagemContainer.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            abrirGaleria(produto.fotos, 0, produto.nome || 'Produto');
        });
    }
    
    return card;
}