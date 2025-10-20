<?php

namespace app\modules\vendas;

/**
 * vendas module definition class
 */
class Vendas extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\vendas\controllers';
    public $defaultRoute = 'inicio'; // 🔥 Define dashboard como padrão

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
}
