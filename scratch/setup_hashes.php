<?php
require 'vendor/autoload.php';
require 'vendor/yiisoft/yii2/Yii.php';
$config = require 'config/web.php';
new yii\web\Application($config);

$transaction = Yii::$app->db->beginTransaction();
try {
    echo "Adicionando coluna 'hash'...\n";
    Yii::$app->db->createCommand("ALTER TABLE orcamentos ADD COLUMN IF NOT EXISTS hash VARCHAR(64) UNIQUE")->execute();
    
    echo "Gerando hashes para registros existentes...\n";
    $orcamentos = Yii::$app->db->createCommand("SELECT id FROM orcamentos WHERE hash IS NULL")->queryAll();
    
    foreach ($orcamentos as $orc) {
        $hash = bin2hex(random_bytes(16));
        Yii::$app->db->createCommand()->update('orcamentos', ['hash' => $hash], ['id' => $orc['id']])->execute();
        echo "ID {$orc['id']} -> Hash: {$hash}\n";
    }
    
    $transaction->commit();
    echo "Concluído com sucesso.\n";
} catch (\Exception $e) {
    $transaction->rollBack();
    echo "ERRO: " . $e->getMessage() . "\n";
}
