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
    // ✅ CORREÇÃO: O produto no carrinho agora terá 'id' e não 'produto_id'
    return carrinho.some(item => item.id === produtoId);
}

/**
 * Adiciona produto ao carrinho
 */
export function adicionarAoCarrinho(produto, quantidade) {
     // ✅ CORREÇÃO: O JSON do produto tem 'id'
    if (!produto || !produto.id || !quantidade || quantidade <= 0) {
        return false;
    }
    
    // ✅ CORREÇÃO: Buscar por 'id'
    const itemExistente = carrinho.find(item => item.id === produto.id);
    
    if (itemExistente) {
        alert('Este item já está no seu carrinho.');
        return false;
    }
    
    // ✅ CORREÇÃO: Adicionar 'produto_id' manualmente para o backend
    // O backend espera 'produto_id', mas o objeto produto tem 'id'.
    // Vamos adicionar os dois para compatibilidade.
    const itemParaAdicionar = {
        ...produto,
        produto_id: produto.id, // Garante que o backend receba o que espera
        quantidade: quantidade
    };
    
    carrinho.push(itemParaAdicionar);
    
    salvarCarrinho(carrinho);
    
    return true;
}

/**
 * Remove produto do carrinho
 */
export function removerDoCarrinho(index) {
    if (index >= 0 && index < carrinho.length) {
         // ✅ CORREÇÃO: Ler 'id'
        const produtoId = carrinho[index].id;
        carrinho.splice(index, 1);
        salvarCarrinho(carrinho);
        return produtoId;
    }
    return null;
}

/**
 * Aumenta a quantidade de um item
 */
export function aumentarQuantidadeItem(produtoId) {
    // ✅ CORREÇÃO: Buscar por 'id'
    const item = carrinho.find(i => i.id === produtoId);
    if (item) {
        // ✅ CORREÇÃO: Garantir que é número
        item.quantidade = (parseInt(item.quantidade, 10) || 0) + 1;
        salvarCarrinho(carrinho);
        return true;
    }
    return false;
}

/**
 * Diminui a quantidade de um item
 */
export function diminuirQuantidadeItem(produtoId) {
     // ✅ CORREÇÃO: Buscar por 'id'
    const item = carrinho.find(i => i.id === produtoId);
    if (item && item.quantidade > 1) {
         // ✅ CORREÇÃO: Garantir que é número
        item.quantidade = (parseInt(item.quantidade, 10) || 0) - 1;
        salvarCarrinho(carrinho);
        return true;
    }
    return false;
}

/**
 * Calcula total do carrinho
 */
export function calcularTotalCarrinho() {
    return carrinho.reduce((total, item) => {
        // ✅ CORREÇÃO: Usar 'preco_venda_sugerido'
        const preco = parseFloat(item.preco_venda_sugerido || 0);
         // ✅ CORREÇÃO: Garantir que é número
        const qtd = parseInt(item.quantidade || 0, 10);
        return total + (preco * qtd);
    }, 0);
}

/**
 * Calcula total de itens no carrinho
 */
export function calcularTotalItens() {
    // ✅ CORREÇÃO: Garantir que é número
    return carrinho.reduce((acc, item) => acc + (parseInt(item.quantidade, 10) || 0), 0);
}

/**
 * Limpa o carrinho
 */
export function limparCarrinho() {
    carrinho = [];
    salvarCarrinho(carrinho); // Salva o array vazio no IndexedDB
    
    // NOVO: Atualiza visualmente todos os cards para remover o indicador 'no carrinho'
    const todosCards = document.querySelectorAll(`[data-produto-card]`);
    todosCards.forEach(card => {
        const badge = card.querySelector('.badge-no-carrinho');
        if (badge) {
            badge.classList.add('hidden');
        }
    });
}

/**
 * Atualiza indicadores visuais dos cards de produtos
 */
export function atualizarIndicadoresCarrinho() {
    // Esconde todos os badges primeiro
    document.querySelectorAll('.badge-no-carrinho').forEach(badge => badge.classList.add('hidden'));

    // Mostra apenas para itens que estão no carrinho
    carrinho.forEach(item => {
        // ✅ CORREÇÃO: Buscar por 'id'
        const card = document.querySelector(`[data-produto-card="${item.id}"]`);
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
     // ✅ CORREÇÃO: Buscar por 'id'
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