<?php

namespace app\modules\evolution;

/**
 * Módulo de integração com a Evolution API Go (Engine v0.7.1).
 *
 * Responsável por gerenciar conexões WhatsApp individuais por empresa/tenant,
 * expondo ao PULSE-PLUS a capacidade de parear dispositivos via QR Code e
 * disparar notificações automatizadas de retaguarda.
 *
 * Registro em config/web.php:
 *   'evolution' => ['class' => 'app\modules\evolution\Module'],
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\evolution\controllers';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
    }
}
