<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "prod_ficha_tecnica".
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $produto_id
 * @property int $material_id
 * @property string $quantidade_necessaria
 *
 * @property CadastEmpresas $empresa
 * @property CadastMateriais $material
 * @property CadastProdutos $produto
 */
class ProdFichaTecnica extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prod_ficha_tecnica';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'produto_id', 'material_id', 'quantidade_necessaria'], 'required'],
            [['empresa_id', 'produto_id', 'material_id'], 'default', 'value' => null],
            [['empresa_id', 'produto_id', 'material_id'], 'integer'],
            [['quantidade_necessaria'], 'number'],
            [['empresa_id', 'produto_id', 'material_id'], 'unique', 'targetAttribute' => ['empresa_id', 'produto_id', 'material_id']],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
            [['material_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastMateriais::className(), 'targetAttribute' => ['material_id' => 'id']],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastProdutos::className(), 'targetAttribute' => ['produto_id' => 'id']],
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
            'produto_id' => 'Produto ID',
            'material_id' => 'Material ID',
            'quantidade_necessaria' => 'Quantidade Necessaria',
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
     * {@inheritdoc}
     * @return \app\modules\servicos\query\ProdFichaTecnicaQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\ProdFichaTecnicaQuery(get_called_class());
    }
}
