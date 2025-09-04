<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "vendas_status_pedido".
 *
 * @property int $id
 * @property string $descricao
 *
 * @property VendasPedidos[] $vendasPedidos
 */
class VendasStatusPedido extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'vendas_status_pedido';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descricao'], 'required'],
            [['descricao'], 'string', 'max' => 50],
            [['descricao'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'descricao' => 'Descricao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVendasPedidos()
    {
        return $this->hasMany(VendasPedidos::className(), ['status_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\VendasStatusPedidoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\VendasStatusPedidoQuery(get_called_class());
    }
}
