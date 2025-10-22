// js/serviceWorkerManager.js
export async function inicializarServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        console.warn('[SW] Service Worker não suportado');
        return false;
    }

    try {
        const registration = await navigator.serviceWorker.register('../catalogo/sw.js');
        console.log('[SW] Registrado:', registration.scope);

        // Verifica atualizações a cada 5 minutos
        setInterval(() => {
            registration.update().then(() => console.log('[SW] Verificação de atualização concluída'));
        }, 5 * 60 * 1000);

        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            console.log('[SW] Nova versão encontrada');

            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    if (confirm('Nova versão disponível! Atualizar agora?')) {
                        newWorker.postMessage({ type: 'SKIP_WAITING' });
                        window.location.reload();
                    }
                }
            });
        });

        let refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (!refreshing) {
                refreshing = true;
                window.location.reload();
            }
        });

        return true;
    } catch (error) {
        console.error('[SW] Erro ao registrar:', error);
        return false;
    }
}

export function adicionarListenerMensagensSW(callback) {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', event => {
            if (event.data && callback) callback(event.data);
        });
    }
}