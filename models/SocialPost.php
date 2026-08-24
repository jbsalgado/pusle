<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\Json;

/**
 * Esta é a classe de modelo ActiveRecord para a tabela "prest_social_posts".
 *
 * @property string $id (UUID)
 * @property string $tenant_id (UUID)
 * @property string $social_account_id (UUID)
 * @property string $platform
 * @property string $media_type
 * @property string $media_url
 * @property string|null $caption
 * @property string|null $creation_id
 * @property string|null $published_media_id
 * @property string $status
 * @property array|string|null $error_payload
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Usuario $tenant
 * @property SocialAccount $socialAccount
 */
class SocialPost extends ActiveRecord
{
    const PLATFORM_INSTAGRAM = 'INSTAGRAM';
    const PLATFORM_FACEBOOK = 'FACEBOOK';
    const PLATFORM_BOTH = 'BOTH';

    const MEDIA_TYPE_IMAGE = 'IMAGE';
    const MEDIA_TYPE_REELS = 'REELS';
    const MEDIA_TYPE_VIDEO = 'VIDEO';

    const STATUS_PENDING = 'PENDING';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_PUBLISHED = 'PUBLISHED';
    const STATUS_FAILED = 'FAILED';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_social_posts';
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
            [['tenant_id', 'social_account_id', 'media_type', 'media_url'], 'required'],
            [['caption', 'media_url'], 'string'],
            [['media_url'], 'url', 'defaultScheme' => 'https'],
            [['created_at', 'updated_at', 'error_payload'], 'safe'],
            [['platform'], 'string', 'max' => 20],
            [['platform'], 'default', 'value' => self::PLATFORM_INSTAGRAM],
            [['platform'], 'in', 'range' => [self::PLATFORM_INSTAGRAM, self::PLATFORM_FACEBOOK, self::PLATFORM_BOTH]],
            [['media_type'], 'string', 'max' => 20],
            [['media_type'], 'in', 'range' => [self::MEDIA_TYPE_IMAGE, self::MEDIA_TYPE_REELS, self::MEDIA_TYPE_VIDEO]],
            [['status'], 'string', 'max' => 50],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_PUBLISHED, self::STATUS_FAILED]],
            [['creation_id', 'published_media_id'], 'string', 'max' => 255],
            [['tenant_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['tenant_id' => 'id']],
            [['social_account_id'], 'exist', 'skipOnError' => true, 'targetClass' => SocialAccount::class, 'targetAttribute' => ['social_account_id' => 'id']],
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
            'social_account_id' => 'Conta Social ID',
            'platform' => 'Plataforma',
            'media_type' => 'Tipo de Mídia',
            'media_url' => 'URL da Mídia',
            'caption' => 'Legenda',
            'creation_id' => 'Container ID (Meta)',
            'published_media_id' => 'ID da Mídia Publicada',
            'status' => 'Status',
            'error_payload' => 'Payload de Erro',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ];
    }

    /**
     * Atualiza status para PROCESSING e registra o Container ID da Meta.
     *
     * @param string $creationId
     * @return bool
     */
    public function markAsProcessing(string $creationId): bool
    {
        $this->status = self::STATUS_PROCESSING;
        $this->creation_id = $creationId;
        return $this->save(false, ['status', 'creation_id', 'updated_at']);
    }

    /**
     * Atualiza status para PUBLISHED e salva o ID final da publicação.
     *
     * @param string $publishedMediaId
     * @return bool
     */
    public function markAsPublished(string $publishedMediaId): bool
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->published_media_id = $publishedMediaId;
        return $this->save(false, ['status', 'published_media_id', 'updated_at']);
    }

    /**
     * Atualiza status para FAILED e registra os detalhes do erro em formato JSON (PostgreSQL jsonb).
     *
     * @param mixed $errorData String ou Array com detalhes do erro
     * @return bool
     */
    public function markAsFailed($errorData): bool
    {
        $this->status = self::STATUS_FAILED;
        if (is_array($errorData) || is_object($errorData)) {
            $this->error_payload = Json::encode($errorData);
        } else {
            $this->error_payload = Json::encode([
                'message' => (string) $errorData,
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        }
        return $this->save(false, ['status', 'error_payload', 'updated_at']);
    }

    /**
     * Transforma error_payload (JSON string do PG) em Array PHP.
     *
     * @return array|null
     */
    public function getParsedErrorPayload(): ?array
    {
        if (empty($this->error_payload)) {
            return null;
        }
        if (is_array($this->error_payload)) {
            return $this->error_payload;
        }
        try {
            return Json::decode($this->error_payload);
        } catch (\Exception $e) {
            return ['raw' => $this->error_payload];
        }
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
    public function getSocialAccount()
    {
        return $this->hasOne(SocialAccount::class, ['id' => 'social_account_id']);
    }
}
