// network.js - Gerenciamento de status online/offline

/**
 * Atualiza status online/offline na interface
 */
export function atualizarStatusOnline() {
    const htmlTag = document.documentElement;
    const isOnline = navigator.onLine;
    
    // Remove ambas as classes primeiro
    htmlTag.classList.remove('online', 'offline');
    
    if (isOnline) {
        htmlTag.classList.add('online');
        console.log('[Network] ✅ Status: ONLINE');
    } else {
        htmlTag.classList.add('offline');
        console.log('[Network] ⚠️ Status: OFFLINE');
    }
    
    return isOnline;
}

/**
 * Inicializa listeners de rede
 */
export function inicializarMonitoramentoRede() {
    // Verifica status inicial imediatamente
    atualizarStatusOnline();
    
    window.addEventListener('online', () => {
        atualizarStatusOnline();
        console.log('[Network] 🌐 Conexão restaurada');
        // Dispara evento customizado que pode ser ouvido por outros módulos
        window.dispatchEvent(new CustomEvent('app:online'));
    });
    
    window.addEventListener('offline', () => {
        atualizarStatusOnline();
        console.log('[Network] 📴 Conexão perdida');
        // Dispara evento customizado que pode ser ouvido por outros módulos
        window.dispatchEvent(new CustomEvent('app:offline'));
    });
    
    // Verificação adicional: atualiza periodicamente (a cada 5 segundos) para garantir
    // Isso ajuda em casos onde navigator.onLine pode estar desatualizado
    setInterval(() => {
        atualizarStatusOnline();
    }, 5000);
}

/**
 * Verifica se está online
 */
export function estaOnline() {
    return navigator.onLine;
}