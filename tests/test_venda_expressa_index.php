<?php
/**
 * Harness de diagnóstico — Venda Expressa (PDV)
 *
 * Reproduz, em CLI, a renderização de GET /vendas/venda-expressa/index com
 * identidade injetada, capturando a exceção REAL que, em produção, aparece
 * mascarada como "Error (#2) / An internal server error occurred.".
 *
 * Segurança / garantias:
 *  - NÃO altera .env, nem config, nem código da aplicação.
 *  - Toda a renderização roda dentro de uma transação de banco que é
 *    SEMPRE revertida (rollBack) -> nenhum INSERT/UPDATE é persistido,
 *    mesmo com os bootstraps de forma de pagamento do actionIndex().
 *  - Mede a severidade do erro: se for yii\base\ErrorException com code 2,
 *    é exatamente o E_WARNING que gera a página "Error (#2)".
 *
 * Uso:
 *   php tests/test_venda_expressa_index.php                    # todos os usuários
 *   php tests/test_venda_expressa_index.php --all               # idem
 *   php tests/test_venda_expressa_index.php --loja=<uuid>
 *   php tests/test_venda_expressa_index.php --dump              # salva HTML em /tmp
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

define('YII_DEBUG', true);
define('YII_ENV', 'test'); // evita bootstrap dos módulos debug/gii definidos em config/web.php

$opts = [];
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--loja=') === 0) {
        $opts['loja'] = substr($arg, 7);
    } elseif ($arg === '--all') {
        $opts['all'] = true;
    } elseif ($arg === '--dump') {
        $opts['dump'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Uso: php tests/test_venda_expressa_index.php [--loja=<uuid>] [--all] [--dump]\n";
        exit(0);
    }
}

// ---------------------------------------------------------------------------
// 1) Ambiente web simulado (mesmo bootstrap de web/index.php)
// ---------------------------------------------------------------------------
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../web/index.php';
$_SERVER['DOCUMENT_ROOT']   = realpath(__DIR__ . '/../web');
$_SERVER['REQUEST_URI']     = '/vendas/venda-expressa/index';
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['HTTP_HOST']       = 'localhost';
$_SERVER['SERVER_NAME']     = 'localhost';
$_SERVER['SERVER_PORT']     = '80';
$_SERVER['HTTPS']           = '';
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['argv']            = [];

require __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
} catch (\Throwable $e) {
    fwrite(STDERR, "Aviso: falha ao carregar .env: {$e->getMessage()}\n");
}

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

// ---------------------------------------------------------------------------
// 2) Sessão controlada (CLI não tem cookies/headers)
// ---------------------------------------------------------------------------
@ini_set('session.use_cookies', '0');
@ini_set('session.cache_limiter', '');
@ini_set('session.save_path', sys_get_temp_dir());
@session_id('cline-venda-expressa-harness');
@session_start();

$app = new yii\web\Application($config);

// Não queremos que a injeção de identidade escreva em sessão
Yii::$app->user->enableSession = false;


// ---------------------------------------------------------------------------
// 3) Utilidades de diagnóstico
// ---------------------------------------------------------------------------
function severityName(int $severity): string
{
    $map = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];
    return $map[$severity] ?? ('desconhecida(' . $severity . ')');
}

function describeThrowable(\Throwable $e, string $prefix = '')
{
    $isTargetSignature = ($e instanceof \yii\base\ErrorException) && ((int)$e->getCode() === 2);

    echo "{$prefix}CLASS   : " . get_class($e) . "\n";
    echo "{$prefix}CODE    : " . var_export($e->getCode(), true) . "\n";
    if ($e instanceof \yii\base\ErrorException) {
        echo "{$prefix}SEVERITY: " . (int)$e->getSeverity() . " (" . severityName((int)$e->getSeverity()) . ")\n";
    }
    echo "{$prefix}MESSAGE : " . $e->getMessage() . "\n";
    echo "{$prefix}FILE    : " . $e->getFile() . ":" . $e->getLine() . "\n";
    if ($isTargetSignature) {
        echo "{$prefix}>>> ASSINATURA DO ERRO DE PRODUCAO: 'Error (#2)' (E_WARNING convertido pelo Yii)\n";
    }

    $prev = $e->getPrevious();
    $depth = 0;
    while ($prev !== null && $depth < 3) {
        echo "{$prefix}PREVIOUS[" . get_class($prev) . "]: " . $prev->getMessage()
            . " @ " . $prev->getFile() . ":" . $prev->getLine() . "\n";
        $prev = $prev->getPrevious();
        $depth++;
    }

    echo "{$prefix}TRACE (10 primeiras linhas):\n";
    $lines = explode("\n", $e->getTraceAsString());
    foreach (array_slice($lines, 0, 10) as $line) {
        echo "{$prefix}    " . $line . "\n";
    }
}

// ---------------------------------------------------------------------------
// 4) Seleção de usuários/tenants
// ---------------------------------------------------------------------------
if (!empty($opts['loja'])) {
    $usuarios = Yii::$app->db->createCommand(
        'SELECT id, nome, eh_dono_loja FROM prest_usuarios WHERE id = :id',
        [':id' => $opts['loja']]
    )->queryAll();
    if (empty($usuarios)) {
        fwrite(STDERR, "Usuário '{$opts['loja']}' não encontrado em prest_usuarios.\n");
        exit(1);
    }
} else {
    $usuarios = Yii::$app->db->createCommand(
        'SELECT id, nome, eh_dono_loja FROM prest_usuarios ORDER BY eh_dono_loja DESC, nome ASC'
    )->queryAll();
}

echo "==============================================================\n";
echo " HARNESS DA VENDA EXPRESSA - diagnostico do 'Error (#2)'\n";
echo "==============================================================\n";
echo " PHP              : " . PHP_VERSION . "\n";
echo " error_reporting  : " . error_reporting() . " (E_WARNING incluso? "
    . ((error_reporting() & E_WARNING) ? 'SIM' : 'NAO') . ")\n";
echo " YII_DEBUG        : " . (YII_DEBUG ? 'true' : 'false') . "\n";
echo " YII_ENV          : " . YII_ENV . "\n";
echo " DB DSN           : " . (Yii::$app->db->dsn ?? '?') . "\n";
echo " View esperada    : " . realpath(__DIR__ . '/../modules/vendas/views/venda-expressa/index.php') . "\n";
echo " Usuarios testados: " . count($usuarios) . "\n";
echo "==============================================================\n\n";

$module = Yii::$app->getModule('vendas');
if ($module === null) {
    fwrite(STDERR, "Módulo 'vendas' não encontrado na aplicação.\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// 5) Execução do diagnóstico (com rollback garantido)
// ---------------------------------------------------------------------------
$totOk = 0;
$totFalha = 0;
$totAssinatura = 0;

foreach ($usuarios as $u) {
    $uuid = $u['id'];
    $dono = ($u['eh_dono_loja'] === true || $u['eh_dono_loja'] === 't' || $u['eh_dono_loja'] === 1);
    $rotulo = ($dono ? 'DONO' : 'COLABORADOR');

    echo "--------------------------------------------------------------\n";
    echo "► {$u['nome']} ({$rotulo}) — {$uuid}\n";
    echo "--------------------------------------------------------------\n";

    $html = null;
    $erro = null;

    $tx = Yii::$app->db->beginTransaction();
    try {
        $identidade = \app\models\Usuario::findOne($uuid);
        if (!$identidade) {
            throw new \RuntimeException("Usuário {$uuid} não pôde ser carregado como identity.");
        }

        Yii::$app->user->setIdentity($identidade);

        /** @var \app\modules\vendas\controllers\VendaExpressaController $controller */
        $controller = new \app\modules\vendas\controllers\VendaExpressaController('venda-expressa', $module);
        $controller->layout = false; // a tela roda sem layout (findLayoutFile devolve false)

        $html = $controller->runAction('index');
    } catch (\Throwable $e) {
        $erro = $e;
    } finally {
        try {
            if (Yii::$app->db->getTransaction() !== null) {
                Yii::$app->db->getTransaction()->rollBack();
            }
        } catch (\Throwable $rb) {
            fwrite(STDERR, "Falha ao reverter transação: {$rb->getMessage()}\n");
        }
    }

    if ($erro === null) {
        $totOk++;
        $tamanho = is_string($html) ? strlen($html) : 0;
        $temProdutos = is_string($html) && strpos($html, 'const produtosArray') !== false;
        echo "✅ RENDERIZOU SEM EXCEÇÃO\n";
        echo "   HTML bytes           : {$tamanho}\n";
        echo "   contém produtosArray : " . ($temProdutos ? 'SIM' : 'NAO') . "\n";

        if (!empty($opts['dump']) && is_string($html)) {
            $alvo = '/tmp/venda-expressa-' . $uuid . '.html';
            @file_put_contents($alvo, $html);
            echo "   dump                 : {$alvo}\n";
        }
    } else {
        $totFalha++;
        if ($erro instanceof \yii\base\ErrorException && (int)$erro->getCode() === 2) {
            $totAssinatura++;
        }
        echo "❌ EXCEÇÃO NA RENDERIZAÇÃO\n";
        describeThrowable($erro, '   ');
    }
    echo "\n";
}

echo "==============================================================\n";
echo " RESUMO\n";
echo "==============================================================\n";
echo " Renderizações OK                            : {$totOk}\n";
echo " Exceções capturadas                         : {$totFalha}\n";
echo " ↳ com assinatura 'Error (#2)' (E_WARNING)   : {$totAssinatura}\n";
if ($totAssinatura > 0) {
    echo "\n➡️  CAUSA CONFIRMADA: 'Error (#2)' = yii\\base\\ErrorException (severity 2 = E_WARNING).\n";
    echo "    A mensagem / arquivo:linha acima é exatamente o ponto a corrigir.\n";
} elseif ($totFalha > 0) {
    echo "\n➡️  Há exceções, mas sem a assinatura (#2). Ver classes/códigos acima.\n";
} elseif ($totOk > 0) {
    echo "\n✅ Nenhuma exceção: todos os tenants renderizaram a Venda Expressa.\n";
    echo "    Se você já aplicou as correções, o 'Error (#2)' está resolvido.\n";
} else {
    echo "\n➡️  Nenhum usuário testado. Verifique os argumentos (--loja=<uuid>).\n";
}
echo "==============================================================\n";


