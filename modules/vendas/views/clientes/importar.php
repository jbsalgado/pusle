<?php

use yii\helpers\Html;

$this->title = 'Importar Clientes via CSV';
$this->params['breadcrumbs'][] = ['label' => 'Clientes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 py-4 px-3 sm:py-6 sm:px-4 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <?= Html::encode($this->title) ?>
                </h1>
                <p class="text-sm text-gray-600 mt-1">Cadastre múltiplos clientes de forma rápida através de uma planilha CSV.</p>
            </div>
            
            <div class="flex gap-2 w-full sm:w-auto">
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>Voltar',
                    ['index'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow transition text-sm w-full sm:w-auto justify-center']
                ) ?>
                <?= Html::a(
                    '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Baixar Modelo CSV',
                    ['baixar-modelo-csv'],
                    ['class' => 'inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow transition text-sm w-full sm:w-auto justify-center']
                ) ?>
            </div>
        </div>

        <!-- Banner Informativo -->
        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded-r-lg shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-amber-800">Verificação Automática de CPF</h3>
                    <div class="mt-1 text-sm text-amber-700">
                        <p>Os registros com <strong>CPF já cadastrado</strong> no sistema serão <strong>ignorados automaticamente</strong> e o processo continuará para os próximos registros sem interromper a importação.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de Upload -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <?= Html::beginForm(['importar-csv'], 'post', ['enctype' => 'multipart/form-data', 'id' => 'form-import-csv']) ?>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Selecione o arquivo CSV (.csv ou .txt)</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-500 transition-colors cursor-pointer" id="drop-zone">
                    <div class="space-y-2 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20a4 4 0 004 4h24a4 4 0 004-4V20L28 8z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M28 8v12h12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="flex text-sm text-gray-600 justify-center">
                            <label for="csv_file" class="relative cursor-pointer bg-white rounded-md font-semibold text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                <span>Clique para escolher o arquivo</span>
                                <input id="csv_file" name="csv_file" type="file" accept=".csv, .txt" class="sr-only" required>
                            </label>
                            <p class="pl-1">ou arraste para cá</p>
                        </div>
                        <p class="text-xs text-gray-500">Arquivos CSV ou TXT (máx. 5MB). Delimitadores suportados: vírgula (,) ou ponto e vírgula (;)</p>
                        <div id="file-name-preview" class="text-sm font-semibold text-blue-700 hidden mt-2"></div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-gray-200">
                <?= Html::a('Cancelar', ['index'], ['class' => 'px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg text-center transition']) ?>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Iniciar Importação
                </button>
            </div>

            <?= Html::endForm() ?>
        </div>

        <!-- Instruções de Colunas -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Colunas Aceitas no Arquivo CSV
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-200">
                    <span class="font-bold text-blue-900 block mb-1">nome_completo <span class="text-red-500">*obrigatório</span></span>
                    <span class="text-gray-600 text-xs">Apelidos aceitos: <code>nome</code>, <code>cliente</code>, <code>razao_social</code>.</span>
                </div>

                <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-200">
                    <span class="font-bold text-blue-900 block mb-1">cpf</span>
                    <span class="text-gray-600 text-xs">CPF do cliente (com ou sem pontuação). Apelidos: <code>documento</code>.</span>
                </div>

                <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-200">
                    <span class="font-bold text-blue-900 block mb-1">telefone</span>
                    <span class="text-gray-600 text-xs">Telefone/Celular do cliente. Apelidos: <code>celular</code>, <code>fone</code>.</span>
                </div>

                <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-200">
                    <span class="font-bold text-blue-900 block mb-1">email</span>
                    <span class="text-gray-600 text-xs">E-mail do cliente.</span>
                </div>

                <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-200">
                    <span class="font-bold text-blue-900 block mb-1">logradouro</span>
                    <span class="text-gray-600 text-xs">Endereço (rua, avenida). Apelidos: <code>endereco</code>, <code>rua</code>.</span>
                </div>

                <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-200">
                    <span class="font-bold text-blue-900 block mb-1">numero, complemento, bairro</span>
                    <span class="text-gray-600 text-xs">Número do imóvel, complemento (apto, bloco) e bairro.</span>
                </div>

                <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-200">
                    <span class="font-bold text-blue-900 block mb-1">cidade, estado, cep</span>
                    <span class="text-gray-600 text-xs">Cidade, UF (2 letras) e CEP (apenas números ou formatado).</span>
                </div>

                <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-200">
                    <span class="font-bold text-blue-900 block mb-1">observacoes, regiao</span>
                    <span class="text-gray-600 text-xs">Observações do cliente e nome da região vinculada.</span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('csv_file');
    const preview = document.getElementById('file-name-preview');
    const dropZone = document.getElementById('drop-zone');

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                preview.textContent = '📄 Arquivo selecionado: ' + this.files[0].name;
                preview.classList.remove('hidden');
            }
        });
    }

    if (dropZone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.classList.add('border-blue-500', 'bg-blue-50');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-blue-500', 'bg-blue-50');
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files[0] && fileInput) {
                fileInput.files = files;
                preview.textContent = '📄 Arquivo selecionado: ' + files[0].name;
                preview.classList.remove('hidden');
            }
        });
    }
});
</script>
