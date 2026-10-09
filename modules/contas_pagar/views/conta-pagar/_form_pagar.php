<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\vendas\models\FormaPagamento;

/** @var yii\web\View $this */
/** @var app\modules\contas_pagar\models\ContaPagar $model */

// Buscar formas de pagamento
$formasPagamento = FormaPagamento::find()->where(['ativo' => true])->orderBy(['nome' => SORT_ASC])->all();
$formasPagamentoList = \yii\helpers\ArrayHelper::map($formasPagamento, 'id', 'nome');

// Verificar saldo do caixa
$caixa = \app\modules\caixa\helpers\CaixaHelper::getCaixaAberto();
$saldoAtual = $caixa ? $caixa->calcularValorEsperado() : 0;
$saldoSuficiente = $saldoAtual >= $model->valor;

// Garante que "Débito em Conta" esteja disponível na lista
$temDebitoConta = false;
foreach ($formasPagamento as $fp) {
    if (stripos($fp->nome, 'débito em conta') !== false || stripos($fp->nome, 'debito em conta') !== false) {
        $temDebitoConta = true;
        break;
    }
}

if (!$temDebitoConta) {
    $novaForma = new FormaPagamento();
    $novaForma->nome = 'Débito em Conta';
    $novaForma->tipo = FormaPagamento::TIPO_TRANSFERENCIA;
    $novaForma->ativo = true;
    $novaForma->usuario_id = $caixa ? $caixa->usuario_id : Yii::$app->user->id;
    if ($novaForma->save(false)) {
        $formasPagamentoList[$novaForma->id] = $novaForma->nome;
    }
}

// Sugestão de forma de pagamento padrão caso o saldo seja insuficiente
$formaSelecionadaId = $model->forma_pagamento_id;
if (!$formaSelecionadaId && !$saldoSuficiente) {
    foreach ($formasPagamentoList as $idFp => $nomeFp) {
        if (stripos($nomeFp, 'cartão de débito') !== false || stripos($nomeFp, 'debito em conta') !== false || stripos($nomeFp, 'cartão') !== false) {
            $formaSelecionadaId = $idFp;
            break;
        }
    }
}

$isAjax = Yii::$app->request->isAjax;
?>

