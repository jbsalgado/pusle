// REMOVIDO: importScripts('js/idb-keyval.js');
// A lógica do idbKeyval de utils.js foi incorporada abaixo e corrigida para o escopo do Service Worker.

// === LÓGICA DO idbKeyval PORTADA DE utils.js (CORRIGIDA PARA SW) ===
// Garante que o Service Worker use o mesmo DB name que a página principal
const DB_NAME = 'catalogo-db';
const STORE_NAME = 'keyval-store';

function openDb() {
    return new Promise((resolve, reject) => {
        // ✅ CORREÇÃO CRÍTICA: Verifica o suporte a IndexedDB no escopo 'self' (Service Worker)
        if (!('indexedDB' in self)) { 
            reject(new Error("IndexedDB not supported in this context (Service Worker)."));
            return;
        }

        // ✅ CORREÇÃO CRÍTICA: Usa self.indexedDB para garantir o acesso correto no Service Worker
        const request = self.indexedDB.open(DB_NAME, 1);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                // Cria o Object Store para armazenar as chaves (carrinho, pedido pendente, etc.)
                db.createObjectStore(STORE_NAME);
            }
        };

        request.onsuccess = (event) => {
            resolve(event.target.result);
        };

        request.onerror = (event) => {
            reject(event.target.error);
        };
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
    },
    // Chaves desnecessárias para o Service Worker, removidas para simplificação
};
// === FIM DA LÓGICA DO idbKeyval ===


// ✅ CHAVE DE ARMAZENAMENTO CORRETA (Baseada em config.js)
const PEDIDO_PENDENTE_KEY = 'pedido_pendente_pwa';

// Detectar ambiente automaticamente
const isProduction = self.location.hostname !== 'localhost' && self.location.hostname !== '127.0.0.1';
const URL_API = isProduction ? '/pulse/web/index.php' : '/pulse/basic/web/index.php';
const URL_BASE_WEB = isProduction ? '/pulse/web' : '/pulse/basic/web';

// Configuração de Caminhos e Cache
const API_PRODUTO_URL = `${URL_API}/api/produto`;
const API_PEDIDO_URL = `${URL_API}/api/pedido`;
// Incrementado para forçar a atualização de cache
const CACHE_NAME = 'catalogo-cache-v9'; 

const APP_SHELL_FILES = [
    `${URL_BASE_WEB}/catalogo/index.html`,
    `${URL_BASE_WEB}/catalogo/app.js`,
    `${URL_BASE_WEB}/catalogo/style.css`,
    `${URL_BASE_WEB}/catalogo/manifest.json`,
    `${URL_BASE_WEB}/catalogo/js/idb-keyval.js`,
];

// Arquivos críticos que SEMPRE devem buscar na rede primeiro
const CRITICAL_FILES = [
    `${URL_BASE_WEB}/catalogo/index.html`,
    `${URL_BASE_WEB}/catalogo/app.js`
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
        // Agora idbKeyval usa o DB correto ('catalogo-db') e o escopo correto ('self')
        pedidoPendente = await idbKeyval.get(PEDIDO_PENDENTE_KEY);
    } catch (err) {
        console.error('[SW] Erro ao ler pedido do IndexedDB:', err);
        return;
    }

    if (pedidoPendente) {
        console.log('[SW] ✅ Pedido pendente encontrado:', pedidoPendente);
        console.log('[SW] 🌐 Enviando para URL:', API_PEDIDO_URL);
        
        try {
            const response = await fetch(API_PEDIDO_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(pedidoPendente)
            });

            console.log('[SW] 📡 Status da resposta:', response.status, response.statusText);

            if (response.ok) {
                const responseData = await response.json();
                console.log('[SW] ✅ Pedido enviado com sucesso! Resposta:', responseData);
                
                // Usando a chave correta para remover
                await idbKeyval.del(PEDIDO_PENDENTE_KEY);
                console.log('[SW] 🗑️ Pedido removido do IndexedDB.');

                const clients = await self.clients.matchAll({ 
                    type: 'window', 
                    includeUncontrolled: true 
                });
                
                clients.forEach(client => {
                    client.postMessage({ 
                        type: 'SYNC_SUCCESS',
                        pedido: responseData
                    });
                });
                console.log(`[SW] 📨 SYNC_SUCCESS enviado para ${clients.length} cliente(s).`);
            } else {
                const responseBody = await response.text();
                console.error('[SW] ❌ Falha ao enviar pedido. Status:', response.status);
                console.error('[SW] 📄 Corpo da resposta:', responseBody);
                
                // Notificar o frontend sobre o erro
                const clients = await self.clients.matchAll({ 
                    type: 'window', 
                    includeUncontrolled: true 
                });
                
                clients.forEach(client => {
                    client.postMessage({ 
                        type: 'SYNC_ERROR',
                        error: `Erro ${response.status}: ${responseBody}`
                    });
                });
                
                throw new Error(`Falha no servidor: ${response.status} - ${responseBody}`);
            }
        } catch (error) {
            console.error('[SW] ❌ Erro durante envio:', error);
            console.error('[SW] 📍 URL tentada:', API_PEDIDO_URL);
            
            // Notificar o frontend sobre o erro
            const clients = await self.clients.matchAll({ 
                type: 'window', 
                includeUncontrolled: true 
            });
            
            clients.forEach(client => {
                client.postMessage({ 
                    type: 'SYNC_ERROR',
                    error: error.message
                });
            });
            
            throw error;
        }
    } else {
        console.log('[SW] ℹ️ Nenhum pedido pendente encontrado.');
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