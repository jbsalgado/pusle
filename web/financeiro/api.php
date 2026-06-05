<?php
// Define constants to bootstrap Yii2
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/web.php';
new yii\web\Application($config);

// Set header to JSON
header('Content-Type: application/json; charset=utf-8');

// Resolve logged user with fallback for local dev
$usuarioId = Yii::$app->user->id;
if (!$usuarioId) {
    try {
        $usuarioId = Yii::$app->db->createCommand("
            SELECT u.id 
            FROM prest_usuarios u 
            LEFT JOIN prest_contas_pagar c ON c.usuario_id = u.id 
            GROUP BY u.id 
            ORDER BY COUNT(c.id) DESC 
            LIMIT 1
        ")->queryScalar();
    } catch (\Exception $e) {
        echo json_encode(['error' => 'Erro ao conectar ao banco de dados: ' . $e->getMessage()]);
        exit;
    }
}

if (!$usuarioId) {
    echo json_encode(['error' => 'Nenhum usuário cadastrado no sistema.']);
    exit;
}

// Get filter parameters
$mes = isset($_GET['mes']) ? sprintf('%02d', max(1, min(12, intval($_GET['mes'])))) : date('m');
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
$mes_ano = "$ano-$mes";

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, intval($mes), $ano);

try {
    // ----------------------------------------------------
    // 1. QUERY REVENUE (RECEITAS)
    // ----------------------------------------------------
    $revenueSql = "
        SELECT 
            EXTRACT(DAY FROM p.data_pagamento) as dia,
            CASE 
                WHEN LOWER(f.nome) LIKE '%crédito%' OR LOWER(f.nome) LIKE '%credito%' THEN 'credito'
                WHEN LOWER(f.nome) LIKE '%débito%' OR LOWER(f.nome) LIKE '%debito%' OR f.tipo = 'BOLETO' THEN 'debito'
                WHEN f.tipo = 'PIX' OR f.tipo = 'PIX_ESTATICO' OR LOWER(f.nome) LIKE '%pix%' THEN 'pix'
                WHEN f.tipo = 'DINHEIRO' OR f.tipo = 'PAGAR_AO_ENTREGADOR' OR LOWER(f.nome) LIKE '%dinheiro%' OR LOWER(f.nome) LIKE '%entrega%' THEN 'cash'
                ELSE 'cash'
            END as categoria,
            SUM(COALESCE(NULLIF(p.valor_pago, 0), p.valor_parcela)) as total
        FROM prest_parcelas p
        LEFT JOIN prest_formas_pagamento f ON p.forma_pagamento_id = f.id
        WHERE p.data_pagamento IS NOT NULL 
          AND TO_CHAR(p.data_pagamento, 'YYYY-MM') = :mes_ano
          AND p.usuario_id = :uid
        GROUP BY dia, categoria
    ";
    
    $revenueRows = Yii::$app->db->createCommand($revenueSql, [
        ':mes_ano' => $mes_ano,
        ':uid' => $usuarioId
    ])->queryAll();

    // Initialize daily revenue matrix
    $receitasData = [];
    $receitasCategorias = ['debito', 'credito', 'pix', 'cash'];
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dayStr = sprintf('%02d', $d);
        $receitasData[$dayStr] = [
            'debito' => 0,
            'credito' => 0,
            'pix' => 0,
            'cash' => 0,
            'total' => 0
        ];
    }

    foreach ($revenueRows as $row) {
        $dayStr = sprintf('%02d', intval($row['dia']));
        $cat = $row['categoria'];
        $val = floatval($row['total']);
        if (isset($receitasData[$dayStr][$cat])) {
            $receitasData[$dayStr][$cat] = $val;
            $receitasData[$dayStr]['total'] += $val;
        }
    }

    // ----------------------------------------------------
    // 2. QUERY FIXED EXPENSES (DESPESAS FIXAS)
    // ----------------------------------------------------
    $fixedCats = Yii::$app->db->createCommand("
        SELECT nome FROM prest_tipos_despesa 
        WHERE grupo = 'FIXA' AND usuario_id = :uid AND ativo = TRUE
        ORDER BY nome
    ", [':uid' => $usuarioId])->queryColumn();

    $fixedSql = "
        SELECT 
            EXTRACT(DAY FROM COALESCE(p.data_pagamento, p.data_vencimento)) as dia,
            t.nome as categoria_nome,
            SUM(p.valor) as total
        FROM prest_contas_pagar p
        INNER JOIN prest_tipos_despesa t ON p.tipo_despesa_id = t.id
        WHERE t.grupo = 'FIXA'
          AND TO_CHAR(COALESCE(p.data_pagamento, p.data_vencimento), 'YYYY-MM') = :mes_ano
          AND p.usuario_id = :uid
          AND p.status <> 'CANCELADA'
        GROUP BY dia, t.nome
    ";
    
    $fixedRows = Yii::$app->db->createCommand($fixedSql, [
        ':mes_ano' => $mes_ano,
        ':uid' => $usuarioId
    ])->queryAll();

    $despesasFixasData = [];
    foreach ($fixedCats as $cat) {
        $despesasFixasData[$cat] = array_fill(1, $daysInMonth, 0);
    }
    // Totais por dia
    $despesasFixasTotaisDia = array_fill(1, $daysInMonth, 0);

    foreach ($fixedRows as $row) {
        $day = intval($row['dia']);
        $cat = $row['categoria_nome'];
        $val = floatval($row['total']);
        
        if (!isset($despesasFixasData[$cat])) {
            $despesasFixasData[$cat] = array_fill(1, $daysInMonth, 0);
            $fixedCats[] = $cat; // add category dynamic if not active but has records
        }
        $despesasFixasData[$cat][$day] = $val;
        $despesasFixasTotaisDia[$day] += $val;
    }

    // ----------------------------------------------------
    // 3. QUERY VARIABLE EXPENSES (DESPESAS VARIAVEIS)
    // ----------------------------------------------------
    $varCats = Yii::$app->db->createCommand("
        SELECT nome FROM prest_tipos_despesa 
        WHERE grupo = 'VARIAVEL' AND usuario_id = :uid AND ativo = TRUE
        ORDER BY nome
    ", [':uid' => $usuarioId])->queryColumn();

    $varSql = "
        SELECT 
            EXTRACT(DAY FROM COALESCE(p.data_pagamento, p.data_vencimento)) as dia,
            t.nome as categoria_nome,
            SUM(p.valor) as total
        FROM prest_contas_pagar p
        INNER JOIN prest_tipos_despesa t ON p.tipo_despesa_id = t.id
        WHERE t.grupo = 'VARIAVEL'
          AND TO_CHAR(COALESCE(p.data_pagamento, p.data_vencimento), 'YYYY-MM') = :mes_ano
          AND p.usuario_id = :uid
          AND p.status <> 'CANCELADA'
        GROUP BY dia, t.nome
    ";
    
    $varRows = Yii::$app->db->createCommand($varSql, [
        ':mes_ano' => $mes_ano,
        ':uid' => $usuarioId
    ])->queryAll();

    $despesasVariaveisData = [];
    foreach ($varCats as $cat) {
        $despesasVariaveisData[$cat] = array_fill(1, $daysInMonth, 0);
    }
    $despesasVariaveisTotaisDia = array_fill(1, $daysInMonth, 0);

    foreach ($varRows as $row) {
        $day = intval($row['dia']);
        $cat = $row['categoria_nome'];
        $val = floatval($row['total']);
        
        if (!isset($despesasVariaveisData[$cat])) {
            $despesasVariaveisData[$cat] = array_fill(1, $daysInMonth, 0);
            $varCats[] = $cat;
        }
        $despesasVariaveisData[$cat][$day] = $val;
        $despesasVariaveisTotaisDia[$day] += $val;
    }

    // ----------------------------------------------------
    // 4. QUERY INVENTORY PURCHASES (COMPRAS DE MERCADORIA)
    // ----------------------------------------------------
    $purchaseSql = "
        SELECT 
            EXTRACT(DAY FROM COALESCE(p.data_pagamento, p.data_vencimento)) as dia,
            COALESCE(f.nome_fantasia, f.razao_social, 'Fornecedor Não Identificado') as fornecedor_nome,
            SUM(p.valor) as total
        FROM prest_contas_pagar p
        LEFT JOIN prest_fornecedores f ON p.fornecedor_id = f.id
        INNER JOIN prest_tipos_despesa t ON p.tipo_despesa_id = t.id
        WHERE (t.grupo = 'MERCADORIA' OR p.compra_id IS NOT NULL)
          AND TO_CHAR(COALESCE(p.data_pagamento, p.data_vencimento), 'YYYY-MM') = :mes_ano
          AND p.usuario_id = :uid
          AND p.status <> 'CANCELADA'
        GROUP BY dia, fornecedor_nome
    ";
    
    $purchaseRows = Yii::$app->db->createCommand($purchaseSql, [
        ':mes_ano' => $mes_ano,
        ':uid' => $usuarioId
    ])->queryAll();

    // Get all vendors with records for this month
    $purchaseCats = [];
    foreach ($purchaseRows as $row) {
        $name = $row['fornecedor_nome'];
        if (!in_array($name, $purchaseCats)) {
            $purchaseCats[] = $name;
        }
    }
    sort($purchaseCats);

    $comprasData = [];
    foreach ($purchaseCats as $cat) {
        $comprasData[$cat] = array_fill(1, $daysInMonth, 0);
    }
    $comprasTotaisDia = array_fill(1, $daysInMonth, 0);

    foreach ($purchaseRows as $row) {
        $day = intval($row['dia']);
        $cat = $row['fornecedor_nome'];
        $val = floatval($row['total']);
        
        $comprasData[$cat][$day] = $val;
        $comprasTotaisDia[$day] += $val;
    }

    // ----------------------------------------------------
    // 5. COMPUTE SUMMARY STATS
    // ----------------------------------------------------
    $totalReceita = 0;
    foreach ($receitasData as $dData) {
        $totalReceita += $dData['total'];
    }

    $totalFixas = array_sum($despesasFixasTotaisDia);
    $totalVariaveis = array_sum($despesasVariaveisTotaisDia);
    $totalCompras = array_sum($comprasTotaisDia);
    
    $totalDespesas = $totalFixas + $totalVariaveis + $totalCompras;
    $saldoLiquido = $totalReceita - $totalDespesas;
    
    // Count days with revenue to calculate average (or divide by calendar days)
    $mediaDiaria = $daysInMonth > 0 ? ($totalReceita / $daysInMonth) : 0;

    // ----------------------------------------------------
    // 6. HISTORICAL CHART DATA (LAST 6 MONTHS)
    // ----------------------------------------------------
    $monthsList = [];
    for ($i = 5; $i >= 0; $i--) {
        $d = new DateTime("$ano-$mes-01");
        $d->modify("-$i months");
        $monthsList[] = $d->format('Y-m');
    }

    $startDate = $monthsList[0] . '-01';
    $endDObj = new DateTime($monthsList[5] . '-01');
    $endDObj->modify('last day of this month');
    $endDate = $endDObj->format('Y-m-d');

    // Historical revenues
    $histRevSql = "
        SELECT 
            TO_CHAR(p.data_pagamento, 'YYYY-MM') as mes_ano,
            SUM(COALESCE(NULLIF(p.valor_pago, 0), p.valor_parcela)) as total
        FROM prest_parcelas p
        WHERE p.data_pagamento IS NOT NULL
          AND p.usuario_id = :uid
          AND p.data_pagamento >= :start_date AND p.data_pagamento <= :end_date
        GROUP BY mes_ano
    ";
    
    $histRevRows = Yii::$app->db->createCommand($histRevSql, [
        ':uid' => $usuarioId,
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ])->queryAll();

    $histRevenues = array_fill_keys($monthsList, 0);
    foreach ($histRevRows as $row) {
        if (isset($histRevenues[$row['mes_ano']])) {
            $histRevenues[$row['mes_ano']] = floatval($row['total']);
        }
    }

    // Historical expenses by group
    $histExpSql = "
        SELECT 
            TO_CHAR(COALESCE(p.data_pagamento, p.data_vencimento), 'YYYY-MM') as mes_ano,
            t.grupo as grupo,
            SUM(p.valor) as total
        FROM prest_contas_pagar p
        INNER JOIN prest_tipos_despesa t ON p.tipo_despesa_id = t.id
        WHERE p.usuario_id = :uid
          AND p.status <> 'CANCELADA'
          AND COALESCE(p.data_pagamento, p.data_vencimento) >= :start_date 
          AND COALESCE(p.data_pagamento, p.data_vencimento) <= :end_date
        GROUP BY mes_ano, grupo
    ";

    $histExpRows = Yii::$app->db->createCommand($histExpSql, [
        ':uid' => $usuarioId,
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ])->queryAll();

    $histFixas = array_fill_keys($monthsList, 0);
    $histVariaveis = array_fill_keys($monthsList, 0);
    $histCompras = array_fill_keys($monthsList, 0);

    foreach ($histExpRows as $row) {
        $m = $row['mes_ano'];
        $g = $row['grupo'];
        $val = floatval($row['total']);
        if (isset($monthsList[$m]) || in_array($m, $monthsList)) {
            if ($g === 'FIXA') {
                $histFixas[$m] = $val;
            } elseif ($g === 'VARIAVEL') {
                $histVariaveis[$m] = $val;
            } elseif ($g === 'MERCADORIA') {
                $histCompras[$m] = $val;
            }
        }
    }

    // Map month names for labels (pt-BR)
    $monthNamesPt = [
        '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun',
        '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
    ];
    
    $historicalLabels = [];
    $historicalRevenueData = [];
    $historicalFixaData = [];
    $historicalVariavelData = [];
    $historicalCompraData = [];
    $historicalTotalExpenseData = [];
    $historicalAverageDailyData = [];

    foreach ($monthsList as $m) {
        $parts = explode('-', $m);
        $lbl = $monthNamesPt[$parts[1]] . '/' . substr($parts[0], 2);
        $historicalLabels[] = $lbl;
        
        $rev = $histRevenues[$m];
        $fix = $histFixas[$m];
        $var = $histVariaveis[$m];
        $comp = $histCompras[$m];
        $totExp = $fix + $var + $comp;
        
        $historicalRevenueData[] = $rev;
        $historicalFixaData[] = $fix;
        $historicalVariavelData[] = $var;
        $historicalCompraData[] = $comp;
        $historicalTotalExpenseData[] = $totExp;
        
        $mDays = cal_days_in_month(CAL_GREGORIAN, intval($parts[1]), intval($parts[0]));
        $historicalAverageDailyData[] = $mDays > 0 ? ($rev / $mDays) : 0;
    }

    // ----------------------------------------------------
    // 7. ASSEMBLE JSON RESPONSE
    // ----------------------------------------------------
    echo json_encode([
        'success' => true,
        'info' => [
            'mes' => $mes,
            'ano' => $ano,
            'dias' => $daysInMonth,
            'usuario' => Yii::$app->db->createCommand("SELECT nome FROM prest_usuarios WHERE id = :uid", [':uid' => $usuarioId])->queryScalar()
        ],
        'summary' => [
            'receita_total' => $totalReceita,
            'despesa_total' => $totalDespesas,
            'saldo_liquido' => $saldoLiquido,
            'media_diaria' => $mediaDiaria,
            'custo_fixo_total' => $totalFixas,
            'custo_variavel_total' => $totalVariaveis,
            'custo_mercadoria_total' => $totalCompras
        ],
        'receitas' => [
            'categorias' => $receitasCategorias,
            'dados' => $receitasData
        ],
        'despesas_fixas' => [
            'categorias' => $fixedCats,
            'dados' => $despesasFixasData,
            'totais_dia' => $despesasFixasTotaisDia
        ],
        'despesas_variaveis' => [
            'categorias' => $varCats,
            'dados' => $despesasVariaveisData,
            'totais_dia' => $despesasVariaveisTotaisDia
        ],
        'compras' => [
            'categorias' => $purchaseCats,
            'dados' => $comprasData,
            'totais_dia' => $comprasTotaisDia
        ],
        'historical' => [
            'labels' => $historicalLabels,
            'receita' => $historicalRevenueData,
            'custo_fixo' => $historicalFixaData,
            'custo_variavel' => $historicalVariavelData,
            'custo_mercadoria' => $historicalCompraData,
            'despesa_total' => $historicalTotalExpenseData,
            'media_diaria' => $historicalAverageDailyData
        ]
    ]);

} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno ao processar dados do banco: ' . $e->getMessage() . "\n" . $e->getTraceAsString()
    ]);
}
