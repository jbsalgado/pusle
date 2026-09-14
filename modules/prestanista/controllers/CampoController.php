<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use app\modules\vendas\models\Colaborador;

/**
 * CampoController - Hub Seletor de Turno para Colaboradores de Campo Prestanista
 */
class CampoController extends Controller
{
    public $layout = false; // Layout mobile app dedicado

    public function actionIndex()
    {
        $usuario = Yii::$app->user->identity;
        if (!$usuario) {
            return $this->redirect(['/auth/login']);
        }

        $colaborador = $usuario->colaborador;
        $ehDono = $usuario->eh_dono_loja || $usuario->is_admin;

        // Se for vendedor exclusivo, redireciona direto
        if (!$ehDono && $usuario->isVendedorAmbulante() && !$usuario->isCobradorRua()) {
            return $this->redirect(['/prestanista/vendedor/index']);
        }

        // Se for cobrador exclusivo, redireciona direto
        if (!$ehDono && $usuario->isCobradorRua() && !$usuario->isVendedorAmbulante()) {
            return $this->redirect(['/prestanista/cobrador/index']);
        }

        $nomeLoja = $usuario->nome_loja ?? 'Pulse Prestanista';

        return $this->render('index', [
            'usuario' => $usuario,
            'colaborador' => $colaborador,
            'ehDono' => $ehDono,
            'nomeLoja' => $nomeLoja,
        ]);
    }
}
