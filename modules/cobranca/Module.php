<?php

namespace app\modules\cobranca;

use Yii;

/**
 * Módulo de Automação de Cobranças
 * 
 * Responsável por:
 * - Integração com WhatsApp (Z-API)
 * - Envio automático de lembretes de pagamento
 * - Gestão de templates de mensagens
 * - Histórico de cobranças enviadas
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\cobranca\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // Configurações do módulo
        Yii::setAlias('@cobranca', __DIR__);
    }
}
