<?php

namespace app\modules\marketplace\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use app\modules\marketplace\models\MarketplaceConfig;

/**
 * Sync Controller para sincronização manual com marketplaces
 */
class SyncController extends Controller
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
     * Executa sincronização manual
     * @param string $id ID da configuração
     * @param string $tipo Tipo de sincronização (produtos, estoque, pedidos, all)
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionRun($id, $tipo = 'all')
    {
        $config = $this->findModel($id);

        if (!$config->ativo) {
            Yii::$app->session->setFlash('error', 'Este marketplace está desativado. Ative-o antes de sincronizar.');
            return $this->redirect(['/marketplace/dashboard/index']);
        }

        // TODO: Implementar sincronização real quando tiver as integrações
        Yii::$app->session->setFlash('info', sprintf(
            'Sincronização manual com %s ainda não implementada. Aguarde a implementação da integração.',
            $config->getMarketplaceNome()
        ));

        return $this->redirect(['/marketplace/dashboard/index']);
    }

    /**
     * Sincroniza apenas produtos
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionProdutos($id)
    {
        return $this->actionRun($id, 'produtos');
    }

    /**
     * Sincroniza apenas estoque
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionEstoque($id)
    {
        return $this->actionRun($id, 'estoque');
    }

    /**
     * Importa pedidos
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionPedidos($id)
    {
        return $this->actionRun($id, 'pedidos');
    }

    /**
     * Encontra model por ID
     * @param string $id
     * @return MarketplaceConfig
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = MarketplaceConfig::findOne([
            'id' => $id,
            'usuario_id' => Yii::$app->user->id,
        ]);

        if ($model === null) {
            throw new NotFoundHttpException('Configuração não encontrada.');
        }

        return $model;
    }
}
