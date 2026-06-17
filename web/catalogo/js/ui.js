// ui.js - Funções de gerenciamento da interface

import { getCarrinho, calcularTotalCarrinho, calcularTotalItens } from './cart.js';
import { formatarCPF } from './utils.js';

/**
 * Atualiza modal do carrinho
 */
export function atualizarModalCarrinho() {
    const itensCarrinhoContainer = document.getElementById('itens-carrinho');
    const valorTotalCarrinho = document.getElementById('valor-total-carrinho');
    const btnFinalizarPedido = document.getElementById('btn-finalizar-pedido');
    const contadorCarrinho = document.getElementById('contador-carrinho');
    
    if (!itensCarrinhoContainer) return;
    
    const carrinho = getCarrinho();
    
    if (carrinho.length === 0) {
        renderizarCarrinhoVazio(itensCarrinhoContainer, btnFinalizarPedido, contadorCarrinho, valorTotalCarrinho);
    } else {
        renderizarCarrinhoComItens(itensCarrinhoContainer, carrinho, btnFinalizarPedido, contadorCarrinho, valorTotalCarrinho);
    }
}

/**
 * Renderiza carrinho vazio
 */
function renderizarCarrinhoVazio(container, btnFinalizar, contador, valorTotal) {
    container.innerHTML = `
        <div id="carrinho-vazio-msg" class="text-center text-gray-500 py-12 flex flex-col items-center justify-center h-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <p class="text-lg font-medium">Seu carrinho está vazio</p>
            <p class="text-sm text-gray-400 mt-1">Adicione produtos para começar</p>
        </div>
    `;
    
    if (btnFinalizar) btnFinalizar.disabled = true;
    if (contador) contador.classList.add('hidden');
    if (valorTotal) valorTotal.textContent = 'R$ 0,00';
    
    const totalItensFooter = document.getElementById('total-itens-footer');
    if (totalItensFooter) totalItensFooter.textContent = '0';
}

/**
 * Renderiza carrinho com itens
 */
function renderizarCarrinhoComItens(container, carrinho, btnFinalizar, contador, valorTotal) {
    container.innerHTML = '';
    
    carrinho.forEach((item, index) => {
        const cardItem = criarCardItemCarrinho(item, index);
        container.appendChild(cardItem);
    });
    
    const total = calcularTotalCarrinho();
    if (valorTotal) valorTotal.textContent = `R$ ${total.toFixed(2)}`;
    if (btnFinalizar) btnFinalizar.disabled = false;
    
    const totalItens = calcularTotalItens();
    if (contador) {
        contador.textContent = totalItens;
        contador.classList.remove('hidden');
    }
    
    const totalItensFooter = document.getElementById('total-itens-footer');
    if (totalItensFooter) totalItensFooter.textContent = totalItens;
}

/**
 * Cria card de item do carrinho
 */
function criarCardItemCarrinho(item, index) {
    const preco = parseFloat(item.preco_unitario || 0);
    const qtd = parseInt(item.quantidade || 0, 10);
    const subtotal = preco * qtd;

    const cardItem = document.createElement('div');
    cardItem.className = 'bg-white rounded-xl shadow-md border border-gray-200 p-4 relative hover:shadow-xl transition-shadow';
    
    cardItem.innerHTML = `
        <button data-index="${index}" class="remover-item-carrinho absolute top-2 right-2 w-8 h-8 flex items-center justify-center rounded-full bg-red-50 hover:bg-red-100 text-red-500 transition-all z-10" title="Remover item">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" />
            </svg>
        </button>
        
        <div class="flex justify-center items-center mb-3 bg-gray-50 rounded-lg p-3">
            <img src="${item.imagem || 'https://via.placeholder.com/150'}" alt="${item.nome || ''}" class="w-32 h-32 object-contain">
        </div>
        
        <h3 class="text-center font-semibold text-gray-800 text-sm mb-3 px-2 line-clamp-2 min-h-[2.5rem]" title="${item.nome || 'Item'}">
            ${item.nome || 'Item'}
        </h3>
        
        <div class="flex items-center justify-center gap-3 mb-4">
            <button data-index="${index}" class="btn-diminuir-item w-10 h-10 flex items-center justify-center rounded-full bg-red-500 hover:bg-red-600 text-white font-bold text-xl disabled:bg-gray-300 disabled:cursor-not-allowed transition-all shadow-md active:scale-95" title="Diminuir quantidade" ${qtd <= 1 ? 'disabled' : ''}>-</button>
            
            <div class="w-12 h-12 flex items-center justify-center rounded-full bg-yellow-400 border-4 border-yellow-500 shadow-lg">
                <span class="font-bold text-gray-900 text-xl">${qtd}</span>
            </div>
            
            <button data-index="${index}" class="btn-aumentar-item w-10 h-10 flex items-center justify-center rounded-full bg-green-500 hover:bg-green-600 text-white font-bold text-xl transition-all shadow-md active:scale-95" title="Aumentar quantidade">+</button>
        </div>
        
        <div class="text-center border-t pt-3">
            <p class="text-xs text-gray-600 mb-1 font-medium">Val. do Item</p>
            <p class="text-2xl font-bold text-gray-900">R$ ${subtotal.toFixed(2)}</p>
        </div>
        
        <p class="text-center text-xs text-gray-500 mt-2">R$ ${preco.toFixed(2)} / unidade</p>
    `;
    
    return cardItem;
}

