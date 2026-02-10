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
        ];
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
