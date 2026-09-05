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
}
