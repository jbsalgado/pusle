<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\helpers\Url;
use app\models\SocialAccount;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Desabilita validação CSRF para o callback automatizado de exclusão de dados da Meta
     */
    public function beforeAction($action)
    {
        if ($action->id === 'exclusao-dados') {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Página inicial do SaaS
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->isGuest) {
            $usuario = Yii::$app->user->identity;
            if ($usuario->is_admin && !$usuario->eh_dono_loja) {
                return $this->redirect(['/admin/loja/index']);
            }
            return $this->redirect(['/vendas/inicio']);
        }

        // Usuário não logado → vai para a vitrine pública de lojas (SaaS)
        return $this->redirect(Yii::$app->request->baseUrl . '/catalogo/lojas.html');
    }

    /**
     * Página pública de Política de Privacidade (LGPD e Meta Platform Compliance)
     */
    public function actionPoliticaPrivacidade()
    {
        return $this->render('politica-privacidade');
    }

    /**
     * Página pública de Termos de Uso e Serviço
     */
    public function actionTermosDeUso()
    {
        return $this->render('termos-de-uso');
    }

    /**
     * Instruções e Endpoint de Callback Oficial de Exclusão de Dados da Meta (Data Deletion Request Callback)
     * Requisito obrigatório da Meta Platform Terms Section 4.b e LGPD.
     */
    public function actionExclusaoDados()
    {
        // Se for requisição POST da Meta (Data Deletion Callback)
        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $signedRequest = Yii::$app->request->post('signed_request');
            $appSecret = Yii::$app->params['meta_app_secret'] ?? '';

            $data = null;
            if (!empty($signedRequest) && !empty($appSecret)) {
                $data = $this->parseMetaSignedRequest($signedRequest, $appSecret);
            }

            $userId = $data['user_id'] ?? 'unknown_user_' . time();
            $confirmationCode = 'del_' . substr(hash('sha256', $userId . time() . uniqid()), 0, 16);

            // Log de auditoria da exclusão
            Yii::info("Meta Data Deletion Request recebido para user_id: {$userId}. Confirmation code: {$confirmationCode}", __METHOD__);

            // Desconecta contas associadas se houver
            try {
                SocialAccount::updateAll(
                    ['status' => SocialAccount::STATUS_DISCONNECTED, 'updated_at' => date('Y-m-d H:i:s')],
                    ['or', ['facebook_page_id' => $userId], ['instagram_business_id' => $userId]]
                );
            } catch (\Throwable $e) {
                Yii::warning("Erro ao desconectar registros de conta social durante exclusão: " . $e->getMessage(), __METHOD__);
            }

            $statusUrl = Url::to(['/site/exclusao-dados', 'id' => $confirmationCode], true);

            // Garante HTTPS na URL retornada à Meta
            if (strpos($statusUrl, 'http://') === 0) {
                $statusUrl = 'https://' . substr($statusUrl, 7);
            }

            return [
                'url' => $statusUrl,
                'confirmation_code' => $confirmationCode,
            ];
        }

        // Se for requisição GET (Usuário acessando instruções ou verificando status)
        return $this->render('exclusao-dados');
    }

    /**
     * Decodifica e valida a assinatura do signed_request enviado pela Meta
     *
     * @param string $signedRequest
     * @param string $secret
     * @return array|null
     */
    protected function parseMetaSignedRequest($signedRequest, $secret)
    {
        if (strpos($signedRequest, '.') === false) {
            return null;
        }

        list($encodedSig, $payload) = explode('.', $signedRequest, 2);

        $sig = base64_decode(strtr($encodedSig, '-_', '+/'));
        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        if (empty($data) || empty($data['algorithm']) || strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
            return null;
        }

        $expectedSig = hash_hmac('sha256', $payload, $secret, true);
        if (hash_equals($sig, $expectedSig)) {
            return $data;
        }

        return null;
    }
}
