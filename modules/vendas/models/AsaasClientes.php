<?php

namespace app\modules\vendas\models;

use Yii;

/**
 * This is the model class for table "asaas_clientes".
 *
 * @property int $id
 * @property string $usuario_id
 * @property string $customer_asaas_id
 * @property string $cpf_cnpj
 * @property string|null $nome
 * @property string|null $email
 * @property string|null $created_at
 *
 * @property PrestUsuarios $usuario
 */
class AsaasClientes extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'asaas_clientes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome', 'email'], 'default', 'value' => null],
            [['usuario_id', 'customer_asaas_id', 'cpf_cnpj'], 'required'],
            [['usuario_id'], 'string'],
            [['created_at'], 'safe'],
            [['customer_asaas_id'], 'string', 'max' => 100],
            [['cpf_cnpj'], 'string', 'max' => 20],
            [['nome', 'email'], 'string', 'max' => 255],
            [['usuario_id', 'cpf_cnpj'], 'unique', 'targetAttribute' => ['usuario_id', 'cpf_cnpj']],
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
            'usuario_id' => 'Usuario ID',
            'customer_asaas_id' => 'Customer Asaas ID',
            'cpf_cnpj' => 'Cpf Cnpj',
            'nome' => 'Nome',
            'email' => 'Email',
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
     * @return \app\modules\vendas\query\AsaasClientesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\vendas\query\AsaasClientesQuery(get_called_class());
    }

}
