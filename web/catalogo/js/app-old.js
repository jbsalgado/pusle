// app.js - Arquivo principal que orquestra todos os módulos

import { ELEMENTOS_CRITICOS } from './config.js';
import { verificarElementosCriticos, maskCPF, maskPhone, validarCPF } from './utils.js';
import { inicializarServiceWorker, adicionarListenerMensagensSW } from './serviceWorkerManager.js';
import { inicializarMonitoramentoRede, estaOnline } from './network.js';
import { carregarCarrinho as carregarCarrinhoStorage, limparDadosLocaisPosSinc } from './storage.js';
import { 
    getCarrinho, 
    setCarrinho, 
    adicionarAoCarrinho, 
    removerDoCarrinho, 
    aumentarQuantidadeItem, 
    diminuirQuantidadeItem,
    calcularTotalCarrinho,
    atualizarIndicadoresCarrinho,
    atualizarBadgeProduto
} from './cart.js';
import { carregarProdutos, getIdUsuarioLoja } from './products.js';
import { 
    buscarClientePorCpf, 
    fazerLogin, 
    cadastrarCliente,
    getClienteAtual,
    setClienteAtual 
} from './customer.js';
import { buscarVendedorPorCpf } from './seller.js';
import { carregarFormasPagamento, calcularParcelas, formatarInfoParcelas } from './payment.js';
import { finalizarPedido } from './order.js';
import { 
    atualizarModalCarrinho,
    mostrarErroLogin,
    esconderErroLogin,
    mostrarErroCadastro,
    esconderErroCadastro,
    limparFormularioCadastro,
    atualizarInfoCliente,
    atualizarInfoVendedor,
    popularFormasPagamento,
    atualizarInfoParcelas
} from './ui.js';

document.addEventListener('DOMContentLoaded', async () => {
    console.log('[App] DOM Carregado. Iniciando...');

    // Verifica elementos críticos
    verificarElementosCriticos(ELEMENTOS_CRITICOS);

    // Inicializa Service Worker
    inicializarServiceWorker();

    // Inicializa monitoramento de rede
    inicializarMonitoramentoRede();

    // Obtém referências dos elementos
    const elementos = obterElementosDOM();

    // Configura event listeners
    configurarEventListeners(elementos);

    // Adiciona listener para mensagens do Service Worker
    adicionarListenerMensagensSW(async (data) => {
        if (data.type === 'SYNC_SUCCESS') {
            await processarSincronizacao(elementos.catalogoContainer);
        }
    });

    // Inicialização da aplicação
    await inicializarAplicacao(elementos.catalogoContainer);

    console.log('[App] Aplicação iniciada com sucesso!');
});

/**
 * Obtém referências de elementos do DOM
 */
