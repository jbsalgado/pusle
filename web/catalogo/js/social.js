/**
 * social.js - Módulo para integração com Redes Sociais (WhatsApp/Instagram)
 */

import { CONFIG } from './config.js';
import { formatarMoeda } from './utils.js';

// Estado local do módulo
const estado = {
    modoDivulgacao: false,
    produtosSelecionados: new Set(), // Armazena IDs dos produtos
    produtosDados: new Map() // Armazena dados completos dos produtos selecionados
};

/**
 * Inicializa o módulo social
 */
export function inicializarSocial() {
    console.log('[Social] 🚀 Inicializando módulo social...');
    
    // Injetar estilos CSS dinamicamente se necessário, ou garantir que classes existam
    // Configurar listeners de eventos globais
    configurarListeners();
    
    // Verificar se há IDs na URL para filtrar (Link Compartilhado)
    verificarLinkCompartilhado();
}

/**
 * Configura listeners para os botões da UI
 */
function configurarListeners() {
    const btnDivulgar = document.getElementById('btn-modo-divulgacao');
    if (btnDivulgar) {
        btnDivulgar.addEventListener('click', toggleModoDivulgacao);
    }

    const btnGerarStory = document.getElementById('btn-gerar-story');
    if (btnGerarStory) {
        btnGerarStory.addEventListener('click', gerarStory);
    }
    
    const btnCopiarLink = document.getElementById('btn-copiar-link-social');
    if (btnCopiarLink) {
        btnCopiarLink.addEventListener('click', copiarLinkDivulgacao);
    }
    
    const btnCancelar = document.getElementById('btn-cancelar-divulgacao');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', toggleModoDivulgacao);
    }
}

/**
 * Alterna entre modo normal e modo de divulgação
 */
