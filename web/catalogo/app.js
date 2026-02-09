// Detecção de atualização do Service Worker
if ('serviceWorker' in navigator) {
    let refreshing = false;
    
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (refreshing) return;
        refreshing = true;
        console.log('[APP] Novo Service Worker ativado, recarregando pagina...');
        window.location.reload();
    });
    
    navigator.serviceWorker.register('sw.js')
        .then(registration => {
            console.log('Service Worker registrado:', registration.scope);
            setInterval(() => { registration.update(); }, 60000);
            
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                console.log('[APP] Nova versao do SW detectada!');
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        console.log('[APP] Nova versao disponivel!');
                        newWorker.postMessage({ type: 'SKIP_WAITING' });
                    }
                });
            });
        })
        .catch(error => console.error('Falha ao registrar Service Worker:', error));
}

document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM Carregado. Iniciando...");

    // --- VERIFICACAO DE ELEMENTOS CRITICOS ---
    const elementosCriticos = [
        'catalogo-produtos',
        'btn-abrir-carrinho',
        'modal-carrinho',
        'modal-cliente-pedido'
    ];

    for (const id of elementosCriticos) {
        if (!document.getElementById(id)) {
            console.error(`Elemento critico nao encontrado: ${id}`);
        }
    }

    // --- SELETORES ---
    const catalogoContainer = document.getElementById('catalogo-produtos');
    const btnAbrirCarrinho = document.getElementById('btn-abrir-carrinho');
    const modalCarrinho = document.getElementById('modal-carrinho');
    const btnFecharCarrinho = document.getElementById('btn-fechar-carrinho');
    const btnFinalizarPedido = document.getElementById('btn-finalizar-pedido');
    const itensCarrinhoContainer = document.getElementById('itens-carrinho');
    const valorTotalCarrinho = document.getElementById('valor-total-carrinho');
    const contadorCarrinho = document.getElementById('contador-carrinho');
    
    // Modal Detalhes do Pedido
    const modalClientePedido = document.getElementById('modal-cliente-pedido');
    const btnFecharModalCliente = document.getElementById('btn-fechar-modal-cliente');
    const btnBuscarCliente = document.getElementById('btn-buscar-cliente');
    const inputClienteCpfBusca = document.getElementById('cliente-cpf-busca');
    const clienteInfoResultado = document.getElementById('cliente-info-resultado');
    const inputClienteId = document.getElementById('cliente_id');
    const btnConfirmarPedido = document.getElementById('btn-confirmar-pedido');
    const inputObservacoes = document.getElementById('observacoes');
    const inputParcelas = document.getElementById('numero_parcelas');
    const parcelaInfoResultado = document.getElementById('parcela-info-resultado');
    
    // Seletores de Vendedor (Request 2)
    const radioTipoVendaCliente = document.getElementById('tipo_venda_cliente');
    const radioTipoVendaVendedor = document.getElementById('tipo_venda_vendedor');
    const campoVendedorCpf = document.getElementById('campo-vendedor-cpf');
    const inputVendedorCpfBusca = document.getElementById('vendedor_cpf_busca');
    const btnBuscarVendedor = document.getElementById('btn-buscar-vendedor');
    const vendedorInfoResultado = document.getElementById('vendedor-info-resultado');
    const inputColaboradorVendedorId = document.getElementById('colaborador_vendedor_id');

    // Modal Login
    const modalLoginCliente = document.getElementById('modal-login-cliente');
    const btnFecharModalLogin = document.getElementById('btn-fechar-modal-login');
    const btnCancelarLogin = document.getElementById('btn-cancelar-login');
    const btnFazerLogin = document.getElementById('btn-fazer-login');
    const inputLoginSenha = document.getElementById('login-senha');
    const loginClienteNome = document.getElementById('login-cliente-nome');
    const loginClienteErros = document.getElementById('login-cliente-erros');
    const loginClienteErroMsg = document.getElementById('login-cliente-erro-msg');

    // Modal Cadastro
    const modalCadastroCliente = document.getElementById('modal-cadastro-cliente');
    const btnFecharModalCadastro = document.getElementById('btn-fechar-modal-cadastro');
    const btnCancelarCadastro = document.getElementById('btn-cancelar-cadastro');
    const btnSalvarCliente = document.getElementById('btn-salvar-cliente');
    const inputCadastroNome = document.getElementById('cadastro-nome');
    const inputCadastroCpf = document.getElementById('cadastro-cpf');
    const inputCadastroTelefone = document.getElementById('cadastro-telefone');
    const inputCadastroEmail = document.getElementById('cadastro-email');
    const inputCadastroSenha = document.getElementById('cadastro-senha');
    const inputCadastroLogradouro = document.getElementById('cadastro-logradouro');
    const inputCadastroNumero = document.getElementById('cadastro-numero');
    const inputCadastroComplemento = document.getElementById('cadastro-complemento');
    const inputCadastroBairro = document.getElementById('cadastro-bairro');
    const inputCadastroCidade = document.getElementById('cadastro-cidade');
    const inputCadastroEstado = document.getElementById('cadastro-estado');
    const inputCadastroCep = document.getElementById('cadastro-cep');
    const cadastroClienteErros = document.getElementById('cadastro-cliente-erros');
    const cadastroClienteErroMsg = document.getElementById('cadastro-cliente-erro-msg');

    // --- CONFIGURACAO ---
    const URL_API = '/pulse/basic/web/index.php';
    const URL_BASE_WEB = '/pulse/basic/web';
    const API_PRODUTO_URL = `${URL_API}/api/produto`;
    const API_CLIENTE_URL = `${URL_API}/api/cliente`;
    const API_CLIENTE_BUSCA_CPF_URL = `${URL_API}/api/cliente/buscar-cpf`;
    const API_CLIENTE_LOGIN_URL = `${URL_API}/api/cliente/login`;
    const API_CALCULO_PARCELA_URL = `${URL_API}/api/calculo/calcular-parcelas`;
    const API_COLABORADOR_BUSCA_CPF_URL = `${URL_API}/api/colaborador/buscar-cpf`;
    const CACHE_NAME = 'catalogo-cache-v4';
    
    let carrinho = [];
    let idUsuarioLoja = null;
    let clienteAtual = null;

    // --- STATUS ONLINE/OFFLINE ---
    const htmlTag = document.documentElement;
    function atualizarStatusOnline() {
        const isOnline = navigator.onLine;
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

    // Verificar se produto esta no carrinho
    function produtoEstaNoCarrinho(produtoId) {
        return carrinho.some(item => item.produto_id === produtoId);
    }

    // Atualizar indicadores visuais dos cards
    function atualizarIndicadoresCarrinho() {
        carrinho.forEach(item => {
            const card = document.querySelector(`[data-produto-card="${item.produto_id}"]`);
            if (card) {
                const badge = card.querySelector('.badge-no-carrinho');
                if (badge) {
                    badge.classList.remove('hidden');
                }
            }
        });
    }

    async function carregarFormasPagamento() {
        const selectFormaPagamento = document.getElementById('forma_pagamento');
        if (!selectFormaPagamento || !idUsuarioLoja) return;

        selectFormaPagamento.innerHTML = '<option value="">Carregando...</option>';
        selectFormaPagamento.disabled = true;
        
        try {
            const response = await fetch(`${URL_API}/api/forma-pagamento?usuario_id=${idUsuarioLoja}`);
            if (!response.ok) throw new Error(`Status: ${response.status}`);

            const formas = await response.json();
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
                selectFormaPagamento.innerHTML = '<option value="">Nenhuma opcao disponivel</option>';
            }
        } catch (error) {
            console.error('Erro ao carregar formas de pagamento:', error);
            selectFormaPagamento.innerHTML = '<option value="">Erro ao carregar</option>';
        }
    }

    async function carregarProdutos() {
        try {
            const response = await fetch(API_PRODUTO_URL, { cache: 'no-cache' });
            if (!response.ok) throw new Error(`Erro: ${response.statusText}`);

            const data = await response.json();
            const produtos = data.items || data;

            if (!produtos || produtos.length === 0) {
                catalogoContainer.innerHTML = '<p class="col-span-full text-center text-gray-500">Nenhum produto disponivel.</p>';
                return;
            }

            if (produtos[0] && produtos[0].usuario_id) {
                idUsuarioLoja = produtos[0].usuario_id;
                console.log("ID da loja:", idUsuarioLoja);
            }

            catalogoContainer.innerHTML = '';
            produtos.forEach(produto => {
                let urlImagem = 'https://via.placeholder.com/300x300.png?text=Sem+Foto';
                if (produto.fotos && produto.fotos.length > 0 && produto.fotos[0].arquivo_path) {
                    urlImagem = `${URL_BASE_WEB}/${produto.fotos[0].arquivo_path}`;
                }

                const estoque = parseInt(produto.estoque_atual || 0);
                const temEstoque = estoque > 0;
                const estaNoCarrinho = produtoEstaNoCarrinho(produto.id);

                const card = document.createElement('div');
                card.className = 'bg-white rounded-lg shadow-md overflow-hidden flex flex-col relative';
                card.setAttribute('data-produto-card', produto.id);
                
                card.innerHTML = `
                    <div class="badge-no-carrinho absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg flex items-center gap-1 z-10 ${estaNoCarrinho ? '' : 'hidden'}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        No Carrinho
                    </div>

                    ${!temEstoque ? `
                    <div class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg z-10">
                        Sem Estoque
                    </div>
                    ` : ''}

                    <div class="h-48 w-full overflow-hidden bg-gray-100 ${!temEstoque ? 'opacity-60' : ''}">
                        <img src="${urlImagem}" alt="${produto.nome || 'Produto'}" class="w-full h-full object-contain" onerror="this.src='https://via.placeholder.com/300x300.png?text=Erro';">
                    </div>
                    <div class="p-4 flex flex-col flex-grow">
                        <h3 class="text-lg font-semibold text-gray-800 truncate">${produto.nome || 'Produto'}</h3>
                        <p class="text-sm text-gray-500 mb-2 truncate">${produto.descricao || 'Sem descricao'}</p>
                        
                        <p class="text-xs ${temEstoque ? 'text-green-600' : 'text-red-600'} mb-2">
                            ${temEstoque ? `${estoque} unidade(s) disponivel` : 'Indisponivel'}
                        </p>
                        
                        <p class="text-2xl font-bold text-blue-600 mb-4 mt-auto">R$ ${parseFloat(produto.preco_venda_sugerido || 0).toFixed(2)}</p>
                        
                        <div class="flex items-center gap-2">
                            <input 
                                type="number" 
                                id="qty-produto-${produto.id}" 
                                class="w-16 p-2 border border-gray-300 rounded-lg text-center ${!temEstoque ? 'bg-gray-100 cursor-not-allowed' : ''}" 
                                value="1" 
                                min="1"
                                max="${estoque}"
                                aria-label="Quantidade"
                                ${!temEstoque ? 'disabled' : ''}
                            >
                            <button 
                                data-id="${produto.id}" 
                                data-nome="${produto.nome || 'Produto'}" 
                                data-preco="${produto.preco_venda_sugerido || 0}" 
                                data-img="${urlImagem}" 
                                data-estoque="${estoque}"
                                class="btn-adicionar-carrinho w-full p-2 rounded-lg font-semibold transition-colors
                                    ${!temEstoque ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-blue-500 text-white hover:bg-blue-600'}"
                                ${!temEstoque ? 'disabled' : ''}
                            >
                                ${!temEstoque ? 'Indisponivel' : 'Adicionar'}
                            </button>
                        </div>
                    </div>
                `;
                
                catalogoContainer.appendChild(card);
            });
        } catch (error) {
            console.error('Erro ao carregar produtos:', error);
            if (catalogoContainer) {
                catalogoContainer.innerHTML = '<p class="col-span-full text-center text-red-600">Erro ao carregar produtos.</p>';
            }
        }
    }

    function adicionarAoCarrinho(produto, quantidade) {
        if (!produto || !produto.produto_id || !quantidade || quantidade <= 0) return;
        
        const itemExistente = carrinho.find(item => item.produto_id === produto.produto_id);
        
        if (itemExistente) {
            alert('Este item ja esta no seu carrinho.');
            return;
        } else {
            produto.quantidade = quantidade;
            carrinho.push(produto);
        }
        
        salvarCarrinhoLocal();
        atualizarModalCarrinho();
        
        const card = document.querySelector(`[data-produto-card="${produto.produto_id}"]`);
        if (card) {
            const badge = card.querySelector('.badge-no-carrinho');
            if (badge) {
                badge.classList.remove('hidden');
            }
        }
    }

    function atualizarModalCarrinho() {
        const itensCarrinhoContainer = document.getElementById('itens-carrinho');
        
        if (carrinho.length === 0) {
            itensCarrinhoContainer.innerHTML = '';

            const msgVazia = document.createElement('div');
            msgVazia.id = 'carrinho-vazio-msg';
            msgVazia.className = 'text-center text-gray-500 py-12 flex flex-col items-center justify-center h-full'; 
            msgVazia.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <p class="text-lg font-medium">Seu carrinho esta vazio</p>
                <p class="text-sm text-gray-400 mt-1">Adicione produtos para comecar</p>
            `;
            itensCarrinhoContainer.appendChild(msgVazia);
            
            btnFinalizarPedido.disabled = true;
            contadorCarrinho.classList.add('hidden');
            valorTotalCarrinho.textContent = 'R$ 0,00';
            
            const totalItensFooter = document.getElementById('total-itens-footer');
            if (totalItensFooter) totalItensFooter.textContent = '0';
        } else {
            itensCarrinhoContainer.innerHTML = '';
            let total = 0;

            carrinho.forEach((item, index) => {
                const preco = parseFloat(item.preco_unitario || 0);
                const qtd = parseInt(item.quantidade || 0, 10);
                const subtotal = preco * qtd;
                total += subtotal;

                const cardItem = document.createElement('div');
                cardItem.className = 'bg-white rounded-xl shadow-md border border-gray-200 p-4 relative hover:shadow-xl transition-shadow';
                
                const btnRemover = document.createElement('button');
                btnRemover.setAttribute('data-index', index);
                btnRemover.className = 'remover-item-carrinho absolute top-2 right-2 w-8 h-8 flex items-center justify-center rounded-full bg-red-50 hover:bg-red-100 text-red-500 transition-all z-10';
                btnRemover.title = 'Remover item';
                btnRemover.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" /></svg>';
                cardItem.appendChild(btnRemover);
                
                const imgContainer = document.createElement('div');
                imgContainer.className = 'flex justify-center items-center mb-3 bg-gray-50 rounded-lg p-3';
                const img = document.createElement('img');
                img.src = item.imagem || 'https://via.placeholder.com/150';
                img.alt = item.nome || '';
                img.className = 'w-32 h-32 object-contain';
                imgContainer.appendChild(img);
                cardItem.appendChild(imgContainer);
                
                const nomeProduto = document.createElement('h3');
                nomeProduto.className = 'text-center font-semibold text-gray-800 text-sm mb-3 px-2 line-clamp-2 min-h-[2.5rem]';
                nomeProduto.textContent = item.nome || 'Item';
                nomeProduto.title = item.nome || 'Item';
                cardItem.appendChild(nomeProduto);
                
                const controlesQtd = document.createElement('div');
                controlesQtd.className = 'flex items-center justify-center gap-3 mb-4';
                
                const btnDiminuir = document.createElement('button');
                btnDiminuir.setAttribute('data-index', index);
                btnDiminuir.className = 'btn-diminuir-item w-10 h-10 flex items-center justify-center rounded-full bg-red-500 hover:bg-red-600 text-white font-bold text-xl disabled:bg-gray-300 disabled:cursor-not-allowed transition-all shadow-md active:scale-95';
                btnDiminuir.textContent = '-';
                btnDiminuir.title = 'Diminuir quantidade';
                if (qtd <= 1) btnDiminuir.disabled = true;
                
                const badgeQtd = document.createElement('div');
                badgeQtd.className = 'w-12 h-12 flex items-center justify-center rounded-full bg-yellow-400 border-4 border-yellow-500 shadow-lg';
                const spanQtd = document.createElement('span');
                spanQtd.className = 'font-bold text-gray-900 text-xl';
                spanQtd.textContent = qtd;
                badgeQtd.appendChild(spanQtd);
                
                const btnAumentar = document.createElement('button');
                btnAumentar.setAttribute('data-index', index);
                btnAumentar.className = 'btn-aumentar-item w-10 h-10 flex items-center justify-center rounded-full bg-green-500 hover:bg-green-600 text-white font-bold text-xl transition-all shadow-md active:scale-95';
                btnAumentar.textContent = '+';
                btnAumentar.title = 'Aumentar quantidade';
                
                controlesQtd.appendChild(btnDiminuir);
                controlesQtd.appendChild(badgeQtd);
                controlesQtd.appendChild(btnAumentar);
                cardItem.appendChild(controlesQtd);
                
                const valorContainer = document.createElement('div');
                valorContainer.className = 'text-center border-t pt-3';
                
                const labelValor = document.createElement('p');
                labelValor.className = 'text-xs text-gray-600 mb-1 font-medium';
                labelValor.textContent = 'Val. do Item';
                
                const valorItem = document.createElement('p');
                valorItem.className = 'text-2xl font-bold text-gray-900';
                valorItem.textContent = `R$ ${subtotal.toFixed(2)}`;
                
                valorContainer.appendChild(labelValor);
                valorContainer.appendChild(valorItem);
                cardItem.appendChild(valorContainer);
                
                const precoUnitario = document.createElement('p');
                precoUnitario.className = 'text-center text-xs text-gray-500 mt-2';
                precoUnitario.textContent = `R$ ${preco.toFixed(2)} / unidade`;
                cardItem.appendChild(precoUnitario);
                
                itensCarrinhoContainer.appendChild(cardItem);
            });

            valorTotalCarrinho.textContent = `R$ ${total.toFixed(2)}`;
            btnFinalizarPedido.disabled = false;
            
            const totalItens = carrinho.reduce((acc, item) => acc + (item.quantidade || 0), 0);
            contadorCarrinho.textContent = totalItens;
            contadorCarrinho.classList.remove('hidden');
            
            const totalItensFooter = document.getElementById('total-itens-footer');
            if (totalItensFooter) totalItensFooter.textContent = totalItens;
        }
    }

    async function salvarCarrinhoLocal() {
        try {
            await idbKeyval.set('carrinho_atual', carrinho);
        } catch (err) {
            console.error('Erro ao salvar carrinho:', err);
        }
    }

    async function carregarCarrinhoLocal() {
        try {
            const carrinhoSalvo = await idbKeyval.get('carrinho_atual');
            if (carrinhoSalvo && Array.isArray(carrinhoSalvo)) {
                carrinho = carrinhoSalvo;
            } else {
                carrinho = [];
            }
        } catch(err) {
            console.error("Erro ao carregar carrinho:", err);
            carrinho = [];
        }
        atualizarModalCarrinho();
        atualizarIndicadoresCarrinho();
    }

    function removerDoCarrinho(index) {
        if (index >= 0 && index < carrinho.length) {
            const produtoId = carrinho[index].produto_id;
            carrinho.splice(index, 1);
            salvarCarrinhoLocal();
            atualizarModalCarrinho();
            
            const card = document.querySelector(`[data-produto-card="${produtoId}"]`);
            if (card) {
                const badge = card.querySelector('.badge-no-carrinho');
                if (badge) {
                    badge.classList.add('hidden');
                }
            }
        }
    }

    function aumentarQuantidadeItem(index) {
        if (index >= 0 && index < carrinho.length) {
            carrinho[index].quantidade++;
            salvarCarrinhoLocal();
            atualizarModalCarrinho();
        }
    }

    function diminuirQuantidadeItem(index) {
        if (index >= 0 && index < carrinho.length) {
            if (carrinho[index].quantidade > 1) { 
                carrinho[index].quantidade--;
                salvarCarrinhoLocal();
                atualizarModalCarrinho();
            }
        }
    }

    // === FUNÇÃO DE CÁLCULO DE PARCELAS ===
    async function atualizarCalculoParcelas() {
        if (!parcelaInfoResultado || !idUsuarioLoja) {
            return;
        }

        const numeroParcelas = parseInt(inputParcelas.value, 10);
        
        if (numeroParcelas <= 1) {
            parcelaInfoResultado.innerHTML = '';
            parcelaInfoResultado.style.display = 'none';
            return;
        }

        const valorBase = carrinho.reduce((total, item) => {
            return total + (parseFloat(item.preco_unitario || 0) * parseInt(item.quantidade || 0));
        }, 0);

        if (valorBase <= 0) {
            parcelaInfoResultado.innerHTML = '';
            parcelaInfoResultado.style.display = 'none';
            return;
        }

        parcelaInfoResultado.innerHTML = 'Calculando...';
        parcelaInfoResultado.style.display = 'block';

        try {
            const url = `${API_CALCULO_PARCELA_URL}?valor_base=${valorBase}&numero_parcelas=${numeroParcelas}&usuario_id=${idUsuarioLoja}`;
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`Erro ${response.status} ao calcular parcelas.`);
            }

            const data = await response.json();

            const valorParcelaFmt = data.valor_parcela.toFixed(2).replace('.', ',');
            const valorTotalFmt = data.valor_total_prazo.toFixed(2).replace('.', ',');

            if (data.acrescimo_percentual > 0) {
                parcelaInfoResultado.innerHTML = `
                    <span class="font-bold text-blue-600">${data.numero_parcelas}x de R$ ${valorParcelaFmt}</span>
                    <br>
                    <span class="text-xs">(Total a prazo: R$ ${valorTotalFmt})</span>
                `;
            } else {
                parcelaInfoResultado.innerHTML = `
                    <span class="font-bold text-blue-600">${data.numero_parcelas}x de R$ ${valorParcelaFmt}</span>
                    <br>
                    <span class="text-xs">(Total: R$ ${valorTotalFmt}, sem acréscimo)</span>
                `;
            }

        } catch (error) {
            console.error('Erro ao calcular parcelas:', error);
            parcelaInfoResultado.innerHTML = '<span class="text-red-500 text-xs">Não foi possível calcular o valor da parcela.</span>';
        }
    }

    // === FUNÇÃO PARA ALTERNAR CAMPO DE VENDEDOR ===
    function alternarCampoVendedor() {
        if (radioTipoVendaVendedor && radioTipoVendaVendedor.checked) {
            campoVendedorCpf.classList.remove('hidden');
        } else {
            campoVendedorCpf.classList.add('hidden');
            if (inputVendedorCpfBusca) inputVendedorCpfBusca.value = '';
            if (inputColaboradorVendedorId) inputColaboradorVendedorId.value = '';
            if (vendedorInfoResultado) vendedorInfoResultado.innerHTML = '';
        }
    }

    // === FUNÇÃO PARA BUSCAR VENDEDOR POR CPF ===
    async function buscarVendedorPorCpf() {
        if (!inputVendedorCpfBusca) {
            console.error('Elemento inputVendedorCpfBusca não encontrado');
            alert('Erro interno: campo de CPF do vendedor não encontrado.');
            return;
        }

        const cpfOriginalComMascara = inputVendedorCpfBusca.value.trim();
        const cpf = cpfOriginalComMascara.replace(/[^\d]/g, '');

        if (!cpf) {
            alert('Digite o CPF do vendedor para buscar.');
            return;
        }

        if (!validarCPF(cpf)) {
            alert('CPF inválido. Verifique os dígitos e tente novamente.');
            inputVendedorCpfBusca.focus();
            return;
        }
        
        if (!idUsuarioLoja) {
            console.error('ID da loja não identificado');
            alert('Erro: ID da loja não identificado.');
            return;
        }

        console.log('Buscando vendedor com CPF:', cpf);

        btnBuscarVendedor.disabled = true;
        btnBuscarVendedor.textContent = 'Buscando...';

        try {
            const response = await fetch(`${API_COLABORADOR_BUSCA_CPF_URL}?cpf=${cpf}&usuario_id=${idUsuarioLoja}`);
            const data = await response.json();

            if (response.ok && data.existe) {
                console.log('Vendedor encontrado:', data.colaborador);
                
                if (inputColaboradorVendedorId) {
                    inputColaboradorVendedorId.value = data.colaborador.id;
                }
                
                if (vendedorInfoResultado) {
                    vendedorInfoResultado.innerHTML = `
                        <p class="text-green-600 font-semibold">✓ Vendedor: ${data.colaborador.nome_completo}</p>
                        <p class="text-gray-600 text-xs">CPF: ${formatarCPF(cpf)}</p>
                    `;
                }
                
            } else if (response.ok && !data.existe) {
                if (inputColaboradorVendedorId) inputColaboradorVendedorId.value = '';
                if (vendedorInfoResultado) {
                    vendedorInfoResultado.innerHTML = `
                        <p class="text-red-600 font-semibold">✗ Vendedor não encontrado</p>
                        <p class="text-gray-600 text-xs">Verifique o CPF ou cadastre o vendedor no sistema</p>
                    `;
                }
                alert('Vendedor não encontrado. Verifique o CPF ou cadastre-o no sistema.');
            } else {
                throw new Error('Erro ao buscar vendedor.');
            }
        } catch (error) {
            console.error('Erro na busca do vendedor:', error);
            alert('Não foi possível buscar o vendedor. Verifique sua conexão.');
            if (vendedorInfoResultado) {
                vendedorInfoResultado.innerHTML = '<p class="text-red-600 text-xs">Erro ao buscar vendedor</p>';
            }
        } finally {
            btnBuscarVendedor.disabled = false;
            btnBuscarVendedor.textContent = 'Buscar';
        }
    }

    // CONTINUA NA PARTE 3...
    async function buscarClientePorCpf() {
        if (modalClientePedido?.classList.contains('hidden')) {
            console.warn('Modal de pedido nao esta aberto');
            return;
        }

        if (!inputClienteCpfBusca) {
            console.error('Elemento inputClienteCpfBusca nao encontrado');
            alert('Erro interno: campo de CPF nao encontrado.');
            return;
        }

        const cpfOriginalComMascara = inputClienteCpfBusca.value.trim();
        const cpf = cpfOriginalComMascara.replace(/[^\d]/g, '');

        if (!cpf) {
            alert('Digite o CPF para buscar.');
            return;
        }

        if (!validarCPF(cpf)) {
            alert('CPF invalido. Verifique os digitos e tente novamente.');
            inputClienteCpfBusca.focus();
            return;
        }
        
        if (!idUsuarioLoja) {
            console.error('ID da loja nao identificado');
            alert('Erro: ID da loja nao identificado.');
            return;
        }

        console.log('Buscando cliente com CPF:', cpf);

        btnBuscarCliente.disabled = true;
        btnBuscarCliente.textContent = 'Buscando...';

        try {
            const response = await fetch(`${API_CLIENTE_BUSCA_CPF_URL}?cpf=${cpf}&usuario_id=${idUsuarioLoja}`);
            const data = await response.json();

            if (response.ok && data.existe) {
                console.log('Cliente encontrado:', data.cliente);
                clienteAtual = data.cliente;
                loginClienteNome.textContent = clienteAtual.nome_completo;
                modalClientePedido.classList.add('hidden');
                modalLoginCliente.classList.remove('hidden');
                if (inputLoginSenha) {
                    inputLoginSenha.value = '';
                    inputLoginSenha.focus();
                }
                esconderErroLogin();
            } else if (response.ok && !data.existe) {
                console.log('Cliente nao existe, abrindo formulario de cadastro');
                clienteAtual = null;
                if (inputCadastroCpf) {
                     inputCadastroCpf.value = cpf;
                     maskCPF(inputCadastroCpf);
                }
                limparFormularioCadastro();
                modalClientePedido.classList.add('hidden');
                modalCadastroCliente.classList.remove('hidden');
                if (inputCadastroNome) inputCadastroNome.focus();
            } else {
                throw new Error('Erro ao buscar cliente.');
            }
        } catch (error) {
            console.error('Erro na busca:', error);
            alert('Nao foi possivel buscar o cliente. Verifique sua conexao.');
        } finally {
            btnBuscarCliente.disabled = false;
            btnBuscarCliente.textContent = 'Buscar';
        }
    }
    
    async function fazerLoginCliente() {
        if (modalLoginCliente?.classList.contains('hidden')) {
            console.warn('Modal de login nao esta aberto');
            return;
        }

        if (!inputLoginSenha) {
            console.error('Elemento inputLoginSenha nao encontrado');
            alert('Erro interno: campo de senha nao encontrado.');
            return;
        }

        const senha = inputLoginSenha.value.trim();
        
        if (!senha) {
            mostrarErroLogin('Digite a senha.');
            return;
        }

        btnFazerLogin.disabled = true;
        btnFazerLogin.textContent = 'Entrando...';
        esconderErroLogin();

        try {
            const response = await fetch(API_CLIENTE_LOGIN_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cpf: clienteAtual.cpf,
                    senha: senha,
                    usuario_id: idUsuarioLoja
                })
            });

            const data = await response.json();

            if (response.ok) {
                console.log('Login bem-sucedido para cliente:', data.cliente.nome_completo);
                clienteAtual = data.cliente;
                if (inputClienteId) inputClienteId.value = clienteAtual.id;
                
                console.log('Cliente ID definido:', clienteAtual.id);
                
                if (clienteInfoResultado) {
                    clienteInfoResultado.innerHTML = `
                        <p class="text-green-600 font-semibold">Cliente: ${clienteAtual.nome_completo}</p>
                        <p class="text-gray-600 text-xs">CPF: ${formatarCPF(clienteAtual.cpf)}</p>
                    `;
                    clienteInfoResultado.classList.remove('hidden');
                }
                
                if (btnConfirmarPedido) btnConfirmarPedido.disabled = false;
                
                modalLoginCliente.classList.add('hidden');
                modalClientePedido.classList.remove('hidden');

                atualizarCalculoParcelas();
                
            } else if (response.status === 401) {
                mostrarErroLogin('Senha incorreta. Tente novamente.');
            } else {
                throw new Error(data.message || 'Erro ao fazer login.');
            }
        } catch (error) {
            console.error('Erro no login:', error);
            mostrarErroLogin('Nao foi possivel fazer login. Tente novamente.');
        } finally {
            btnFazerLogin.disabled = false;
            btnFazerLogin.textContent = 'Entrar';
        }
    }

    function mostrarErroLogin(mensagem) {
        if (loginClienteErroMsg) loginClienteErroMsg.textContent = mensagem;
        if (loginClienteErros) loginClienteErros.classList.remove('hidden');
    }

    function esconderErroLogin() {
        if (loginClienteErros) loginClienteErros.classList.add('hidden');
    }

    function mostrarErroCadastro(mensagem) {
        if (cadastroClienteErroMsg) cadastroClienteErroMsg.textContent = mensagem;
        if (cadastroClienteErros) cadastroClienteErros.classList.remove('hidden');
    }

    function esconderErroCadastro() {
        if (cadastroClienteErros) cadastroClienteErros.classList.add('hidden');
    }

    function limparFormularioCadastro() {
        if (inputCadastroNome) inputCadastroNome.value = '';
        if (inputCadastroTelefone) inputCadastroTelefone.value = '';
        if (inputCadastroEmail) inputCadastroEmail.value = '';
        if (inputCadastroSenha) inputCadastroSenha.value = '';
        if (inputCadastroLogradouro) inputCadastroLogradouro.value = '';
        if (inputCadastroNumero) inputCadastroNumero.value = '';
        if (inputCadastroComplemento) inputCadastroComplemento.value = '';
        if (inputCadastroBairro) inputCadastroBairro.value = '';
        if (inputCadastroCidade) inputCadastroCidade.value = '';
        if (inputCadastroEstado) inputCadastroEstado.value = '';
        if (inputCadastroCep) inputCadastroCep.value = '';
        esconderErroCadastro();
    }

    function formatarCPF(cpf) {
        if (!cpf) return '';
        cpf = String(cpf).replace(/[^\d]/g, '');
        if (cpf.length !== 11) return cpf;
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }

    function maskCPF(input) {
        let value = input.value.replace(/[^\d]/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = value;
    }

    function maskPhone(input) {
        let value = input.value.replace(/[^\d]/g, '');
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        if (value.length > 14) {
            value = value.replace(/(\d{5})(\d{4}).*/, '$1-$2');
        } else if (value.length > 10) {
             value = value.replace(/(\d{4})(\d{4}).*/, '$1-$2');
        }
        if (value.length === 14 && value.length > 13) {
             value = value.replace(/(\d{4})-(\d{4})/, '$1$2');
             value = value.replace(/(\d{5})(\d{4})/, '$1-$2');
        }
        input.value = value;
    }

    function validarCPF(cpf) {
        if (!cpf) return false;
        
        cpf = String(cpf).replace(/[^\d]/g, '');

        if (cpf.length !== 11) return false;

        if (/^(\d)\1{10}$/.test(cpf)) return false;

        let soma = 0;
        let resto;

        for (let i = 1; i <= 9; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;

        soma = 0;

        for (let i = 1; i <= 10; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) return false;

        return true;
    }

    async function salvarNovoCliente() {
        if (modalCadastroCliente?.classList.contains('hidden')) {
            console.warn('Modal de cadastro nao esta aberto');
            return;
        }

        esconderErroCadastro();

        const nome = inputCadastroNome?.value.trim().toUpperCase() || '';
        const cpf = inputCadastroCpf?.value.trim().replace(/[^\d]/g, '') || '';
        const telefone = inputCadastroTelefone?.value.trim().replace(/[^\d]/g, '') || '';
        const email = inputCadastroEmail?.value.trim().toUpperCase() || '';
        const senha = inputCadastroSenha?.value.trim() || '';
        const logradouro = inputCadastroLogradouro?.value.trim().toUpperCase() || '';
        const numero = inputCadastroNumero?.value.trim().toUpperCase() || '';
        const bairro = inputCadastroBairro?.value.trim().toUpperCase() || '';
        const cidade = inputCadastroCidade?.value.trim().toUpperCase() || '';
        const complemento = inputCadastroComplemento?.value.trim().toUpperCase() || '';
        const estado = inputCadastroEstado?.value.trim().toUpperCase() || '';
        const cep = inputCadastroCep?.value.trim().replace(/[^\d]/g, '') || '';

        if (cpf && !validarCPF(cpf)) {
            mostrarErroCadastro('CPF invalido. Verifique os digitos ou deixe o campo em branco.');
            inputCadastroCpf?.focus();
            return;
        }

        if (!nome) {
            mostrarErroCadastro('Nome completo e obrigatorio.');
            inputCadastroNome?.focus();
            return;
        }
        
        if (!telefone) {
            mostrarErroCadastro('Telefone e obrigatorio.');
            inputCadastroTelefone?.focus();
            return;
        }

        if (!senha || senha.length < 4) {
            mostrarErroCadastro('Senha e obrigatoria e deve ter no minimo 4 caracteres.');
            inputCadastroSenha?.focus();
            return;
        }

        if (!logradouro) {
            mostrarErroCadastro('Logradouro e obrigatorio para entrega.');
            inputCadastroLogradouro?.focus();
            return;
        }

        if (!numero) {
            mostrarErroCadastro('Numero do endereco e obrigatorio.');
            inputCadastroNumero?.focus();
            return;
        }

        if (!bairro) {
            mostrarErroCadastro('Bairro e obrigatorio para entrega.');
            inputCadastroBairro?.focus();
            return;
        }

        if (!cidade) {
            mostrarErroCadastro('Cidade e obrigatoria para entrega.');
            inputCadastroCidade?.focus();
            return;
        }

        const dadosCliente = {
            usuario_id: idUsuarioLoja,
            nome_completo: nome,
            cpf: cpf || null,
            telefone: telefone,
            email: email || null,
            senha: senha,
            endereco_logradouro: logradouro,
            endereco_numero: numero,
            endereco_complemento: complemento || null,
            endereco_bairro: bairro,
            endereco_cidade: cidade,
            endereco_estado: estado || null,
            endereco_cep: cep || null
        };

        btnSalvarCliente.disabled = true;
        btnSalvarCliente.textContent = 'Salvando...';

        try {
            const response = await fetch(API_CLIENTE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dadosCliente)
            });

            const data = await response.json();

            if (response.ok && response.status === 201) {
                clienteAtual = data;
                if (inputClienteId) inputClienteId.value = data.id;
                
                if (clienteInfoResultado) {
                    clienteInfoResultado.innerHTML = `
                        <p class="text-green-600 font-semibold">Cliente cadastrado: ${data.nome_completo}</p>
                        <p class="text-xs text-gray-600">${data.endereco_logradouro}, ${data.endereco_numero} - ${data.endereco_bairro}</p>
                        <p class="text-xs text-gray-600">${data.endereco_cidade}${data.endereco_estado ? ' - ' + data.endereco_estado : ''}</p>
                    `;
                    clienteInfoResultado.classList.remove('hidden');
                }
                
                modalCadastroCliente.classList.add('hidden');
                modalClientePedido.classList.remove('hidden');
                if (btnConfirmarPedido) btnConfirmarPedido.disabled = false;

                atualizarCalculoParcelas();
                
            } else if (response.status === 422) {
                let errorMsg = 'Erros de validacao:\n';
                for (const field in data.errors) {
                    errorMsg += `- ${data.errors[field].join(', ')}\n`;
                }
                mostrarErroCadastro(errorMsg);
            } else {
                throw new Error(data.message || 'Erro ao cadastrar cliente.');
            }
        } catch (error) {
            console.error('Erro ao salvar cliente:', error);
            mostrarErroCadastro('Nao foi possivel conectar ao servidor. Verifique sua conexao.');
        } finally {
            btnSalvarCliente.disabled = false;
            btnSalvarCliente.textContent = 'Salvar e Continuar';
        }
    }

    async function finalizarPedido(dadosPedido) {
        console.log("Iniciando finalizarPedido com dados:", dadosPedido);
        
        if (carrinho.length === 0) {
            alert('Carrinho esta vazio!');
            return;
        }

        if (!dadosPedido.cliente_id) {
            console.error("ERRO CRITICO: cliente_id esta vazio!");
            alert('Erro: Cliente nao identificado. Por favor, busque o CPF e faca login/cadastro.');
            return;
        }

        const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
        if (!uuidRegex.test(dadosPedido.cliente_id)) {
            console.error("ERRO CRITICO: cliente_id nao e um UUID valido:", dadosPedido.cliente_id);
            alert('Erro: ID do cliente invalido. Por favor, busque o CPF novamente.');
            return;
        }

        const selectFormaPagamento = document.getElementById('forma_pagamento');
        dadosPedido.forma_pagamento_id = selectFormaPagamento?.value;

        if (!dadosPedido.forma_pagamento_id) {
            alert('Por favor, selecione a forma de pagamento.');
            selectFormaPagamento?.focus();
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

        // Inclui vendedor se selecionado
        if (radioTipoVendaVendedor && radioTipoVendaVendedor.checked && inputColaboradorVendedorId) {
            const colaboradorId = inputColaboradorVendedorId.value;
            if (colaboradorId) {
                pedido.colaborador_vendedor_id = colaboradorId;
                console.log("Incluindo colaborador_vendedor_id no pedido:", colaboradorId);
            }
        }

        console.log("Objeto Pedido validado e pronto para salvar:", pedido);

        try {
            await idbKeyval.set('pedido_pendente', pedido);
            console.log("Pedido salvo no IndexedDB");
        } catch (err) {
            console.error('Erro ao salvar pedido no IndexedDB:', err);
            alert('Erro ao salvar pedido localmente.');
            return;
        }

        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            try {
                const swReg = await navigator.serviceWorker.ready;
                console.log("Registrando sync tag: sync-novo-pedido");
                await swReg.sync.register('sync-novo-pedido');
                alert('Pedido salvo localmente! Ele sera enviado assim que houver conexao.');
                modalCarrinho.classList.add('hidden');
                modalClientePedido.classList.add('hidden');
            } catch (err) {
                console.error('Falha ao registrar sync:', err);
                alert('Pedido salvo. Sera enviado quando possivel.');
                modalCarrinho.classList.add('hidden');
                modalClientePedido.classList.add('hidden');
            }
        } else {
            console.warn("SyncManager nao suportado.");
            alert('Pedido salvo localmente.');
            modalCarrinho.classList.add('hidden');
            modalClientePedido.classList.add('hidden');
        }
        console.log("finalizarPedido concluido.");
    }

   async function limparDadosLocais() {
        console.log('Limpando dados locais (carrinho) apos SYNC_SUCCESS...');
        carrinho = [];
        try {
            await idbKeyval.del('carrinho_atual');
            console.log('[APP] Chave "carrinho_atual" removida do IndexedDB.');
        } catch (err) {
            console.error('Erro ao limpar carrinho:', err);
        }
        atualizarModalCarrinho();

        try {
            if ('caches' in window) {
                const cache = await caches.open(CACHE_NAME);
                await cache.delete(API_PRODUTO_URL);
                console.log('[APP] Cache de produtos limpo.');
            }
        } catch (err) {
            console.error('Erro ao limpar cache:', err);
        }

        alert('Pedido sincronizado com sucesso!');
        
        if (navigator.onLine) {
            console.log('[APP] Recarregando produtos apos sincronizacao.');
            carregarProdutos();
        }
    }

    // --- EVENT LISTENERS ---

    if (catalogoContainer) {
        catalogoContainer.addEventListener('click', event => {
            const botao = event.target.closest('.btn-adicionar-carrinho'); 
            
            if (botao && !botao.disabled) {
                const produtoId = botao.dataset.id;
                const estoqueDisponivel = parseInt(botao.dataset.estoque || 0);
                
                const inputQty = document.getElementById(`qty-produto-${produtoId}`);
                const quantidade = parseInt(inputQty.value, 10);

                if (estoqueDisponivel <= 0) {
                    alert('Produto sem estoque disponivel.');
                    return;
                }

                if (quantidade <= 0 || isNaN(quantidade)) {
                    alert('A quantidade deve ser pelo menos 1.');
                    inputQty.value = 1;
                    return;
                }

                if (quantidade > estoqueDisponivel) {
                    alert(`Quantidade solicitada (${quantidade}) excede o estoque disponivel (${estoqueDisponivel}).`);
                    inputQty.value = estoqueDisponivel;
                    return;
                }

                const produto = {
                    produto_id: produtoId,
                    nome: botao.dataset.nome,
                    preco_unitario: parseFloat(botao.dataset.preco || 0),
                    imagem: botao.dataset.img
                };
                
                if (produto.produto_id && produto.nome && !isNaN(produto.preco_unitario)) {
                    adicionarAoCarrinho(produto, quantidade);
                }
            }
        });
    }

    if (itensCarrinhoContainer) {
        itensCarrinhoContainer.addEventListener('click', event => {
            const botaoRemover = event.target.closest('.remover-item-carrinho');
            const botaoAumentar = event.target.closest('.btn-aumentar-item');
            const botaoDiminuir = event.target.closest('.btn-diminuir-item');
            
            if (botaoRemover && botaoRemover.dataset.index !== undefined) {
                const index = parseInt(botaoRemover.dataset.index, 10);
                if (!isNaN(index)) removerDoCarrinho(index);
                return;
            }

            if (botaoAumentar && botaoAumentar.dataset.index !== undefined) {
                const index = parseInt(botaoAumentar.dataset.index, 10);
                if (!isNaN(index)) aumentarQuantidadeItem(index);
                return;
            }

            if (botaoDiminuir && botaoDiminuir.dataset.index !== undefined) {
                const index = parseInt(botaoDiminuir.dataset.index, 10);
                if (!isNaN(index)) diminuirQuantidadeItem(index);
                return;
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
            
            if (inputClienteCpfBusca) inputClienteCpfBusca.value = '';
            if (inputClienteId) inputClienteId.value = '';
            if (clienteInfoResultado) {
                clienteInfoResultado.innerHTML = '';
                clienteInfoResultado.classList.add('hidden');
            }
            if (btnConfirmarPedido) btnConfirmarPedido.disabled = true;
            clienteAtual = null;
            
            if (inputObservacoes) inputObservacoes.value = '';
            if (inputParcelas) inputParcelas.value = '1';

            if (parcelaInfoResultado) {
                parcelaInfoResultado.innerHTML = '';
                parcelaInfoResultado.style.display = 'none';
            }
            
            // Reset vendedor
            if (radioTipoVendaCliente) radioTipoVendaCliente.checked = true;
            if (radioTipoVendaVendedor) radioTipoVendaVendedor.checked = false;
            if (inputVendedorCpfBusca) inputVendedorCpfBusca.value = '';
            if (inputColaboradorVendedorId) inputColaboradorVendedorId.value = '';
            if (vendedorInfoResultado) vendedorInfoResultado.innerHTML = '';
            if (campoVendedorCpf) campoVendedorCpf.classList.add('hidden');
        });
    }

    if (btnFecharModalCliente) btnFecharModalCliente.addEventListener('click', () => modalClientePedido.classList.add('hidden'));

    if (btnBuscarCliente) {
        btnBuscarCliente.addEventListener('click', (e) => {
            e.preventDefault();
            if (!modalClientePedido?.classList.contains('hidden')) {
                buscarClientePorCpf();
            }
        });
    }
    
    if (inputClienteCpfBusca) {
        inputClienteCpfBusca.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (!modalClientePedido?.classList.contains('hidden')) {
                    buscarClientePorCpf();
                }
            }
        });
        
        inputClienteCpfBusca.addEventListener('input', () => {
            maskCPF(inputClienteCpfBusca);
        });
    }

    if (btnFecharModalLogin) btnFecharModalLogin.addEventListener('click', () => {
        modalLoginCliente.classList.add('hidden');
        modalClientePedido.classList.remove('hidden');
    });
    
    if (btnCancelarLogin) btnCancelarLogin.addEventListener('click', () => {
        modalLoginCliente.classList.add('hidden');
        modalClientePedido.classList.remove('hidden');
    });
    
    if (btnFazerLogin) btnFazerLogin.addEventListener('click', fazerLoginCliente);
    
    if (inputLoginSenha) {
        inputLoginSenha.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                fazerLoginCliente();
            }
        });
    }

    if (btnFecharModalCadastro) btnFecharModalCadastro.addEventListener('click', () => {
        modalCadastroCliente.classList.add('hidden');
        modalClientePedido.classList.remove('hidden');
    });
    
    if (btnCancelarCadastro) btnCancelarCadastro.addEventListener('click', () => {
        modalCadastroCliente.classList.add('hidden');
        modalClientePedido.classList.remove('hidden');
    });
    
    if (btnSalvarCliente) btnSalvarCliente.addEventListener('click', salvarNovoCliente);

    if (inputCadastroTelefone) {
        inputCadastroTelefone.addEventListener('input', () => {
            maskPhone(inputCadastroTelefone);
        });
    }

    if (inputParcelas) {
        inputParcelas.addEventListener('change', atualizarCalculoParcelas);
    }

    // Event Listeners de Vendedor
    if (radioTipoVendaCliente) {
        radioTipoVendaCliente.addEventListener('change', alternarCampoVendedor);
    }
    
    if (radioTipoVendaVendedor) {
        radioTipoVendaVendedor.addEventListener('change', alternarCampoVendedor);
    }
    
    if (btnBuscarVendedor) {
        btnBuscarVendedor.addEventListener('click', (e) => {
            e.preventDefault();
            buscarVendedorPorCpf();
        });
    }
    
    if (inputVendedorCpfBusca) {
        inputVendedorCpfBusca.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarVendedorPorCpf();
            }
        });
        
        inputVendedorCpfBusca.addEventListener('input', () => {
            maskCPF(inputVendedorCpfBusca);
        });
    }

    if (btnConfirmarPedido) {
        btnConfirmarPedido.addEventListener('click', () => {
            if (!inputClienteId) {
                console.error('Campo cliente_id nao encontrado no DOM');
                alert('Erro interno: campo de cliente nao encontrado.');
                return;
            }

            const selectFormaPagamento = document.getElementById('forma_pagamento');
            const clienteId = inputClienteId.value;
            
            console.log('Verificando dados antes de confirmar pedido:');
            console.log('- Cliente ID:', clienteId);
            console.log('- Forma Pagamento:', selectFormaPagamento?.value);
            
            if (!clienteId || clienteId === '') {
                alert("Erro: Voce precisa buscar o CPF e fazer login/cadastro antes de confirmar o pedido.");
                if (inputClienteCpfBusca) inputClienteCpfBusca.focus();
                return;
            }
            
            const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
            if (!uuidRegex.test(clienteId)) {
                console.error("ID do cliente nao e um UUID valido:", clienteId);
                alert("Erro interno: ID do cliente invalido. Por favor, busque o CPF novamente.");
                if (inputClienteId) inputClienteId.value = '';
                if (btnConfirmarPedido) btnConfirmarPedido.disabled = true;
                return;
            }
            
            if (!selectFormaPagamento?.value) {
                alert("Por favor, selecione a forma de pagamento.");
                selectFormaPagamento?.focus();
                return;
            }

            const dadosPedido = {
                cliente_id: clienteId,
                observacoes: inputObservacoes?.value.trim().toUpperCase() || null,
                numero_parcelas: parseInt(inputParcelas?.value, 10) || 1,
            };
            
            console.log("Finalizando pedido com dados validos:", dadosPedido);
            finalizarPedido(dadosPedido);
        });
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', event => {
            if (event.data && event.data.type === 'SYNC_SUCCESS') {
                limparDadosLocais();
            }
        });
    }

    // --- INICIALIZACAO ---
    (async () => {
        try {
            atualizarStatusOnline();
            await carregarProdutos();
            await carregarCarrinhoLocal();
            console.log("Aplicacao iniciada.");
        } catch (error) {
            console.error("ERRO NA INICIALIZACAO:", error);
        }
    })();
});