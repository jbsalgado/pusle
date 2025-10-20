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
            
            // Login
            [['login'], 'trim'],
            [['login'], 'string'],
            
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
            'login' => 'CPF',
            'senha' => 'Senha',
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
            if (!$usuario || !$usuario->validatePassword($this->senha)) {
                $this->addError($attribute, 'CPF/E-mail ou senha incorretos.');
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