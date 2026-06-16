<?php

namespace app\modules\admin;

use Yii;
use yii\base\Module as BaseModule;

/**
 * Módulo Admin — Painel de administração do SaaS PULSE.
 *
 * Acesso restrito a usuários com is_admin = true.
 * Registrar em config/web.php:
 *   'admin' => ['class' => 'app\modules\admin\Module'],
 */
class Module extends BaseModule
{
    /** @var string Layout do módulo admin */
    public $layout = 'main';

    public function init()
    {
        parent::init();
        $this->layoutPath = '@app/modules/admin/views/layouts';
    }

    /**
     * Garante que apenas admins acessem este módulo.
     * Chamado pelo beforeAction via AccessControl nos controllers.
     */
    public static function verificarAcesso(): void
    {
        if (Yii::$app->user->isGuest) {
            Yii::$app->response->redirect(['/auth/login'])->send();
            exit;
        }

        if (!\app\components\TenantHelper::isAdmin()) {
            throw new \yii\web\ForbiddenHttpException('Acesso restrito ao administrador do sistema.');
        }
    }
}
