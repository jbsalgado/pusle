<?php

namespace app\modules\api\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "orcamento_itens".
 *
 * @property int $id
 * @property int $orcamento_id
 * @property string $produto_id
 * @property float $quantidade
 * @property float $preco_unitario
 * @property float|null $desconto_valor
 * @property float $subtotal
 * @property string|null $observacoes
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
            [['orcamento_id', 'produto_id', 'quantidade', 'preco_unitario', 'subtotal'], 'required'],
            [['orcamento_id'], 'integer'],
            [['produto_id'], 'string'],
            [['quantidade', 'preco_unitario', 'desconto_valor', 'subtotal'], 'number'],
            [['observacoes'], 'string'],
            [['produto_id'], 'validarEstoque'],
        ];
    }

    /**
     * Valida se o produto ou variante possui estoque suficiente para o orçamento
     */
    public function validarEstoque($attribute, $params)
    {
        $produto = \app\modules\vendas\models\Produto::findOne($this->produto_id);
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
     * Gets query for [[Orcamento]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrcamento()
    {
        return $this->hasOne(Orcamento::class, ['id' => 'orcamento_id']);
    }

    public function getProduto()
    {
        return $this->hasOne(\app\modules\vendas\models\Produto::class, ['id' => 'produto_id']);
    }
}
