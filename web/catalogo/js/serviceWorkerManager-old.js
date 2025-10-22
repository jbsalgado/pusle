// serviceWorkerManager.js - Gerenciamento do Service Worker

/**
 * Registra e gerencia o Service Worker
 */
export function inicializarServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        console.warn('[SW] Service Worker não suportado');
        return;
    }

    let refreshing = false;
    
    // Detecta mudança de controlador (nova versão ativa)
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (refreshing) return;
        refreshing = true;
        console.log('[SW] Novo Service Worker ativado, recarregando página...');
        window.location.reload();
    });
    
    // Registra Service Worker
    navigator.serviceWorker.register('sw.js')
        .then(registration => {
            console.log('[SW] Service Worker registrado:', registration.scope);
            
            // Verifica atualizações periodicamente (a cada 1 minuto)
            setInterval(() => {
                registration.update();
            }, 60000);
            
            // Detecta quando nova versão está sendo instalada
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                console.log('[SW] Nova versão detectada!');
                
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        console.log('[SW] Nova versão disponível!');
                        // Instrui o novo SW a assumir o controle imediatamente
                        newWorker.postMessage({ type: 'SKIP_WAITING' });
                    }
                });
            });
        })
        .catch(error => {
            console.error('[SW] Falha ao registrar Service Worker:', error);
        });
}

/**
 * Adiciona listener para mensagens do Service Worker
 */
export function adicionarListenerMensagensSW(callback) {
    if (!('serviceWorker' in navigator)) return;
    
    navigator.serviceWorker.addEventListener('message', event => {
        if (event.data && event.data.type) {
            console.log('[SW] Mensagem recebida:', event.data.type);
            callback(event.data);
        }
    });
}

/**
 * Envia mensagem para o Service Worker
 */
export async function enviarMensagemParaSW(mensagem) {
    if (!('serviceWorker' in navigator)) {
        console.warn('[SW] Service Worker não disponível');
        return false;
    }

    try {
        const registration = await navigator.serviceWorker.ready;
        if (registration.active) {
            registration.active.postMessage(mensagem);
            return true;
        }
        return false;
    } catch (error) {
        console.error('[SW] Erro ao enviar mensagem:', error);
        return false;
    }
}