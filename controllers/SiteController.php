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

        // Usuário não logado → vai para a vitrine pública de lojas (SaaS)
        // O cadastro de nova loja está disponível via botão na própria vitrine
        return $this->redirect(Yii::$app->request->baseUrl . '/catalogo/lojas.html');
    }
}

