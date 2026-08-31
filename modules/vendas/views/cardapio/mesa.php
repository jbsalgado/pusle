<?php

use yii\helpers\Html;
use yii\helpers\Url;

$nomeLoja = ($loja && !empty($loja->nome)) ? $loja->nome : 'PULSE Food Service';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio Digital — Mesa <?= Html::encode($mesa->numero_mesa) ?></title>
    <?= Html::csrfMetaTags() ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 pb-24">

    <!-- Header Fixo Topo -->
    <header class="sticky top-0 z-40 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 text-white shadow-lg p-4 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-black tracking-tight leading-none"><?= Html::encode($nomeLoja) ?></h1>
            <span class="text-xs text-emerald-100 font-semibold mt-1 inline-block">Mesa <?= Html::encode($mesa->numero_mesa) ?></span>
        </div>

        <div class="flex items-center space-x-2">
            <button type="button" onclick="chamarGarcomAction()" class="px-3 py-1.5 bg-amber-400 hover:bg-amber-500 text-gray-950 font-black text-xs rounded-xl shadow transition flex items-center gap-1 active:scale-95">
                <span>🔔</span>
                <span>Garçom</span>
            </button>

            <button type="button" onclick="pedirContaAction()" class="px-3 py-1.5 bg-rose-500 hover:bg-rose-600 text-white font-black text-xs rounded-xl shadow transition flex items-center gap-1 active:scale-95">
                <span>🧾</span>
                <span>Conta</span>
            </button>
        </div>
    </header>

    <!-- Banner de Boas-Vindas da Mesa -->
    <div class="p-4">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200/80 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-gray-500 uppercase">Você está na</span>
                <h2 class="text-xl font-black text-emerald-700">Mesa <?= Html::encode($mesa->numero_mesa) ?></h2>
                <p class="text-[11px] text-gray-500">Escolha os pratos e bebidas e envie seu pedido direto para a cozinha!</p>
            </div>
            <div class="text-4xl">🍹</div>
        </div>
    </div>

    <!-- Categorias (Carrossel Horizontal) -->
    <div class="px-4 overflow-x-auto whitespace-nowrap scrollbar-none flex space-x-2 mb-4">
        <button type="button" onclick="filtrarCategoria('todas')" class="btn-cat active px-4 py-2 bg-emerald-600 text-white font-extrabold text-xs rounded-full shadow transition">
            Todas
        </button>
        <?php foreach ($categorias as $cat): ?>
            <button type="button" onclick="filtrarCategoria('cat-<?= $cat->id ?>')" class="btn-cat px-4 py-2 bg-white text-gray-700 font-bold text-xs rounded-full shadow-sm border border-gray-200 transition">
                <?= Html::encode($cat->nome) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Lista de Produtos -->
    <div class="px-4 space-y-3">
        <?php foreach ($produtos as $p): ?>
            <?php 
                $catClass = $p->categoria_id ? 'cat-' . $p->categoria_id : 'cat-outras';
                $fotoObj = $p->fotoPrincipal;
                $fotoUrl = ($fotoObj && method_exists($fotoObj, 'getUrl')) ? $fotoObj->getUrl() : 'https://placehold.co/100x100?text=Produto';
            ?>
            <div class="card-produto <?= $catClass ?> bg-white rounded-2xl p-3 shadow-sm border border-gray-200/80 flex gap-3">
                <img src="<?= Html::encode($fotoUrl) ?>" class="w-20 h-20 rounded-xl object-cover flex-shrink-0 bg-gray-100" alt="<?= Html::encode($p->nome) ?>">
                
                <div class="flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 leading-tight"><?= Html::encode($p->nome) ?></h3>
                        <?php if ($p->descricao): ?>
                            <p class="text-[11px] text-gray-500 line-clamp-2 mt-0.5"><?= Html::encode($p->descricao) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center justify-between mt-2">
                        <span class="text-sm font-black text-emerald-600">R$ <?= number_format($p->getPrecoFinal(), 2, ',', '.') ?></span>
                        <button type="button" onclick='abrirModalItem(<?= json_encode([
                            "id" => $p->id,
                            "nome" => $p->nome,
                            "preco" => (float)$p->getPrecoFinal(),
                            "preco_formatado" => number_format($p->getPrecoFinal(), 2, ',', '.'),
                            "opcionais" => array_map(function($op) {
                                return [
                                    "id" => $op->id,
                                    "nome" => $op->nome,
                                    "valor_adicional" => (float)$op->valor_adicional,
                                    "valor_formatado" => number_format($op->valor_adicional, 2, ',', '.')
                                ];
                            }, $p->opcionais)
                        ]) ?>)' class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow transition active:scale-95">
                            + Pedir
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Floating Footer Cart Bar -->
    <div id="barCarrinho" class="fixed bottom-0 inset-x-0 z-30 bg-gray-900 text-white p-4 border-t border-gray-800 hidden flex items-center justify-between shadow-2xl">
        <div>
            <span id="txtCountCarrinho" class="text-xs font-bold text-gray-400">0 itens no carrinho</span>
            <div id="txtTotalCarrinho" class="text-lg font-black text-emerald-400">Total: R$ 0,00</div>
        </div>

        <button type="button" onclick="enviarPedidoCarrinho()" id="btnEnviarPedidoMesa" class="px-5 py-3 bg-emerald-500 hover:bg-emerald-400 text-gray-950 font-black text-xs rounded-xl shadow-lg transition flex items-center gap-1.5 active:scale-95">
            <span>🚀</span>
            <span>Enviar Pedido</span>
        </button>
    </div>

    <!-- Modal Adicionar Item com Opcionais -->
    <div id="modalItemMesa" class="fixed inset-0 z-50 hidden bg-gray-950/70 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="w-full max-w-lg bg-white rounded-t-3xl sm:rounded-2xl p-6 shadow-2xl space-y-4 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 id="lblModalNomeProduto" class="text-base font-black text-gray-900">Nome do Produto</h3>
                <button type="button" onclick="fecharModalItemMesa()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold px-2">&times;</button>
            </div>

            <!-- Opcionais Container -->
            <div id="boxOpcionaisModal" class="space-y-2 hidden">
                <span class="text-xs font-bold text-gray-700 block">Deseja Adicionais?</span>
                <div id="listOpcionaisModal" class="space-y-1.5 text-xs"></div>
            </div>

            <!-- Observações -->
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Observações para a cozinha (opcional)</label>
                <input type="text" id="txtObsModal" placeholder="Ex: Sem cebola, gelo e limão..." class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs">
            </div>

            <!-- Quantidade & Preço -->
            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="alterarQtdModal(-1)" class="w-8 h-8 bg-gray-200 font-bold rounded-lg">-</button>
                    <span id="lblQtdModal" class="font-black text-sm w-6 text-center">1</span>
                    <button type="button" onclick="alterarQtdModal(1)" class="w-8 h-8 bg-gray-200 font-bold rounded-lg">+</button>
                </div>

                <button type="button" onclick="confirmarAdicionarCarrinho()" class="px-5 py-2.5 bg-emerald-600 text-white font-extrabold text-xs rounded-xl shadow">
                    Adicionar • R$ <span id="lblTotalItemModal">0,00</span>
                </button>
            </div>
        </div>
    </div>

    <script>
    const mesaId = '<?= $mesa->id ?>';
    let produtoAtual = null;
    let qtdAtual = 1;
    let carrinho = [];

    function filtrarCategoria(catClass) {
        document.querySelectorAll('.btn-cat').forEach(b => b.classList.remove('bg-emerald-600', 'text-white'));
        event.target.classList.add('bg-emerald-600', 'text-white');

        document.querySelectorAll('.card-produto').forEach(c => {
            if (catClass === 'todas' || c.classList.contains(catClass)) {
                c.classList.remove('hidden');
            } else {
                c.classList.add('hidden');
            }
        });
    }

    function abrirModalItem(p) {
        produtoAtual = p;
        qtdAtual = 1;
        document.getElementById('lblModalNomeProduto').innerText = p.nome;
        document.getElementById('txtObsModal').value = '';
        document.getElementById('lblQtdModal').innerText = 1;

        const boxOps = document.getElementById('boxOpcionaisModal');
        const listOps = document.getElementById('listOpcionaisModal');
        listOps.innerHTML = '';

        if (p.opcionais && p.opcionais.length > 0) {
            boxOps.classList.remove('hidden');
            p.opcionais.forEach(op => {
                listOps.innerHTML += `
                    <label class="flex items-center justify-between bg-gray-50 border border-gray-200 p-2 rounded-xl cursor-pointer">
                        <span class="font-bold text-gray-800">
                            <input type="checkbox" class="chk-modal-op text-emerald-600 rounded mr-2" data-id="${op.id}" data-nome="${op.nome}" data-valor="${op.valor_adicional}" onchange="recalcularModal()">
                            ${op.nome}
                        </span>
                        <span class="font-extrabold text-emerald-600">+R$ ${op.valor_formatado}</span>
                    </label>
                `;
            });
        } else {
            boxOps.classList.add('hidden');
        }

        recalcularModal();
        document.getElementById('modalItemMesa').classList.remove('hidden');
    }

    function fecharModalItemMesa() {
        document.getElementById('modalItemMesa').classList.add('hidden');
    }

    function alterarQtdModal(delta) {
        qtdAtual += delta;
        if (qtdAtual < 1) qtdAtual = 1;
        document.getElementById('lblQtdModal').innerText = qtdAtual;
        recalcularModal();
    }

    function recalcularModal() {
        if (!produtoAtual) return;
        let base = produtoAtual.preco;
        document.querySelectorAll('.chk-modal-op:checked').forEach(c => {
            base += parseFloat(c.getAttribute('data-valor')) || 0;
        });
        const total = base * qtdAtual;
        document.getElementById('lblTotalItemModal').innerText = total.toFixed(2).replace('.', ',');
    }

    function confirmarAdicionarCarrinho() {
        let valAdicional = 0;
        const opsNomes = [];
        document.querySelectorAll('.chk-modal-op:checked').forEach(c => {
            valAdicional += parseFloat(c.getAttribute('data-valor')) || 0;
            opsNomes.push(c.getAttribute('data-nome'));
        });

        let obs = document.getElementById('txtObsModal').value.trim();
        if (opsNomes.length > 0) {
            obs = 'Adicionais: ' + opsNomes.join(', ') + (obs ? ' | ' + obs : '');
        }

        carrinho.push({
            produto_id: produtoAtual.id,
            nome: produtoAtual.nome,
            quantidade: qtdAtual,
            valor_unitario: produtoAtual.preco + valAdicional,
            valor_adicional: valAdicional,
            observacoes: obs,
        });

        fecharModalItemMesa();
        atualizarBarCarrinho();
    }

    function atualizarBarCarrinho() {
        const bar = document.getElementById('barCarrinho');
        if (carrinho.length === 0) {
            bar.classList.add('hidden');
            return;
        }

        bar.classList.remove('hidden');
        let total = 0;
        let qtdTotal = 0;
        carrinho.forEach(item => {
            total += item.valor_unitario * item.quantidade;
            qtdTotal += item.quantidade;
        });

        document.getElementById('txtCountCarrinho').innerText = `${qtdTotal} item(ns) selecionado(s)`;
        document.getElementById('txtTotalCarrinho').innerText = `Total: R$ ${total.toFixed(2).replace('.', ',')}`;
    }

    async function enviarPedidoCarrinho() {
        if (carrinho.length === 0) return;

        const btn = document.getElementById('btnEnviarPedidoMesa');
        btn.disabled = true;
        btn.innerText = 'Enviando...';

        try {
            const formData = new FormData();
            formData.append('mesa_id', mesaId);
            formData.append('itens', JSON.stringify(carrinho));

            const csrfParam = document.querySelector('meta[name="csrf-param"]')?.content;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfParam && csrfToken) {
                formData.append(csrfParam, csrfToken);
            }

            const resp = await fetch('<?= Url::to(['/vendas/cardapio/fazer-pedido-mesa']) ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
                },
                body: formData
            });

            const data = await resp.json();
            alert(data.message || 'Pedido enviado!');
            if (data.success) {
                carrinho = [];
                atualizarBarCarrinho();
            }
        } catch(e) {
            console.error(e);
            alert('Erro ao enviar pedido para a mesa.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span>🚀</span><span>Enviar Pedido</span>';
        }
    }

    async function chamarGarcomAction() {
        try {
            const formData = new FormData();
            formData.append('mesa_id', mesaId);

            const csrfParam = document.querySelector('meta[name="csrf-param"]')?.content;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfParam && csrfToken) {
                formData.append(csrfParam, csrfToken);
            }

            const resp = await fetch('<?= Url::to(['/vendas/cardapio/chamar-garcom']) ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
                },
                body: formData
            });

            const data = await resp.json();
            alert(data.message || 'Garçom chamado! Atendente a caminho.');
        } catch(e) {
            console.error(e);
            alert('Erro ao chamar garçom.');
        }
    }

    async function pedirContaAction() {
        try {
            const formData = new FormData();
            formData.append('mesa_id', mesaId);

            const csrfParam = document.querySelector('meta[name="csrf-param"]')?.content;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfParam && csrfToken) {
                formData.append(csrfParam, csrfToken);
            }

            const resp = await fetch('<?= Url::to(['/vendas/cardapio/pedir-conta']) ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
                },
                body: formData
            });

            const data = await resp.json();
            alert(data.message || 'Conta solicitada!');
        } catch(e) {
            console.error(e);
            alert('Erro ao pedir conta.');
        }
    }
    </script>
</body>
</html>