function obterElementosDOM() {
    return {
        // Catálogo
        catalogoContainer: document.getElementById('catalogo-produtos'),
        
        // Carrinho
        btnAbrirCarrinho: document.getElementById('btn-abrir-carrinho'),
        modalCarrinho: document.getElementById('modal-carrinho'),
        btnFecharCarrinho: document.getElementById('btn-fechar-carrinho'),
        btnFinalizarPedido: document.getElementById('btn-finalizar-pedido'),
        itensCarrinhoContainer: document.getElementById('itens-carrinho'),
        
        // Modal Cliente/Pedido
        modalClientePedido: document.getElementById('modal-cliente-pedido'),
        btnFecharModalCliente: document.getElementById('btn-fechar-modal-cliente'),
        btnBuscarCliente: document.getElementById('btn-buscar-cliente'),
        inputClienteCpfBusca: document.getElementById('cliente-cpf-busca'),
        inputClienteId: document.getElementById('cliente_id'),
        btnConfirmarPedido: document.getElementById('btn-confirmar-pedido'),
        inputObservacoes: document.getElementById('observacoes'),
        inputParcelas: document.getElementById('numero_parcelas'),
        
        // === NOVO: Campo de data do primeiro pagamento ===
        campoDataPrimeiroPagamento: document.getElementById('campo-data-primeiro-pagamento'),
        inputDataPrimeiroPagamento: document.getElementById('data_primeiro_pagamento'),
        
        // Vendedor
        radioTipoVendaCliente: document.getElementById('tipo_venda_cliente'),
        radioTipoVendaVendedor: document.getElementById('tipo_venda_vendedor'),
        campoVendedorCpf: document.getElementById('campo-vendedor-cpf'),
        inputVendedorCpfBusca: document.getElementById('vendedor_cpf_busca'),
        btnBuscarVendedor: document.getElementById('btn-buscar-vendedor'),
        inputColaboradorVendedorId: document.getElementById('colaborador_vendedor_id'),
        
        // Modal Login
        modalLoginCliente: document.getElementById('modal-login-cliente'),
        btnFecharModalLogin: document.getElementById('btn-fechar-modal-login'),
        btnCancelarLogin: document.getElementById('btn-cancelar-login'),
        btnFazerLogin: document.getElementById('btn-fazer-login'),
        inputLoginSenha: document.getElementById('login-senha'),
        loginClienteNome: document.getElementById('login-cliente-nome'),
        
        // Modal Cadastro
        modalCadastroCliente: document.getElementById('modal-cadastro-cliente'),
        btnFecharModalCadastro: document.getElementById('btn-fechar-modal-cadastro'),
        btnCancelarCadastro: document.getElementById('btn-cancelar-cadastro'),
        btnSalvarCliente: document.getElementById('btn-salvar-cliente'),
        inputCadastroNome: document.getElementById('cadastro-nome'),
        inputCadastroCpf: document.getElementById('cadastro-cpf'),
        inputCadastroTelefone: document.getElementById('cadastro-telefone'),
        inputCadastroEmail: document.getElementById('cadastro-email'),
        inputCadastroSenha: document.getElementById('cadastro-senha'),
        inputCadastroLogradouro: document.getElementById('cadastro-logradouro'),
        inputCadastroNumero: document.getElementById('cadastro-numero'),
        inputCadastroComplemento: document.getElementById('cadastro-complemento'),
        inputCadastroBairro: document.getElementById('cadastro-bairro'),
        inputCadastroCidade: document.getElementById('cadastro-cidade'),
        inputCadastroEstado: document.getElementById('cadastro-estado'),
        inputCadastroCep: document.getElementById('cadastro-cep')
    };
}

/**
 * Configura todos os event listeners
 */
function configurarEventListeners(el) {
    // Listeners do catálogo
    configurarListenersCatalogo(el);
    
    // Listeners do carrinho
    configurarListenersCarrinho(el);
    
    // Listeners do modal de pedido
    configurarListenersModalPedido(el);
    
    // Listeners de cliente
    configurarListenersCliente(el);
    
    // Listeners de vendedor
    configurarListenersVendedor(el);
    
    // Listeners de login
    configurarListenersLogin(el);
    
    // Listeners de cadastro
    configurarListenersCadastro(el);
}

/**
 * Listeners do catálogo de produtos
 */
function configurarListenersCatalogo(el) {
    if (!el.catalogoContainer) return;
    
    el.catalogoContainer.addEventListener('click', event => {
        const botao = event.target.closest('.btn-adicionar-carrinho');
        
        if (botao && !botao.disabled) {
            const produtoId = botao.dataset.id;
            const estoqueDisponivel = parseInt(botao.dataset.estoque || 0);
            const inputQty = document.getElementById(`qty-produto-${produtoId}`);
            const quantidade = parseInt(inputQty.value, 10);

            if (estoqueDisponivel <= 0) {
                alert('Produto sem estoque disponível.');
                return;
            }

            if (quantidade <= 0 || isNaN(quantidade)) {
                alert('A quantidade deve ser pelo menos 1.');
                inputQty.value = 1;
                return;
            }

            if (quantidade > estoqueDisponivel) {
                alert(`Quantidade solicitada (${quantidade}) excede o estoque disponível (${estoqueDisponivel}).`);
                inputQty.value = estoqueDisponivel;
                return;
            }

            const produto = {
                produto_id: produtoId,
                nome: botao.dataset.nome,
                preco_unitario: parseFloat(botao.dataset.preco || 0),
                imagem: botao.dataset.img
            };
            
            if (adicionarAoCarrinho(produto, quantidade)) {
                atualizarModalCarrinho();
                atualizarBadgeProduto(produtoId, true);
            }
        }
    });
}

/**
 * Listeners do carrinho
 */
