<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model para os itens que compõem um Kit/Combo.
 *
 * @property string $id
 * @property string $kit_id
 * @property string $produto_id
 * @property float $quantidade
 * @property string $data_criacao
 *
 * @property Produto $kit
 * @property Produto $produto
 */
class ProdutoKitItem extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_produto_kit_itens';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['kit_id', 'produto_id', 'quantidade'], 'required'],
            [['kit_id', 'produto_id'], 'string'],
            [['quantidade'], 'number', 'min' => 0.001],
            [['kit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Produto::class, 'targetAttribute' => ['kit_id' => 'id']],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Produto::class, 'targetAttribute' => ['produto_id' => 'id']],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKit()
    {
        return $this->hasOne(Produto::class, ['id' => 'kit_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }
}
