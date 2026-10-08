<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Orcamento;

/**
 * @property int $id
 * @property int $orcamento_id
 * @property string $produto_id
 * @property float $quantidade
 * @property float $preco_unitario
 * @property float $desconto_valor
 * @property float $subtotal
 * @property string $observacoes
 * 
 * @property Orcamento $orcamento
 * @property Produto $produto
 */
class OrcamentoItem extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'orcamento_itens';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['orcamento_id', 'produto_id', 'quantidade', 'preco_unitario'], 'required'],
            [['produto_id'], 'string'],
            [['orcamento_id'], 'integer'],
            [['quantidade'], 'number', 'min' => 0.001],
            [['preco_unitario', 'subtotal', 'desconto_valor'], 'number', 'min' => 0],
            [['observacoes'], 'string'],
            [['orcamento_id'], 'exist', 'skipOnError' => true, 'targetClass' => Orcamento::class, 'targetAttribute' => ['orcamento_id' => 'id']],
            [['produto_id'], 'validarEstoque'],
        ];
    }

    /**
     * Valida se o produto ou variante possui estoque suficiente para o orçamento
     */
    public function validarEstoque($attribute, $params)
    {
        $produto = Produto::findOne($this->produto_id);
        $variante = null;
        if (!$produto) {
            $variante = \app\modules\vendas\models\ProdutoVariante::findOne($this->produto_id);
        }

        if ($produto || $variante) {
            $estoque = $variante ? (float)$variante->estoque_atual : (float)$produto->estoque_atual;
            $permiteNegativo = $variante 
                ? ($variante->produto ? (bool)$variante->produto->permite_estoque_negativo : false) 
                : (bool)$produto->permite_estoque_negativo;
            $nome = $variante ? $variante->getNomeFormatado() : $produto->nome;

            if (!$permiteNegativo) {
                if ($estoque <= 0) {
                    $this->addError($attribute, "O produto '{$nome}' está sem estoque disponível e não pode ser incluído no orçamento.");
                } elseif ((float)$this->quantidade > $estoque) {
                    $this->addError('quantidade', "Estoque insuficiente para o produto '{$nome}'. Disponível: {$estoque}, solicitado: {$this->quantidade}.");
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'orcamento_id' => 'Orçamento',
            'produto_id' => 'Produto',
            'quantidade' => 'Quantidade',
            'preco_unitario' => 'Preço Unitário',
            'desconto_valor' => 'Desconto (R$)',
            'subtotal' => 'Subtotal',
            'observacoes' => 'Observações',
        ];
    }

    /**
     * Antes de salvar, calcula valor total
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->subtotal = $this->quantidade * $this->preco_unitario;
            return true;
        }
        return false;
    }

    public function getOrcamento()
    {
        return $this->hasOne(Orcamento::class, ['id' => 'orcamento_id']);
    }

    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }
}
