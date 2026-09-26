<?php
/**
 * Harness de regressão — Página de erro (Fase D)
 *
 * Valida que o 500 genérico voltou a ser rastreável:
 *  1. status HTTP 500;
 *  2. mensagem amigável sem vazar detalhes técnicos;
 *  3. ID de incidente exibido no HTML;
 *  4. ID de incidente gravado no log com contexto curado;
 *  5. o log NÃO contém variáveis sensíveis do ambiente (logVars = []).
 *
 * Uso: php tests/test_site_error_page.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

// --prod simula o comportamento da VPS (YII_DEBUG=false): sem detalhes técnicos na tela.
$modoProd = in_array('--prod', $argv ?? [], true);

define('YII_DEBUG', !$modoProd);
define('YII_ENV', 'test');

$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../web/index.php';
$_SERVER['DOCUMENT_ROOT']   = realpath(__DIR__ . '/../web');
$_SERVER['REQUEST_URI']     = '/vendas/venda-expressa/index';
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['HTTP_HOST']       = 'localhost';
$_SERVER['SERVER_NAME']     = 'localhost';
$_SERVER['SERVER_PORT']     = '80';
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['argv']            = [];

require __DIR__ . '/../vendor/autoload.php';

try {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
} catch (\Throwable $e) {
    // opcional em CLI
}

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

@ini_set('session.use_cookies', '0');
@ini_set('session.cache_limiter', '');
@ini_set('session.save_path', sys_get_temp_dir());
@session_id('cline-error-page-harness');
@session_start();

$app = new yii\web\Application($config);
Yii::$app->user->enableSession = false;

$falhas = [];
$checar = function (string $descricao, bool $ok) use (&$falhas) {
    echo ($ok ? '✅' : '❌') . ' ' . $descricao . "\n";
    if (!$ok) {
        $falhas[] = $descricao;
    }
};

$logFile = __DIR__ . '/../runtime/logs/app.log';
$logSizeAntes = is_file($logFile) ? filesize($logFile) : 0;

// Simula exatamente a assinatura do erro de produção: E_WARNING convertido pelo Yii.
$excecao = new \yii\base\ErrorException(
    'Undefined variable $usuarioLoja (simulado pelo harness)',
    E_WARNING,
    E_WARNING,
    __DIR__ . '/../modules/vendas/views/venda-expressa/index.php',
    813
);

Yii::$app->errorHandler->exception = $excecao;

echo "==============================================================\n";
echo " HARNESS DA PAGINA DE ERRO (Fase D)\n";
echo " Modo: " . (YII_DEBUG ? 'DEV (YII_DEBUG=true)' : 'PROD (YII_DEBUG=false, igual à VPS)') . "\n";
echo "==============================================================\n";

$html = Yii::$app->runAction('site/error');

$checar('Status HTTP = 500', (int)Yii::$app->response->statusCode === 500);
$checar('HTML contém a mensagem genérica', is_string($html) && strpos($html, 'An internal server error occurred.') !== false);
$checar('HTML contém o endereço da requisição', is_string($html) && strpos($html, '/vendas/venda-expressa/index') !== false);

if (YII_DEBUG) {
    $checar(
        'HTML em DEV expõe detalhes técnicos (esperado)',
        is_string($html) && strpos($html, 'Undefined variable $usuarioLoja') !== false
    );
} else {
    $checar(
        'HTML em PROD NÃO expõe a mensagem técnica',
        is_string($html) && strpos($html, 'Undefined variable $usuarioLoja') === false
    );
    $checar(
        'HTML em PROD NÃO expõe caminho de arquivo do servidor',
        is_string($html) && strpos($html, '/modules/vendas/views/') === false
    );
}

$temIncidente = is_string($html) && preg_match('/[A-F0-9]{10}/', $html, $m) === 1;
$checar('HTML contém ID de incidente (10 hex)', $temIncidente);
$incidente = $temIncidente ? $m[0] : null;
echo "   Incidente detectado: " . var_export($incidente, true) . "\n";

// Força o flush do logger para inspecionar o log curado.
Yii::getLogger()->flush(true);

$logNovo = '';
if (is_file($logFile)) {
    $fh = fopen($logFile, 'r');
    if ($fh !== false) {
        fseek($fh, $logSizeAntes);
        $logNovo = stream_get_contents($fh);
        fclose($fh);
    }
}

$checar('Log contém o ID de incidente', $incidente !== null && strpos($logNovo, $incidente) !== false);
$checar('Log contém a mensagem técnica original', strpos($logNovo, 'Undefined variable $usuarioLoja') !== false);
$checar('Log contém contexto curado (url)', strpos($logNovo, '"url"') !== false);
$checar('Log NÃO contém DB_PASSWORD (logVars = [])', strpos($logNovo, 'DB_PASSWORD') === false);
$checar('Log NÃO contém MP_ACCESS_TOKEN (logVars = [])', strpos($logNovo, 'MP_ACCESS_TOKEN') === false);

echo "==============================================================\n";
if (empty($falhas)) {
    echo " ✅ TODOS OS CHECKS PASSARAM\n";
    echo "==============================================================\n";
    exit(0);
}

echo ' ❌ FALHAS: ' . count($falhas) . "\n";
foreach ($falhas as $f) {
    echo '    - ' . $f . "\n";
}
echo "==============================================================\n";
exit(1);
