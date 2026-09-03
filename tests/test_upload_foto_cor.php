<?php

require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/../config/console.php';
$app = new yii\console\Application($config);

use app\modules\vendas\models\Produto;
use app\modules\vendas\models\ProdutoFoto;

echo "========================================================\n";
echo " TESTE: VINCULAÇÃO E CONSULTA DE FOTOS POR COR \n";
echo "========================================================\n\n";

$produto = Produto::findOne('4c39bd98-2653-499c-9ecd-2447914d9c96');
if (!$produto) {
    echo "❌ Produto não encontrado.\n";
    exit(1);
}

echo "✅ Produto carregado: {$produto->nome}\n";

// 1. Simula vinculação de uma foto à cor MARRON
$fotoTeste = new ProdutoFoto();
$fotoTeste->produto_id = $produto->id;
$fotoTeste->cor = 'MARRON';
$fotoTeste->arquivo_nome = 'teste_marron.jpg';
$fotoTeste->arquivo_path = 'uploads/produtos/' . $produto->id . '/teste_marron.jpg';
$fotoTeste->ordem = 99;
$fotoTeste->eh_principal = false;

if (!$fotoTeste->save(false)) {
    echo "❌ Erro ao salvar foto para MARRON.\n";
    exit(1);
}
echo "✅ Foto para a cor MARRON salva com sucesso no banco!\n";

// 2. Consulta fotos da cor MARRON
$fotosMarron = $produto->getFotosPorCor('MARRON');
echo "Total de fotos da cor MARRON recuperadas: " . count($fotosMarron) . "\n";

if (count($fotosMarron) >= 1 && $fotosMarron[0]->cor === 'MARRON') {
    echo "✅ SUCESSO: A cor MARRON agora possui foto associada e recuperada perfeitamente!\n";
} else {
    echo "❌ FALHA ao recuperar foto da cor MARRON.\n";
    exit(1);
}

// Limpeza da foto de teste
$fotoTeste->delete();
echo "✅ Limpeza da foto de teste concluída.\n";

echo "========================================================\n";
echo " TODOS OS TESTES PASSARAM COM SUCESSO! \n";
echo "========================================================\n";
