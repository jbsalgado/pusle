<?php 
namespace app\controllers;

use app\helpers\Salg;
use Yii;
use app\models\LoginFormNew;
use app\modules\indicadores\models\SysModulos;
use yii\web\Controller;

class LoginController extends Controller
{
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginFormNew();
        $modulos = SysModulos::getModulosParaSelect();
        
        if ($model->load(Yii::$app->request->post())) {
            Salg::log($model, false, "LOGIN-TENTATIVA");
            
            // CORREÇÃO: Verificar se o login foi bem-sucedido
            if ($model->login()) {
                // Login bem-sucedido
                Salg::log($model, false, "LOGIN-SUCESSO");
                
                // Armazenar o módulo selecionado na sessão
                Yii::$app->session->set('modulo_selecionado', $model->modulo);
                
                // Debug temporário
                if (YII_DEBUG) {
                    error_log('=== LOGIN SUCESSO ===');
                    error_log('Módulo selecionado: ' . $model->modulo);
                    error_log('Usuário logado: ' . Yii::$app->user->id);
                    error_log('==================');
                }
                
                return $this->redirect(['/metricas']);
            } else {
                // Login falhou - log do erro
                Salg::log($model, false, "LOGIN-FALHA");
                
                // Debug temporário
                if (YII_DEBUG) {
                    error_log('=== LOGIN FALHOU ===');
                    error_log('Erros do modelo: ' . print_r($model->errors, true));
                    error_log('==================');
                }
                
                // Adicionar flash message para mostrar erro na tela
                Yii::$app->session->setFlash('error', 'Falha na autenticação. Verifique suas credenciais.');
            }
        }

        return $this->render('login', [
            'model' => $model,
            'modulos' => $modulos,
        ]);
    }

    public function actionIndex()
    {
        return $this->redirect(['login']);
    }
    
    public function actionLogout()
    {
        // Limpar sessão do módulo ao fazer logout
        Yii::$app->session->remove('modulo_selecionado');
        Yii::$app->user->logout();
        
        return $this->goHome();
    }
}