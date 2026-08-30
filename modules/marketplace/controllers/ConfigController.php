<?php

namespace app\modules\marketplace\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\helpers\Url;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\components\MercadoLivreService;
use app\modules\marketplace\components\ShopeeService;
use app\modules\marketplace\components\MagaluService;
use app\modules\marketplace\components\TemuService;
use app\components\TenantHelper;

/**
 * ConfigController - Gerenciamento de Contas e Autenticação OAuth de Marketplaces
 */
class ConfigController extends Controller
{
    /**
     * {@inheritdoc}
     */
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
     * Lista todas as configurações e contas de marketplace do tenant
     */
    public function actionIndex()
    {
        $usuarioId = TenantHelper::getId();

        $configs = MarketplaceConfig::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['marketplace' => SORT_ASC, 'apelido_conta' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'configs' => $configs,
        ]);
    }

    /**
     * Cria nova configuração / conexão
     */
    public function actionCreate()
    {
        $model = new MarketplaceConfig();
        $model->usuario_id = TenantHelper::getId();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Configuração de marketplace cadastrada com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza configuração existente
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Configuração atualizada com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Exibe detalhes da configuração
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Deleta configuração
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();

        Yii::$app->session->setFlash('success', 'Configuração removida com sucesso!');
        return $this->redirect(['index']);
    }

    /**
     * Ativa/desativa sincronização do marketplace
     */
    public function actionToggle($id)
    {
        $model = $this->findModel($id);
        $model->ativo = !$model->ativo;
        $model->save(false);

        $status = $model->ativo ? 'ativada' : 'desativada';
        Yii::$app->session->setFlash('success', "Integração {$model->getMarketplaceNome()} ({$model->apelido_conta}) {$status} com sucesso!");

        return $this->redirect(['index']);
    }

    /**
     * Inicia fluxo de autorização OAuth
     */
    public function actionAuth($id)
    {
        $model = $this->findModel($id);
        $callbackUrl = Url::to(['/marketplace/config/callback', 'id' => $model->id], true);

        if ($model->marketplace === MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE) {
            if (empty($model->client_id)) {
                Yii::$app->session->setFlash('error', 'Preencha o Client ID (App ID) do Mercado Livre antes de autenticar.');
                return $this->redirect(['update', 'id' => $model->id]);
            }
            $authUrl = "https://auth.mercadolivre.com.br/authorization?response_type=code&client_id={$model->client_id}&redirect_uri=" . urlencode($callbackUrl);
            return $this->redirect($authUrl);
        }

        if ($model->marketplace === MarketplaceConfig::MARKETPLACE_SHOPEE) {
            if (empty($model->client_id) || empty($model->client_secret)) {
                Yii::$app->session->setFlash('error', 'Preencha o Partner ID e Partner Key antes de autenticar com a Shopee.');
                return $this->redirect(['update', 'id' => $model->id]);
            }
            $timestamp = time();
            $path = '/api/v2/shop/auth_partner';
            $sign = hash_hmac('sha256', $model->client_id . $path . $timestamp, $model->client_secret);
            $authUrl = "https://partner.shopeemobile.com{$path}?partner_id={$model->client_id}&timestamp={$timestamp}&sign={$sign}&redirect=" . urlencode($callbackUrl);
            return $this->redirect($authUrl);
        }

        Yii::$app->session->setFlash('info', "O marketplace {$model->getMarketplaceNome()} utiliza API Token estático. Basta salvar as credenciais.");
        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Callback do OAuth para receber o authorization code
     */
    public function actionCallback($id)
    {
        $model = $this->findModel($id);
        $code = Yii::$app->request->get('code');
        $shopId = Yii::$app->request->get('shop_id');
        $callbackUrl = Url::to(['/marketplace/config/callback', 'id' => $model->id], true);

        if (!$code) {
            Yii::$app->session->setFlash('error', 'Código de autorização não recebido do marketplace.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $service = null;
        if ($model->marketplace === MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE) {
            $service = new MercadoLivreService();
            $service->setConfig($model->attributes);
            $sucesso = $service->authenticate($code, $callbackUrl);
        } elseif ($model->marketplace === MarketplaceConfig::MARKETPLACE_SHOPEE) {
            $service = new ShopeeService();
            $service->setConfig($model->attributes);
            $sucesso = $service->authenticate($code, $shopId);
        } else {
            $sucesso = false;
        }

        if ($sucesso) {
            $model->refresh();
            $model->ativo = true;
            $model->save(false);
            Yii::$app->session->setFlash('success', "Autenticação realizada com sucesso no {$model->getMarketplaceNome()}!");
        } else {
            Yii::$app->session->setFlash('error', "Falha ao obter tokens de acesso do {$model->getMarketplaceNome()}. Verifique suas credenciais.");
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Busca o model garantindo isolamento do tenant
     */
    protected function findModel($id): MarketplaceConfig
    {
        $model = MarketplaceConfig::findOne([
            'id' => $id,
            'usuario_id' => TenantHelper::getId(),
        ]);

        if ($model === null) {
            throw new NotFoundHttpException('Configuração não encontrada ou acesso não autorizado.');
        }

        return $model;
    }
}
