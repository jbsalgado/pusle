<?php

namespace app\modules\evolution\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Modelo ActiveRecord para a tabela pulse_whatsapp_config.
 *
 * @property int    $id
 * @property string $empresa_id    UUID do tenant em prest_usuarios
 * @property string $instance_name Ex: pulse_empresa_id_{uuid_curto}
 * @property string $token         Token gerado pela Evolution API Go
 * @property string $status        'CONNECTED' | 'DISCONNECTED'
 * @property string $created_at
 * @property string $updated_at
 * @property int    $delay_min
 * @property int    $delay_max
 * @property int    $simular_digitacao
 */
class WhatsappConfig extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return 'pulse_whatsapp_config';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['empresa_id', 'instance_name'], 'required'],
            [['empresa_id'], 'string', 'max' => 36],
            [['instance_name', 'token'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 50],
            [['status'], 'in', 'range' => ['CONNECTED', 'DISCONNECTED']],
            [['status'], 'default', 'value' => 'DISCONNECTED'],
            [['token'], 'default', 'value' => ''],
            [['delay_min', 'delay_max', 'simular_digitacao'], 'integer'],
            [['delay_min'], 'default', 'value' => 1500],
            [['delay_max'], 'default', 'value' => 2500],
            [['simular_digitacao'], 'default', 'value' => 1],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id'                => 'ID',
            'empresa_id'        => 'Empresa',
            'instance_name'     => 'Nome da Instância',
            'token'             => 'Token',
            'status'            => 'Status',
            'delay_min'         => 'Delay Mínimo (ms)',
            'delay_max'         => 'Delay Máximo (ms)',
            'simular_digitacao' => 'Simular Digitação',
            'created_at'        => 'Criado em',
            'updated_at'        => 'Atualizado em',
        ];
    }

    /**
     * Encontra o registro de configuração de uma empresa.
     *
     * @param string $empresaId UUID do tenant
     * @return static|null
     */
    public static function findByEmpresa(string $empresaId): ?self
    {
        return static::findOne(['empresa_id' => $empresaId]);
    }
}
