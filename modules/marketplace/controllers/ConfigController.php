<?php

namespace app\modules\marketplace\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\components\MarketplaceAuthManager;

/**
 * Config Controller para gerenciar configurações de marketplaces
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
     * Lista todas as configurações do usuário
     * @return string
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        $configs = MarketplaceConfig::find()
            ->where(['usuario_id' => $usuarioId])
            ->all();

        return $this->render('index', [
            'configs' => $configs,
        ]);
    }

    /**
     * Cria nova configuração de marketplace
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new MarketplaceConfig();
        $model->usuario_id = Yii::$app->user->id;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Configuração de marketplace criada com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza configuração existente
     * @param string $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
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
     * Visualiza configuração
     * @param string $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Deleta configuração
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();

        Yii::$app->session->setFlash('success', 'Configuração removida com sucesso!');
        return $this->redirect(['index']);
    }

    /**
     * Ativa/desativa marketplace
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionToggle($id)
    {
        $model = $this->findModel($id);
        $model->ativo = !$model->ativo;
        $model->save();

        $status = $model->ativo ? 'ativado' : 'desativado';
        Yii::$app->session->setFlash('success', "Marketplace {$status} com sucesso!");

        return $this->redirect(['index']);
    }

    /**
     * Testa conexão com marketplace
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionTest($id)
    {
        $model = $this->findModel($id);

        // TODO: Implementar teste de conexão real quando tiver as integrações
        Yii::$app->session->setFlash('info', 'Teste de conexão ainda não implementado. Aguarde a implementação da integração com ' . $model->getMarketplaceNome());

        return $this->redirect(['view', 'id' => $id]);
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
            'usuario_id' => Yii::$app->user->id, // Segurança: apenas configs do próprio usuário
        ]);

        if ($model === null) {
            throw new NotFoundHttpException('Configuração não encontrada.');
        }

        return $model;
    }
}
