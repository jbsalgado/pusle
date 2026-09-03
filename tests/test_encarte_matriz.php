<?php
ob_start();

require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/../config/web.php';
$app = new yii\web\Application($config);

use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Encarte;
use app\modules\vendas\models\EncarteProduto;
use app\models\Usuario;
use app\modules\vendas\controllers\EncarteController;
use app\modules\vendas\services\EncartePdfService;

echo "========================================================\n";
echo " TESTE E2E: GERAÇÃO DE ENCARTE COM MATRIZ E PRODUTOS\n";
echo "========================================================\n\n";

$produtoId = '4c39bd98-2653-499c-9ecd-2447914d9c96'; // TENIS TIPO ALL STAR
$produto = Produto::findOne($produtoId);
if (!$produto) {
    echo "❌ Produto matriz não encontrado!\n";
    exit(1);
}

echo "✅ Produto matriz encontrado: {$produto->nome}\n";
echo "   Modo Grade: {$produto->modo_grade}\n";
echo "   Dono ID: {$produto->usuario_id}\n";

// Autentica o dono do produto
$usuario = Usuario::findOne($produto->usuario_id);
if (!$usuario) {
    echo "❌ Usuário dono não encontrado!\n";
    exit(1);
}
Yii::$app->user->login($usuario);
echo "✅ Usuário autenticado na sessão: {$usuario->nome} ({$usuario->email})\n\n";

// Simula requisição POST para gerar o encarte
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'modo_selecao' => 'PRODUTOS',
    'produtos_ids' => [$produtoId],
    'desmembrar_matriz' => 1,
    'apenas_com_estoque' => 1,
    'titulo' => 'OFERTAS EXCLUSIVAS ALL STAR POR COR/TAMANHO',
    'subtitulo' => 'Confira os modelos e tamanhos disponíveis',
    'cor_tema' => '#dc2626',
    'produtos_por_pagina' => 6,
    'inativar_anteriores' => 0
];

$controller = new EncarteController('encarte', Yii::$app->getModule('vendas'));
$resposta = $controller->actionGerar();

echo "Resultado actionGerar():\n";
print_r($resposta);

if (empty($resposta['success']) || empty($resposta['encarte_id'])) {
    echo "❌ Falha ao gerar encarte!\n";
    exit(1);
}

$encarteId = $resposta['encarte_id'];
$encarte = Encarte::findOne($encarteId);
echo "\n✅ Encarte criado com sucesso!\n";
echo "   ID: {$encarte->id}\n";
echo "   Token Público: {$encarte->token_publico}\n";
echo "   URL Pública: {$resposta['url_publica']}\n\n";

// Consulta os itens gerados no encarte
$itens = EncarteProduto::find()
    ->where(['encarte_id' => $encarteId])
    ->orderBy(['ordem' => SORT_ASC])
    ->all();

echo "Total de cards gerados (1 card por cor): " . count($itens) . "\n";
echo "Detalhes de cada Card por Cor:\n";
foreach ($itens as $idx => $it) {
    $fotoUrl = $it->getFotoUrl();
    $grade = $it->getGradeTamanhos();
    $pillsStr = implode(', ', array_map(function($g) {
        return $g['tamanho'] . ' (' . $g['qtd'] . 'un)';
    }, $grade));

    echo sprintf(
        "  #%02d: %s\n      Cor: %-10s | Estoque Total: %3d un | R$ %s\n      Pills Tamanhos: [%s]\n      Foto: %s\n\n",
        $idx + 1,
        $it->getNomeExibicao(),
        $it->cor ?: 'N/A',
        (int)$it->quantidade,
        number_format($it->getPrecoFinal(), 2, ',', '.'),
        $pillsStr,
        $fotoUrl ? 'SIM (' . basename(parse_url($fotoUrl, PHP_URL_PATH)) . ')' : 'SEM FOTO'
    );
}

// Testa a geração de PDF para este encarte
echo "\nTestando geração de PDF com EncartePdfService...\n";
$pdfService = new EncartePdfService();
$caminhoPdf = $pdfService->gerarPdf($encarte, [
    'show_marca' => true,
    'show_ref' => true,
    'show_badge' => true,
]);

if (file_exists($caminhoPdf)) {
    echo "✅ PDF gerado com sucesso! Arquivo: " . basename($caminhoPdf) . " (" . filesize($caminhoPdf) . " bytes)\n";
} else {
    echo "❌ Erro: Arquivo PDF não encontrado em: $caminhoPdf\n";
}

echo "\n========================================================\n";
echo " TESTE CONCLUÍDO COM SUCESSO!\n";
echo "========================================================\n";
