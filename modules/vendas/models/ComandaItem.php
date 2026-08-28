<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model: Item da Comanda/Pedido
 * Tabela: prest_comanda_itens
 *
 * @property string $id
 * @property string $comanda_id
 * @property string $produto_id
 * @property float $quantidade
 * @property float $valor_unitario
 * @property string|null $observacoes
 * @property string $destino_preparo
 * @property string $status_preparo
 * @property string $data_pedido
 *
 * @property Comanda $comanda
 * @property Produto $produto
 */
class ComandaItem extends ActiveRecord
{
    const DESTINO_COZINHA = 'cozinha';
    const DESTINO_BAR = 'bar';
    const DESTINO_CHAPA = 'chapa';
    const DESTINO_COPA = 'copa';

    const STATUS_PENDENTE = 'pendente';
    const STATUS_EM_PREPARO = 'em_preparo';
    const STATUS_PRONTO = 'pronto';
    const STATUS_ENTREGUE = 'entregue';

    public static function tableName()
    {
        return 'prest_comanda_itens';
    }

    public function rules()
    {
        return [
            [['comanda_id', 'produto_id', 'quantidade', 'valor_unitario'], 'required'],
            [['id', 'comanda_id', 'produto_id'], 'string'],
            [['quantidade'], 'number', 'min' => 0.001],
            [['valor_unitario'], 'number', 'min' => 0],
            [['observacoes'], 'string'],
            [['destino_preparo'], 'string', 'max' => 30],
            [['destino_preparo'], 'default', 'value' => self::DESTINO_COZINHA],
            [['status_preparo'], 'string', 'max' => 30],
            [['status_preparo'], 'default', 'value' => self::STATUS_PENDENTE],
            [['data_pedido'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'comanda_id' => 'Comanda',
            'produto_id' => 'Produto',
            'quantidade' => 'Quantidade',
            'valor_unitario' => 'Valor Unitário',
            'observacoes' => 'Observações / Adicionais',
            'destino_preparo' => 'Destino de Preparo',
            'status_preparo' => 'Status do Preparo',
            'data_pedido' => 'Hora do Pedido',
        ];
    }

    public function getComanda()
    {
        return $this->hasOne(Comanda::class, ['id' => 'comanda_id']);
    }

    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    public function getSubtotal()
    {
        return (float)$this->quantidade * (float)$this->valor_unitario;
    }
}
