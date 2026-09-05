<?php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/web.php');
new yii\web\Application($config);

use app\modules\vendas\services\AudioProcessorService;
use app\modules\api\controllers\BridgeController;

echo "=== TESTANDO INTEGRAÇÃO DO PULSE BRIDGE GO ===\n\n";

echo "1. Verificando status da Bridge...\n";
$isOnline = BridgeController::isBridgeOnline();
$lastSeen = BridgeController::getBridgeLastSeenSeconds();
echo "   Online: " . ($isOnline ? "SIM (Ativo)" : "NÃO (Offline)") . "\n";
echo "   Último sinal: {$lastSeen}s atrás\n\n";

if (!$isOnline) {
    echo "ERRO: A Bridge precisa estar online para este teste.\n";
    exit(1);
}

// Testando com vídeo do YouTube NÃO em cache
$url = 'https://www.youtube.com/watch?v=kXYiU_JCYtU';
echo "2. Solicitando download via AudioProcessorService::downloadYoutubeAudio...\n";
echo "   URL: $url\n";
$inicio = microtime(true);

try {
    $res = AudioProcessorService::downloadYoutubeAudio($url);
    $duracao = round(microtime(true) - $inicio, 2);
    echo "   Resultado:\n";
    print_r($res);
    echo "\n   Tempo total de processamento: {$duracao}s\n";
    echo "   Arquivo físico gerado em: " . Yii::getAlias('@app/web/' . $res['arquivo']) . "\n";
    echo "   Tamanho do arquivo: " . round(filesize(Yii::getAlias('@app/web/' . $res['arquivo'])) / 1024, 1) . " KB\n";
    echo "\n=== TESTE CONCLUÍDO COM SUCESSO! ===\n";
} catch (\Throwable $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
