<?php

namespace app\modules\contas_pagar;

/**
 * Módulo Contas a Pagar - Gestão de Contas a Pagar
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\contas_pagar\controllers';

    /**
     * Controller padrão ao acessar /contas-pagar
     */
    public $defaultRoute = 'conta-pagar';

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
            'conta-pagar' => 'app\modules\contas_pagar\controllers\ContaPagarController',
            'relatorio' => 'app\modules\contas_pagar\controllers\RelatorioController',
        ];
    }
}
