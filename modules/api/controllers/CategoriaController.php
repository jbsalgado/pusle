<?php

namespace app\modules\api\controllers;

use yii\rest\Controller;
use app\modules\vendas\models\Categoria;
use yii\web\Response;
use yii\web\BadRequestHttpException;

class CategoriaController extends BaseController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
        ];
    }

    /**
     * Lista todas as categorias ativas para a loja.
     * GET /api/categoria?usuario_id=xxx
     */
    public function actionIndex()
    {
        // Pega o usuario_id da query string
        $usuarioId = \Yii::$app->request->get('usuario_id');

        // Se não informar usuario_id, retorna vazio (segurança multi-tenancy)
        if (!$usuarioId) {
            return $this->success([]);
        }

        $categorias = Categoria::find()
            ->where(['ativo' => true, 'usuario_id' => $usuarioId])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        return $this->success($categorias);
    }
}
