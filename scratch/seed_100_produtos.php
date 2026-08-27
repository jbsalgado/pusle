<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
new yii\web\Application($config);

use app\models\Usuario;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\Encarte;
use app\modules\vendas\models\EncarteProduto;

echo "=== Seeding de 100 Produtos e Geração de Encarte ===\n";

$usuario = Usuario::findOne('5e449fee-4486-4536-a64f-74aed38a6987');
if (!$usuario) {
    $usuario = Usuario::find()->one();
}

if (!$usuario) {
    die("Erro: Nenhum usuário encontrado no sistema.\n");
}

echo "Usuário selecionado: " . $usuario->nome . " (ID: " . $usuario->id . ")\n";

// 1. Garantir Categoria para Testes
$categoria = Categoria::find()->where(['usuario_id' => $usuario->id])->one();
if (!$categoria) {
    $categoria = new Categoria();
    $categoria->usuario_id = $usuario->id;
    $categoria->nome = 'PETSHOP E AVES';
    $categoria->save(false);
}

echo "Categoria: " . $categoria->nome . "\n";

// Nomes e Preços para 100 produtos reais
$baseProdutos = [
    ['Ração Premium Cães Adultos 15kg', 149.90, 'SUPREME PET', 'KG'],
    ['Gaiola Criadeira Luxo Canário Malha Fina', 189.00, 'ROYAL GAIOLAS', 'UN'],
    ['Agapornis Roseicollis Cassal Reprodutor', 300.00, 'ALEX BIRDS', 'PAR'],
    ['Agapornis Personata Azul Cobalto Único', 180.00, 'ALEX BIRDS', 'UN'],
    ['Mistura Especial para Calopsitas 1kg', 18.50, 'NUTRILIFE', 'PCT'],
    ['Suplemento Vitamínico Aves Vita-Gold 50ml', 24.90, 'VETNIL', 'FRASCO'],
    ['Bebedouro Automático Malha Larga 200ml', 9.90, 'PLASTIPET', 'UN'],
    ['Comedouro com Poleiro em Madeira Nobre', 14.50, 'ARTESANAL', 'UN'],
    ['Semente de Girassol Miúdo Selecionada 1kg', 12.00, 'AGROSEEDS', 'KG'],
    ['Painço Português Amarelo Selecionado 1kg', 15.00, 'AGROSEEDS', 'KG'],
    ['Ninho em Madeira para Calopsita e Agapornis', 35.00, 'WOODPET', 'UN'],
    ['Viveiro Grande com Rodízios para Aves', 490.00, 'ROYAL GAIOLAS', 'UN'],
    ['Ração Extrusada para Psitacídeos 500g', 32.00, 'NUTRITROPICA', 'PCT'],
    ['Areia Sanitária Higiênica para Pássaros 2kg', 19.90, 'CLEANPET', 'PCT'],
    ['Brinquedo Balanço de Madeira com Miçangas', 16.90, 'TOYBIRDS', 'UN'],
    ['Ração Gatos Castrados Frango 10kg', 139.90, 'GOLDEN', 'KG'],
    ['Shampoo Antipulgas e Carrapatos 500ml', 29.90, 'SANOL', 'FRASCO'],
    ['Osso Mastigável Natural Bovino Pacote c/ 3', 22.00, 'DOGTOYS', 'PCT'],
    ['Escova Tira Pelos Rasqueadeira Profissional', 27.50, 'GROOMER', 'UN'],
    ['Comedouro Inox Antiderrapante 900ml', 34.00, 'INUXPET', 'UN']
];

// Buscar fotos existentes no banco para reusar nos 100 produtos
$fotoExistente = \app\modules\vendas\models\ProdutoFoto::find()->one();

$produtosCriadosIds = [];

for ($i = 1; $i <= 100; $i++) {
    $idxBase = ($i - 1) % count($baseProdutos);
    $itemInfo = $baseProdutos[$idxBase];

    $nomeProduto = "TESTE #" . sprintf('%03d', $i) . " - " . $itemInfo[0];
    $precoVenda = $itemInfo[1] + (($i % 10) * 2.5); // Variação de preço
    $marca = $itemInfo[2];
    $unidade = $itemInfo[3];
    $codigoRef = 'TESTE-' . sprintf('%04d', $i);

    // Verifica se produto já existe
    $prod = Produto::find()->where(['usuario_id' => $usuario->id, 'codigo_referencia' => $codigoRef])->one();
    if (!$prod) {
        $prod = new Produto();
        $prod->usuario_id = $usuario->id;
        $prod->categoria_id = $categoria->id;
        $prod->codigo_referencia = $codigoRef;
    }

    $prod->nome = $nomeProduto;
    $prod->preco_custo = $precoVenda * 0.6;
    $prod->preco_venda_sugerido = $precoVenda;
    $prod->marca = $marca;
    $prod->unidade_medida = $unidade;
    $prod->estoque_atual = 50;
    $prod->estoque_minimo = 5;
    $prod->ponto_corte = 5;
    $prod->ativo = true;
    $prod->save(false);

    // Se temos foto existente, vincula ao produto
    if ($fotoExistente && !\app\modules\vendas\models\ProdutoFoto::find()->where(['produto_id' => $prod->id])->exists()) {
        $novaFoto = new \app\modules\vendas\models\ProdutoFoto();
        $novaFoto->produto_id = $prod->id;
        $novaFoto->arquivo_nome = $fotoExistente->arquivo_nome ?: 'foto.jpg';
        $novaFoto->arquivo_path = $fotoExistente->arquivo_path;
        $novaFoto->eh_principal = true;
        $novaFoto->ordem = 1;
        $novaFoto->save(false);
    }

    $produtosCriadosIds[] = $prod->id;
}

echo "Total de 100 produtos cadastrados/atualizados com sucesso!\n";

// 2. Criar Encarte com os 100 Produtos
$encarte = new Encarte();
$encarte->usuario_id = $usuario->id;
$encarte->titulo = "GRANDIOSO FESTIVAL DE 100 OFERTAS DA SEMANA";
$encarte->subtitulo = "Confira 100 Super Preços Imbatíveis de Nossa Loja!";
$encarte->cor_tema = "red_gold";
$encarte->produtos_por_pagina = 6;
$encarte->save(false);

$ordem = 1;
foreach ($produtosCriadosIds as $prodId) {
    $ep = new EncarteProduto();
    $ep->encarte_id = $encarte->id;
    $ep->produto_id = $prodId;
    $ep->ordem = $ordem++;
    $ep->save(false);
}

$totalPaginas = ceil(count($produtosCriadosIds) / 6);

echo "========================================================\n";
echo "ENCARTE DE 100 PRODUTOS CRIADO COM SUCESSO!\n";
echo "ID Encarte: " . $encarte->id . "\n";
echo "Token Público: " . $encarte->token_publico . "\n";
echo "Total Produtos: " . count($produtosCriadosIds) . "\n";
echo "Total Lâminas/Páginas: " . $totalPaginas . "\n";
echo "URL Pública: /encarte/" . $encarte->token_publico . "\n";
echo "URL PDF: /encarte/pdf/" . $encarte->token_publico . "\n";
echo "========================================================\n";
