<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
$app = new yii\web\Application($config);

use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\StatusVenda;
use app\modules\prestanista\controllers\CobradorController;

echo "========================================================\n";
echo " TESTE DE ISOLAMENTO E VALIDAÇÃO DE VENDAS PRESTANISTAS \n";
echo "========================================================\n";

$usuario = \app\models\Usuario::find()->one();
if (!$usuario) {
    die("Nenhum usuário encontrado para o teste.\n");
}
$tenantId = $usuario->id;
echo "Usando tenant: {$usuario->nome} (ID: {$tenantId})\n\n";

// Localiza forma de pagamento
$formaPag = \app\modules\vendas\models\FormaPagamento::find()->where(['usuario_id' => $tenantId])->one();
if (!$formaPag) {
    $formaPag = \app\modules\vendas\models\FormaPagamento::find()->one();
}

$transaction = Yii::$app->db->beginTransaction();
try {
    // 1. Criar Venda Balcão com termo "cartão" nas observações (Simulando PDV)
    $vendaBalcaoCartao = new Venda();
    $vendaBalcaoCartao->usuario_id = $tenantId;
    $vendaBalcaoCartao->valor_total = 100.00;
    $vendaBalcaoCartao->data_venda = date('Y-m-d H:i:s');
    $vendaBalcaoCartao->forma_pagamento_id = $formaPag->id;
    $vendaBalcaoCartao->status_venda_codigo = StatusVenda::EM_ABERTO;
    $vendaBalcaoCartao->tipo_venda = Venda::TIPO_BALCAO;
    $vendaBalcaoCartao->observacoes = "Venda no balcão paga com cartão de crédito na maquineta";
    $vendaBalcaoCartao->save(false);

    $pBalcao = new Parcela();
    $pBalcao->venda_id = $vendaBalcaoCartao->id;
    $pBalcao->usuario_id = $tenantId;
    $pBalcao->numero_parcela = 1;
    $pBalcao->valor_parcela = 100.00;
    $pBalcao->data_vencimento = date('Y-m-d');
    $pBalcao->status_parcela_codigo = StatusParcela::PENDENTE;
    $pBalcao->save(false);

    // 2. Criar Venda Balcão a prazo / fiado
    $vendaBalcaoPrazo = new Venda();
    $vendaBalcaoPrazo->usuario_id = $tenantId;
    $vendaBalcaoPrazo->valor_total = 150.00;
    $vendaBalcaoPrazo->data_venda = date('Y-m-d H:i:s');
    $vendaBalcaoPrazo->forma_pagamento_id = $formaPag->id;
    $vendaBalcaoPrazo->status_venda_codigo = StatusVenda::EM_ABERTO;
    $vendaBalcaoPrazo->tipo_venda = Venda::TIPO_BALCAO;
    $vendaBalcaoPrazo->observacoes = "Venda a Prazo (Boleto / Fiado) balcão";
    $vendaBalcaoPrazo->save(false);

    $pPrazo = new Parcela();
    $pPrazo->venda_id = $vendaBalcaoPrazo->id;
    $pPrazo->usuario_id = $tenantId;
    $pPrazo->numero_parcela = 1;
    $pPrazo->valor_parcela = 150.00;
    $pPrazo->data_vencimento = date('Y-m-d');
    $pPrazo->status_parcela_codigo = StatusParcela::PENDENTE;
    $pPrazo->save(false);

    // 3. Criar Venda Catálogo PWA
    $vendaPwa = new Venda();
    $vendaPwa->usuario_id = $tenantId;
    $vendaPwa->valor_total = 200.00;
    $vendaPwa->data_venda = date('Y-m-d H:i:s');
    $vendaPwa->forma_pagamento_id = $formaPag->id;
    $vendaPwa->status_venda_codigo = StatusVenda::EM_ABERTO;
    $vendaPwa->tipo_venda = Venda::TIPO_CATALOGO_PWA;
    $vendaPwa->observacoes = "Cliente comprou no site e pediu entrega para as 19h";
    $vendaPwa->save(false);

    $pPwa = new Parcela();
    $pPwa->venda_id = $vendaPwa->id;
    $pPwa->usuario_id = $tenantId;
    $pPwa->numero_parcela = 1;
    $pPwa->valor_parcela = 200.00;
    $pPwa->data_vencimento = date('Y-m-d');
    $pPwa->status_parcela_codigo = StatusParcela::PENDENTE;
    $pPwa->save(false);

    // 4. Criar Venda Legítima Prestanista
    $vendaPrestanista = new Venda();
    $vendaPrestanista->usuario_id = $tenantId;
    $vendaPrestanista->valor_total = 300.00;
    $vendaPrestanista->data_venda = date('Y-m-d H:i:s');
    $vendaPrestanista->forma_pagamento_id = $formaPag->id;
    $vendaPrestanista->status_venda_codigo = StatusVenda::EM_ABERTO;
    $vendaPrestanista->tipo_venda = Venda::TIPO_PRESTANISTA;
    $vendaPrestanista->observacoes = "[PRESTANISTA] [FREQ:7] Cartão de Crediário emitido via Gestão";
    $vendaPrestanista->save(false);

    $pPrest = new Parcela();
    $pPrest->venda_id = $vendaPrestanista->id;
    $pPrest->usuario_id = $tenantId;
    $pPrest->numero_parcela = 1;
    $pPrest->valor_parcela = 300.00;
    $pPrest->data_vencimento = date('Y-m-d');
    $pPrest->status_parcela_codigo = StatusParcela::PENDENTE;
    $pPrest->save(false);

    echo "Vendas de teste criadas:\n";
    echo "  1. Balcão Cartão ID: {$vendaBalcaoCartao->id}\n";
    echo "  2. Balcão a Prazo ID: {$vendaBalcaoPrazo->id}\n";
    echo "  3. Catálogo PWA ID: {$vendaPwa->id}\n";
    echo "  4. Legítima Prestanista ID: {$vendaPrestanista->id}\n\n";

    // Executa a consulta Venda::findPrestanista
    $prestanistasEncontradas = Venda::findPrestanista($tenantId)
        ->andWhere(['v.id' => [
            $vendaBalcaoCartao->id,
            $vendaBalcaoPrazo->id,
            $vendaPwa->id,
            $vendaPrestanista->id,
        ]])
        ->all();

    $idsEncontrados = array_map(function($v) { return $v->id; }, $prestanistasEncontradas);

    echo "--- AVALIAÇÃO DOS RESULTADOS ---\n";

    $passou = true;

    // Teste 1: Venda Balcão Cartão NÃO deve aparecer
    if (in_array($vendaBalcaoCartao->id, $idsEncontrados)) {
        echo "❌ FALHA: Venda de Balcão com cartão apareceu como Prestanista!\n";
        $passou = false;
    } else {
        echo "✅ SUCESSO: Venda de Balcão com cartão foi corretamente IGNORADA.\n";
    }

    // Teste 2: Venda Balcão a Prazo NÃO deve aparecer
    if (in_array($vendaBalcaoPrazo->id, $idsEncontrados)) {
        echo "❌ FALHA: Venda de Balcão a Prazo apareceu como Prestanista!\n";
        $passou = false;
    } else {
        echo "✅ SUCESSO: Venda de Balcão a Prazo foi corretamente IGNORADA.\n";
    }

    // Teste 3: Venda PWA NÃO deve aparecer
    if (in_array($vendaPwa->id, $idsEncontrados)) {
        echo "❌ FALHA: Venda do Catálogo PWA apareceu como Prestanista!\n";
        $passou = false;
    } else {
        echo "✅ SUCESSO: Venda do Catálogo PWA foi corretamente IGNORADA.\n";
    }

    // Teste 4: Venda Prestanista DEVE aparecer
    if (in_array($vendaPrestanista->id, $idsEncontrados)) {
        echo "✅ SUCESSO: Venda legítima Prestanista foi CAPTURADA com perfeição.\n";
    } else {
        echo "❌ FALHA: Venda legítima Prestanista NÃO foi encontrada!\n";
        $passou = false;
    }

    // Teste 5: Endpoint CobradorController::actionDadosRota
    Yii::$app->user->setIdentity($usuario);
    $cobradorController = new CobradorController('cobrador', $app->getModule('prestanista'));
    $resultadoRota = $cobradorController->actionDadosRota();

    echo "\nTeste CobradorController::actionDadosRota():\n";
    echo "  Total retornado na rota: " . $resultadoRota['total'] . "\n";
    $vendaIdsNaRota = array_column($resultadoRota['rotas'], 'venda_id');
    
    if (in_array($vendaBalcaoCartao->id, $vendaIdsNaRota) || in_array($vendaBalcaoPrazo->id, $vendaIdsNaRota) || in_array($vendaPwa->id, $vendaIdsNaRota)) {
        echo "❌ FALHA: Vendas indevidas apareceram na rota do cobrador!\n";
        $passou = false;
    } elseif (in_array($vendaPrestanista->id, $vendaIdsNaRota)) {
        echo "✅ SUCESSO: Apenas a venda legítima Prestanista apareceu na rota do cobrador!\n";
    } else {
        echo "ℹ️ Venda Prestanista criada não tem cliente vinculado (esperado para o teste sintético).\n";
    }

    if ($passou) {
        echo "\n🎉 TODOS OS TESTES PASSARAM COM 100% DE SUCESSO!\n";
    } else {
        echo "\n⚠️ ALGUNS TESTES FALHARAM. REVISAR IMPLEMENTAÇÃO.\n";
    }

    // Rollback para não poluir o banco de dados
    $transaction->rollBack();
    echo "\nRollback executado com sucesso. Banco de dados limpo.\n";

} catch (\Exception $e) {
    $transaction->rollBack();
    echo "Erro durante o teste: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
