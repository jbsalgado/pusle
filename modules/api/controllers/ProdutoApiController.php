<?php

namespace app\modules\api\controllers;

use Yii;
use yii\web\Response;
use app\modules\vendas\models\Produto;

/**
 * API Controller para consulta de produtos no módulo Prestanista
 */
class ProdutoApiController extends BaseController
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    /**
     * GET /api/produto-api/buscar?q=termo&usuario_id=uuid
     * Busca produtos por nome ou código de referência
     */
    public function actionBuscar()
    {
        $q = Yii::$app->request->get('q');
        $usuarioId = Yii::$app->request->get('usuario_id');

        if (!$usuarioId) {
            Yii::$app->response->statusCode = 400;
            return ['erro' => 'usuario_id é obrigatório'];
        }

        $query = Produto::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true]);

        if (!empty($q)) {
            $query->andWhere([
                'or',
                ['ilike', 'nome', $q],
                ['ilike', 'codigo_referencia', $q]
            ]);
        }

        $produtos = $query->limit(20)->all();

        return [
            'sucesso' => true,
            'produtos' => array_map(function ($p) {
                return [
                    'id' => $p->id,
                    'nome' => $p->nome,
                    'preco' => $p->preco_venda_sugerido,
                    'codigo' => $p->codigo_referencia
                ];
            }, $produtos)
        ];
    }
}
