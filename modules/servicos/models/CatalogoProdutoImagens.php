<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "catalogo_produto_imagens".
 *
 * @property int $id
 * @property int $produto_id
 * @property string $url_imagem
 * @property string $texto_alternativo
 * @property int $ordem_exibicao
 *
 * @property CadastProdutos $produto
 */
class CatalogoProdutoImagens extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'catalogo_produto_imagens';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['produto_id', 'url_imagem'], 'required'],
            [['produto_id', 'ordem_exibicao'], 'default', 'value' => null],
            [['produto_id', 'ordem_exibicao'], 'integer'],
            [['url_imagem'], 'string'],
            [['texto_alternativo'], 'string', 'max' => 150],
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
            'produto_id' => 'Produto ID',
            'url_imagem' => 'Url Imagem',
            'texto_alternativo' => 'Texto Alternativo',
            'ordem_exibicao' => 'Ordem Exibicao',
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
     * {@inheritdoc}
     * @return \app\modules\servicos\query\CatalogoProdutoImagensQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\CatalogoProdutoImagensQuery(get_called_class());
    }
}
