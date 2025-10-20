// AJUSTE 1: Mantido seu import local
importScripts('js/idb-keyval.js');

// CAMINHOS COMPLETOS PARA SEU AMBIENTE
const URL_BASE = '/pulse/basic/web';
const API_URL = `${URL_BASE}/index.php/api/produto`;
const API_PEDIDO_URL = `${URL_BASE}/index.php/api/pedido`;

// AJUSTE 2: Nova versão do Cache
const CACHE_NAME = 'catalogo-cache-v3';

// AJUSTE 3: Adicionado seu script local ao cache
const APP_SHELL_FILES = [
    `${URL_BASE}/catalogo/index.html`,
    `${URL_BASE}/catalogo/app.js`,
    `${URL_BASE}/catalogo/style.css`,
    `${URL_BASE}/catalogo/manifest.json`,
    `${URL_BASE}/catalogo/js/idb-keyval.js` // <-- ADICIONADO
];

// 1. Evento de Instalação
self.addEventListener('install', event => {
    console.log('[SW] Instalando...');
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('[SW] Cacheando App Shell e API...');
            // Cacheia a casca do app
            cache.addAll(APP_SHELL_FILES);
            
            // Cacheia os dados da API (para catálogo offline)
            return cache.add(API_URL);
        })
    );
});

//
// AJUSTE 4: Evento 'fetch' com lógica para imagens
//
self.addEventListener('fetch', event => {
    const url = event.request.url;

    // 1. API (Stale-While-Revalidate)
    if (url.includes(API_URL)) {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return cache.match(event.request).then(cachedResponse => {
                    const fetchedResponsePromise = fetch(event.request).then(networkResponse => {
                        cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    });
                    return cachedResponse || fetchedResponsePromise;
                });
            })
        );
        return;
    }

    // 2. IMAGENS (Network-Only)
    // Se for uma imagem do produto, busque direto da rede.
    // Isso atende ao requisito de "online-only" e "não salvar no dispositivo".
    if (url.includes('/uploads/produtos/')) {
        event.respondWith(
            fetch(event.request)
            .catch(() => {
                // Se falhar (offline), retorna um erro 404
                return new Response('', { status: 404, statusText: 'Offline' });
            })
        );
        return;
    }
    
    // 3. APP SHELL (Cache-First)
    // Para todos os outros arquivos (HTML, CSS, JS local)
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});


// 3. Evento Sync: Envia pedidos pendentes (Sem mudança, já estava correto)
self.addEventListener('sync', event => {
    if (event.tag === 'sync-novo-pedido') {
        console.log('[SW] Sincronizando novos pedidos...');
        event.waitUntil(enviarPedidosPendentes());
    }
});

// (Sem mudança, já estava correto)
async function enviarPedidosPendentes() {
    const pedidoPendente = await idbKeyval.get('pedido_pendente');

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
            } else {
                console.error('[SW] Falha ao enviar pedido:', response.statusText);
                throw new Error('Falha no servidor');
            }
        } catch (error) {
            console.error('[SW] Erro de rede ao enviar pedido:', error);
            throw error;
        }
    }
}

// 4. Evento Activate: Limpa caches antigos (Sem mudança)
self.addEventListener('activate', event => {
    console.log('[SW] Ativando...');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    // Atualizado para checar a nova versão do cache
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Limpando cache antigo:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});