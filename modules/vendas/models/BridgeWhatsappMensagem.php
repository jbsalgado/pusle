<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model para prest_bridge_whatsapp_mensagens
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $direcao
 * @property string $numero_destino
 * @property string|null $numero_remetente
 * @property string $tipo
 * @property string|null $conteudo_texto
 * @property string|null $midia_url
 * @property string $status
 * @property string|null $mensagem_id_whatsapp
 * @property string|null $erro_motivo
 * @property string $created_at
 * @property string $updated_at
 */
class BridgeWhatsappMensagem extends ActiveRecord
{
    const DIRECAO_OUTBOUND = 'outbound';
    const DIRECAO_INBOUND  = 'inbound';

    const STATUS_PENDING   = 'pending';
    const STATUS_SENT      = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ      = 'read';
    const STATUS_FAILED    = 'failed';

    const TIPO_TEXT     = 'text';
    const TIPO_IMAGE    = 'image';
    const TIPO_VIDEO    = 'video';
    const TIPO_AUDIO    = 'audio';
    const TIPO_DOCUMENT = 'document';

    public static function tableName()
    {
        return 'prest_bridge_whatsapp_mensagens';
    }

    public function rules()
    {
        return [
            [['usuario_id', 'numero_destino'], 'required'],
            [['direcao'], 'in', 'range' => [self::DIRECAO_OUTBOUND, self::DIRECAO_INBOUND]],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_SENT, self::STATUS_DELIVERED, self::STATUS_READ, self::STATUS_FAILED]],
            [['tipo'], 'string', 'max' => 32],
            [['numero_destino', 'numero_remetente'], 'string', 'max' => 32],
            [['mensagem_id_whatsapp'], 'string', 'max' => 128],
            [['conteudo_texto', 'midia_url', 'erro_motivo'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }
}
