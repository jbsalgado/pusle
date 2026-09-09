<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use GuzzleHttp\Client;

/**
 * Comandos de automação do Mercado Pago
 * 
 * Uso:
 * php yii mercado-pago/refresh-token - Renova tokens OAuth próximos ao vencimento
 */
class MercadoPagoController extends Controller
{
    /**
     * Renova tokens OAuth do Mercado Pago que expiram nos próximos 15 dias (ou nulos).
     * 
     * Este comando deve ser executado diariamente via cron job.
     * Exemplo: 0 3 * * * cd /srv/http/pulse && php yii mercado-pago/refresh-token
     */
    public function actionRefreshToken()
    {
        $this->stdout("=== Renovação de Tokens do Mercado Pago ===\n", Console::FG_CYAN);
        $this->stdout("Iniciando em: " . date('Y-m-d H:i:s') . "\n\n");

        $appId = $_ENV['MP_APP_ID'] ?? $_SERVER['MP_APP_ID'] ?? $_ENV['MP_APP_CLIENT_ID'] ?? $_SERVER['MP_APP_CLIENT_ID'] ?? getenv('MP_APP_ID') ?: getenv('MERCADO_PAGO_APP_ID') ?: getenv('MP_APP_CLIENT_ID') ?: (Yii::$app->params['mp_app_id'] ?? null);
        $clientSecret = $_ENV['MP_CLIENT_SECRET'] ?? $_SERVER['MP_CLIENT_SECRET'] ?? $_ENV['MP_APP_CLIENT_SECRET'] ?? $_SERVER['MP_APP_CLIENT_SECRET'] ?? getenv('MP_CLIENT_SECRET') ?: getenv('MERCADO_PAGO_CLIENT_SECRET') ?: getenv('MP_APP_CLIENT_SECRET') ?: (Yii::$app->params['mp_app_secret'] ?? null);

        if (empty($appId) || empty($clientSecret)) {
            $this->stderr("ERRO: MP_APP_ID (ou MP_APP_CLIENT_ID) e MP_CLIENT_SECRET não configurados no ambiente (.env).\n", Console::FG_RED);
            $this->stderr("Configure no seu arquivo .env:\n  MP_APP_ID=seu_client_id\n  MP_CLIENT_SECRET=seu_client_secret\n", Console::FG_YELLOW);
            return ExitCode::CONFIG;
        }

        // Buscar lojistas com gateway Mercado Pago e que possuem refresh_token
        // Filtramos por expiração próxima (menos de 15 dias) ou nula
        $hoje = date('Y-m-d H:i:s');
        $limiteVencimento = date('Y-m-d H:i:s', strtotime('+15 days'));

        $usuarios = Yii::$app->db->createCommand("
            SELECT id, nome, 
                   COALESCE(mp_refresh_token, '') AS mp_refresh_token, 
                   mp_token_expiration 
            FROM prest_usuarios 
            WHERE (api_de_pagamento = true OR gateway_pagamento = 'mercadopago' OR mp_access_token IS NOT NULL OR mercadopago_access_token IS NOT NULL)
              AND mp_refresh_token IS NOT NULL 
              AND (mp_token_expiration IS NULL OR mp_token_expiration <= :limite)
        ", [':limite' => $limiteVencimento])->queryAll();

        if (empty($usuarios)) {
            $this->stdout("Nenhum token expirando nos próximos 15 dias.\n", Console::FG_GREEN);
            return ExitCode::OK;
        }

        $client = new Client(['base_uri' => 'https://api.mercadopago.com']);
        $total = count($usuarios);
        $sucessos = 0;
        $falhas = 0;

        foreach ($usuarios as $usuario) {
            $this->stdout("Processando: {$usuario['nome']} (ID: {$usuario['id']})...\n");
            try {
                $response = $client->post('/oauth/token', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $clientSecret,
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ],
                    'form_params' => [
                        'grant_type' => 'refresh_token',
                        'client_id' => $appId,
                        'client_secret' => $clientSecret,
                        'refresh_token' => $usuario['mp_refresh_token']
                    ]
                ]);

                if ($response->getStatusCode() === 200) {
                    $payload = json_decode((string)$response->getBody(), true);
                    
                    $expiration = null;
                    if (!empty($payload['expires_in'])) {
                        $expiration = (new \DateTimeImmutable('now'))
                            ->add(new \DateInterval('PT' . (int)$payload['expires_in'] . 'S'))
                            ->format('Y-m-d H:i:sP');
                    }

                    $updateData = [
                        'mp_access_token' => $payload['access_token'] ?? null,
                        'mercadopago_access_token' => $payload['access_token'] ?? null,
                        'mp_refresh_token' => $payload['refresh_token'] ?? null,
                        'mp_public_key' => $payload['public_key'] ?? null,
                        'mercadopago_public_key' => $payload['public_key'] ?? null,
                        'mp_user_id' => isset($payload['user_id']) ? (string)$payload['user_id'] : null,
                        'mp_token_expiration' => $expiration,
                    ];
                    Yii::$app->db->createCommand()->update('prest_usuarios', $updateData, 'id = :id', [':id' => $usuario['id']])->execute();

                    $this->stdout("  ✓ Token renovado com sucesso! Válido até: " . ($expiration ?: 'N/A') . "\n", Console::FG_GREEN);
                    $sucessos++;
                } else {
                    $this->stderr("  ✗ Erro na requisição: Status " . $response->getStatusCode() . "\n", Console::FG_RED);
                    $falhas++;
                }
            } catch (\Throwable $e) {
                $this->stderr("  ✗ Erro ao renovar token: " . $e->getMessage() . "\n", Console::FG_RED);
                $falhas++;
            }
        }

        $this->stdout("\n=== Concluído ===\n", Console::FG_CYAN);
        $this->stdout("Total processados: {$total}\n");
        $this->stdout("Sucessos: {$sucessos}\n", Console::FG_GREEN);
        $this->stdout("Falhas: {$falhas}\n", $falhas > 0 ? Console::FG_RED : Console::FG_GREEN);

        return ExitCode::OK;
    }
}
