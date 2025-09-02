<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "catalogo_produto_categoria_assoc".
 *
 * @property int $produto_id
 * @property int $categoria_id
 *
 * @property CadastProdutos $produto
 * @property CatalogoCategorias $categoria
 */
class CatalogoProdutoCategoriaAssoc extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'catalogo_produto_categoria_assoc';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['produto_id', 'categoria_id'], 'required'],
            [['produto_id', 'categoria_id'], 'default', 'value' => null],
            [['produto_id', 'categoria_id'], 'integer'],
            [['produto_id', 'categoria_id'], 'unique', 'targetAttribute' => ['produto_id', 'categoria_id']],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastProdutos::className(), 'targetAttribute' => ['produto_id' => 'id']],
            [['categoria_id'], 'exist', 'skipOnError' => true, 'targetClass' => CatalogoCategorias::className(), 'targetAttribute' => ['categoria_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'produto_id' => 'Produto ID',
            'categoria_id' => 'Categoria ID',
        ];
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
    public function getCategoria()
    {
        return $this->hasOne(CatalogoCategorias::className(), ['id' => 'categoria_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\CatalogoProdutoCategoriaAssocQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\CatalogoProdutoCategoriaAssocQuery(get_called_class());
    }
}
