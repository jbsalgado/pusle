<?php

// tests/check_db_schema.php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/console.php');
new yii\console\Application($config);

$db = Yii::$app->db;
$table = $db->getTableSchema('prest_produtos');

echo "🔍 Esquema da Tabela: prest_produtos\n";
echo "------------------------------------\n";

foreach ($table->columns as $column) {
    if (in_array($column->name, ['estoque_minimo', 'ponto_corte'])) {
        echo "Coluna: {$column->name}\n";
        echo "  Tipo: {$column->dbType}\n";
        echo "  Default: " . json_encode($column->defaultValue) . "\n";
        echo "  Allow Null: " . ($column->allowNull ? 'Yes' : 'No') . "\n\n";
    }
}

// Busca constraints de check no Postgres
$sql = "SELECT conname, pg_get_constraintdef(oid) FROM pg_constraint WHERE conrelid = 'prest_produtos'::regclass AND contype = 'c'";
$checks = $db->createCommand($sql)->queryAll();

echo "🔧 Restrições de CHECK:\n";
foreach ($checks as $check) {
    echo "  - {$check['conname']}: {$check['pg_get_constraintdef']}\n";
}
