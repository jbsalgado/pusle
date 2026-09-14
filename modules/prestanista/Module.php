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

        $controllerId = $action->controller->id;
        $actionId = $action->id;
        $rotaAtual = "{$controllerId}/{$actionId}";

        // 1. Rotas estritamente públicas (ex: cliente final consultando seu cartão via link/WhatsApp)
        if ($rotaAtual === 'cartao/publico') {
            return true;
        }

        // 2. APIs de sincronização offline de campo
        $rotasApiOffline = [
            'vendedor/sincronizar',
            'vendedor/dados-iniciais',
            'cobrador/sincronizar',
            'cobrador/dados-rota',
            'cobrador/salvar-ordem-rota',
        ];

        // Se usuário não autenticado via web
        if (Yii::$app->user->isGuest) {
            // Se for API offline permitida, deixa prosseguir para o controller validar o payload
            if (in_array($rotaAtual, $rotasApiOffline)) {
                return true;
            }

            Yii::$app->user->loginRequired();
            return false;
        }

        $usuario = Yii::$app->user->identity;
        $lojaId = $usuario->getTenantId();

        // 3. Verificação de permissão do módulo SaaS para a loja
        if ($lojaId && class_exists(LojaPermissao::class)) {
            if (!LojaPermissao::temPermissao('modulo-prestanista', $lojaId)) {
                Yii::$app->session->setFlash('warning', 'O Módulo Prestanista não está habilitado para a sua loja. Solicite a liberação ao administrador.');
                Yii::$app->response->redirect(['/vendas/inicio'])->send();
                return false;
            }
        }

        // 4. Se for Dono da Loja ou Administrador Master/Supervisor, tem acesso irrestrito
        if ($usuario->eh_dono_loja || $usuario->is_admin || $usuario->isGestorPrestanista()) {
            return true;
        }

        // 5. O usuário logado é um COLABORADOR DE CAMPO. Aplicar separação estrita de momentos:
        $ehVendedor = $usuario->isVendedorAmbulante();
        $ehCobrador = $usuario->isCobradorRua();

        // A) Acesso ao App do Vendedor Ambulante (Momento de Venda e Entrada de Crediário)
        if ($controllerId === 'vendedor') {
            if (!$ehVendedor) {
                Yii::$app->session->setFlash('warning', 'Seu perfil de acesso é exclusivo para Cobrança de Rua.');
                Yii::$app->response->redirect(['/prestanista/cobrador/index'])->send();
                return false;
            }
            return true;
        }

        // B) Acesso ao App do Cobrador de Rua (Momento de Cobrança em Campo e Baixa de Parcelas)
        if ($controllerId === 'cobrador') {
            if (!$ehCobrador) {
                Yii::$app->session->setFlash('warning', 'Seu perfil de acesso é exclusivo para Vendas Ambulantes.');
                Yii::$app->response->redirect(['/prestanista/vendedor/index'])->send();
                return false;
            }
            return true;
        }

        // C) Acesso ao Hub Seletor de Campo (apenas se for híbrido)
        if ($controllerId === 'campo') {
            if ($ehVendedor || $ehCobrador) {
                return true;
            }
        }

        // D) Bloqueio dos Controladores de Gestão/Retaguarda para Colaboradores de Campo
        // (default, cartao, atribuicao, acerto, carga, comissao, equipe)
        Yii::$app->session->setFlash('error', 'Acesso restrito ao administrador da loja.');

        if ($ehVendedor && $ehCobrador) {
            Yii::$app->response->redirect(['/prestanista/campo/index'])->send();
        } elseif ($ehCobrador) {
            Yii::$app->response->redirect(['/prestanista/cobrador/index'])->send();
        } elseif ($ehVendedor) {
            Yii::$app->response->redirect(['/prestanista/vendedor/index'])->send();
        } else {
            Yii::$app->response->redirect(['/auth/login'])->send();
        }

        return false;
    }
}
