<?php

namespace app\modules\cobranca\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use app\modules\cobranca\models\CobrancaHistorico;
use app\modules\cobranca\components\CobrancaProcessor;

/**
 * HistoricoController
 * 
 * Gerencia o histórico de cobranças enviadas
 */
class HistoricoController extends Controller
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
     * Lista o histórico de cobranças
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        $query = CobrancaHistorico::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['data_criacao' => SORT_DESC]);

        // Filtros
        $tipo = Yii::$app->request->get('tipo');
        $status = Yii::$app->request->get('status');
        $dataInicio = Yii::$app->request->get('data_inicio');
        $dataFim = Yii::$app->request->get('data_fim');

        if ($tipo) {
            $query->andWhere(['tipo' => $tipo]);
        }

        if ($status) {
            $query->andWhere(['status' => $status]);
        }

        if ($dataInicio) {
            $query->andWhere(['>=', 'data_criacao', $dataInicio . ' 00:00:00']);
        }

        if ($dataFim) {
            $query->andWhere(['<=', 'data_criacao', $dataFim . ' 23:59:59']);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        // Estatísticas
        $stats = [
            'total' => CobrancaHistorico::find()->where(['usuario_id' => $usuarioId])->count(),
            'enviadas' => CobrancaHistorico::find()->where(['usuario_id' => $usuarioId, 'status' => 'ENVIADO'])->count(),
            'falhas' => CobrancaHistorico::find()->where(['usuario_id' => $usuarioId, 'status' => 'FALHA'])->count(),
            'pendentes' => CobrancaHistorico::find()->where(['usuario_id' => $usuarioId, 'status' => 'PENDENTE'])->count(),
        ];

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
            'filtros' => [
                'tipo' => $tipo,
                'status' => $status,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
            ],
        ]);
    }

    /**
     * Visualiza detalhes de um envio
     */
    public function actionView($id)
    {
        $usuarioId = Yii::$app->user->id;

        $model = CobrancaHistorico::find()
            ->where(['id' => $id, 'usuario_id' => $usuarioId])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException('Registro não encontrado.');
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Reenvia uma cobrança (AJAX)
     */
    public function actionReenviar($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $usuarioId = Yii::$app->user->id;

        $historico = CobrancaHistorico::find()
            ->where(['id' => $id, 'usuario_id' => $usuarioId])
            ->one();

        if (!$historico) {
            return [
                'success' => false,
                'message' => 'Registro não encontrado.',
            ];
        }

        $processor = new CobrancaProcessor();
        $sucesso = $processor->reenviarCobranca($historico);

        if ($sucesso) {
            return [
                'success' => true,
                'message' => 'Cobrança reenviada com sucesso!',
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Erro ao reenviar cobrança. Verifique as configurações.',
            ];
        }
    }
}
