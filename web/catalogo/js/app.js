// app.js - Arquivo principal que orquestra todos os módulos

// --- MODIFICADO: Importar CONFIG junto com ELEMENTOS_CRITICOS ---
import { ELEMENTOS_CRITICOS, CONFIG } from './config.js';
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
// --- MODIFICADO: Remover a importação de getIdUsuarioLoja ---
import { carregarProdutos } from './products.js';
import { 
    buscarClientePorCpf, 
    cadastrarCliente,
    getClienteAtual,
    setClienteAtual 
} from './customer.js';
import { buscarVendedorPorCpf } from './seller.js';
import { carregarFormasPagamento, calcularParcelas, formatarInfoParcelas } from './payment.js';
import { finalizarPedido } from './order.js';
import { 
    atualizarModalCarrinho,
    mostrarErroCadastro,
    esconderErroCadastro,
    limparFormularioCadastro,
    atualizarInfoCliente,
    atualizarInfoVendedor,
    popularFormasPagamento,
    atualizarInfoParcelas
} from './ui.js';
import { inicializarGaleria } from './gallery.js';

document.addEventListener('DOMContentLoaded', async () => {
    console.log('[App] DOM Carregado. Iniciando...');

    verificarElementosCriticos(ELEMENTOS_CRITICOS);
    inicializarServiceWorker();
    inicializarMonitoramentoRede();
    inicializarGaleria(); // Inicializar galeria de fotos

    const elementos = obterElementosDOM();
    configurarEventListeners(elementos);

    adicionarListenerMensagensSW(async (data) => {
        if (data.type === 'SYNC_SUCCESS') {
            await processarSincronizacao(elementos.catalogoContainer);
        }
    });

    await inicializarAplicacao(elementos.catalogoContainer);

    console.log('[App] Aplicação iniciada com sucesso!');
});