function configurarListenersCarrinho(el) {
    // Abrir carrinho
    if (el.btnAbrirCarrinho) {
        el.btnAbrirCarrinho.addEventListener('click', () => {
            el.modalCarrinho?.classList.remove('hidden');
        });
    }
    
    // Fechar carrinho
    if (el.btnFecharCarrinho) {
        el.btnFecharCarrinho.addEventListener('click', () => {
            el.modalCarrinho?.classList.add('hidden');
        });
    }
    
    // Finalizar pedido
    if (el.btnFinalizarPedido) {
        el.btnFinalizarPedido.addEventListener('click', async () => {
            el.modalCarrinho?.classList.add('hidden');
            el.modalClientePedido?.classList.remove('hidden');
            
            await carregarFormasPagamento(getIdUsuarioLoja())
                .then(formas => popularFormasPagamento(formas))
                .catch(error => console.error('[App] Erro ao carregar formas:', error));
            
            resetarFormularioPedido(el);
        });
    }
    
    // Controles do carrinho (remover, aumentar, diminuir)
    if (el.itensCarrinhoContainer) {
        el.itensCarrinhoContainer.addEventListener('click', event => {
            const botaoRemover = event.target.closest('.remover-item-carrinho');
            const botaoAumentar = event.target.closest('.btn-aumentar-item');
            const botaoDiminuir = event.target.closest('.btn-diminuir-item');
            
            if (botaoRemover) {
                const index = parseInt(botaoRemover.dataset.index, 10);
                const produtoId = removerDoCarrinho(index);
                if (produtoId) {
                    atualizarModalCarrinho();
                    atualizarBadgeProduto(produtoId, false);
                }
            } else if (botaoAumentar) {
                const index = parseInt(botaoAumentar.dataset.index, 10);
                if (aumentarQuantidadeItem(index)) {
                    atualizarModalCarrinho();
                }
            } else if (botaoDiminuir) {
                const index = parseInt(botaoDiminuir.dataset.index, 10);
                if (diminuirQuantidadeItem(index)) {
                    atualizarModalCarrinho();
                }
            }
        });
    }
}

/**
 * Listeners do modal de pedido
 */
function configurarListenersModalPedido(el) {
    if (el.btnFecharModalCliente) {
        el.btnFecharModalCliente.addEventListener('click', () => {
            el.modalClientePedido?.classList.add('hidden');
        });
    }
    
    // === ATUALIZADO: Listener para mudança no número de parcelas ===
    if (el.inputParcelas) {
        el.inputParcelas.addEventListener('change', async () => {
            await calcularEAtualizarParcelas(el);
            alternarCampoDataPrimeiroPagamento(el); // Nova função
        });
    }
    
    if (el.btnConfirmarPedido) {
        el.btnConfirmarPedido.addEventListener('click', async () => {
            await confirmarPedido(el);
        });
    }
}

/**
 * Listeners de cliente
 */
function configurarListenersCliente(el) {
    // Buscar cliente
    if (el.btnBuscarCliente) {
        el.btnBuscarCliente.addEventListener('click', async (e) => {
            e.preventDefault();
            await processarBuscaCliente(el);
        });
    }
    
    // Máscara e enter no CPF
    if (el.inputClienteCpfBusca) {
        el.inputClienteCpfBusca.addEventListener('input', () => {
            maskCPF(el.inputClienteCpfBusca);
        });
        
        el.inputClienteCpfBusca.addEventListener('keypress', async (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                await processarBuscaCliente(el);
            }
        });
    }
}

/**
 * Listeners de vendedor
 */
function configurarListenersVendedor(el) {
    // Alternar tipo de venda
    if (el.radioTipoVendaCliente) {
        el.radioTipoVendaCliente.addEventListener('change', () => {
            alternarCampoVendedor(el);
        });
    }
    
    if (el.radioTipoVendaVendedor) {
        el.radioTipoVendaVendedor.addEventListener('change', () => {
            alternarCampoVendedor(el);
        });
    }
    
    // Buscar vendedor
    if (el.btnBuscarVendedor) {
        el.btnBuscarVendedor.addEventListener('click', async (e) => {
            e.preventDefault();
            await processarBuscaVendedor(el);
        });
    }
    
    // Máscara e enter no CPF do vendedor
    if (el.inputVendedorCpfBusca) {
        el.inputVendedorCpfBusca.addEventListener('input', () => {
            maskCPF(el.inputVendedorCpfBusca);
        });
        
        el.inputVendedorCpfBusca.addEventListener('keypress', async (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                await processarBuscaVendedor(el);
            }
        });
    }
}

/**
 * Listeners de login
 */
