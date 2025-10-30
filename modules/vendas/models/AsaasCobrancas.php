<?php

namespace app\modules\vendas\models;

use Yii;

/**
 * This is the model class for table "asaas_cobrancas".
 *
 * @property int $id
 * @property string $payment_id
 * @property string $external_reference
 * @property string $usuario_id
 * @property string|null $cliente_id
 * @property string|null $customer_asaas_id
 * @property float $valor
 * @property float|null $valor_recebido
 * @property string $metodo_pagamento
 * @property string|null $status
 * @property string|null $status_asaas
 * @property string|null $vencimento
 * @property string|null $data_pagamento
 * @property string|null $dados_request
 * @property string|null $dados_cobranca
 * @property string|null $ambiente
 * @property string|null $created_at
 * @property string|null $ultima_atualizacao
 * @property string|null $pedido_id
 *
 * @property PrestUsuarios $usuario
 */
class AsaasCobrancas extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'asaas_cobrancas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cliente_id', 'customer_asaas_id', 'valor_recebido', 'status_asaas', 'vencimento', 'data_pagamento', 'dados_request', 'dados_cobranca', 'ultima_atualizacao', 'pedido_id'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 'pending'],
            [['ambiente'], 'default', 'value' => 'producao'],
            [['payment_id', 'external_reference', 'usuario_id', 'valor', 'metodo_pagamento'], 'required'],
            [['usuario_id', 'cliente_id', 'pedido_id'], 'string'],
            [['valor', 'valor_recebido'], 'number'],
            [['vencimento', 'data_pagamento', 'dados_request', 'dados_cobranca', 'created_at', 'ultima_atualizacao'], 'safe'],
            [['payment_id', 'external_reference', 'customer_asaas_id'], 'string', 'max' => 100],
            [['metodo_pagamento', 'status', 'status_asaas'], 'string', 'max' => 50],
            [['ambiente'], 'string', 'max' => 20],
            [['payment_id'], 'unique'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => PrestUsuarios::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'payment_id' => 'Payment ID',
            'external_reference' => 'External Reference',
            'usuario_id' => 'Usuario ID',
            'cliente_id' => 'Cliente ID',
            'customer_asaas_id' => 'Customer Asaas ID',
            'valor' => 'Valor',
            'valor_recebido' => 'Valor Recebido',
            'metodo_pagamento' => 'Metodo Pagamento',
            'status' => 'Status',
            'status_asaas' => 'Status Asaas',
            'vencimento' => 'Vencimento',
            'data_pagamento' => 'Data Pagamento',
            'dados_request' => 'Dados Request',
            'dados_cobranca' => 'Dados Cobranca',
            'ambiente' => 'Ambiente',
            'created_at' => 'Created At',
            'ultima_atualizacao' => 'Ultima Atualizacao',
            'pedido_id' => 'Pedido ID',
        ];
    }

    /**
     * Gets query for [[Usuario]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestUsuariosQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(PrestUsuarios::class, ['id' => 'usuario_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\query\AsaasCobrancasQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\vendas\query\AsaasCobrancasQuery(get_called_class());
    }

}
