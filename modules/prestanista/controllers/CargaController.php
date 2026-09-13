<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Colaborador;

/**
 * Gestão de Carga do Carrinho / Estoque Consignado dos Ambulantes
 */
class CargaController extends Controller
{
    public function actionIndex()
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        $produtos = Produto::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        $vendedores = Colaborador::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->all();

        return $this->render('index', [
            'produtos' => $produtos,
            'vendedores' => $vendedores,
        ]);
    }
}
