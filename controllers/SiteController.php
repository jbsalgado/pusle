<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->isGuest) {
            $usuario = Yii::$app->user->identity;
            if ($usuario->is_admin && !$usuario->eh_dono_loja) {
                return $this->redirect(['/admin/loja/index']);
            }
            return $this->redirect(['/vendas/inicio']);
        }
        return $this->redirect(['/loja-cadastro/index']);
    }
}

