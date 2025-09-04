<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "vendas_pedido_itens".
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $pedido_id
 * @property int $produto_id
 * @property int $quantidade
 * @property string $preco_unitario
 *
 * @property CadastEmpresas $empresa
 * @property CadastProdutos $produto
 * @property VendasPedidos $pedido
 */
class VendasPedidoItens extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'vendas_pedido_itens';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'pedido_id', 'produto_id', 'quantidade', 'preco_unitario'], 'required'],
            [['empresa_id', 'pedido_id', 'produto_id', 'quantidade'], 'default', 'value' => null],
            [['empresa_id', 'pedido_id', 'produto_id', 'quantidade'], 'integer'],
            [['preco_unitario'], 'number'],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastProdutos::className(), 'targetAttribute' => ['produto_id' => 'id']],
            [['pedido_id'], 'exist', 'skipOnError' => true, 'targetClass' => VendasPedidos::className(), 'targetAttribute' => ['pedido_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'empresa_id' => 'Empresa ID',
            'pedido_id' => 'Pedido ID',
            'produto_id' => 'Produto ID',
            'quantidade' => 'Quantidade',
            'preco_unitario' => 'Preco Unitario',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmpresa()
    {
        return $this->hasOne(CadastEmpresas::className(), ['id' => 'empresa_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduto()
    {
        return $this->hasOne(CadastProdutos::className(), ['id' => 'produto_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPedido()
    {
        return $this->hasOne(VendasPedidos::className(), ['id' => 'pedido_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\VendasPedidoItensQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\VendasPedidoItensQuery(get_called_class());
    }
}
