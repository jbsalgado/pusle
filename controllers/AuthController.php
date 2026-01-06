<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\LoginForm;
use app\modules\vendas\models\Configuracao;

class AuthController extends Controller
{
    public $layout = false; // Sem layout para a página de login

    /**
     * Action de login
     * @return string|\yii\web\Response
     */
    public function actionLogin()
    {
        $model = new LoginForm();

        // Carrega dados da empresa (tenta buscar de um usuário padrão ou usa valores padrão)
        $dadosEmpresa = $this->carregarDadosEmpresa();

        // Processa o formulário de login se for POST
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // Após login bem-sucedido, redireciona para o dashboard
            return $this->redirect(['/vendas/dashboard']);
        }

        $model->senha = '';
        // Sempre exibe a página de login, mesmo se o usuário já estiver logado
        // (útil para logout ou trocar de conta)
        return $this->render('@app/views/auth/login', [
            'model' => $model,
            'dadosEmpresa' => $dadosEmpresa,
        ]);
    }

    /**
     * Action de logout
     * @return \yii\web\Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    /**
     * Action de cadastro (Signup)
     * @return string|\yii\web\Response
     */
    public function actionSignup()
    {
        $model = new \app\models\SignupForm();

        // Carrega dados da empresa (para layout)
        $dadosEmpresa = $this->carregarDadosEmpresa();

        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    // Redireciona para o dashboard após cadastro e login
                    return $this->redirect(['/vendas/dashboard']);
                }
            }
        }

        return $this->render('@app/views/auth/signup', [
            'model' => $model,
            'dadosEmpresa' => $dadosEmpresa,
        ]);
    }

    /**
     * Carrega dados da empresa para exibir no login
     * @return array
     */
    protected function carregarDadosEmpresa()
    {
        try {
            // Tenta buscar a primeira configuração disponível
            $config = Configuracao::find()->one();

            if ($config) {
                return [
                    'nome_loja' => $config->nome_loja ?? 'THAUSZ-PULSE',
                    'logo_path' => $config->logo_path ?? '',
                ];
            }

            // Se não houver configuração, tenta buscar do primeiro usuário
            $usuario = \app\models\Usuario::find()->one();

            if ($usuario) {
                return [
                    'nome_loja' => $usuario->nome ?? 'THAUSZ-PULSE',
                    'logo_path' => $usuario->logo_path ?? '',
                ];
            }
        } catch (\Exception $e) {
            Yii::error('Erro ao carregar dados da empresa: ' . $e->getMessage(), __METHOD__);
        }

        // Valores padrão
        return [
            'nome_loja' => 'THAUSZ-PULSE',
            'logo_path' => '',
        ];
    }
}
