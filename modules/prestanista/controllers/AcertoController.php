<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use app\modules\vendas\models\HistoricoCobranca;
use app\modules\vendas\models\Colaborador;

/**
 * Fechamento de Caixa e Acerto Diário com os Cobradores
 */
class AcertoController extends Controller
{
    public function actionIndex($data = null, $cobrador_id = null)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario->loja_id ?? $usuario->id;

        $dataFiltro = $data ?: date('Y-m-d');

        $query = HistoricoCobranca::find()
            ->where(['usuario_id' => $usuarioId, 'tipo_acao' => HistoricoCobranca::TIPO_PAGAMENTO])
            ->andWhere(['>=', 'data_acao', $dataFiltro . ' 00:00:00'])
            ->andWhere(['<=', 'data_acao', $dataFiltro . ' 23:59:59'])
            ->with(['cliente', 'cobrador', 'parcela'])
            ->orderBy(['data_acao' => SORT_DESC]);

        if ($cobrador_id) {
            $query->andWhere(['cobrador_id' => $cobrador_id]);
        }

        $pagamentos = $query->all();
        $totalArrecadado = 0;
        foreach ($pagamentos as $p) {
            $totalArrecadado += (float)$p->valor_recebido;
        }

        $cobradores = Colaborador::find()->where(['usuario_id' => $usuarioId, 'ativo' => true])->all();

        return $this->render('index', [
            'pagamentos' => $pagamentos,
            'totalArrecadado' => $totalArrecadado,
            'dataFiltro' => $dataFiltro,
            'cobrador_id' => $cobrador_id,
            'cobradores' => $cobradores,
        ]);
    }
}
