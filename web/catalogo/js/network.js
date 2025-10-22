// network.js - Gerenciamento de status online/offline

/**
 * Atualiza status online/offline na interface
 */
export function atualizarStatusOnline() {
    const htmlTag = document.documentElement;
    const isOnline = navigator.onLine;
    
    if (isOnline) {
        htmlTag.classList.remove('offline');
        htmlTag.classList.add('online');
        console.log('[Network] Status: ONLINE');
    } else {
        htmlTag.classList.remove('online');
        htmlTag.classList.add('offline');
        console.log('[Network] Status: OFFLINE');
    }
    
    return isOnline;
}

/**
 * Inicializa listeners de rede
 */
export function inicializarMonitoramentoRede() {
    window.addEventListener('online', () => {
        atualizarStatusOnline();
        console.log('[Network] Conexão restaurada');
        // Dispara evento customizado que pode ser ouvido por outros módulos
        window.dispatchEvent(new CustomEvent('app:online'));
    });
    
    window.addEventListener('offline', () => {
        atualizarStatusOnline();
        console.log('[Network] Conexão perdida');
        // Dispara evento customizado que pode ser ouvido por outros módulos
        window.dispatchEvent(new CustomEvent('app:offline'));
    });
    
    // Verifica status inicial
    atualizarStatusOnline();
}

/**
 * Verifica se está online
 */
export function estaOnline() {
    return navigator.onLine;
}