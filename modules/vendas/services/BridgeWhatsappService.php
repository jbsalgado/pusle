<?php

namespace app\modules\vendas\services;

use Yii;
use yii\helpers\FileHelper;
use app\modules\vendas\models\BridgeWhatsappLoja;
use app\modules\vendas\models\BridgeWhatsappMensagem;

/**
 * Serviço de orquestração do Pulse Bridge WhatsApp (Agente Local Go)
 * 100% independente do módulo Evolution.
 */
class BridgeWhatsappService
{
    /**
     * Retorna o diretório de controle de comandos do agente local
     */
    public static function getCommandDir($tokenAgente)
    {
        $dir = Yii::getAlias('@app/runtime/bridge_wa/' . $tokenAgente);
        if (!is_dir($dir)) {
            FileHelper::createDirectory($dir, 0777, true);
        }
        return $dir;
    }

    /**
     * Obtém ou inicializa a configuração do Agente da Loja
     */
    public static function getConfigLoja($usuarioId)
    {
        return BridgeWhatsappLoja::obterOuCriarParaUsuario($usuarioId);
    }

    /**
     * Enfileira uma mensagem de texto ou mídia para envio via agente local
     */
    public static function enfileirarMensagem($usuarioId, $numeroDestino, $texto, $midiaUrl = null, $tipo = BridgeWhatsappMensagem::TIPO_TEXT)
    {
        // Limpa formatação do número
        $numeroLimpo = preg_replace('/[^0-9]/', '', (string)$numeroDestino);
        if (strlen($numeroLimpo) < 10) {
            return [
                'success' => false,
                'message' => 'Número de telefone inválido.'
            ];
        }

        $model = new BridgeWhatsappMensagem();
        $model->usuario_id = $usuarioId;
        $model->direcao = BridgeWhatsappMensagem::DIRECAO_OUTBOUND;
        $model->numero_destino = $numeroLimpo;
        $model->tipo = $tipo;
        $model->conteudo_texto = $texto;
        $model->midia_url = $midiaUrl;
        $model->status = BridgeWhatsappMensagem::STATUS_PENDING;

        if ($model->save()) {
            return [
                'success' => true,
                'mensagem_id' => $model->id,
                'message' => 'Mensagem enfileirada com sucesso para o Agente Local.'
            ];
        }

        return [
            'success' => false,
            'message' => 'Falha ao enfileirar mensagem: ' . implode(', ', $model->getFirstErrors())
        ];
    }

    /**
     * Envia comando para o Agente Local gerar um novo QR Code
     */
    public static function solicitarQrCode($usuarioId)
    {
        $loja = self::getConfigLoja($usuarioId);
        $dir = self::getCommandDir($loja->token_agente);
        
        $cmd = [
            'action' => 'request_qr',
            'created_at' => time()
        ];
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'cmd_request_qr.json', json_encode($cmd), LOCK_EX);

        // Atualiza status preliminar
        $loja->status_conexao = BridgeWhatsappLoja::STATUS_CONNECTING;
        $loja->qr_code_base64 = null;
        $loja->save(false);

        return [
            'success' => true,
            'message' => 'Comando enviado ao Agente Local. Aguardando emissão do QR Code.'
        ];
    }

    /**
     * Envia comando para o Agente Local desconectar o WhatsApp
     */
    public static function desconectar($usuarioId)
    {
        $loja = self::getConfigLoja($usuarioId);
        $dir = self::getCommandDir($loja->token_agente);

        $cmd = [
            'action' => 'disconnect',
            'created_at' => time()
        ];
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'cmd_disconnect.json', json_encode($cmd), LOCK_EX);

        $loja->status_conexao = BridgeWhatsappLoja::STATUS_DISCONNECTED;
        $loja->qr_code_base64 = null;
        $loja->telefone_conectado = null;
        $loja->push_name = null;
        $loja->save(false);

        return [
            'success' => true,
            'message' => 'Comando de desconexão enviado ao Agente Local.'
        ];
    }
}
