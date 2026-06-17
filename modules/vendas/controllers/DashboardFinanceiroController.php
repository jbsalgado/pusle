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
use app\modules\caixa\models\Caixa;
use app\modules\caixa\helpers\CaixaHelper;
use app\modules\contas_pagar\models\ContaPagar;

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
            'fluxo_caixa' => $this->getFluxoCaixaMensal($usuarioId),
            'contas_pagar_status' => $this->getContasPagarPorStatus($usuarioId),
            'fluxo_projetado' => $this->getFluxoProjetado($usuarioId, 30),
        ];

        $alertas = $this->getAlertasFinanceiros($usuarioId);

        // Tabelas de próximas contas
        $contasPagar = $this->getProximasContasPagar($usuarioId, 10);
        $parcelasReceber = $this->getProximasParcelasReceber($usuarioId, 10);

        return $this->render('index', [
            'kpis' => $kpis,
            'charts' => $charts,
            'alertas' => $alertas,
            'contasPagar' => $contasPagar,
            'parcelasReceber' => $parcelasReceber,
        ]);
    }

    protected function getFinancialKPIs($usuarioId)
    {
        // KPIs de Vendas (existentes)
        $kpis = [
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

        // KPIs de Caixa (novos)
        $caixa = CaixaHelper::getCaixaAberto($usuarioId);
        $kpis['caixa_aberto'] = $caixa ? true : false;
        $kpis['saldo_caixa'] = $caixa ? $caixa->calcularValorEsperado() : 0;
        $kpis['caixa_id'] = $caixa ? $caixa->id : null;

        // KPIs de Contas a Pagar (novos)
        $kpis['contas_pagar_pendente'] = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId, 'status' => ContaPagar::STATUS_PENDENTE])
            ->sum('valor') ?: 0;

        $kpis['contas_pagar_vencidas'] = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['<', 'data_vencimento', date('Y-m-d')])
            ->andWhere(['status' => ContaPagar::STATUS_PENDENTE])
            ->sum('valor') ?: 0;

        $kpis['contas_pagar_vencidas_qtd'] = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['<', 'data_vencimento', date('Y-m-d')])
            ->andWhere(['status' => ContaPagar::STATUS_PENDENTE])
            ->count();

        $kpis['contas_pagar_proximos_7dias'] = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['>=', 'data_vencimento', date('Y-m-d')])
            ->andWhere(['<=', 'data_vencimento', date('Y-m-d', strtotime('+7 days'))])
            ->andWhere(['status' => ContaPagar::STATUS_PENDENTE])
            ->sum('valor') ?: 0;

        // Contas a Receber (Parcelas Pendentes)
        $kpis['contas_receber_pendente'] = Parcela::find()->alias('p')
            ->innerJoin('prest_vendas v', 'v.id = p.venda_id')
            ->where(['v.usuario_id' => $usuarioId])
            ->andWhere(['p.status_parcela_codigo' => StatusParcela::PENDENTE])
            ->sum('p.valor_parcela') ?: 0;

        return $kpis;
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

    /**
     * Novo: Fluxo de Caixa Mensal (Entradas x Saídas)
     */
    protected function getFluxoCaixaMensal($usuarioId)
    {
        // Entradas (vendas)
        $entradas = Yii::$app->db->createCommand("
            SELECT TO_CHAR(data_venda, 'YYYY-MM') as mes, SUM(valor_total) as total 
            FROM prest_vendas 
            WHERE usuario_id = :uid 
            GROUP BY mes ORDER BY mes DESC LIMIT 6
        ", [':uid' => $usuarioId])->queryAll();

        // Saídas (contas pagas)
        $saidas = Yii::$app->db->createCommand("
            SELECT TO_CHAR(data_pagamento, 'YYYY-MM') as mes, SUM(valor) as total 
            FROM prest_contas_pagar 
            WHERE usuario_id = :uid AND status = 'PAGA'
            GROUP BY mes ORDER BY mes DESC LIMIT 6
        ", [':uid' => $usuarioId])->queryAll();

        // Combina os dados
        $meses = [];
        foreach ($entradas as $e) {
            $meses[$e['mes']]['entradas'] = $e['total'];
        }
        foreach ($saidas as $s) {
            if (!isset($meses[$s['mes']])) {
                $meses[$s['mes']] = ['entradas' => 0];
            }
            $meses[$s['mes']]['saidas'] = $s['total'];
        }

        // Preenche saídas faltantes
        foreach ($meses as $mes => &$dados) {
            if (!isset($dados['saidas'])) {
                $dados['saidas'] = 0;
            }
        }

        krsort($meses); // Ordena por mês decrescente
        return $meses;
    }

    /**
     * Novo: Contas a Pagar por Status
     */
    protected function getContasPagarPorStatus($usuarioId)
    {
        $sql = "SELECT status, COUNT(*) as quantidade, SUM(valor) as total 
                FROM prest_contas_pagar 
                WHERE usuario_id = :uid 
                GROUP BY status";
        return Yii::$app->db->createCommand($sql, [':uid' => $usuarioId])->queryAll();
    }

    /**
     * Novo: Alertas Financeiros
     */
    protected function getAlertasFinanceiros($usuarioId)
    {
        $alertas = [];

        // Alerta: Caixa Fechado
        $caixa = CaixaHelper::getCaixaAberto($usuarioId);
        if (!$caixa) {
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Caixa Fechado',
                'mensagem' => 'Você não possui um caixa aberto. Abra um caixa para registrar movimentações.',
                'acao' => '/caixa/caixa/create',
                'acao_texto' => 'Abrir Caixa'
            ];
        }

        // Alerta: Saldo Baixo
        if ($caixa && $caixa->calcularValorEsperado() < 100) {
            $alertas[] = [
                'tipo' => 'danger',
                'titulo' => 'Saldo Baixo',
                'mensagem' => 'O saldo do seu caixa está abaixo de R$ 100,00.',
                'acao' => null,
                'acao_texto' => null
            ];
        }

        // Alerta: Contas Vencidas
        $vencidas = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['<', 'data_vencimento', date('Y-m-d')])
            ->andWhere(['status' => ContaPagar::STATUS_PENDENTE])
            ->count();

        if ($vencidas > 0) {
            $alertas[] = [
                'tipo' => 'danger',
                'titulo' => 'Contas Vencidas',
                'mensagem' => "Você possui {$vencidas} conta(s) vencida(s).",
                'acao' => '/contas-pagar/relatorio/vencidas',
                'acao_texto' => 'Ver Contas'
            ];
        }

        // Alerta: Vencimentos Próximos
        $proximos = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['>=', 'data_vencimento', date('Y-m-d')])
            ->andWhere(['<=', 'data_vencimento', date('Y-m-d', strtotime('+7 days'))])
            ->andWhere(['status' => ContaPagar::STATUS_PENDENTE])
            ->count();

        if ($proximos > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Vencimentos Próximos',
                'mensagem' => "Você possui {$proximos} conta(s) vencendo nos próximos 7 dias.",
                'acao' => '/contas-pagar/relatorio/a-vencer?dias=7',
                'acao_texto' => 'Ver Contas'
            ];
        }

        return $alertas;
    }

    /**
     * Fluxo de caixa projetado (próximos N dias)
     */
    protected function getFluxoProjetado($usuarioId, $dias = 30)
    {
        $projecao = [];
        $saldoAtual = $this->getSaldoCaixaAtual($usuarioId);

        for ($i = 0; $i <= $dias; $i++) {
            $data = date('Y-m-d', strtotime("+{$i} days"));
            $dataLabel = date('d/m', strtotime($data));

            // Entradas previstas (parcelas a receber)
            $entradas = Parcela::find()
                ->joinWith('venda')
                ->where(['prest_vendas.usuario_id' => $usuarioId])
                ->andWhere(['prest_parcelas.status_parcela_codigo' => 'PENDENTE'])
                ->andWhere(['prest_parcelas.data_vencimento' => $data])
                ->sum('valor_parcela') ?: 0;

            // Saídas previstas (contas a pagar)
            $saidas = ContaPagar::find()
                ->where(['usuario_id' => $usuarioId, 'status' => 'PENDENTE'])
                ->andWhere(['data_vencimento' => $data])
                ->sum('valor') ?: 0;

            $saldoAtual = $saldoAtual + $entradas - $saidas;

            $projecao[] = [
                'data' => $dataLabel,
                'entradas' => $entradas,
                'saidas' => $saidas,
                'saldo' => $saldoAtual,
            ];
        }

        return $projecao;
    }

    /**
     * Despesas por categoria
     */
    protected function getDespesasPorCategoria($usuarioId)
    {
        $inicio = date('Y-m-01');
        $fim = date('Y-m-t');

        return ContaPagar::find()
            ->select(['categoria', 'SUM(valor) as total'])
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['between', 'data_vencimento', $inicio, $fim])
            ->groupBy('categoria')
            ->asArray()
            ->all();
    }

    /**
     * Próximas contas a pagar
     */
    protected function getProximasContasPagar($usuarioId, $limit = 10)
    {
        return ContaPagar::find()
            ->where(['usuario_id' => $usuarioId, 'status' => 'PENDENTE'])
            ->andWhere(['<=', 'data_vencimento', date('Y-m-d', strtotime('+30 days'))])
            ->orderBy(['data_vencimento' => SORT_ASC])
            ->limit($limit)
            ->all();
    }

    /**
     * Próximas parcelas a receber
     */
    protected function getProximasParcelasReceber($usuarioId, $limit = 10)
    {
        return Parcela::find()
            ->joinWith('venda')
            ->where(['prest_vendas.usuario_id' => $usuarioId])
            ->andWhere(['prest_parcelas.status_parcela_codigo' => 'PENDENTE'])
            ->orderBy(['prest_parcelas.data_vencimento' => SORT_ASC])
            ->limit($limit)
            ->all();
    }

    /**
     * Saldo de caixa atual
     */
    protected function getSaldoCaixaAtual($usuarioId)
    {
        $caixa = Caixa::find()
            ->where(['usuario_id' => $usuarioId, 'status' => 'ABERTO'])
            ->one();

        return $caixa ? $caixa->calcularValorEsperado() : 0;
    }
}
