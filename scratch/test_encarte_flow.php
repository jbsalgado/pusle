<?php
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
new yii\web\Application($config);

use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Encarte;
use app\modules\vendas\models\EncarteProduto;
use app\modules\vendas\services\EncartePdfService;
use kartik\mpdf\Pdf;

echo "=== Testando Fluxo de Encarte Digital ===\n";

$produtos = Produto::find()->limit(4)->all();
if (empty($produtos)) {
    echo "Nenhum produto cadastrado no banco.\n";
    exit(0);
}

$lojaId = $produtos[0]->usuario_id;
echo "Loja ID: " . $lojaId . "\n";
echo "Total produtos encontrados: " . count($produtos) . "\n";

$encarte = new Encarte();
$encarte->usuario_id = $lojaId;
$encarte->titulo = "TESTE ENCARTE DE OFERTAS";
$encarte->subtitulo = "Ofertas Válidas Hoje";
$encarte->cor_tema = "red_gold";
$encarte->produtos_por_pagina = 6;

if ($encarte->save()) {
    echo "Encarte criado com sucesso! ID: {$encarte->id}, Token: {$encarte->token_publico}\n";
    echo "URL pública: " . $encarte->getUrlPublica() . "\n";

    foreach ($produtos as $idx => $p) {
        $ep = new EncarteProduto();
        $ep->encarte_id = $encarte->id;
        $ep->produto_id = $p->id;
        $ep->ordem = $idx + 1;
        $ep->save();
    }

    echo "Produtos vinculados ao encarte.\n";

    // Testar geração de PDF
    try {
        $pdfOutput = EncartePdfService::gerarPdf($encarte, Pdf::DEST_STRING);
        echo "PDF Gerado com Sucesso! Tamanho: " . strlen($pdfOutput) . " bytes.\n";
    } catch (\Exception $e) {
        echo "Erro ao gerar PDF: " . $e->getMessage() . "\n";
    }

} else {
    echo "Erro ao criar encarte: " . json_encode($encarte->getErrors()) . "\n";
}

echo "=== Teste Concluído ===\n";
