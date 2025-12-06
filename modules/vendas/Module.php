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
        
        // Mapeamento de controllers com hífen para garantir que funcionem corretamente
        $this->controllerMap = [
            'forma-pagamento' => 'app\modules\vendas\controllers\FormaPagamentoController',
            'carteira-cobranca' => 'app\modules\vendas\controllers\CarteiraCobrancaController',
            'rota-cobranca' => 'app\modules\vendas\controllers\RotaCobrancaController',
            'historico-cobranca' => 'app\modules\vendas\controllers\HistoricoCobrancaController',
            'periodo-cobranca' => 'app\modules\vendas\controllers\PeriodoCobrancaController',
        ];
    }
}