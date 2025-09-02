<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "catalogo_categorias".
 *
 * @property int $id
 * @property int $empresa_id
 * @property string $nome
 * @property string $descricao
 * @property bool $ativa
 *
 * @property CadastEmpresas $empresa
 * @property CatalogoProdutoCategoriaAssoc[] $catalogoProdutoCategoriaAssocs
 * @property CadastProdutos[] $produtos
 */
class CatalogoCategorias extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'catalogo_categorias';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'nome'], 'required'],
            [['empresa_id'], 'default', 'value' => null],
            [['empresa_id'], 'integer'],
            [['descricao'], 'string'],
            [['ativa'], 'boolean'],
            [['nome'], 'string', 'max' => 100],
            [['empresa_id', 'nome'], 'unique', 'targetAttribute' => ['empresa_id', 'nome']],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
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
            'nome' => 'Nome',
            'descricao' => 'Descricao',
            'ativa' => 'Ativa',
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
    public function getCatalogoProdutoCategoriaAssocs()
    {
        return $this->hasMany(CatalogoProdutoCategoriaAssoc::className(), ['categoria_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProdutos()
    {
        return $this->hasMany(CadastProdutos::className(), ['id' => 'produto_id'])->viaTable('catalogo_produto_categoria_assoc', ['categoria_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\CatalogoCategoriasQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\CatalogoCategoriasQuery(get_called_class());
    }
}
