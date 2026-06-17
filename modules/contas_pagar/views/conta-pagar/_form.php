<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use yii\web\JsExpression;
use app\modules\vendas\models\Fornecedor;
use app\modules\vendas\models\FormaPagamento;
use app\modules\contas_pagar\models\TipoDespesa;

/* @var $this yii\web\View */
/* @var $model app\modules\contas_pagar\models\ContaPagar */

// Dropdowns
$fornecedores    = ArrayHelper::map(
    Fornecedor::find()->orderBy('nome_fantasia')->all(),
    'id', 'nome_fantasia'
);
$formasPagamento = ArrayHelper::map(
    FormaPagamento::find()->where(['ativo' => true])->orderBy('nome')->all(),
    'id', 'nome'
);

// Tipos de despesa — lista plana com grupo embutido
$usuarioId  = Yii::$app->user->id;
$todosTipos = TipoDespesa::find()
    ->where(['ativo' => true, 'usuario_id' => $usuarioId])
    ->orderBy(['grupo' => SORT_ASC, 'nome' => SORT_ASC])
    ->all();

// Para o dropdown: [id => 'Ícone Grupo — Nome']
$tiposDropdown = [];
foreach ($todosTipos as $t) {
    $tiposDropdown[$t->id] = TipoDespesa::getGrupoIcon($t->grupo) . ' '
        . TipoDespesa::getGrupoLabel($t->grupo) . ' — ' . $t->nome;
}

// Para o JS: { "uuid": { grupo: "FIXA", label: "Despesas Fixas", icon: "🔴" }, ... }
$tiposInfoJs = [];
foreach ($todosTipos as $t) {
    $tiposInfoJs[$t->id] = [
        'grupo' => $t->grupo,
        'label' => TipoDespesa::getGrupoLabel($t->grupo),
        'icon'  => TipoDespesa::getGrupoIcon($t->grupo),
    ];
}
$tiposInfoJson = Json::htmlEncode($tiposInfoJs);

// Badge CSS por grupo
$badgeCssMap = [
    TipoDespesa::GRUPO_FIXA       => 'bg-red-100 text-red-800 border border-red-200',
    TipoDespesa::GRUPO_VARIAVEL   => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
    TipoDespesa::GRUPO_MERCADORIA => 'bg-blue-100 text-blue-800 border border-blue-200',
];
$badgeCssJson = Json::htmlEncode($badgeCssMap);

