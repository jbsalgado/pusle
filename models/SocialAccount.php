<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Esta é a classe de modelo ActiveRecord para a tabela "prest_social_accounts".
 *
 * @property string $id (UUID)
 * @property string $tenant_id (UUID)
 * @property string|null $facebook_page_id
 * @property string|null $instagram_business_account_id
 * @property string $page_name
 * @property string $access_token
 * @property string|null $token_expires_at
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Usuario $tenant
 * @property SocialPost[] $posts
 */
class SocialAccount extends ActiveRecord
{
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_DISCONNECTED = 'DISCONNECTED';

    /**
     * Chave secreta de criptografia para tokens sensíveis.
     */
    private static function getSecretKey(): string
    {
        if (!empty(Yii::$app->params['meta_token_encryption_key'])) {
            return Yii::$app->params['meta_token_encryption_key'];
        }
        if (isset(Yii::$app->request) && !empty(Yii::$app->request->cookieValidationKey)) {
            return Yii::$app->request->cookieValidationKey;
        }
        return 'pulse-meta-social-token-secret-key-2026';
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_social_accounts';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
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
            [['tenant_id', 'page_name', 'access_token'], 'required'],
            [['access_token'], 'string'],
            [['token_expires_at', 'created_at', 'updated_at'], 'safe'],
            [['facebook_page_id', 'instagram_business_account_id', 'page_name'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 50],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_DISCONNECTED]],
            [['tenant_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['tenant_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tenant_id' => 'Tenant ID',
            'facebook_page_id' => 'Facebook Page ID',
            'instagram_business_account_id' => 'Instagram Business Account ID',
            'page_name' => 'Nome da Página',
            'access_token' => 'Token de Acesso (Criptografado)',
            'token_expires_at' => 'Data de Expiração do Token',
            'status' => 'Status',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ];
    }

    /**
     * Define e criptografa o token de acesso antes de atribuir ao atributo.
     *
     * @param string $plainToken
     */
    public function setEncryptedAccessToken(string $plainToken): void
    {
        if (empty($plainToken)) {
            $this->access_token = '';
            return;
        }

        $encrypted = Yii::$app->security->encryptByKey($plainToken, self::getSecretKey());
        $this->access_token = base64_encode($encrypted);
    }

    /**
     * Retorna o token de acesso descriptografado.
     *
     * @return string|null
     */
    public function getDecryptedAccessToken(): ?string
    {
        if (empty($this->access_token)) {
            return null;
        }

        $decoded = base64_decode($this->access_token, true);
        if ($decoded === false) {
            // Suporte para token plano se não estiver em base64 (fallback seguro)
            return $this->access_token;
        }

        $decrypted = Yii::$app->security->decryptByKey($decoded, self::getSecretKey());
        return $decrypted !== false ? $decrypted : $this->access_token;
    }

    /**
     * Verifica se o token de acesso está expirado ou próximo de expirar (margem de 1 dia).
     *
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        if (empty($this->token_expires_at)) {
            return false; // Tokens sem expiração definida (ex: Page Access Token perpétuo obtido via User Long-Lived Token)
        }

        $expires = new \DateTime($this->token_expires_at);
        $threshold = (new \DateTime())->modify('+1 day');

        return $expires <= $threshold;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTenant()
    {
        return $this->hasOne(Usuario::class, ['id' => 'tenant_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosts()
    {
        return $this->hasMany(SocialPost::class, ['social_account_id' => 'id']);
    }
}
