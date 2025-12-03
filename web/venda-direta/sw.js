// pulse/basic/web/venda-direta/sw.js
// VERSÃO V3 - CORREÇÃO PIX

// === LÓGICA DO idbKeyval ===
const DB_NAME = 'venda-direta-db';
const STORE_NAME = 'keyval-store';

function openDb() {
    return new Promise((resolve, reject) => {
        if (!('indexedDB' in self)) { 
            reject(new Error("IndexedDB not supported in this context (Service Worker)."));
            return;
        }
        const request = self.indexedDB.open(DB_NAME, 1);
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME);
            }
        };
        request.onsuccess = (event) => resolve(event.target.result);
        request.onerror = (event) => reject(event.target.error);
    });
}

const idbKeyval = {
    async get(key) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const store = tx.objectStore(STORE_NAME);
            const request = store.get(key);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },
    async set(key, val) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.put(val, key);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
            request.onerror = () => reject(request.error);
        });
    },
    async del(key) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.delete(key);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
            request.onerror = () => reject(request.error);
        });
    }
};

// Configurações
const PEDIDO_PENDENTE_KEY = 'pedido_pendente_venda_direta';
const isProduction = self.location.hostname !== 'localhost' && self.location.hostname !== '127.0.0.1';
const URL_API = isProduction ? '/pulse/web/index.php' : '/pulse/basic/web/index.php';
const URL_BASE_WEB = isProduction ? '/pulse/web' : '/pulse/basic/web';

const getLojaId = () => {
    const pathname = self.location.pathname;
    const segments = pathname.split('/').filter(p => p);
    const lojaPath = segments[segments.length - 2]; 
    const lojaMap = {
        'catalogo': 'a99a38a9-e368-4a47-a4bd-02ba3bacaa76',
        'alexbird': '5eb98116-77c2-4a01-bd60-50db21eaa206',
        'victor': '0b633731-25a1-4991-b1c4-c46acc6bce06',
        'venda-direta': 'a99a38a9-e368-4a47-a4bd-02ba3bacaa76',
    };
    return lojaMap[lojaPath] || lojaMap['venda-direta'];
};

const ID_USUARIO_LOJA = getLojaId();
const API_PRODUTO_URL = `${URL_API}/api/produto?usuario_id=${ID_USUARIO_LOJA}`;
const API_PEDIDO_URL = `${URL_API}/api/pedido`;

// 🔥 ATUALIZAÇÃO IMPORTANTE: Versão v3 para forçar novo pix.js
const CACHE_NAME = 'venda-direta-cache-v3'; 

const APP_SHELL_FILES = [
    `${URL_BASE_WEB}/venda-direta/index.html`,
    `${URL_BASE_WEB}/venda-direta/js/app.js`,
    `${URL_BASE_WEB}/venda-direta/js/pix.js`, // Garante cache do novo arquivo
    `${URL_BASE_WEB}/venda-direta/style.css`,
    `${URL_BASE_WEB}/venda-direta/manifest.json`,
    `${URL_BASE_WEB}/venda-direta/js/idb-keyval.js`,
];

const CRITICAL_FILES = [
    `${URL_BASE_WEB}/venda-direta/index.html`,
    `${URL_BASE_WEB}/venda-direta/js/app.js`,
    `${URL_BASE_WEB}/venda-direta/js/pix.js`
];

// Install
self.addEventListener('install', event => {
    console.log('[SW] Instalando versão:', CACHE_NAME);
    event.waitUntil(
        caches.open(CACHE_NAME).then(async (cache) => {
            await cache.addAll(APP_SHELL_FILES).catch(err => 
                console.error('[SW] Falha ao cachear App Shell:', err)
            );
            try {
                await cache.add(API_PRODUTO_URL);
            } catch (err) {
                console.warn(`[SW] Falha ao cachear API inicial:`, err);
            }
        }).then(() => self.skipWaiting())
    );
});

// Fetch
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    if (event.request.method !== 'GET') return;

    // 1. API Produtos (Stale-While-Revalidate)
    if (event.request.url === API_PRODUTO_URL) {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return cache.match(event.request).then(cachedResponse => {
                    const fetchPromise = fetch(event.request).then(networkResponse => {
                        if (networkResponse.ok) cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    }).catch(() => {});
                    return cachedResponse || fetchPromise;
                });
            })
        );
        return;
    }

    // 2. Outras APIs (NETWORK-ONLY)
    if (url.pathname.includes('/api/')) {
        event.respondWith(
            fetch(event.request).catch(err => {
                return new Response(JSON.stringify({
                    sucesso: false,
                    erro: 'Offline - Sem conexão para esta operação'
                }), {
                    status: 503, 
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
        return;
    }

    // 3. Imagens
    if (url.hostname === 'via.placeholder.com' || event.request.destination === 'image') {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return fetch(event.request)
                    .then(networkResponse => {
                        if (networkResponse.ok) cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    })
                    .catch(() => cache.match(event.request) || new Response('', { status: 404 }));
            })
        );
        return;
    }

    // 4. Arquivos Críticos (Network First)
    const isCriticalFile = CRITICAL_FILES.some(file => event.request.url.includes(file));
    if (isCriticalFile) {
        event.respondWith(
            fetch(event.request)
                .then(networkResponse => {
                    if (networkResponse.ok) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseToCache));
                    }
                    return networkResponse;
                })
                .catch(() => caches.match(event.request))
        );
        return;
    }

    // 5. Cache First (Padrão)
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request).then(networkResponse => {
                if (networkResponse.ok) {
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, networkResponse.clone()));
                }
                return networkResponse;
            });
        })
    );
});

// Sync
self.addEventListener('sync', event => {
    if (event.tag === 'sync-novo-pedido') {
        event.waitUntil(enviarPedidosPendentes());
    }
});

async function enviarPedidosPendentes() {
    let pedidoPendente;
    try {
        pedidoPendente = await idbKeyval.get(PEDIDO_PENDENTE_KEY);
    } catch (err) { return; }

    if (pedidoPendente) {
        try {
            const response = await fetch(API_PEDIDO_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(pedidoPendente)
            });

            if (response.ok) {
                const responseData = await response.json();
                await idbKeyval.del(PEDIDO_PENDENTE_KEY);
                const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
                clients.forEach(client => client.postMessage({ type: 'SYNC_SUCCESS', pedido: responseData }));
            } else {
                const responseBody = await response.text();
                const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
                clients.forEach(client => client.postMessage({ type: 'SYNC_ERROR', error: `Erro ${response.status}` }));
            }
        } catch (error) {
            const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
            clients.forEach(client => client.postMessage({ type: 'SYNC_ERROR', error: error.message }));
        }
    }
}

self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) return caches.delete(cacheName);
                })
            );
        }).then(() => self.clients.claim())
    );
});