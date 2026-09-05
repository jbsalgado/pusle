<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;
use app\modules\vendas\models\BridgeWhatsappLoja;
use app\modules\vendas\models\BridgeWhatsappMensagem;
use app\modules\vendas\services\BridgeWhatsappService;

/**
 * Controller de API REST para comunicação bidirecional com o Agente Local Go Whatsmeow
 * Prefixo de Rota: /api/bridge-whatsapp/
 */
class BridgeWhatsappController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    /**
     * Autentica o agente local a partir do token único de loja
     * @return BridgeWhatsappLoja
     * @throws UnauthorizedHttpException
     */
    protected function autenticarAgente()
    {
        $token = Yii::$app->request->headers->get('X-Agent-Token')
            ?: Yii::$app->request->get('token')
            ?: Yii::$app->request->post('token');

        if (empty($token)) {
            throw new UnauthorizedHttpException('Token de autenticação do agente não fornecido.');
        }

        $loja = BridgeWhatsappLoja::findOne(['token_agente' => (string)$token]);
        if (!$loja) {
            throw new UnauthorizedHttpException('Token do agente inválido ou inexistente.');
        }

        // Registra heartbeat e IP
        $loja->ultimo_heartbeat = date('Y-m-d H:i:s');
        $loja->ip_origem_agente = Yii::$app->request->getUserIP();
        $loja->save(false);

        return $loja;
    }

    /**
     * Handshake inicial quando o agente Go é iniciado no computador da loja
     * POST /api/bridge-whatsapp/handshake
     */
    public function actionHandshake()
    {
        $loja = $this->autenticarAgente();
        $versao = Yii::$app->request->post('version', '1.0.0');
        $os = Yii::$app->request->post('os', 'unknown');

        return [
            'success' => true,
            'message' => 'Agente local autenticado com sucesso.',
            'loja_id' => $loja->usuario_id,
            'status_conexao' => $loja->status_conexao,
            'telefone' => $loja->telefone_conectado,
            'ip_detectado' => $loja->ip_origem_agente,
            'server_time' => time()
        ];
    }

    /**
     * Long-polling do Agente para buscar comandos ou mensagens de disparo pendentes
     * GET /api/bridge-whatsapp/poll
     */
    public function actionPoll()
    {
        $loja = $this->autenticarAgente();
        $dir = BridgeWhatsappService::getCommandDir($loja->token_agente);

        // 1. Verifica comandos prioritários de controle (QR Code / Desconectar)
        $cmdFiles = glob($dir . DIRECTORY_SEPARATOR . 'cmd_*.json');
        if (!empty($cmdFiles)) {
            $cmdFile = $cmdFiles[0];
            $content = @file_get_contents($cmdFile);
            @unlink($cmdFile);
            if ($content) {
                $cmdData = json_decode($content, true);
                if ($cmdData) {
                    return [
                        'success' => true,
                        'type' => 'command',
                        'data' => $cmdData
                    ];
                }
            }
        }

        // 2. Se o WhatsApp estiver conectado, verifica fila de mensagens para envio
        if ($loja->status_conexao === BridgeWhatsappLoja::STATUS_CONNECTED) {
            $msg = BridgeWhatsappMensagem::find()
                ->where([
                    'usuario_id' => $loja->usuario_id,
                    'direcao' => BridgeWhatsappMensagem::DIRECAO_OUTBOUND,
                    'status' => BridgeWhatsappMensagem::STATUS_PENDING
                ])
                ->orderBy(['created_at' => SORT_ASC])
                ->one();

            if ($msg) {
                // Marca temporariamente como sending
                $msg->status = 'sending';
                $msg->save(false);

                return [
                    'success' => true,
                    'type' => 'send_message',
                    'data' => [
                        'id' => $msg->id,
                        'numero_destino' => $msg->numero_destino,
                        'tipo' => $msg->tipo,
                        'texto' => $msg->conteudo_texto,
                        'midia_url' => $msg->midia_url
                    ]
                ];
            }
        }

        return [
            'success' => true,
            'type' => 'idle',
            'data' => null
        ];
    }

    /**
     * Recebe o QR Code gerado pelo Whatsmeow no agente local
     * POST /api/bridge-whatsapp/qr-code
     */
    public function actionQrCode()
    {
        $loja = $this->autenticarAgente();
        $qrCode = Yii::$app->request->post('qr_code');

        $loja->qr_code_base64 = $qrCode;
        $loja->status_conexao = BridgeWhatsappLoja::STATUS_QR_READY;
        $loja->save(false);

        return [
            'success' => true,
            'message' => 'QR Code atualizado com sucesso.'
        ];
    }

    /**
     * Recebe atualização de status da conexão do WhatsApp pelo agente
     * POST /api/bridge-whatsapp/status
     */
    public function actionStatus()
    {
        $loja = $this->autenticarAgente();
        $status = Yii::$app->request->post('status', BridgeWhatsappLoja::STATUS_DISCONNECTED);
        $telefone = Yii::$app->request->post('phone');
        $pushName = Yii::$app->request->post('push_name');

        $loja->status_conexao = in_array($status, [
            BridgeWhatsappLoja::STATUS_DISCONNECTED,
            BridgeWhatsappLoja::STATUS_QR_READY,
            BridgeWhatsappLoja::STATUS_CONNECTING,
            BridgeWhatsappLoja::STATUS_CONNECTED
        ], true) ? $status : BridgeWhatsappLoja::STATUS_DISCONNECTED;

        if ($telefone) $loja->telefone_conectado = $telefone;
        if ($pushName) $loja->push_name = $pushName;
        if ($status === BridgeWhatsappLoja::STATUS_CONNECTED) {
            $loja->qr_code_base64 = null; // limpa qr code se já conectou
        }

        $loja->save(false);

        return [
            'success' => true,
            'message' => 'Status da conexão atualizado com sucesso.',
            'status' => $loja->status_conexao
        ];
    }

    /**
     * Reporta confirmação de entrega / leitura / falha de uma mensagem enviada
     * POST /api/bridge-whatsapp/ack
     */
    public function actionAck()
    {
        $loja = $this->autenticarAgente();
        $msgId = Yii::$app->request->post('id');
        $status = Yii::$app->request->post('status', BridgeWhatsappMensagem::STATUS_SENT);
        $waId = Yii::$app->request->post('whatsapp_id');
        $erro = Yii::$app->request->post('error');

        $msg = BridgeWhatsappMensagem::findOne([
            'id' => $msgId,
            'usuario_id' => $loja->usuario_id
        ]);

        if ($msg) {
            $msg->status = $status;
            if ($waId) $msg->mensagem_id_whatsapp = $waId;
            if ($erro) $msg->erro_motivo = $erro;
            $msg->save(false);
        }

        return ['success' => true];
    }

    /**
     * Recebe mensagens enviadas por clientes para o WhatsApp da loja
     * POST /api/bridge-whatsapp/inbound
     */
    public function actionInbound()
    {
        $loja = $this->autenticarAgente();
        $remetente = Yii::$app->request->post('from');
        $texto = Yii::$app->request->post('text');
        $midiaUrl = Yii::$app->request->post('media_url');
        $tipo = Yii::$app->request->post('type', BridgeWhatsappMensagem::TIPO_TEXT);
        $waId = Yii::$app->request->post('whatsapp_id');

        $msg = new BridgeWhatsappMensagem();
        $msg->usuario_id = $loja->usuario_id;
        $msg->direcao = BridgeWhatsappMensagem::DIRECAO_INBOUND;
        $msg->numero_remetente = preg_replace('/[^0-9]/', '', (string)$remetente);
        $msg->numero_destino = (string)$loja->telefone_conectado;
        $msg->tipo = $tipo;
        $msg->conteudo_texto = $texto;
        $msg->midia_url = $midiaUrl;
        $msg->status = BridgeWhatsappMensagem::STATUS_READ;
        $msg->mensagem_id_whatsapp = $waId;
        $msg->save(false);

        return ['success' => true, 'id' => $msg->id];
    }
}
