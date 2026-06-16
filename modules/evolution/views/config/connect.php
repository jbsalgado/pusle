<?php

/** @var \yii\web\View $this */
/** @var string|null $qrCodeBase64 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Parear WhatsApp — PULSE-PLUS';

// URL Ajax para o polling de status (rota interna do módulo)
$checkStatusUrl = Url::to(['/evolution/config/check-status-ajax']);
$indexUrl       = Url::to(['/evolution/config/index']);
$pollingInterval = 4000; // milissegundos entre cada verificação

// Registra o script de polling apenas se o QR Code foi gerado com sucesso
if ($qrCodeBase64 !== null) {
    $this->registerJs(
        <<<JS
        (function () {
            var intervalId = null;
            var maxAttempts = 75; // ~5 minutos com polling a cada 4s
            var attempts    = 0;

            function checkConnection() {
                attempts++;

                if (attempts > maxAttempts) {
                    clearInterval(intervalId);
                    document.getElementById('polling-status').innerHTML =
                        '<span class="text-yellow-600 flex items-center justify-center"><svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> QR Code expirado. <a href="" class="underline ml-1 hover:text-yellow-700">Clique aqui para gerar um novo.</a></span>';
                    return;
                }

                fetch('{$checkStatusUrl}', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.connected === true) {
                        clearInterval(intervalId);

                        var statusEl = document.getElementById('polling-status');
                        if (statusEl) {
                            statusEl.innerHTML =
                                '<span class="text-green-600 font-bold flex items-center justify-center">' +
                                '<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>' +
                                'WhatsApp conectado! Redirecionando…' +
                                '</span>';
                        }

                        // Aguarda 1.5s para o usuário ver a mensagem de sucesso
                        setTimeout(function () {
                            window.location.href = '{$indexUrl}';
                        }, 1500);
                    }
                })
                .catch(function (err) {
                    // Falha silenciosa — o polling continua na próxima iteração
                    console.warn('Evolution polling error:', err);
                });
            }

            // Inicia o polling imediatamente
            checkConnection();
            intervalId = setInterval(checkConnection, {$pollingInterval});
        })();
        JS,
        \yii\web\View::POS_END
    );
}
?>

<div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2 mb-1 m-0">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                Parear WhatsApp
            </h1>
            <p class="text-gray-500 m-0">Escaneie o QR Code abaixo com o WhatsApp do seu celular.</p>
        </div>
        <?= Html::a(
            '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg> Voltar',
            ['index'],
            ['class' => 'inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors']
        ) ?>
    </div>

    <div class="flex justify-center">
        <div class="w-full max-w-md">

            <?php if ($qrCodeBase64 !== null): ?>

                <!-- Card do QR Code -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden text-center mb-6">
                    <div class="p-8">

                        <div class="flex justify-center mb-6">
                            <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-sm">
                                <img
                                    id="qr-code-img"
                                    src="<?= Html::encode($qrCodeBase64) ?>"
                                    alt="QR Code WhatsApp"
                                    class="w-64 h-64 object-contain rounded-lg"
                                />
                            </div>
                        </div>

                        <p class="text-gray-500 text-sm mb-4 flex flex-col sm:flex-row items-center justify-center m-0">
                            <svg class="w-4 h-4 mr-1.5 mb-1 sm:mb-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            <span>Abra o WhatsApp &rarr; <strong class="mx-1 text-gray-700 font-semibold">Aparelhos conectados</strong> &rarr; <strong class="ml-1 text-gray-700 font-semibold">Conectar aparelho</strong></span>
                        </p>

                        <!-- Status do polling -->
                        <div id="polling-status" class="mt-6 text-sm text-gray-500 flex items-center justify-center bg-gray-50 py-3 rounded-lg border border-gray-100 font-medium">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Aguardando pareamento…
                        </div>

                    </div>
                </div>

                <!-- Instruções -->
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-6 text-sm text-blue-800 shadow-sm">
                    <p class="mb-3 font-semibold text-blue-900 flex items-center m-0">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Passo a passo:
                    </p>
                    <ol class="list-decimal pl-5 space-y-2 text-blue-800 m-0">
                        <li>Abra o <strong>WhatsApp</strong> no seu celular.</li>
                        <li>Toque em <strong>Mais opções</strong> (três pontinhos) ou <strong>Configurações</strong>.</li>
                        <li>Selecione <strong>Aparelhos conectados</strong>.</li>
                        <li>Toque em <strong>Conectar aparelho</strong> e aponte para o QR Code.</li>
                    </ol>
                </div>

            <?php else: ?>

                <!-- Erro ao gerar QR Code -->
                <div class="bg-red-50 border border-red-200 rounded-xl p-8 text-center shadow-sm">
                    <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <h2 class="text-xl font-bold text-red-800 mb-2">Falha ao gerar o QR Code</h2>
                    <p class="text-sm text-red-700 mb-8">
                        Não foi possível contactar a Evolution API. Verifique se o serviço Go está em execução
                        e se as configurações de URL e chave global estão corretas em <code class="bg-red-100 px-1.5 py-0.5 rounded font-mono">config/params.php</code>.
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center gap-3">
                        <?= Html::a(
                            '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Tentar novamente',
                            ['connect'],
                            ['class' => 'inline-flex justify-center items-center px-5 py-2.5 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors']
                        ) ?>
                        <?= Html::a(
                            'Voltar',
                            ['index'],
                            ['class' => 'inline-flex justify-center items-center px-5 py-2.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors']
                        ) ?>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>

</div>
