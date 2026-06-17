<?php
// proxy-asaas.php

// Define URLs base da Asaas
define('ASAAS_URL_PRODUCTION', 'https://api.asaas.com/v3');
define('ASAAS_URL_SANDBOX', 'https://sandbox.asaas.com/api/v3');

// Define content type como JSON para a resposta deste script
header('Content-Type: application/json');
// Permite requisições do localhost (ajuste se seu HTML estiver em outra origem)
header('Access-Control-Allow-Origin: *'); // Pode ser mais restritivo: 'http://localhost'
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS preflight (embora a chamada seja same-origin, é boa prática)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Garante que só aceitamos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Método não permitido. Use POST.']);
    exit;
}

// --- Lê os dados enviados pelo JavaScript ---
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Verifica se o JSON é válido
if (json_last_error() !== JSON_ERROR_NONE || !$input) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => 'Requisição JSON inválida para o proxy.', 'details' => json_last_error_msg()]);
    exit;
}

// Extrai os dados necessários com validação básica
$apiKey = $input['apiKey'] ?? null;
$environment = $input['environment'] ?? 'sandbox';
$method = strtoupper($input['method'] ?? 'GET');
$endpoint = $input['endpoint'] ?? null; // Endpoint é obrigatório
$bodyData = $input['bodyData'] ?? null; // Objeto/Array PHP ou null

// Valida campos obrigatórios
if (!$apiKey || !$endpoint) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => 'Dados incompletos enviados ao proxy (apiKey e endpoint são obrigatórios).']);
    exit;
}

// --- Monta a requisição cURL para a Asaas ---
$baseUrl = ($environment === 'production') ? ASAAS_URL_PRODUCTION : ASAAS_URL_SANDBOX;
$url = $baseUrl . $endpoint;

$ch = curl_init();

// Define a URL
curl_setopt($ch, CURLOPT_URL, $url);

// Define o método HTTP
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

// Define cabeçalhos
$headers = [
    'Accept: application/json',
    'Content-Type: application/json',
    'User-Agent: Proxy-PHP-cURL-Tester/1.0', // Adiciona User-Agent
    'access_token: ' . $apiKey // Chave API da Asaas
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Define corpo da requisição para POST/PUT/PATCH etc.
if (!in_array($method, ['GET', 'DELETE']) && $bodyData !== null) {
    $jsonData = json_encode($bodyData);
    // Verifica erro na codificação do corpo
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Erro ao codificar corpo da requisição para JSON no proxy.', 'details' => json_last_error_msg()]);
        curl_close($ch);
        exit;
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
}

// Configurações cURL importantes
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retorna a resposta como string em vez de imprimir
curl_setopt($ch, CURLOPT_TIMEOUT, 45); // Timeout aumentado para 45 segundos
curl_setopt($ch, CURLOPT_FAILONERROR, false); // Importante: NÃO falhar em erros HTTP (4xx, 5xx), queremos capturar a resposta
// curl_setopt($ch, CURLOPT_HEADER, true); // Descomente se precisar depurar os headers da resposta da Asaas

// --- Executa a requisição cURL ---
$responseBody = curl_exec($ch);
$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Pega o código HTTP retornado pela Asaas
$curlErrorNo = curl_errno($ch); // Pega o código de erro do cURL, se houver
$curlErrorMsg = curl_error($ch); // Pega a mensagem de erro do cURL, se houver

curl_close($ch); // Fecha a sessão cURL

// --- Processa a resposta ---
if ($curlErrorNo) {
    // Erro na execução do cURL (rede, timeout, DNS, SSL, etc.)
    http_response_code(502); // Bad Gateway - O proxy não conseguiu falar com a Asaas
    echo json_encode([
        'success' => false, // Indica falha do proxy
        'error' => 'Erro ao executar cURL para a Asaas.',
        'details' => "cURL Error ({$curlErrorNo}): {$curlErrorMsg}",
        'asaas_status' => null, // Não obtivemos status da Asaas
        'asaas_body' => null
    ]);
    exit;
}

// Chegou aqui, a comunicação cURL funcionou, vamos analisar a resposta da Asaas

// Tenta decodificar a resposta JSON da Asaas
$decodedBody = json_decode($responseBody, true);

// Verifica se o decode falhou E se a resposta não foi um sucesso (2xx)
// Se for HTML de login ou erro não-JSON, $decodedBody será null ou false
if (json_last_error() !== JSON_ERROR_NONE && ($httpStatusCode < 200 || $httpStatusCode >= 300)) {
    // A resposta da Asaas não foi JSON E foi um erro HTTP
    // Retorna o corpo bruto para o JS analisar (pode ser HTML de login ou erro texto)
    http_response_code(200); // A requisição DO PROXY funcionou, retornamos a resposta da Asaas
    echo json_encode([
        'success' => true, // O proxy funcionou
        'asaas_status' => $httpStatusCode, // O status de erro da Asaas
        'asaas_body' => $responseBody // Envia o corpo bruto (HTML, texto de erro, etc)
    ]);
    exit;
}

// Se o decode funcionou OU se foi sucesso (2xx) mesmo não sendo JSON (pouco provável na Asaas)
// Retorna o corpo decodificado (se era JSON) ou o corpo bruto (se não era JSON mas status 2xx)
http_response_code(200); // Requisição DO PROXY foi OK
echo json_encode([
    'success' => true, // O proxy funcionou
    'asaas_status' => $httpStatusCode, // O status real da Asaas
    'asaas_body' => (json_last_error() === JSON_ERROR_NONE) ? $decodedBody : $responseBody // Envia decodificado ou bruto
]);
exit;

?>