function configurarListenersLogin(el) {
    if (el.btnFecharModalLogin) {
        el.btnFecharModalLogin.addEventListener('click', () => {
            el.modalLoginCliente?.classList.add('hidden');
            el.modalClientePedido?.classList.remove('hidden');
        });
    }
    
    if (el.btnCancelarLogin) {
        el.btnCancelarLogin.addEventListener('click', () => {
            el.modalLoginCliente?.classList.add('hidden');
            el.modalClientePedido?.classList.remove('hidden');
        });
    }
    
    if (el.btnFazerLogin) {
        el.btnFazerLogin.addEventListener('click', async () => {
            await processarLogin(el);
        });
    }
    
    if (el.inputLoginSenha) {
        el.inputLoginSenha.addEventListener('keypress', async (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                await processarLogin(el);
            }
        });
    }
}

/**
 * Listeners de cadastro
 */
function configurarListenersCadastro(el) {
    if (el.btnFecharModalCadastro) {
        el.btnFecharModalCadastro.addEventListener('click', () => {
            el.modalCadastroCliente?.classList.add('hidden');
            el.modalClientePedido?.classList.remove('hidden');
        });
    }
    
    if (el.btnCancelarCadastro) {
        el.btnCancelarCadastro.addEventListener('click', () => {
            el.modalCadastroCliente?.classList.add('hidden');
            el.modalClientePedido?.classList.remove('hidden');
        });
    }
    
    if (el.btnSalvarCliente) {
        el.btnSalvarCliente.addEventListener('click', async () => {
            await processarCadastro(el);
        });
    }
    
    if (el.inputCadastroTelefone) {
        el.inputCadastroTelefone.addEventListener('input', () => {
            maskPhone(el.inputCadastroTelefone);
        });
    }
}

// === FUNÇÕES DE PROCESSAMENTO ===

async function processarBuscaCliente(el) {
    const cpf = el.inputClienteCpfBusca?.value.trim().replace(/[^\d]/g, '');
    
    if (!cpf) {
        alert('Digite o CPF para buscar.');
        return;
    }
    
    if (!validarCPF(cpf)) {
        alert('CPF inválido. Verifique os dígitos e tente novamente.');
        el.inputClienteCpfBusca?.focus();
        return;
    }
    
    el.btnBuscarCliente.disabled = true;
    el.btnBuscarCliente.textContent = 'Buscando...';
    
    try {
        const resultado = await buscarClientePorCpf(cpf, getIdUsuarioLoja());
        
        if (resultado.existe) {
            setClienteAtual(resultado.cliente);
            el.loginClienteNome.textContent = resultado.cliente.nome_completo;
            el.modalClientePedido?.classList.add('hidden');
            el.modalLoginCliente?.classList.remove('hidden');
            if (el.inputLoginSenha) {
                el.inputLoginSenha.value = '';
                el.inputLoginSenha.focus();
            }
            esconderErroLogin();
        } else {
            setClienteAtual(null);
            if (el.inputCadastroCpf) {
                el.inputCadastroCpf.value = cpf;
                maskCPF(el.inputCadastroCpf);
            }
            limparFormularioCadastro();
            el.modalClientePedido?.classList.add('hidden');
            el.modalCadastroCliente?.classList.remove('hidden');
            if (el.inputCadastroNome) el.inputCadastroNome.focus();
        }
    } catch (error) {
        console.error('[App] Erro na busca:', error);
        alert('Não foi possível buscar o cliente. Verifique sua conexão.');
    } finally {
        el.btnBuscarCliente.disabled = false;
        el.btnBuscarCliente.textContent = 'Buscar';
    }
}

async function processarLogin(el) {
    const senha = el.inputLoginSenha?.value.trim();
    
    if (!senha) {
        mostrarErroLogin('Digite a senha.');
        return;
    }
    
    el.btnFazerLogin.disabled = true;
    el.btnFazerLogin.textContent = 'Entrando...';
    esconderErroLogin();
    
    try {
        const clienteAtual = getClienteAtual();
        const cliente = await fazerLogin(clienteAtual.cpf, senha, getIdUsuarioLoja());
        
        el.inputClienteId.value = cliente.id;
        atualizarInfoCliente(cliente);
        
        if (el.btnConfirmarPedido) el.btnConfirmarPedido.disabled = false;
        
        el.modalLoginCliente?.classList.add('hidden');
        el.modalClientePedido?.classList.remove('hidden');
        
        await calcularEAtualizarParcelas(el);
        alternarCampoDataPrimeiroPagamento(el); // Nova função
        
    } catch (error) {
        console.error('[App] Erro no login:', error);
        mostrarErroLogin(error.message || 'Não foi possível fazer login. Tente novamente.');
    } finally {
        el.btnFazerLogin.disabled = false;
        el.btnFazerLogin.textContent = 'Entrar';
    }
}

