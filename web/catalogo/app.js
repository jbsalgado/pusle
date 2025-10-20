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
            setInterval(() => { registration.update(); }, 60000);
            
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                console.log('[APP] Nova versão do SW detectada!');
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        console.log('[APP] Nova versão disponível!');
                        newWorker.postMessage({ type: 'SKIP_WAITING' });
                    }
                });
            });
        })
        .catch(error => console.error('Falha ao registrar Service Worker:', error));
}

document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM Carregado. Iniciando...");

    // --- VERIFICAÇÃO DE ELEMENTOS CRÍTICOS ---
    const elementosCriticos = [
        'catalogo-produtos',
        'btn-abrir-carrinho',
        'modal-carrinho',
        'modal-cliente-pedido'
    ];

    for (const id of elementosCriticos) {
        if (!document.getElementById(id)) {
            console.error(`❌ Elemento crítico não encontrado: ${id}`);
        }
    }

    // --- SELETORES ---
    const catalogoContainer = document.getElementById('catalogo-produtos');
    const btnAbrirCarrinho = document.getElementById('btn-abrir-carrinho');
    const modalCarrinho = document.getElementById('modal-carrinho');
    const btnFecharCarrinho = document.getElementById('btn-fechar-carrinho');
    const btnFinalizarPedido = document.getElementById('btn-finalizar-pedido');
    const itensCarrinhoContainer = document.getElementById('itens-carrinho');
    const carrinhoVazioMsg = document.getElementById('carrinho-vazio-msg');
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

    // --- CONFIGURAÇÃO ---
    const URL_API = '/pulse/basic/web/index.php';
    const URL_BASE_WEB = '/pulse/basic/web';
    const API_PRODUTO_URL = `${URL_API}/api/produto`;
    const API_CLIENTE_URL = `${URL_API}/api/cliente`;
    const API_CLIENTE_BUSCA_CPF_URL = `${URL_API}/api/cliente/buscar-cpf`;
    const API_CLIENTE_LOGIN_URL = `${URL_API}/api/cliente/login`;
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
                selectFormaPagamento.innerHTML = '<option value="">Nenhuma opção disponível</option>';
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
                catalogoContainer.innerHTML = '<p class="col-span-full text-center text-gray-500">Nenhum produto disponível.</p>';
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

                const card = document.createElement('div');
                card.className = 'bg-white rounded-lg shadow-md overflow-hidden flex flex-col';
                card.innerHTML = `
                    <div class="h-48 w-full overflow-hidden bg-gray-200">
                        <img src="${urlImagem}" alt="${produto.nome || 'Produto'}" class="w-full h-full object-cover" onerror="this.src='https://via.placeholder.com/300x300.png?text=Erro';">
                    </div>
                    <div class="p-4 flex flex-col flex-grow">
                        <h3 class="text-lg font-semibold text-gray-800 truncate">${produto.nome || 'Produto'}</h3>
                        <p class="text-sm text-gray-500 mb-2 truncate">${produto.descricao || 'Sem descrição'}</p>
                        <p class="text-2xl font-bold text-blue-600 mb-4 mt-auto">R$ ${parseFloat(produto.preco_venda_sugerido || 0).toFixed(2)}</p>
                        <button data-id="${produto.id}" data-nome="${produto.nome || 'Produto'}" data-preco="${produto.preco_venda_sugerido || 0}" data-img="${urlImagem}" class="w-full bg-blue-500 text-white p-2 rounded-lg font-semibold hover:bg-blue-600">Adicionar</button>
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

    function adicionarAoCarrinho(produto) {
        if (!produto || !produto.produto_id) return;
        const itemExistente = carrinho.find(item => item.produto_id === produto.produto_id);
        if (itemExistente) {
            itemExistente.quantidade++;
        } else {
            produto.quantidade = 1;
            carrinho.push(produto);
        }
        salvarCarrinhoLocal();
        atualizarModalCarrinho();
    }

    function atualizarModalCarrinho() {
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
                        <img src="${item.imagem || 'https://via.placeholder.com/48'}" alt="${item.nome || ''}" class="w-12 h-12 object-cover rounded mr-3 flex-shrink-0">
                        <div class="flex-grow overflow-hidden">
                            <p class="font-semibold truncate">${item.nome || 'Item'}</p>
                            <p class="text-sm text-gray-600">Qtd: ${qtd} x R$ ${preco.toFixed(2)}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="font-semibold">R$ ${subtotal.toFixed(2)}</span>
                        <button data-index="${index}" class="text-red-500 hover:text-red-700 remover-item-carrinho p-1">
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
    }

    function removerDoCarrinho(index) {
        if (index >= 0 && index < carrinho.length) {
            carrinho.splice(index, 1);
            salvarCarrinhoLocal();
            atualizarModalCarrinho();
        }
    }

    async function buscarClientePorCpf() {
        // Verifica se modal está aberto
        if (modalClientePedido?.classList.contains('hidden')) {
            console.warn('Modal de pedido não está aberto');
            return;
        }

        if (!inputClienteCpfBusca) {
            console.error('Elemento inputClienteCpfBusca não encontrado');
            alert('Erro interno: campo de CPF não encontrado.');
            return;
        }

        const cpf = inputClienteCpfBusca.value.trim();
        
        if (!cpf) {
            alert('Digite o CPF para buscar.');
            return;
        }
        
        if (cpf.length !== 11) {
            alert('CPF deve ter 11 dígitos (somente números).');
            return;
        }
        
        if (!idUsuarioLoja) {
            console.error('❌ ID da loja não identificado');
            alert('Erro: ID da loja não identificado.');
            return;
        }

        console.log('🔍 Buscando cliente com CPF:', cpf);
        console.log('🔍 ID da loja:', idUsuarioLoja);

        btnBuscarCliente.disabled = true;
        btnBuscarCliente.textContent = 'Buscando...';

        try {
            const response = await fetch(`${API_CLIENTE_BUSCA_CPF_URL}?cpf=${cpf}&usuario_id=${idUsuarioLoja}`);
            const data = await response.json();

            if (response.ok && data.existe) {
                // Cliente EXISTE - Pedir senha
                console.log('✅ Cliente encontrado:', data.cliente);
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
                // Cliente NÃO EXISTE - Abrir formulário de cadastro
                console.log('ℹ️ Cliente não existe, abrindo formulário de cadastro');
                clienteAtual = null;
                if (inputCadastroCpf) inputCadastroCpf.value = cpf;
                limparFormularioCadastro();
                modalClientePedido.classList.add('hidden');
                modalCadastroCliente.classList.remove('hidden');
                if (inputCadastroNome) inputCadastroNome.focus();
            } else {
                throw new Error('Erro ao buscar cliente.');
            }
        } catch (error) {
            console.error('Erro na busca:', error);
            alert('Não foi possível buscar o cliente. Verifique sua conexão.');
        } finally {
            btnBuscarCliente.disabled = false;
            btnBuscarCliente.textContent = 'Buscar';
        }
    }

    async function fazerLoginCliente() {
        // Verifica se modal de login está aberto
        if (modalLoginCliente?.classList.contains('hidden')) {
            console.warn('Modal de login não está aberto');
            return;
        }

        if (!inputLoginSenha) {
            console.error('Elemento inputLoginSenha não encontrado');
            alert('Erro interno: campo de senha não encontrado.');
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
                console.log('✅ Login bem-sucedido para cliente:', data.cliente.nome_completo);
                clienteAtual = data.cliente;
                if (inputClienteId) inputClienteId.value = clienteAtual.id;
                
                console.log('📋 Cliente ID definido:', clienteAtual.id);
                
                if (clienteInfoResultado) {
                    clienteInfoResultado.innerHTML = `
                        <p class="text-green-600 font-semibold">✓ Cliente: ${clienteAtual.nome_completo}</p>
                        <p class="text-gray-600 text-xs">CPF: ${formatarCPF(clienteAtual.cpf)}</p>
                    `;
                    clienteInfoResultado.classList.remove('hidden');
                }
                
                if (btnConfirmarPedido) btnConfirmarPedido.disabled = false;
                
                modalLoginCliente.classList.add('hidden');
                modalClientePedido.classList.remove('hidden');
                
            } else if (response.status === 401) {
                mostrarErroLogin('Senha incorreta. Tente novamente.');
            } else {
                throw new Error(data.message || 'Erro ao fazer login.');
            }
        } catch (error) {
            console.error('Erro no login:', error);
            mostrarErroLogin('Não foi possível fazer login. Tente novamente.');
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
        if (!cpf || cpf.length !== 11) return cpf;
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }

    async function salvarNovoCliente() {
        // Verifica se modal de cadastro está aberto
        if (modalCadastroCliente?.classList.contains('hidden')) {
            console.warn('Modal de cadastro não está aberto');
            return;
        }

        esconderErroCadastro();

        const nome = inputCadastroNome?.value.trim() || '';
        const cpf = inputCadastroCpf?.value.trim() || '';
        const telefone = inputCadastroTelefone?.value.trim() || '';
        const email = inputCadastroEmail?.value.trim() || '';
        const senha = inputCadastroSenha?.value.trim() || '';
        const logradouro = inputCadastroLogradouro?.value.trim() || '';
        const numero = inputCadastroNumero?.value.trim() || '';
        const bairro = inputCadastroBairro?.value.trim() || '';
        const cidade = inputCadastroCidade?.value.trim() || '';
        const complemento = inputCadastroComplemento?.value.trim() || '';
        const estado = inputCadastroEstado?.value.trim().toUpperCase() || '';
        const cep = inputCadastroCep?.value.trim() || '';

        if (!nome) {
            mostrarErroCadastro('Nome completo é obrigatório.');
            inputCadastroNome?.focus();
            return;
        }
        
        if (!telefone) {
            mostrarErroCadastro('Telefone é obrigatório.');
            inputCadastroTelefone?.focus();
            return;
        }

        if (!senha || senha.length < 4) {
            mostrarErroCadastro('Senha é obrigatória e deve ter no mínimo 4 caracteres.');
            inputCadastroSenha?.focus();
            return;
        }

        if (!logradouro) {
            mostrarErroCadastro('Logradouro é obrigatório para entrega.');
            inputCadastroLogradouro?.focus();
            return;
        }

        if (!numero) {
            mostrarErroCadastro('Número do endereço é obrigatório.');
            inputCadastroNumero?.focus();
            return;
        }

        if (!bairro) {
            mostrarErroCadastro('Bairro é obrigatório para entrega.');
            inputCadastroBairro?.focus();
            return;
        }

        if (!cidade) {
            mostrarErroCadastro('Cidade é obrigatória para entrega.');
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
                        <p class="text-green-600 font-semibold">✓ Cliente cadastrado: ${data.nome_completo}</p>
                        <p class="text-xs text-gray-600">${data.endereco_logradouro}, ${data.endereco_numero} - ${data.endereco_bairro}</p>
                        <p class="text-xs text-gray-600">${data.endereco_cidade}${data.endereco_estado ? ' - ' + data.endereco_estado : ''}</p>
                    `;
                    clienteInfoResultado.classList.remove('hidden');
                }
                
                modalCadastroCliente.classList.add('hidden');
                modalClientePedido.classList.remove('hidden');
                if (btnConfirmarPedido) btnConfirmarPedido.disabled = false;
                
            } else if (response.status === 422) {
                let errorMsg = 'Erros de validação:\n';
                for (const field in data.errors) {
                    errorMsg += `- ${data.errors[field].join(', ')}\n`;
                }
                mostrarErroCadastro(errorMsg);
            } else {
                throw new Error(data.message || 'Erro ao cadastrar cliente.');
            }
        } catch (error) {
            console.error('Erro ao salvar cliente:', error);
            mostrarErroCadastro('Não foi possível conectar ao servidor. Verifique sua conexão.');
        } finally {
            btnSalvarCliente.disabled = false;
            btnSalvarCliente.textContent = 'Salvar e Continuar';
        }
    }

    async function finalizarPedido(dadosPedido) {
        console.log("🔍 Iniciando finalizarPedido com dados:", dadosPedido);
        
        if (carrinho.length === 0) {
            alert('Carrinho está vazio!');
            return;
        }

        if (!dadosPedido.cliente_id) {
            console.error("❌ ERRO CRÍTICO: cliente_id está vazio!");
            alert('Erro: Cliente não identificado. Por favor, busque o CPF e faça login/cadastro.');
            return;
        }

        const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
        if (!uuidRegex.test(dadosPedido.cliente_id)) {
            console.error("❌ ERRO CRÍTICO: cliente_id não é um UUID válido:", dadosPedido.cliente_id);
            alert('Erro: ID do cliente inválido. Por favor, busque o CPF novamente.');
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

        console.log("✅ Objeto Pedido validado e pronto para salvar:", pedido);

        try {
            await idbKeyval.set('pedido_pendente', pedido);
            console.log("✅ Pedido salvo no IndexedDB com cliente_id:", pedido.cliente_id);
        } catch (err) {
            console.error('❌ Erro ao salvar pedido no IndexedDB:', err);
            alert('Erro ao salvar pedido localmente.');
            return;
        }

        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            try {
                const swReg = await navigator.serviceWorker.ready;
                console.log("🔄 Registrando sync tag: sync-novo-pedido");
                await swReg.sync.register('sync-novo-pedido');
                alert('✅ Pedido salvo localmente! Ele será enviado assim que houver conexão.');
                modalCarrinho.classList.add('hidden');
                modalClientePedido.classList.add('hidden');
            } catch (err) {
                console.error('❌ Falha ao registrar sync:', err);
                alert('Pedido salvo. Será enviado quando possível.');
                modalCarrinho.classList.add('hidden');
                modalClientePedido.classList.add('hidden');
            }
        } else {
            console.warn("⚠️ SyncManager não suportado.");
            alert('Pedido salvo localmente.');
            modalCarrinho.classList.add('hidden');
            modalClientePedido.classList.add('hidden');
        }
        console.log("✅ finalizarPedido concluído.");
    }

   async function limparDadosLocais() {
        console.log('Limpando dados locais (carrinho) após SYNC_SUCCESS...');
        carrinho = [];
        try {
            await idbKeyval.del('carrinho_atual');
            console.log('✅ [APP] Chave "carrinho_atual" removida do IndexedDB.');
        } catch (err) {
            console.error('Erro ao limpar carrinho:', err);
        }
        atualizarModalCarrinho(); // Atualiza a UI (ícone do carrinho, etc)

        try {
            if ('caches' in window) {
                const cache = await caches.open(CACHE_NAME);
                await cache.delete(API_PRODUTO_URL);
                console.log('✅ [APP] Cache de produtos limpo.');
            }
        } catch (err) {
            console.error('Erro ao limpar cache:', err);
        }

        alert('Pedido sincronizado com sucesso!');
        
        if (navigator.onLine) {
            console.log('[APP] Recarregando produtos após sincronização.');
            carregarProdutos();
        }
    }

    // --- EVENT LISTENERS ---

    if (catalogoContainer) {
        catalogoContainer.addEventListener('click', event => {
            const botao = event.target.closest('button[data-id]');
            if (botao) {
                const produto = {
                    produto_id: botao.dataset.id,
                    nome: botao.dataset.nome,
                    preco_unitario: parseFloat(botao.dataset.preco || 0),
                    imagem: botao.dataset.img
                };
                if (produto.produto_id && produto.nome && !isNaN(produto.preco_unitario)) {
                    adicionarAoCarrinho(produto);
                }
            }
        });
    }

    if (itensCarrinhoContainer) {
        itensCarrinhoContainer.addEventListener('click', event => {
            const botaoRemover = event.target.closest('.remover-item-carrinho');
            if (botaoRemover && botaoRemover.dataset.index !== undefined) {
                const index = parseInt(botaoRemover.dataset.index, 10);
                if (!isNaN(index)) removerDoCarrinho(index);
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
            
            // Limpar dados do cliente
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
        });
    }

    if (btnFecharModalCliente) btnFecharModalCliente.addEventListener('click', () => modalClientePedido.classList.add('hidden'));

    if (btnBuscarCliente) {
        btnBuscarCliente.addEventListener('click', (e) => {
            e.preventDefault();
            // Só executar se o modal estiver visível
            if (!modalClientePedido?.classList.contains('hidden')) {
                buscarClientePorCpf();
            }
        });
    }
    
    if (inputClienteCpfBusca) {
        inputClienteCpfBusca.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Só executar se o modal estiver visível
                if (!modalClientePedido?.classList.contains('hidden')) {
                    buscarClientePorCpf();
                }
            }
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

    if (btnConfirmarPedido) {
        btnConfirmarPedido.addEventListener('click', () => {
            // Verificações de segurança
            if (!inputClienteId) {
                console.error('❌ Campo cliente_id não encontrado no DOM');
                alert('Erro interno: campo de cliente não encontrado.');
                return;
            }

            const selectFormaPagamento = document.getElementById('forma_pagamento');
            const clienteId = inputClienteId.value;
            
            console.log('🔍 Verificando dados antes de confirmar pedido:');
            console.log('- Cliente ID:', clienteId);
            console.log('- Forma Pagamento:', selectFormaPagamento?.value);
            
            if (!clienteId || clienteId === '') {
                alert("❌ Erro: Você precisa buscar o CPF e fazer login/cadastro antes de confirmar o pedido.");
                if (inputClienteCpfBusca) inputClienteCpfBusca.focus();
                return;
            }
            
            const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
            if (!uuidRegex.test(clienteId)) {
                console.error("❌ ID do cliente não é um UUID válido:", clienteId);
                alert("❌ Erro interno: ID do cliente inválido. Por favor, busque o CPF novamente.");
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
                observacoes: inputObservacoes?.value || null,
                numero_parcelas: parseInt(inputParcelas?.value, 10) || 1,
            };
            
            console.log("✅ Finalizando pedido com dados válidos:", dadosPedido);
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

    // --- INICIALIZAÇÃO ---
    (async () => {
        try {
            atualizarStatusOnline();
            await carregarProdutos();
            await carregarCarrinhoLocal();
            console.log("Aplicação iniciada.");
        } catch (error) {
            console.error("ERRO NA INICIALIZAÇÃO:", error);
        }
    })();
});