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
use app\modules\vendas\models\Categoria;

echo "========================================================\n";
echo " TESTE DE BUSCA: PREFIXO DE NOME E RANKING DE RELEVÂNCIA \n";
echo "========================================================\n\n";

$usuarioId = '5e449fee-4486-4536-a64f-74aed38a6987';
$categoria = Categoria::find()->where(['usuario_id' => $usuarioId])->one();
$categoriaId = $categoria ? $categoria->id : null;

// Produtos para o teste
$produtosCenarios = [
    [
        'nome' => 'LUMINÁRIA LED SOBREPOR QUADRADA 12W TESTE',
        'codigo_referencia' => 'ELET-TESTE-01',
        'codigo_barras' => '7891001001',
    ],
    [
        'nome' => 'PÁ QUADRADA COM CABO 71 CM TESTE',
        'codigo_referencia' => 'FERR-TESTE-21',
        'codigo_barras' => '7891117048671',
    ],
    [
        'nome' => 'PÁ DE BICO COM CABO 71 CM TESTE',
        'codigo_referencia' => 'FERR-TESTE-22',
        'codigo_barras' => '7891117048672',
    ],
    [
        'nome' => 'RALO CAIXA SIFONADA QUADRADA 150X50 TESTE',
        'codigo_referencia' => 'HIDR-TESTE-01',
        'codigo_barras' => '7892001001',
    ],
    [
        'nome' => 'LÂMPADA LED BULBO 9W QUADRADA TESTE',
        'codigo_referencia' => 'ELET-TESTE-02',
        'codigo_barras' => '7891002002',
    ],
    [
        'nome' => 'PARAFUSO SEXTAVADO 1/4 TESTE',
        'codigo_referencia' => 'FIX-TESTE-01',
        'codigo_barras' => '7893001001',
    ],
    [
        'nome' => 'CABO DE MADEIRA PARA PÁ TESTE',
        'codigo_referencia' => 'FERR-TESTE-23',
        'codigo_barras' => '7891117048673',
    ]
];

$idsCriados = [];

