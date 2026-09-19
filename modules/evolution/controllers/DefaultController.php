<?php

namespace app\modules\evolution\controllers;

use yii\web\Controller;
use yii\web\Response;

/**
 * DefaultController para o módulo Evolution.
 *
 * Redireciona rotas padrão como /evolution/default ou /evolution/default/index
 * para o painel principal de configuração em /evolution/config/index,
 * prevenindo erros 404 (Not Found).
 */
class DefaultController extends Controller
{
    /**
     * Redireciona a ação padrão para a tela de configuração da Evolution API.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        return $this->redirect(['/evolution/config/index']);
    }
}
