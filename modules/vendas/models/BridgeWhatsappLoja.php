<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\models\Usuario;

/**
 * Model para prest_bridge_whatsapp_lojas
 * Gerencia o estado e credenciais do Agente Local Go Whatsmeow de cada loja.
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $token_agente
 * @property string $status_conexao
 * @property string|null $qr_code_base64
 * @property string|null $telefone_conectado
 * @property string|null $push_name
 * @property string|null $ip_origem_agente
 * @property string|null $ultimo_heartbeat
 * @property string $created_at
 * @property string $updated_at
 */
class BridgeWhatsappLoja extends ActiveRecord
{
    const STATUS_DISCONNECTED = 'disconnected';
    const STATUS_QR_READY     = 'qr_ready';
    const STATUS_CONNECTING   = 'connecting';
    const STATUS_CONNECTED    = 'connected';

    public static function tableName()
    {
        return 'prest_bridge_whatsapp_lojas';
    }

    public function rules()
    {
        return [
            [['usuario_id', 'token_agente'], 'required'],
            [['status_conexao'], 'string', 'max' => 32],
            [['qr_code_base64'], 'string'],
            [['telefone_conectado'], 'string', 'max' => 32],
            [['push_name'], 'string', 'max' => 255],
            [['ip_origem_agente'], 'string', 'max' => 64],
            [['ultimo_heartbeat', 'created_at', 'updated_at'], 'safe'],
            [['usuario_id'], 'unique'],
            [['token_agente'], 'unique'],
        ];
    }

    /**
     * Retorna a configuração da loja pelo usuario_id, criando uma caso não exista
     */
    public static function obterOuCriarParaUsuario($usuarioId)
    {
        $model = self::findOne(['usuario_id' => $usuarioId]);
        if (!$model) {
            $model = new self();
            $model->usuario_id = $usuarioId;
            $model->token_agente = 'pba_' . bin2hex(random_bytes(24));
            $model->status_conexao = self::STATUS_DISCONNECTED;
            $model->save(false);
        }
        return $model;
    }

    /**
     * Retorna se o agente local está ativo nos últimos 45 segundos
     */
    public function isAgenteOnline()
    {
        if (empty($this->ultimo_heartbeat)) {
            return false;
        }
        $timestamp = strtotime($this->ultimo_heartbeat);
        return (time() - $timestamp) <= 45;
    }

    /**
     * Retorna se a sessão do WhatsApp está conectada
     */
    public function isWhatsappConectado()
    {
        return $this->isAgenteOnline() && $this->status_conexao === self::STATUS_CONNECTED;
    }
}
