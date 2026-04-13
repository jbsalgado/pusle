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
 * Retorna o preço unitário correto com base na quantidade (Escala Inteligente - Lógica 1)
 * Agora suporta teto de preço para evitar que quantidades menores custem mais que as maiores.
 * @param {Object} item - O item do carrinho
 * @param {number} quantidade - A nova quantidade
 */
export function getPrecoVigente(item, quantidade) {
  const q = parseFloat(quantidade) || 0;
  if (q <= 0) return parseFloat(item.preco_venda_sugerido || 0);

  // 1. Extrair todas as escalas válidas
  const escalas = [];
  for (let i = 1; i <= 5; i++) {
    const qtd = parseFloat(item[`qtd_escala_${i}`] || 0);
    const preco = parseFloat(item[`preco_escala_${i}`] || 0); // Preço TOTAL daquela qtd
    if (qtd > 0 && preco > 0) {
      escalas.push({ qtd, preco, unitario: preco / qtd });
    }
  }

  // Se não houver escalas, retorna o preço sugerido (unitário)
  if (escalas.length === 0) {
    return parseFloat(item.preco_venda_sugerido || 0);
  }

  // Ordenar escalas por quantidade crescente
  escalas.sort((a, b) => a.qtd - b.qtd);

  // 2. Encontrar a faixa atingida
  let faixaAtingida = null;
  let proximaFaixa = null;

  for (let i = 0; i < escalas.length; i++) {
    if (q >= escalas[i].qtd) {
      faixaAtingida = escalas[i];
    } else {
      proximaFaixa = escalas[i];
      break;
    }
  }

  // Se estiver abaixo da primeira faixa, usamos o valor unitário da primeira faixa
  if (!faixaAtingida) {
    faixaAtingida = escalas[0];
    proximaFaixa = escalas[1] || null; // O 0 já é o atingido virtual
  }

  // 3. Calcular preço base (Qtd * Unitário da Faixa)
  let precoBase = q * faixaAtingida.unitario;

  // 4. Aplicar TETO (Lógica 1): Se o preço base ultrapassar o preço total da próxima faixa, usamos o da próxima
  if (proximaFaixa && precoBase > proximaFaixa.preco) {
    precoBase = proximaFaixa.preco;
  }

  // Retornamos um "Unitário Virtual" que resulte no preço base correto quando multiplicado no app.js
  return precoBase / q;
}

/**
 * Motor Dinâmico de Precificação por Volume
 * Aplica lógica de desconto informado para escalas menores que o preço sugerido
 * e ajuste de preço unitário para escalas maiores (acréscimos).
 */
function aplicarRegrasEscala(item) {
  const quantidade = parseFloat(item.quantidade || 0);
  if (quantidade <= 0) return;

  const precoSugerido = parseFloat(item.preco_venda_sugerido || 0);
  
  // O motor de escala agora retorna o unitário virtual já considerando o teto
  const precoEscala = getPrecoVigente(item, quantidade);

  // Cenário: Preço de Escala Total difere do Sugerido * Qtd
  // Note: precoEscala é um unitário virtual.
  const totalSugerido = precoSugerido * quantidade;
  const totalReal = precoEscala * quantidade;

  if (Math.abs(totalReal - totalSugerido) > 0.01) {
    // Se o preço total for menor que o sugerido, aplicamos como desconto para o ERP entender
    if (totalReal < totalSugerido) {
      item.descontoValor = Math.round((totalSugerido - totalReal) * 100) / 100;
      item.descontoPercentual = 0;
      item.preco_final = precoSugerido;
    } else {
      // Se for maior (ex: primeira faixa tem valor unitário superior ao sugerido)
      item.preco_final = precoEscala;
      item.descontoValor = 0;
      item.descontoPercentual = 0;
    }
    item.isFilteredScale = true; 
  } else {
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
