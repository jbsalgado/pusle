<?php

/**
 * Script de teste para NFe/NFCe
 * 
 * Uso: php test_nfe.php
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../config/bootstrap.php';

use app\components\nfe\NFeBuilder;
use app\components\nfe\NFeService;
use app\modules\vendas\models\Venda;

echo "🧪 Teste de NFe/NFCe - Sistema Pulse\n";
echo str_repeat("=", 70) . "\n\n";

// Teste 1: Verificar configuração
echo "1️⃣  Verificando configuração...\n";
echo str_repeat("-", 70) . "\n";

$config = Yii::$app->params['nfe'];

if (!$config) {
    echo "❌ Configuração NFe não encontrada em params.php\n";
    exit(1);
}

echo "✅ Configuração carregada\n";
echo "   Ambiente: " . $config['ambiente'] . "\n";
echo "   CNPJ: " . $config['emitente']['cnpj'] . "\n";
echo "   UF: " . $config['emitente']['endereco']['uf'] . "\n\n";

// Teste 2: Verificar certificado
echo "2️⃣  Verificando certificado digital...\n";
echo str_repeat("-", 70) . "\n";

if (!file_exists($config['certificado']['path'])) {
    echo "❌ Certificado não encontrado: " . $config['certificado']['path'] . "\n";
    exit(1);
}

echo "✅ Certificado encontrado\n";
echo "   Path: " . $config['certificado']['path'] . "\n\n";

// Teste 3: Inicializar NFeService
echo "3️⃣  Inicializando NFeService...\n";
echo str_repeat("-", 70) . "\n";

try {
    $service = new NFeService();
    echo "✅ NFeService inicializado com sucesso\n\n";
} catch (\Exception $e) {
    echo "❌ Erro ao inicializar NFeService:\n";
    echo "   " . $e->getMessage() . "\n";
    exit(1);
}

// Teste 4: Consultar status SEFAZ
echo "4️⃣  Consultando status do serviço SEFAZ...\n";
echo str_repeat("-", 70) . "\n";

try {
    $status = $service->consultarStatus('65');

    if ($status['success']) {
        echo "✅ SEFAZ em operação\n";
        echo "   Mensagem: " . $status['mensagem'] . "\n";
        echo "   Código: " . $status['codigo'] . "\n\n";
    } else {
        echo "⚠️  SEFAZ indisponível\n";
        echo "   Mensagem: " . $status['mensagem'] . "\n";
        echo "   Código: " . ($status['codigo'] ?? 'N/A') . "\n\n";
    }
} catch (\Exception $e) {
    echo "❌ Erro ao consultar status:\n";
    echo "   " . $e->getMessage() . "\n\n";
}

// Teste 5: Gerar XML de teste (se houver venda)
echo "5️⃣  Testando geração de XML...\n";
echo str_repeat("-", 70) . "\n";

// Buscar uma venda para teste
$venda = Venda::find()
    ->with(['itens.produto', 'cliente'])
    ->where(['IS NOT', 'cliente_id', null])
    ->one();

if (!$venda) {
    echo "⚠️  Nenhuma venda encontrada para teste\n";
    echo "   Crie uma venda com cliente para testar a geração de XML\n\n";
} else {
    echo "✅ Venda encontrada para teste\n";
    echo "   ID: " . $venda->id . "\n";
    echo "   Cliente: " . ($venda->cliente->nome ?? 'N/A') . "\n";
    echo "   Valor: R$ " . number_format($venda->valor_total, 2, ',', '.') . "\n";
    echo "   Itens: " . count($venda->itens) . "\n\n";

    try {
        echo "   Gerando XML NFCe...\n";
        $xml = NFeBuilder::buildFromVenda($venda, '65');

        echo "✅ XML gerado com sucesso!\n";
        echo "   Tamanho: " . strlen($xml) . " bytes\n";

        // Salvar XML para inspeção
        $xmlPath = __DIR__ . '/../../runtime/nfe_teste.xml';
        file_put_contents($xmlPath, $xml);
        echo "   Salvo em: " . $xmlPath . "\n\n";

        // Validar estrutura básica
        if (strpos($xml, '<NFe') !== false) {
            echo "✅ Estrutura XML válida\n";
        } else {
            echo "⚠️  Estrutura XML pode estar incorreta\n";
        }

        echo "\n";
    } catch (\Exception $e) {
        echo "❌ Erro ao gerar XML:\n";
        echo "   " . $e->getMessage() . "\n";
        echo "   Arquivo: " . $e->getFile() . "\n";
        echo "   Linha: " . $e->getLine() . "\n\n";
    }
}

// Resumo
echo str_repeat("=", 70) . "\n";
echo "📊 Resumo dos Testes\n";
echo str_repeat("=", 70) . "\n";
echo "✅ Configuração: OK\n";
echo "✅ Certificado: OK\n";
echo "✅ NFeService: OK\n";
echo ($status['success'] ?? false) ? "✅" : "⚠️ " . " SEFAZ: " . ($status['mensagem'] ?? 'N/A') . "\n";
echo isset($xml) ? "✅" : "⚠️ " . " Geração XML: " . (isset($xml) ? "OK" : "Pendente") . "\n";
echo "\n";

echo "💡 Próximos passos:\n";
echo "   1. Revisar XML gerado em runtime/nfe_teste.xml\n";
echo "   2. Validar dados do emitente em config/params.php\n";
echo "   3. Obter CSC para NFCe no portal SEFAZ PE\n";
echo "   4. Testar transmissão em homologação\n";
echo "\n";
