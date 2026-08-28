<?php

use yii\helpers\Html;
use yii\helpers\Url;

$nomeLoja = $loja ? ($loja->nome ?: 'PULSE Fast Food') : 'PULSE Fast Food';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Totem de Autoatendimento — <?= Html::encode($nomeLoja) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; user-select: none; }
        .touch-scroll { -webkit-overflow-scrolling: touch; }
    </style>
</head>
<body class="bg-gray-950 text-white h-screen overflow-hidden flex flex-col justify-between">

    <!-- Tela 1: Boas-Vindas (Início do Totem) -->
    <div id="stepBoasVindas" class="h-full flex flex-col justify-between p-8 text-center bg-gradient-to-b from-gray-900 via-emerald-950 to-gray-950 cursor-pointer" onclick="iniciarPedidoTotem()">
        <div class="pt-10">
            <h1 class="text-4xl font-black text-emerald-400 uppercase tracking-widest leading-tight"><?= Html::encode($nomeLoja) ?></h1>
            <p class="text-xl text-gray-300 mt-2 font-medium">Autoatendimento Rápido & Fácil</p>
        </div>

        <div class="my-auto space-y-6">
            <div class="text-8xl animate-bounce">🍔</div>
            <div class="inline-block px-10 py-6 bg-emerald-500 hover:bg-emerald-400 text-gray-950 font-black text-3xl rounded-3xl shadow-2xl transition transform active:scale-95 border-4 border-emerald-300">
                TOQUE PARA COMEÇAR
            </div>
            <p class="text-base text-gray-400">Peça seu lanche sem filas e retire no balcão!</p>
        </div>

        <div class="pb-6 text-xs text-gray-500">PULSE KIOSK • Pressione F11 para Tela Cheia</div>
    </div>

    <!-- Tela 2: Tipo de Consumo (Comer Aqui vs Levar) -->
    <div id="stepTipoConsumo" class="h-full hidden flex-col justify-between p-8 text-center bg-gray-900">
        <div class="pt-6">
            <h2 class="text-3xl font-black text-white">Como deseja consumir?</h2>
            <p class="text-gray-400 text-base mt-1">Escolha a opção para continuarmos o seu pedido</p>
        </div>

        <div class="grid grid-cols-2 gap-8 max-w-3xl mx-auto w-full my-auto">
            <button type="button" onclick="selecionarTipoConsumo('comer_aqui')" class="bg-gray-800 hover:bg-emerald-900/40 border-4 border-gray-700 hover:border-emerald-500 rounded-3xl p-10 flex flex-col items-center justify-center space-y-4 shadow-2xl transition active:scale-95">
                <span class="text-7xl">🍽️</span>
                <span class="text-2xl font-black text-white">COMER AQUI</span>
            </button>

            <button type="button" onclick="selecionarTipoConsumo('levar')" class="bg-gray-800 hover:bg-teal-900/40 border-4 border-gray-700 hover:border-teal-500 rounded-3xl p-10 flex flex-col items-center justify-center space-y-4 shadow-2xl transition active:scale-95">
                <span class="text-7xl">🛍️</span>
                <span class="text-2xl font-black text-white">PARA LEVAR</span>
            </button>
        </div>

        <button type="button" onclick="voltarParaBoasVindas()" class="text-gray-400 font-bold text-sm underline pb-4">← Voltar ao Início</button>
    </div>

    <!-- Tela 3: Cardápio Kiosk (Navegação & Escolha) -->
    <div id="stepCardapioTotem" class="h-full hidden flex-col justify-between bg-gray-900">
        <!-- Top Bar Kiosk -->
        <div class="bg-gray-950 p-4 border-b border-gray-800 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">🍔</span>
                <div>
                    <h2 class="text-lg font-black text-white"><?= Html::encode($nomeLoja) ?></h2>
                    <span id="lblTipoConsumoBadge" class="text-xs font-bold text-emerald-400 uppercase">Comer Aqui</span>
                </div>
            </div>
            <button type="button" onclick="cancelarPedidoTotem()" class="px-4 py-2 bg-rose-600/30 text-rose-300 border border-rose-500/40 font-bold text-xs rounded-xl">Cancelar</button>
        </div>

        <!-- Main Body: Categorias + Grid Produtos -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Sidebar Categorias -->
            <div class="w-1/4 bg-gray-950 border-r border-gray-800 p-3 space-y-2 overflow-y-auto">
                <button type="button" onclick="filtrarCategoriaTotem('todas')" class="btn-totem-cat active w-full p-4 bg-emerald-600 text-white font-black text-sm rounded-2xl shadow text-left flex items-center justify-between">
                    <span>Todas as Opções</span>
                </button>
                <?php foreach ($categorias as $cat): ?>
                    <button type="button" onclick="filtrarCategoriaTotem('cat-<?= $cat->id ?>')" class="btn-totem-cat w-full p-4 bg-gray-900 hover:bg-gray-800 text-gray-300 font-bold text-sm rounded-2xl border border-gray-800 text-left">
                        <?= Html::encode($cat->nome) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Grid de Produtos -->
            <div class="flex-1 p-6 overflow-y-auto grid grid-cols-2 lg:grid-cols-3 gap-6 touch-scroll">
                <?php foreach ($produtos as $p): ?>
                    <?php 
                        $catClass = $p->categoria_id ? 'cat-' . $p->categoria_id : 'cat-outras';
                        $fotoObj = $p->getFotoPrincipal();
                        $fotoUrl = $fotoObj ? $fotoObj->getUrl() : 'https://placehold.co/300x300?text=Lanche';
                    ?>
                    <div class="card-totem-prod <?= $catClass ?> bg-gray-800 border border-gray-700/80 rounded-3xl p-4 flex flex-col justify-between shadow-xl hover:border-emerald-500 transition">
                        <img src="<?= Html::encode($fotoUrl) ?>" class="w-full h-36 object-cover rounded-2xl bg-gray-900 mb-3" alt="<?= Html::encode($p->nome) ?>">
                        
                        <div>
                            <h3 class="text-base font-black text-white leading-tight mb-1"><?= Html::encode($p->nome) ?></h3>
                            <?php if ($p->descricao): ?>
                                <p class="text-xs text-gray-400 line-clamp-2 mb-2"><?= Html::encode($p->descricao) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-700/60">
                            <span class="text-lg font-black text-emerald-400">R$ <?= number_format($p->getPrecoFinal(), 2, ',', '.') ?></span>
                            <button type="button" onclick='abrirModalTotemItem(<?= json_encode([
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
                            ]) ?>)' class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-gray-950 font-black text-xs rounded-2xl shadow-lg transition active:scale-95">
                                + Adicionar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Footer Carrinho Kiosk Bar -->
        <div id="barCarrinhoTotem" class="bg-gray-950 p-5 border-t border-gray-800 flex items-center justify-between shadow-2xl">
            <div>
                <span id="lblQtdCarrinhoTotem" class="text-xs font-bold text-gray-400 uppercase tracking-wider">0 itens selecionados</span>
                <div id="lblTotalCarrinhoTotem" class="text-2xl font-black text-emerald-400">Total: R$ 0,00</div>
            </div>

            <button type="button" onclick="irParaFinalizacaoTotem()" id="btnAvancarTotem" class="px-8 py-4 bg-emerald-500 text-gray-950 font-black text-base rounded-2xl shadow-2xl transition opacity-50 pointer-events-none active:scale-95">
                FINALIZAR PEDIDO →
            </button>
        </div>
    </div>

    <!-- Tela 4: Tela de Confirmação & Senha -->
    <div id="stepSucessoSenha" class="h-full hidden flex-col justify-between p-8 text-center bg-gradient-to-b from-gray-900 via-emerald-950 to-gray-950">
        <div class="pt-10">
            <h2 class="text-3xl font-black text-emerald-400">PEDIDO REALIZADO COM SUCESSO! 🎉</h2>
            <p class="text-gray-300 text-lg mt-2">Acompanhe a sua senha no Painel da TV do Salão</p>
        </div>

        <div class="my-auto space-y-6">
            <div class="text-sm font-bold text-gray-400 uppercase tracking-widest">Sua Senha de Retirada é:</div>
            <div id="txtSenhaGerada" class="text-8xl font-black text-white bg-gray-900 border-4 border-emerald-500 inline-block px-12 py-6 rounded-3xl shadow-2xl animate-pulse">
                #042
            </div>
            <div class="text-xl font-bold text-emerald-300" id="txtTotalPagoTotem">Total Pago: R$ 0,00</div>
        </div>

        <div class="pb-10">
            <p class="text-sm text-gray-400 mb-4">Esta tela reiniciará automaticamente em alguns segundos...</p>
            <button type="button" onclick="reiniciarTotemGeral()" class="px-8 py-3 bg-gray-800 hover:bg-gray-700 text-white font-bold text-sm rounded-2xl">
                Novo Pedido
            </button>
        </div>
    </div>

    <!-- Modal de Opcionais do Totem -->
    <div id="modalTotemItem" class="fixed inset-0 z-50 hidden bg-gray-950/80 backdrop-blur-md flex items-center justify-center p-6">
        <div class="w-full max-w-lg bg-gray-900 border border-gray-800 rounded-3xl p-6 shadow-2xl space-y-6 text-white">
            <div class="flex items-center justify-between border-b border-gray-800 pb-3">
                <h3 id="lblTotemModalNome" class="text-xl font-black text-white">Nome do Produto</h3>
                <button type="button" onclick="fecharModalTotemItem()" class="text-gray-400 text-3xl font-bold px-2">&times;</button>
            </div>

            <div id="boxTotemOpcionais" class="space-y-3 hidden">
                <span class="text-sm font-bold text-gray-300 block">Escolha os Adicionais:</span>
                <div id="listTotemOpcionais" class="space-y-2 text-sm"></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-400 mb-1">Observações do Item (opcional)</label>
                <input type="text" id="txtTotemObs" placeholder="Ex: Sem cebola, pão bem passado..." class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-2xl text-sm text-white">
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-gray-800">
                <div class="flex items-center space-x-3">
                    <button type="button" onclick="alterarQtdTotem(-1)" class="w-10 h-10 bg-gray-800 border border-gray-700 text-xl font-bold rounded-xl">-</button>
                    <span id="lblQtdTotemModal" class="font-black text-lg w-8 text-center">1</span>
                    <button type="button" onclick="alterarQtdTotem(1)" class="w-10 h-10 bg-gray-800 border border-gray-700 text-xl font-bold rounded-xl">+</button>
                </div>

                <button type="button" onclick="confirmarAdicionarCarrinhoTotem()" class="px-6 py-3 bg-emerald-500 hover:bg-emerald-400 text-gray-950 font-black text-sm rounded-2xl shadow-xl">
                    Adicionar • R$ <span id="lblTotalItemTotem">0,00</span>
                </button>
            </div>
        </div>
    </div>

    <script>
    let tipoConsumoAtual = 'comer_aqui';
    let produtoTotemAtual = null;
    let qtdTotemAtual = 1;
    let carrinhoTotem = [];

    function iniciarPedidoTotem() {
        document.getElementById('stepBoasVindas').classList.add('hidden');
        document.getElementById('stepTipoConsumo').classList.remove('hidden');
        document.getElementById('stepTipoConsumo').classList.add('flex');
    }

    function voltarParaBoasVindas() {
        document.getElementById('stepTipoConsumo').classList.add('hidden');
        document.getElementById('stepTipoConsumo').classList.remove('flex');
        document.getElementById('stepBoasVindas').classList.remove('hidden');
    }

    function selecionarTipoConsumo(tipo) {
        tipoConsumoAtual = tipo;
        document.getElementById('lblTipoConsumoBadge').innerText = tipo === 'levar' ? 'Para Levar 🛍️' : 'Comer Aqui 🍽️';

        document.getElementById('stepTipoConsumo').classList.add('hidden');
        document.getElementById('stepTipoConsumo').classList.remove('flex');
        document.getElementById('stepCardapioTotem').classList.remove('hidden');
        document.getElementById('stepCardapioTotem').classList.add('flex');
    }

    function cancelarPedidoTotem() {
        carrinhoTotem = [];
        atualizarBarCarrinhoTotem();
        document.getElementById('stepCardapioTotem').classList.add('hidden');
        document.getElementById('stepCardapioTotem').classList.remove('flex');
        document.getElementById('stepBoasVindas').classList.remove('hidden');
    }

    function filtrarCategoriaTotem(catClass) {
        document.querySelectorAll('.btn-totem-cat').forEach(b => b.classList.remove('bg-emerald-600', 'text-white'));
        event.target.classList.add('bg-emerald-600', 'text-white');

        document.querySelectorAll('.card-totem-prod').forEach(c => {
            if (catClass === 'todas' || c.classList.contains(catClass)) {
                c.classList.remove('hidden');
            } else {
                c.classList.add('hidden');
            }
        });
    }

    function abrirModalTotemItem(p) {
        produtoTotemAtual = p;
        qtdTotemAtual = 1;
        document.getElementById('lblTotemModalNome').innerText = p.nome;
        document.getElementById('txtTotemObs').value = '';
        document.getElementById('lblQtdTotemModal').innerText = 1;

        const boxOps = document.getElementById('boxTotemOpcionais');
        const listOps = document.getElementById('listTotemOpcionais');
        listOps.innerHTML = '';

        if (p.opcionais && p.opcionais.length > 0) {
            boxOps.classList.remove('hidden');
            p.opcionais.forEach(op => {
                listOps.innerHTML += `
                    <label class="flex items-center justify-between bg-gray-800 border border-gray-700 p-3 rounded-2xl cursor-pointer">
                        <span class="font-bold text-white">
                            <input type="checkbox" class="chk-totem-op text-emerald-500 rounded mr-2" data-id="${op.id}" data-nome="${op.nome}" data-valor="${op.valor_adicional}" onchange="recalcularTotemModal()">
                            ${op.nome}
                        </span>
                        <span class="font-black text-emerald-400">+R$ ${op.valor_formatado}</span>
                    </label>
                `;
            });
        } else {
            boxOps.classList.add('hidden');
        }

        recalcularTotemModal();
        document.getElementById('modalTotemItem').classList.remove('hidden');
    }

    function fecharModalTotemItem() {
        document.getElementById('modalTotemItem').classList.add('hidden');
    }

    function alterarQtdTotem(delta) {
        qtdTotemAtual += delta;
        if (qtdTotemAtual < 1) qtdTotemAtual = 1;
        document.getElementById('lblQtdTotemModal').innerText = qtdTotemAtual;
        recalcularTotemModal();
    }

    function recalcularTotemModal() {
        if (!produtoTotemAtual) return;
        let base = produtoTotemAtual.preco;
        document.querySelectorAll('.chk-totem-op:checked').forEach(c => {
            base += parseFloat(c.getAttribute('data-valor')) || 0;
        });
        const total = base * qtdTotemAtual;
        document.getElementById('lblTotalItemTotem').innerText = total.toFixed(2).replace('.', ',');
    }

    function confirmarAdicionarCarrinhoTotem() {
        let valAdicional = 0;
        const opsNomes = [];
        document.querySelectorAll('.chk-totem-op:checked').forEach(c => {
            valAdicional += parseFloat(c.getAttribute('data-valor')) || 0;
            opsNomes.push(c.getAttribute('data-nome'));
        });

        let obs = document.getElementById('txtTotemObs').value.trim();
        if (opsNomes.length > 0) {
            obs = 'Adicionais: ' + opsNomes.join(', ') + (obs ? ' | ' + obs : '');
        }

        carrinhoTotem.push({
            produto_id: produtoTotemAtual.id,
            nome: produtoTotemAtual.nome,
            quantidade: qtdTotemAtual,
            valor_unitario: produtoTotemAtual.preco + valAdicional,
            valor_adicional: valAdicional,
            observacoes: obs,
        });

        fecharModalTotemItem();
        atualizarBarCarrinhoTotem();
    }

    function atualizarBarCarrinhoTotem() {
        const btn = document.getElementById('btnAvancarTotem');
        if (carrinhoTotem.length === 0) {
            btn.classList.add('opacity-50', 'pointer-events-none');
            document.getElementById('lblQtdCarrinhoTotem').innerText = '0 itens selecionados';
            document.getElementById('lblTotalCarrinhoTotem').innerText = 'Total: R$ 0,00';
            return;
        }

        btn.classList.remove('opacity-50', 'pointer-events-none');
        let total = 0;
        let qtdTotal = 0;
        carrinhoTotem.forEach(item => {
            total += item.valor_unitario * item.quantidade;
            qtdTotal += item.quantidade;
        });

        document.getElementById('lblQtdCarrinhoTotem').innerText = `${qtdTotal} item(ns) selecionado(s)`;
        document.getElementById('lblTotalCarrinhoTotem').innerText = `Total: R$ ${total.toFixed(2).replace('.', ',')}`;
    }

    async function irParaFinalizacaoTotem() {
        if (carrinhoTotem.length === 0) return;

        const nome = prompt("Digite seu Nome para a Senha (opcional):", "Cliente Totem") || "Cliente Totem";

        try {
            const formData = new FormData();
            formData.append('cliente_nome', nome);
            formData.append('tipo_consumo', tipoConsumoAtual);
            formData.append('itens', JSON.stringify(carrinhoTotem));

            const resp = await fetch('<?= Url::to(['/vendas/totem/finalizar-pedido']) ?>', {
                method: 'POST',
                body: formData
            });

            const data = await resp.json();
            if (data.success) {
                document.getElementById('txtSenhaGerada').innerText = data.senha;
                document.getElementById('txtTotalPagoTotem').innerText = 'Total Pago: R$ ' + data.valor_total_formatado;

                document.getElementById('stepCardapioTotem').classList.add('hidden');
                document.getElementById('stepCardapioTotem').classList.remove('flex');
                document.getElementById('stepSucessoSenha').classList.remove('hidden');
                document.getElementById('stepSucessoSenha').classList.add('flex');

                setTimeout(reiniciarTotemGeral, 10000);
            } else {
                alert(data.message || 'Erro ao finalizar pedido.');
            }
        } catch(e) {
            alert('Erro ao enviar pedido no totem.');
        }
    }

    function reiniciarTotemGeral() {
        carrinhoTotem = [];
        atualizarBarCarrinhoTotem();

        document.getElementById('stepSucessoSenha').classList.add('hidden');
        document.getElementById('stepSucessoSenha').classList.remove('flex');
        document.getElementById('stepBoasVindas').classList.remove('hidden');
    }
    </script>
</body>
</html>
