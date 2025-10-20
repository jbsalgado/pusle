// Detecção de atualização do Service Worker
if ('serviceWorker' in navigator) {
    let refreshing = false;
    
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (refreshing) return;
        refreshing = true;
        console.log('[APP] Novo Service Worker ativado, recarregando página...');
        window.location.reload();
    });
    
    navigator.serviceWorker.register('sw.js')
        .then(registration => {
            console.log('Service Worker registrado:', registration.scope);
            
            setInterval(() => {
                registration.update();
            }, 60000);
            
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                console.log('[APP] Nova versão do SW detectada!');
                
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        console.log('[APP] Nova versão disponível!');
                        // Atualiza automaticamente sem perguntar
                        newWorker.postMessage({ type: 'SKIP_WAITING' });
                    }
                });
            });
        })
        .catch(error => {
            console.error('Falha ao registrar Service Worker:', error);
        });
}

console.log("app.js carregado. Adicionando listener DOMContentLoaded...");

document.addEventListener('DOMContentLoaded', () => {
    console.log("DOMContentLoaded disparado. Iniciando seletores...");

    // --- Seletores do DOM ---
    const catalogoContainer = document.getElementById('catalogo-produtos');
    const btnAbrirCarrinho = document.getElementById('btn-abrir-carrinho');
    const modalCarrinho = document.getElementById('modal-carrinho');
    const btnFecharCarrinho = document.getElementById('btn-fechar-carrinho');
    const btnFinalizarPedido = document.getElementById('btn-finalizar-pedido');
    const itensCarrinhoContainer = document.getElementById('itens-carrinho');
    const carrinhoVazioMsg = document.getElementById('carrinho-vazio-msg');
    const valorTotalCarrinho = document.getElementById('valor-total-carrinho');
    const contadorCarrinho = document.getElementById('contador-carrinho');
    const modalClientePedido = document.getElementById('modal-cliente-pedido');
    const btnFecharModalCliente = document.getElementById('btn-fechar-modal-cliente');
    const btnConfirmarPedido = document.getElementById('btn-confirmar-pedido');
    const inputClienteId = document.getElementById('cliente_id');
    const clienteNomeDisplay = document.getElementById('cliente-nome-display');
    const inputObservacoes = document.getElementById('observacoes');
    const inputParcelas = document.getElementById('numero_parcelas');

    const modalCadastroCliente = document.getElementById('modal-cadastro-cliente');
    const btnFecharModalCadastro = document.getElementById('btn-fechar-modal-cadastro');
    const btnCancelarCadastro = document.getElementById('btn-cancelar-cadastro');
    const btnSalvarCliente = document.getElementById('btn-salvar-cliente');
    const inputCadastroNome = document.getElementById('cadastro-nome');
    const inputCadastroCpf = document.getElementById('cadastro-cpf');
    const inputCadastroTelefone = document.getElementById('cadastro-telefone');
    const inputCadastroEmail = document.getElementById('cadastro-email');
    const cadastroClienteErros = document.getElementById('cadastro-cliente-erros');
    const cadastroClienteErroMsg = document.getElementById('cadastro-cliente-erro-msg');

    console.log("Seletores DOM concluídos.");

    // --- Configuração ---
    const URL_API = '/pulse/basic/web/index.php';
    const URL_BASE_WEB = '/pulse/basic/web';
    const API_PRODUTO_URL = `${URL_API}/api/produto`;
    const API_CLIENTE_URL = `${URL_API}/api/cliente`;
    const CACHE_NAME = 'catalogo-cache-v4';
    const CLIENTE_SIMULADO_ID = 'cliente-simulado-uuid';
    let carrinho = [];
    let idUsuarioLoja = null;
    let pedidoTemporario = {};

    console.log("Configuração concluída.");

    // --- Status Online/Offline ---
    const htmlTag = document.documentElement;
    function atualizarStatusOnline() {
        const isOnline = navigator.onLine;
        console.log(`Status: ${isOnline ? 'Online' : 'Offline'}`);
        if (isOnline) {
            htmlTag.classList.remove('offline');
            htmlTag.classList.add('online');
        } else {
            htmlTag.classList.remove('online');
            htmlTag.classList.add('offline');
        }
    }
    window.addEventListener('online', atualizarStatusOnline);
    window.addEventListener('offline', atualizarStatusOnline);
    console.log("Listeners de status online/offline adicionados.");

    // --- Funções ---

    async function carregarFormasPagamento() {
        console.log("Iniciando carregarFormasPagamento...");
        const selectFormaPagamento = document.getElementById('forma_pagamento');

        if (!selectFormaPagamento) {
            console.error('ERRO CRÍTICO: Elemento select#forma_pagamento não encontrado no HTML!');
            return;
        }

        if (!idUsuarioLoja) {
            console.error("Erro: ID do usuário da loja não definido.");
            selectFormaPagamento.innerHTML = '<option value="">Erro: Usuário não identificado</option>';
            selectFormaPagamento.disabled = true;
            return;
        }

        selectFormaPagamento.innerHTML = '<option value="">Carregando...</option>';
        selectFormaPagamento.disabled = true;
        try {
            const urlFormas = `${URL_API}/api/forma-pagamento?usuario_id=${idUsuarioLoja}`;
            console.log("Buscando formas de pagamento em:", urlFormas);

            const response = await fetch(urlFormas);
            console.log("Fetch forma-pagamento status:", response.status);
            if (!response.ok) {
                const errorBody = await response.text();
                console.error("Erro corpo:", errorBody);
                throw new Error(`Falha ao buscar formas de pagamento (Status: ${response.status})`);
            }

            const formas = await response.json();
            console.log("Formas recebidas:", formas);
            selectFormaPagamento.innerHTML = '<option value="">Selecione...</option>';
            if (formas && formas.length > 0) {
                formas.forEach(forma => {
                    const option = document.createElement('option');
                    option.value = forma.id;
                    option.textContent = forma.nome;
                    selectFormaPagamento.appendChild(option);
                });
                selectFormaPagamento.disabled = false;
            } else {
                console.warn("Nenhuma forma de pagamento encontrada.");
                selectFormaPagamento.innerHTML = '<option value="">Nenhuma opção disponível</option>';
                selectFormaPagamento.disabled = true;
            }
        } catch (error) {
            console.error('Erro ao carregar formas de pagamento:', error);
            selectFormaPagamento.innerHTML = '<option value="">Erro ao carregar</option>';
            selectFormaPagamento.disabled = true;
        }
        console.log("Finalizando carregarFormasPagamento.");
    }

    async function carregarProdutos() {
        console.log("Iniciando carregarProdutos...");
        try {
            const response = await fetch(API_PRODUTO_URL, {
                cache: 'no-cache'
            });
            console.log("Fetch produto status:", response.status);
            if (!response.ok) throw new Error(`Erro de rede: ${response.statusText}`);

            const data = await response.json();
            console.log("Dados recebidos da API (Produtos):", data);
            const produtos = data.items || data;

            if (!catalogoContainer) {
                console.error("ERRO: Elemento #catalogo-produtos não encontrado!");
                return;
            }
            catalogoContainer.innerHTML = '';

            if (!produtos || produtos.length === 0) {
                console.warn("Nenhum produto encontrado na API.");
                catalogoContainer.innerHTML = '<p class="col-span-full text-center text-gray-500">Nenhum produto disponível no momento.</p>';
                idUsuarioLoja = null;
                return;
            }

            if (produtos[0] && produtos[0].usuario_id) {
                idUsuarioLoja = produtos[0].usuario_id;
                console.log("ID do Usuário da Loja definido como:", idUsuarioLoja);
            } else {
                console.error("ERRO: Não foi possível determinar o ID do usuário da loja!");
                idUsuarioLoja = null;
            }

            produtos.forEach(produto => {
                let urlImagem = 'https://via.placeholder.com/300x300.png?text=Sem+Foto';
                if (produto.fotos && produto.fotos.length > 0 && produto.fotos[0].arquivo_path) {
                    urlImagem = `${URL_BASE_WEB}/${produto.fotos[0].arquivo_path}`;
                } else {
                    console.warn(`Produto "${produto.nome}" (ID: ${produto.id}) sem fotos válidas.`);
                }

                const card = document.createElement('div');
                card.className = 'bg-white rounded-lg shadow-md overflow-hidden flex flex-col';
                card.innerHTML = `
                    <div class="h-48 w-full overflow-hidden bg-gray-200">
                        <img src="${urlImagem}" alt="${produto.nome || 'Imagem do Produto'}" 
                             class="w-full h-full object-cover" 
                             onerror="this.src='https://via.placeholder.com/300x300.png?text=Erro+Img'; this.alt='Erro ao carregar imagem';">
                    </div>
                    <div class="p-4 flex flex-col flex-grow">
                        <h3 class="text-lg font-semibold text-gray-800 truncate" 
                            title="${produto.nome || ''}">${produto.nome || 'Produto sem nome'}</h3>
                        <p class="text-sm text-gray-500 mb-2 truncate" 
                           title="${produto.descricao || ''}">${produto.descricao || 'Sem descrição'}</p>
                        <p class="text-2xl font-bold text-blue-600 mb-4 mt-auto">
                            R$ ${parseFloat(produto.preco_venda_sugerido || 0).toFixed(2)}
                        </p>
                        <button
                            data-id="${produto.id}"
                            data-nome="${produto.nome || 'Produto'}"
                            data-preco="${produto.preco_venda_sugerido || 0}"
                            data-img="${urlImagem}"
                            class="w-full bg-blue-500 text-white p-2 rounded-lg font-semibold hover:bg-blue-600">
                            Adicionar
                        </button>
                    </div>
                `;
                catalogoContainer.appendChild(card);
            });

        } catch (error) {
            console.error('Falha CRÍTICA ao carregar produtos:', error);
            idUsuarioLoja = null;
            if (catalogoContainer) {
                catalogoContainer.innerHTML = '<p class="col-span-full text-center text-red-600 font-semibold">Erro grave ao carregar produtos. Tente recarregar a página.</p>';
            }
        }
        console.log("Finalizando carregarProdutos.");
    }

    function adicionarAoCarrinho(produto) {
        if (!produto || !produto.produto_id) {
            console.error("Tentativa de adicionar produto inválido ao carrinho:", produto);
            return;
        }
        const itemExistente = carrinho.find(item => item.produto_id === produto.produto_id);
        if (itemExistente) {
            itemExistente.quantidade++;
        } else {
            produto.quantidade = 1;
            carrinho.push(produto);
        }
        console.log('Carrinho atualizado:', carrinho);
        salvarCarrinhoLocal();
        atualizarModalCarrinho();
    }

    function atualizarModalCarrinho() {
        if (!itensCarrinhoContainer || !carrinhoVazioMsg || !valorTotalCarrinho || !btnFinalizarPedido || !contadorCarrinho) {
            console.error("Erro: Elementos do modal do carrinho não encontrados!");
            return;
        }

        if (carrinho.length === 0) {
            carrinhoVazioMsg.classList.remove('hidden');
            itensCarrinhoContainer.innerHTML = '';
            itensCarrinhoContainer.appendChild(carrinhoVazioMsg);
            btnFinalizarPedido.disabled = true;
            contadorCarrinho.classList.add('hidden');
            valorTotalCarrinho.textContent = 'R$ 0,00';
        } else {
            carrinhoVazioMsg.classList.add('hidden');
            itensCarrinhoContainer.innerHTML = '';
            let total = 0;

            carrinho.forEach((item, index) => {
                const preco = parseFloat(item.preco_unitario || 0);
                const qtd = parseInt(item.quantidade || 0, 10);
                const subtotal = preco * qtd;
                total += subtotal;

                const itemDiv = document.createElement('div');
                itemDiv.className = 'flex items-center justify-between py-2 border-b';
                itemDiv.innerHTML = `
                    <div class="flex items-center overflow-hidden mr-2">
                        <img src="${item.imagem || 'https://via.placeholder.com/48x48.png?text=?'}" 
                             alt="${item.nome || ''}" 
                             class="w-12 h-12 object-cover rounded mr-3 flex-shrink-0">
                        <div class="flex-grow overflow-hidden">
                            <p class="font-semibold truncate" title="${item.nome || ''}">${item.nome || 'Item sem nome'}</p>
                            <p class="text-sm text-gray-600">Qtd: ${qtd} x R$ ${preco.toFixed(2)}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="font-semibold text-right">R$ ${subtotal.toFixed(2)}</span>
                        <button data-index="${index}" class="text-red-500 hover:text-red-700 remover-item-carrinho p-1" title="Remover item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                `;
                itensCarrinhoContainer.appendChild(itemDiv);
            });

            valorTotalCarrinho.textContent = `R$ ${total.toFixed(2)}`;
            btnFinalizarPedido.disabled = false;

            contadorCarrinho.textContent = carrinho.reduce((acc, item) => acc + (item.quantidade || 0), 0);
            contadorCarrinho.classList.remove('hidden');
        }
    }

    async function salvarCarrinhoLocal() {
        try {
            await idbKeyval.set('carrinho_atual', carrinho);
        } catch (err) {
            console.error('Falha ao salvar carrinho no IndexedDB', err);
        }
    }

    async function carregarCarrinhoLocal() {
        console.log("Iniciando carregarCarrinhoLocal...");
        try {
            const carrinhoSalvo = await idbKeyval.get('carrinho_atual');
            if (carrinhoSalvo && Array.isArray(carrinhoSalvo)) {
                carrinho = carrinhoSalvo;
                console.log("Carrinho local carregado:", carrinho);
            } else {
                console.log("Nenhum carrinho local válido encontrado.");
                carrinho = [];
            }
        } catch(err) {
            console.error("Erro ao carregar carrinho do IndexedDB:", err);
            carrinho = [];
        }
        atualizarModalCarrinho();
        console.log("Finalizando carregarCarrinhoLocal.");
    }

    function removerDoCarrinho(index) {
        console.log(`Tentando remover item no índice: ${index}`);
        if (index >= 0 && index < carrinho.length) {
            carrinho.splice(index, 1);
            console.log("Carrinho após remoção:", carrinho);
            salvarCarrinhoLocal();
            atualizarModalCarrinho();
        } else {
            console.error(`Índice inválido para remoção: ${index}`);
        }
    }

    async function finalizarPedido(dadosPedido) {
        console.log("Iniciando finalizarPedido com dados:", dadosPedido);
        if (carrinho.length === 0) {
            alert('Carrinho está vazio!');
            return;
        }

        if (!dadosPedido.cliente_id || dadosPedido.cliente_id === CLIENTE_SIMULADO_ID) {
            console.error("ERRO: Tentativa de finalizar pedido sem um cliente_id válido!", dadosPedido.cliente_id);
            alert("Erro interno: ID do cliente inválido.");
            return;
        }

        const selectFormaPagamento = document.getElementById('forma_pagamento');
        if (!selectFormaPagamento) {
            console.error('ERRO CRÍTICO: Elemento select#forma_pagamento não encontrado ao finalizar!');
            alert('Erro interno: Não foi possível encontrar o campo de forma de pagamento.');
            return;
        }
        dadosPedido.forma_pagamento_id = selectFormaPagamento.value;

        if (!dadosPedido.forma_pagamento_id) {
            alert('Por favor, selecione a forma de pagamento.');
            selectFormaPagamento.focus();
            return;
        }

        const pedido = {
            cliente_id: dadosPedido.cliente_id,
            observacoes: dadosPedido.observacoes || null,
            numero_parcelas: parseInt(dadosPedido.numero_parcelas, 10) || 1,
            forma_pagamento_id: dadosPedido.forma_pagamento_id,
            itens: carrinho.map(item => ({
                produto_id: item.produto_id,
                quantidade: item.quantidade,
                preco_unitario: item.preco_unitario
            }))
        };
        console.log("Objeto Pedido a ser salvo:", pedido);

        try {
            await idbKeyval.set('pedido_pendente', pedido);
            console.log("Pedido salvo no IndexedDB.");
        } catch (err) {
            console.error('Falha ao salvar pedido no IndexedDB', err);
            alert('Erro ao salvar pedido localmente.');
            return;
        }

        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            try {
                const swReg = await navigator.serviceWorker.ready;
                console.log("Registrando sync tag: sync-novo-pedido");
                await swReg.sync.register('sync-novo-pedido');
                alert('Pedido salvo localmente! Ele será enviado assim que houver conexão.');
                modalCarrinho.classList.add('hidden');
                modalClientePedido.classList.add('hidden');
            } catch (err) {
                console.error('Falha ao registrar sync.', err);
                alert('Erro ao agendar envio. O pedido está salvo e será enviado mais tarde.');
                modalCarrinho.classList.add('hidden');
                modalClientePedido.classList.add('hidden');
            }
        } else {
            console.warn("SyncManager não suportado.");
            alert('Pedidos offline não totalmente suportados. O pedido está salvo.');
            modalCarrinho.classList.add('hidden');
            modalClientePedido.classList.add('hidden');
        }
        console.log("Finalizando finalizarPedido.");
    }

    function mostrarErroCadastro(mensagem) {
        if (cadastroClienteErros && cadastroClienteErroMsg) {
            cadastroClienteErroMsg.textContent = mensagem || "Ocorreu um erro desconhecido.";
            cadastroClienteErros.classList.remove('hidden');
        } else {
            console.error("Elementos de erro do cadastro não encontrados!");
            alert("Erro no cadastro: " + mensagem);
        }
    }

    function esconderErroCadastro() {
        if (cadastroClienteErros) {
            cadastroClienteErros.classList.add('hidden');
        }
    }

    async function limparDadosLocais() {
        console.log('Recebido SYNC_SUCCESS. Limpando carrinho local e cache de produtos...');
        carrinho = [];
        try {
            await idbKeyval.del('carrinho_atual');
            console.log('Carrinho local (IndexedDB) limpo.');
        } catch (err) {
            console.error('Erro ao limpar carrinho do IndexedDB:', err);
        }
        atualizarModalCarrinho();

        try {
            if ('caches' in window) {
                const cache = await caches.open(CACHE_NAME);
                const deleted = await cache.delete(API_PRODUTO_URL);
                if (deleted) {
                    console.log('Cache de produtos (API) limpo.');
                } else {
                    console.warn('Cache de produtos (API) não encontrado para limpeza.');
                }
            }
        } catch (err) {
            console.error('Erro ao limpar cache de produtos:', err);
        }

        alert('Pedido sincronizado com sucesso! Dados locais foram limpos.');
        if (navigator.onLine) {
            console.log('Recarregando produtos da API...');
            carregarProdutos();
        }
    }

    // --- Event Listeners ---
    console.log("Adicionando Event Listeners...");

    if (catalogoContainer) {
        catalogoContainer.addEventListener('click', event => {
            const botao = event.target.closest('button[data-id]');
            if (botao) {
                try {
                    const produto = {
                        produto_id: botao.dataset.id,
                        nome: botao.dataset.nome,
                        preco_unitario: parseFloat(botao.dataset.preco || 0),
                        imagem: botao.dataset.img
                    };
                    if (produto.produto_id && produto.nome && !isNaN(produto.preco_unitario)) {
                        adicionarAoCarrinho(produto);
                    } else {
                        console.error("Dados do produto incompletos:", produto);
                    }
                } catch (error) {
                    console.error("Erro no handler de clique:", error);
                }
            }
        });
    }

    if (itensCarrinhoContainer) {
        itensCarrinhoContainer.addEventListener('click', event => {
            const botaoRemover = event.target.closest('.remover-item-carrinho');
            if (botaoRemover && botaoRemover.dataset.index !== undefined) {
                const index = parseInt(botaoRemover.dataset.index, 10);
                if (!isNaN(index)) {
                    removerDoCarrinho(index);
                }
            }
        });
    }

    if (btnAbrirCarrinho) btnAbrirCarrinho.addEventListener('click', () => modalCarrinho.classList.remove('hidden'));
    if (btnFecharCarrinho) btnFecharCarrinho.addEventListener('click', () => modalCarrinho.classList.add('hidden'));

    if (btnFinalizarPedido) {
        btnFinalizarPedido.addEventListener('click', () => {
            modalCarrinho.classList.add('hidden');
            modalClientePedido.classList.remove('hidden');
            carregarFormasPagamento();
        });
    }

    if (btnFecharModalCliente) btnFecharModalCliente.addEventListener('click', () => modalClientePedido.classList.add('hidden'));

    if (btnConfirmarPedido) {
        btnConfirmarPedido.addEventListener('click', () => {
            const currentClienteId = inputClienteId.value;
            const selectFormaPagamento = document.getElementById('forma_pagamento');
            const currentObservacoes = inputObservacoes.value || null;
            const currentNumParcelas = parseInt(inputParcelas.value, 10) || 1;
            const currentFormaPgtoId = selectFormaPagamento ? selectFormaPagamento.value : null;

            if (!currentFormaPgtoId) {
                alert("Por favor, selecione a forma de pagamento.");
                if(selectFormaPagamento) selectFormaPagamento.focus();
                return;
            }

            if (currentClienteId === CLIENTE_SIMULADO_ID) {
                console.log("Cliente simulado detectado. Abrindo modal de cadastro...");
                pedidoTemporario = {
                    observacoes: currentObservacoes,
                    numero_parcelas: currentNumParcelas,
                    forma_pagamento_id: currentFormaPgtoId
                };

                if(modalCadastroCliente) {
                    inputCadastroNome.value = '';
                    inputCadastroCpf.value = '';
                    inputCadastroTelefone.value = '';
                    inputCadastroEmail.value = '';
                    esconderErroCadastro();
                    modalCadastroCliente.classList.remove('hidden');
                    inputCadastroNome.focus();
                } else {
                    console.error("Modal de cadastro não encontrado!");
                    alert("Funcionalidade de cadastro não implementada.");
                }
            } else {
                console.log("Cliente real detectado. Finalizando pedido...");
                const dadosPedido = {
                    cliente_id: currentClienteId,
                    observacoes: currentObservacoes,
                    numero_parcelas: currentNumParcelas,
                };
                finalizarPedido(dadosPedido);
            }
        });
    }

    if (btnFecharModalCadastro) {
        btnFecharModalCadastro.addEventListener('click', () => {
            modalCadastroCliente.classList.add('hidden');
        });
    }

    if (btnCancelarCadastro) {
        btnCancelarCadastro.addEventListener('click', () => {
            modalCadastroCliente.classList.add('hidden');
        });
    }

    if (btnSalvarCliente) {
        btnSalvarCliente.addEventListener('click', async () => {
            console.log("✅ Botão 'Salvar Cliente' CLICADO.");
            esconderErroCadastro();

            const nome = inputCadastroNome.value.trim();
            const cpf = inputCadastroCpf.value.trim();
            const telefone = inputCadastroTelefone.value.trim();
            const email = inputCadastroEmail.value.trim();

            if (!nome) {
                mostrarErroCadastro("O nome completo é obrigatório.");
                inputCadastroNome.focus();
                return;
            }
            if (!idUsuarioLoja) {
                mostrarErroCadastro("Erro interno: ID da loja não encontrado.");
                return;
            }

            const dadosNovoCliente = {
                usuario_id: idUsuarioLoja,
                nome_completo: nome,
                cpf: cpf || null,
                telefone: telefone || null,
                email: email || null
            };

            console.log("Enviando dados para cadastro:", dadosNovoCliente);
            btnSalvarCliente.disabled = true;
            btnSalvarCliente.textContent = 'Salvando...';

            try {
                const response = await fetch(API_CLIENTE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(dadosNovoCliente)
                });
                const responseData = await response.json();
                console.log("Resposta do cadastro:", response.status, responseData);

                if (response.ok && response.status === 201) {
                    console.log("Cliente cadastrado com sucesso:", responseData);
                    inputClienteId.value = responseData.id;
                    if (clienteNomeDisplay) clienteNomeDisplay.textContent = responseData.nome_completo;
                    modalCadastroCliente.classList.add('hidden');

                    const dadosPedidoFinal = {
                        cliente_id: responseData.id,
                        observacoes: pedidoTemporario.observacoes,
                        numero_parcelas: pedidoTemporario.numero_parcelas,
                    };
                    finalizarPedido(dadosPedidoFinal);
                } else if (response.status === 422) {
                    console.error("Erro de validação:", responseData.errors);
                    let errorMsg = "Por favor, corrija os seguintes erros:\n";
                    for (const field in responseData.errors) {
                        errorMsg += `- ${responseData.errors[field].join(', ')}\n`;
                    }
                    mostrarErroCadastro(errorMsg.trim());
                } else {
                    throw new Error(responseData.message || `Erro ${response.status} ao cadastrar cliente.`);
                }
            } catch (error) {
                console.error("Falha na requisição de cadastro:", error);
                mostrarErroCadastro("Não foi possível conectar ao servidor. Verifique sua conexão.");
            } finally {
                btnSalvarCliente.disabled = false;
                btnSalvarCliente.textContent = 'Salvar Cliente e Continuar';
            }
        });
        console.log("✅ Listener adicionado para btnSalvarCliente.");
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', event => {
            console.log("Mensagem recebida do SW:", event.data);
            if (event.data && event.data.type === 'SYNC_SUCCESS') {
                limparDadosLocais();
            }
        });
    }

    console.log("EventListeners adicionados.");

    // --- Inicialização ---
    (async () => {
        try {
            console.log("Iniciando aplicação...");
            atualizarStatusOnline();
            await carregarProdutos();
            await carregarCarrinhoLocal();
            console.log("Inicialização concluída.");
        } catch (error) {
            console.error("ERRO NA INICIALIZAÇÃO:", error);
        }
    })();

});

console.log("app.js finalizado.");