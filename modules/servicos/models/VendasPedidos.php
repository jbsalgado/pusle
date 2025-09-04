<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "vendas_pedidos".
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $cliente_id
 * @property int $status_id
 * @property string $data_pedido
 * @property string $valor_total
 *
 * @property FinanContasReceber $finanContasReceber
 * @property VendasPedidoItens[] $vendasPedidoItens
 * @property CadastClientes $cliente
 * @property CadastEmpresas $empresa
 * @property VendasStatusPedido $status
 */
class VendasPedidos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'vendas_pedidos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'cliente_id', 'status_id', 'valor_total'], 'required'],
            [['empresa_id', 'cliente_id', 'status_id'], 'default', 'value' => null],
            [['empresa_id', 'cliente_id', 'status_id'], 'integer'],
            [['data_pedido'], 'safe'],
            [['valor_total'], 'number'],
            [['cliente_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastClientes::className(), 'targetAttribute' => ['cliente_id' => 'id']],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => VendasStatusPedido::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'cliente_id' => 'Cliente ID',
            'status_id' => 'Status ID',
            'data_pedido' => 'Data Pedido',
            'valor_total' => 'Valor Total',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFinanContasReceber()
    {
        return $this->hasOne(FinanContasReceber::className(), ['pedido_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVendasPedidoItens()
    {
        return $this->hasMany(VendasPedidoItens::className(), ['pedido_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCliente()
    {
        return $this->hasOne(CadastClientes::className(), ['id' => 'cliente_id']);
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
    public function getStatus()
    {
        return $this->hasOne(VendasStatusPedido::className(), ['id' => 'status_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\VendasPedidosQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\VendasPedidosQuery(get_called_class());
    }
}
