<?php
/**
 * SignupForm - Formulário de Cadastro
 * Localização: app/models/SignupForm.php
 */

namespace app\models;

use Yii;
use yii\base\Model;


class SignupForm extends Model
{
    public $nome;
    public $email;
    public $password;
    public $password_repeat;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome', 'password', 'password_repeat'], 'required'],
            ['nome', 'string', 'min' => 3, 'max' => 255],
            
            ['email', 'trim'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => Usuario::class, 'message' => 'Este e-mail já está em uso.'],
            
            ['password', 'string', 'min' => 6],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'message' => 'As senhas não coincidem.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'nome' => 'Nome Completo',
            'email' => 'E-mail',
            'password' => 'Senha',
            'password_repeat' => 'Confirmar Senha',
        ];
    }

    /**
     * Cadastra um novo usuário
     * @return Usuario|null o usuário salvo ou null se houver erro
     */
    public function signup()
    {
        if (!$this->validate()) {
            return null;
        }
        
        $usuario = new Usuario();
        $usuario->nome = $this->nome;
        $usuario->email = $this->email;
        $usuario->setPassword($this->password);
        $usuario->generateAuthKey();
        
        return $usuario->save() ? $usuario : null;
    }
}