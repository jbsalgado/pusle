<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\modules\vendas\models\Venda;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Produto;

/**
 * ============================================================================================================
 * Model: VendaItem
 * ============================================================================================================
 * Tabela: prest_venda_itens
 * 
 * @property string $id
 * @property string $venda_id
 * @property string $produto_id
 * @property integer $quantidade
 * @property float $preco_unitario_venda
 * @property float $valor_total_item
 * 
 * @property Venda $venda
 * @property Produto $produto
 */
class VendaItem extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_venda_itens';
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['venda_id', 'produto_id', 'quantidade', 'preco_unitario_venda'], 'required'],
            [['venda_id', 'produto_id'], 'string'],
            [['quantidade'], 'integer', 'min' => 1],
            [['preco_unitario_venda', 'valor_total_item', 'desconto_percentual', 'desconto_valor'], 'number', 'min' => 0],
            [['venda_id'], 'exist', 'skipOnError' => true, 'targetClass' => Venda::class, 'targetAttribute' => ['venda_id' => 'id']],
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
            'venda_id' => 'Venda',
            'produto_id' => 'Produto',
            'quantidade' => 'Quantidade',
            'preco_unitario_venda' => 'Preço Unitário',
            'valor_total_item' => 'Valor Total',
            'desconto_percentual' => 'Desconto (%)',
            'desconto_valor' => 'Desconto (R$)',
        ];
    }

    /**
     * Antes de salvar, calcula valor total
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Garante que descontos são numéricos
            $this->desconto_percentual = (float)$this->desconto_percentual;
            $this->desconto_valor = (float)$this->desconto_valor;

            // Calcula o subtotal sem desconto
            $subtotal = $this->quantidade * $this->preco_unitario_venda;

            // Aplica desconto (valor tem prioridade sobre percentual se ambos forem > 0, mas idealmente usa-se um ou outro)
            // Aqui assumimos que o controller já define o desconto_valor corretamente se for percentual

            // Mas por segurança, recalculamos o valor total subtraindo o desconto_valor salvo
            $totalComDesconto = $subtotal - $this->desconto_valor;

            // Evita valor negativo
            $this->valor_total_item = max(0, $totalComDesconto);

            return true;
        }
        return false;
    }

    public function getVenda()
    {
        return $this->hasOne(Venda::class, ['id' => 'venda_id']);
    }

    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    // VendaItem.php - ADICIONADO:
    public function fields()
    {
        $fields = parent::fields();
        $fields['produto'] = 'produto';
        return $fields;
    }
}
