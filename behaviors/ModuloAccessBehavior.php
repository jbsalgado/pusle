<?php
/**
 * ModuloAccessBehavior - Verifica se usuário tem acesso ao módulo
 * Localização: app/behaviors/ModuloAccessBehavior.php
 * 
 * Adicione este behavior nos controllers dos módulos para verificar acesso
 */

namespace app\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\Controller;
use yii\web\ForbiddenHttpException;
use app\controllers\DashboardController;

/**
 * Behavior para verificar acesso a módulos
 * 
 * Uso nos controllers dos módulos:
 * 
 * public function behaviors()
 * {
 *     return [
 *         'moduloAccess' => [
 *             'class' => ModuloAccessBehavior::class,
 *             'moduloCodigo' => 'vendas', // Código do módulo
 *         ],
 *     ];
 * }
 */
class ModuloAccessBehavior extends Behavior
{
    /**
     * @var string Código do módulo a verificar
     */
    public $moduloCodigo;

    /**
     * @var array Actions que NÃO precisam de verificação
     */
    public $except = [];

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }

    /**
     * Executa antes de cada action
     */
    public function beforeAction($event)
    {
        // Se a action está na lista de exceções, não verifica
        if (in_array($event->action->id, $this->except)) {
            return true;
        }

        // Se usuário não estiver logado, deixa o AccessControl padrão tratar
        if (Yii::$app->user->isGuest) {
            return true;
        }

        // Verifica se o código do módulo foi definido
        if (empty($this->moduloCodigo)) {
            throw new \yii\base\InvalidConfigException('A propriedade "moduloCodigo" deve ser definida.');
        }

        // Verifica se o usuário tem acesso ao módulo
        $usuarioId = Yii::$app->user->id;
        $temAcesso = DashboardController::verificarAcessoModulo($usuarioId, $this->moduloCodigo);

        if (!$temAcesso) {
            // Redireciona para o dashboard com mensagem
            Yii::$app->session->setFlash('error', 'Você não tem acesso ao módulo ' . $this->moduloCodigo . '. Assine um plano para ter acesso.');
            Yii::$app->controller->redirect(['/dashboard/index']);
            return false;
        }

        return true;
    }
}