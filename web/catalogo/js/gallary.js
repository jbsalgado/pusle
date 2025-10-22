// gallery.js - Gerenciamento de galeria de fotos dos produtos

import { CONFIG } from './config.js';

let galeriaAtual = [];
let indiceFotoAtual = 0;

/**
 * Inicializa a galeria modal
 */
export function inicializarGaleria() {
    const modalGaleria = document.getElementById('modal-galeria');
    if (!modalGaleria) {
        console.warn('[Gallery] Modal da galeria não encontrado no DOM');
        return;
    }

    // Botão fechar
    const btnFechar = document.getElementById('btn-fechar-galeria');
    if (btnFechar) {
        btnFechar.addEventListener('click', fecharGaleria);
    }

    // Navegação
    const btnAnterior = document.getElementById('btn-foto-anterior');
    const btnProxima = document.getElementById('btn-foto-proxima');
    
    if (btnAnterior) {
        btnAnterior.addEventListener('click', mostrarFotoAnterior);
    }
    
    if (btnProxima) {
        btnProxima.addEventListener('click', mostrarFotoProxima);
    }

    // Fechar ao clicar no fundo escuro
    modalGaleria.addEventListener('click', (e) => {
        if (e.target === modalGaleria) {
            fecharGaleria();
        }
    });

    // Navegação por teclado
    document.addEventListener('keydown', (e) => {
        if (!modalGaleria.classList.contains('hidden')) {
            if (e.key === 'Escape') {
                fecharGaleria();
            } else if (e.key === 'ArrowLeft') {
                mostrarFotoAnterior();
            } else if (e.key === 'ArrowRight') {
                mostrarFotoProxima();
            }
        }
    });

    console.log('[Gallery] Galeria inicializada com sucesso');
}

/**
 * Abre a galeria com as fotos do produto
 * @param {Array} fotos - Array de fotos do produto
 * @param {number} indiceInicial - Índice da foto inicial (padrão: 0)
 * @param {string} nomeProduto - Nome do produto para exibir
 */
export function abrirGaleria(fotos, indiceInicial = 0, nomeProduto = '') {
    if (!fotos || fotos.length === 0) {
        console.warn('[Gallery] Nenhuma foto para exibir');
        return;
    }

    galeriaAtual = fotos;
    indiceFotoAtual = indiceInicial;

    const modalGaleria = document.getElementById('modal-galeria');
    if (!modalGaleria) {
        console.error('[Gallery] Modal não encontrado');
        return;
    }

    // Atualizar nome do produto
    const tituloProduto = document.getElementById('galeria-titulo-produto');
    if (tituloProduto) {
        tituloProduto.textContent = nomeProduto;
    }

    // Exibir foto atual
    atualizarFotoExibida();

    // Mostrar modal
    modalGaleria.classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevenir scroll do body

    console.log(`[Gallery] Galeria aberta com ${fotos.length} foto(s)`);
}

/**
 * Fecha a galeria
 */
export function fecharGaleria() {
    const modalGaleria = document.getElementById('modal-galeria');
    if (modalGaleria) {
        modalGaleria.classList.add('hidden');
        document.body.style.overflow = ''; // Restaurar scroll
    }

    galeriaAtual = [];
    indiceFotoAtual = 0;

    console.log('[Gallery] Galeria fechada');
}

/**
 * Mostra a foto anterior
 */
function mostrarFotoAnterior() {
    if (galeriaAtual.length === 0) return;

    indiceFotoAtual = (indiceFotoAtual - 1 + galeriaAtual.length) % galeriaAtual.length;
    atualizarFotoExibida();
}

/**
 * Mostra a próxima foto
 */
function mostrarFotoProxima() {
    if (galeriaAtual.length === 0) return;

    indiceFotoAtual = (indiceFotoAtual + 1) % galeriaAtual.length;
    atualizarFotoExibida();
}

/**
 * Atualiza a foto exibida na galeria
 */
function atualizarFotoExibida() {
    if (galeriaAtual.length === 0) return;

    const fotoAtual = galeriaAtual[indiceFotoAtual];
    const urlImagem = fotoAtual.arquivo_path 
        ? `${CONFIG.URL_BASE_WEB}/${fotoAtual.arquivo_path}`
        : 'https://via.placeholder.com/800x800.png?text=Sem+Foto';

    // Atualizar imagem
    const imgGaleria = document.getElementById('galeria-imagem');
    if (imgGaleria) {
        imgGaleria.src = urlImagem;
        imgGaleria.alt = `Foto ${indiceFotoAtual + 1}`;
    }

    // Atualizar contador
    const contador = document.getElementById('galeria-contador');
    if (contador) {
        contador.textContent = `${indiceFotoAtual + 1} / ${galeriaAtual.length}`;
    }

    // Atualizar visibilidade dos botões de navegação
    const btnAnterior = document.getElementById('btn-foto-anterior');
    const btnProxima = document.getElementById('btn-foto-proxima');

    if (galeriaAtual.length <= 1) {
        // Se só tem uma foto, esconder botões de navegação
        if (btnAnterior) btnAnterior.classList.add('hidden');
        if (btnProxima) btnProxima.classList.add('hidden');
    } else {
        if (btnAnterior) btnAnterior.classList.remove('hidden');
        if (btnProxima) btnProxima.classList.remove('hidden');
    }

    // Atualizar miniaturas se existirem
    atualizarMiniaturas();
}

/**
 * Atualiza as miniaturas (thumbnails) na galeria
 */
function atualizarMiniaturas() {
    const containerMiniaturas = document.getElementById('galeria-miniaturas');
    if (!containerMiniaturas || galeriaAtual.length <= 1) {
        if (containerMiniaturas) containerMiniaturas.innerHTML = '';
        return;
    }

    containerMiniaturas.innerHTML = '';

    galeriaAtual.forEach((foto, index) => {
        const urlMiniatura = foto.arquivo_path 
            ? `${CONFIG.URL_BASE_WEB}/${foto.arquivo_path}`
            : 'https://via.placeholder.com/80x80.png?text=Sem+Foto';

        const miniatura = document.createElement('button');
        miniatura.className = `w-16 h-16 rounded-lg overflow-hidden border-2 transition-all ${
            index === indiceFotoAtual 
                ? 'border-blue-500 ring-2 ring-blue-300' 
                : 'border-gray-300 hover:border-blue-400'
        }`;
        miniatura.innerHTML = `<img src="${urlMiniatura}" alt="Miniatura ${index + 1}" class="w-full h-full object-cover">`;
        
        miniatura.addEventListener('click', () => {
            indiceFotoAtual = index;
            atualizarFotoExibida();
        });

        containerMiniaturas.appendChild(miniatura);
    });
}

/**
 * Verifica se um produto tem múltiplas fotos
 * @param {Object} produto - Objeto do produto
 * @returns {boolean}
 */
export function produtoTemMultiplasFotos(produto) {
    return produto.fotos && produto.fotos.length > 1;
}

/**
 * Retorna o número de fotos de um produto
 * @param {Object} produto - Objeto do produto
 * @returns {number}
 */
export function contarFotosProduto(produto) {
    return produto.fotos ? produto.fotos.length : 0;
}