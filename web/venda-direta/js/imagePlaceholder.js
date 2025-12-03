// imagePlaceholder.js - Gerenciamento de imagens com fallback local

/**
 * SVG Placeholder embutido (funciona sempre, offline, sem arquivo externo)
 * Imagem 300x300 com texto "Sem Imagem"
 */
export const PLACEHOLDER_SVG = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300"%3E%3Crect fill="%23f0f0f0" width="300" height="300"/%3E%3Cg%3E%3Cpath fill="%23d0d0d0" d="M150 80 L170 120 L220 120 L180 150 L200 190 L150 160 L100 190 L120 150 L80 120 L130 120 Z"/%3E%3C/g%3E%3Ctext fill="%23999" font-family="Arial, sans-serif" font-size="18" font-weight="600" x="50%25" y="65%25" text-anchor="middle" dominant-baseline="middle"%3ESem Imagem%3C/text%3E%3C/svg%3E';

/**
 * Aplica fallback de imagem a um elemento <img>
 * @param {HTMLImageElement} imgElement - Elemento img
 * @param {string} fallbackSrc - URL do fallback (opcional, usa SVG se não fornecido)
 */
export function aplicarFallbackImagem(imgElement, fallbackSrc = PLACEHOLDER_SVG) {
    if (!imgElement || imgElement.tagName !== 'IMG') {
        console.warn('[ImagePlaceholder] Elemento inválido');
        return;
    }
    
    // Evita loop: só permite 1 tentativa de fallback
    imgElement.onerror = function() {
        console.log('[ImagePlaceholder] Imagem falhou, usando placeholder:', this.src);
        
        // Remove o handler para evitar loop
        this.onerror = null;
        
        // Aplica placeholder
        this.src = fallbackSrc;
        
        // Adiciona classe CSS para estilização opcional
        this.classList.add('img-placeholder');
    };
}

/**
 * Cria elemento img com fallback automático
 * @param {string} src - URL da imagem
 * @param {string} alt - Texto alternativo
 * @param {string} className - Classes CSS
 * @returns {HTMLImageElement}
 */
export function criarImagemComFallback(src, alt = '', className = '') {
    const img = document.createElement('img');
    img.src = src;
    img.alt = alt;
    
    if (className) {
        img.className = className;
    }
    
    aplicarFallbackImagem(img);
    
    return img;
}

/**
 * Aplica fallback em todas as imagens de produtos já existentes na página
 * Útil para aplicar após o carregamento da página
 */
export function aplicarFallbackEmTodasImagens() {
    const imagens = document.querySelectorAll('img');
    let contador = 0;
    
    imagens.forEach(img => {
        // Se a imagem não tem onerror definido, aplica
        if (!img.onerror || img.onerror.toString().includes('placeholder.com')) {
            aplicarFallbackImagem(img);
            contador++;
        }
    });
    
    console.log(`[ImagePlaceholder] Fallback aplicado em ${contador} imagem(ns)`);
    return contador;
}

/**
 * Gera placeholder SVG personalizado
 * @param {Object} options - Opções de customização
 * @returns {string} Data URI do SVG
 */
export function gerarPlaceholderCustomizado(options = {}) {
    const {
        width = 300,
        height = 300,
        bgColor = '#f0f0f0',
        iconColor = '#d0d0d0',
        textColor = '#999',
        text = 'Sem Imagem',
        fontSize = 18
    } = options;
    
    const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
            <rect fill="${bgColor}" width="${width}" height="${height}"/>
            <g>
                <path fill="${iconColor}" d="M${width/2} ${height*0.27} L${width*0.57} ${height*0.4} L${width*0.73} ${height*0.4} L${width*0.6} ${height*0.5} L${width*0.67} ${height*0.63} L${width/2} ${height*0.53} L${width*0.33} ${height*0.63} L${width*0.4} ${height*0.5} L${width*0.27} ${height*0.4} L${width*0.43} ${height*0.4} Z"/>
            </g>
            <text fill="${textColor}" font-family="Arial, sans-serif" font-size="${fontSize}" font-weight="600" x="50%" y="65%" text-anchor="middle" dominant-baseline="middle">${text}</text>
        </svg>
    `.trim();
    
    // Encode SVG para data URI
    return 'data:image/svg+xml,' + encodeURIComponent(svg);
}

// Auto-aplicar em todas as imagens quando o módulo é carregado
if (typeof window !== 'undefined') {
    // Espera o DOM carregar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            aplicarFallbackEmTodasImagens();
        });
    } else {
        // DOM já carregado, aplica imediatamente
        aplicarFallbackEmTodasImagens();
    }
    
    // Observa novas imagens sendo adicionadas ao DOM
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.tagName === 'IMG') {
                    aplicarFallbackImagem(node);
                } else if (node.querySelectorAll) {
                    const imagens = node.querySelectorAll('img');
                    imagens.forEach(img => aplicarFallbackImagem(img));
                }
            });
        });
    });
    
    // Começa a observar quando DOM estiver pronto
    if (document.body) {
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        console.log('[ImagePlaceholder] Observer ativo - novas imagens terão fallback automático');
    }
}