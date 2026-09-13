<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\HistoricoCobranca;
use app\modules\vendas\models\Venda;

/**
 * Gestão e Apuração de Comissões de Venda e Cobrança
 */
class ComissaoController extends Controller
{
    public function actionIndex($mes = null)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        $mesFiltro = $mes ?: date('Y-m');
        $inicioMes = $mesFiltro . '-01 00:00:00';
        $fimMes = date('Y-m-t 23:59:59', strtotime($inicioMes));

        $colaboradores = Colaborador::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->all();

        $dadosComissao = [];
        foreach ($colaboradores as $c) {
            // Vendas do vendedor no mês
            $totalVendido = (float)Venda::find()
                ->where(['usuario_id' => $usuarioId, 'colaborador_vendedor_id' => $c->id])
                ->andWhere(['>=', 'data_venda', $inicioMes])
                ->andWhere(['<=', 'data_venda', $fimMes])
                ->sum('valor_total') ?? 0;

            // Cobranças recebidas pelo cobrador no mês
            $totalCobrado = (float)HistoricoCobranca::find()
                ->where(['usuario_id' => $usuarioId, 'cobrador_id' => $c->id, 'tipo_acao' => HistoricoCobranca::TIPO_PAGAMENTO])
                ->andWhere(['>=', 'data_acao', $inicioMes])
                ->andWhere(['<=', 'data_acao', $fimMes])
                ->sum('valor_recebido') ?? 0;

            // Estimativa de comissão (ex: 5% padrão se não configurado)
            $comissaoVenda = $totalVendido * 0.05;
            $comissaoCobranca = $totalCobrado * 0.05;

            $dadosComissao[] = [
                'colaborador' => $c,
                'totalVendido' => $totalVendido,
                'totalCobrado' => $totalCobrado,
                'comissaoVenda' => $comissaoVenda,
                'comissaoCobranca' => $comissaoCobranca,
                'totalComissao' => $comissaoVenda + $comissaoCobranca,
            ];
        }

        return $this->render('index', [
            'dadosComissao' => $dadosComissao,
            'mesFiltro' => $mesFiltro,
        ]);
    }
}