try {
    foreach ($produtosCenarios as $dados) {
        $p = new Produto();
        $p->usuario_id = $usuarioId;
        $p->categoria_id = $categoriaId;
        $p->nome = $dados['nome'];
        $p->codigo_referencia = $dados['codigo_referencia'];
        $p->codigo_barras = $dados['codigo_barras'];
        $p->preco_custo = 10.00;
        $p->preco_venda_sugerido = 25.00;
        $p->estoque_atual = 10;
        $p->ativo = true;
        if (!$p->save(false)) {
            echo "❌ Erro ao criar produto de teste: " . json_encode($p->getErrors()) . "\n";
            exit(1);
        }
        $idsCriados[] = $p->id;
    }
    echo "✅ " . count($idsCriados) . " produtos de teste criados com sucesso.\n\n";

    // Função de simulação da consulta atualizada da API
    $executarBusca = function($termoBusca) use ($usuarioId, $idsCriados) {
        $query = Produto::find()
            ->where(['ativo' => true, 'usuario_id' => $usuarioId])
            ->andWhere(['in', 'id', $idsCriados]);

        $rankParams = [];
        if ($termoBusca && trim($termoBusca) !== '') {
            $buscaTrim = trim(preg_replace('/\s+/', ' ', $termoBusca));
            $palavras = array_values(array_filter(explode(' ', $buscaTrim), function ($p) {
                return trim($p) !== '';
            }));

            if (!empty($palavras)) {
                $rankParams = [
                    ':rank_prefix_full' => $buscaTrim . '%',
                    ':rank_word_first' => $palavras[0] . ' %',
                    ':rank_prefix_first' => $palavras[0] . '%',
                    ':rank_contains_full' => '%' . $buscaTrim . '%',
                ];

                foreach ($palavras as $i => $palavra) {
                    $pRaw = trim($palavra);
                    $pSafe = str_replace(['%', '_'], ['\%', '\_'], $pRaw);
                    
                    $pStart = $pSafe . '%';
                    $pWord = '% ' . $pSafe . '%';
                    $pDash = '%-' . $pSafe . '%';
                    $pSlash = '%/' . $pSafe . '%';
                    $pExact = '%' . $pSafe . '%';

                    $pStartParam = ':p_start_' . $i;
                    $pWordParam = ':p_word_' . $i;
                    $pDashParam = ':p_dash_' . $i;
                    $pSlashParam = ':p_slash_' . $i;
                    $pRefParam = ':p_ref_' . $i;

                    $query->andWhere([
                        'OR',
                        ['ilike', new \yii\db\Expression('unaccent(prest_produtos.nome)'), new \yii\db\Expression("unaccent({$pStartParam})", [$pStartParam => $pStart])],
                        ['ilike', new \yii\db\Expression('unaccent(prest_produtos.nome)'), new \yii\db\Expression("unaccent({$pWordParam})", [$pWordParam => $pWord])],
                        ['ilike', new \yii\db\Expression('unaccent(prest_produtos.nome)'), new \yii\db\Expression("unaccent({$pDashParam})", [$pDashParam => $pDash])],
                        ['ilike', new \yii\db\Expression('unaccent(prest_produtos.nome)'), new \yii\db\Expression("unaccent({$pSlashParam})", [$pSlashParam => $pSlash])],
                        ['ilike', new \yii\db\Expression('unaccent(prest_produtos.codigo_referencia)'), new \yii\db\Expression("unaccent({$pRefParam})", [$pRefParam => $pExact])],
                        ['ilike', 'prest_produtos.codigo_barras', $pRaw],
                    ]);
                }
            }
        }

        if (!empty($rankParams)) {
            $query->orderBy(new \yii\db\Expression("
                CASE 
                    WHEN unaccent(prest_produtos.nome) ILIKE unaccent(:rank_prefix_full) THEN 1
                    WHEN unaccent(prest_produtos.nome) ILIKE unaccent(:rank_word_first) THEN 2
                    WHEN unaccent(prest_produtos.nome) ILIKE unaccent(:rank_prefix_first) THEN 3
                    WHEN unaccent(prest_produtos.nome) ILIKE unaccent(:rank_contains_full) THEN 4
                    ELSE 5
                END ASC,
                prest_produtos.nome ASC
            ", $rankParams));
        }

        return $query->all();
    };

    // CENÁRIO 1: Busca "PA QUADRADA" (o caso exato da imagem)
    echo "--- Cenário 1: Busca 'PA QUADRADA' ---\n";
    $resultados1 = $executarBusca('PA QUADRADA');
    $nomes1 = array_map(function($p) { return $p->nome; }, $resultados1);
    
    // Deve conter PÁ QUADRADA no topo (1º lugar)
    if (!empty($nomes1) && strpos($nomes1[0], 'PÁ QUADRADA') !== false) {
        echo "✅ PASS: O 1º produto retornado é '{$nomes1[0]}'!\n";
    } else {
        echo "❌ FAIL: 'PÁ QUADRADA' não veio no 1º lugar. Veio: " . ($nomes1[0] ?? 'Vazio') . "\n";
        exit(1);
    }

    // Não deve conter LUMINÁRIA nem RALO nem LÂMPADA
    $temLuminaria = false;
    $temRalo = false;
    foreach ($nomes1 as $n) {
        if (strpos($n, 'LUMINÁRIA') !== false) $temLuminaria = true;
        if (strpos($n, 'RALO') !== false) $temRalo = true;
    }
    if (!$temLuminaria && !$temRalo) {
        echo "✅ PASS: Nenhuma luminária ou ralo indevido foi retornado na busca 'PA QUADRADA'!\n";
    } else {
        echo "❌ FAIL: Luminária ou ralo indevido ainda apareceu nos resultados!\n";
        exit(1);
    }

    // CENÁRIO 2: Busca "PA"
    echo "\n--- Cenário 2: Busca 'PA' ---\n";
    $resultados2 = $executarBusca('PA');
    $nomes2 = array_map(function($p) { return $p->nome; }, $resultados2);
    
    // Itens que começam com PA devem vir nas primeiras posições
    $primeiroEhPa = !empty($nomes2) && (strpos($nomes2[0], 'PÁ') === 0 || strpos($nomes2[0], 'PA') === 0);
    if ($primeiroEhPa) {
        echo "✅ PASS: Topo da lista começa com PÁ / PARAFUSO: '{$nomes2[0]}'\n";
    } else {
        echo "❌ FAIL: Topo não começou com prefixo PA: " . ($nomes2[0] ?? 'Vazio') . "\n";
        exit(1);
    }

    // "LÂMPADA" não deve aparecer na busca "PA" (word boundary)
    $temLampada = false;
    foreach ($nomes2 as $n) {
        if (strpos($n, 'LÂMPADA') !== false) $temLampada = true;
    }
    if (!$temLampada) {
        echo "✅ PASS: 'LÂMPADA' (que contém 'pa' no meio) foi corretamente excluída da busca por 'PA'!\n";
    } else {
        echo "❌ FAIL: 'LÂMPADA' apareceu erroneamente na busca por 'PA'!\n";
        exit(1);
    }

    // CENÁRIO 3: Busca insensível a acentuação "PÁ QUADRADA" vs "PA QUADRADA"
    echo "\n--- Cenário 3: Busca com acento 'PÁ QUADRADA' ---\n";
    $resultados3 = $executarBusca('PÁ QUADRADA');
    $nomes3 = array_map(function($p) { return $p->nome; }, $resultados3);
    if (!empty($nomes3) && strpos($nomes3[0], 'PÁ QUADRADA') !== false) {
        echo "✅ PASS: Busca com acento 'PÁ QUADRADA' retornou '{$nomes3[0]}' no 1º lugar!\n";
    } else {
        echo "❌ FAIL: Busca com acento falhou.\n";
        exit(1);
    }

    // CENÁRIO 4: Busca por Código de Barras
    echo "\n--- Cenário 4: Busca por Código de Barras '7891117048671' ---\n";
    $resultados4 = $executarBusca('7891117048671');
    $nomes4 = array_map(function($p) { return $p->nome; }, $resultados4);
    if (count($nomes4) === 1 && strpos($nomes4[0], 'PÁ QUADRADA') !== false) {
        echo "✅ PASS: Código de barras exato retornou '{$nomes4[0]}'\n";
    } else {
        echo "❌ FAIL: Busca por código de barras falhou.\n";
        exit(1);
    }

    // CENÁRIO 5: Busca por Referência 'FERR-TESTE-21'
    echo "\n--- Cenário 5: Busca por Código de Referência 'FERR-TESTE-21' ---\n";
    $resultados5 = $executarBusca('FERR-TESTE-21');
    $nomes5 = array_map(function($p) { return $p->nome; }, $resultados5);
    if (count($nomes5) === 1 && strpos($nomes5[0], 'PÁ QUADRADA') !== false) {
        echo "✅ PASS: Código de referência retornou '{$nomes5[0]}'\n";
    } else {
        echo "❌ FAIL: Busca por referência falhou.\n";
        exit(1);
    }

    echo "\n========================================================\n";
    echo "🎉 TODOS OS 5 CENÁRIOS DE BUSCA PASSARAM COM SUCESSO!\n";
    echo "========================================================\n";

} finally {
    // Limpeza
    if (!empty($idsCriados)) {
        Produto::deleteAll(['id' => $idsCriados]);
        echo "🧹 Produtos de teste removidos do banco.\n";
    }
}
