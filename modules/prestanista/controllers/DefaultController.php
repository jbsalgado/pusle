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
        $usuarioId = $usuario->loja_id ?? $usuario->id;
        $hoje = date('Y-m-d');
        $inicioMes = date('Y-m-01');
        $fimMes = date('Y-m-t');

        // Métricas de Parcelas e Cartões
        $queryParcelas = Parcela::find()->where(['usuario_id' => $usuarioId]);

        // Total A Receber em Aberto
        $totalAReceber = (float)Parcela::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['!=', 'status_parcela_codigo', 'PAGA'])
            ->andWhere(['!=', 'status_parcela_codigo', 'CANCELADA'])
            ->sum('valor_parcela') ?? 0;

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
        $totalCartoes = Venda::find()->where(['usuario_id' => $usuarioId])->count();
        $cartoesAtivos = Venda::find()->where(['usuario_id' => $usuarioId, 'status_venda_codigo' => 'EM_ABERTO'])->count();
        $cartoesQuitados = Venda::find()->where(['usuario_id' => $usuarioId, 'status_venda_codigo' => 'FINALIZADA'])->count();

        // Parcelas Atrasadas
        $parcelasAtrasadasQtd = Parcela::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['<', 'data_vencimento', $hoje])
            ->andWhere(['!=', 'status_parcela_codigo', 'PAGA'])
            ->andWhere(['!=', 'status_parcela_codigo', 'CANCELADA'])
            ->count();

        $valorAtrasado = (float)Parcela::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['<', 'data_vencimento', $hoje])
            ->andWhere(['!=', 'status_parcela_codigo', 'PAGA'])
            ->andWhere(['!=', 'status_parcela_codigo', 'CANCELADA'])
            ->sum('valor_parcela') ?? 0;

        // Últimos pagamentos registrados
        $ultimosPagamentos = HistoricoCobranca::find()
            ->where(['usuario_id' => $usuarioId, 'tipo_acao' => HistoricoCobranca::TIPO_PAGAMENTO])
            ->with(['cliente', 'cobrador', 'parcela'])
            ->orderBy(['id' => SORT_DESC])
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
            'cartoesQuitados' => $cartoesQuitados,
            'parcelasAtrasadasQtd' => $parcelasAtrasadasQtd,
            'valorAtrasado' => $valorAtrasado,
            'ultimosPagamentos' => $ultimosPagamentos,
            'cobradores' => $cobradores,
        ]);
    }
}
