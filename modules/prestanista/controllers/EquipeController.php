<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\RotaCobranca;

/**
 * Gestão de Equipes Prestanistas: Vendedores Ambulantes e Cobradores de Rua
 */
class EquipeController extends Controller
{
    public function actionIndex()
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        $colaboradores = Colaborador::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->all();

        $rotas = RotaCobranca::find()
            ->where(['usuario_id' => $usuarioId])
            ->all();

        $lojaNome = $usuario ? ($usuario->nome_loja ?? $usuario->nome ?? 'Pulse Prestanista') : 'Pulse Prestanista';

        return $this->render('index', [
            'colaboradores' => $colaboradores,
            'rotas' => $rotas,
            'lojaNome' => $lojaNome,
        ]);
    }
}
