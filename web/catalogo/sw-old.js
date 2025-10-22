// Importa a biblioteca idb-keyval
importScripts('js/idb-keyval.js');

// Configuração de Caminhos e Cache
const URL_BASE = '/pulse/basic/web';
const API_PRODUTO_URL = `${URL_BASE}/index.php/api/produto`;
const API_PEDIDO_URL = `${URL_BASE}/index.php/api/pedido`;
const CACHE_NAME = 'catalogo-cache-v5'; // INCREMENTADO - IMPORTANTE!

const APP_SHELL_FILES = [
    `${URL_BASE}/catalogo/index.html`,
    `${URL_BASE}/catalogo/app.js`,
    `${URL_BASE}/catalogo/style.css`,
    `${URL_BASE}/catalogo/manifest.json`,
    `${URL_BASE}/catalogo/js/idb-keyval.js`,
];

// Arquivos críticos que SEMPRE devem buscar na rede primeiro
const CRITICAL_FILES = [
    `${URL_BASE}/catalogo/index.html`,
    `${URL_BASE}/catalogo/app.js`
];

// Evento de Instalação
self.addEventListener('install', event => {
    console.log('[SW] Instalando versão:', CACHE_NAME);
    event.waitUntil(
        caches.open(CACHE_NAME).then(async (cache) => {
            console.log('[SW] Cacheando App Shell...');
            await cache.addAll(APP_SHELL_FILES).catch(err => 
                console.error('[SW] Falha ao cachear App Shell:', err)
            );

            console.log('[SW] Tentando cachear API de produtos...');
            try {
                await cache.add(API_PRODUTO_URL);
                console.log('[SW] API de produtos cacheada.');
            } catch (err) {
                console.warn('[SW] Falha ao cachear API:', err);
            }
        }).then(() => {
            console.log('[SW] skipWaiting chamado');
            return self.skipWaiting();
        })
    );
});

// Evento Fetch - ESTRATÉGIAS MELHORADAS
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Ignora requisições não-GET
    if (event.request.method !== 'GET') {
        return;
    }

    // 1. API de Produtos (Stale-While-Revalidate)
    if (event.request.url === API_PRODUTO_URL) {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return cache.match(event.request).then(cachedResponse => {
                    const fetchPromise = fetch(event.request).then(networkResponse => {
                        if (networkResponse.ok) {
                            cache.put(event.request, networkResponse.clone());
                        }
                        return networkResponse;
                    }).catch(err => {
                        console.error('[SW] Fetch da API falhou:', err);
                        if (!cachedResponse) {
                            return new Response('Erro de rede ao buscar produtos.', {
                                status: 408,
                                headers: { 'Content-Type': 'text/plain' }
                            });
                        }
                    });
                    return cachedResponse || fetchPromise;
                });
            })
        );
        return;
    }

    // 2. Imagens (Network-First com fallback)
    if (url.hostname === 'via.placeholder.com' || event.request.destination === 'image') {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return fetch(event.request)
                    .then(networkResponse => {
                        if (networkResponse.ok) {
                            cache.put(event.request, networkResponse.clone());
                        }
                        return networkResponse;
                    })
                    .catch(() => cache.match(event.request) || 
                        new Response('', { status: 404 })
                    );
            })
        );
        return;
    }

    // 3. ARQUIVOS CRÍTICOS (HTML, JS) - NETWORK-FIRST
    const isCriticalFile = CRITICAL_FILES.some(file => 
        event.request.url.includes(file)
    );
    
    if (isCriticalFile) {
        console.log('[SW] Arquivo crítico detectado, usando Network-First:', event.request.url);
        event.respondWith(
            fetch(event.request)
                .then(networkResponse => {
                    // Clone ANTES de fazer qualquer operação
                    if (networkResponse.ok) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, responseToCache);
                        });
                    }
                    return networkResponse;
                })
                .catch(err => {
                    console.warn('[SW] Network falhou para arquivo crítico, usando cache:', err);
                    // Fallback para cache apenas se a rede falhar
                    return caches.match(event.request).then(cachedResponse => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        return new Response('Offline - arquivo não disponível', {
                            status: 503,
                            statusText: 'Service Unavailable'
                        });
                    });
                })
        );
        return;
    }

    // 4. Outros arquivos (Cache-First)
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request).then(networkResponse => {
                if (networkResponse.ok) {
                    return caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    });
                }
                return networkResponse;
            });
        }).catch(err => {
            console.error('[SW] Erro no fetch:', err);
            return new Response('Erro ao carregar recurso', { status: 500 });
        })
    );
});

// Evento Sync
self.addEventListener('sync', event => {
    if (event.tag === 'sync-novo-pedido') {
        console.log('[SW] Evento Sync recebido: sync-novo-pedido');
        event.waitUntil(enviarPedidosPendentes());
    }
});

// Função para enviar pedidos
async function enviarPedidosPendentes() {
    let pedidoPendente;
    try {
        pedidoPendente = await idbKeyval.get('pedido_pendente');
    } catch (err) {
        console.error('[SW] Erro ao ler pedido do IndexedDB:', err);
        return;
    }

    if (pedidoPendente) {
        console.log('[SW] Enviando pedido pendente:', pedidoPendente);
        try {
            const response = await fetch(API_PEDIDO_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(pedidoPendente)
            });

            if (response.ok) {
                console.log('[SW] Pedido enviado com sucesso!');
                await idbKeyval.del('pedido_pendente');
                console.log('[SW] Pedido removido do IndexedDB.');

                const clients = await self.clients.matchAll({ 
                    type: 'window', 
                    includeUncontrolled: true 
                });
                
                clients.forEach(client => {
                    client.postMessage({ type: 'SYNC_SUCCESS' });
                });
                console.log(`[SW] SYNC_SUCCESS enviado para ${clients.length} cliente(s).`);
            } else {
                console.error('[SW] Falha ao enviar pedido:', response.status);
                const responseBody = await response.text();
                console.error('[SW] Corpo da resposta:', responseBody);
                throw new Error(`Falha no servidor: ${response.status}`);
            }
        } catch (error) {
            console.error('[SW] Erro durante envio:', error);
            throw error;
        }
    } else {
        console.log('[SW] Nenhum pedido pendente encontrado.');
    }
}

// Evento Message - Permite skip waiting sob demanda
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        console.log('[SW] SKIP_WAITING recebido da página');
        self.skipWaiting();
    }
});

// Evento Activate - LIMPA CACHES ANTIGOS
self.addEventListener('activate', event => {
    console.log('[SW] Ativando versão:', CACHE_NAME);
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Deletando cache antigo:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('[SW] clients.claim() chamado');
            return self.clients.claim();
        })
    );
});