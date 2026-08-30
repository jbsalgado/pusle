<?php
/**
 * LoginForm - Formulário de Login Global
 * Localização: app/models/LoginForm.php
 * 
 * Aceita login por CPF ou Email
 */

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\Usuario;

/**
 * LoginForm - Autenticação global do sistema
 */
class LoginForm extends Model
{
    public $login; // CPF ou Email
    public $senha;
    public $loja; // Slug ou ID da loja de onde veio o login
    public $lembrar_me = true;

    private $_usuario = false;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Login e senha obrigatórios
            [['login', 'senha'], 'required', 'message' => 'Este campo é obrigatório.'],
            
            // Login e Loja
            [['login', 'loja'], 'trim'],
            [['login', 'loja'], 'string'],
            
            // Senha
            [['senha'], 'string'],
            [['senha'], 'validatePassword'],
            
            // Lembrar-me
            [['lembrar_me'], 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'login' => 'CPF ou E-mail',
            'senha' => 'Senha',
            'loja' => 'Loja',
            'lembrar_me' => 'Lembrar-me',
        ];
    }

    /**
     * Valida a senha do usuário
     */
    // public function validatePassword($attribute, $params)
    // {
    //     if (!$this->hasErrors()) {
    //         $usuario = $this->getUsuario();
            
    //         if (!$usuario) {
    //             $this->addError('login', 'CPF/E-mail ou senha incorretos.');
    //             $this->addError('senha', ' '); // Adiciona erro vazio para destacar o campo
    //             return;
    //         }
            
    //         if (!$usuario->validatePassword($this->senha)) {
    //             $this->addError('login', 'CPF/E-mail ou senha incorretos.');
    //             $this->addError('senha', ' ');
    //         }
    //     }
    // }
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $usuario = $this->getUsuario();
            
            if (!$usuario) {
                $this->addError($attribute, 'Usuário não encontrado.');
                return;
            }
            
            // Verifica se está bloqueado
            if ($usuario->isBlocked()) {
                $this->addError($attribute, 'Usuário bloqueado. Entre em contato com o administrador.');
                return;
            }
            
            // Converte is_admin de forma segura (pode vir como 't', 'true' do PostgreSQL)
            $isAdmin = false;
            if (is_string($usuario->is_admin)) {
                $isAdmin = in_array(strtolower(trim($usuario->is_admin)), ['t', 'true', '1']);
            } else {
                $isAdmin = (bool) $usuario->is_admin;
            }

            // Se não é dono E não é gestor do SaaS, verifica se é um colaborador vinculado
            if ($usuario->eh_dono_loja === false && !$isAdmin) {
                $colaborador = \app\modules\vendas\models\Colaborador::find()
                    ->where(['prest_usuario_login_id' => $usuario->id])
                    ->andWhere(['ativo' => true])
                    ->one();

                if (!$colaborador) {
                    $this->addError($attribute, 'Usuário não está associado a nenhuma loja ativa. Entre em contato com o administrador da sua loja.');
                    return;
                }
            }
            
            // Valida senha
            if (!$usuario->validatePassword($this->senha)) {
                $this->addError($attribute, 'CPF/E-mail ou senha incorretos.');
                return;
            }

            // Super Administrador (is_admin = true) tem acesso irrestrito global
            if ($isAdmin) {
                return;
            }

            // Se o login foi originado de uma loja específica (?loja=slug-ou-id)
            if (!empty($this->loja)) {
                $lojaAlvo = Usuario::find()
                    ->where(['catalogo_path' => $this->loja, 'eh_dono_loja' => true])
                    ->one();

                if (!$lojaAlvo) {
                    $lojaAlvo = Usuario::find()
                        ->where(['id' => $this->loja, 'eh_dono_loja' => true])
                        ->one();
                }

                if ($lojaAlvo) {
                    $nomeLojaAlvo = $lojaAlvo->nome;
                    $configLojaAlvo = \app\modules\vendas\models\LojaConfiguracao::findOne(['usuario_id' => $lojaAlvo->id]);
                    if ($configLojaAlvo && !empty($configLojaAlvo->nome_loja)) {
                        $nomeLojaAlvo = $configLojaAlvo->nome_loja;
                    }

                    // Se for Dono de Loja: só pode entrar na SUA PRÓPRIA loja
                    if ($usuario->eh_dono_loja) {
                        if ($usuario->id !== $lojaAlvo->id) {
                            $this->addError($attribute, "Este usuário não pertence à loja selecionada ({$nomeLojaAlvo}). Por favor, acesse o sistema através da página da sua própria loja.");
                            return;
                        }
                    } else {
                        // Se for Colaborador: deve pertencer à loja alvo
                        $colab = \app\modules\vendas\models\Colaborador::find()
                            ->where(['prest_usuario_login_id' => $usuario->id, 'usuario_id' => $lojaAlvo->id, 'ativo' => true])
                            ->one();

                        if (!$colab) {
                            $this->addError($attribute, "Este colaborador não possui permissão de acesso à loja selecionada ({$nomeLojaAlvo}).");
                            return;
                        }
                    }
                }
            }
        }
    }

    /**
     * Realiza o login do usuário
     * @return bool
     */
    // public function login()
    // {
    //     if (!$this->validate()) {
    //         return false;
    //     }

    //     $usuario = $this->getUsuario();
        
    //     if (!$usuario) {
    //         return false;
    //     }

    //     // Define duração da sessão
    //     $duracao = $this->lembrar_me ? 3600 * 24 * 30 : 0;

    //     // Faz o login
    //     $sucesso = Yii::$app->user->login($usuario, $duracao);
        
    //     if ($sucesso) {
    //         // Registra último acesso
    //         $this->registrarAcesso($usuario);
            
    //         Yii::info("Login bem-sucedido: {$this->login} (ID: {$usuario->id})", __METHOD__);
    //     } else {
    //         Yii::error("Falha no login para: {$this->login}", __METHOD__);
    //     }

    //     return $sucesso;
    // }
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUsuario(), $this->lembrar_me ? 3600 * 24 * 30 : 0);
        }
        return false;
    }

    /**
     * Busca o usuário pelo CPF ou Email
     * @return Usuario|null
     */
    protected function getUsuario()
    {
        if ($this->_usuario === false) {
            $this->_usuario = Usuario::findByLogin($this->login);
        }

        return $this->_usuario;
    }

    /**
     * Registra o último acesso do usuário
     */
    protected function registrarAcesso($usuario)
    {
        try {
            // O trigger já atualiza data_atualizacao automaticamente
            // Mas podemos forçar uma atualização se necessário
            $usuario->touch('data_atualizacao');
        } catch (\Exception $e) {
            Yii::error("Erro ao registrar acesso: {$e->getMessage()}", __METHOD__);
        }
    }
}