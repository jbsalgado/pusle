<?php

namespace app\components;

use Yii;

/**
 * TelegramHelper - Envia alertas via Telegram Bot API
 */
class TelegramHelper
{
    /**
     * Envia uma mensagem simples para o Telegram
     * 
     * @param string $message Mensagem a ser enviada
     * @return bool Sucesso do envio
     */
    public static function sendMessage($message)
    {
        $botToken = Yii::$app->params['telegram_bot_token'] ?? null;
        $chatId = Yii::$app->params['telegram_chat_id'] ?? null;

        if (!$botToken || !$chatId) {
            Yii::warning("Telegram não configurado em params.php (bot_token/chat_id)", 'telegram');
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $data = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ];

            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                ],
            ];

            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);

            if ($result === false) {
                Yii::error("Erro ao enviar mensagem para Telegram", 'telegram');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Yii::error("Exceção Telegram: " . $e->getMessage(), 'telegram');
            return false;
        }
    }

    /**
     * Formata uma nova venda para o Telegram
     * 
     * @param \app\modules\vendas\models\Venda $venda
     * @return string
     */
    public static function formatVendaAlerta($venda)
    {
        $valorStr = number_format($venda->valor_total, 2, ',', '.');
        $cliente = $venda->cliente ? $venda->cliente->nome_completo : 'Consumidor Final';
        $pwaStr = $venda->is_pwa ? "📱 PWA" : "💻 Dashboard";

        $msg = "📢 *Nova Venda Realizada!*\n\n";
        $msg .= "💰 *Valor:* R$ $valorStr\n";
        $msg .= "👤 *Cliente:* $cliente\n";
        $msg .= "📍 *Origem:* $pwaStr\n";
        $msg .= "🕒 *Data:* " . date('d/m/Y H:i') . "\n";

        return $msg;
    }
}
