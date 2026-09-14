<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\HistoricoCobranca;
use app\modules\vendas\models\Colaborador;

/**
 * Controller principal / Dashboard do Módulo Prestanista
 */
class DefaultController extends Controller
{
    public function actionIndex()
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;
        $hoje = date('Y-m-d');
        $inicioMes = date('Y-m-01');
        $fimMes = date('Y-m-t');

        // Total A Receber em Aberto (Apenas Vendas Prestanistas / Crediário)
        $totalAReceber = (float)Parcela::findPrestanista($usuarioId)
            ->andWhere(['!=', 'p.status_parcela_codigo', 'PAGA'])
            ->andWhere(['!=', 'p.status_parcela_codigo', 'CANCELADA'])
            ->sum('p.valor_parcela') ?? 0;

        // Arrecadado Hoje
        $recebidoHoje = (float)HistoricoCobranca::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['tipo_acao' => HistoricoCobranca::TIPO_PAGAMENTO])
            ->andWhere(['>=', 'data_acao', $hoje . ' 00:00:00'])
            ->andWhere(['<=', 'data_acao', $hoje . ' 23:59:59'])
            ->sum('valor_recebido') ?? 0;

        // Arrecadado no Mês
        $recebidoMes = (float)HistoricoCobranca::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['tipo_acao' => HistoricoCobranca::TIPO_PAGAMENTO])
            ->andWhere(['>=', 'data_acao', $inicioMes . ' 00:00:00'])
            ->andWhere(['<=', 'data_acao', $fimMes . ' 23:59:59'])
            ->sum('valor_recebido') ?? 0;

        // Total de Cartões (Vendas de Crediário / Prestanista)
        $totalCartoes = (int)Venda::findPrestanista($usuarioId)->count();
        $cartoesAtivos = (int)Venda::findPrestanista($usuarioId)->andWhere(['v.status_venda_codigo' => ['EM_ABERTO', 'PARCIALMENTE_PAGA']])->count();
        $cartoesQuitados = (int)Venda::findPrestanista($usuarioId)->andWhere(['v.status_venda_codigo' => ['FINALIZADA', 'QUITADA']])->count();

        // Cartões atribuídos a cobradores de rua vs Cartões pendentes de atribuição
        $cartoesEmRota = (int)Venda::findPrestanista($usuarioId)
            ->andWhere(['v.status_venda_codigo' => ['EM_ABERTO', 'PARCIALMENTE_PAGA']])
            ->andWhere([
                'exists',
                (new \yii\db\Query())
                    ->from('prest_parcelas pp_cob')
                    ->where('pp_cob.venda_id = v.id')
                    ->andWhere(['not', ['pp_cob.cobrador_id' => null]])
                    ->andWhere(['!=', 'pp_cob.status_parcela_codigo', 'PAGA'])
                    ->andWhere(['!=', 'pp_cob.status_parcela_codigo', 'CANCELADA'])
            ])
            ->count();
        $cartoesSemCobrador = max(0, $cartoesAtivos - $cartoesEmRota);

        // Parcelas Atrasadas (Apenas Vendas Prestanistas)
        $parcelasAtrasadasQtd = (int)Parcela::findPrestanista($usuarioId)
            ->andWhere(['<', 'p.data_vencimento', $hoje])
            ->andWhere(['!=', 'p.status_parcela_codigo', 'PAGA'])
            ->andWhere(['!=', 'p.status_parcela_codigo', 'CANCELADA'])
            ->count();

        $valorAtrasado = (float)Parcela::findPrestanista($usuarioId)
            ->andWhere(['<', 'p.data_vencimento', $hoje])
            ->andWhere(['!=', 'p.status_parcela_codigo', 'PAGA'])
            ->andWhere(['!=', 'p.status_parcela_codigo', 'CANCELADA'])
            ->sum('p.valor_parcela') ?? 0;

        // Últimos pagamentos registrados
        $ultimosPagamentos = HistoricoCobranca::find()
            ->where(['usuario_id' => $usuarioId, 'tipo_acao' => HistoricoCobranca::TIPO_PAGAMENTO])
            ->with(['cliente', 'cobrador', 'parcela'])
            ->orderBy(['data_acao' => SORT_DESC])
            ->limit(8)
            ->all();

        // Cobradores Ativos
        $cobradores = Colaborador::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->all();

        return $this->render('index', [
            'totalAReceber' => $totalAReceber,
            'recebidoHoje' => $recebidoHoje,
            'recebidoMes' => $recebidoMes,
            'totalCartoes' => $totalCartoes,
            'cartoesAtivos' => $cartoesAtivos,
            'cartoesEmRota' => $cartoesEmRota,
            'cartoesSemCobrador' => $cartoesSemCobrador,
            'cartoesQuitados' => $cartoesQuitados,
            'parcelasAtrasadasQtd' => $parcelasAtrasadasQtd,
            'valorAtrasado' => $valorAtrasado,
            'ultimosPagamentos' => $ultimosPagamentos,
            'cobradores' => $cobradores,
        ]);
    }
}
