<?php

namespace app\modules\api\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "orcamentos".
 *
 * @property int $id
 * @property string $usuario_id
 * @property string|null $cliente_id
 * @property string|null $colaborador_vendedor_id
 * @property float $valor_total
 * @property float|null $desconto_valor
 * @property float|null $acrescimo_valor
 * @property string|null $acrescimo_tipo
 * @property string|null $observacao_acrescimo
 * @property string|null $observacoes
 * @property string|null $status
 * @property string|null $data_criacao
 * @property string|null $data_atualizacao
 * @property string|null $data_validade
 * @property string|null $forma_pagamento_id
 *
 * @property OrcamentoItem[] $itens
 */
class Orcamento extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'orcamentos';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'data_criacao',
                'updatedAtAttribute' => 'data_atualizacao',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id'], 'required'],
            [['usuario_id', 'cliente_id', 'colaborador_vendedor_id', 'forma_pagamento_id'], 'string'], // IDs são UUIDs no banco, mas string no PHP
            [['valor_total', 'desconto_valor', 'acrescimo_valor'], 'number'],
            [['observacoes', 'acrescimo_tipo', 'observacao_acrescimo', 'status'], 'string'],
            [['data_criacao', 'data_atualizacao', 'data_validade'], 'safe'],
            [['status'], 'default', 'value' => 'PENDENTE'],
            [['valor_total'], 'default', 'value' => 0],
        ];
    }

    /**
     * Gets query for [[Itens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getItens()
    {
        return $this->hasMany(OrcamentoItem::class, ['orcamento_id' => 'id']);
    }

    public function getCliente()
    {
        return $this->hasOne(\app\modules\vendas\models\Cliente::class, ['id' => 'cliente_id']);
    }

    public function getVendedor()
    {
        return $this->hasOne(\app\modules\vendas\models\Colaborador::class, ['id' => 'colaborador_vendedor_id']);
    }

    public function getFormaPagamento()
    {
        return $this->hasOne(\app\modules\vendas\models\FormaPagamento::class, ['id' => 'forma_pagamento_id']);
    }
}