async function processarCadastro(el) {
    const dadosCliente = {
        usuario_id: getIdUsuarioLoja(),
        nome_completo: el.inputCadastroNome?.value.trim().toUpperCase() || '',
        cpf: el.inputCadastroCpf?.value.trim().replace(/[^\d]/g, '') || null,
        telefone: el.inputCadastroTelefone?.value.trim().replace(/[^\d]/g, '') || '',
        email: el.inputCadastroEmail?.value.trim().toUpperCase() || null,
        senha: el.inputCadastroSenha?.value.trim() || '',
        endereco_logradouro: el.inputCadastroLogradouro?.value.trim().toUpperCase() || '',
        endereco_numero: el.inputCadastroNumero?.value.trim().toUpperCase() || '',
        endereco_complemento: el.inputCadastroComplemento?.value.trim().toUpperCase() || null,
        endereco_bairro: el.inputCadastroBairro?.value.trim().toUpperCase() || '',
        endereco_cidade: el.inputCadastroCidade?.value.trim().toUpperCase() || '',
        endereco_estado: el.inputCadastroEstado?.value.trim().toUpperCase() || null,
        endereco_cep: el.inputCadastroCep?.value.trim().replace(/[^\d]/g, '') || null
    };
    
    el.btnSalvarCliente.disabled = true;
    el.btnSalvarCliente.textContent = 'Salvando...';
    esconderErroCadastro();
    
    try {
        const cliente = await cadastrarCliente(dadosCliente);
        
        el.inputClienteId.value = cliente.id;
        atualizarInfoCliente(cliente);
        
        el.modalCadastroCliente?.classList.add('hidden');
        el.modalClientePedido?.classList.remove('hidden');
        
        if (el.btnConfirmarPedido) el.btnConfirmarPedido.disabled = false;
        
        await calcularEAtualizarParcelas(el);
        alternarCampoDataPrimeiroPagamento(el); // Nova função
        
    } catch (error) {
        console.error('[App] Erro ao cadastrar:', error);
        mostrarErroCadastro(error.message || 'Não foi possível cadastrar o cliente.');
    } finally {
        el.btnSalvarCliente.disabled = false;
        el.btnSalvarCliente.textContent = 'Salvar e Continuar';
    }
}

async function processarBuscaVendedor(el) {
    const cpf = el.inputVendedorCpfBusca?.value.trim().replace(/[^\d]/g, '');
    
    if (!cpf) {
        alert('Digite o CPF do vendedor para buscar.');
        return;
    }
    
    if (!validarCPF(cpf)) {
        alert('CPF inválido. Verifique os dígitos e tente novamente.');
        el.inputVendedorCpfBusca?.focus();
        return;
    }
    
    el.btnBuscarVendedor.disabled = true;
    el.btnBuscarVendedor.textContent = 'Buscando...';
    
    try {
        const resultado = await buscarVendedorPorCpf(cpf, getIdUsuarioLoja());
        
        if (resultado.existe) {
            el.inputColaboradorVendedorId.value = resultado.colaborador.id;
            atualizarInfoVendedor(resultado.colaborador, true);
        } else {
            el.inputColaboradorVendedorId.value = '';
            atualizarInfoVendedor(null, false);
            alert('Vendedor não encontrado. Verifique o CPF ou cadastre-o no sistema.');
        }
    } catch (error) {
        console.error('[App] Erro na busca do vendedor:', error);
        alert('Não foi possível buscar o vendedor. Verifique sua conexão.');
        atualizarInfoVendedor(null, false);
    } finally {
        el.btnBuscarVendedor.disabled = false;
        el.btnBuscarVendedor.textContent = 'Buscar';
    }
}

