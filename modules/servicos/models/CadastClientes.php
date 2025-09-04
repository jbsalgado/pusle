<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "cadast_clientes".
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $tipo_pessoa_id
 * @property string $nome_razao_social
 * @property string $cpf_cnpj
 *
 * @property CadastEmpresas $empresa
 * @property CadastTiposPessoa $tipoPessoa
 * @property FinanContasReceber[] $finanContasRecebers
 * @property VendasPedidos[] $vendasPedidos
 */
class CadastClientes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cadast_clientes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'tipo_pessoa_id', 'nome_razao_social'], 'required'],
            [['empresa_id', 'tipo_pessoa_id'], 'default', 'value' => null],
            [['empresa_id', 'tipo_pessoa_id'], 'integer'],
            [['nome_razao_social'], 'string', 'max' => 150],
            [['cpf_cnpj'], 'string', 'max' => 18],
            [['empresa_id', 'cpf_cnpj'], 'unique', 'targetAttribute' => ['empresa_id', 'cpf_cnpj']],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
            [['tipo_pessoa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastTiposPessoa::className(), 'targetAttribute' => ['tipo_pessoa_id' => 'id']],
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
            'tipo_pessoa_id' => 'Tipo Pessoa ID',
            'nome_razao_social' => 'Nome Razao Social',
            'cpf_cnpj' => 'Cpf Cnpj',
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
    public function getTipoPessoa()
    {
        return $this->hasOne(CadastTiposPessoa::className(), ['id' => 'tipo_pessoa_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFinanContasRecebers()
    {
        return $this->hasMany(FinanContasReceber::className(), ['cliente_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVendasPedidos()
    {
        return $this->hasMany(VendasPedidos::className(), ['cliente_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\CadastClientesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\CadastClientesQuery(get_called_class());
    }
}
