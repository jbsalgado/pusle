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
        
        // Verifica se há usuários cadastrados (se não houver, mostra link de cadastro)
        $temUsuarios = \app\models\Usuario::find()->exists();
        
        return $this->render('login', [
            'model' => $model,
            'dadosEmpresa' => $dadosEmpresa,
            'mostrarCadastro' => !$temUsuarios, // Mostra cadastro apenas se não houver usuários
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
        
        // Verifica se já existem usuários cadastrados
        // Se existir, apenas o dono da loja pode criar novos usuários (via sistema interno)
        $temUsuarios = \app\models\Usuario::find()->exists();
        if ($temUsuarios) {
            Yii::$app->session->setFlash('error', 'O cadastro público está desabilitado. Entre em contato com o administrador da loja para criar uma conta.');
            return $this->redirect(['auth/login']);
        }

        $model = new SignupForm();
        
        if ($model->load(Yii::$app->request->post())) {
            // 🔍 DEBUG: Log dos dados recebidos
            Yii::info('📝 Dados POST recebidos no signup: ' . json_encode(Yii::$app->request->post()), __METHOD__);
            Yii::info('📝 Model após load: ' . json_encode($model->attributes), __METHOD__);
            
            // Valida antes de tentar salvar
            if (!$model->validate()) {
                Yii::error('❌ Erros de validação no signup: ' . json_encode($model->errors), __METHOD__);
                Yii::$app->session->setFlash('error', 'Por favor, corrija os erros abaixo e tente novamente.');
            } else {
                // Tenta criar o usuário
                if ($usuario = $model->signup()) {
                    // Faz login automaticamente
                    if (Yii::$app->user->login($usuario)) {
                        Yii::$app->session->setFlash('success', 'Cadastro realizado com sucesso! Bem-vindo!');
                        // ✅ AJUSTADO: Redireciona para o dashboard de vendas após cadastro
                        return $this->redirect(['/vendas/dashboard']);
                    } else {
                        Yii::error('❌ Erro ao fazer login após cadastro', __METHOD__);
                        Yii::$app->session->setFlash('error', 'Cadastro realizado, mas houve erro ao fazer login. Tente fazer login manualmente.');
                    }
                } else {
                    // signup() retornou null - pode ser erro de validação ou de salvamento
                    if ($model->hasErrors()) {
                        Yii::error('❌ Erros após signup(): ' . json_encode($model->errors), __METHOD__);
                        
                        // Monta mensagem de erro detalhada
                        $mensagensErro = [];
                        foreach ($model->errors as $campo => $erros) {
                            foreach ($erros as $erro) {
                                $label = $model->getAttributeLabel($campo);
                                $mensagensErro[] = $label . ': ' . $erro;
                            }
                        }
                        
                        if (!empty($mensagensErro)) {
                            $mensagemErro = 'Erro ao cadastrar: ' . implode(' | ', $mensagensErro);
                        } else {
                            $mensagemErro = 'Erro ao cadastrar. Verifique os dados e tente novamente.';
                        }
                        
                        Yii::$app->session->setFlash('error', $mensagemErro);
                    } else {
                        Yii::error('❌ signup() retornou null sem erros visíveis', __METHOD__);
                        Yii::$app->session->setFlash('error', 'Erro desconhecido ao cadastrar. Verifique os logs do servidor.');
                    }
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
        try {
            // Busca configurações ordenadas por data de atualização (mais recente primeiro)
            // Prioriza configurações que tenham nome_loja preenchido
            $configs = \app\modules\vendas\models\Configuracao::find()
                ->orderBy(['data_atualizacao' => SORT_DESC, 'data_criacao' => SORT_DESC])
                ->all();
            
            $config = null;
            $usuario = null;
            
            // Primeiro, tenta encontrar uma configuração com nome_loja preenchido
            foreach ($configs as $c) {
                if (!empty($c->nome_loja)) {
                    $config = $c;
                    break;
                }
            }
            
            // Se não encontrou uma com nome_loja, usa a mais recente
            if (!$config && !empty($configs)) {
                $config = $configs[0];
            }
            
            // Busca o usuário relacionado à configuração
            if ($config) {
                $usuario = Yii::$app->db->createCommand("
                    SELECT id, nome, logo_path
                    FROM prest_usuarios
                    WHERE id = :id::uuid
                    LIMIT 1
                ", [':id' => $config->usuario_id])->queryOne();
            }
            
            // Se não houver configuração, busca qualquer usuário dono de loja como fallback
            if (!$config) {
                $usuario = Yii::$app->db->createCommand("
                    SELECT id, nome, logo_path
                    FROM prest_usuarios
                    WHERE eh_dono_loja = true
                    ORDER BY data_criacao DESC NULLS LAST, id DESC
                    LIMIT 1
                ")->queryOne();
                
                // Se não encontrar dono de loja, pega qualquer usuário
                if (!$usuario) {
                    $usuario = Yii::$app->db->createCommand("
                        SELECT id, nome, logo_path
                        FROM prest_usuarios
                        ORDER BY data_criacao DESC NULLS LAST, id DESC
                        LIMIT 1
                    ")->queryOne();
                }
            }
            
            if (!$usuario && !$config) {
                return [
                    'nome_loja' => 'THAUSZ-PULSE',
                    'logo_path' => null,
                ];
            }
            
            // Logo: prioriza prest_configuracoes, depois prest_usuarios
            $logoPath = '';
            if ($config && !empty($config->logo_path)) {
                $logoPath = trim($config->logo_path);
            } elseif (!empty($usuario['logo_path'])) {
                $logoPath = trim($usuario['logo_path']);
            }
            
            // Nome da loja: SEMPRE prioriza prest_configuracoes.nome_loja
            // NÃO usa o nome do usuário como fallback, pois nome do usuário != nome da loja
            $nomeLoja = 'THAUSZ-PULSE';
            if ($config && !empty($config->nome_loja)) {
                $nomeLoja = trim($config->nome_loja);
            }
            
            // Log para debug
            Yii::info("🔍 Dados da empresa para login - Nome Loja: {$nomeLoja}, Logo: " . ($logoPath ?: 'não configurado'), __METHOD__);
            
            return [
                'nome_loja' => $nomeLoja,
                'logo_path' => $logoPath ?: null,
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