async function calcularEAtualizarParcelas(el) {
    const numeroParcelas = parseInt(el.inputParcelas?.value, 10);
    
    if (numeroParcelas <= 1) {
        atualizarInfoParcelas(null);
        return;
    }
    
    const valorBase = calcularTotalCarrinho();
    
    if (valorBase <= 0) {
        atualizarInfoParcelas(null);
        return;
    }
    
    atualizarInfoParcelas('Calculando...');
    
    try {
        const dadosParcela = await calcularParcelas(valorBase, numeroParcelas, getIdUsuarioLoja());
        const htmlParcelas = formatarInfoParcelas(dadosParcela);
        atualizarInfoParcelas(htmlParcelas);
    } catch (error) {
        console.error('[App] Erro ao calcular parcelas:', error);
        atualizarInfoParcelas('<span class="text-red-500 text-xs">Não foi possível calcular o valor da parcela.</span>');
    }
}

// === NOVA FUNÇÃO: Alternar visibilidade do campo de data ===
function alternarCampoDataPrimeiroPagamento(el) {
    if (!el.campoDataPrimeiroPagamento || !el.inputParcelas) return;
    
    const numeroParcelas = parseInt(el.inputParcelas.value, 10);
    
    if (numeroParcelas > 1) {
        // Mostra o campo e define data mínima como hoje
        el.campoDataPrimeiroPagamento.classList.remove('hidden');
        
        if (el.inputDataPrimeiroPagamento) {
            // Define data mínima como hoje
            const hoje = new Date().toISOString().split('T')[0];
            el.inputDataPrimeiroPagamento.setAttribute('min', hoje);
            
            // Se ainda não tem valor, define como hoje
            if (!el.inputDataPrimeiroPagamento.value) {
                el.inputDataPrimeiroPagamento.value = hoje;
            }
        }
    } else {
        // Esconde o campo e limpa o valor
        el.campoDataPrimeiroPagamento.classList.add('hidden');
        if (el.inputDataPrimeiroPagamento) {
            el.inputDataPrimeiroPagamento.value = '';
        }
    }
}

async function confirmarPedido(el) {
    const clienteId = el.inputClienteId?.value;
    const selectFormaPagamento = document.getElementById('forma_pagamento');
    
    if (!clienteId) {
        alert('Erro: Você precisa buscar o CPF e fazer login/cadastro antes de confirmar o pedido.');
        el.inputClienteCpfBusca?.focus();
        return;
    }
    
    if (!selectFormaPagamento?.value) {
        alert('Por favor, selecione a forma de pagamento.');
        selectFormaPagamento?.focus();
        return;
    }
    
    const numeroParcelas = parseInt(el.inputParcelas?.value, 10) || 1;
    
    // === DEBUG: Verificar estado do campo de data ===
    console.log('🔍 DEBUG - Número de parcelas:', numeroParcelas);
    console.log('🔍 DEBUG - Elemento campo data:', el.campoDataPrimeiroPagamento);
    console.log('🔍 DEBUG - Elemento input data:', el.inputDataPrimeiroPagamento);
    console.log('🔍 DEBUG - Campo está oculto?:', el.campoDataPrimeiroPagamento?.classList.contains('hidden'));
    console.log('🔍 DEBUG - Valor do input data:', el.inputDataPrimeiroPagamento?.value);
    
    // === VALIDAÇÃO: Verifica data do primeiro pagamento para vendas parceladas ===
    if (numeroParcelas > 1) {
        const dataInput = el.inputDataPrimeiroPagamento?.value;
        console.log('🔍 DEBUG - Data capturada:', dataInput);
        
        if (!dataInput) {
            alert('⚠️ Por favor, informe a data do primeiro pagamento para vendas parceladas.');
            el.inputDataPrimeiroPagamento?.focus();
            // Força mostrar o campo se estiver oculto
            el.campoDataPrimeiroPagamento?.classList.remove('hidden');
            return;
        }
        
        // Valida se a data não é anterior a hoje
        const dataInformada = new Date(dataInput);
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        
        if (dataInformada < hoje) {
            alert('⚠️ A data do primeiro pagamento não pode ser anterior à data de hoje.');
            el.inputDataPrimeiroPagamento?.focus();
            return;
        }
    }
    
    const dadosPedido = {
        cliente_id: clienteId,
        observacoes: el.inputObservacoes?.value.trim().toUpperCase() || null,
        numero_parcelas: numeroParcelas,
        forma_pagamento_id: selectFormaPagamento.value
    };
    
    // === ADICIONA: Data do primeiro pagamento se houver parcelas ===
    if (numeroParcelas > 1 && el.inputDataPrimeiroPagamento?.value) {
        dadosPedido.data_primeiro_pagamento = el.inputDataPrimeiroPagamento.value;
        console.log('✅ DEBUG - Campo data_primeiro_pagamento adicionado:', dadosPedido.data_primeiro_pagamento);
    } else if (numeroParcelas > 1) {
        console.error('❌ DEBUG - Parcelas > 1 mas sem data!');
    }
    
    // Adiciona vendedor se selecionado
    if (el.radioTipoVendaVendedor?.checked && el.inputColaboradorVendedorId?.value) {
        dadosPedido.colaborador_vendedor_id = el.inputColaboradorVendedorId.value;
    }
    
    console.log('📤 DEBUG - Dados do pedido que serão enviados:', JSON.stringify(dadosPedido, null, 2));
    
    try {
        const resultado = await finalizarPedido(dadosPedido, getCarrinho());
        alert(resultado.mensagem);
        
        el.modalCarrinho?.classList.add('hidden');
        el.modalClientePedido?.classList.add('hidden');
    } catch (error) {
        console.error('[App] Erro ao finalizar pedido:', error);
        alert(error.message || 'Erro ao salvar pedido.');
    }
}

