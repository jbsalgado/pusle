<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * ActiveRecord para a tabela {{%prest_disparos_massa}}.
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $titulo
 * @property array $canais
 * @property array $configuracoes
 * @property string|null $mensagem_texto
 * @property string $status
 * @property int $total_itens
 * @property int $itens_enviados
 * @property int $itens_erro
 * @property string $created_at
 * @property string $updated_at
 *
 * @property DisparoItem[] $itens
 */
class DisparoMassa extends ActiveRecord
{
    const STATUS_PENDENTE = 'pendente';
    const STATUS_PROCESSANDO = 'processando';
    const STATUS_CONCLUIDO = 'concluido';
    const STATUS_CANCELADO = 'cancelado';

    const CANAL_STATUS = 'status';
    const CANAL_WHATSAPP = 'whatsapp';
    const CANAL_EMAIL = 'email';
    const CANAL_HUB = 'hub';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%prest_disparos_massa}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id', 'titulo'], 'required'],
            [['mensagem_texto'], 'string'],
            [['total_itens', 'itens_enviados', 'itens_erro'], 'integer'],
            [['canais', 'configuracoes', 'created_at', 'updated_at'], 'safe'],
            [['usuario_id', 'titulo', 'status'], 'string', 'max' => 255],
            [['status'], 'default', 'value' => self::STATUS_PENDENTE],
        ];
    }

    /**
     * Relacionamento com os itens do disparo.
     */
    public function getItens()
    {
        return $this->hasMany(DisparoItem::class, ['disparo_id' => 'id']);
    }

    /**
     * Calcula o percentual de progresso da campanha.
     */
    public function getProgressoPercentual(): float
    {
        if ($this->total_itens <= 0) {
            return 100.0;
        }

        $processados = $this->itens_enviados + $this->itens_erro;
        return round(($processados / $this->total_itens) * 100, 1);
    }
}