/**
 * Mostra mensagem de erro no modal de login
 */
export function mostrarErroLogin(mensagem) {
    const loginClienteErroMsg = document.getElementById('login-cliente-erro-msg');
    const loginClienteErros = document.getElementById('login-cliente-erros');
    
    if (loginClienteErroMsg) loginClienteErroMsg.textContent = mensagem;
    if (loginClienteErros) loginClienteErros.classList.remove('hidden');
}

/**
 * Esconde mensagem de erro no modal de login
 */
export function esconderErroLogin() {
    const loginClienteErros = document.getElementById('login-cliente-erros');
    if (loginClienteErros) loginClienteErros.classList.add('hidden');
}

/**
 * Mostra mensagem de erro no modal de cadastro
 */
export function mostrarErroCadastro(mensagem) {
    const cadastroClienteErroMsg = document.getElementById('cadastro-cliente-erro-msg');
    const cadastroClienteErros = document.getElementById('cadastro-cliente-erros');
    
    if (cadastroClienteErroMsg) cadastroClienteErroMsg.textContent = mensagem;
    if (cadastroClienteErros) cadastroClienteErros.classList.remove('hidden');
}

/**
 * Esconde mensagem de erro no modal de cadastro
 */
export function esconderErroCadastro() {
    const cadastroClienteErros = document.getElementById('cadastro-cliente-erros');
    if (cadastroClienteErros) cadastroClienteErros.classList.add('hidden');
}

/**
 * Limpa formulário de cadastro
 */
export function limparFormularioCadastro() {
    const campos = [
        'cadastro-nome', 'cadastro-telefone', 'cadastro-email', 'cadastro-senha',
        'cadastro-logradouro', 'cadastro-numero', 'cadastro-complemento',
        'cadastro-bairro', 'cadastro-cidade', 'cadastro-estado', 'cadastro-cep'
    ];
    
    campos.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) campo.value = '';
    });
    
    esconderErroCadastro();
}

/**
 * Atualiza informações do cliente na tela
 */
export function atualizarInfoCliente(cliente) {
    const clienteInfoResultado = document.getElementById('cliente-info-resultado');
    
    if (clienteInfoResultado && cliente) {
        clienteInfoResultado.innerHTML = `
            <p class="text-green-600 font-semibold">Cliente: ${cliente.nome_completo}</p>
            <p class="text-gray-600 text-xs">CPF: ${formatarCPF(cliente.cpf)}</p>
        `;
        clienteInfoResultado.classList.remove('hidden');
    }
}

/**
 * Atualiza informações do vendedor na tela
 */
export function atualizarInfoVendedor(vendedor, sucesso = true) {
    const vendedorInfoResultado = document.getElementById('vendedor-info-resultado');
    
    if (!vendedorInfoResultado) return;
    
    if (sucesso && vendedor) {
        vendedorInfoResultado.innerHTML = `
            <p class="text-green-600 font-semibold">✓ Vendedor: ${vendedor.nome_completo}</p>
            <p class="text-gray-600 text-xs">CPF: ${formatarCPF(vendedor.cpf)}</p>
        `;
    } else {
        vendedorInfoResultado.innerHTML = `
            <p class="text-red-600 font-semibold">✗ Vendedor não encontrado</p>
            <p class="text-gray-600 text-xs">Verifique o CPF ou cadastre o vendedor no sistema</p>
        `;
    }
}

/**
 * Popula select de formas de pagamento
 */
export function popularFormasPagamento(formas) {
    const selectFormaPagamento = document.getElementById('forma_pagamento');
    if (!selectFormaPagamento) return;
    
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
}

/**
 * Atualiza informações de parcelas
 */
export function atualizarInfoParcelas(htmlParcelas) {
    const parcelaInfoResultado = document.getElementById('parcela-info-resultado');
    if (!parcelaInfoResultado) return;
    
    if (htmlParcelas) {
        parcelaInfoResultado.innerHTML = htmlParcelas;
        parcelaInfoResultado.style.display = 'block';
    } else {
        parcelaInfoResultado.innerHTML = '';
        parcelaInfoResultado.style.display = 'none';
    }
}