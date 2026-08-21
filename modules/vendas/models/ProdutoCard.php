<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Produto;

/**
 * Model: ProdutoCard
 * Tabela: prest_produto_cards
 *
 * @property string $id
 * @property string $produto_id
 * @property string $usuario_id
 * @property string $formato
 * @property string $card_path
 * @property string $card_url
 * @property array $metadata
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Produto $produto
 * @property Usuario $usuario
 */
class ProdutoCard extends ActiveRecord
{
    const FORMATO_FEED = 'feed';
    const FORMATO_STORIES = 'stories';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_produto_cards';
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
            [['produto_id', 'usuario_id', 'formato', 'card_path'], 'required'],
            [['produto_id', 'usuario_id'], 'string', 'max' => 36],
            [['formato'], 'in', 'range' => [self::FORMATO_FEED, self::FORMATO_STORIES]],
            [['card_path', 'card_url'], 'string', 'max' => 500],
            [['metadata'], 'safe'],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Produto::class, 'targetAttribute' => ['produto_id' => 'id']],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'produto_id' => 'Produto',
            'usuario_id' => 'Usuário',
            'formato' => 'Formato (Feed / Stories)',
            'card_path' => 'Caminho do Arquivo',
            'card_url' => 'URL Pública',
            'metadata' => 'Metadados',
            'data_criacao' => 'Data de Geração',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Hook beforeSave para gerar UUID no PHP se necessário
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && empty($this->id)) {
                try {
                    $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                    if (!empty($uuid)) {
                        $this->id = $uuid;
                    }
                } catch (\Exception $e) {
                    if (function_exists('uuid_create')) {
                        $this->id = uuid_create(UUID_TYPE_RANDOM);
                    } else {
                        $this->id = sprintf(
                            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                    }
                }
            }

            // Encode json se for array
            if (is_array($this->metadata)) {
                $this->metadata = json_encode($this->metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            return true;
        }
        return false;
    }

    /**
     * Hook afterFind para decode de metadata em array
     */
    public function afterFind()
    {
        parent::afterFind();
        if (is_string($this->metadata)) {
            $this->metadata = json_decode($this->metadata, true);
        }
    }

    /**
     * Retorna a URL pública completa do card
     */
    public function getUrlCompleta()
    {
        if (!empty($this->card_url)) {
            return $this->card_url;
        }

        $caminhoRelativo = ltrim($this->card_path, '/');
        if (Yii::$app->has('request') && Yii::$app->get('request') instanceof \yii\web\Request) {
            return \yii\helpers\Url::to('@web/' . $caminhoRelativo, true);
        }
        return '/' . $caminhoRelativo;
    }

    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
}
