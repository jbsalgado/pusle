<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model: Comanda (Ficha de consumo do cliente)
 * Tabela: prest_comandas
 *
 * @property string $id
 * @property string $usuario_id
 * @property string|null $mesa_id
 * @property string $numero_comanda
 * @property string|null $cliente_nome
 * @property string $status
 * @property string $data_abertura
 * @property string|null $data_fechamento
 *
 * @property Mesa|null $mesa
 * @property ComandaItem[] $itens
 */
class Comanda extends ActiveRecord
{
    const STATUS_ABERTA = 'aberta';
    const STATUS_FECHADA = 'fechada';
    const STATUS_CANCELADA = 'cancelada';

    public static function tableName()
    {
        return 'prest_comandas';
    }

    public function rules()
    {
        return [
            [['usuario_id', 'numero_comanda'], 'required'],
            [['id', 'usuario_id', 'mesa_id'], 'string'],
            [['numero_comanda'], 'string', 'max' => 30],
            [['cliente_nome'], 'string', 'max' => 150],
            [['status'], 'string', 'max' => 30],
            [['status'], 'default', 'value' => self::STATUS_ABERTA],
            [['data_abertura', 'data_fechamento'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Dono da Loja',
            'mesa_id' => 'Mesa',
            'numero_comanda' => 'Número da Comanda/Ficha',
            'cliente_nome' => 'Nome do Cliente',
            'status' => 'Status',
            'data_abertura' => 'Abertura',
            'data_fechamento' => 'Fechamento',
        ];
    }

    public function getMesa()
    {
        return $this->hasOne(Mesa::class, ['id' => 'mesa_id']);
    }

    public function getItens()
    {
        return $this->hasMany(ComandaItem::class, ['comanda_id' => 'id']);
    }

    /**
     * Calcula a soma total dos itens da comanda
     */
    public function getValorTotal()
    {
        $total = 0.00;
        foreach ($this->itens as $item) {
            $total += ((float)$item->quantidade * (float)$item->valor_unitario);
        }
        return $total;
    }
}
