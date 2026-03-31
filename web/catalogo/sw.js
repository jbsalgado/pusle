// pulse/basic/web/catalogo/sw.js
// ✅ ARQUIVO COMPLETO E AJUSTADO

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

// ==================================================================
// ✅ AJUSTE: LÓGICA PARA OBTER O ID DA LOJA (COPIADO DO config.js)
// ==================================================================
const getLojaId = () => {
    // self.location.pathname aqui é /pulse/basic/web/catalogo/sw.js
    const pathname = self.location.pathname;
    const segments = pathname.split('/').filter(p => p);
    // segments = ['pulse', 'basic', 'web', 'catalogo', 'sw.js']
    const lojaPath = segments[segments.length - 2]; // Pega 'catalogo'
    
    const lojaMap = {
        'catalogo': '5e449fee-4486-4536-a64f-74aed38a6987', // Top Construções
        'top-construcoes': '5e449fee-4486-4536-a64f-74aed38a6987', // Top Construções
        'alexbird': '5eb98116-77c2-4a01-bd60-50db21eaa206',
        'victor':'0b633731-25a1-4991-b1c4-c46acc6bce06',
    };
    
    return lojaMap[lojaPath] || lojaMap['catalogo']; // Retorna o ID
};

// Define o ID da Loja que será usado pelo SW
const ID_USUARIO_LOJA = getLojaId();
// ==================================================================
// FIM DO AJUSTE
// ==================================================================


// Configuração de Caminhos e Cache
// ✅ AJUSTE: A URL da API de produtos AGORA INCLUI o ID da loja
const API_PRODUTO_URL = `${URL_API}/api/produto?usuario_id=${ID_USUARIO_LOJA}`;
const API_PEDIDO_URL = `${URL_API}/api/pedido`;

// ✅ AJUSTE: Incrementado para v14 (para forçar atualização - descrição completa)
const CACHE_NAME = 'catalogo-cache-v14'; 

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

            // ✅ AJUSTE: Log mais claro
            console.log(`[SW] Tentando cachear API de produtos (Loja: ${ID_USUARIO_LOJA})...`);
            try {
                // ✅ AJUSTE: Tenta cachear a URL correta (com ID)
                await cache.add(API_PRODUTO_URL);
                console.log('[SW] API de produtos cacheada.');
            } catch (err) {
                // Se falhar (ex: offline na primeira instalação), o fetch lidará com isso depois
                console.warn(`[SW] Falha ao cachear API (${API_PRODUTO_URL}):`, err);
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
    // ✅ AJUSTE: Agora compara a URL exata (com ID) que vem do app.js
    if (event.request.url === API_PRODUTO_URL) {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return cache.match(event.request).then(cachedResponse => {
                    // Tenta buscar na rede em paralelo
                    const fetchPromise = fetch(event.request).then(networkResponse => {
                        if (networkResponse.ok) {
                            // Se a rede responder, atualiza o cache
                            cache.put(event.request, networkResponse.clone());
                        }
                        return networkResponse;
                    }).catch(err => {
                        // Se a rede falhar
                        console.error('[SW] Fetch da API falhou:', err);
                        if (!cachedResponse) {
                            // E não tiver nada no cache, retorna erro
                            return new Response('Erro de rede ao buscar produtos.', {
                                status: 408,
                                headers: { 'Content-Type': 'text/plain' }
                            });
                        }
                        // Se a rede falhar MAS tiver cache, não faz nada (retorna o cache)
                    });
                    
                    // Retorna o cache imediatamente (se houver), ou aguarda a rede
                    return cachedResponse || fetchPromise;
                });
            })
        );
        return;
    }

    // ========================================================================
    // ✅✅✅ INÍCIO DA CORREÇÃO ✅✅✅
    // ========================================================================
    // 1.5. API Calls (NETWORK-ONLY)
    // Força todas as outras chamadas de /api/ (como /api/asaas/consultar-status)
    // a irem DIRETAMENTE para a rede, ignorando o cache do SW.
    if (url.pathname.includes('/api/')) {
        console.log('[SW] API call detectada (Network-Only):', event.request.url);
        event.respondWith(
            fetch(event.request).catch(err => {
                console.error('[SW] Falha no fetch (Network-Only) da API:', err);
                // Retorna uma resposta de erro padronizada em JSON
                return new Response(JSON.stringify({
                    sucesso: false,
                    erro: 'Erro de rede no Service Worker',
                    details: err.message
                }), {
                    status: 503, // Service Unavailable
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
        return; // Importante para parar a execução
    }
    // ========================================================================
    // ✅✅✅ FIM DA CORREÇÃO ✅✅✅
    // ========================================================================


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
    // ESTA REGRA NÃO VAI MAIS PEGAR AS CHAMADAS DE API
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