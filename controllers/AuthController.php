<?php
/**
 * AuthController - Autenticação Global
 * Localização: app/controllers/AuthController.php
 * * Gerencia login, logout e registro para todo o sistema
 */

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Usuario;
use app\models\LoginForm;
use app\models\SignupForm;

class AuthController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Login
     */
    public function actionLogin()
    {
        // Se já estiver logado, redireciona para o Dashboard Global
        if (!Yii::$app->user->isGuest) {
            // ✅ AJUSTADO: Redireciona para o dashboard global
            return $this->goHome(); 
        }

        $model = new LoginForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // ✅ AJUSTADO: Redireciona para o dashboard global após login
            return $this->goBack();
        }

        $model->senha = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->redirect(['login']);
    }

    /**
     * Cadastro de novo usuário
     */
    public function actionSignup()
    {
        // Se já estiver logado, redireciona para o Dashboard Global
        if (!Yii::$app->user->isGuest) {
            // ✅ AJUSTADO: Redireciona para o dashboard global
            return $this->goHome();
        }

        $model = new SignupForm();
        
        if ($model->load(Yii::$app->request->post())) {
            if ($usuario = $model->signup()) {
                // Faz login automaticamente
                if (Yii::$app->user->login($usuario)) {
                    Yii::$app->session->setFlash('success', 'Cadastro realizado com sucesso! Bem-vindo!');
                    // ✅ AJUSTADO: Redireciona para o dashboard global após cadastro
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Esqueci minha senha
     */
    public function actionForgotPassword()
    {
        // TODO: Implementar recuperação de senha
        Yii::$app->session->setFlash('info', 'Funcionalidade em desenvolvimento.');
        return $this->redirect(['login']);
    }

    /**
     * Resetar senha
     */
    public function actionResetPassword($token)
    {
        // TODO: Implementar reset de senha
        Yii::$app->session->setFlash('info', 'Funcionalidade em desenvolvimento.');
        return $this->redirect(['login']);
    }
}