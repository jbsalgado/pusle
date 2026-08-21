<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * ActiveRecord para a tabela {{%prest_disparo_itens}}.
 *
 * @property string $id
 * @property string $disparo_id
 * @property string $produto_id
 * @property string|null $cliente_id
 * @property string $canal
 * @property string|null $destino
 * @property string|null $card_path
 * @property string|null $card_url
 * @property string|null $mensagem_personalizada
 * @property string $status
 * @property string|null $erro_mensagem
 * @property string|null $enviado_em
 * @property string $created_at
 *
 * @property DisparoMassa $disparo
 * @property Produto $produto
 * @property Cliente $cliente
 */
class DisparoItem extends ActiveRecord
{
    const STATUS_PENDENTE = 'pendente';
    const STATUS_PROCESSANDO = 'processando';
    const STATUS_ENVIADO = 'enviado';
    const STATUS_ERRO = 'erro';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%prest_disparo_itens}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['disparo_id', 'produto_id', 'canal'], 'required'],
            [['mensagem_personalizada', 'erro_mensagem'], 'string'],
            [['enviado_em', 'created_at'], 'safe'],
            [['disparo_id', 'produto_id', 'cliente_id'], 'string', 'max' => 36],
            [['canal', 'status'], 'string', 'max' => 30],
            [['destino', 'card_path', 'card_url'], 'string', 'max' => 500],
            [['status'], 'default', 'value' => self::STATUS_PENDENTE],
        ];
    }

    /**
     * Relacionamento com a campanha pai.
     */
    public function getDisparo()
    {
        return $this->hasOne(DisparoMassa::class, ['id' => 'disparo_id']);
    }

    /**
     * Relacionamento com o Produto.
     */
    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    /**
     * Relacionamento com o Cliente.
     */
    public function getCliente()
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }
}
