<?php

require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/../config/console.php';
new yii\console\Application($config);

use app\modules\vendas\models\Produto;
use app\modules\vendas\models\ProdutoVariante;
use app\modules\vendas\models\Categoria;

echo "========================================================\n";
echo " TESTE: VARIANTES COM LETRAS (P, M, G, GG) E ÍMPARES \n";
echo "========================================================\n\n";

$categoria = Categoria::find()->one();
if (!$categoria) {
    echo "❌ Erro: Nenhuma categoria encontrada.\n";
    exit(1);
}

// 1. Cria Camiseta com tamanhos em LETRAS (P, M, G, GG, XG)
$camiseta = new Produto();
$camiseta->usuario_id = $categoria->usuario_id;
$camiseta->categoria_id = $categoria->id;
$camiseta->nome = 'CAMISETA DRY FIT TESTE ' . time();
$camiseta->codigo_referencia = 'CAM-' . rand(100, 999);
$camiseta->preco_custo = 25.00;
$camiseta->preco_venda_sugerido = 69.90;
$camiseta->modo_grade = 'matriz';
$camiseta->ativo = true;

if (!$camiseta->save()) {
    echo "❌ Erro ao salvar camiseta: " . json_encode($camiseta->getErrors()) . "\n";
    exit(1);
}
echo "✅ Produto Criado: {$camiseta->nome}\n";

$tamanhosLetras = [
    'P' => 10,
    'M' => 15,
    'G' => 20,
    'GG' => 8,
    'XG' => 4,
];

foreach ($tamanhosLetras as $letra => $qtd) {
    $v = new ProdutoVariante();
    $v->produto_id = $camiseta->id;
    $v->cor = 'AZUL MARINHO';
    $v->tamanho = $letra;
    $v->estoque_atual = $qtd;
    $v->preco_venda_sugerido = $camiseta->preco_venda_sugerido;
    $v->codigo_referencia = $camiseta->codigo_referencia . "-AZUL-{$letra}";
    $v->ativo = true;
    if (!$v->save()) {
        echo "❌ Erro ao salvar variante letra {$letra}: " . json_encode($v->getErrors()) . "\n";
        exit(1);
    }
}
echo "✅ 5 Variantes em letras criadas com sucesso (P, M, G, GG, XG)!\n";

$camiseta->refresh();
$camiseta->recalculateStockSum();
$camiseta->refresh();

// 10 + 15 + 20 + 8 + 4 = 57
echo "Estoque total consolidado da Camiseta: {$camiseta->estoque_atual} (Esperado: 57)\n";
if ((float)$camiseta->estoque_atual === 57.0) {
    echo "✅ SUCESSO: Estoque consolidado de letras validado com perfeição!\n";
} else {
    echo "❌ FALHA no estoque consolidado.\n";
    exit(1);
}

// Limpeza
ProdutoVariante::deleteAll(['produto_id' => $camiseta->id]);
$camiseta->delete();
echo "✅ Limpeza concluída com sucesso.\n";
