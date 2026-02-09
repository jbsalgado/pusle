<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\modules\vendas\models\CupomFiscal;
use app\modules\vendas\models\Venda;
use yii\web\Response;

/**
 * CupomFiscalController implements the management for CupomFiscal models.
 */
class CupomFiscalController extends Controller
{
    public $layout = 'main';

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
     * Lists all CupomFiscal models.
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;
        $dataProvider = new ActiveDataProvider([
            'query' => CupomFiscal::find()->where(['usuario_id' => $usuarioId])->orderBy(['data_emissao' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single CupomFiscal model.
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Generates and outputs the DANFE PDF.
     */
    public function actionPdf($id)
    {
        $model = $this->findModel($id);

        if (!file_exists($model->xml_path)) {
            Yii::$app->session->setFlash('error', 'Arquivo XML não encontrado em repatitório local.');
            return $this->redirect(['index']);
        }

        $xmlContent = file_get_contents($model->xml_path);

        try {
            $pdf = Yii::$app->nfwService->gerarDanfe($xmlContent, $model->modelo);

            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->add('Content-Type', 'application/pdf');
            Yii::$app->response->headers->add('Content-Disposition', 'inline; filename="DANFE_' . $model->chave_acesso . '.pdf"');

            return $pdf;
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Erro ao gerar PDF: ' . $e->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    /**
     * Downloads the XML file.
     */
    public function actionXml($id)
    {
        $model = $this->findModel($id);

        if (!file_exists($model->xml_path)) {
            throw new NotFoundHttpException('O arquivo XML não existe.');
        }

        return Yii::$app->response->sendFile($model->xml_path, $model->chave_acesso . '.xml');
    }

    /**
     * Finds the CupomFiscal model based on its primary key value.
     */
    protected function findModel($id)
    {
        if (($model = CupomFiscal::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}
