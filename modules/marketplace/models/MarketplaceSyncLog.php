<?php

namespace app\modules\marketplace\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\models\Usuario;

/**
 * Model: MarketplaceSyncLog
 * Tabela: prest_marketplace_sync_log
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $marketplace
 * @property string $tipo_sync
 * @property string $status
 * @property integer $itens_processados
 * @property integer $itens_sucesso
 * @property integer $itens_erro
 * @property string $mensagem
 * @property array $detalhes
 * @property integer $tempo_execucao_ms
 * @property string $data_inicio
 * @property string $data_fim
 *
 * @property Usuario $usuario
 */
class MarketplaceSyncLog extends ActiveRecord
{
    // Tipos de sincronização
    const TIPO_PRODUTOS = 'PRODUTOS';
    const TIPO_ESTOQUE = 'ESTOQUE';
    const TIPO_PEDIDOS = 'PEDIDOS';
    const TIPO_WEBHOOK = 'WEBHOOK';

    // Status
    const STATUS_SUCESSO = 'SUCESSO';
    const STATUS_ERRO = 'ERRO';
    const STATUS_PARCIAL = 'PARCIAL';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_marketplace_sync_log';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'data_inicio',
                'updatedAtAttribute' => false,
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
            [['usuario_id', 'marketplace', 'tipo_sync', 'status'], 'required'],
            [['usuario_id'], 'string'],
            [['marketplace'], 'string', 'max' => 50],
            [['tipo_sync'], 'string', 'max' => 50],
            [['tipo_sync'], 'in', 'range' => [
                self::TIPO_PRODUTOS,
                self::TIPO_ESTOQUE,
                self::TIPO_PEDIDOS,
                self::TIPO_WEBHOOK,
            ]],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [
                self::STATUS_SUCESSO,
                self::STATUS_ERRO,
                self::STATUS_PARCIAL,
            ]],
            [['itens_processados', 'itens_sucesso', 'itens_erro', 'tempo_execucao_ms'], 'integer', 'min' => 0],
            [['itens_processados', 'itens_sucesso', 'itens_erro'], 'default', 'value' => 0],
            [['mensagem'], 'string'],
            [['detalhes'], 'safe'],
            [['data_inicio', 'data_fim'], 'safe'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
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
            'marketplace' => 'Marketplace',
            'tipo_sync' => 'Tipo',
            'status' => 'Status',
            'itens_processados' => 'Itens Processados',
            'itens_sucesso' => 'Sucesso',
            'itens_erro' => 'Erros',
            'mensagem' => 'Mensagem',
            'tempo_execucao_ms' => 'Tempo (ms)',
            'data_inicio' => 'Início',
            'data_fim' => 'Fim',
        ];
    }

    /**
     * Relação com usuário
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Retorna badge HTML do status
     * @return string
     */
    public function getStatusBadge()
    {
        $badges = [
            self::STATUS_SUCESSO => '<span class="badge badge-success">Sucesso</span>',
            self::STATUS_ERRO => '<span class="badge badge-danger">Erro</span>',
            self::STATUS_PARCIAL => '<span class="badge badge-warning">Parcial</span>',
        ];

        return $badges[$this->status] ?? $this->status;
    }

    /**
     * Retorna tempo de execução formatado
     * @return string
     */
    public function getTempoFormatado()
    {
        if (empty($this->tempo_execucao_ms)) {
            return '-';
        }

        if ($this->tempo_execucao_ms < 1000) {
            return $this->tempo_execucao_ms . 'ms';
        }

        return round($this->tempo_execucao_ms / 1000, 2) . 's';
    }
}
