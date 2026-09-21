<?php

namespace app\components;

use Yii;

/**
 * Componente Notificador de Eventos em Tempo Real via WebSocket Broker
 * 
 * Envia mensagens e eventos para o microserviço Node.js interno (127.0.0.1:3001),
 * que faz o push instantâneo para todos os clientes conectados daquela loja.
 */
class WebSocketNotifier
{
    const DEFAULT_PORT = 3001;
    const DEFAULT_SECRET = 'pulse_internal_ws_secret_key_2026';

    /**
     * Notifica todos os navegadores/clientes conectados da loja especificada
     *
     * @param int|string $lojaId ID da loja (tenant)
     * @param string $tipoEvento Tipo do evento (ex: 'nova_mensagem', 'atendimento_encerrado', 'conversa_limpa')
     * @param array $dados Dados complementares do evento
     * @return bool
     */
    public static function notificarLoja($lojaId, string $tipoEvento, array $dados = []): bool
    {
        if (empty($lojaId)) {
            return false;
        }

        $url = 'http://127.0.0.1:' . self::DEFAULT_PORT . '/publish';
        $payload = [
            'secret' => self::DEFAULT_SECRET,
            'loja_id' => (string)$lojaId,
            'event' => array_merge([
                'type' => $tipoEvento,
                'loja_id' => (string)$lojaId,
                'ts' => time(),
            ], $dados)
        ];

        try {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json)
            ]);
            // Timeout ultra-curto (300ms) para jamais travar o PHP caso o daemon esteja offline
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 150);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 300);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return ($httpCode === 200);
        } catch (\Throwable $e) {
            Yii::warning("Falha ao notificar WebSocket broker: " . $e->getMessage(), 'websocket');
            return false;
        }
    }
}
