<?php
namespace app\modules\vendas;

/**
 * Módulo Vendas
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\vendas\controllers';

    /**
     * Controller padrão ao acessar /vendas
     */
    public $defaultRoute = 'inicio';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // Layout padrão do módulo
        $this->layout = 'main';
    }
}