export function toggleModoDivulgacao() {
    estado.modoDivulgacao = !estado.modoDivulgacao;
    const body = document.body;
    const barraAcao = document.getElementById('barra-acao-social');
    const containerProdutos = document.getElementById('catalogo-produtos');
    const btnDivulgar = document.getElementById('btn-modo-divulgacao');

    if (estado.modoDivulgacao) {
        body.classList.add('modo-social');
        if (barraAcao) barraAcao.classList.remove('translate-y-full');
        if (btnDivulgar) {
            btnDivulgar.classList.add('bg-purple-600', 'text-white');
            btnDivulgar.classList.remove('text-gray-600', 'hover:bg-gray-100');
            btnDivulgar.innerHTML = '<span class="font-bold">Terminar Seleção</span>';
        }
        
        // Limpar seleção anterior ao entrar? Ou manter? Vamos manter por enquanto.
        atualizarUiSelecao();
    } else {
        body.classList.remove('modo-social');
        if (barraAcao) barraAcao.classList.add('translate-y-full');
        if (btnDivulgar) {
            btnDivulgar.classList.remove('bg-purple-600', 'text-white');
            btnDivulgar.classList.add('text-gray-600', 'hover:bg-gray-100');
            btnDivulgar.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
            `;
        }
    }
}

/**
 * Adiciona ou remove produto da seleção
 * @param {Object} produto - Objeto do produto completo
 */
export function toggleSelecaoProduto(produto) {
    if (!estado.modoDivulgacao) return;

    if (estado.produtosSelecionados.has(produto.id)) {
        estado.produtosSelecionados.delete(produto.id);
        estado.produtosDados.delete(produto.id);
    } else {
        // Limite de produtos para Story (ex: 4 para ficar bonito)
        if (estado.produtosSelecionados.size >= 4) {
            alert('Selecione no máximo 4 produtos para gerar um Story otimizado.');
            return;
        }
        estado.produtosSelecionados.add(produto.id);
        estado.produtosDados.set(produto.id, produto);
    }

    atualizarUiSelecao();
}

/**
 * Atualiza a UI baseada na seleção atual
 */
function atualizarUiSelecao() {
    // Atualizar contador
    const contador = document.getElementById('contador-selecao-social');
    if (contador) {
        contador.textContent = estado.produtosSelecionados.size;
    }

    // Atualizar visual dos cards
    document.querySelectorAll('[data-produto-card]').forEach(card => {
        const id = card.getAttribute('data-produto-card'); // Agora é string UUID
        const check = card.querySelector('.social-check');
        
        // Converter ID do card para verificar no Set (garantir tipos compatíveis)
        // O Set armazena o ID conforme vem do objeto produto (geralmente string UUID ou int)
        // Se o Set tiver "123" e o atributo for "123", ok.
        
        // Verifica se o ID está no Set. Como IDs podem ser numéricos ou strings, 
        // a comparação deve ser cuidadosa se houver mistura, mas no PHP geralmente é consistente.
        // Vamos assumir que ambos são strings para comparação segura.
        let isSelected = false;
        
        // Itera sobre o Set para comparar, caso haja divergência de tipos (num vs string)
        for (let selectedId of estado.produtosSelecionados) {
            if (String(selectedId) === String(id)) {
                isSelected = true;
                break;
            }
        }

        if (isSelected) {
            card.classList.add('ring-4', 'ring-purple-500', 'ring-opacity-50');
            if (check) check.classList.remove('hidden');
        } else {
            card.classList.remove('ring-4', 'ring-purple-500', 'ring-opacity-50');
            if (check) check.classList.add('hidden');
        }
    });
    
    // Habilitar/desabilitar botões
    const temSelecao = estado.produtosSelecionados.size > 0;
    const btnGerar = document.getElementById('btn-gerar-story');
    const btnLink = document.getElementById('btn-copiar-link-social');
    
    if (btnGerar) btnGerar.disabled = !temSelecao;
    if (btnLink) btnLink.disabled = !temSelecao;
}


/**
 * Verifica se a URL atual contém parâmetros de compartilhamento
 */
function verificarLinkCompartilhado() {
    const params = new URLSearchParams(window.location.search);
    const ids = params.get('ids');
    
    if (ids) {
        console.log('[Social] 🔗 Link compartilhado detectado com IDs:', ids);
        // Ativar modo filtro global
        window.FILTRO_IDS_SOCIAL = ids.split(',').map(id => id.trim());
        
        // Adicionar aviso visual
        const header = document.querySelector('header nav');
        if (header) {
            const aviso = document.createElement('div');
            aviso.className = 'bg-purple-100 text-purple-800 text-center text-sm py-2 px-4 mb-2 rounded-lg border border-purple-200';
            aviso.innerHTML = `
                Mostrando <strong>seleção especial</strong>. 
                <button onclick="window.limparFiltroSocial()" class="underline font-bold ml-1">Ver todos</button>
            `;
            header.insertAdjacentElement('afterend', aviso);
        }
    }
}

/**
 * Limpa o filtro de IDs da URL
 */
window.limparFiltroSocial = function() {
    const url = new URL(window.location.href);
    url.searchParams.delete('ids');
    window.history.pushState({}, '', url);
    window.location.reload();
}

/**
 * Gera a imagem do Story
 */
async function gerarStory() {
    if (estado.produtosSelecionados.size === 0) return;

    // Mostrar loading
    const btn = document.getElementById('btn-gerar-story');
    const textoOriginal = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="animate-spin mr-2">⏳</span> Gerando...';

    try {
        // Criar container temporário para o Story
        const container = document.createElement('div');
        container.id = 'story-container-temp';
        container.style.position = 'fixed'; // Fixed para não afetar layout mas ser renderizável
        container.style.top = '0';
        container.style.left = '-9999px'; // Fora da tela
        container.style.width = '1080px';
        container.style.height = '1920px';
        container.style.background = 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)';
        container.style.zIndex = '-1';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.fontFamily = 'Inter, sans-serif'; 
        
        // Buscar logo
        const logoSrc = document.getElementById('logo-empresa')?.src || '';
        
        // Construir HTML do Story
        const produtosArray = Array.from(estado.produtosDados.values());
        
        let gridTemplate = '';
        if (produtosArray.length === 1) gridTemplate = 'grid-cols-1 grid-rows-1';
        else if (produtosArray.length === 2) gridTemplate = 'grid-cols-1 grid-rows-2';
        else if (produtosArray.length === 3) gridTemplate = 'grid-cols-1 grid-rows-3'; // Ajustar layout
        else gridTemplate = 'grid-cols-2 grid-rows-2';

        const produtosHtml = produtosArray.map(p => {
             // Tratamento da imagem do produto
             let imgUrl = 'https://dummyimage.com/600x600/cccccc/ffffff.png&text=Sem+Foto';
             if (p.fotos && p.fotos.length > 0) {
                 // Resolver caminho da imagem
                 const path = p.fotos[0].arquivo_path.replace(/^\//, '');
                 const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
                 imgUrl = `${baseUrl}/${path}`;
             }

             return `
                <div class="bg-white p-8 rounded-3xl shadow-xl flex flex-col items-center justify-center h-full border border-gray-100 relative overflow-hidden">
                    <img src="${imgUrl}" crossorigin="anonymous" class="w-64 h-64 object-contain mb-6" style="max-height: 60%;">
                    <h2 class="text-4xl font-bold text-gray-800 text-center mb-4 leading-tight line-clamp-2">${p.nome}</h2>
                    <div class="bg-blue-600 text-white text-5xl font-extrabold px-8 py-4 rounded-full shadow-lg transform scale-100">
                        ${formatarMoeda(p.preco_venda_sugerido)}
                    </div>
                </div>
             `;
        }).join('');

        container.innerHTML = `
            <div class="p-12 h-full flex flex-col relative bg-gradient-to-br from-gray-50 to-gray-200">
                <!-- Header -->
                <div class="flex flex-col items-center mb-10 pt-4">
                     ${logoSrc ? `<img src="${logoSrc}" crossorigin="anonymous" class="h-32 w-auto object-contain mb-4 drop-shadow-md">` : '<h1 class="text-5xl font-black text-blue-900 tracking-tighter uppercase">Ofertas</h1>'}
                     <div class="h-1.5 w-32 bg-blue-500 rounded-full"></div>
                </div>

                <!-- Grid Produtos -->
                <div class="grid ${gridTemplate} gap-6 flex-1 w-full px-4">
                    ${produtosArray.map(p => {
                        // Tratamento da imagem
                        let imgUrl = 'https://dummyimage.com/600x600/cccccc/ffffff.png&text=Sem+Foto';
                        if (p.fotos && p.fotos.length > 0) {
                            const path = p.fotos[0].arquivo_path.replace(/^\//, '');
                            const baseUrl = CONFIG.URL_BASE_WEB.replace(/\/$/, '');
                            imgUrl = `${baseUrl}/${path}`;
                        }
                        
                        return `
                        <div class="bg-white p-5 rounded-[2rem] shadow-xl flex flex-col h-full border border-gray-100 overflow-hidden relative group">
                            <!-- Área da Imagem com fundo suave -->
                            <div class="h-[55%] w-full bg-gray-50 rounded-2xl flex items-center justify-center p-6 mb-4 relative overflow-hidden">
                                <img src="${imgUrl}" crossorigin="anonymous" class="w-full h-full object-contain transform transition-transform duration-300">
                            </div>
                            
                            <!-- Informações -->
                            <div class="flex-1 flex flex-col items-center text-center justify-between py-2">
                                <h2 class="text-xl font-bold text-gray-800 leading-tight line-clamp-3 px-1 mb-1 w-full h-20 flex items-center justify-center">
                                    ${p.nome}
                                </h2>
                                
                                <div class="w-full mt-1">
                                    <div class="inline-block bg-blue-600 text-white text-3xl font-bold px-6 py-2 rounded-xl shadow-md border-b-4 border-blue-800">
                                        ${formatarMoeda(p.preco_venda_sugerido)}
                                    </div>
                                    ${p.estoque_atual > 0 ? 
                                        `<div class="text-green-600 font-medium text-sm mt-2 flex items-center justify-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Disponível
                                         </div>` : ''}
                                </div>
                            </div>
                        </div>
                        `;
                    }).join('')}
                </div>

                <!-- Footer Compacto -->
                <div class="mt-10 mb-6 mx-4">
                    <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-lg border border-white/50 flex items-center justify-between gap-6">
                        <div class="flex-1">
                            <p class="text-3xl font-bold text-gray-800">Gostou?</p>
                            <p class="text-xl text-blue-600 font-medium">Peça pelo Link na Bio</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(container);

        // Aguardar carregamento das imagens (importante para html2canvas)
        // Check simples de tempo por segurança, mas idealmente verificaria 'load' das imagens
        await new Promise(resolve => setTimeout(resolve, 1500)); 

        // Gerar Canvas
        const canvas = await html2canvas(container, {
            useCORS: true, // Permitir imagens externas se configurado CORS corretamente
            allowTaint: true,
            scale: 1, // 1080p nativo
            backgroundColor: null
        });

        // Remover container temporário
        document.body.removeChild(container);

        // Mostrar no Modal de Preview
        const imgData = canvas.toDataURL('image/png');
        mostrarModalPreview(imgData);

    } catch (error) {
        console.error('[Social] Erro ao gerar story:', error);
        alert('Erro ao gerar imagem. Verifique se as imagens dos produtos carregaram corretamente.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = textoOriginal;
    }
}

/**
 * Mostra modal com preview da imagem gerada
 */
function mostrarModalPreview(imgData) {
    // Reutilizar modal de comprovante modificando o conteúdo ou criar um novo
    // Vamos criar/usar um modal específico injetado dinamicamente para simplificar
    
    let modal = document.getElementById('modal-social-preview');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modal-social-preview';
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-sm w-full max-h-[90vh] flex flex-col overflow-hidden">
                    <div class="p-4 border-b flex justify-between items-center">
                        <h3 class="font-bold text-lg">Preview do Story</h3>
                        <button onclick="document.getElementById('modal-social-preview').classList.add('hidden')" class="text-gray-500 text-2xl">&times;</button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 bg-gray-100 flex justify-center">
                        <img id="img-social-preview" src="" class="max-w-full shadow-lg rounded-lg h-auto" style="max-height: 60vh;">
                    </div>
                    <div class="p-4 border-t bg-gray-50 grid grid-cols-2 gap-3">
                         <button id="btn-social-baixar" class="bg-gray-800 text-white py-3 rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-black">
                            📥 Baixar
                         </button>
                         <button id="btn-social-share" class="bg-green-600 text-white py-3 rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-green-700">
                            📲 Postar
                         </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    const img = modal.querySelector('#img-social-preview');
    img.src = imgData;
    
    const btnBaixar = modal.querySelector('#btn-social-baixar');
    btnBaixar.onclick = () => {
        const link = document.createElement('a');
        link.download = `story-catalogo-${Date.now()}.png`;
        link.href = imgData;
        link.click();
    };
    
    const btnShare = modal.querySelector('#btn-social-share');
    btnShare.onclick = async () => {
        try {
            const blob = await (await fetch(imgData)).blob();
            const file = new File([blob], 'story.png', { type: 'image/png' });
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({
                    files: [file],
                    title: 'Confira nossas ofertas!',
                    text: 'Ofertas especiais do catálogo'
                });
            } else {
                alert('Seu navegador não suporta compartilhamento direto de imagem. Use a opção Baixar.');
            }
        } catch (e) {
            console.error('Erro ao compartilhar:', e);
        }
    };
    
    modal.classList.remove('hidden');
}

/**
 * Gera e copia link com IDs selecionados
 */
function copiarLinkDivulgacao() {
    if (estado.produtosSelecionados.size === 0) return;
    
    const ids = Array.from(estado.produtosSelecionados).join(',');
    const urlBase = window.location.href.split('?')[0];
    const link = `${urlBase}?ids=${ids}`;
    
    navigator.clipboard.writeText(link).then(() => {
        alert('Link copiado! Envie para seus clientes.');
    }).catch(() => {
        prompt('Copie o link abaixo:', link);
    });
}
