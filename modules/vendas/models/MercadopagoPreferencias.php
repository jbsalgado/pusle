<?php

namespace app\modules\vendas\models;

use Yii;

/**
 * This is the model class for table "mercadopago_preferencias".
 *
 * @property int $id
 * @property string $preference_id ID da preferência gerada pelo Mercado Pago
 * @property string $external_reference Referência única do pedido no sistema
 * @property string $usuario_id
 * @property string|null $payment_id
 * @property string|null $payment_status
 * @property float|null $transaction_amount
 * @property string|null $payment_type
 * @property float $valor_total
 * @property string|null $status
 * @property string|null $dados_request JSON com dados originais da requisição
 * @property string|null $ultima_atualizacao
 * @property string|null $created_at
 *
 * @property PrestUsuarios $usuario
 */
class MercadopagoPreferencias extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mercadopago_preferencias';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['payment_id', 'transaction_amount', 'payment_type', 'dados_request', 'ultima_atualizacao'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 'pending'],
            [['preference_id', 'external_reference', 'usuario_id', 'valor_total'], 'required'],
            [['usuario_id'], 'string'],
            [['transaction_amount', 'valor_total'], 'number'],
            [['dados_request', 'ultima_atualizacao', 'created_at'], 'safe'],
            [['preference_id', 'external_reference', 'payment_id'], 'string', 'max' => 100],
            [['payment_status', 'payment_type', 'status'], 'string', 'max' => 50],
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
            'preference_id' => 'Preference ID',
            'external_reference' => 'External Reference',
            'usuario_id' => 'Usuario ID',
            'payment_id' => 'Payment ID',
            'payment_status' => 'Payment Status',
            'transaction_amount' => 'Transaction Amount',
            'payment_type' => 'Payment Type',
            'valor_total' => 'Valor Total',
            'status' => 'Status',
            'dados_request' => 'Dados Request',
            'ultima_atualizacao' => 'Ultima Atualizacao',
            'created_at' => 'Created At',
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
     * @return \app\modules\vendas\query\MercadopagoPreferenciasQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\vendas\query\MercadopagoPreferenciasQuery(get_called_class());
    }

}
