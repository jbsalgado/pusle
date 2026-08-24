<?php
/**
 * Script de Migração Automática de Fotos de Produtos
 * Baixa as 2.104 imagens de top-construcoes.catalogo.cloud para o servidor local construcao.oncode.app.br
 */
set_time_limit(0);
ini_set('memory_limit', '512M');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/console.php';
new yii\console\Application($config);

$baseUrl = "https://top-construcoes.catalogo.cloud/";
$baseDir = "/srv/http/construcao/pulse-plus/web/";

echo "==========================================================" . PHP_EOL;
echo "🚀 Iniciando Migração Automática das Fotos dos Produtos" . PHP_EOL;
echo "==========================================================" . PHP_EOL;

$sql = "SELECT id, produto_id, arquivo_path FROM prest_produto_fotos WHERE arquivo_path IS NOT NULL AND arquivo_path <> ''";
$fotos = Yii::$app->db->createCommand($sql)->queryAll();

$total = count($fotos);
echo "📌 Total de registros de fotos no banco: {$total}" . PHP_EOL;

$baixados = 0;
$ja_existiam = 0;
$falhas = 0;

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

foreach ($fotos as $index => $foto) {
    $relativePath = ltrim($foto['arquivo_path'], '/');
    $destPath = $baseDir . $relativePath;
    $sourceUrl = $baseUrl . $relativePath;

    if (file_exists($destPath) && filesize($destPath) > 0) {
        $ja_existiam++;
        continue;
    }

    $destDir = dirname($destPath);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0777, true);
    }

    curl_setopt($ch, CURLOPT_URL, $sourceUrl);
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode === 200 && !empty($content)) {
        file_put_contents($destPath, $content);
        @chmod($destPath, 0777);
        $baixados++;
        if ($baixados % 50 === 0 || $baixados === 1) {
            echo "  [".($index + 1)."/{$total}] Baixado: {$relativePath} (" . round(strlen($content)/1024, 1) . " KB)" . PHP_EOL;
        }
    } else {
        $falhas++;
        echo "  ❌ Erro {$httpCode} ao baixar: {$sourceUrl}" . PHP_EOL;
    }
}

curl_close($ch);

// Ajustar permissões de toda a pasta uploads
exec("chown -R http:http " . escapeshellarg($baseDir . "uploads") . " 2>/dev/null");
exec("chmod -R 777 " . escapeshellarg($baseDir . "uploads") . " 2>/dev/null");

echo "==========================================================" . PHP_EOL;
echo "✅ Migração Concluída com Sucesso!" . PHP_EOL;
echo "   • Total processado: {$total}" . PHP_EOL;
echo "   • Baixadas agora:   {$baixados}" . PHP_EOL;
echo "   • Já existiam:      {$ja_existiam}" . PHP_EOL;
echo "   • Falhas:           {$falhas}" . PHP_EOL;
echo "==========================================================" . PHP_EOL;
