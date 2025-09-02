<?php 

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class Users extends ActiveRecord implements IdentityInterface
{
    // Tabela no banco (mesma do Dektrium)
    public static function tableName()
    {
        return '{{%user}}';
    }

    public $rememberMe=true;

    // Regras de validação
    public function rules()
    {
        return [
            [['username', 'email'], 'required'],
            ['email', 'email'],
            ['rememberMe', 'boolean'],
            ['password_hash', 'string', 'min' => 6],
        ];
    }

    // Comportamentos (timestamps automáticos)
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    // Métodos obrigatórios para autenticação
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" não implementado.');
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    // Método para validar senha (usando bcrypt)
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    // Método para definir senha (hash automático)
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Busca um usuário pelo nome de usuário
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }


    public function login()
    {
        return Yii::$app->user->login($this, $this->rememberMe ? 3600*24*30 : 0);
    }



}