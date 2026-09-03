<?php

/**
 * Verificar Fotos Órfãs — Diagnóstico e Reconciliação de ProdutoFoto
 * =============================================================================
 * Compara os `arquivo_path` registrados em `prest_produto_fotos` com os arquivos
 * físicos em `web/` e gera um relatório de fotos ausentes (órfãs).
 *
 * Uso:
 *   php scripts/verificar_fotos_orfas.php
 *
 * Opções:
 *   --por-loja     Agrupa o relatório por usuário/loja (dono)
 *   --json         Emite o resultado em JSON (útil para automação)
 *   --somente-missing  Lista apenas os registros cujo arquivo não existe
 */

require __DIR__ . '/../vendor/autoload.php';

// Carrega as variáveis de ambiente do arquivo .env (mesmo padrão de web/index.php)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$dsn      = $_ENV['DB_DSN']      ?? getenv('DB_DSN')      ?: 'pgsql:host=localhost;port=5432;dbname=pulse';
$username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'postgres';
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'postgres';

$webRoot = realpath(__DIR__ . '/../web');
if ($webRoot === false) {
    fwrite(STDERR, "Erro: diretório web não encontrado em " . __DIR__ . "/../web\n");
    exit(1);
}

$porLoja        = in_array('--por-loja',        $argv, true);
$json           = in_array('--json',            $argv, true);
$somenteMissing = in_array('--somente-missing', $argv, true);

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Erro ao conectar ao banco: " . $e->getMessage() . "\n");
    exit(1);
}

$sql = "
    SELECT
        f.id,
        f.produto_id,
        f.arquivo_nome,
        f.arquivo_path,
        f.eh_principal,
        f.ordem,
        p.usuario_id,
        p.nome AS produto_nome,
        u.nome AS usuario_nome
    FROM prest_produto_fotos f
    LEFT JOIN prest_produtos p ON p.id = f.produto_id
    LEFT JOIN prest_usuarios u ON u.id = p.usuario_id
    ORDER BY u.nome, p.nome, f.ordem
";

$rows = $pdo->query($sql)->fetchAll();

$total     = 0;
$existem   = 0;
$faltam    = 0;
$missing   = [];

foreach ($rows as $row) {
    $total++;
    $rel   = ltrim((string)$row['arquivo_path'], '/');
    $fisico = $webRoot . '/' . $rel;
    $existe = is_file($fisico);

    if ($existe) {
        $existem++;
        continue;
    }

    $faltam++;
    $missing[] = [
        'id'             => $row['id'],
        'produto_id'     => $row['produto_id'],
        'usuario_id'     => $row['usuario_id'],
        'usuario_nome'   => $row['usuario_nome'] ?? '(sem usuário)',
        'produto_nome'   => $row['produto_nome'] ?? '(sem produto)',
        'arquivo_nome'   => $row['arquivo_nome'],
        'arquivo_path'   => $row['arquivo_path'],
        'eh_principal'   => (bool)($row['eh_principal'] ?? false),
        'caminho_fisico' => $fisico,
    ];
}

$resumo = [
    'dsn'           => $dsn,
    'web_root'      => $webRoot,
    'total_registros' => $total,
    'arquivos_existem' => $existem,
    'arquivos_faltam'  => $faltam,
];

if ($json) {
    echo json_encode([
        'resumo'  => $resumo,
        'missing' => $somenteMissing ? $missing : $missing,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

echo "═══════════════════════════════════════════════════════════\n";
echo " Diagnóstico de Fotos Órfãs (prest_produto_fotos)\n";
echo "═══════════════════════════════════════════════════════════\n";
echo " DSN:        {$resumo['dsn']}\n";
echo " Web root:   {$resumo['web_root']}\n";
echo " Registros:  {$resumo['total_registros']}\n";
echo " Existem:    {$resumo['arquivos_existem']}\n";
echo " FALTAM:     {$resumo['arquivos_faltam']}\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (empty($missing)) {
    echo "✅ Nenhuma foto órfã encontrada. Todos os arquivos existem.\n";
    exit(0);
}

if ($porLoja) {
    $porUsuario = [];
    foreach ($missing as $m) {
        $key = $m['usuario_nome'] . ' (' . $m['usuario_id'] . ')';
        $porUsuario[$key][] = $m;
    }
    foreach ($porUsuario as $usuario => $itens) {
        echo "🏪 Loja: {$usuario}\n";
        foreach ($itens as $m) {
            echo "   - {$m['produto_nome']} | {$m['arquivo_path']}" . ($m['eh_principal'] ? ' [PRINCIPAL]' : '') . "\n";
        }
        echo "\n";
    }
} else {
    foreach ($missing as $m) {
        echo "✗ [{$m['id']}] {$m['produto_nome']}\n";
        echo "    usuario: {$m['usuario_nome']} ({$m['usuario_id']})\n";
        echo "    path:    {$m['arquivo_path']}" . ($m['eh_principal'] ? ' [PRINCIPAL]' : '') . "\n";
        echo "    físico:  {$m['caminho_fisico']}\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════\n";
echo " Total de arquivos físicos ausentes: {$faltam}\n";
echo "═══════════════════════════════════════════════════════════\n";