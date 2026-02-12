<?php

namespace app\modules\marketplace\models;

use Yii;
use yii\db\ActiveRecord;
use app\modules\vendas\models\Produto;

/**
 * Model: MarketplacePedidoItem
 * Tabela: prest_marketplace_pedido_item
 *
 * @property string $id
 * @property string $pedido_id
 * @property string $marketplace_produto_id
 * @property string $produto_id
 * @property string $titulo
 * @property integer $quantidade
 * @property float $preco_unitario
 * @property float $preco_total
 * @property string $sku
 * @property string $variacao
 * @property array $dados_completos
 * @property string $data_criacao
 *
 * @property MarketplacePedido $pedido
 * @property Produto $produto
 */
class MarketplacePedidoItem extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_marketplace_pedido_item';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pedido_id', 'titulo', 'quantidade', 'preco_unitario', 'preco_total'], 'required'],
            [['pedido_id', 'produto_id'], 'string'],
            [['marketplace_produto_id'], 'string', 'max' => 255],
            [['titulo', 'variacao'], 'string', 'max' => 255],
            [['quantidade'], 'integer', 'min' => 1],
            [['preco_unitario', 'preco_total'], 'number', 'min' => 0],
            [['sku'], 'string', 'max' => 100],
            [['dados_completos'], 'safe'],
            [['pedido_id'], 'exist', 'skipOnError' => true, 'targetClass' => MarketplacePedido::class, 'targetAttribute' => ['pedido_id' => 'id']],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Produto::class, 'targetAttribute' => ['produto_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pedido_id' => 'Pedido',
            'marketplace_produto_id' => 'ID Produto Marketplace',
            'produto_id' => 'Produto',
            'titulo' => 'Título',
            'quantidade' => 'Quantidade',
            'preco_unitario' => 'Preço Unitário',
            'preco_total' => 'Preço Total',
            'sku' => 'SKU',
            'variacao' => 'Variação',
            'data_criacao' => 'Data de Criação',
        ];
    }

    /**
     * Relação com pedido
     */
    public function getPedido()
    {
        return $this->hasOne(MarketplacePedido::class, ['id' => 'pedido_id']);
    }

    /**
     * Relação com produto
     */
    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }
}
