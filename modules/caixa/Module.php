<?php
namespace app\modules\caixa;

/**
 * Módulo Caixa - Gestão de Fluxo de Caixa
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\caixa\controllers';

    /**
     * Controller padrão ao acessar /caixa
     */
    public $defaultRoute = 'caixa';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // Layout padrão do módulo (usa o mesmo do vendas)
        $this->layout = 'main';
        
        // Mapeamento de controllers com hífen
        $this->controllerMap = [
            'caixa' => 'app\modules\caixa\controllers\CaixaController',
            'movimentacao' => 'app\modules\caixa\controllers\MovimentacaoController',
        ];
    }
}

