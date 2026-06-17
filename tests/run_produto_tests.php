<?php

// tests/run_produto_tests.php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/console.php');
new yii\console\Application($config);

use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Categoria;

function assertEquals($expected, $actual, $message) {
    if ($expected === $actual) {
        echo "✅ PASS: $message\n";
    } else {
        echo "❌ FAIL: $message (Expected: $expected, Actual: $actual)\n";
    }
}

function assertTrue($condition, $message) {
    if ($condition) {
        echo "✅ PASS: $message\n";
    } else {
        echo "❌ FAIL: $message\n";
    }
}

echo "🚀 Iniciando Testes Unitários de Produto...\n";
echo "------------------------------------------\n";

$usuarioId = '5e449fee-4486-4536-a64f-74aed38a6987';
$cat = Categoria::find()->where(['usuario_id' => $usuarioId])->one();
if (!$cat) {
    $cat = new Categoria(['nome' => 'Teste', 'usuario_id' => $usuarioId]);
    $cat->save(false);
}
$categoriaId = $cat->id;

// 1. Teste de Validação de Defaults
echo "1. Validando Defaults de Estoque...\n";
$p = new Produto();
$p->usuario_id = $usuarioId;
$p->categoria_id = $categoriaId;
$p->nome = 'Teste Default Valid';
$p->preco_custo = 10;
$p->preco_venda_sugerido = 20;
$p->codigo_referencia = 'TST-' . time();
assertTrue($p->validate(), 'Produto com defaults deve ser válido (ponto_corte=0, estoque_min=0)');
if (!$p->validate()) {
    print_r($p->getErrors());
}

// 2. Teste de Somatório de Grade
echo "\n2. Validando Somatório de Grade...\n";
$mestre = new Produto();
$mestre->usuario_id = $usuarioId;
$mestre->categoria_id = $categoriaId;
$mestre->nome = 'Mestre Teste';
$mestre->preco_custo = 10;
$mestre->preco_venda_sugerido = 20;
$mestre->codigo_referencia = 'MST-' . time();
$mestre->estoque_atual = 0;
$mestre->save(false);

echo "Mestre Criado ID: " . $mestre->id . "\n";

$v1 = new Produto();
$v1->parent_id = $mestre->id;
$v1->usuario_id = $usuarioId;
$v1->categoria_id = $categoriaId;
$v1->nome = 'Var 1';
$v1->cor = 'Azul';
$v1->estoque_atual = 33;
$v1->preco_custo = 10;
$v1->preco_venda_sugerido = 20;
$v1->codigo_referencia = $mestre->codigo_referencia . '-V1';
$v1->save(false);

$v2 = new Produto();
$v2->parent_id = $mestre->id;
$v2->usuario_id = $usuarioId;
$v2->categoria_id = $categoriaId;
$v2->nome = 'Var 2';
$v1->cor = 'Vermelho';
$v2->estoque_atual = 17;
$v2->preco_custo = 10;
$v2->preco_venda_sugerido = 20;
$v2->codigo_referencia = $mestre->codigo_referencia . '-V2';
$v2->save(false);

echo "Variacoes criadas: V1=33, V2=17\n";

$mestre->recalculateStockSum();
$mestre->refresh();

assertEquals(50.0, (float)$mestre->estoque_atual, 'Estoque mestre deve ser a soma das variacoes (33 + 17 = 50)');

// Limpeza
$v1->delete();
$v2->delete();
$mestre->delete();

echo "------------------------------------------\n";
echo "🏁 Testes concluídos.\n";
