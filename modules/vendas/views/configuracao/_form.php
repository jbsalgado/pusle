<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use Yii;

?>

<div class="configuracao-form">
    <?php $form = ActiveForm::begin([
        'id' => 'configuracao-form',
        'options' => ['class' => 'space-y-6 p-4 sm:p-6 lg:p-8'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
            'inputOptions' => ['class' => 'w-full px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'],
            'errorOptions' => ['class' => 'text-red-600 text-sm mt-1'],
        ],
    ]); ?>

    <!-- Identificação -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Identificação da Loja
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div class="sm:col-span-2">
                <?= $form->field($model, 'nome_loja')->textInput(['maxlength' => true, 'placeholder' => 'Nome da sua loja']) ?>
            </div>

            <div class="sm:col-span-2">
                <?= $form->field($model, 'logo_path')->textInput(['maxlength' => true, 'placeholder' => 'Caminho relativo (ex: imagens/logo/logo-02.png) ou URL completa']) ?>
                <p class="text-xs text-gray-500 mt-1">💡 Use caminho relativo a partir da pasta web (ex: imagens/logo/logo-02.png) ou URL completa (ex: https://exemplo.com/logo.png)</p>
                <?php if ($model->logo_path): ?>
                    <div class="mt-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preview:</label>
                        <?php
                        // Se o caminho não começar com http://, https:// ou /, adiciona @web
                        $logoUrl = $model->logo_path;
                        if (!preg_match('/^(https?:\/\/|\/)/', $logoUrl)) {
                            $logoUrl = Yii::getAlias('@web') . '/' . ltrim($logoUrl, '/');
                        }
                        ?>
                        <img src="<?= Html::encode($logoUrl) ?>" alt="Logo" class="h-20 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div style="display: none;" class="h-20 border border-gray-300 rounded-lg p-2 bg-gray-50 flex items-center justify-center text-gray-400 text-xs">
                            <span>⚠️ Logo não encontrada. Verifique o caminho.</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Integração Mercado Pago -->
    <?php $usuarioAtual = Yii::$app->user->identity; ?>
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Pagamentos Mercado Pago (Split 0,5%)
        </h2>
        <p class="text-sm text-gray-600 mb-4">Conecte sua conta Mercado Pago para ativar PIX com split automático (nossa taxa 0,5% já calculada na application_fee).</p>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <button
                type="button"
                id="btn-conectar-mercadopago"
                data-tenant="<?= Html::encode($usuarioAtual->id ?? '') ?>"
                class="inline-flex items-center px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white font-semibold rounded-lg shadow-md transition duration-300"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5a1 1 0 112 0v6a1 1 0 01-.293.707l-3 3a1 1 0 11-1.414-1.414L11 10.586V5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 19h10" />
                </svg>
                Conectar conta Mercado Pago
            </button>

            <?php $mpConectado = $usuarioAtual && ($usuarioAtual->mp_access_token || $usuarioAtual->mercadopago_access_token); ?>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm <?= $mpConectado ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                <?= $mpConectado ? 'Conta conectada' : 'Conta não conectada' ?>
            </span>
        </div>
        <p class="text-xs text-gray-500 mt-2">Usamos os dados da loja logada para associar o token OAuth ao tenant correto.</p>
    </div>

    <!-- Cores -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
            </svg>
            Cores do Tema
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
                <?= $form->field($model, 'cor_primaria')->textInput([
                    'maxlength' => 7,
                    'type' => 'color',
                    'class' => 'w-full h-12 border border-gray-300 rounded-lg cursor-pointer',
                    'placeholder' => '#3B82F6'
                ])->hint('Cor primária da loja (formato hexadecimal: #RRGGBB)') ?>
            </div>

            <div>
                <?= $form->field($model, 'cor_secundaria')->textInput([
                    'maxlength' => 7,
                    'type' => 'color',
                    'class' => 'w-full h-12 border border-gray-300 rounded-lg cursor-pointer',
                    'placeholder' => '#10B981'
                ])->hint('Cor secundária da loja (formato hexadecimal: #RRGGBB)') ?>
            </div>
        </div>
    </div>

    <!-- Redes Sociais -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
            Redes Sociais (Opcional)
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
            <div>
                <?= $form->field($model, 'whatsapp')->textInput(['maxlength' => true, 'placeholder' => '(00) 00000-0000']) ?>
            </div>

            <div>
                <?= $form->field($model, 'instagram')->textInput(['maxlength' => true, 'placeholder' => '@usuario ou URL']) ?>
            </div>

            <div>
                <?= $form->field($model, 'facebook')->textInput(['maxlength' => true, 'placeholder' => 'URL do Facebook']) ?>
            </div>
        </div>
    </div>

    <!-- Configuração PIX -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Configuração PIX Estático
        </h2>
        <p class="text-sm text-gray-600 mb-4">Configure os dados para geração de QR Code PIX estático (sem gateway de pagamento)</p>

        <div class="space-y-4">
            <div>
                <?= $form->field($model, 'pix_chave')->textInput([
                    'maxlength' => true,
                    'placeholder' => 'Ex: +5581992888872 (celular), 12345678900 (CPF), ou email@exemplo.com'
                ])->hint('Chave PIX: celular (formato E.164: +55XXXXXXXXXXX), CPF, CNPJ, email ou chave aleatória') ?>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <?= $form->field($model, 'pix_nome')->textInput([
                        'maxlength' => 100,
                        'placeholder' => 'Ex: JOSE BARBOSA DOS SANTOS'
                    ])->hint('Nome do recebedor (máx 25 caracteres, sem acentos)') ?>
                </div>

                <div>
                    <?= $form->field($model, 'pix_cidade')->textInput([
                        'maxlength' => 50,
                        'placeholder' => 'Ex: CARUARU'
                    ])->hint('Cidade do recebedor (máx 15 caracteres, sem acentos)') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Endereço e Mensagem -->
    <div class="border-b border-gray-200 pb-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Informações Adicionais
        </h2>

        <div class="space-y-4">
            <div>
                <?= $form->field($model, 'endereco_completo')->textarea([
                    'rows' => 3,
                    'placeholder' => 'Endereço completo da loja...'
                ]) ?>
            </div>

            <div>
                <?= $form->field($model, 'mensagem_boas_vindas')->textarea([
                    'rows' => 4,
                    'placeholder' => 'Mensagem de boas-vindas para os clientes...'
                ]) ?>
            </div>
        </div>
    </div>

    <!-- Configurações do Sistema -->
    <div class="pb-2">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Configurações do Sistema
        </h2>

        <div class="space-y-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <?= Html::activeCheckbox($model, 'catalogo_publico', [
                            'class' => 'w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2',
                            'label' => false,
                            'id' => 'catalogo_publico_checkbox'
                        ]) ?>
                    </div>
                    <div class="ml-3">
                        <label for="catalogo_publico_checkbox" class="font-medium text-gray-900 text-sm sm:text-base cursor-pointer">Catálogo Público</label>
                        <p class="text-xs text-gray-500">Permite que o catálogo seja acessado publicamente sem necessidade de login</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <?= Html::activeCheckbox($model, 'aceita_orcamentos', [
                            'class' => 'w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2',
                            'label' => false,
                            'id' => 'aceita_orcamentos_checkbox'
                        ]) ?>
                    </div>
                    <div class="ml-3">
                        <label for="aceita_orcamentos_checkbox" class="font-medium text-gray-900 text-sm sm:text-base cursor-pointer">Aceita Orçamentos</label>
                        <p class="text-xs text-gray-500">Permite que clientes solicitem orçamentos através do sistema</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200">
        <?= Html::submitButton(
            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Salvar Configurações',
            ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300']
        ) ?>
        <?= Html::a(
            '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar',
            ['view'],
            ['class' => 'w-full sm:flex-1 inline-flex items-center justify-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg transition duration-300']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
// Preview de cores em tempo real
document.addEventListener('DOMContentLoaded', function() {
    const corPrimaria = document.getElementById('configuracao-cor_primaria');
    const corSecundaria = document.getElementById('configuracao-cor_secundaria');
    
    if (corPrimaria) {
        corPrimaria.addEventListener('input', function() {
            // Atualiza preview se houver
            const preview = document.querySelector('.preview-cor-primaria');
            if (preview) {
                preview.style.backgroundColor = this.value;
            }
        });
    }
    
    if (corSecundaria) {
        corSecundaria.addEventListener('input', function() {
            const preview = document.querySelector('.preview-cor-secundaria');
            if (preview) {
                preview.style.backgroundColor = this.value;
            }
        });
    }

    // OAuth Mercado Pago
    const btnConectarMP = document.getElementById('btn-conectar-mercadopago');
    if (btnConectarMP) {
        btnConectarMP.addEventListener('click', async function(e) {
            e.preventDefault();
            const tenantId = this.dataset.tenant;
            if (!tenantId) {
                alert('Não foi possível identificar a loja logada. Faça login novamente.');
                return;
            }

            const original = this.innerHTML;
            this.disabled = true;
            this.innerHTML = 'Gerando link...';

            try {
                const resp = await fetch('<?= Url::to(['/api/mercado-pago/connect-url']) ?>?tenant_id=' + tenantId);
                const data = await resp.json();

                if (data && data.url) {
                    window.open(data.url, '_blank');
                } else {
                    alert(data.erro || 'Não foi possível gerar a URL de conexão.');
                }
            } catch (err) {
                alert('Erro ao conectar com o Mercado Pago.');
            } finally {
                this.disabled = false;
                this.innerHTML = original;
            }
        });
    }
});
</script>

