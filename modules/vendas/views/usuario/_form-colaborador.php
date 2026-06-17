<?php

use yii\helpers\Html;

?>

<div class="mt-4 p-3 bg-green-50 rounded-md border border-green-200">
    <p class="text-sm text-green-800">
        <strong>✅ Dados Sincronizados:</strong> Os dados pessoais (nome, CPF, telefone, email) serão automaticamente copiados do usuário para o colaborador. Você não precisa preencher novamente.
    </p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <?= $form->field($model, 'nome_completo')->hiddenInput(['id' => 'colaborador-nome_completo'])->label(false) ?>

    <?= $form->field($model, 'cpf')->hiddenInput(['id' => 'colaborador-cpf'])->label(false) ?>

    <?= $form->field($model, 'telefone')->hiddenInput(['id' => 'colaborador-telefone'])->label(false) ?>

    <?= $form->field($model, 'email')->hiddenInput(['id' => 'colaborador-email', 'type' => 'email'])->label(false) ?>

    <?= $form->field($model, 'data_admissao')->input('date', [
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500',
    ]) ?>

    <?= $form->field($model, 'observacoes')->textarea([
        'rows' => 3,
        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500',
        'placeholder' => 'Observações sobre o colaborador...'
    ]) ?>
</div>

<!-- Permissões e Papéis -->
<div class="mt-6 p-4 bg-gray-50 rounded-lg">
    <h3 class="text-md font-semibold text-gray-900 mb-4">Permissões e Papéis</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <?= $form->field($model, 'eh_vendedor')->checkbox([
                'class' => 'h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded',
                'label' => 'É Vendedor',
                'labelOptions' => ['class' => 'text-sm font-medium text-gray-700']
            ]) ?>
            <?= $form->field($model, 'percentual_comissao_venda')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'min' => '0',
                'max' => '100',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500',
                'placeholder' => '0.00'
            ])->label('Comissão Venda (%)')->hint('Percentual de comissão sobre vendas.') ?>
        </div>
        
        <div>
            <?= $form->field($model, 'eh_cobrador')->checkbox([
                'class' => 'h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded',
                'label' => 'É Cobrador',
                'labelOptions' => ['class' => 'text-sm font-medium text-gray-700']
            ]) ?>
            <?= $form->field($model, 'percentual_comissao_cobranca')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'min' => '0',
                'max' => '100',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500',
                'placeholder' => '0.00'
            ])->label('Comissão Cobrança (%)')->hint('Percentual de comissão sobre cobranças.') ?>
        </div>
    </div>
    
    <div class="mt-4">
        <?= $form->field($model, 'eh_administrador')->checkbox([
            'class' => 'h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded',
            'label' => 'É Administrador (acesso completo)',
            'labelOptions' => ['class' => 'text-sm font-medium text-gray-700']
        ])->hint('Administradores têm acesso completo ao sistema, igual ao dono da loja.') ?>
    </div>
</div>

<div class="mt-4 p-3 bg-yellow-50 rounded-md border border-yellow-200">
    <p class="text-sm text-yellow-800">
        <strong>⚠️ Importante:</strong> O colaborador deve ser pelo menos <strong>Vendedor</strong> ou <strong>Cobrador</strong> (ou ambos).
    </p>
</div>

