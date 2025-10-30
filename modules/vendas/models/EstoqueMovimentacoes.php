<?php

namespace app\modules\vendas\models;

use Yii;

/**
 * This is the model class for table "prest_estoque_movimentacoes".
 *
 * @property string $id
 * @property string $produto_id
 * @property string $usuario_id
 * @property string $tipo_movimentacao
 * @property int $quantidade
 * @property int $saldo_anterior
 * @property int $saldo_novo
 * @property string|null $venda_id
 * @property string|null $observacao
 * @property string $data_movimentacao
 *
 * @property PrestProdutos $produto
 * @property PrestUsuarios $usuario
 * @property PrestVendas $venda
 */
class EstoqueMovimentacoes extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_estoque_movimentacoes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['venda_id', 'observacao'], 'default', 'value' => null],
            [['id', 'produto_id', 'usuario_id', 'tipo_movimentacao', 'quantidade', 'saldo_anterior', 'saldo_novo'], 'required'],
            [['id', 'produto_id', 'usuario_id', 'venda_id', 'observacao'], 'string'],
            [['quantidade', 'saldo_anterior', 'saldo_novo'], 'default', 'value' => null],
            [['quantidade', 'saldo_anterior', 'saldo_novo'], 'integer'],
            [['data_movimentacao'], 'safe'],
            [['tipo_movimentacao'], 'string', 'max' => 20],
            [['id'], 'unique'],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => PrestProdutos::class, 'targetAttribute' => ['produto_id' => 'id']],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => PrestUsuarios::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['venda_id'], 'exist', 'skipOnError' => true, 'targetClass' => PrestVendas::class, 'targetAttribute' => ['venda_id' => 'id']],
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
            'usuario_id' => 'Usuario ID',
            'tipo_movimentacao' => 'Tipo Movimentacao',
            'quantidade' => 'Quantidade',
            'saldo_anterior' => 'Saldo Anterior',
            'saldo_novo' => 'Saldo Novo',
            'venda_id' => 'Venda ID',
            'observacao' => 'Observacao',
            'data_movimentacao' => 'Data Movimentacao',
        ];
    }

    /**
     * Gets query for [[Produto]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestProdutosQuery
     */
    public function getProduto()
    {
        return $this->hasOne(PrestProdutos::class, ['id' => 'produto_id']);
    }

    /**
     * Gets query for [[Usuario]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestUsuariosQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(PrestUsuarios::class, ['id' => 'usuario_id']);
    }

    /**
     * Gets query for [[Venda]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestVendasQuery
     */
    public function getVenda()
    {
        return $this->hasOne(PrestVendas::class, ['id' => 'venda_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\query\EstoqueMovimentacoesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\vendas\query\EstoqueMovimentacoesQuery(get_called_class());
    }

}
