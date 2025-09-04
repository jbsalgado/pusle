<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "cadast_produtos".
 *
 * @property int $id
 * @property int $empresa_id
 * @property string $ref_produto
 * @property string $descricao
 * @property string $preco_venda
 * @property string $descricao_detalhada Campo para texto longo, HTML ou Markdown para a página do produto.
 * @property bool $visivel_no_catalogo Controla se o produto aparece na loja/catálogo (True/False).
 * @property bool $produto_destaque Marca o produto para aparecer em seções de destaque (True/False).
 *
 * @property CadastEmpresas $empresa
 * @property CatalogoProdutoCategoriaAssoc[] $catalogoProdutoCategoriaAssocs
 * @property CatalogoCategorias[] $categorias
 * @property CatalogoProdutoImagens[] $catalogoProdutoImagens
 * @property EstoqMovimentacoes[] $estoqMovimentacoes
 * @property ProdFichaTecnica[] $prodFichaTecnicas
 * @property ProdOrdensProducao[] $prodOrdensProducaos
 * @property VendasPedidoItens[] $vendasPedidoItens
 */
class CadastProdutos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cadast_produtos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'ref_produto', 'descricao', 'preco_venda'], 'required'],
            [['empresa_id'], 'default', 'value' => null],
            [['empresa_id'], 'integer'],
            [['preco_venda'], 'number'],
            [['descricao_detalhada'], 'string'],
            [['visivel_no_catalogo', 'produto_destaque'], 'boolean'],
            [['ref_produto'], 'string', 'max' => 50],
            [['descricao'], 'string', 'max' => 200],
            [['empresa_id', 'ref_produto'], 'unique', 'targetAttribute' => ['empresa_id', 'ref_produto']],
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
            'ref_produto' => 'Ref Produto',
            'descricao' => 'Descricao',
            'preco_venda' => 'Preco Venda',
            'descricao_detalhada' => 'Descricao Detalhada',
            'visivel_no_catalogo' => 'Visivel No Catalogo',
            'produto_destaque' => 'Produto Destaque',
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
        return $this->hasMany(CatalogoProdutoCategoriaAssoc::className(), ['produto_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategorias()
    {
        return $this->hasMany(CatalogoCategorias::className(), ['id' => 'categoria_id'])->viaTable('catalogo_produto_categoria_assoc', ['produto_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCatalogoProdutoImagens()
    {
        return $this->hasMany(CatalogoProdutoImagens::className(), ['produto_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEstoqMovimentacoes()
    {
        return $this->hasMany(EstoqMovimentacoes::className(), ['produto_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProdFichaTecnicas()
    {
        return $this->hasMany(ProdFichaTecnica::className(), ['produto_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProdOrdensProducaos()
    {
        return $this->hasMany(ProdOrdensProducao::className(), ['produto_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVendasPedidoItens()
    {
        return $this->hasMany(VendasPedidoItens::className(), ['produto_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\CadastProdutosQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\CadastProdutosQuery(get_called_class());
    }
}
