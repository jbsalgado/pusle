<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\modules\indicadores\models\SysModulos;

class LoginFormNew extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;
    public $modulo;

    private $_user;

    public function rules()
    {
        return [
            [['username', 'password', 'modulo'], 'required'],
            ['rememberMe', 'boolean'],
            ['modulo','safe'],
            ['password', 'validatePassword'],
            ['modulo', 'validateModulo'],
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            
            // Debug temporário - remover após teste
            if (YII_DEBUG) {
                error_log('=== DEBUG LOGIN ===');
                error_log('Username: ' . $this->username);
                error_log('Módulo: ' . $this->modulo);
                error_log('User found: ' . ($user ? 'SIM' : 'NÃO'));
                if ($user) {
                    error_log('User ID: ' . $user->id);
                    error_log('Password validation: ' . ($user->validatePassword($this->password) ? 'VÁLIDA' : 'INVÁLIDA'));
                }
                error_log('==================');
            }
            
            if (!$user) {
                $this->addError($attribute, 'Usuário não encontrado.');
                return;
            }
            
            if (!$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Senha inválida.');
                return;
            }
            
            // Verificar se usuário está ativo (se tiver campo status)
            if (method_exists($user, 'getIsActive') && !$user->getIsActive()) {
                $this->addError($attribute, 'Usuário inativo.');
                return;
            }
        }
    }
    
    public function validateModulo($attribute, $params)
    {
        if (!empty($this->modulo)) {
            // Verificar se o módulo existe e está ativo
            $modulo = SysModulos::findOne(['id' => $this->modulo, 'status' => true]);
            if (!$modulo) {
                $this->addError($attribute, 'Módulo selecionado é inválido.');
            }
        }
    }

    public function login()
    {
        // Debug temporário - remover após teste
        if (YII_DEBUG) {
            error_log('=== TENTATIVA DE LOGIN ===');
            error_log('Validate result: ' . ($this->validate() ? 'PASSOU' : 'FALHOU'));
            if ($this->hasErrors()) {
                error_log('Erros: ' . print_r($this->errors, true));
            }
        }
        
        if ($this->validate()) {
            $user = $this->getUser();
            
            if (!$user) {
                // Se por algum motivo o usuário não foi encontrado aqui
                if (YII_DEBUG) {
                    error_log('ERRO: Usuário não encontrado no momento do login');
                }
                return false;
            }
            
            $loginResult = Yii::$app->user->login($user, $this->rememberMe ? 3600*24*30 : 0);
            
            // Debug temporário - remover após teste
            if (YII_DEBUG) {
                error_log('Login result: ' . ($loginResult ? 'SUCESSO' : 'FALHOU'));
                error_log('Is Guest after login: ' . (Yii::$app->user->isGuest ? 'SIM' : 'NÃO'));
                error_log('User ID after login: ' . Yii::$app->user->id);
            }
            
            // Se login foi bem-sucedido, armazenar informações adicionais
            if ($loginResult) {
                // Armazenar o módulo na sessão do usuário
                Yii::$app->session->set('user_modulo', $this->modulo);
                
                // Opcional: armazenar outras informações que possam ser úteis
                $moduloInfo = SysModulos::findOne($this->modulo);
                if ($moduloInfo) {
                    Yii::$app->session->set('user_modulo_name', $moduloInfo->modulo);
                }
                
                if (YII_DEBUG) {
                    error_log('Módulo armazenado na sessão: ' . $this->modulo);
                }
            }
            
            return $loginResult;
        }
        
        return false;
    }

    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = Users::findByUsername($this->username);
        }
        return $this->_user;
    }
    
    public function attributeLabels()
    {
        return [
            'username' => 'Usuário',
            'password' => 'Senha',
            'modulo' => 'Módulo',
            'rememberMe' => 'Lembrar-me',
        ];
    }
    
    /**
     * Retorna o módulo selecionado pelo usuário logado
     */
    public static function getUserModulo()
    {
        return Yii::$app->session->get('user_modulo');
    }
    
    /**
     * Retorna o nome do módulo selecionado pelo usuário logado
     */
    public static function getUserModuloName()
    {
        return Yii::$app->session->get('user_modulo_name');
    }
}