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
        // Se já estiver logado, redireciona para o Dashboard de Vendas
        if (!Yii::$app->user->isGuest) {
            // ✅ AJUSTADO: Redireciona para o dashboard de vendas
            return $this->redirect(['/vendas/dashboard']);
        }

        $model = new LoginForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // ✅ AJUSTADO: Redireciona para o dashboard de vendas após login bem-sucedido
            return $this->redirect(['/vendas/dashboard']);
        }

        $model->senha = '';
        
        // Busca dados da empresa para exibir na página de login
        $dadosEmpresa = $this->buscarDadosEmpresa();
        
        return $this->render('login', [
            'model' => $model,
            'dadosEmpresa' => $dadosEmpresa,
        ]);
    }

    /**
     * Logout
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->redirect(['/auth/login']);
    }

    /**
     * Cadastro de novo usuário
     */
    public function actionSignup()
    {
        // Se já estiver logado, redireciona para o Dashboard de Vendas
        if (!Yii::$app->user->isGuest) {
            // ✅ AJUSTADO: Redireciona para o dashboard de vendas
            return $this->redirect(['/vendas/dashboard']);
        }

        $model = new SignupForm();
        
        if ($model->load(Yii::$app->request->post())) {
            if ($usuario = $model->signup()) {
                // Faz login automaticamente
                if (Yii::$app->user->login($usuario)) {
                    Yii::$app->session->setFlash('success', 'Cadastro realizado com sucesso! Bem-vindo!');
                    // ✅ AJUSTADO: Redireciona para o dashboard de vendas após cadastro
                    return $this->redirect(['/vendas/dashboard']);
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

    /**
     * Busca dados da empresa da tabela prest_configuracoes
     * @return array
     */
    private function buscarDadosEmpresa()
    {
        // ID padrão da loja (mesmo usado no catálogo e landing page)
        $lojaId = 'a99a38a9-e368-4a47-a4bd-02ba3bacaa76';
        
        try {
            // Busca dados do usuário
            $sql = "
                SELECT 
                    id,
                    nome,
                    logo_path
                FROM prest_usuarios
                WHERE id = :id::uuid
                LIMIT 1
            ";
            
            $usuario = Yii::$app->db->createCommand($sql, [
                ':id' => $lojaId
            ])->queryOne();
            
            if (!$usuario) {
                return [
                    'nome_loja' => 'THAUSZ-PULSE',
                    'logo_path' => null,
                ];
            }
            
            // Busca configuração da loja
            $config = \app\modules\vendas\models\Configuracao::findOne(['usuario_id' => $lojaId]);
            
            // Logo: prioriza prest_configuracoes, depois prest_usuarios
            $logoPath = '';
            if ($config && !empty($config->logo_path)) {
                $logoPath = $config->logo_path;
            } elseif (!empty($usuario['logo_path'])) {
                $logoPath = $usuario['logo_path'];
            }
            
            // Nome da loja: prioriza prest_configuracoes, depois prest_usuarios
            $nomeLoja = 'THAUSZ-PULSE';
            if ($config && !empty($config->nome_loja)) {
                $nomeLoja = $config->nome_loja;
            } elseif (!empty($usuario['nome'])) {
                $nomeLoja = $usuario['nome'];
            }
            
            return [
                'nome_loja' => $nomeLoja,
                'logo_path' => $logoPath,
            ];
            
        } catch (\Exception $e) {
            Yii::error('Erro ao buscar dados da empresa: ' . $e->getMessage());
            return [
                'nome_loja' => 'THAUSZ-PULSE',
                'logo_path' => null,
            ];
        }
    }
}