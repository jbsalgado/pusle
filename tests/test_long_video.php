<?php

require(__DIR__ . '/../vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/web.php');
new yii\web\Application($config);

use app\modules\vendas\services\AudioProcessorService;
use app\modules\api\controllers\BridgeController;

echo "=== TESTANDO DOWNLOAD DE VÍDEO LONGO (30 MINUTOS AUTO) VIA PULSE BRIDGE ===\n\n";

$isOnline = BridgeController::isBridgeOnline();
echo "Bridge Online: " . ($isOnline ? "SIM" : "NÃO") . "\n";
if (!$isOnline) {
    echo "Erro: Bridge offline.\n";
    exit(1);
}

$url = $argv[1] ?? 'https://www.youtube.com/watch?v=78QAexgLtcw';
echo "Solicitando vídeo: $url\n";
$inicio = microtime(true);

try {
    $res = AudioProcessorService::downloadYoutubeAudio($url);
    $tempo = round(microtime(true) - $inicio, 2);
    echo "\n=== SUCESSO! ===\n";
    echo "Tempo total: {$tempo}s\n";
    print_r($res);
    $caminhoFisico = Yii::getAlias('@app/web/' . $res['arquivo']);
    echo "Arquivo gerado: $caminhoFisico\n";
    echo "Tamanho: " . round(filesize($caminhoFisico) / 1024 / 1024, 2) . " MB\n";
    echo "Duração registrada: " . $res['duracao'] . "s (" . round($res['duracao'] / 60, 1) . " min)\n";
} catch (\Throwable $e) {
    echo "\nERRO: " . $e->getMessage() . "\n";
    echo "Em: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
