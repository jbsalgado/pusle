<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Comissao;
use app\modules\vendas\models\AsaasCobrancas;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\StatusParcela;

class DashboardFinanceiroController extends Controller
{
    public $layout = 'main';

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

    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        $kpis = $this->getFinancialKPIs($usuarioId);
        $charts = [
            'receita_mensal' => $this->getReceitaMensal($usuarioId),
            'comissoes_status' => $this->getComissoesPorStatus($usuarioId),
            'taxas_plataforma' => $this->getTaxasPlataformaMensais($usuarioId),
        ];

        return $this->render('index', [
            'kpis' => $kpis,
            'charts' => $charts,
        ]);
    }

    protected function getFinancialKPIs($usuarioId)
    {
        return [
            'receita_total' => Venda::find()->where(['usuario_id' => $usuarioId])->sum('valor_total') ?: 0,
            'comissoes_pendentes' => Comissao::find()->where(['usuario_id' => $usuarioId, 'status' => Comissao::STATUS_PENDENTE])->sum('valor_comissao') ?: 0,
            'valor_recebido_asaas' => AsaasCobrancas::find()->where(['usuario_id' => $usuarioId, 'status' => 'RECEIVED'])->sum('valor_recebido') ?: 0,
            'taxas_plataforma' => Yii::$app->db->createCommand("
                SELECT SUM(platform_fee) FROM saas_financial_logs 
                WHERE tenant_id = :uid AND (status = 'approved' OR status = 'received' OR status = 'confirmed')
            ", [':uid' => $usuarioId])->queryScalar() ?: 0,
            'inadimplencia' => Parcela::find()->alias('p')
                ->innerJoin('prest_vendas v', 'v.id = p.venda_id')
                ->where(['v.usuario_id' => $usuarioId])
                ->andWhere(['p.status_parcela_codigo' => StatusParcela::ATRASADA])
                ->sum('p.valor_parcela') ?: 0,
        ];
    }

    protected function getReceitaMensal($usuarioId)
    {
        $sql = "SELECT TO_CHAR(data_venda, 'YYYY-MM') as mes, SUM(valor_total) as total 
                FROM prest_vendas 
                WHERE usuario_id = :uid 
                GROUP BY mes ORDER BY mes DESC LIMIT 12";
        return Yii::$app->db->createCommand($sql, [':uid' => $usuarioId])->queryAll();
    }

    protected function getComissoesPorStatus($usuarioId)
    {
        $sql = "SELECT status, SUM(valor_comissao) as total 
                FROM prest_comissoes 
                WHERE usuario_id = :uid 
                GROUP BY status";
        return Yii::$app->db->createCommand($sql, [':uid' => $usuarioId])->queryAll();
    }

    protected function getTaxasPlataformaMensais($usuarioId)
    {
        $sql = "SELECT TO_CHAR(created_at, 'YYYY-MM') as mes, SUM(platform_fee) as total 
                FROM saas_financial_logs 
                WHERE tenant_id = :uid AND (status = 'approved' OR status = 'received' OR status = 'confirmed')
                GROUP BY mes ORDER BY mes DESC LIMIT 12";
        return Yii::$app->db->createCommand($sql, [':uid' => $usuarioId])->queryAll();
    }
}