<div class="pagar-conta-form">

    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-green-100 rounded-full mb-3">
            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </div>
        <h2 class="text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight">Confirmar Pagamento</h2>
        <p class="text-gray-500 mt-1 font-medium text-sm"><?= Html::encode($model->descricao) ?></p>
        <div class="mt-3 inline-block px-4 py-2 bg-gray-900 rounded-xl">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Valor a Pagar</span>
            <span class="text-xl sm:text-2xl font-bold text-white"><?= Yii::$app->formatter->asCurrency($model->valor) ?></span>
        </div>
    </div>

    <!-- Alertas de Contexto -->
    <?php if (!$caixa): ?>
        <div class="flex items-start p-4 mb-5 bg-amber-50 rounded-2xl border border-amber-200">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-bold text-amber-900">Caixa Fechado</h3>
                <p class="text-xs text-amber-700 mt-1 leading-relaxed">
                    A conta será marcada como paga no sistema, mas sem débito automático no fluxo de caixa (caixa fechado).
                </p>
            </div>
        </div>
    <?php elseif (!$saldoSuficiente): ?>
        <div class="flex items-start p-4 mb-5 bg-blue-50 rounded-2xl border border-blue-200">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-bold text-blue-900">Pagamento com Recurso Externo (Cartão / Débito em Conta)</h3>
                <p class="text-xs text-blue-700 mt-1 leading-relaxed">
                    Saldo disponível em espécie: <span class="font-bold"><?= Yii::$app->formatter->asCurrency($saldoAtual) ?></span>.<br>
                    Ao pagar com <b>Cartão</b>, <b>Débito em Conta</b> ou <b>PIX</b>, o sistema efetua o <b>duplo registro contábil</b> (Entrada de Aporte + Saída da Conta), quitando a dívida <b>sem deixar o saldo do caixa negativo</b>.
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="flex items-center p-3.5 mb-5 bg-emerald-50 rounded-2xl border border-emerald-200">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-bold text-emerald-900">Saldo Disponível no Caixa</p>
                <p class="text-xs text-emerald-700">Saldo atual: <b><?= Yii::$app->formatter->asCurrency($saldoAtual) ?></b>.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php $form = ActiveForm::begin([
        'id' => 'form-pagar-conta',
        'action' => ['pagar', 'id' => $model->id],
        'options' => ['class' => 'space-y-5'],
    ]); ?>

    <div class="space-y-4">
        <!-- Data de Pagamento -->
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Data de Pagamento</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <input type="date"
                    name="data_pagamento"
                    id="campo_data_pagamento"
                    value="<?= date('Y-m-d') ?>"
                    class="block w-full pl-12 pr-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl text-gray-900 font-semibold focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-200 outline-none sm:text-sm"
                    required>
            </div>
            <div id="alerta_data_retroativa" class="hidden mt-2 p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 leading-relaxed">
                ⚠️ <b>Data retroativa detectada:</b> No contas a pagar, a data contábil de quitação será gravada como selecionada. No caixa diário, o lançamento ficará vinculado ao expediente de hoje com tag de referência <code>[Ref. Data]</code>, mantendo a integridade cronológica do caixa. Caso esse pagamento tenha ocorrido por conta bancária fora do expediente deste caixa, você pode desmarcar a opção <b>"Lançar no Caixa Diário Aberto"</b> abaixo.
            </div>
        </div>

        <!-- Forma de Pagamento -->
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Forma de Pagamento</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <select name="forma_pagamento_id" id="forma_pagamento_select"
                    class="block w-full pl-12 pr-10 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl text-gray-900 font-semibold focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-200 outline-none appearance-none sm:text-sm"
                    required>
                    <option value="">Selecione a forma de pagamento...</option>
                    <?php foreach ($formasPagamentoList as $id => $nome): ?>
                        <option value="<?= $id ?>" <?= ($formaSelecionadaId == $id) ? 'selected' : '' ?>>
                            <?= Html::encode($nome) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
            <p class="mt-1 text-xs text-gray-400 italic ml-1">Selecione Cartão, Débito em Conta ou PIX para pagamento com recurso externo.</p>
        </div>

        <!-- Duplo Registro para Evitar Saldo Negativo -->
        <?php if ($caixa): ?>
            <div class="pt-1">
                <input type="hidden" name="duplo_registro" value="0">
                <label class="relative flex items-center cursor-pointer select-none group p-3 bg-gray-50 hover:bg-blue-50 rounded-2xl border border-gray-200 transition-all">
                    <input type="checkbox"
                        name="duplo_registro"
                        value="1"
                        id="duplo_registro"
                        <?= !$saldoSuficiente ? 'checked' : '' ?>
                        class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[14px] after:left-[14px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 transition-colors"></div>
                    <div class="ml-14">
                        <span class="text-xs sm:text-sm font-bold text-gray-800 block group-hover:text-gray-900 transition-colors">Duplo registro no caixa (Aporte + Pagamento)</span>
                        <span class="text-[11px] text-gray-500 block leading-tight">Gera entrada e saída simultâneas no caixa, evitando saldo negativo.</span>
                    </div>
                </label>
            </div>

            <!-- Movimentar Caixa Diário -->
            <div class="pt-1">
                <input type="hidden" name="movimentar_caixa" value="0">
                <label class="relative flex items-center cursor-pointer select-none group p-3 bg-gray-50 hover:bg-emerald-50 rounded-2xl border border-gray-200 transition-all">
                    <input type="checkbox"
                        name="movimentar_caixa"
                        value="1"
                        id="movimentar_caixa"
                        checked
                        class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[14px] after:left-[14px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600 transition-colors"></div>
                    <div class="ml-14">
                        <span class="text-xs sm:text-sm font-bold text-gray-800 block group-hover:text-gray-900 transition-colors">Lançar no Caixa Diário Aberto</span>
                        <span class="text-[11px] text-gray-500 block leading-tight">Mantenha marcado para registrar no caixa atual. Desmarque caso seja quitação de títulos antigos pagos fora do expediente deste caixa.</span>
                    </div>
                </label>
            </div>
            
            <input type="hidden" name="validar_saldo" value="0">
        <?php endif; ?>
    </div>

    <!-- Botões de Ação -->
    <div class="flex flex-col sm:flex-row gap-3 pt-6 mt-4 border-t border-gray-100">
        <button type="submit"
            class="flex-1 order-1 sm:order-2 px-6 py-3.5 bg-green-600 hover:bg-green-700 text-white font-extrabold rounded-2xl shadow-lg shadow-green-200 hover:shadow-green-300 transform active:scale-95 transition-all duration-200 flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Confirmar Pagamento
        </button>

        <?php if ($isAjax): ?>
            <button type="button"
                onclick="if (typeof fecharModalPagamento === 'function') { fecharModalPagamento(); } else { var m = document.getElementById('modal-pagar'); if (m) m.classList.add('hidden'); }"
                class="flex-1 order-2 sm:order-1 px-6 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-2xl transition-all duration-200">
                Cancelar
            </button>
        <?php else: ?>
            <?= Html::a('Descartar', ['index'], [
                'class' => 'flex-1 order-2 sm:order-1 px-6 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-2xl text-center transition-all duration-200'
            ]) ?>
        <?php endif; ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<style>
    /* Custom transitions for the toggle */
    .peer-checked\:bg-blue-600:checked~.peer {
        background-color: #2563eb;
    }
</style>

<?php
$urlIndex = \yii\helpers\Url::to(['index']);
$this->registerJs(
    <<<JS
// Monitora data retroativa
function verificarDataRetroativa() {
    var input = document.getElementById('campo_data_pagamento');
    var alerta = document.getElementById('alerta_data_retroativa');
    if (!input || !alerta) return;
    
    var hoje = new Date().toISOString().split('T')[0];
    if (input.value && input.value < hoje) {
        alerta.classList.remove('hidden');
    } else {
        alerta.classList.add('hidden');
    }
}
$(document).off('change input', '#campo_data_pagamento').on('change input', '#campo_data_pagamento', verificarDataRetroativa);
verificarDataRetroativa();

// Submissão do formulário via AJAX
$(document).off('submit', '#form-pagar-conta').on('submit', '#form-pagar-conta', function(e) {
    e.preventDefault();
    
    var form = $(this);
    var submitBtn = form.find('button[type="submit"]');
    var originalBtnContent = submitBtn.html();
    
    // Disable button to prevent multiple submissions
    submitBtn.prop('disabled', true).html('<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processando...');
    
    var formData = form.serialize();
    
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response && response.success === false) {
                alert('❌ ' + (response.message || 'Erro ao processar pagamento.'));
                submitBtn.prop('disabled', false).html(originalBtnContent);
                return;
            }
            // Recarrega a página ou redireciona para a lista
            if (window.location.pathname.indexOf('index') !== -1) {
                location.reload();
            } else {
                window.location.href = '{$urlIndex}';
            }
        },
        error: function(xhr) {
            if (xhr.status === 200 || xhr.status === 302) {
                location.reload();
                return;
            }
            alert('Erro ao processar pagamento. Verifique os dados e tente novamente.');
            submitBtn.prop('disabled', false).html(originalBtnContent);
        }
    });
});
JS
);
?>