<?php
/**
 * Model: Usuario
 * Localização: app/modules/vendas/models/Usuario.php
 * * Tabela: prest_usuarios
 */

namespace app\models;

use app\helpers\Salg;
use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;

/**
 * Usuario model
 *
 * @property string $id UUID
 * @property string $nome
 * @property string $email
 * @property string $hash_senha
 * @property string $auth_key       // Chave para "Lembrar-me"
 * @property string $cpf
 * @property string $telefone
 * @property string $data_criacao
 * @property string $data_atualizacao
 */
class Usuario extends ActiveRecord implements IdentityInterface
{
    // Campos virtuais usados em formulários, não existem na tabela do banco
    public $senha;
    public $senha_confirmacao;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_usuarios';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['nome', 'cpf', 'telefone', 'email', 'senha', 'senha_confirmacao'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'data_criacao',
                'updatedAtAttribute' => 'data_atualizacao',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Campos obrigatórios básicos
            [['nome', 'cpf', 'telefone'], 'required'],
            
                    
            // Email
            [['email'], 'email'],
            //[['email'], 'unique', 'message' => 'Este e-mail já está em uso.'],
            
            // CPF
            [['cpf'], 'string', 'length' => 11],
            [['cpf'], 'unique', 'message' => 'Este CPF já está cadastrado.'],
            
            // Outros
            [['nome'], 'string', 'max' => 100],
            [['telefone'], 'string', 'max' => 15],
        ];
    }
    
    //======================================================================
    // MÉTODOS DA IdentityInterface (NECESSÁRIOS PARA LOGIN)
    //======================================================================

    /**
     * {@inheritdoc}
     * Encontra uma identidade pelo seu ID (chave primária)
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id]);
    }

    /**
     * {@inheritdoc}
     * Encontra uma identidade pelo token de acesso. Não usaremos neste projeto.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null; // Não implementado
    }

    /**
     * {@inheritdoc}
     * Retorna o ID do usuário
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     * Retorna a chave de autenticação do usuário (usada para "Lembrar-me")
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     * Valida a chave de autenticação
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    //======================================================================
    // MÉTODOS DE AJUDA PARA AUTENTICAÇÃO
    //======================================================================

    /**
     * Encontra um usuário pelo seu login (pode ser CPF ou Email)
     * Usado pelo LoginForm.
     * @param string $login
     * @return static|null
     */
    public static function findByLogin($login)
    {
        return static::find()->where(['cpf' => $login])->orWhere(['email' => $login])->one();
    }

    /**
     * Gera uma nova "auth key" para o usuário.
     * Deve ser chamado antes de salvar um novo usuário no SignupForm.
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
    
   

    /**
     * Valida a senha fornecida contra o hash armazenado
     * @param string $senha
     * @return bool
     */
    public function validatePassword($senha)
    {
        return Yii::$app->security->validatePassword($senha, $this->hash_senha);
    }
    
    //======================================================================
    // MÉTODOS GETTERS (PARA FORMATAÇÃO E FACILIDADE)
    //======================================================================

    /**
     * Retorna primeiro nome
     * @return string
     */
    public function getPrimeiroNome()
    {
        $palavras = explode(' ', trim($this->nome));
        return $palavras[0] ?? '';
    }

    /**
     * Retorna CPF formatado (XXX.XXX.XXX-XX)
     * @return string
     */
    public function getCpfFormatado()
    {
        $cpf = preg_replace('/[^0-9]/', '', $this->cpf);
        if (strlen($cpf) == 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
        }
        return $this->cpf;
    }

    /**
     * Retorna Telefone formatado
     * @return string
     */
    public function getTelefoneFormatado()
    {
        $telefone = preg_replace('/[^0-9]/', '', $this->telefone);
        
        if (strlen($telefone) == 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
        } elseif (strlen($telefone) == 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
        }
        
        return $this->telefone;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Se for um novo registo (inserção), a senha deve ser processada.
            if ($this->isNewRecord) {
                
                // Gera o hash da senha
                $this->hash_senha = Yii::$app->security->generatePasswordHash($this->senha);
                
                // Gera a chave de autenticação para "Lembrar-me"
                $this->generateAuthKey();

            }
            Salg::log($this,false,"USUARIO DE DENTO DO BEFORE SAVE");
            return true;
        }
        return false;
    }

    public function getIniciais()
    {
        $palavras = explode(' ', trim($this->nome));
        $iniciais = '';

        if (isset($palavras[0])) {
            $iniciais .= mb_substr($palavras[0], 0, 1);
        }

        if (count($palavras) > 1) {
            $iniciais .= mb_substr(end($palavras), 0, 1);
        }

        return strtoupper($iniciais);
    }

}