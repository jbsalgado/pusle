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
        $usuario = Yii::$app->user->identity;
        
        // Verifica se é dono da loja (acesso completo automático)
        $ehDonoLoja = $usuario && $usuario->eh_dono_loja === true;
        
        // Busca o colaborador associado ao usuário (se houver)
        $colaborador = null;
        $ehAdministrador = false;
        
        if ($usuario) {
            // Se é dono da loja, tem acesso completo
            if ($ehDonoLoja) {
                $ehAdministrador = true;
            } else {
                // Se não é dono, verifica se é colaborador administrador
                $colaborador = \app\modules\vendas\models\Colaborador::find()
                    ->where(['usuario_id' => $usuario->id])
                    ->andWhere(['ativo' => true])
                    ->one();
                
                if ($colaborador) {
                    $ehAdministrador = (bool)$colaborador->eh_administrador;
                }
            }
        }
        
        return $this->render('index', [
            'colaborador' => $colaborador,
            'ehAdministrador' => $ehAdministrador,
            'ehDonoLoja' => $ehDonoLoja,
        ]);
    }
}