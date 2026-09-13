<?php

namespace app\modules\prestanista;

use Yii;
use yii\web\ForbiddenHttpException;
use app\modules\vendas\models\LojaPermissao;

/**
 * Módulo Prestanista - Gestão de Vendas e Crediário Ambulante de Porta em Porta
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\prestanista\controllers';

    public $defaultRoute = 'default/index';

    public function init()
    {
        parent::init();
        $this->layout = 'main';
    }

    /**
     * Verifica permissão de acesso ao módulo antes de qualquer ação
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Ações públicas e de campo liberadas (vendedores e cobradores de rua)
        $controllerId = $action->controller->id;
        $actionId = $action->id;
        $rotaAtual = "{$controllerId}/{$actionId}";
        $rotasLiberadas = [
            'cartao/publico',
            'vendedor/index',
            'vendedor/dados-iniciais',
            'vendedor/sincronizar',
            'cobrador/index',
            'cobrador/dados-rota',
            'cobrador/sincronizar',
            'cobrador/salvar-ordem-rota',
        ];
        if (in_array($rotaAtual, $rotasLiberadas)) {
            return true;
        }

        // Se usuário não autenticado, redireciona para login
        if (Yii::$app->user->isGuest) {
            Yii::$app->user->loginRequired();
            return false;
        }

        // Se o usuário logado pertence a uma loja, verifica se o módulo prestanista está liberado
        $usuario = Yii::$app->user->identity;
        $lojaId = $usuario->loja_id ?? $usuario->id ?? null;

        if ($lojaId && class_exists(LojaPermissao::class)) {
            if (!LojaPermissao::temPermissao('modulo-prestanista', $lojaId)) {
                // Se não tiver permissão no SaaS Admin, exibe mensagem e redireciona
                Yii::$app->session->setFlash('warning', 'O Módulo Prestanista não está habilitado para a sua loja. Solicite a liberação ao administrador.');
                Yii::$app->response->redirect(['/vendas/inicio'])->send();
                return false;
            }
        }

        return true;
    }
}
