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
use app\modules\vendas\models\ProdutoFoto;
use app\modules\vendas\models\Categoria;

echo "========================================================\n";
echo " TESTE DE VALIDAÇÃO: MATRIZ DE PRODUTO, VARIANTES & FOTOS \n";
echo "========================================================\n\n";

// 1. Busca uma categoria e usuário válidos
$categoria = Categoria::find()->one();
if (!$categoria) {
    echo "❌ Erro: Nenhuma categoria encontrada para o teste.\n";
    exit(1);
}

$usuarioId = $categoria->usuario_id;
echo "✅ Categoria ID: {$categoria->id}\n";
echo "✅ Usuário ID: {$usuarioId}\n\n";

// 2. Cria o Produto Mestre
$produto = new Produto();
$produto->usuario_id = $usuarioId;
$produto->categoria_id = $categoria->id;
$produto->nome = 'TÊNIS RUNNER PRO TESTE ' . time();
$produto->codigo_referencia = 'REF-TEST-' . rand(100, 999);
$produto->preco_custo = 120.00;
$produto->preco_venda_sugerido = 259.90;
$produto->modo_grade = 'matriz';
$produto->ativo = true;

if (!$produto->save()) {
    echo "❌ Erro ao criar produto mestre: " . json_encode($produto->getErrors()) . "\n";
    exit(1);
}
echo "✅ Produto Mestre Criado com Sucesso: ID {$produto->id} - Nome: {$produto->nome}\n";

// 3. Cria as variações na cor VERDE (exatamente o exemplo do usuário: 12, 36, 38, 40)
$tamanhosVerde = [
    '12' => 3,
    '36' => 5,
    '38' => 8,
    '40' => 4,
];

foreach ($tamanhosVerde as $tam => $qtd) {
    $var = new ProdutoVariante();
    $var->produto_id = $produto->id;
    $var->cor = 'VERDE';
    $var->tamanho = (string)$tam;
    $var->estoque_atual = $qtd;
    $var->preco_venda_sugerido = $produto->preco_venda_sugerido;
    $var->codigo_referencia = $produto->codigo_referencia . "-VERDE-{$tam}";
    $var->ativo = true;

    if (!$var->save()) {
        echo "❌ Erro ao criar variante VERDE {$tam}: " . json_encode($var->getErrors()) . "\n";
        exit(1);
    }
}
echo "✅ 4 Variantes da cor VERDE criadas (12, 36, 38, 40).\n";

// 4. Cria outra cor: PRETO (tamanhos 39, 41)
$tamanhosPreto = [
    '39' => 6,
    '41' => 7,
];

foreach ($tamanhosPreto as $tam => $qtd) {
    $var = new ProdutoVariante();
    $var->produto_id = $produto->id;
    $var->cor = 'PRETO';
    $var->tamanho = (string)$tam;
    $var->estoque_atual = $qtd;
    $var->preco_venda_sugerido = $produto->preco_venda_sugerido;
    $var->codigo_referencia = $produto->codigo_referencia . "-PRETO-{$tam}";
    $var->ativo = true;

    if (!$var->save()) {
        echo "❌ Erro ao criar variante PRETO {$tam}: " . json_encode($var->getErrors()) . "\n";
        exit(1);
    }
}
echo "✅ 2 Variantes da cor PRETO criadas (39, 41).\n";

// 5. Vincula Foto específica à cor VERDE
$fotoVerde = new ProdutoFoto();
$fotoVerde->produto_id = $produto->id;
$fotoVerde->cor = 'VERDE';
$fotoVerde->arquivo_nome = 'tenis_verde_lateral.jpg';
$fotoVerde->arquivo_path = 'uploads/produtos/' . $produto->id . '/tenis_verde.jpg';
$fotoVerde->eh_principal = false;
$fotoVerde->ordem = 1;

if (!$fotoVerde->save()) {
    echo "❌ Erro ao salvar foto da cor VERDE: " . json_encode($fotoVerde->getErrors()) . "\n";
    exit(1);
}
echo "✅ Foto vinculada exclusivamente à cor VERDE salva com sucesso!\n";

// 6. Validação do Estoque Consolidado
// Soma esperada: 3 + 5 + 8 + 4 + 6 + 7 = 33
$produto->refresh();
$produto->recalculateStockSum();
$produto->refresh();

$totalEsperado = 33;
echo "\n--- VERIFICAÇÃO DE ESTOQUE CONSOLIDADO ---\n";
echo "Estoque Atual no Mestre: {$produto->estoque_atual}\n";
echo "Estoque Esperado: {$totalEsperado}\n";

if ((float)$produto->estoque_atual === (float)$totalEsperado) {
    echo "✅ SUCESSO: O estoque total consolidado bate perfeitamente com a soma de todas as variantes!\n";
} else {
    echo "❌ FALHA: Estoque do mestre difere do total esperado.\n";
    exit(1);
}

// 7. Teste de Baixa de Estoque em 1 Variante
$varianteTeste = ProdutoVariante::findOne(['produto_id' => $produto->id, 'cor' => 'VERDE', 'tamanho' => '38']);
echo "\n--- TESTE DE BAIXA DE ESTOQUE NA VARIANTE (VERDE 38) ---\n";
echo "Estoque anterior da variante: {$varianteTeste->estoque_atual}\n";
$varianteTeste->baixarEstoque(2); // Vende 2 pares
$varianteTeste->refresh();
echo "Estoque após venda de 2 pares: {$varianteTeste->estoque_atual}\n";

$produto->refresh();
echo "Novo Estoque consolidado no Mestre: {$produto->estoque_atual} (Esperado: 31)\n";
if ((float)$produto->estoque_atual === 31.0) {
    echo "✅ SUCESSO: A baixa de estoque individual atualizou atomicamente o estoque do produto mestre!\n";
} else {
    echo "❌ FALHA: Estoque do pai não refletiu a baixa.\n";
    exit(1);
}

// 8. Teste de Filtro de Fotos por Cor
$fotosVerde = $produto->getFotosPorCor('VERDE');
echo "\n--- TESTE DE FOTOS POR COR ---\n";
echo "Total de fotos para VERDE: " . count($fotosVerde) . "\n";
if (count($fotosVerde) === 1 && $fotosVerde[0]->cor === 'VERDE') {
    echo "✅ SUCESSO: A foto vinculada ao tênis VERDE foi recuperada perfeitamente!\n";
} else {
    echo "❌ FALHA ao buscar fotos por cor.\n";
    exit(1);
}

// 9. Limpeza dos dados de teste
ProdutoFoto::deleteAll(['produto_id' => $produto->id]);
ProdutoVariante::deleteAll(['produto_id' => $produto->id]);
$produto->delete();
echo "\n✅ Dados de teste limpos do banco.\n";
echo "========================================================\n";
echo " TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! \n";
echo "========================================================\n";
