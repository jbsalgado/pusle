<?php
/**
 * InicioController - VERSÃO DE TESTE ESTÁTICO
 */
namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\behaviors\ModuloAccessBehavior;

class InicioController extends Controller
{
    public $layout = 'main';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Garante que só utilizadores logados acedem
                    ],
                ],
            ],
            // O behavior de acesso ao módulo pode ser mantido
            'moduloAccess' => [
                'class' => ModuloAccessBehavior::class,
                'moduloCodigo' => 'vendas',
            ],
        ];
    }

    /**
     * A action mais simples possível.
     * Apenas chama a view, sem passar nenhuma variável.
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}