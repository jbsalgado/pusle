// cart.js - Gerenciamento do carrinho de compras

import { salvarCarrinho } from './storage.js';

let carrinho = [];

/**
 * Obtém o carrinho atual
 */
export function getCarrinho() {
    return carrinho;
}

/**
 * Define o carrinho
 */
export function setCarrinho(novoCarrinho) {
    carrinho = novoCarrinho;
}

/**
 * Verifica se produto está no carrinho
 */
export function produtoEstaNoCarrinho(produtoId) {
    return carrinho.some(item => item.produto_id === produtoId);
}

/**
 * Adiciona produto ao carrinho
 */
export function adicionarAoCarrinho(produto, quantidade) {
    if (!produto || !produto.produto_id || !quantidade || quantidade <= 0) {
        return false;
    }
    
    const itemExistente = carrinho.find(item => item.produto_id === produto.produto_id);
    
    if (itemExistente) {
        alert('Este item já está no seu carrinho.');
        return false;
    }
    
    produto.quantidade = quantidade;
    carrinho.push(produto);
    
    salvarCarrinho(carrinho);
    
    return true;
}

/**
 * Remove produto do carrinho
 */
export function removerDoCarrinho(index) {
    if (index >= 0 && index < carrinho.length) {
        const produtoId = carrinho[index].produto_id;
        carrinho.splice(index, 1);
        salvarCarrinho(carrinho);
        return produtoId;
    }
    return null;
}

/**
 * Aumenta quantidade de um item
 */
export function aumentarQuantidadeItem(index) {
    if (index >= 0 && index < carrinho.length) {
        carrinho[index].quantidade++;
        salvarCarrinho(carrinho);
        return true;
    }
    return false;
}

/**
 * Diminui quantidade de um item
 */
export function diminuirQuantidadeItem(index) {
    if (index >= 0 && index < carrinho.length) {
        if (carrinho[index].quantidade > 1) {
            carrinho[index].quantidade--;
            salvarCarrinho(carrinho);
            return true;
        }
    }
    return false;
}

/**
 * Calcula total do carrinho
 */
export function calcularTotalCarrinho() {
    return carrinho.reduce((total, item) => {
        const preco = parseFloat(item.preco_unitario || 0);
        const qtd = parseInt(item.quantidade || 0, 10);
        return total + (preco * qtd);
    }, 0);
}

/**
 * Calcula total de itens no carrinho
 */
export function calcularTotalItens() {
    return carrinho.reduce((acc, item) => acc + (item.quantidade || 0), 0);
}

/**
 * Limpa o carrinho
 */
export function limparCarrinho() {
    carrinho = [];
    salvarCarrinho(carrinho);
}

/**
 * Atualiza indicadores visuais dos cards de produtos
 */
export function atualizarIndicadoresCarrinho() {
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

/**
 * Atualiza badge de um produto específico
 */
export function atualizarBadgeProduto(produtoId, mostrar) {
    const card = document.querySelector(`[data-produto-card="${produtoId}"]`);
    if (card) {
        const badge = card.querySelector('.badge-no-carrinho');
        if (badge) {
            if (mostrar) {
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }
}