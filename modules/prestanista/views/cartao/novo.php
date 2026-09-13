<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Cliente[] $clientes */
/** @var app\modules\vendas\models\Produto[] $produtos */
/** @var app\modules\vendas\models\Colaborador[] $vendedores */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Emitir Novo Cartão de Crediário';
?>

<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>➕</span>
                <span>Emitir Novo Cartão de Crediário</span>
            </h1>
            <p class="text-xs text-slate-400">
                Preencha os dados do cliente, as mercadorias vendidas e as condições de pagamento a prazo.
            </p>
        </div>
        <a href="<?= Url::to(['/prestanista/cartao/index']) ?>" class="text-xs font-bold text-slate-400 hover:text-white">
            ✕ Cancelar
        </a>
    </div>

    <form method="post" action="<?= Url::to(['/prestanista/cartao/novo']) ?>" id="formNovoCartao" class="bg-slate-950 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-6">
        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>" />

        <!-- 1. Cliente & Vendedor -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-black text-slate-300 uppercase tracking-wider mb-1.5">Cliente (Comprador) *</label>
                <select name="cliente_id" required class="w-full h-12 px-3.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none">
                    <option value="">Selecione o Cliente...</option>
                    <?php foreach ($clientes as $cl): ?>
                        <option value="<?= $cl->id ?>"><?= Html::encode($cl->nome) ?> (<?= Html::encode($cl->bairro ?? 'Sem bairro') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-black text-slate-300 uppercase tracking-wider mb-1.5">Vendedor Ambulante</label>
                <select name="vendedor_id" class="w-full h-12 px-3.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:border-amber-500 focus:outline-none">
                    <option value="">Selecione o Vendedor...</option>
                    <?php foreach ($vendedores as $v): ?>
                        <option value="<?= $v->id ?>"><?= Html::encode($v->nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- 2. Adicionar Objetos / Mercadorias -->
        <div class="border-t border-slate-800 pt-4 space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-xs font-black text-slate-300 uppercase tracking-wider">Objetos Vendidos (Mercadorias)</label>
                <button type="button" onclick="adicionarLinhaProduto()" class="text-xs font-black text-amber-400 hover:text-amber-300">
                    + Adicionar Mercadoria
                </button>
            </div>

            <div id="containerItensVenda" class="space-y-2.5">
                <!-- Linha 1 padrão -->
                <div class="grid grid-cols-12 gap-2 item-venda-linha">
                    <div class="col-span-7">
                        <select name="itens[0][produto_id]" required onchange="atualizarPrecoProduto(this, 0)" class="w-full h-11 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                            <option value="">Selecione a mercadoria...</option>
                            <?php foreach ($produtos as $p): ?>
                                <option value="<?= $p->id ?>" data-preco="<?= (float)$p->preco ?>"><?= Html::encode($p->nome) ?> (R$ <?= number_format($p->preco, 2, ',', '.') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <input type="number" name="itens[0][quantidade]" value="1" min="1" required oninput="calcularTotalCartao()" class="w-full h-11 text-center bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold" placeholder="Qtd">
                    </div>
                    <div class="col-span-3">
                        <input type="number" step="0.01" name="itens[0][preco]" id="preco_item_0" required oninput="calcularTotalCartao()" class="w-full h-11 px-3 text-right bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold" placeholder="R$ Valor">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Condições de Pagamento e Frequência -->
        <div class="border-t border-slate-800 pt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-black text-slate-300 uppercase tracking-wider mb-1.5">Frequência da Cobrança</label>
                <select name="frequencia" class="w-full h-11 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                    <option value="7" selected>SEMANAL (A cada 7 dias)</option>
                    <option value="15">QUINZENAL (A cada 15 dias)</option>
                    <option value="30">MENSAL (A cada 30 dias)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-black text-slate-300 uppercase tracking-wider mb-1.5">Nº de Prestações</label>
                <input type="number" name="numero_parcelas" value="10" min="1" max="100" class="w-full h-11 px-3 text-center bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold">
            </div>

            <div>
                <label class="block text-xs font-black text-slate-300 uppercase tracking-wider mb-1.5">Entrada no Ato (R$)</label>
                <input type="text" name="valor_entrada" value="0,00" class="w-full h-11 px-3 text-right bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold" placeholder="0,00">
            </div>
        </div>

        <!-- 4. Resumo do Cartão & Botão de Emissão -->
        <div class="border-t border-slate-800 pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <span class="block text-xs text-slate-400">Total das Mercadorias:</span>
                <span id="labelTotalCalculado" class="text-2xl font-black text-amber-400">R$ 0,00</span>
            </div>

            <button type="submit" class="w-full sm:w-auto px-6 py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-sm rounded-2xl shadow-lg transition active:scale-95 flex items-center justify-center gap-2">
                <span>📇</span>
                <span>Salvar e Emitir Cartão</span>
            </button>
        </div>

    </form>

</div>

<script>
    let contadorLinhas = 1;
    function atualizarPrecoProduto(select, idx) {
        const option = select.options[select.selectedIndex];
        const preco = option.getAttribute('data-preco');
        const inputPreco = document.getElementById('preco_item_' + idx);
        if (inputPreco && preco) {
            inputPreco.value = parseFloat(preco).toFixed(2);
        }
        calcularTotalCartao();
    }

    function calcularTotalCartao() {
        let total = 0;
        document.querySelectorAll('.item-venda-linha').forEach(linha => {
            const qtd = parseFloat(linha.querySelector('input[name*="[quantidade]"]')?.value) || 0;
            const preco = parseFloat(linha.querySelector('input[name*="[preco]"]')?.value) || 0;
            total += (qtd * preco);
        });
        document.getElementById('labelTotalCalculado').textContent = 'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function adicionarLinhaProduto() {
        const container = document.getElementById('containerItensVenda');
        const novaLinha = document.createElement('div');
        novaLinha.className = 'grid grid-cols-12 gap-2 item-venda-linha';
        novaLinha.innerHTML = `
            <div class="col-span-7">
                <select name="itens[${contadorLinhas}][produto_id]" required onchange="atualizarPrecoProduto(this, ${contadorLinhas})" class="w-full h-11 px-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
                    <option value="">Selecione a mercadoria...</option>
                    <?php foreach ($produtos as $p): ?>
                        <option value="<?= $p->id ?>" data-preco="<?= (float)$p->preco ?>"><?= Html::encode($p->nome) ?> (R$ <?= number_format($p->preco, 2, ',', '.') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-span-2">
                <input type="number" name="itens[${contadorLinhas}][quantidade]" value="1" min="1" required oninput="calcularTotalCartao()" class="w-full h-11 text-center bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold" placeholder="Qtd">
            </div>
            <div class="col-span-3 flex items-center gap-1">
                <input type="number" step="0.01" name="itens[${contadorLinhas}][preco]" id="preco_item_${contadorLinhas}" required oninput="calcularTotalCartao()" class="w-full h-11 px-3 text-right bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-bold" placeholder="R$ Valor">
                <button type="button" onclick="this.closest('.item-venda-linha').remove(); calcularTotalCartao();" class="text-rose-400 hover:text-rose-300 p-1 text-xs">✕</button>
            </div>
        `;
        container.appendChild(novaLinha);
        contadorLinhas++;
    }
</script>
