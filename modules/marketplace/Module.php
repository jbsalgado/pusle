<?php

namespace app\modules\marketplace;

use Yii;

/**
 * Módulo de Integração com Marketplaces
 * 
 * Gerencia integrações com:
 * - Mercado Livre
 * - Shopee
 * - Magazine Luiza
 * - Amazon Brasil
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\marketplace\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // Configurações personalizadas do módulo
        $this->layout = 'main';
    }

    /**
     * Verifica se o módulo está habilitado
     * @return bool
     */
    public static function isEnabled()
    {
        return Yii::$app->params['marketplace']['enabled'] ?? false;
    }

    /**
     * Verifica se um marketplace específico está habilitado
     * @param string $marketplace Nome do marketplace (mercado_livre, shopee, etc)
     * @return bool
     */
    public static function isMarketplaceEnabled($marketplace)
    {
        if (!self::isEnabled()) {
            return false;
        }

        return Yii::$app->params['marketplace'][$marketplace] ?? false;
    }
}
