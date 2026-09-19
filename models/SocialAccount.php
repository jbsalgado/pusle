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
 * @property string $provider
 * @property string|null $tiktok_open_id
 * @property string|null $tiktok_refresh_token
 * @property string|null $tiktok_refresh_expires_at
 * @property string|null $account_avatar_url
 * @property string|null $colaborador_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Usuario $tenant
 * @property \app\modules\vendas\models\Colaborador|null $colaborador
 * @property SocialPost[] $posts
 */
class SocialAccount extends ActiveRecord
{
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_DISCONNECTED = 'DISCONNECTED';

    const PROVIDER_META = 'META';
    const PROVIDER_TIKTOK = 'TIKTOK';

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
            [['access_token', 'tiktok_refresh_token', 'account_avatar_url'], 'string'],
            [['token_expires_at', 'tiktok_refresh_expires_at', 'created_at', 'updated_at', 'colaborador_id'], 'safe'],
            [['facebook_page_id', 'instagram_business_account_id', 'page_name', 'tiktok_open_id'], 'string', 'max' => 255],
            [['provider'], 'string', 'max' => 50],
            [['provider'], 'default', 'value' => self::PROVIDER_META],
            [['provider'], 'in', 'range' => [self::PROVIDER_META, self::PROVIDER_TIKTOK]],
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
            'provider' => 'Provedor (Meta/TikTok)',
            'facebook_page_id' => 'Facebook Page ID',
            'instagram_business_account_id' => 'Instagram Business Account ID',
            'tiktok_open_id' => 'TikTok Open ID',
            'page_name' => 'Nome da Conta/Página',
            'access_token' => 'Token de Acesso (Criptografado)',
            'token_expires_at' => 'Data de Expiração do Token',
            'tiktok_refresh_token' => 'Refresh Token TikTok (Criptografado)',
            'tiktok_refresh_expires_at' => 'Expiração do Refresh Token',
            'account_avatar_url' => 'Avatar da Conta',
            'colaborador_id' => 'Afiliado / Colaborador ID',
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
     * Define e criptografa o refresh token antes de salvar.
     *
     * @param string $plainToken
     */
    public function setEncryptedRefreshToken(string $plainToken): void
    {
        if (empty($plainToken)) {
            $this->tiktok_refresh_token = null;
            return;
        }

        $encrypted = Yii::$app->security->encryptByKey($plainToken, self::getSecretKey());
        $this->tiktok_refresh_token = base64_encode($encrypted);
    }

    /**
     * Retorna o refresh token descriptografado.
     *
     * @return string|null
     */
    public function getDecryptedRefreshToken(): ?string
    {
        if (empty($this->tiktok_refresh_token)) {
            return null;
        }

        $decoded = base64_decode($this->tiktok_refresh_token, true);
        if ($decoded === false) {
            return $this->tiktok_refresh_token;
        }

        $decrypted = Yii::$app->security->decryptByKey($decoded, self::getSecretKey());
        return $decrypted !== false ? $decrypted : $this->tiktok_refresh_token;
    }

    /**
     * Verifica se o refresh token está expirado.
     *
     * @return bool
     */
    public function isRefreshTokenExpired(): bool
    {
        if (empty($this->tiktok_refresh_expires_at)) {
            return false;
        }

        $expires = new \DateTime($this->tiktok_refresh_expires_at);
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
    public function getColaborador()
    {
        return $this->hasOne(\app\modules\vendas\models\Colaborador::class, ['id' => 'colaborador_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosts()
    {
        return $this->hasMany(SocialPost::class, ['social_account_id' => 'id']);
    }
}
