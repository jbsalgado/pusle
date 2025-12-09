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
        
        if (!$usuario) {
            Yii::warning("⚠️ Usuário não autenticado!", __METHOD__);
            return $this->redirect(['/auth/login']);
        }
        
        // Verifica se é dono da loja (acesso completo automático)
        // Usa verificação mais robusta para garantir que funciona com diferentes tipos de dados
        $ehDonoLoja = $usuario->eh_dono_loja === true || $usuario->eh_dono_loja === '1' || $usuario->eh_dono_loja === 1;
        
        // Busca o colaborador associado ao usuário (se houver)
        $colaborador = null;
        $ehAdministrador = false;
        
        // Se é dono da loja, tem acesso completo
        if ($ehDonoLoja) {
            $ehAdministrador = true;
            Yii::info("✅ Usuário é dono da loja - Acesso completo concedido. ID: {$usuario->id}, eh_dono_loja: " . var_export($usuario->eh_dono_loja, true), __METHOD__);
        } else {
            // Se não é dono, verifica se é colaborador administrador
            $colaborador = \app\modules\vendas\models\Colaborador::find()
                ->where(['usuario_id' => $usuario->id])
                ->andWhere(['ativo' => true])
                ->one();
            
            if ($colaborador) {
                $ehAdministrador = (bool)$colaborador->eh_administrador;
                Yii::info("Colaborador encontrado - eh_administrador: " . ($colaborador->eh_administrador ? 'true' : 'false'), __METHOD__);
            } else {
                Yii::info("Colaborador não encontrado ou inativo para usuário ID: {$usuario->id}", __METHOD__);
            }
        }
        
        Yii::info("🔍 DEBUG InicioController - ehDonoLoja: " . ($ehDonoLoja ? 'true' : 'false') . ", ehAdministrador: " . ($ehAdministrador ? 'true' : 'false') . ", usuario->eh_dono_loja: " . var_export($usuario->eh_dono_loja, true), __METHOD__);
        
        return $this->render('index', [
            'colaborador' => $colaborador,
            'ehAdministrador' => $ehAdministrador,
            'ehDonoLoja' => $ehDonoLoja,
        ]);
    }
}