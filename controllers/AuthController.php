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
            $redirectUrl = Yii::$app->request->get('redirect_url');

            if ($redirectUrl) {
                // Se houver uma URL de redirecionamento (ex: vindo do PWA),
                // gera um token JWT e anexa à URL
                $usuario = Yii::$app->user->identity;
                $token = $usuario->generateJwt();

                // Verifica se a URL já tem parâmetros
                $separator = (strpos($redirectUrl, '?') === false) ? '?' : '&';
                return $this->redirect($redirectUrl . $separator . 'token=' . $token);
            }

            // Após login bem-sucedido, verifica o tipo de usuário
            $usuario = Yii::$app->user->identity;
            
            // Se for apenas Gestor do SaaS (admin, mas não é dono de loja)
            if ($usuario->is_admin && !$usuario->eh_dono_loja) {
                return $this->redirect(['/admin/loja/index']);
            }

            // Caso contrário (Dono de Loja ou Colaborador), vai para o painel de vendas
            return $this->redirect(['/vendas/inicio']);
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
                    // Redireciona para a página inicial após cadastro e login
                    return $this->redirect(['/vendas/inicio']);
                }
            }
        }

        return $this->render('@app/views/auth/signup', [
            'model' => $model,
            'dadosEmpresa' => $dadosEmpresa,
        ]);
    }

    /**
     * Carrega dados da empresa para exibir no login.
     * Se houver ?loja=<slug> na URL, prioriza os dados daquela loja.
     * @return array
     */
    protected function carregarDadosEmpresa()
    {
        try {
            // 1. Verifica se há slug de loja na URL (?loja=meu-slug)
            $slug = Yii::$app->request->get('loja');

            if ($slug) {
                // Busca a loja pelo catalogo_path (slug)
                $usuario = \app\models\Usuario::find()
                    ->where(['catalogo_path' => $slug, 'eh_dono_loja' => true])
                    ->one();

                if ($usuario) {
                    $lojaConfig = \app\modules\vendas\models\LojaConfiguracao::findOne(['usuario_id' => $usuario->id]);
                    $nomeLoja   = $lojaConfig->nome_loja ?? $usuario->nome ?? 'Loja';
                    $logoPath   = $lojaConfig->logo_path ?? $usuario->logo_path ?? '';

                    return [
                        'nome_loja' => $nomeLoja,
                        'logo_path' => $logoPath,
                        'slug'      => $slug,
                    ];
                }
            }

            // 2. Fallback: primeira configuração disponível (comportamento anterior)
            $config = \app\modules\vendas\models\Configuracao::find()->one();

            if ($config) {
                return [
                    'nome_loja' => $config->nome_loja ?? 'THAUSZ-PULSE',
                    'logo_path' => $config->logo_path ?? '',
                    'slug'      => null,
                ];
            }

            // 3. Último fallback: primeiro usuário cadastrado
            $usuario = \app\models\Usuario::find()->one();

            if ($usuario) {
                return [
                    'nome_loja' => $usuario->nome ?? 'THAUSZ-PULSE',
                    'logo_path' => $usuario->logo_path ?? '',
                    'slug'      => null,
                ];
            }

            return [
                'nome_loja' => 'THAUSZ-PULSE',
                'logo_path' => '',
                'slug'      => null,
            ];

        } catch (\Exception $e) {
            Yii::warning('Erro ao carregar dados da empresa: ' . $e->getMessage(), __METHOD__);
            return [
                'nome_loja' => 'THAUSZ-PULSE',
                'logo_path' => '',
                'slug'      => null,
            ];
        }
    }
}
