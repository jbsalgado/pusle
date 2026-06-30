<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
(new yii\web\Application($config));

use app\modules\vendas\models\Compra;
use app\modules\vendas\models\Fornecedor;
use app\modules\contas_pagar\models\ContaPagar;
use app\models\Usuario;

// Mock identity to avoid session/headers issues
$user = Usuario::find()->one();
if (!$user) {
    echo "[FALHA] Nenhum usuário cadastrado no banco.\n";
    exit(1);
}

Yii::$app->user->setIdentity($user);

echo "--- Iniciando Teste de Parcelamento Customizado de Compras ---\n\n";
echo "Usuário selecionado: " . $user->email . " (ID: " . $user->id . ")\n";

$fornecedor = Fornecedor::find()->where(['usuario_id' => $user->id])->one();
if (!$fornecedor) {
    // Cria um fornecedor fictício para o teste
    $fornecedor = new Fornecedor();
    $fornecedor->usuario_id = $user->id;
    $fornecedor->razao_social = "Fornecedor Teste Ltda";
    $fornecedor->nome_fantasia = "Fornecedor Teste";
    $fornecedor->cnpj = "00000000000000";
    if (!$fornecedor->save()) {
        echo "[FALHA] Erro ao cadastrar fornecedor de teste: " . json_encode($fornecedor->errors) . "\n";
        exit(1);
    }
}
echo "Fornecedor selecionado: " . $fornecedor->nome_fantasia . " (ID: " . $fornecedor->id . ")\n";

// Transação para não poluir o banco definitivo
$transaction = Yii::$app->db->beginTransaction();

try {
    // 2. Testar criação com parcelas manuais
    echo "\nTestando geração de parcelas manuais...\n";
    $compra = new Compra();
    $compra->usuario_id = $user->id;
    $compra->fornecedor_id = $fornecedor->id;
    $compra->data_compra = date('Y-m-d');
    $compra->status_compra = Compra::STATUS_PENDENTE;
    $compra->valor_total = 1500.00;
    $compra->valor_frete = 100.00;
    $compra->valor_desconto = 50.00;
    
    // Total líquido esperado = 1500.00 + 100.00 - 50.00 = 1550.00

    if (!$compra->save()) {
        throw new \Exception("Erro ao salvar compra: " . json_encode($compra->errors));
    }
    echo "Compra criada: ID " . $compra->id . "\n";

    $parcelasManuais = [
        ['data_vencimento' => date('Y-m-d', strtotime('+15 days')), 'valor' => '550,00'],
        ['data_vencimento' => date('Y-m-d', strtotime('+30 days')), 'valor' => '1000.00']
    ];

    $resultado = $compra->gerarContasPagar(false, $parcelasManuais);
    
    if (!$resultado['success']) {
        throw new \Exception("Erro no gerarContasPagar: " . ($resultado['message'] ?? '') . json_encode($resultado['erros']));
    }

    echo "[OK] Contas a pagar geradas com sucesso. Quantidade: " . $resultado['contas_criadas'] . "\n";

    // Validar as contas criadas
    $contas = ContaPagar::find()->where(['compra_id' => $compra->id])->orderBy('data_vencimento')->all();
    if (count($contas) !== 2) {
        throw new \Exception("Esperava 2 contas, mas encontrou " . count($contas));
    }

    if (floatval($contas[0]->valor) !== 550.00) {
        throw new \Exception("Valor da parcela 1 incorreto. Esperado 550.00, obtido " . $contas[0]->valor);
    }
    if (floatval($contas[1]->valor) !== 1000.00) {
        throw new \Exception("Valor da parcela 2 incorreto. Esperado 1000.00, obtido " . $contas[1]->valor);
    }

    echo "[OK] Parcelas e valores batem perfeitamente.\n";

    // 3. Testar regeneração
    echo "\nTestando regeneração de parcelas...\n";
    $novasParcelas = [
        ['data_vencimento' => date('Y-m-d', strtotime('+10 days')), 'valor' => '300,00'],
        ['data_vencimento' => date('Y-m-d', strtotime('+20 days')), 'valor' => '500,00'],
        ['data_vencimento' => date('Y-m-d', strtotime('+30 days')), 'valor' => '750,00']
    ];

    $resultadoRegen = $compra->gerarContasPagar(true, $novasParcelas);
    if (!$resultadoRegen['success']) {
        throw new \Exception("Erro na regeneração: " . json_encode($resultadoRegen['erros']));
    }

    $contasNovas = ContaPagar::find()->where(['compra_id' => $compra->id])->orderBy('data_vencimento')->all();
    if (count($contasNovas) !== 3) {
        throw new \Exception("Esperava 3 contas após regenerar, encontrou " . count($contasNovas));
    }

    echo "[OK] Regeneração gerou exatamente 3 novas contas.\n";
    echo "Parcela 1: " . $contasNovas[0]->data_vencimento . " | Valor: " . $contasNovas[0]->valor . "\n";
    echo "Parcela 2: " . $contasNovas[1]->data_vencimento . " | Valor: " . $contasNovas[1]->valor . "\n";
    echo "Parcela 3: " . $contasNovas[2]->data_vencimento . " | Valor: " . $contasNovas[2]->valor . "\n";

    $transaction->rollBack();
    echo "\n--- Todos os testes do modelo passaram com sucesso! (Rollback executado) ---\n";
} catch (\Exception $e) {
    $transaction->rollBack();
    echo "\n[FALHA] Teste abortado por erro: " . $e->getMessage() . "\n";
    exit(1);
}
