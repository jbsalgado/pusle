<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Orcamento;

/**
 * ============================================================================================================
 * Model: OrcamentoItem
 * ============================================================================================================
 * Tabela: prest_orcamento_itens
 * 
 * @property string $id
 * @property string $orcamento_id
 * @property string $produto_id
 * @property integer $quantidade
 * @property float $preco_unitario
 * @property float $valor_total
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
        return 'prest_orcamento_itens';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['orcamento_id', 'produto_id', 'quantidade', 'preco_unitario', 'valor_total'], 'required'],
            [['orcamento_id', 'produto_id'], 'string'],
            [['quantidade'], 'integer', 'min' => 1],
            [['preco_unitario', 'valor_total'], 'number', 'min' => 0],
            [['orcamento_id'], 'exist', 'skipOnError' => true, 'targetClass' => Orcamento::class, 'targetAttribute' => ['orcamento_id' => 'id']],
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
            'orcamento_id' => 'Orçamento',
            'produto_id' => 'Produto',
            'quantidade' => 'Quantidade',
            'preco_unitario' => 'Preço Unitário',
            'valor_total' => 'Valor Total',
        ];
    }

    /**
     * Antes de salvar, calcula valor total
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->valor_total = $this->quantidade * $this->preco_unitario;
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