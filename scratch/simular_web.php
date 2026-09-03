<?php
/**
 * Simulação do ambiente web (Apache + DocumentRoot = /srv/http/pulse/web)
 * Replica o bootstrap exato de web/index.php para testar a resolução de
 * aliases Yii, baseUrl e Url::to antes/depois das correções.
 *
 * Uso:
 *   php scratch/simular_web.php            # cenário A: acesso via http://localhost/...
 *   php scratch/simular_web.php pulse-web  # cenário B: acesso via http://localhost/pulse/web/...
 */

// Não imprime nada antes do bootstrap: captura o diagnóstico num array e só imprime no final.
$out = [];

$subdir = ($argv[1] ?? '') === 'pulse-web';

// ---- CENÁRIO A: DocumentRoot=/srv/http/pulse/web (acesso http://localhost/vendas/...) ----
if (!$subdir) {
    $_SERVER['SCRIPT_NAME']     = '/index.php';
    $_SERVER['REQUEST_URI']     = '/vendas/produto/index';
    $_SERVER['SCRIPT_FILENAME'] = '/srv/http/pulse/web/index.php';
    $_SERVER['DOCUMENT_ROOT']   = '/srv/http/pulse/web';
} 
// ---- CENÁRIO B: DocumentRoot=/srv/http/pulse (acesso http://localhost/pulse/web/...) ----
else {
    $_SERVER['SCRIPT_NAME']     = '/pulse/web/index.php';
    $_SERVER['REQUEST_URI']     = '/pulse/web/vendas/produto/index';
    $_SERVER['SCRIPT_FILENAME'] = '/srv/http/pulse/web/index.php';
    $_SERVER['DOCUMENT_ROOT']   = '/srv/http/pulse';
}
$_SERVER['HTTP_HOST']      = 'localhost';
$_SERVER['SERVER_NAME']    = 'localhost';
$_SERVER['SERVER_PORT']    = '80';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Desativa o ErrorHandler do Yii para não tentar enviar HTTP error code/log no CLI
define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_DEBUG', false);
define('YII_ENV', 'prod');

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

$_SERVER['argv'] = []; // evita que o Application tente agir como console

// Não roda ->run() para não enviar resposta HTTP no CLI; apenas instancia.
$app = new yii\web\Application($config);
$app->session->close(); // evita tentativa de abrir sessão no CLI

use yii\helpers\Url;

$caminho = 'uploads/produtos/4c39bd98-2653-499c-9ecd-2447914d9c96/6a98f70a89115.jpg';

$out[] = "========== Ambiente Web Simulado ==========";
$out[] = "Cenário      : " . ($subdir ? 'B (DocumentRoot=/srv/http/pulse, URL /pulse/web/...)' : 'A (DocumentRoot=/srv/http/pulse/web, URL /...)');
$out[] = "SCRIPT_NAME  : {$_SERVER['SCRIPT_NAME']}";
$out[] = "REQUEST_URI  : {$_SERVER['REQUEST_URI']}";
$out[] = "";

$out[] = "getAlias('@app')                  : " . var_export(Yii::getAlias('@app'), true);
$out[] = "getAlias('@web')                  : " . var_export(Yii::getAlias('@web'), true);
$out[] = "getAlias('@webroot')              : " . var_export(Yii::getAlias('@webroot'), true);
$out[] = "";

$out[] = "request->getBaseUrl()             : " . var_export(Yii::$app->request->getBaseUrl(), true);
$out[] = "request->baseUrl (prop)           : " . var_export(Yii::$app->request->baseUrl, true);
$out[] = "request->scriptUrl                : " . var_export(Yii::$app->request->scriptUrl, true);
$out[] = "request->getHostInfo()            : " . var_export(Yii::$app->request->getHostInfo(), true);
$out[] = "";

$fisicoWebroot = Yii::getAlias('@webroot/' . $caminho);
$fisicoApp = Yii::getAlias('@app/web/' . $caminho);
$out[] = "getAlias('@webroot/' . caminho)   : " . var_export($fisicoWebroot, true);
$out[] = "is_file(fisicoWebroot)            : " . var_export($fisicoWebroot !== false && is_file($fisicoWebroot), true);
$out[] = "getAlias('@app/web/' . caminho)   : " . var_export($fisicoApp, true);
$out[] = "is_file(fisicoApp)                : " . var_export($fisicoApp !== false && is_file($fisicoApp), true);
$out[] = "";

// Fluxo atualizado do gerarUrlFoto (cards, view)
$out[] = "--- Fluxo do gerarUrlFoto (cards, view) ---";
$caminhoFisico = Yii::getAlias('@webroot/' . ltrim($caminho, '/'));
if (!is_file($caminhoFisico)) {
    $caminhoFisico = Yii::getAlias('@app/web/' . ltrim($caminho, '/'));
}
if (!is_file($caminhoFisico)) {
    $out[] = "1. is_file retornou false → URL = null";
} else {
    $out[] = "1. is_file OK (" . $caminhoFisico . ")";
    $url = null;
    try {
        $webAlias = Yii::getAlias('@web');
        if (!empty($webAlias) && $webAlias !== '@web') {
            $url = rtrim($webAlias, '/') . '/' . ltrim($caminho, '/');
        }
    } catch (\Throwable $e) {}

    if (empty($url) && Yii::$app->has('request')) {
        $base = Yii::$app->request->baseUrl;
        if (!empty($base)) {
            $url = rtrim($base, '/') . '/' . ltrim($caminho, '/');
        }
    }
    if (empty($url)) {
        $url = '/' . ltrim($caminho, '/');
    }
    $out[] = "URL FINAL gerada pela view (cards): " . var_export($url, true);
}

// Fluxo da grid/tabela (view)
$out[] = "";
$out[] = "--- Fluxo da grid/tabela (view) ---";
$caminhoFoto = ltrim($caminho, '/');
$urlFoto = null;
try {
    $webAlias = Yii::getAlias('@web');
    if (!empty($webAlias) && $webAlias !== '@web') {
        $urlFoto = rtrim($webAlias, '/') . '/' . $caminhoFoto;
    }
} catch (\Throwable $e) {}
if (empty($urlFoto) && Yii::$app->has('request')) {
    $baseUrl = Yii::$app->request->baseUrl;
    if (!empty($baseUrl)) {
        $urlFoto = rtrim($baseUrl, '/') . '/' . $caminhoFoto;
    }
}
if (empty($urlFoto)) {
    $urlFoto = '/' . $caminhoFoto;
}
$out[] = "URL FINAL gerada pela view (grid): " . var_export($urlFoto, true);

// Teste direto do Model ProdutoFoto
$out[] = "";
$out[] = "--- Teste ProdutoFoto Model ---";
$pf = new app\modules\vendas\models\ProdutoFoto();
$pf->arquivo_path = $caminho;
$out[] = "ProdutoFoto->getUrl()             : " . var_export($pf->getUrl(), true);
$out[] = "ProdutoFoto->getUrlCompleta()     : " . var_export($pf->getUrlCompleta(), true);

$out[] = "";
$out[] = "-- Arquivo físico no disco: " . (is_file($fisicoWebroot) ? 'EXISTE' : 'NÃO EXISTE') . " --";

echo implode("\n", $out) . "\n";