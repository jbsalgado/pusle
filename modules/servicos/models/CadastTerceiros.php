<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "cadast_terceiros".
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $tipo_pessoa_id
 * @property string $nome_razao_social
 * @property string $cpf_cnpj
 * @property string $telefone
 * @property string $email
 *
 * @property CadastEmpresas $empresa
 * @property CadastTiposPessoa $tipoPessoa
 * @property FinanContasPagar[] $finanContasPagars
 * @property ProdLotes[] $prodLotes
 */
class CadastTerceiros extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cadast_terceiros';
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
            [['telefone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
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
            'telefone' => 'Telefone',
            'email' => 'Email',
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
    public function getFinanContasPagars()
    {
        return $this->hasMany(FinanContasPagar::className(), ['terceiro_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProdLotes()
    {
        return $this->hasMany(ProdLotes::className(), ['terceiro_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\CadastTerceirosQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\CadastTerceirosQuery(get_called_class());
    }
}
