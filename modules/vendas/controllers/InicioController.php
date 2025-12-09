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
        // Helper para converter valor boolean do PostgreSQL para PHP boolean
        $ehDonoLoja = $this->converterParaBoolean($usuario->eh_dono_loja);
        
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
                // Helper para converter valor boolean do PostgreSQL para PHP boolean
                $ehAdministrador = $this->converterParaBoolean($colaborador->eh_administrador);
                Yii::info("Colaborador encontrado - eh_administrador: " . var_export($colaborador->eh_administrador, true) . " -> " . ($ehAdministrador ? 'true' : 'false'), __METHOD__);
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
    
    /**
     * Converte valor boolean do PostgreSQL para PHP boolean
     * PostgreSQL pode retornar: true, false, 't', 'f', '1', '0', 1, 0
     * 
     * @param mixed $valor
     * @return bool
     */
    protected function converterParaBoolean($valor)
    {
        if ($valor === true || $valor === 1 || $valor === '1' || $valor === 't' || $valor === 'true') {
            return true;
        }
        
        if (is_string($valor) && strtolower(trim($valor)) === 't') {
            return true;
        }
        
        return false;
    }
}