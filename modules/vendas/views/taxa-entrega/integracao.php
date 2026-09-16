<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $lojaConfig app\modules\vendas\models\LojaConfiguracao */

$this->title = 'Integração de Fretes — Melhor Envio';
$servicosAtivos = !empty($lojaConfig->melhor_envio_servicos) ? explode(',', $lojaConfig->melhor_envio_servicos) : ['1', '2', '3', '4'];
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="<?= Url::to(['index']) ?>" class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight"><?= Html::encode($this->title) ?></h1>
            </div>
            <p class="mt-1 text-sm text-gray-500">Cotação automática e descontos de até 80% em Correios e Jadlog sem mensalidade.</p>
        </div>
        
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold <?= $lojaConfig->melhor_envio_ativo && !empty($lojaConfig->melhor_envio_token) ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
                <?= $lojaConfig->melhor_envio_ativo && !empty($lojaConfig->melhor_envio_token) ? '● Integração Ativa' : '○ Não Integrado / Inativo' ?>
            </span>
        </div>
    </div>

    <!-- Card Como Funciona -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-3xl p-6 sm:p-8 text-white shadow-xl">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <span class="inline-block px-3 py-1 bg-white/20 text-white rounded-full text-xs font-bold uppercase tracking-wider">100% Gratuito</span>
                <h2 class="text-xl sm:text-2xl font-black">Cálculo de Frete em Tempo Real</h2>
                <p class="text-blue-100 text-sm leading-relaxed">
                    O Melhor Envio não cobra assinatura nem consulta de API. Ao ativar esta integração, seus clientes poderão cotar <strong>PAC, SEDEX e Jadlog</strong> diretamente no catálogo da sua loja com os maiores descontos do Brasil.
                </p>
            </div>
            <a href="https://melhorenvio.com.br/painel/gerenciar/tokens" target="_blank" rel="noopener noreferrer" 
               class="inline-flex items-center px-5 py-3 bg-white text-blue-700 font-bold rounded-2xl hover:bg-blue-50 shadow-lg transition-transform transform hover:-translate-y-0.5 whitespace-nowrap text-sm">
                <span>Criar Token no Melhor Envio</span>
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Formulário de Configuração -->
    <form method="post" action="<?= Url::to(['integracao']) ?>" class="space-y-6">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sm:p-8 space-y-6">

            <!-- Toggle de Ativação -->
            <div class="flex items-center justify-between pb-6 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Ativar Cálculo Automático via Melhor Envio</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Quando ativado, os clientes verão as opções das transportadoras no carrinho.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="LojaConfiguracao[melhor_envio_ativo]" value="1" 
                           <?= $lojaConfig->melhor_envio_ativo ? 'checked' : '' ?> 
                           class="sr-only peer">
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <!-- Token de Acesso -->
            <div class="space-y-2">
                <label class="block text-sm font-bold text-gray-700">Token de Acesso (Bearer Token) *</label>
                <div class="relative">
                    <input type="password" id="input-token-me" name="LojaConfiguracao[melhor_envio_token]" 
                           value="<?= Html::encode($lojaConfig->melhor_envio_token) ?>" 
                           placeholder="Cole aqui o Token de Acesso gerado no painel do Melhor Envio"
                           class="w-full pl-4 pr-24 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm font-mono transition-all">
                    <button type="button" onclick="toggleTokenVisibilidade()" 
                            class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-gray-500 hover:text-gray-800">
                        Mostrar
                    </button>
                </div>
                <p class="text-xs text-gray-400">Gere seu token no painel do Melhor Envio em: <em>Configurações → Informações da Conta → Chaves de Acesso</em>.</p>
            </div>

            <!-- CEP de Origem e Ambiente -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">CEP de Origem do Despacho *</label>
                    <input type="text" name="LojaConfiguracao[melhor_envio_cep_origem]" 
                           value="<?= Html::encode($lojaConfig->melhor_envio_cep_origem ?: $lojaConfig->cep) ?>" 
                           placeholder="00000-000" maxlength="9"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm font-bold transition-all">
                    <p class="text-xs text-gray-400 mt-1">CEP de onde saem seus pacotes para cálculo da distância.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Ambiente da API</label>
                    <select name="LojaConfiguracao[melhor_envio_ambiente]" 
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm transition-all font-semibold">
                        <option value="production" <?= ($lojaConfig->melhor_envio_ambiente ?: 'production') === 'production' ? 'selected' : '' ?>>Produção (Oficial - Recomendado)</option>
                        <option value="sandbox" <?= $lojaConfig->melhor_envio_ambiente === 'sandbox' ? 'selected' : '' ?>>Sandbox (Ambiente de Testes)</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Use 'Produção' com o token da sua conta oficial do Melhor Envio.</p>
                </div>
            </div>

            <!-- Serviços Habilitados -->
            <div class="pt-4 border-t border-gray-100 space-y-3">
                <label class="block text-sm font-bold text-gray-700">Transportadoras e Serviços Disponíveis no Catálogo</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <?php 
                    $servicosLista = [
                        '1' => ['nome' => 'Correios PAC', 'icone' => '📦', 'desc' => 'Econômico nacional'],
                        '2' => ['nome' => 'Correios SEDEX', 'icone' => '⚡', 'desc' => 'Entrega expressa'],
                        '3' => ['nome' => 'Jadlog .Package', 'icone' => '🚚', 'desc' => 'Econômico privado'],
                        '4' => ['nome' => 'Jadlog .Com', 'icone' => '🚀', 'desc' => 'Expresso privado'],
                        '17' => ['nome' => 'Correios Mini Envios', 'icone' => '✉️', 'desc' => 'Pequenos volumes'],
                        '27' => ['nome' => 'Loggi Express', 'icone' => '🛵', 'desc' => 'Urbano e regional'],
                    ];
                    foreach ($servicosLista as $codigo => $info):
                        $checked = in_array((string)$codigo, $servicosAtivos);
                    ?>
                        <label class="flex items-start p-3.5 border border-gray-200 rounded-2xl cursor-pointer hover:bg-gray-50 transition-colors <?= $checked ? 'border-blue-300 bg-blue-50/30' : '' ?>">
                            <input type="checkbox" name="LojaConfiguracao[melhor_envio_servicos][]" value="<?= $codigo ?>" 
                                   <?= $checked ? 'checked' : '' ?> 
                                   class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <div class="ml-3">
                                <span class="text-sm font-bold text-gray-900"><?= $info['icone'] ?> <?= Html::encode($info['nome']) ?></span>
                                <p class="text-[11px] text-gray-500"><?= Html::encode($info['desc']) ?></p>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ajustes Opcionais de Expedição -->
            <div class="pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Prazo Adicional de Manuseio (Dias Úteis)</label>
                    <input type="number" min="0" max="30" name="LojaConfiguracao[melhor_envio_acrescimo_dias]" 
                           value="<?= Html::encode($lojaConfig->melhor_envio_acrescimo_dias ?: 0) ?>" 
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm transition-all">
                    <p class="text-xs text-gray-400 mt-1">Dias adicionados ao prazo da transportadora para você preparar e postar o pedido.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Taxa Extra de Embalagem (R$)</label>
                    <input type="number" step="0.01" min="0" name="LojaConfiguracao[melhor_envio_acrescimo_valor]" 
                           value="<?= number_format($lojaConfig->melhor_envio_acrescimo_valor ?: 0, 2, '.', '') ?>" 
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm transition-all">
                    <p class="text-xs text-gray-400 mt-1">Valor fixo opcional adicionado ao frete para custear caixa, fita ou plástico bolha.</p>
                </div>
            </div>

            <!-- Botão de Testar Conexão -->
            <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <button type="button" onclick="testarConexaoMelhorEnvio()" 
                        class="inline-flex items-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold rounded-xl text-sm transition active:scale-95">
                    <svg class="w-4 h-4 mr-2 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                    </svg>
                    Testar Conexão com a API
                </button>
                <div id="resultado-teste-me" class="text-xs font-semibold"></div>
            </div>

        </div>

        <!-- Botões de Ação -->
        <div class="flex flex-col sm:flex-row items-center gap-4">
            <button type="submit" 
                    class="w-full sm:flex-1 py-4 bg-gray-900 text-white font-bold rounded-2xl hover:bg-gray-800 shadow-xl transition-all transform hover:-translate-y-0.5 active:scale-95 text-base">
                Salvar Configurações de Frete
            </button>
            <a href="<?= Url::to(['index']) ?>" 
               class="w-full sm:w-auto px-8 py-4 bg-gray-100 text-gray-700 font-bold rounded-2xl hover:bg-gray-200 transition text-center text-sm">
                Cancelar
            </a>
        </div>
    </form>

