<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "estoq_movimentacoes".
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $tipo_movimento_id
 * @property int $material_id
 * @property int $produto_id
 * @property string $data_movimento
 * @property string $quantidade
 * @property string $observacao
 *
 * @property CadastEmpresas $empresa
 * @property CadastMateriais $material
 * @property CadastProdutos $produto
 * @property EstoqTiposMovimento $tipoMovimento
 */
class EstoqMovimentacoes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'estoq_movimentacoes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'tipo_movimento_id', 'quantidade'], 'required'],
            [['empresa_id', 'tipo_movimento_id', 'material_id', 'produto_id'], 'default', 'value' => null],
            [['empresa_id', 'tipo_movimento_id', 'material_id', 'produto_id'], 'integer'],
            [['data_movimento'], 'safe'],
            [['quantidade'], 'number'],
            [['observacao'], 'string'],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
            [['material_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastMateriais::className(), 'targetAttribute' => ['material_id' => 'id']],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastProdutos::className(), 'targetAttribute' => ['produto_id' => 'id']],
            [['tipo_movimento_id'], 'exist', 'skipOnError' => true, 'targetClass' => EstoqTiposMovimento::className(), 'targetAttribute' => ['tipo_movimento_id' => 'id']],
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
            'tipo_movimento_id' => 'Tipo Movimento ID',
            'material_id' => 'Material ID',
            'produto_id' => 'Produto ID',
            'data_movimento' => 'Data Movimento',
            'quantidade' => 'Quantidade',
            'observacao' => 'Observacao',
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
    public function getMaterial()
    {
        return $this->hasOne(CadastMateriais::className(), ['id' => 'material_id']);
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
    public function getTipoMovimento()
    {
        return $this->hasOne(EstoqTiposMovimento::className(), ['id' => 'tipo_movimento_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\EstoqMovimentacoesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\EstoqMovimentacoesQuery(get_called_class());
    }
}