function obterElementosDOM() {
    return {
        catalogoContainer: document.getElementById('catalogo-produtos'),
        btnAbrirCarrinho: document.getElementById('btn-abrir-carrinho'),
        modalCarrinho: document.getElementById('modal-carrinho'),
        btnFecharCarrinho: document.getElementById('btn-fechar-carrinho'),
        btnFinalizarPedido: document.getElementById('btn-finalizar-pedido'),
        itensCarrinhoContainer: document.getElementById('itens-carrinho'),
        modalClientePedido: document.getElementById('modal-cliente-pedido'),
        btnFecharModalCliente: document.getElementById('btn-fechar-modal-cliente'),
        btnBuscarCliente: document.getElementById('btn-buscar-cliente'),
        inputClienteCpfBusca: document.getElementById('cliente-cpf-busca'),
        inputClienteId: document.getElementById('cliente_id'),
        btnConfirmarPedido: document.getElementById('btn-confirmar-pedido'),
        inputObservacoes: document.getElementById('observacoes'),
        inputParcelas: document.getElementById('numero_parcelas'),
        campoDataPrimeiroPagamento: document.getElementById('campo-data-primeiro-pagamento'),
        inputDataPrimeiroPagamento: document.getElementById('data_primeiro_pagamento'),
        campoIntervaloParcelas: document.getElementById('campo-intervalo-parcelas'),
        inputIntervaloParcelas: document.getElementById('intervalo_dias_parcelas'),
        radioTipoVendaCliente: document.getElementById('tipo_venda_cliente'),
        radioTipoVendaVendedor: document.getElementById('tipo_venda_vendedor'),
        campoVendedorCpf: document.getElementById('campo-vendedor-cpf'),
        inputVendedorCpfBusca: document.getElementById('vendedor_cpf_busca'),
        btnBuscarVendedor: document.getElementById('btn-buscar-vendedor'),
        inputColaboradorVendedorId: document.getElementById('colaborador_vendedor_id'),
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

function configurarEventListeners(el) {
    configurarListenersCatalogo(el);
    configurarListenersCarrinho(el);
    configurarListenersModalPedido(el);
    configurarListenersCliente(el);
    configurarListenersVendedor(el);
    configurarListenersCadastro(el);
}

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

function configurarListenersCarrinho(el) {
    if (el.btnAbrirCarrinho) {
        el.btnAbrirCarrinho.addEventListener('click', () => {
            el.modalCarrinho?.classList.remove('hidden');
        });
    }
    
    if (el.btnFecharCarrinho) {
        el.btnFecharCarrinho.addEventListener('click', () => {
            el.modalCarrinho?.classList.add('hidden');
        });
    }
    
    if (el.btnFinalizarPedido) {
        el.btnFinalizarPedido.addEventListener('click', async () => {
            el.modalCarrinho?.classList.add('hidden');
            el.modalClientePedido?.classList.remove('hidden');
            
            // --- MODIFICADO: Usar CONFIG.ID_USUARIO_LOJA ---
            await carregarFormasPagamento(CONFIG.ID_USUARIO_LOJA)
                .then(formas => popularFormasPagamento(formas))
                .catch(error => console.error('[App] Erro ao carregar formas:', error));
            
            resetarFormularioPedido(el);
        });
    }
    
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

function configurarListenersModalPedido(el) {
    if (el.btnFecharModalCliente) {
        el.btnFecharModalCliente.addEventListener('click', () => {
            el.modalClientePedido?.classList.add('hidden');
        });
    }
    
    if (el.inputParcelas) {
        el.inputParcelas.addEventListener('change', async () => {
            await calcularEAtualizarParcelas(el);
            alternarCampoDataPrimeiroPagamento(el);
        });
    }
    
    if (el.btnConfirmarPedido) {
        el.btnConfirmarPedido.addEventListener('click', async () => {
            await confirmarPedido(el);
        });
    }
}

function configurarListenersCliente(el) {
    if (el.btnBuscarCliente) {
        el.btnBuscarCliente.addEventListener('click', async (e) => {
            e.preventDefault();
            await processarBuscaCliente(el);
        });
    }
    
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

function configurarListenersVendedor(el) {
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
    
    if (el.btnBuscarVendedor) {
        el.btnBuscarVendedor.addEventListener('click', async (e) => {
            e.preventDefault();
            await processarBuscaVendedor(el);
        });
    }
    
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
        // --- MODIFICADO: Usar CONFIG.ID_USUARIO_LOJA ---
        const resultado = await buscarClientePorCpf(cpf, CONFIG.ID_USUARIO_LOJA);
        
        if (resultado.existe) {
            // Cliente encontrado - preenche dados automaticamente (SEM login)
            setClienteAtual(resultado.cliente);
            el.inputClienteId.value = resultado.cliente.id;
            atualizarInfoCliente(resultado.cliente);
            
            if (el.btnConfirmarPedido) el.btnConfirmarPedido.disabled = false;
            
            await calcularEAtualizarParcelas(el);
            alternarCampoDataPrimeiroPagamento(el);
            
            alert(`Cliente ${resultado.cliente.nome_completo} identificado com sucesso!`);
        } else {
            // Cliente não existe - abre modal de cadastro
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

async function processarCadastro(el) {
    const dadosCliente = {
        // --- MODIFICADO: Usar CONFIG.ID_USUARIO_LOJA ---
        usuario_id: CONFIG.ID_USUARIO_LOJA,
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
        alternarCampoDataPrimeiroPagamento(el);
        
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
        // --- MODIFICADO: Usar CONFIG.ID_USUARIO_LOJA ---
        const resultado = await buscarVendedorPorCpf(cpf, CONFIG.ID_USUARIO_LOJA);
        
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
        // --- MODIFICADO: Usar CONFIG.ID_USUARIO_LOJA ---
        const dadosParcela = await calcularParcelas(valorBase, numeroParcelas, CONFIG.ID_USUARIO_LOJA);
        const htmlParcelas = formatarInfoParcelas(dadosParcela);
        atualizarInfoParcelas(htmlParcelas);
    } catch (error) {
        console.error('[App] Erro ao calcular parcelas:', error);
        atualizarInfoParcelas('<span class="text-red-500 text-xs">Não foi possível calcular o valor da parcela.</span>');
    }
}

function alternarCampoDataPrimeiroPagamento(el) {
    if (!el.campoDataPrimeiroPagamento || !el.inputParcelas) return;
    
    const numeroParcelas = parseInt(el.inputParcelas.value, 10);
    
    if (numeroParcelas > 1) {
        el.campoDataPrimeiroPagamento.classList.remove('hidden');
        if (el.campoIntervaloParcelas) {
            el.campoIntervaloParcelas.classList.remove('hidden');
        }
        
        if (el.inputDataPrimeiroPagamento) {
            const hoje = new Date();
            const dataHoje = hoje.toISOString().split('T')[0];
            el.inputDataPrimeiroPagamento.setAttribute('min', dataHoje);
            
            if (!el.inputDataPrimeiroPagamento.value) {
                el.inputDataPrimeiroPagamento.value = dataHoje;
            }
        }
        
        if (el.inputIntervaloParcelas && !el.inputIntervaloParcelas.value) {
            el.inputIntervaloParcelas.value = '30';
        }
    } else {
        el.campoDataPrimeiroPagamento.classList.add('hidden');
        if (el.campoIntervaloParcelas) {
            el.campoIntervaloParcelas.classList.add('hidden');
        }
        
        if (el.inputDataPrimeiroPagamento) {
            el.inputDataPrimeiroPagamento.value = '';
        }
        if (el.inputIntervaloParcelas) {
            el.inputIntervaloParcelas.value = '';
        }
    }
}

async function confirmarPedido(el) {
    const clienteId = el.inputClienteId?.value;
    const selectFormaPagamento = document.getElementById('forma_pagamento');
    
    if (!clienteId) {
        alert('Erro: Você precisa buscar o CPF do cliente antes de confirmar o pedido.');
        el.inputClienteCpfBusca?.focus();
        return;
    }
    
    if (!selectFormaPagamento?.value) {
        alert('Por favor, selecione a forma de pagamento.');
        selectFormaPagamento?.focus();
        return;
    }
    
    const numeroParcelas = parseInt(el.inputParcelas?.value, 10) || 1;
    
    if (numeroParcelas > 1) {
        const dataInput = el.inputDataPrimeiroPagamento?.value;
        
        if (!dataInput) {
            alert('⚠️ Por favor, informe a data do primeiro pagamento para vendas parceladas.');
            el.inputDataPrimeiroPagamento?.focus();
            el.campoDataPrimeiroPagamento?.classList.remove('hidden');
            return;
        }
        
        const partesData = dataInput.split('-');
        const dataInformada = new Date(partesData[0], partesData[1] - 1, partesData[2]);
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        dataInformada.setHours(0, 0, 0, 0);
        
        if (dataInformada < hoje) {
            alert('⚠️ A data do primeiro pagamento não pode ser anterior à data de hoje.');
            el.inputDataPrimeiroPagamento?.focus();
            return;
        }
        
        const intervaloDias = parseInt(el.inputIntervaloParcelas?.value, 10);
        
        if (!intervaloDias || intervaloDias < 1) {
            alert('⚠️ Por favor, informe um intervalo válido entre as parcelas (mínimo 1 dia).');
            el.inputIntervaloParcelas?.focus();
            el.campoIntervaloParcelas?.classList.remove('hidden');
            return;
        }
        
        if (intervaloDias > 365) {
            alert('⚠️ O intervalo entre parcelas não pode ser maior que 365 dias.');
            el.inputIntervaloParcelas?.focus();
            return;
        }
    }
    
    const dadosPedido = {
        cliente_id: clienteId,
        observacoes: el.inputObservacoes?.value.trim().toUpperCase() || null,
        numero_parcelas: numeroParcelas,
        forma_pagamento_id: selectFormaPagamento.value
    };
    
    if (numeroParcelas > 1) {
        if (el.inputDataPrimeiroPagamento?.value) {
            dadosPedido.data_primeiro_pagamento = el.inputDataPrimeiroPagamento.value;
        }
        
        if (el.inputIntervaloParcelas?.value) {
            dadosPedido.intervalo_dias_parcelas = parseInt(el.inputIntervaloParcelas.value, 10);
        }
    }
    
    if (el.radioTipoVendaVendedor?.checked && el.inputColaboradorVendedorId?.value) {
        dadosPedido.colaborador_vendedor_id = el.inputColaboradorVendedorId.value;
    }
    
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
    
    if (el.inputDataPrimeiroPagamento) el.inputDataPrimeiroPagamento.value = '';
    if (el.campoDataPrimeiroPagamento) el.campoDataPrimeiroPagamento.classList.add('hidden');
    
    if (el.inputIntervaloParcelas) el.inputIntervaloParcelas.value = '';
    if (el.campoIntervaloParcelas) el.campoIntervaloParcelas.classList.add('hidden');
    
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