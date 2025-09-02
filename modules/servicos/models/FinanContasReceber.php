<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "finan_contas_receber".
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $cliente_id
 * @property int $status_id
 * @property int $pedido_id
 * @property string $descricao
 * @property string $valor
 * @property string $data_vencimento
 *
 * @property CadastClientes $cliente
 * @property CadastEmpresas $empresa
 * @property FinanStatusConta $status
 * @property VendasPedidos $pedido
 */
class FinanContasReceber extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'finan_contas_receber';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'cliente_id', 'status_id', 'descricao', 'valor', 'data_vencimento'], 'required'],
            [['empresa_id', 'cliente_id', 'status_id', 'pedido_id'], 'default', 'value' => null],
            [['empresa_id', 'cliente_id', 'status_id', 'pedido_id'], 'integer'],
            [['valor'], 'number'],
            [['data_vencimento'], 'safe'],
            [['descricao'], 'string', 'max' => 255],
            [['pedido_id'], 'unique'],
            [['cliente_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastClientes::className(), 'targetAttribute' => ['cliente_id' => 'id']],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => FinanStatusConta::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'cliente_id' => 'Cliente ID',
            'status_id' => 'Status ID',
            'pedido_id' => 'Pedido ID',
            'descricao' => 'Descricao',
            'valor' => 'Valor',
            'data_vencimento' => 'Data Vencimento',
        ];
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
        return $this->hasOne(FinanStatusConta::className(), ['id' => 'status_id']);
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
     * @return \app\modules\servicos\query\FinanContasReceberQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\FinanContasReceberQuery(get_called_class());
    }
}
