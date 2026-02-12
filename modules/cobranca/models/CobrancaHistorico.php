<?php

namespace app\modules\cobranca\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\models\Usuario;
use app\modules\vendas\models\Parcela;

/**
 * Model: CobrancaHistorico
 * 
 * Histórico de envios de cobranças via WhatsApp
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $parcela_id
 * @property string $tipo
 * @property string $telefone
 * @property string $mensagem
 * @property string $status
 * @property string $resposta_api
 * @property integer $tentativas
 * @property string $data_envio
 * @property string $data_criacao
 * 
 * @property Usuario $usuario
 * @property Parcela $parcela
 */
class CobrancaHistorico extends ActiveRecord
{
    const STATUS_ENVIADO = 'ENVIADO';
    const STATUS_FALHA = 'FALHA';
    const STATUS_PENDENTE = 'PENDENTE';

    const TIPO_ANTES = 'ANTES';
    const TIPO_DIA = 'DIA';
    const TIPO_APOS = 'APOS';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_cobranca_historico';
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
            [['usuario_id', 'parcela_id', 'tipo', 'telefone', 'mensagem'], 'required'],
            [['usuario_id', 'parcela_id', 'tipo', 'status'], 'string'],
            [['mensagem', 'resposta_api'], 'string'],
            [['telefone'], 'string', 'max' => 20],
            [['tentativas'], 'integer', 'min' => 0],
            [['data_envio'], 'safe'],
            [['tipo'], 'in', 'range' => [self::TIPO_ANTES, self::TIPO_DIA, self::TIPO_APOS]],
            [['status'], 'in', 'range' => [self::STATUS_ENVIADO, self::STATUS_FALHA, self::STATUS_PENDENTE]],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['parcela_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parcela::class, 'targetAttribute' => ['parcela_id' => 'id']],

            // Defaults
            [['status'], 'default', 'value' => self::STATUS_PENDENTE],
            [['tentativas'], 'default', 'value' => 0],
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
            'parcela_id' => 'Parcela',
            'tipo' => 'Tipo',
            'telefone' => 'Telefone',
            'mensagem' => 'Mensagem',
            'status' => 'Status',
            'resposta_api' => 'Resposta API',
            'tentativas' => 'Tentativas',
            'data_envio' => 'Data de Envio',
            'data_criacao' => 'Data de Criação',
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
     * Relação com Parcela
     */
    public function getParcela()
    {
        return $this->hasOne(Parcela::class, ['id' => 'parcela_id']);
    }

    /**
     * Lista de status disponíveis
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_ENVIADO => 'Enviado',
            self::STATUS_FALHA => 'Falha',
            self::STATUS_PENDENTE => 'Pendente',
        ];
    }

    /**
     * Retorna o nome do status
     */
    public function getStatusNome()
    {
        $status = self::getStatusList();
        return $status[$this->status] ?? $this->status;
    }

    /**
     * Retorna cor do status para UI
     */
    public function getStatusCor()
    {
        switch ($this->status) {
            case self::STATUS_ENVIADO:
                return 'green';
            case self::STATUS_FALHA:
                return 'red';
            case self::STATUS_PENDENTE:
                return 'yellow';
            default:
                return 'gray';
        }
    }

    /**
     * Verifica se já foi enviada cobrança para esta parcela neste tipo
     */
    public static function jaEnviado($parcelaId, $tipo)
    {
        return static::find()
            ->where(['parcela_id' => $parcelaId, 'tipo' => $tipo])
            ->andWhere(['status' => self::STATUS_ENVIADO])
            ->exists();
    }

    /**
     * Registra tentativa de envio
     */
    public function registrarTentativa($sucesso, $resposta = null)
    {
        $this->tentativas++;
        $this->status = $sucesso ? self::STATUS_ENVIADO : self::STATUS_FALHA;
        $this->data_envio = date('Y-m-d H:i:s');

        if ($resposta) {
            $this->resposta_api = is_array($resposta) ? json_encode($resposta) : $resposta;
        }

        return $this->save(false);
    }
}