// Valor atual formatado para exibição (edição)
$valorDisplay = '';
$valorHidden  = '';
if ($model->valor) {
    $valorHidden  = number_format((float)$model->valor, 2, '.', '');
    $valorDisplay = number_format((float)$model->valor, 2, ',', '.');
}
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">

    <?php $form = ActiveForm::begin([
        'id'      => 'conta-pagar-form',
        'options' => [
            'class'   => 'space-y-6 p-4 sm:p-6 lg:p-8',
            'enctype' => 'multipart/form-data',
        ],
        'fieldConfig' => [
            'template'     => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
            'inputOptions' => ['class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'],
            'errorOptions' => ['class' => 'text-red-600 text-sm mt-1'],
        ],
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">

        <!-- ─── Descrição ──────────────────────────────────────────────── -->
        <div class="sm:col-span-2">
            <?= $form->field($model, 'descricao')->textInput([
                'maxlength'   => true,
                'placeholder' => 'Ex: Aluguel Fevereiro/2026, NF 001 — Fornecedor ABC, Conta de Energia Março/2026',
                'class'       => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
            ])->hint('<span class="text-xs text-gray-400">Detalhe específico do lançamento: NF, mês de referência, fornecedor, etc.</span>') ?>
        </div>

        <!-- ─── Tipo de Despesa (grupo aparece automaticamente via badge) ── -->
        <div class="sm:col-span-2">
            <div class="flex items-center gap-2 mb-2">
                <label class="block text-sm font-medium text-gray-700" for="contapagar-tipo_despesa_id">
                    Tipo de Despesa
                </label>
                <!-- Badge do grupo — atualizado via JS ao selecionar um tipo -->
                <span id="grupo-badge"
                      class="hidden items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold transition-all duration-200">
                </span>
            </div>

            <?= $form->field($model, 'tipo_despesa_id', ['template' => "{input}\n{hint}\n{error}"])->dropDownList(
                $tiposDropdown,
                [
                    'id'      => 'tipo-despesa-select',
                    'prompt'  => '— Selecione o tipo de despesa —',
                    'class'   => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                ]
            )->hint('<span class="text-xs text-gray-400">Categoria genérica. <a href="/contas-pagar/tipo-despesa" target="_blank" class="text-blue-500 hover:underline">Gerenciar tipos</a></span>') ?>
        </div>

        <!-- ─── Valor (máscara RTL) ──────────────────────────────────── -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2" for="valor-display">
                Valor <span class="text-red-500">*</span>
            </label>

            <!-- Campo visual com máscara — não é enviado ao servidor -->
            <input type="text"
                   id="valor-display"
                   name="valor_display"
                   inputmode="numeric"
                   placeholder="0,00"
                   autocomplete="off"
                   value="<?= Html::encode($valorDisplay) ?>"
                   class="currency-input w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base font-bold">

            <!-- Campo oculto com o valor numérico real enviado ao Yii/banco -->
            <?= Html::activeHiddenInput($model, 'valor', [
                'id'    => 'hidden-valor',
                'value' => $valorHidden,
            ]) ?>

            <!-- Erro de validação Yii para o campo valor -->
            <?= Html::error($model, 'valor', ['class' => 'text-red-600 text-sm mt-1']) ?>
        </div>

        <!-- ─── Data de Vencimento ──────────────────────────────────── -->
        <div>
            <?= $form->field($model, 'data_vencimento')->input('date', [
                'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
            ]) ?>
        </div>

        <!-- ─── Fornecedor (Select2 com busca) ────────────────────── -->
        <div>
            <?= $form->field($model, 'fornecedor_id')->widget(Select2::class, [
                'data'    => $fornecedores,
                'options' => [
                    'placeholder' => 'Buscar fornecedor...',
                    'id'          => 'fornecedor-select2',
                ],
                'pluginOptions' => [
                    'allowClear' => true,
                    'language'   => [
                        'noResults' => new JsExpression(
                            "function(){ return 'Nenhum fornecedor encontrado'; }"
                        ),
                    ],
                ],
            ])->hint('<span class="text-xs text-gray-500">Opcional. Se não selecionar, será uma despesa avulsa.</span>') ?>
        </div>

        <!-- ─── Forma de Pagamento ────────────────────────────────── -->
        <div>
            <?= $form->field($model, 'forma_pagamento_id')->dropDownList(
                $formasPagamento,
                [
                    'prompt' => 'Selecione a Forma de Pagamento...',
                    'class'  => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
                ]
            ) ?>
        </div>

        <!-- ─── Comprovante ───────────────────────────────────────── -->
        <div class="sm:col-span-2">
            <?= $form->field($model, 'comprovanteFile')->fileInput([
                'class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base',
            ])->hint('Formatos aceitos: PDF, JPG, PNG (Max: 5MB)') ?>

            <?php if ($model->arquivo_comprovante): ?>
                <div class="mt-2 text-sm text-gray-600 flex items-center">
                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    Arquivo atual: <span class="font-medium ml-1"><?= basename($model->arquivo_comprovante) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- ─── Observações ───────────────────────────────────────── -->
        <div class="sm:col-span-2">
            <?= $form->field($model, 'observacoes')->textarea([
                'rows'        => 3,
                'placeholder' => 'Detalhes adicionais...',
                'class'       => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none text-sm sm:text-base',
            ]) ?>
        </div>

    </div><!-- /grid -->

    <!-- ─── Botões ──────────────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200">
        <?= Html::submitButton(
            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
            . ($model->isNewRecord ? 'Registrar Conta' : 'Salvar Alterações'),
            ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 text-sm sm:text-base']
        ) ?>
        <?= Html::a(
            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar',
            ['index'],
            ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition duration-300 text-sm sm:text-base']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div><!-- /card -->

<?php
$tipoAtualId = Html::encode($model->tipo_despesa_id ?? '');

$this->registerJs(<<<JS
(function () {

    // ─── 1. Badge de grupo automático ────────────────────────────────
    var tiposInfo  = {$tiposInfoJson};
    var badgeCss   = {$badgeCssJson};
    var tipoSelect = document.getElementById('tipo-despesa-select');
    var badge      = document.getElementById('grupo-badge');
    var tipoAtual  = '{$tipoAtualId}';

    function atualizarBadge(id) {
        if (!id || !tiposInfo[id]) {
            badge.className = 'hidden items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold transition-all duration-200';
            badge.textContent = '';
            return;
        }
        var info = tiposInfo[id];
        var css  = badgeCss[info.grupo] || 'bg-gray-100 text-gray-700';
        badge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold transition-all duration-200 ' + css;
        badge.textContent = info.icon + ' ' + info.label;
    }

    // Inicializa badge na edição
    atualizarBadge(tipoAtual || tipoSelect.value);

    tipoSelect.addEventListener('change', function () {
        atualizarBadge(this.value);
    });

    // ─── 2. Máscara de valor (direita para esquerda) ─────────────────
    var inputDisplay = document.getElementById('valor-display');
    var hiddenValor  = document.getElementById('hidden-valor');

    function maskCurrency(event) {
        var raw = event.target.value.replace(/\D/g, '');
        if (raw === '') {
            event.target.value = '';
            hiddenValor.value  = '';
            return;
        }
        var number = parseInt(raw, 10) / 100;
        event.target.value = new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(number);
        hiddenValor.value = number.toFixed(2); // ex: "1500.00"
    }

    // Formata valor inicial na edição (banco retorna "1500.00")
    if (inputDisplay.value && !inputDisplay.value.includes(',')) {
        var num = parseFloat(inputDisplay.value) || 0;
        inputDisplay.value = new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2
        }).format(num);
    }
    // Garante hidden sincronizado na inicialização
    if (inputDisplay.value && !hiddenValor.value) {
        var raw = inputDisplay.value.replace(/\./g, '').replace(',', '.');
        hiddenValor.value = parseFloat(raw).toFixed(2);
    }

    inputDisplay.addEventListener('input', maskCurrency);

    // Desmascara antes do submit para garantir envio correto
    document.getElementById('conta-pagar-form').addEventListener('submit', function () {
        if (inputDisplay.value) {
            var raw = inputDisplay.value.replace(/\./g, '').replace(',', '.');
            hiddenValor.value = parseFloat(raw).toFixed(2);
        }
    });

})();
JS
);
?>