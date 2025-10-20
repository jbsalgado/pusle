<?php
/**
 * AuthController - Controller de Autenticação Global
 * Localização: app/controllers/AuthController.php
 * 
 * Este controller é usado por TODOS os módulos
 */

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\SignupForm;

/**
 * AuthController - Autenticação global do sistema
 */
class AuthController extends Controller
{
    public $layout = 'auth'; // Layout de autenticação

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'signup', 'forgot-password', 'reset-password'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post', 'get'],
                ],
            ],
        ];
    }

    /**
     * Login action
     */
    public function actionLogin()
    {
        // Se já estiver logado, redireciona para dashboard central
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/dashboard/index']);
        }

        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $usuario = Yii::$app->user->identity;
            Yii::$app->session->setFlash('success', 'Bem-vindo de volta, ' . $usuario->getPrimeiroNome() . '!');
            
            // Redireciona para o dashboard central (seleção de módulos)
            return $this->redirect(['/dashboard/index']);
        }

        // Limpa senha em caso de erro
        $model->senha = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Signup action
     */
    public function actionSignup()
    {
        // Se já estiver logado, redireciona para dashboard central
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/dashboard/index']);
        }

        $model = new SignupForm();

        if ($model->load(Yii::$app->request->post())) {
            $usuario = $model->signup();
            
            if ($usuario) {
                // Tenta enviar email de boas-vindas
                $model->sendWelcomeEmail($usuario);
                
                // Faz login automaticamente após o cadastro
                if (Yii::$app->user->login($usuario)) {
                    Yii::$app->session->setFlash('success', 'Cadastro realizado com sucesso! Bem-vindo ao sistema.');
                    
                    // Redireciona para o dashboard central
                    return $this->redirect(['/dashboard/index']);
                }
                
                // Se falhar o login automático, redireciona para o login
                Yii::$app->session->setFlash('success', 'Cadastro realizado! Faça login para continuar.');
                return $this->redirect(['login']);
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action
     */
    public function actionLogout()
    {
        $nomeUsuario = Yii::$app->user->identity->getPrimeiroNome();
        
        Yii::$app->user->logout();
        
        Yii::$app->session->setFlash('info', "Até logo, {$nomeUsuario}!");
        
        return $this->redirect(['login']);
    }

    /**
     * Esqueci minha senha
     */
    public function actionForgotPassword()
    {
        // TODO: Implementar recuperação de senha
        
        return $this->render('forgot-password');
    }

    /**
     * Resetar senha
     */
    public function actionResetPassword($token)
    {
        // TODO: Implementar reset de senha com token
        
        return $this->render('reset-password', [
            'token' => $token,
        ]);
    }
}