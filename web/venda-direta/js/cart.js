// cart.js - Gerenciamento do carrinho de compras

import { salvarCarrinho } from "./storage.js";

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
  return carrinho.some((item) => item.id === produtoId && !item.is_avulso);
}

/**
 * Adiciona produto ao carrinho
 */
export function adicionarAoCarrinho(produto, quantidade) {
  // ✅ CORREÇÃO: O JSON do produto tem 'id'
  if (!produto || !produto.id || !quantidade || quantidade <= 0) {
    return false;
  }

  const itemExistente = carrinho.find((item) => {
    if (produto.is_avulso) {
      return item.id === produto.id && item.nome === produto.nome;
    }
    return item.id === produto.id;
  });

  if (itemExistente) {
    alert("Este item já está no seu carrinho.");
    return false;
  }

  // ✅ CORREÇÃO: Adicionar 'produto_id' manualmente para o backend
  // O backend espera 'produto_id', mas o objeto produto tem 'id'.
  // Vamos adicionar os dois para compatibilidade.
  const itemParaAdicionar = {
    ...produto,
    produto_id: produto.id, // Garante que o backend receba o que espera
    quantidade: quantidade,
  };

  // ✅ Aplica regras de escala/desconto imediatamente
  aplicarRegrasEscala(itemParaAdicionar);

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
  const item = carrinho.find((i) => i.id === produtoId);
  if (item) {
    // Define o passo (0.1 para fracionados, 1 para normais)
    const passo = item.venda_fracionada ? 0.1 : 1;
    
    // ✅ CORREÇÃO: Usar parseFloat e arredondamento para evitar erros de ponto flutuante
    let novaQtd = (parseFloat(item.quantidade) || 0) + passo;
    item.quantidade = Math.round(novaQtd * 1000) / 1000;
    
    // ✅ Aplica motor dinâmico de precificação/desconto
    aplicarRegrasEscala(item);
    
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
  const item = carrinho.find((i) => i.id === produtoId);
  
  if (item) {
    const passo = item.venda_fracionada ? 0.1 : 1;
    const qtdAtual = parseFloat(item.quantidade) || 0;
    
    // Só diminui se for maior que o passo mínimo
    if (qtdAtual > passo) {
      let novaQtd = qtdAtual - passo;
      item.quantidade = Math.round(novaQtd * 1000) / 1000;
      
      // ✅ Aplica motor dinâmico de precificação/desconto
      aplicarRegrasEscala(item);
      
      salvarCarrinho(carrinho);
      return true;
    }
  }
  return false;
}

/**
 * Calcula total do carrinho
 */
// Estado local para acréscimo (não persistido no storage pois é por venda)
let acrescimoAtual = {
  valor: 0,
  tipo: '',
  observacao: ''
};

export function setAcrescimo(valor, tipo, observacao) {
  acrescimoAtual = {
    valor: parseFloat(valor) || 0,
    tipo: tipo || '',
    observacao: observacao || ''
  };
}

export function getAcrescimo() {
  return acrescimoAtual;
}

/**
 * Calcula total do carrinho
 */
export function calcularTotalCarrinho() {
  const totalItens = carrinho.reduce((total, item) => {
    // ✅ CORREÇÃO: Usar preço promocional se disponível (preco_final), senão usar preco_venda_sugerido
    const preco = parseFloat(item.preco_final || item.preco_venda_sugerido || 0);
    // ✅ CORREÇÃO: Garantir que é número (suporta decimais)
    const qtd = parseFloat(item.quantidade || 0);

    let subtotal = preco * qtd;

    // Aplica desconto se houver
    const descontoValor = parseFloat(item.descontoValor || 0);
    const descontoPercentual = parseFloat(item.descontoPercentual || 0);

    let valorDesconto = 0;
    if (descontoValor > 0) {
      valorDesconto = descontoValor;
    } else if (descontoPercentual > 0) {
      valorDesconto = subtotal * (descontoPercentual / 100);
    }

    return total + Math.max(0, subtotal - valorDesconto);
  }, 0);

  // Soma o acréscimo
  return totalItens + (parseFloat(acrescimoAtual.valor) || 0);
}

/**
 * Aplica desconto a um item do carrinho
 * @param {string} produtoId - ID do produto
 * @param {string} tipo - 'valor' ou 'porcentagem'
 * @param {number} valor - Valor do desconto
 */
export function aplicarDescontoItem(produtoId, tipo, valor) {
  const item = carrinho.find((i) => i.id === produtoId);
  if (!item) return false;

  // Se o usuário aplicar um desconto manual, removemos a flag de automático
  // para que a quantidade não sobrescreva a escolha do usuário imediatamente (opcional)
  item.isFilteredScale = false; 

  if (tipo === "valor") {
    item.descontoValor = parseFloat(valor);
    item.descontoPercentual = 0;
  } else {
    item.descontoPercentual = parseFloat(valor);
    item.descontoValor = 0;
  }

  salvarCarrinho(carrinho);
  return true;
}

/**
 * Calcula total de itens no carrinho
 */
export function calcularTotalItens() {
  // ✅ CORREÇÃO: Garantir que é número
  return carrinho.reduce(
    (acc, item) => acc + (parseFloat(item.quantidade) || 0),
    0
  );
}

/**
 * Limpa o carrinho
 */
export async function limparCarrinho() {
  carrinho = [];
  await salvarCarrinho(carrinho); // ✅ CORREÇÃO: Aguarda salvar o array vazio no IndexedDB

  // NOVO: Atualiza visualmente todos os cards para remover o indicador 'no carrinho'
  const todosCards = document.querySelectorAll(`[data-produto-card]`);
  todosCards.forEach((card) => {
    const badge = card.querySelector(".badge-no-carrinho");
    if (badge) {
      badge.classList.add("hidden");
    }
  });
}

/**
 * Atualiza indicadores visuais dos cards de produtos
 */
export function atualizarIndicadoresCarrinho() {
  // Esconde todos os badges primeiro
  document
    .querySelectorAll(".badge-no-carrinho")
    .forEach((badge) => badge.classList.add("hidden"));

  // Mostra apenas para itens que estão no carrinho
  carrinho.forEach((item) => {
    // ✅ CORREÇÃO: Buscar por 'id'
    const card = document.querySelector(`[data-produto-card="${item.id}"]`);
    if (card) {
      const badge = card.querySelector(".badge-no-carrinho");
      if (badge) {
        badge.classList.remove("hidden");
      }
    }
  });
}

/**
 * Retorna o preço unitário correto com base na quantidade (Escala)
 * @param {Object} item - O item do carrinho
 * @param {number} quantidade - A nova quantidade
 */
export function getPrecoVigente(item, quantidade) {
  // Preço base é o preço de venda normal
  let precoFinal = parseFloat(item.preco_venda_sugerido || 0);

  // Percorre as 5 escalas (da maior quantidade para a menor)
  // Isso garante que se a pessoa atingir a escala 3, ela pegue o preço da 3 e não o da 1.
  for (let i = 5; i >= 1; i--) {
    const qtdEscala = parseFloat(item[`qtd_escala_${i}`] || 0);
    const precoEscala = parseFloat(item[`preco_escala_${i}`] || 0);

    if (qtdEscala > 0 && precoEscala > 0 && quantidade >= qtdEscala) {
      precoFinal = precoEscala;
      break; // Encontrou a maior escala atingida
    }
  }

  return precoFinal;
}

/**
 * Motor Dinâmico de Precificação por Volume
 * Aplica lógica de desconto informado para escalas menores que o preço sugerido
 * e ajuste de preço unitário para escalas maiores (acréscimos).
 */
function aplicarRegrasEscala(item) {
  const quantidade = parseFloat(item.quantidade || 0);
  const precoSugerido = parseFloat(item.preco_venda_sugerido || 0);
  const precoEscala = getPrecoVigente(item, quantidade);

  // Cenário A: Preço de Escala < Preço Sugerido (DESCONTO AUTOMÁTICO)
  if (precoEscala > 0 && precoEscala < precoSugerido) {
    const totalSemDesconto = precoSugerido * quantidade;
    const totalComEscala = precoEscala * quantidade;
    
    // Injeta a diferença no campo de desconto (ex: 6 * 180 - 1000 = 80)
    item.descontoValor = Math.round((totalSemDesconto - totalComEscala) * 100) / 100;
    item.descontoPercentual = 0;
    item.preco_final = precoSugerido; // Mantém preço base no display
    item.isFilteredScale = true; // Identifica que é um desconto de volume
  } 
  // Cenário B: Preço de Escala > Preço Sugerido (ACRÉSCIMO/AJUSTE DE TABELA)
  else if (precoEscala > precoSugerido) {
    item.preco_final = precoEscala; // Ajusta o unitário (ex: 0.5 M3 -> 200)
    item.descontoValor = 0;
    item.descontoPercentual = 0;
    item.isFilteredScale = false;
  }
  // Cenário C: Fora de escala ou Preço Sugerido (RESETA SE ERA AUTOMÁTICO)
  else {
    item.preco_final = precoSugerido;
    if (item.isFilteredScale) {
        item.descontoValor = 0;
        item.isFilteredScale = false;
    }
  }
}

/**
 * Atualiza badge de um produto específico
 */
export function atualizarBadgeProduto(produtoId, mostrar) {
  // ✅ CORREÇÃO: Buscar por 'id'
  const card = document.querySelector(`[data-produto-card="${produtoId}"]`);
  if (card) {
    const badge = card.querySelector(".badge-no-carrinho");
    if (badge) {
      if (mostrar) {
        badge.classList.remove("hidden");
      } else {
        badge.classList.add("hidden");
      }
    }
  }
}
