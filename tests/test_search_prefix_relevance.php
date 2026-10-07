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
        'nome' => 'PALHA ACO N1 TESTE',
        'codigo_referencia' => 'UTIL-TESTE-01',
        'codigo_barras' => '7894001001',
    ],
    [
        'nome' => 'PANO CHÃO XADREZ TESTE',
        'codigo_referencia' => 'UTIL-TESTE-02',
        'codigo_barras' => '7894002002',
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
            $temEspacoFinal = (bool)preg_match('/\s+$/', $termoBusca);
            $buscaLimpa = trim(preg_replace('/\s+/', ' ', $termoBusca));
            $palavras = array_values(array_filter(explode(' ', $buscaLimpa), function ($p) {
                return trim($p) !== '';
            }));
            $totalPalavras = count($palavras);

            if (!empty($palavras)) {
                $rankParams = [
                    ':rank_phrase_multi' => $buscaLimpa . '%',
                    ':rank_word_first' => $palavras[0] . ' %',
                    ':rank_prefix_first' => $palavras[0] . '%',
                    ':rank_contains_full' => '%' . $buscaLimpa . '%',
                ];

                foreach ($palavras as $i => $palavra) {
                    $ehUltimaPalavra = ($i === $totalPalavras - 1);
                    $palavraFechada = ($ehUltimaPalavra && $temEspacoFinal);

                    $pRaw = trim($palavra);
                    $pSafe = str_replace(['%', '_'], ['\%', '\_'], $pRaw);
                    
                    $pStartParam = ':p_start_' . $i;
                    $pWordParam = ':p_word_' . $i;
                    $pDashParam = ':p_dash_' . $i;
                    $pSlashParam = ':p_slash_' . $i;
                    $pExactParam = ':p_exact_' . $i;
                    $pRefParam = ':p_ref_' . $i;

                    if ($palavraFechada) {
                        $pWordSpace = $pSafe . ' %';
                        $pWordMid = '% ' . $pSafe . ' %';
                        $pWordDash = '%-' . $pSafe . ' %';
                        $pWordSlash = '%/' . $pSafe . ' %';

                        $query->andWhere([
                            'OR',
                            ['ilike', new \yii\db\Expression('unaccent(prest_produtos.nome)'), new \yii\db\Expression("unaccent({$pStartParam})", [$pStartParam => $pWordSpace])],
                            ['ilike', new \yii\db\Expression('unaccent(prest_produtos.nome)'), new \yii\db\Expression("unaccent({$pWordParam})", [$pWordParam => $pWordMid])],
                            ['ilike', new \yii\db\Expression('unaccent(prest_produtos.nome)'), new \yii\db\Expression("unaccent({$pDashParam})", [$pDashParam => $pWordDash])],
                            ['ilike', new \yii\db\Expression('unaccent(prest_produtos.nome)'), new \yii\db\Expression("unaccent({$pSlashParam})", [$pSlashParam => $pWordSlash])],
                            ['=', new \yii\db\Expression('unaccent(prest_produtos.nome)'), new \yii\db\Expression("unaccent({$pExactParam})", [$pExactParam => $pSafe])],
                            ['ilike', new \yii\db\Expression('unaccent(prest_produtos.codigo_referencia)'), new \yii\db\Expression("unaccent({$pRefParam})", [$pRefParam => '%' . $pSafe . '%'])],
                            ['ilike', 'prest_produtos.codigo_barras', $pRaw],
                        ]);
                    } else {
                        $pStart = $pSafe . '%';
                        $pWord = '% ' . $pSafe . '%';
                        $pDash = '%-' . $pSafe . '%';
                        $pSlash = '%/' . $pSafe . '%';
                        $pExact = '%' . $pSafe . '%';

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
        }

        if (!empty($rankParams)) {
            $query->orderBy(new \yii\db\Expression("
                CASE 
                    WHEN :rank_phrase_multi != :rank_prefix_first AND unaccent(prest_produtos.nome) ILIKE unaccent(:rank_phrase_multi) THEN 1
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

    // CENÁRIO 1: Busca "PA QUADRADA" (o caso da imagem anterior)
    echo "--- Cenário 1: Busca 'PA QUADRADA' ---\n";
    $resultados1 = $executarBusca('PA QUADRADA');
    $nomes1 = array_map(function($p) { return $p->nome; }, $resultados1);
    
    if (!empty($nomes1) && strpos($nomes1[0], 'PÁ QUADRADA') !== false) {
        echo "✅ PASS: O 1º produto retornado é '{$nomes1[0]}'!\n";
    } else {
        echo "❌ FAIL: 'PÁ QUADRADA' não veio no 1º lugar. Veio: " . ($nomes1[0] ?? 'Vazio') . "\n";
        exit(1);
    }

    // CENÁRIO 2: Busca "PA " (COM ESPAÇO NO FINAL - Caso atual relatado pelo usuário!)
    echo "\n--- Cenário 2: Busca 'PA ' (com espaço final - Palavra Concluída) ---\n";
    $resultadosEspaco = $executarBusca('PA ');
    $nomesEspaco = array_map(function($p) { return $p->nome; }, $resultadosEspaco);
    
    // Todos os produtos retornados devem ser PÁ (não pode ter PALHA, PANO, PARAFUSO)
    $temPalhaOuPano = false;
    foreach ($nomesEspaco as $n) {
        if (strpos($n, 'PALHA') !== false || strpos($n, 'PANO') !== false || strpos($n, 'PARAFUSO') !== false) {
            $temPalhaOuPano = true;
        }
    }
    if (!$temPalhaOuPano && count($nomesEspaco) > 0) {
        echo "✅ PASS: Busca 'PA ' retornou APENAS produtos com a palavra completa PÁ (" . count($nomesEspaco) . " encontrados). PALHA/PANO/PARAFUSO foram excluídos!\n";
        foreach ($nomesEspaco as $n) {
            echo "   -> $n\n";
        }
    } else {
        echo "❌ FAIL: Busca 'PA ' ainda retornou produtos como PALHA, PANO ou PARAFUSO!\n";
        print_r($nomesEspaco);
        exit(1);
    }

    // CENÁRIO 3: Busca "PA" (SEM ESPAÇO)
    echo "\n--- Cenário 3: Busca 'PA' (sem espaço - digitação em andamento) ---\n";
    $resultados2 = $executarBusca('PA');
    $nomes2 = array_map(function($p) { return $p->nome; }, $resultados2);
    
    // As PÁS devem vir no topo (Rank 2), antes de PALHA/PANO/PARAFUSO (Rank 3)
    $primeirosDoisSaoPas = (strpos($nomes2[0], 'PÁ') === 0 && strpos($nomes2[1], 'PÁ') === 0);
    if ($primeirosDoisSaoPas) {
        echo "✅ PASS: Na busca 'PA', produtos com a palavra inteira 'PÁ' aparecem no topo antes de PALHA e PANO!\n";
        echo "   1º: {$nomes2[0]}\n";
        echo "   2º: {$nomes2[1]}\n";
    } else {
        echo "❌ FAIL: Pás não vieram no topo em 'PA'. 1º: {$nomes2[0]}, 2º: {$nomes2[1]}\n";
        exit(1);
    }

    // CENÁRIO 4: Busca com acento 'PÁ QUADRADA'
    echo "\n--- Cenário 4: Busca com acento 'PÁ QUADRADA' ---\n";
    $resultados3 = $executarBusca('PÁ QUADRADA');
    $nomes3 = array_map(function($p) { return $p->nome; }, $resultados3);
    if (!empty($nomes3) && strpos($nomes3[0], 'PÁ QUADRADA') !== false) {
        echo "✅ PASS: Busca com acento 'PÁ QUADRADA' retornou '{$nomes3[0]}' no 1º lugar!\n";
    } else {
        echo "❌ FAIL: Busca com acento falhou.\n";
        exit(1);
    }

    echo "\n========================================================\n";
    echo "🎉 TODOS OS CENÁRIOS PASSARAM COM 100% DE SUCESSO!\n";
    echo "========================================================\n";

} finally {
    // Limpeza
    if (!empty($idsCriados)) {
        Produto::deleteAll(['id' => $idsCriados]);
        echo "🧹 Produtos de teste removidos do banco.\n";
    }
}
