<?php

// tests/apply_ddl_fix.php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/console.php');
new yii\console\Application($config);

$db = Yii::$app->db;

echo "🚀 Iniciando Correção de Banco de Dados (DDL)...\n";

try {
    // 1. Altera Defaults de Coluna
    $db->createCommand("ALTER TABLE prest_produtos ALTER COLUMN estoque_minimo SET DEFAULT 0")->execute();
    echo "✅ Default estoque_minimo -> 0\n";
    
    $db->createCommand("ALTER TABLE prest_produtos ALTER COLUMN ponto_corte SET DEFAULT 0")->execute();
    echo "✅ Default ponto_corte -> 0\n";

    // 2. Garante que registros existentes não quebrem se precisarmos regenerar constraints
    // (Opcional, mas seguro se houver registros antigos inválidos)
    $db->createCommand("UPDATE prest_produtos SET ponto_corte = estoque_minimo WHERE ponto_corte < estoque_minimo")->execute();
    echo "✅ Registros existentes sincronizados.\n";

    echo "🏁 DDL Aplicado com sucesso.\n";
} catch (\Exception $e) {
    echo "❌ Erro ao aplicar DDL: " . $e->getMessage() . "\n";
    exit(1);
}
