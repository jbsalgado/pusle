<?php

namespace app\modules\api\controllers;

use Yii;
use app\modules\vendas\models\TaxaEntrega;
use yii\web\Response;

class FreteController extends BaseController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['optional'] = ['calcular'];
        return $behaviors;
    }

    /**
     * Endpoint para calcular a taxa de entrega baseada em geolocalização textual (CEP, Bairro, Cidade)
     * GET /api/frete/calcular?usuario_id=...&cep=...&bairro=...&cidade=...
     */
    public function actionCalcular()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $usuarioId = Yii::$app->request->get('usuario_id');
        $cep = Yii::$app->request->get('cep');
        $bairro = Yii::$app->request->get('bairro');
        $cidade = Yii::$app->request->get('cidade');
        $porte = Yii::$app->request->get('porte', 'P'); // Padrão Pequeno

        if (!$usuarioId) {
            return [
                'success' => false,
                'message' => 'usuario_id não informado'
            ];
        }

        $regra = TaxaEntrega::findRegra($usuarioId, $cidade, $bairro, $cep, $porte);

        return [
            'success' => true,
            'valor' => $regra ? (float)$regra->valor : 0.00,
            'valor_minimo_frete_gratis' => $regra ? ($regra->valor_minimo_frete_gratis ? (float)$regra->valor_minimo_frete_gratis : null) : null,
            'regra_id' => $regra ? $regra->id : null,
            'params' => [
                'cep' => $cep,
                'bairro' => $bairro,
                'cidade' => $cidade
            ]
        ];
    }
}
