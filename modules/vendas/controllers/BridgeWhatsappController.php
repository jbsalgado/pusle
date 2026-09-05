<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\modules\vendas\models\BridgeWhatsappLoja;
use app\modules\vendas\models\BridgeWhatsappMensagem;
use app\modules\vendas\services\BridgeWhatsappService;

/**
 * Controller do Painel Web do Lojista para gerenciamento do WhatsApp via Agente Local
 */
class BridgeWhatsappController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tela Principal de Gestão do WhatsApp Local (Pulse Agent)
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;
        $loja = BridgeWhatsappService::getConfigLoja($usuarioId);

        $mensagens = BridgeWhatsappMensagem::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(20)
            ->all();

        return $this->render('index', [
            'loja' => $loja,
            'mensagens' => $mensagens
        ]);
    }

    /**
     * Endpoint JSON para polling de status em tempo real
     */
    public function actionStatusJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $usuarioId = Yii::$app->user->id;
        $loja = BridgeWhatsappService::getConfigLoja($usuarioId);

        return [
            'success' => true,
            'agente_online' => $loja->isAgenteOnline(),
            'whatsapp_conectado' => $loja->isWhatsappConectado(),
            'status' => $loja->status_conexao,
            'telefone' => $loja->telefone_conectado,
            'push_name' => $loja->push_name,
            'ip_agente' => $loja->ip_origem_agente,
            'ultimo_heartbeat' => $loja->ultimo_heartbeat,
            'qr_code' => $loja->qr_code_base64
        ];
    }

    /**
     * Dispara comando para gerar QR Code
     */
    public function actionConectar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $usuarioId = Yii::$app->user->id;
        return BridgeWhatsappService::solicitarQrCode($usuarioId);
    }

    /**
     * Dispara comando para desconectar
     */
    public function actionDesconectar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $usuarioId = Yii::$app->user->id;
        return BridgeWhatsappService::desconectar($usuarioId);
    }

    /**
     * Envia mensagem de teste
     */
    public function actionEnviarTeste()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $usuarioId = Yii::$app->user->id;
        $numero = Yii::$app->request->post('numero');
        $texto = Yii::$app->request->post('texto', 'Teste de envio via Pulse Bridge WhatsApp Local!');

        return BridgeWhatsappService::enfileirarMensagem($usuarioId, $numero, $texto);
    }

    /**
     * Gera e faz download do inicializador 1-clique para Windows (.bat)
     */
    public function actionBaixarBat()
    {
        $usuarioId = Yii::$app->user->id;
        $loja = BridgeWhatsappService::getConfigLoja($usuarioId);
        $serverUrl = Yii::$app->request->hostInfo;
        $token = $loja->token_agente;

        $content = "@echo off\r\n";
        $content .= "title Pulse Agent WhatsApp - Conexao Local da Loja\r\n";
        $content .= "cls\r\n";
        $content .= "echo =======================================================\r\n";
        $content .= "echo   PULSE AGENT WHATSAPP - CONEXAO LOCAL DA LOJA\r\n";
        $content .= "echo   Zero Custo Meta API - IP Residencial Antiban\r\n";
        $content .= "echo =======================================================\r\n";
        $content .= "echo.\r\n";
        $content .= "if not exist \"pulse-agent.exe\" (\r\n";
        $content .= "    echo [1/2] Baixando executavel do agente pulse-agent.exe...\r\n";
        $content .= "    curl -fsSL \"{$serverUrl}/downloads/bridge/pulse-agent.exe\" -o pulse-agent.exe\r\n";
        $content .= ")\r\n";
        $content .= "echo [2/2] Iniciando Pulse Agent no seu computador...\r\n";
        $content .= "echo Conectando a VPS: {$serverUrl}\r\n";
        $content .= "echo.\r\n";
        $content .= "pulse-agent.exe --token=\"{$token}\" --server=\"{$serverUrl}\"\r\n";
        $content .= "pause\r\n";

        return Yii::$app->response->sendContentAsFile($content, 'iniciar_whatsapp.bat', [
            'mimeType' => 'application/x-bat',
            'inline' => false
        ]);
    }

    /**
     * Gera e faz download do inicializador 1-clique para Linux (.sh)
     */
    public function actionBaixarSh()
    {
        $usuarioId = Yii::$app->user->id;
        $loja = BridgeWhatsappService::getConfigLoja($usuarioId);
        $serverUrl = Yii::$app->request->hostInfo;
        $token = $loja->token_agente;

        $content = "#!/bin/bash\n";
        $content .= "echo '======================================================='\n";
        $content .= "echo '  PULSE AGENT WHATSAPP - CONEXAO LOCAL DA LOJA'\n";
        $content .= "echo '  Zero Custo Meta API - IP Residencial Antiban'\n";
        $content .= "echo '======================================================='\n";
        $content .= "echo ''\n";
        $content .= "if [ ! -f \"pulse-agent-linux\" ]; then\n";
        $content .= "    echo '[1/2] Baixando executavel do agente pulse-agent-linux...'\n";
        $content .= "    curl -fsSL \"{$serverUrl}/downloads/bridge/pulse-agent-linux\" -o pulse-agent-linux\n";
        $content .= "    chmod +x pulse-agent-linux\n";
        $content .= "fi\n";
        $content .= "echo '[2/2] Iniciando Pulse Agent no seu computador...'\n";
        $content .= "echo 'Conectando a VPS: {$serverUrl}'\n";
        $content .= "echo ''\n";
        $content .= "./pulse-agent-linux --token=\"{$token}\" --server=\"{$serverUrl}\"\n";

        return Yii::$app->response->sendContentAsFile($content, 'iniciar_whatsapp.sh', [
            'mimeType' => 'application/x-sh',
            'inline' => false
        ]);
    }
}
