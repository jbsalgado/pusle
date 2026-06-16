<?php

// tests/verify_shopee_search.php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/console.php');
new yii\console\Application($config);

use app\modules\vendas\models\Produto;
use yii\db\Expression;

echo "🔍 Verificando Catálogo Estilo Shopee...\n";
echo "------------------------------------------\n";

$usuarioId = '5e449fee-4486-4536-a64f-74aed38a6987';

// 1. Testa se possui_grade está funcionando no model
$mestre = Produto::find()->where(['parent_id' => null, 'usuario_id' => $usuarioId])
    ->andWhere(['IN', 'id', (new \yii\db\Query())->select('parent_id')->from('prest_produtos')->where(['IS NOT', 'parent_id', null])])
    ->one();

if ($mestre) {
    echo "Teste Model: Produto '{$mestre->nome}' possui_grade: " . ($mestre->possuiGrade ? 'SIM ✅' : 'NÃO ❌') . "\n";
    
    // 2. Simula busca por característica de um filho
    $filho = Produto::find()->where(['parent_id' => $mestre->id])->one();
    if ($filho && $filho->cor) {
        $termo = trim($filho->cor);
        echo "Buscando por cor de uma variação: '{$termo}'\n";
        
        $query = Produto::find()
            ->where(['ativo' => true, 'usuario_id' => $usuarioId])
            ->andWhere(['parent_id' => null]);
            
        $termoLike = '%' . $termo . '%';
        $query->andWhere([
            'OR',
            ['ilike', new Expression('unaccent(nome)'), new Expression('unaccent(:p)', [':p' => $termoLike])],
            ['exists', (new \yii\db\Query())
                ->select(new Expression('1'))
                ->from('prest_produtos child')
                ->where('child.parent_id = prest_produtos.id')
                ->andWhere(['ilike', new Expression('unaccent(child.cor)'), new Expression('unaccent(:p)', [':p' => $termoLike])])
            ]
        ]);
        
        $resultado = $query->one();
        if ($resultado && $resultado->id === $mestre->id) {
            echo "✅ SUCESSO: Busca por cor do filho retornou o Produto Mestre.\n";
        } else {
            echo "❌ FALHA: Busca por característica do filho não retornou o mestre correspondente.\n";
            echo "ID Mestre Esperado: {$mestre->id}\n";
            echo "ID Retornado: " . ($resultado ? $resultado->id : 'NULL') . "\n";
        }
    }
} else {
    echo "ℹ️ Nenhum mestre com variações encontrado para o teste.\n";
}

echo "------------------------------------------\n";
echo "🏁 Verificação concluída.\n";
