// sw.js - Service Worker para Módulo Prestanista

const CACHE_NAME = 'prestanista-cache-v1';
const SYNC_TAG = 'sync-cobranca-prestanista';

// Recursos estáticos para cache
const STATIC_ASSETS = [
    '/pulse/basic/web/prestanista/',
    '/pulse/basic/web/prestanista/index.html',
    '/pulse/basic/web/prestanista/js/app.js',
    '/pulse/basic/web/prestanista/js/config.js',
    '/pulse/basic/web/prestanista/js/storage.js',
    '/pulse/basic/web/prestanista/js/sync.js',
    '/pulse/basic/web/prestanista/js/utils.js',
    '/pulse/basic/web/prestanista/js/idb-keyval.js',
    '/pulse/basic/web/prestanista/manifest.json',
    'https://cdn.tailwindcss.com',
];

// Install - Cache recursos estáticos
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Caching static assets');
                return cache.addAll(STATIC_ASSETS.filter(url => !url.includes('tailwindcss')));
            })
            .catch((err) => {
                console.error('[SW] Error caching assets:', err);
            })
    );
    self.skipWaiting();
});

// Activate - Limpa caches antigos
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    return self.clients.claim();
});

// Fetch - Estratégia: Network First, fallback para cache
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Ignora requisições de API (devem sempre ir para rede)
    if (url.pathname.includes('/api/')) {
        return;
    }
    
    // Para outros recursos, usa Network First
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Se a resposta é válida, atualiza o cache
                if (response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // Se falhar, tenta buscar do cache
                return caches.match(event.request);
            })
    );
});

// Background Sync - Sincroniza pagamentos pendentes quando voltar online
self.addEventListener('sync', (event) => {
    if (event.tag === SYNC_TAG) {
        console.log('[SW] Background sync triggered');
        event.waitUntil(
            syncPagamentosPendentes()
        );
    }
});

/**
 * Sincroniza pagamentos pendentes
 */
async function syncPagamentosPendentes() {
    try {
        // Nota: Esta função deve ser implementada no cliente
        // O Service Worker apenas dispara a sincronização
        const clients = await self.clients.matchAll({ includeUncontrolled: true });
        clients.forEach((client) => {
            client.postMessage({
                type: 'SYNC_PAGAMENTOS',
                timestamp: new Date().toISOString()
            });
        });
        console.log('[SW] Sync message sent to clients');
    } catch (error) {
        console.error('[SW] Error syncing payments:', error);
    }
}

// Push notifications (opcional, para futuras implementações)
self.addEventListener('push', (event) => {
    console.log('[SW] Push notification received');
    // Implementar notificações push se necessário
});

