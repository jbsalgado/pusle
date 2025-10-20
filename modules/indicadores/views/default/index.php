<?php
use app\assets\AppAsset;
use yii\helpers\Url;
use yii\web\View;

$this->title = 'Módulo Métricas';

// Registra o CDN do Tailwind CSS diretamente nesta view
$this->registerJsFile('https://cdn.tailwindcss.com', ['position' => View::POS_HEAD]);
//$this->registerCssFile('@web/css/tailwind34/tailwind.css',['position'=>View::POS_HEAD]);
//AppAsset::register($this);
?>

<div class="bg-gray-100 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-10">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-2">Painel de Indicadores e Métricas</h1>
            <p class="text-md sm:text-lg text-gray-600">Selecione uma opção para gerenciar os dados do sistema.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

            <a href="<?= Url::to(['/metricas/ind-unidades-medida/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-blue-100 text-blue-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m-6 0v-4a2 2 0 012-2h2a2 2 0 012 2v4m-6 6h6m-9-3h9a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Unidades de Medida</h3>
                    <p class="text-gray-500 text-sm">Gerencie as unidades de medida para os indicadores.</p>
                </div>
            </a>

            <a href="<?= Url::to(['/metricas/ind-periodicidades/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-green-100 text-green-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Periodicidades</h3>
                    <p class="text-gray-500 text-sm">Defina e gerencie as periodicidades de medição e divulgação.</p>
                </div>
            </a>

            <a href="<?= Url::to(['/metricas/ind-fontes-dados/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-yellow-100 text-yellow-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M4 4v5h.582m15.418 0h.582m-19 0a2 2 0 002 2h10a2 2 0 002-2m-14 0h14m-12 4h.02M13 16h.02M13 12h.02M13 16h.02M13 12h.02M4 20h16a2 2 0 002-2V6a2 2 0 00-2-2H4a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Fontes de Dados</h3>
                    <p class="text-gray-500 text-sm">Cadastre as fontes de onde os dados são obtidos.</p>
                </div>
            </a>

            <a href="<?= Url::to(['/metricas/ind-dimensoes-indicadores/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-purple-100 text-purple-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Dimensões</h3>
                    <p class="text-gray-500 text-sm">Organize os indicadores por dimensões e sub-dimensões.</p>
                </div>
            </a>
            
            <a href="<?= Url::to(['/metricas/ind-niveis-abrangencia/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-indigo-100 text-indigo-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Níveis de Abrangência</h3>
                    <p class="text-gray-500 text-sm">Defina os níveis geográficos ou de organização aplicáveis.</p>
                </div>
            </a>

            <a href="<?= Url::to(['/metricas/ind-categorias-desagregacao/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-pink-100 text-pink-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M4 8h16M4 12h16M4 16h16" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Categorias de Desagregação</h3>
                    <p class="text-gray-500 text-sm">Crie categorias para desagregar os dados dos indicadores.</p>
                </div>
            </a>

            <a href="<?= Url::to(['/metricas/ind-opcoes-desagregacao/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-red-100 text-red-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2-8H7a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2V8a2 2 0 00-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Opções de Desagregação</h3>
                    <p class="text-gray-500 text-sm">Defina as opções dentro de cada categoria de desagregação.</p>
                </div>
            </a>

            <a href="<?= Url::to(['/metricas/ind-definicoes-indicadores/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-blue-100 text-blue-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Definições de Indicadores</h3>
                    <p class="text-gray-500 text-sm">Cadastre e gerencie a ficha técnica completa dos indicadores.</p>
                </div>
            </a>

            <a href="<?= Url::to(['/metricas/ind-atributos-qualidade-desempenho/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-green-100 text-green-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.03 12.03 0 003 9c0 5.591 3.824 10.29 9 11.691 5.176-1.4 9-6.1 9-11.691 0-1.018-.124-2.016-.382-3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Atributos de Qualidade</h3>
                    <p class="text-gray-500 text-sm">Defina faixas de referência e pesos para a qualidade.</p>
                </div>
            </a>
            
            <a href="<?= Url::to(['/metricas/ind-metas-indicadores/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-yellow-100 text-yellow-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m3 0l6-3m6 3v13M3 13V6a2 2 0 012-2h14a2 2 0 012 2v7M3 13a2 2 0 002 2h4m-4-2v6m6-6v6m10-6v6" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Metas de Indicadores</h3>
                    <p class="text-gray-500 text-sm">Gerencie as metas e objetivos para cada indicador.</p>
                </div>
            </a>

            <a href="<?= Url::to(['/metricas/ind-relacoes-indicadores/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-purple-100 text-purple-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8m3-9h-10a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Relações entre Indicadores</h3>
                    <p class="text-gray-500 text-sm">Vincule indicadores e descreva suas interdependências.</p>
                </div>
            </a>

            <a href="<?= Url::to(['/metricas/ind-valores-indicadores/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-indigo-100 text-indigo-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.03 12.03 0 003 9c0 5.591 3.824 10.29 9 11.691 5.176-1.4 9-6.1 9-11.691 0-1.018-.124-2.016-.382-3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Valores de Indicadores</h3>
                    <p class="text-gray-500 text-sm">Registre e visualize os valores coletados para cada indicador.</p>
                </div>
            </a>
            
            <a href="<?= Url::to(['/metricas/ind-valores-indicadores-desagregacoes/index']) ?>" class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-pink-100 text-pink-500 rounded-full p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354V17.646m0-13.292a2 2 0 00-2-2m2 2a2 2 0 012 2m-2-2l-2 10.646M12 17.646l-2 3.354m2-3.354l2 3.354m-2-2V11m0 0l-2-2m2 2l2 2" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Desagregações</h3>
                    <p class="text-gray-500 text-sm">Associe opções de desagregação aos valores dos indicadores.</p>
                </div>
            </a>

            <a href="<?= Url::to(['/metricas/permissao']) ?>"
                class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-pink-100 text-pink-500 rounded-full p-3 mb-4 flex items-center justify-center">
                        <!-- Usando @web corretamente -->
                        <img
                            src="<?= Yii::getAlias('@web') ?>/imagens/icones/png/vincular.png"
                            alt="Ícone Vincular Usuário"
                            class="h-12 w-12"
                        >
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Vincular Usuário</h3>
                    <p class="text-gray-500 text-sm">Vincular Usuário a Módulos do Sistema</p>
                </div>
            </a>

            <a href="<?= Url::to(['/metricas/sys-modulos/index']) ?>"
                class="transform transition duration-300 ease-in-out hover:scale-105 hover:shadow-lg">
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center text-center h-full">
                    <div class="bg-pink-100 text-pink-500 rounded-full p-3 mb-4 flex items-center justify-center">
                        <!-- Usando @web corretamente -->
                        <img
                            src="<?= Yii::getAlias('@web') ?>/imagens/icones/png/modulos.png"
                            alt="Ícone Vincular Usuário"
                            class="h-12 w-12"
                        >
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Cadastrar Módulos do Sistema</h3>
                    <p class="text-gray-500 text-sm">Cadastra Módulos no Sistema e o vincula a uma Dimensão de Indicadores</p>
                </div>
            </a>

        </div>
    </div>
</div>