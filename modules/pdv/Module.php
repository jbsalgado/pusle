<?php

namespace app\modules\pdv;

use Yii;

/**
 * Módulo PDV - Frente de Caixa com Leitor de Código de Barras e Balança
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\pdv\controllers';

    public $defaultRoute = 'frente/index';

    public function init()
    {
        parent::init();
        $this->layout = false;
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (Yii::$app->user->isGuest) {
            Yii::$app->response->redirect(['/auth/login'])->send();
            return false;
        }

        return true;
    }
}

