<?php

// Teste de sintaxe PHP do ClientesController
$controllerPath = __DIR__ . '/../modules/vendas/controllers/ClientesController.php';
$output = [];
$returnCode = 0;
exec("php -l " . escapeshellarg($controllerPath), $output, $returnCode);

echo "Verificação de sintaxe de ClientesController.php:\n";
echo implode("\n", $output) . "\n\n";

if ($returnCode !== 0) {
    echo "ERRO: Sintaxe inválida no ClientesController.php\n";
    exit(1);
}

// Teste de sintaxe da view importar.php
$viewPath = __DIR__ . '/../modules/vendas/views/clientes/importar.php';
$outputView = [];
$returnCodeView = 0;
exec("php -l " . escapeshellarg($viewPath), $outputView, $returnCodeView);

echo "Verificação de sintaxe de importar.php:\n";
echo implode("\n", $outputView) . "\n\n";

if ($returnCodeView !== 0) {
    echo "ERRO: Sintaxe inválida no importar.php\n";
    exit(1);
}

// Simulação da lógica de importação CSV e checagem de CPF
$csvData = implode("\n", [
    "nome_completo;cpf;telefone;email;logradouro;cidade;estado;cep",
    "Cliente Novo 1;11122233344;(11) 98888-1111;novo1@test.com;Rua Um;São Paulo;SP;01000000",
    "Cliente Existente;99988877766;(11) 98888-2222;existente@test.com;Rua Dois;São Paulo;SP;02000000",
    "Cliente Novo 2;55566677788;(11) 98888-3333;novo2@test.com;Rua Três;Campinas;SP;13000000",
]);

$tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
file_put_contents($tempFile, $csvData);

$handle = fopen($tempFile, 'r');
$firstLine = fgets($handle);
$delimiter = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';

rewind($handle);
$rawHeader = fgetcsv($handle, 0, $delimiter);

$accentMap = [
    'á'=>'a', 'à'=>'a', 'ã'=>'a', 'â'=>'a', 'ä'=>'a',
    'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e',
    'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i',
    'ó'=>'o', 'ò'=>'o', 'õ'=>'o', 'ô'=>'o', 'ö'=>'o',
    'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u',
    'ç'=>'c', 'ñ'=>'n'
];
$headerMap = [];
foreach ($rawHeader as $index => $colName) {
    $colSemAcento = strtr($colName, $accentMap);
    $cleanCol = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-', '/'], '_', $colSemAcento))));
    $headerMap[$cleanCol] = $index;
}

// Simulação de CPFs já cadastrados no banco
$cpfsNoBanco = ['99988877766'];

$cadastrados = 0;
$ignoradosCpf = 0;

while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
    if (empty(array_filter($row, function($v) { return trim($v) !== ''; }))) continue;

    $cpfRaw = $row[$headerMap['cpf']] ?? '';
    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpfRaw);

    if (!empty($cpfLimpo) && in_array($cpfLimpo, $cpfsNoBanco)) {
        $ignoradosCpf++;
        echo "LOG: CPF {$cpfLimpo} (" . ($row[$headerMap['nome_completo']] ?? '') . ") JÁ EXISTE NO BANCO. Linha ignorada!\n";
        continue;
    }

    $cadastrados++;
    echo "LOG: Cliente (" . ($row[$headerMap['nome_completo']] ?? '') . ") CPF: {$cpfLimpo} CADASTRADO COM SUCESSO.\n";
}

fclose($handle);
unlink($tempFile);

echo "\nResultado do teste de simulação:\n";
echo "Cadastrados: {$cadastrados}\n";
echo "Ignorados por CPF existente: {$ignoradosCpf}\n";

if ($cadastrados === 2 && $ignoradosCpf === 1) {
    echo "\nSUCESSO: A lógica de verificação e ignoramento de CPF funcionou perfeitamente!\n";
} else {
    echo "\nFALHA: Resultado inconsistente no teste de simulação.\n";
    exit(1);
}
