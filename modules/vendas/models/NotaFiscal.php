<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Venda;

/**
 * Model para a tabela prest_notas_fiscais
 *
 * @property string $id
 * @property string $usuario_id
 * @property string|null $venda_id
 * @property string|null $marketplace_pedido_id
 * @property string|null $marketplace
 * @property string $modelo
 * @property int $serie
 * @property int $numero
 * @property string|null $chave_acesso
 * @property string|null $protocolo_autorizacao
 * @property string $status_sefaz
 * @property string|null $cstat
 * @property string|null $xmotivo
 * @property string|null $xml_envio
 * @property string|null $xml_autorizado
 * @property string|null $pdf_danfe_path
 * @property int $ambiente
 * @property string $fonte_emissao
 * @property bool $enviada_ml
 * @property string|null $data_envio_ml
 * @property string $data_emissao
 * @property string|null $data_autorizacao
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Usuario $usuario
 * @property Venda $venda
 */
class NotaFiscal extends ActiveRecord
{
    const STATUS_PENDENTE    = 'PENDENTE';
    const STATUS_PROCESSANDO = 'PROCESSANDO';
    const STATUS_AUTORIZADA  = 'AUTORIZADA';
    const STATUS_REJEITADA   = 'REJEITADA';
    const STATUS_CANCELADA   = 'CANCELADA';

    const MODELO_NFE  = '55';
    const MODELO_NFCE = '65';

    const AMBIENTE_PRODUCAO   = 1;
    const AMBIENTE_HOMOLOGACAO = 2;

    // ---- Origem da emissão fiscal ----
    const FONTE_PULSE_ERP       = 'PULSE_ERP';       // Emitida pelo Pulse ERP via NFePHP
    const FONTE_MERCADO_LIVRE   = 'MERCADO_LIVRE';   // Emitida pelo faturador nativo do ML
    const FONTE_SISTEMA_EXTERNO = 'SISTEMA_EXTERNO'; // Emitida por ERP/contador externo via callback

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_notas_fiscais';
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
            [['usuario_id', 'numero'], 'required'],
            [['usuario_id', 'venda_id'], 'string'],
            [['serie', 'numero', 'ambiente'], 'integer'],
            [['xmotivo', 'xml_envio', 'xml_autorizado'], 'string'],
            [['data_emissao', 'data_autorizacao', 'data_envio_ml'], 'safe'],
            [['enviada_ml'], 'boolean'],
            [['modelo'], 'string', 'max' => 2],
            [['status_sefaz'], 'string', 'max' => 30],
            [['fonte_emissao'], 'string', 'max' => 30],
            [['fonte_emissao'], 'in', 'range' => [
                self::FONTE_PULSE_ERP,
                self::FONTE_MERCADO_LIVRE,
                self::FONTE_SISTEMA_EXTERNO,
            ]],
            [['marketplace'], 'string', 'max' => 50],
            [['marketplace_pedido_id', 'protocolo_autorizacao'], 'string', 'max' => 100],
            [['chave_acesso'], 'string', 'max' => 44],
            [['cstat'], 'string', 'max' => 10],
            [['pdf_danfe_path'], 'string', 'max' => 500],
            [['chave_acesso'], 'unique'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['venda_id'], 'exist', 'skipOnError' => true, 'targetClass' => Venda::class, 'targetAttribute' => ['venda_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário / Loja',
            'venda_id' => 'Venda',
            'marketplace_pedido_id' => 'ID Pedido Marketplace',
            'marketplace' => 'Marketplace',
            'modelo' => 'Modelo (55/65)',
            'serie' => 'Série',
            'numero' => 'Número',
            'chave_acesso' => 'Chave de Acesso (44 dígitos)',
            'protocolo_autorizacao' => 'Protocolo de Autorização',
            'status_sefaz' => 'Status SEFAZ',
            'cstat' => 'Código Status (cStat)',
            'xmotivo' => 'Motivo Retorno SEFAZ',
            'xml_envio' => 'XML de Envio',
            'xml_autorizado' => 'XML Autorizado',
            'pdf_danfe_path' => 'Caminho PDF DANFE',
            'ambiente'              => 'Ambiente SEFAZ',
            'fonte_emissao'         => 'Origem da Emissão Fiscal',
            'enviada_ml'            => 'Enviada ao Mercado Livre',
            'data_envio_ml'         => 'Data do Envio ao ML',
            'data_emissao'          => 'Data de Emissão',
            'data_autorizacao'      => 'Data de Autorização',
            'data_criacao'          => 'Data de Criação',
            'data_atualizacao'      => 'Data de Atualização',
        ];
    }

    /**
     * Relacionamento com Usuário / Tenant
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Relacionamento com a Venda Oficial
     */
    public function getVenda()
    {
        return $this->hasOne(Venda::class, ['id' => 'venda_id']);
    }

    /**
     * Verifica se a nota está autorizada
     */
    public function isAutorizada(): bool
    {
        return $this->status_sefaz === self::STATUS_AUTORIZADA;
    }

    /**
     * Verifica se a nota está autorizada mas ainda não foi enviada ao Mercado Livre
     */
    public function isPendenteEnvioML(): bool
    {
        return $this->status_sefaz === self::STATUS_AUTORIZADA
            && !$this->enviada_ml
            && !empty($this->chave_acesso);
    }

    /**
     * Verifica se a nota foi emitida por sistema externo
     */
    public function isSistemaExterno(): bool
    {
        return $this->fonte_emissao === self::FONTE_SISTEMA_EXTERNO;
    }
}
