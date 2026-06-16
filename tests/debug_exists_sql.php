<?php

// tests/debug_exists_sql.php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/console.php');
new yii\console\Application($config);

$db = Yii::$app->db;

$mestreId = 'd4143bf8-cfd0-455a-85dc-13a6c64f99e4';
$termo = '%CROMO%';

echo "Debug SQL EXISTS...\n";

$sql = "SELECT id, nome FROM prest_produtos WHERE id = :mestreId 
        AND EXISTS (
            SELECT 1 FROM prest_produtos child 
            WHERE child.parent_id = prest_produtos.id 
            AND child.cor ILIKE :termo
        )";

$result = $db->createCommand($sql, [
    ':mestreId' => $mestreId,
    ':termo' => $termo
])->queryOne();

if ($result) {
    echo "✅ SQL Funcionou! Retornou: " . $result['nome'] . "\n";
} else {
    echo "❌ SQL Não retornou nada.\n";
    
    // Testa sem o EXISTS para ver se o mestre existe e se o termo bate no filho
    $checkMestre = $db->createCommand("SELECT count(*) FROM prest_produtos WHERE id = :id", [':id' => $mestreId])->queryScalar();
    echo "Check Mestre ($mestreId): $checkMestre\n";
    
    $checkFilho = $db->createCommand("SELECT count(*) FROM prest_produtos WHERE parent_id = :id AND cor ILIKE :termo", [
        ':id' => $mestreId,
        ':termo' => $termo
    ])->queryScalar();
    echo "Check Filhos com termo ($termo): $checkFilho\n";
}
