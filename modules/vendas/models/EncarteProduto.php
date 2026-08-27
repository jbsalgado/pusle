<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model: EncarteProduto
 * Tabela: prest_encarte_produtos
 *
 * @property string $id
 * @property string $encarte_id
 * @property string $produto_id
 * @property float $preco_oferta
 * @property int $ordem
 * @property bool $destaque
 * @property string $created_at
 *
 * @property Encarte $encarte
 * @property Produto $produto
 */
class EncarteProduto extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%prest_encarte_produtos}}';
    }

    public function rules()
    {
        return [
            [['encarte_id', 'produto_id'], 'required'],
            [['encarte_id'], 'string', 'max' => 36],
            [['produto_id'], 'string', 'max' => 36],
            [['preco_oferta'], 'number'],
            [['ordem'], 'integer'],
            [['destaque'], 'boolean'],
            [['ordem'], 'default', 'value' => 0],
            [['destaque'], 'default', 'value' => false],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'encarte_id' => 'Encarte',
            'produto_id' => 'Produto',
            'preco_oferta' => 'Preço de Oferta',
            'ordem' => 'Ordem',
            'destaque' => 'Destaque Especial',
            'created_at' => 'Data de Inserção',
        ];
    }

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
                    $this->id = sprintf(
                        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                }
            }
            return true;
        }
        return false;
    }

    public function getEncarte()
    {
        return $this->hasOne(Encarte::class, ['id' => 'encarte_id']);
    }

    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    public function getPrecoFinal()
    {
        if (!empty($this->preco_oferta) && $this->preco_oferta > 0) {
            return (float)$this->preco_oferta;
        }
        return $this->produto ? (float)$this->produto->preco_venda_sugerido : 0.0;
    }
}
