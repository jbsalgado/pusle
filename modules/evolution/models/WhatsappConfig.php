<?php

namespace app\modules\evolution\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Modelo ActiveRecord para a tabela pulse_whatsapp_config.
 *
 * @property int         $id
 * @property string      $empresa_id               UUID do tenant em prest_usuarios
 * @property string      $instance_name            Ex: pulse_empresa_id_{uuid_curto}
 * @property string      $token                    Token gerado pela Evolution API Go
 * @property string      $status                   'CONNECTED' | 'DISCONNECTED'
 * @property string      $created_at
 * @property string      $updated_at
 * @property int         $delay_min                Delay mínimo entre envios (ms) - Recomendado: 15000 (15s)
 * @property int         $delay_max                Delay máximo entre envios (ms) - Recomendado: 45000 (45s)
 * @property int         $simular_digitacao        1 = Sim, 0 = Não
 * @property string|null $proxy_host               Ex: http://proxy.provedor.com:8080 ou 177.54.12.8:3128
 * @property string|null $proxy_user               Usuário do proxy
 * @property string|null $proxy_pass               Senha do proxy
 * @property int         $lote_tamanho             Quantidade de mensagens por lote antes da pausa longa
 * @property int         $lote_pausa_segundos      Tempo de pausa entre lotes (segundos)
 * @property int         $limite_diario_mensagens  Limite máximo diário de mensagens por instância
 * @property int         $mensagens_enviadas_hoje  Contador de mensagens enviadas na data atual
 * @property string|null $data_contador_diario     Data de referência do contador diário (Y-m-d)
 * @property string      $provider                 'evolution' | 'meta_cloud'
 * @property string|null $meta_waba_id             WhatsApp Business Account ID
 * @property string|null $meta_phone_number_id     ID do Número de Telefone na Meta
 * @property string|null $meta_access_token         Token de Acesso de Sistema da Meta
 * @property string|null $meta_webhook_verify_token Token de Verificação do Webhook Meta
 */
class WhatsappConfig extends ActiveRecord
{
    public const PROVIDER_EVOLUTION  = 'evolution';
    public const PROVIDER_META_CLOUD = 'meta_cloud';

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
            [['instance_name', 'token', 'proxy_host', 'proxy_user', 'proxy_pass'], 'string', 'max' => 255],
            [['meta_waba_id', 'meta_phone_number_id', 'meta_webhook_verify_token'], 'string', 'max' => 100],
            [['meta_access_token'], 'string'],
            [['provider'], 'string', 'max' => 30],
            [['provider'], 'in', 'range' => [self::PROVIDER_EVOLUTION, self::PROVIDER_META_CLOUD]],
            [['provider'], 'default', 'value' => self::PROVIDER_EVOLUTION],
            [['status'], 'string', 'max' => 50],
            [['status'], 'in', 'range' => ['CONNECTED', 'DISCONNECTED']],
            [['status'], 'default', 'value' => 'DISCONNECTED'],
            [['token'], 'default', 'value' => ''],
            [[
                'delay_min',
                'delay_max',
                'simular_digitacao',
                'lote_tamanho',
                'lote_pausa_segundos',
                'limite_diario_mensagens',
                'mensagens_enviadas_hoje'
            ], 'integer'],
            [['delay_min'], 'default', 'value' => 15000],
            [['delay_max'], 'default', 'value' => 45000],
            [['simular_digitacao'], 'default', 'value' => 1],
            [['lote_tamanho'], 'default', 'value' => 15],
            [['lote_pausa_segundos'], 'default', 'value' => 120],
            [['limite_diario_mensagens'], 'default', 'value' => 150],
            [['mensagens_enviadas_hoje'], 'default', 'value' => 0],
            [['data_contador_diario'], 'safe'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id'                        => 'ID',
            'empresa_id'                => 'Empresa',
            'instance_name'             => 'Nome da Instância',
            'token'                     => 'Token',
            'provider'                  => 'Provedor de WhatsApp',
            'meta_waba_id'              => 'WABA ID (Conta WhatsApp)',
            'meta_phone_number_id'      => 'Phone Number ID',
            'meta_access_token'         => 'Access Token Permanente',
            'meta_webhook_verify_token' => 'Verify Token Webhook',
            'status'                    => 'Status',
            'delay_min'                 => 'Delay Mínimo (ms)',
            'delay_max'                 => 'Delay Máximo (ms)',
            'simular_digitacao'         => 'Simular Digitação',
            'proxy_host'                => 'Host do Proxy (IP:Porta)',
            'proxy_user'                => 'Usuário do Proxy',
            'proxy_pass'                => 'Senha do Proxy',
            'lote_tamanho'              => 'Tamanho do Lote (mensagens)',
            'lote_pausa_segundos'       => 'Pausa entre Lotes (segundos)',
            'limite_diario_mensagens'   => 'Limite Diário de Mensagens',
            'mensagens_enviadas_hoje'   => 'Mensagens Enviadas Hoje',
            'data_contador_diario'      => 'Data do Contador',
            'created_at'                => 'Criado em',
            'updated_at'                => 'Atualizado em',
        ];
    }

    /**
     * Verifica se o tenant está utilizando a API Oficial da Meta
     */
    public function isMetaOficial(): bool
    {
        return $this->provider === self::PROVIDER_META_CLOUD 
            && !empty($this->meta_phone_number_id) 
            && !empty($this->meta_access_token);
    }

    /**
     * Verifica se o tenant está utilizando o motor Evolution (não oficial)
     */
    public function isEvolution(): bool
    {
        return $this->provider === self::PROVIDER_EVOLUTION || empty($this->provider);
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

    /**
     * Verifica se a instância ainda possui cota para enviar mensagens na data de hoje.
     * Reseta automaticamente o contador caso o dia tenha mudado.
     *
     * @return bool true se pode enviar, false se atingiu o teto diário
     */
    public function podeEnviarHoje(): bool
    {
        $hoje = date('Y-m-d');

        // Se a data gravada for diferente de hoje, reseta o contador
        if ($this->data_contador_diario !== $hoje) {
            $this->data_contador_diario = $hoje;
            $this->mensagens_enviadas_hoje = 0;
            $this->save(false);
        }

        $limite = $this->limite_diario_mensagens > 0 ? (int)$this->limite_diario_mensagens : 150;
        return (int)$this->mensagens_enviadas_hoje < $limite;
    }

    /**
     * Incrementa o contador de mensagens enviadas na data de hoje de forma atômica.
     */
    public function incrementarEnvioHoje(): void
    {
        $hoje = date('Y-m-d');
        if ($this->data_contador_diario !== $hoje) {
            $this->data_contador_diario = $hoje;
            $this->mensagens_enviadas_hoje = 1;
        } else {
            $this->mensagens_enviadas_hoje = (int)$this->mensagens_enviadas_hoje + 1;
        }
        $this->save(false);
    }

    /**
     * Retorna a quantidade de envios restantes permitidos para o dia de hoje.
     *
     * @return int
     */
    public function getMensagensRestantesHoje(): int
    {
        $hoje = date('Y-m-d');
        if ($this->data_contador_diario !== $hoje) {
            return $this->limite_diario_mensagens > 0 ? (int)$this->limite_diario_mensagens : 150;
        }
        $limite = $this->limite_diario_mensagens > 0 ? (int)$this->limite_diario_mensagens : 150;
        return max(0, $limite - (int)$this->mensagens_enviadas_hoje);
    }
}
