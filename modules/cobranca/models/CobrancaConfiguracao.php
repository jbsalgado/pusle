<?php

namespace app\modules\cobranca\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\models\Usuario;

/**
 * Model: CobrancaConfiguracao
 * 
 * Configurações de automação de cobranças por usuário
 * 
 * @property string $id
 * @property string $usuario_id
 * @property boolean $ativo
 * @property string $whatsapp_provider
 * @property string $zapi_instance_id
 * @property string $zapi_token
 * @property integer $dias_antes_vencimento
 * @property boolean $enviar_dia_vencimento
 * @property integer $dias_apos_vencimento
 * @property string $horario_envio
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 */
class CobrancaConfiguracao extends ActiveRecord
{
    const PROVIDER_ZAPI = 'zapi';
    const PROVIDER_TWILIO = 'twilio';
    const PROVIDER_EVOLUTION = 'evolution';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_cobranca_configuracao';
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
            [['usuario_id'], 'string'],
            [['ativo', 'enviar_dia_vencimento'], 'boolean'],
            [['dias_antes_vencimento', 'dias_apos_vencimento'], 'integer', 'min' => 0, 'max' => 30],
            [['whatsapp_provider'], 'string', 'max' => 20],
            [['whatsapp_provider'], 'in', 'range' => [self::PROVIDER_ZAPI, self::PROVIDER_TWILIO, self::PROVIDER_EVOLUTION]],
            [['zapi_instance_id'], 'string', 'max' => 100],
            [['zapi_token'], 'string', 'max' => 255],
            [['horario_envio'], 'safe'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],

            // Validações condicionais
            [['zapi_instance_id', 'zapi_token'], 'required', 'when' => function ($model) {
                return $model->whatsapp_provider === self::PROVIDER_ZAPI && $model->ativo;
            }, 'message' => 'Credenciais Z-API são obrigatórias quando a automação está ativa.'],

            // Defaults
            [['ativo'], 'default', 'value' => true],
            [['whatsapp_provider'], 'default', 'value' => self::PROVIDER_ZAPI],
            [['dias_antes_vencimento'], 'default', 'value' => 3],
            [['dias_apos_vencimento'], 'default', 'value' => 1],
            [['enviar_dia_vencimento'], 'default', 'value' => true],
            [['horario_envio'], 'default', 'value' => '09:00:00'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'ativo' => 'Automação Ativa',
            'whatsapp_provider' => 'Provedor WhatsApp',
            'zapi_instance_id' => 'Z-API Instance ID',
            'zapi_token' => 'Z-API Token',
            'dias_antes_vencimento' => 'Dias Antes do Vencimento',
            'enviar_dia_vencimento' => 'Enviar no Dia do Vencimento',
            'dias_apos_vencimento' => 'Dias Após Vencimento',
            'horario_envio' => 'Horário de Envio',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Relação com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Busca ou cria configuração para o usuário
     */
    public static function getOrCreateForUser($usuarioId)
    {
        $config = static::findOne(['usuario_id' => $usuarioId]);

        if (!$config) {
            $config = new static();
            $config->usuario_id = $usuarioId;
            $config->save();
        }

        return $config;
    }

    /**
     * Verifica se as credenciais estão configuradas
     */
    public function hasCredentials()
    {
        if ($this->whatsapp_provider === self::PROVIDER_ZAPI) {
            return !empty($this->zapi_instance_id) && !empty($this->zapi_token);
        }

        return false;
    }

    /**
     * Verifica se a automação está pronta para uso
     */
    public function isReady()
    {
        return $this->ativo && $this->hasCredentials();
    }
}
