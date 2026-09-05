<?php
/**
 * Test script for catalogo_ativo (Modo Implantação)
 */

$baseUrl = 'https://catalogos.oncode.app.br/index.php';
$slug = 'andrea-braga-acessoris-e-vendas-em-geral';
$usuarioId = '8e64045f-1d6e-429d-8112-97df69319cd0';

echo "=== INICIANDO TESTE DO MODO IMPLANTAÇÃO (catalogo_ativo) ===\n\n";

function fetchJson($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($response, true)];
}

// 1. Test config-by-slug
echo "[1] Testando /api/usuario/config-by-slug?slug=$slug ...\n";
list($code, $configSlug) = fetchJson("$baseUrl/api/usuario/config-by-slug?slug=$slug");
echo "    Status: $code\n";
echo "    Nome: " . ($configSlug['nome'] ?? 'N/A') . "\n";
echo "    catalogo_ativo: " . (isset($configSlug['catalogo_ativo']) ? var_export($configSlug['catalogo_ativo'], true) : 'AUSENTE') . "\n";
echo "    mensagem_manutencao: " . var_export($configSlug['mensagem_manutencao'] ?? null, true) . "\n\n";

// 2. Test api/usuario/config
echo "[2] Testando /api/usuario/config?usuario_id=$usuarioId ...\n";
list($code, $configUser) = fetchJson("$baseUrl/api/usuario/config?usuario_id=$usuarioId");
echo "    Status: $code\n";
echo "    catalogo_ativo: " . (isset($configUser['catalogo_ativo']) ? var_export($configUser['catalogo_ativo'], true) : 'AUSENTE') . "\n\n";

// 3. Test api/produto
echo "[3] Testando /api/produto?usuario_id=$usuarioId ...\n";
list($code, $produtos) = fetchJson("$baseUrl/api/produto?usuario_id=$usuarioId");
echo "    Status: $code\n";
echo "    Total de produtos retornados: " . ($produtos['meta']['totalCount'] ?? count($produtos['items'] ?? [])) . "\n\n";

// 4. Test api/usuario/lojas
echo "[4] Testando /api/usuario/lojas (Vitrine pública) ...\n";
list($code, $lojas) = fetchJson("$baseUrl/api/usuario/lojas");
$encontrada = false;
foreach ($lojas as $loja) {
    if (($loja['id'] ?? '') === $usuarioId) {
        $encontrada = true;
        break;
    }
}
echo "    Status: $code\n";
echo "    Loja presente na vitrine pública: " . ($encontrada ? "SIM (Ativa)" : "NÃO (Oculta/Implantação)") . "\n\n";

echo "=== TESTES CONCLUÍDOS COM SUCESSO ===\n";
