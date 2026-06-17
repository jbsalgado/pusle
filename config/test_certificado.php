<?php

/**
 * Script de teste para validar certificado digital
 * 
 * Uso: php test_certificado.php
 */

require __DIR__ . '/../vendor/autoload.php';

use NFePHP\Common\Certificate;

echo "🔐 Teste de Certificado Digital - Only-code\n";
echo str_repeat("=", 60) . "\n\n";

$certificadoPath = __DIR__ . '/certificados/only-code.pfx';
$senha = 'onlycode2026';

// Verificar se arquivo existe
if (!file_exists($certificadoPath)) {
    echo "❌ Erro: Certificado não encontrado em:\n";
    echo "   {$certificadoPath}\n";
    exit(1);
}

try {
    // Carregar certificado
    $content = file_get_contents($certificadoPath);
    $certificado = Certificate::readPfx($content, $senha);

    echo "✅ Certificado carregado com sucesso!\n\n";

    // Informações do certificado
    echo "📋 Informações do Certificado:\n";
    echo str_repeat("-", 60) . "\n";
    echo sprintf("   %-20s %s\n", "CNPJ:", $certificado->getCnpj());
    echo sprintf("   %-20s %s\n", "Razão Social:", $certificado->getCompanyName());
    echo sprintf("   %-20s %s\n", "Válido de:", $certificado->getValidFrom()->format('d/m/Y H:i:s'));
    echo sprintf("   %-20s %s\n", "Válido até:", $certificado->getValidTo()->format('d/m/Y H:i:s'));

    // Calcular dias restantes
    $hoje = new DateTime();
    $validade = $certificado->getValidTo();
    $diasRestantes = $hoje->diff($validade)->days;

    if ($validade < $hoje) {
        echo sprintf("   %-20s %s\n", "Status:", "❌ EXPIRADO");
    } else {
        echo sprintf("   %-20s %s (%d dias)\n", "Status:", "✅ VÁLIDO", $diasRestantes);
    }

    echo "\n";

    // Informações técnicas
    echo "🔧 Informações Técnicas:\n";
    echo str_repeat("-", 60) . "\n";
    echo sprintf("   %-20s %s\n", "Tipo:", "Auto-assinado (Teste)");
    echo sprintf("   %-20s %s\n", "Algoritmo:", "RSA");
    echo sprintf("   %-20s %s\n", "Tamanho da Chave:", "2048 bits");
    echo sprintf("   %-20s %s\n", "Formato:", "PFX/PKCS#12");

    echo "\n";

    // Avisos
    echo "⚠️  Avisos Importantes:\n";
    echo str_repeat("-", 60) . "\n";
    echo "   • Este é um certificado de TESTE (auto-assinado)\n";
    echo "   • NÃO funciona no ambiente de homologação da SEFAZ\n";
    echo "   • Use apenas para desenvolvimento local\n";
    echo "   • Para homologação, obtenha certificado oficial\n";

    echo "\n";
    echo "✅ Teste concluído com sucesso!\n";
} catch (\Exception $e) {
    echo "❌ Erro ao carregar certificado:\n\n";
    echo "   Mensagem: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";

    echo "\n";
    echo "💡 Possíveis causas:\n";
    echo "   • Senha incorreta (atual: 'onlycode2026')\n";
    echo "   • Arquivo corrompido\n";
    echo "   • Biblioteca NFePHP não instalada\n";

    exit(1);
}
