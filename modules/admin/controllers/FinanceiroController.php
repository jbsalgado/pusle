<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use app\models\Usuario;
use app\modules\admin\models\SaasPlano;
use app\modules\admin\models\SaasLojaConfig;
use app\modules\admin\models\SaasFatura;
use app\modules\admin\models\SaasConfigGlobal;
use app\modules\admin\services\SaasBillingService;

/**
 * FinanceiroController - Gestão Financeira, Monetização e Planos do SaaS para o Superadmin
 */
class FinanceiroController extends Controller
{
    public $layout = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return \app\components\TenantHelper::isAdmin();
                        },
                    ],
                ],
                'denyCallback' => function () {
                    if (Yii::$app->user->isGuest) {
                        return Yii::$app->response->redirect(['/auth/login']);
                    }
                    throw new ForbiddenHttpException('Acesso restrito ao administrador do SaaS.');
                },
            ],
        ];
    }

    /**
     * Dashboard Financeiro Global do SaaS
     */
    public function actionIndex()
    {
        $mesAtual = date('Y-m');
        $mesAnterior = date('Y-m', strtotime('first day of last month'));

        // Métricas de faturas
        $totalLojas = Usuario::find()->where(['eh_dono_loja' => true])->count();
        $totalFaturadoMes = SaasFatura::find()->where(['mes_referencia' => $mesAnterior])->sum('valor_total') ?: 0;
        $totalRecebidoMes = SaasFatura::find()->where(['mes_referencia' => $mesAnterior, 'status' => SaasFatura::STATUS_PAGA])->sum('valor_total') ?: 0;
        $totalPendenteMes = SaasFatura::find()->where(['mes_referencia' => $mesAnterior, 'status' => [SaasFatura::STATUS_PENDENTE, SaasFatura::STATUS_ATRASADA]])->sum('valor_total') ?: 0;
        
        $gmvMarketplaceTotal = SaasFatura::find()->where(['mes_referencia' => $mesAnterior])->sum('gmv_marketplace') ?: 0;
        $gmvCatalogoTotal = SaasFatura::find()->where(['mes_referencia' => $mesAnterior])->sum('gmv_catalogo') ?: 0;

        // Faturas recentes
        $ultimasFaturas = SaasFatura::find()
            ->with(['usuario'])
            ->orderBy(['data_fechamento' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->render('index', [
            'totalLojas' => $totalLojas,
            'totalFaturadoMes' => $totalFaturadoMes,
            'totalRecebidoMes' => $totalRecebidoMes,
            'totalPendenteMes' => $totalPendenteMes,
            'gmvMarketplaceTotal' => $gmvMarketplaceTotal,
            'gmvCatalogoTotal' => $gmvCatalogoTotal,
            'mesAnterior' => $mesAnterior,
            'ultimasFaturas' => $ultimasFaturas,
        ]);
    }

    /**
     * Relatório de Faturas com filtros
     */
    public function actionFaturas()
    {
        $status = Yii::$app->request->get('status', 'todos');
        $mes = Yii::$app->request->get('mes', '');

        $query = SaasFatura::find()->with(['usuario'])->orderBy(['data_fechamento' => SORT_DESC]);

        if ($status !== 'todos' && $status !== '') {
            $query->andWhere(['status' => $status]);
        }
        if ($mes !== '') {
            $query->andWhere(['mes_referencia' => $mes]);
        }

        $faturas = $query->all();

        return $this->render('faturas', [
            'faturas' => $faturas,
            'status' => $status,
            'mes' => $mes,
        ]);
    }

    /**
     * Visualização detalhada de uma fatura
     */
    public function actionFaturaView($id)
    {
        $fatura = SaasFatura::find()->with(['usuario'])->where(['id' => $id])->one();
        if (!$fatura) {
            throw new NotFoundHttpException('Fatura não encontrada.');
        }

        $lojaConfig = SaasLojaConfig::getOrCreateForUser($fatura->usuario_id);

        return $this->render('fatura_view', [
            'fatura' => $fatura,
            'lojaConfig' => $lojaConfig,
        ]);
    }

    /**
     * Liquidação / Baixa manual de fatura
     */
    public function actionFaturaPagarManual($id)
    {
        $fatura = SaasFatura::findOne($id);
        if (!$fatura) {
            throw new NotFoundHttpException('Fatura não encontrada.');
        }

        $fatura->status = SaasFatura::STATUS_PAGA;
        $fatura->data_pagamento = date('Y-m-d H:i:s');
        $fatura->metodo_pagamento = 'BAIXA_MANUAL_ADMIN';
        $fatura->save(false);

        // Atualiza status da loja para adimplente
        $config = SaasLojaConfig::getOrCreateForUser($fatura->usuario_id);
        if ($config->status_cobranca === SaasLojaConfig::STATUS_INADIMPLENTE || $config->status_cobranca === SaasLojaConfig::STATUS_BLOQUEADO) {
            $config->status_cobranca = SaasLojaConfig::STATUS_ADIMPLENTE;
            $config->save(false);
        }

        Yii::$app->session->setFlash('success', 'Fatura marcada como PAGA com sucesso!');
        return $this->redirect(['fatura-view', 'id' => $id]);
    }

    /**
     * Gestão de Planos Comerciais
     */
    public function actionPlanos()
    {
        $planos = SaasPlano::find()->all();
        return $this->render('planos', [
            'planos' => $planos,
        ]);
    }

    /**
     * Criar / Editar Plano
     */
    public function actionPlanoForm($id = null)
    {
        $model = $id ? SaasPlano::findOne($id) : new SaasPlano();
        if (!$model) {
            throw new NotFoundHttpException('Plano não encontrado.');
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Plano salvo com sucesso!');
            return $this->redirect(['planos']);
        }

        return $this->render('plano_form', [
            'model' => $model,
        ]);
    }

    /**
     * Configurações Globais e Chaves Master do SaaS
     */
    public function actionConfig()
    {
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post('Config', []);
            foreach ($post as $chave => $valor) {
                SaasConfigGlobal::setValor($chave, $valor);
            }
            Yii::$app->session->setFlash('success', 'Configurações globais do SaaS atualizadas!');
            return $this->redirect(['config']);
        }

        $configs = SaasConfigGlobal::find()->all();
        $map = [];
        foreach ($configs as $c) {
            $map[$c->chave] = $c->valor;
        }

        return $this->render('config', [
            'configs' => $map,
        ]);
    }

    /**
     * Disparo manual do fechamento de faturas
     */
    public function actionGerarFaturas()
    {
        $mes = Yii::$app->request->post('mes', date('Y-m', strtotime('first day of last month')));
        $service = new SaasBillingService();
        $faturas = $service->fecharMesGlobal($mes);

        Yii::$app->session->setFlash('success', count($faturas) . " faturas geradas/atualizadas com sucesso para o mês de {$mes}!");
        return $this->redirect(['faturas', 'mes' => $mes]);
    }
}