</div>

<script>
function toggleTokenVisibilidade() {
    const input = document.getElementById('input-token-me');
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}

function testarConexaoMelhorEnvio() {
    const token = document.getElementById('input-token-me').value.trim();
    const ambiente = document.querySelector('select[name="LojaConfiguracao[melhor_envio_ambiente]"]').value;
    const container = document.getElementById('resultado-teste-me');

    if (!token) {
        container.innerHTML = '<span class="text-red-600">⚠️ Por favor, informe o Token de Acesso antes de testar.</span>';
        return;
    }

    container.innerHTML = '<span class="text-blue-600 flex items-center gap-1.5"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Testando comunicação com a API...</span>';

    fetch('<?= Url::to(['testar-melhor-envio']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
        },
        body: new URLSearchParams({
            token: token,
            ambiente: ambiente
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            container.innerHTML = `<span class="text-green-600">✅ Conectado com sucesso! Loja: <strong>${data.data?.nome || 'OK'}</strong> | Saldo: R$ ${(data.data?.saldo || 0).toFixed(2)}</span>`;
        } else {
            container.innerHTML = `<span class="text-red-600">❌ Falha: ${data.message}</span>`;
        }
    })
    .catch(err => {
        container.innerHTML = '<span class="text-red-600">❌ Erro de rede ao conectar com o servidor.</span>';
    });
}
</script>