function alternarCampoVendedor(el) {
    if (el.radioTipoVendaVendedor?.checked) {
        el.campoVendedorCpf?.classList.remove('hidden');
    } else {
        el.campoVendedorCpf?.classList.add('hidden');
        if (el.inputVendedorCpfBusca) el.inputVendedorCpfBusca.value = '';
        if (el.inputColaboradorVendedorId) el.inputColaboradorVendedorId.value = '';
        const vendedorInfoResultado = document.getElementById('vendedor-info-resultado');
        if (vendedorInfoResultado) vendedorInfoResultado.innerHTML = '';
    }
}

// === ATUALIZADA: Reset do formulário incluindo campo de data ===
function resetarFormularioPedido(el) {
    if (el.inputClienteCpfBusca) el.inputClienteCpfBusca.value = '';
    if (el.inputClienteId) el.inputClienteId.value = '';
    
    const clienteInfoResultado = document.getElementById('cliente-info-resultado');
    if (clienteInfoResultado) {
        clienteInfoResultado.innerHTML = '';
        clienteInfoResultado.classList.add('hidden');
    }
    
    if (el.btnConfirmarPedido) el.btnConfirmarPedido.disabled = true;
    setClienteAtual(null);
    
    if (el.inputObservacoes) el.inputObservacoes.value = '';
    if (el.inputParcelas) el.inputParcelas.value = '1';
    atualizarInfoParcelas(null);
    
    // === NOVO: Reset do campo de data ===
    if (el.inputDataPrimeiroPagamento) el.inputDataPrimeiroPagamento.value = '';
    if (el.campoDataPrimeiroPagamento) el.campoDataPrimeiroPagamento.classList.add('hidden');
    
    // Reset vendedor
    if (el.radioTipoVendaCliente) el.radioTipoVendaCliente.checked = true;
    if (el.radioTipoVendaVendedor) el.radioTipoVendaVendedor.checked = false;
    if (el.inputVendedorCpfBusca) el.inputVendedorCpfBusca.value = '';
    if (el.inputColaboradorVendedorId) el.inputColaboradorVendedorId.value = '';
    const vendedorInfoResultado = document.getElementById('vendedor-info-resultado');
    if (vendedorInfoResultado) vendedorInfoResultado.innerHTML = '';
    if (el.campoVendedorCpf) el.campoVendedorCpf.classList.add('hidden');
}

async function processarSincronizacao(catalogoContainer) {
    console.log('[App] Processando sincronização bem-sucedida...');
    await limparDadosLocaisPosSinc();
    atualizarModalCarrinho();
    alert('Pedido sincronizado com sucesso!');
    
    if (estaOnline()) {
        console.log('[App] Recarregando produtos após sincronização.');
        await carregarProdutos(catalogoContainer);
    }
}

async function inicializarAplicacao(catalogoContainer) {
    try {
        await carregarProdutos(catalogoContainer);
        
        const carrinhoSalvo = await carregarCarrinhoStorage();
        setCarrinho(carrinhoSalvo);
        atualizarModalCarrinho();
        atualizarIndicadoresCarrinho();
        
        console.log('[App] Aplicação inicializada.');
    } catch (error) {
        console.error('[App] ERRO NA INICIALIZAÇÃO:', error);
    }
}