<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Produto;


/**
 * ============================================================================================================
 * Model: ProdutoFoto
 * ============================================================================================================
 * Tabela: prest_produto_fotos
 * 
 * @property string $id
 * @property string $produto_id
 * @property string $arquivo_nome
 * @property string $arquivo_path
 * @property boolean $eh_principal
 * @property integer $ordem
 * @property string $data_upload
 * 
 * @property Produto $produto
 */
class ProdutoFoto extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_produto_fotos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['produto_id', 'arquivo_nome', 'arquivo_path'], 'required'],
            [['produto_id'], 'string'],
            [['eh_principal'], 'boolean'],
            [['ordem'], 'integer'],
            [['ordem'], 'default', 'value' => 0],
            [['arquivo_nome'], 'string', 'max' => 255],
            [['arquivo_path'], 'string', 'max' => 500],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Produto::class, 'targetAttribute' => ['produto_id' => 'id']],
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
            'arquivo_nome' => 'Nome do Arquivo',
            'arquivo_path' => 'Caminho',
            'eh_principal' => 'Foto Principal',
            'ordem' => 'Ordem',
            'data_upload' => 'Data de Upload',
        ];
    }

    /**
     * Antes de salvar, se for principal, desmarcar outras fotos principais
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Gera UUID se for um novo registro e não tiver ID definido
            if ($insert && empty($this->id)) {
                try {
                    // Tenta usar gen_random_uuid() do PostgreSQL (nativo, não precisa de extensão)
                    $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                    $this->id = $uuid;
                } catch (\Exception $e) {
                    // Fallback: gera UUID no PHP usando ramsey/uuid ou função nativa
                    if (function_exists('uuid_create')) {
                        $uuid = uuid_create(UUID_TYPE_RANDOM);
                        $this->id = $uuid;
                    } else {
                        // Gera UUID v4 manualmente
                        $this->id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000,
                            mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                    }
                }
            }
            
            if ($this->eh_principal) {
                // Desmarcar outras fotos principais do mesmo produto
                self::updateAll(
                    ['eh_principal' => false],
                    ['produto_id' => $this->produto_id]
                );
            }
            return true;
        }
        return false;
    }

    /**
     * Retorna URL completa da foto
     */
    public function getUrl()
    {
        return Yii::getAlias('@web') . '/' . $this->arquivo_path;
    }

    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }
}