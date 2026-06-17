<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\FinanceiroMensal;
use app\modules\vendas\models\DadosFinanceiros;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

class FinanceiroController extends Controller
{
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'aplicar-sugestao' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        $dataProvider = new ActiveDataProvider([
            'query' => FinanceiroMensal::find()
                ->where(['usuario_id' => $usuarioId])
                ->orderBy(['mes_referencia' => SORT_DESC]),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {
        $model = new FinanceiroMensal();
        $model->usuario_id = Yii::$app->user->id;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Registro financeiro adicionado com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Registro financeiro atualizado com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    /**
     * Calcula e aplica a sugestão baseada na média dos últimos 3 meses
     */
    public function actionAplicarSugestao()
    {
        $usuarioId = Yii::$app->user->id;

        // Pega os últimos 3 meses
        $registros = FinanceiroMensal::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['mes_referencia' => SORT_DESC])
            ->limit(3)
            ->all();

        if (empty($registros)) {
            Yii::$app->session->setFlash('error', 'Não há registros financeiros suficientes para calcular a sugestão.');
            return $this->redirect(['/vendas/dados-financeiros/global']);
        }

        $totalFat = 0;
        $totalFixas = 0;
        $totalVariaveis = 0;

        foreach ($registros as $reg) {
            $totalFat += $reg->faturamento_total;
            $totalFixas += $reg->despesas_fixas_total;
            $totalVariaveis += $reg->despesas_variaveis_total;
        }

        if ($totalFat == 0) {
            Yii::$app->session->setFlash('error', 'Faturamento total é zero, impossível calcular.');
            return $this->redirect(['/vendas/dados-financeiros/global']);
        }

        $taxaFixaMedia = ($totalFixas / $totalFat) * 100;
        $taxaVariavelMedia = ($totalVariaveis / $totalFat) * 100;

        // Atualiza configuração global
        $config = DadosFinanceiros::getConfiguracaoGlobal($usuarioId);
        $config->taxa_fixa_percentual = round($taxaFixaMedia, 2);
        $config->taxa_variavel_percentual = round($taxaVariavelMedia, 2);
        // Sugere margem de mercado razoável se estiver muito alta
        if ($config->lucro_liquido_percentual > 20) {
            $config->lucro_liquido_percentual = 15.00;
        }

        $config->save();

        Yii::$app->session->setFlash('success', "Configuração atualizada com base no histórico! Taxa Fixa: {$config->taxa_fixa_percentual}%, Variável: {$config->taxa_variavel_percentual}%");

        return $this->redirect(['/vendas/dados-financeiros/global']);
    }

    protected function findModel($id)
    {
        if (($model = FinanceiroMensal::findOne($id)) !== null) {
            if ($model->usuario_id !== Yii::$app->user->id) {
                throw new NotFoundHttpException('Você não tem permissão para acessar este item.');
            }
            return $model;
        }

        throw new NotFoundHttpException('Página não encontrada.');
    }
}
