<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Model class for table "sis_pagamentos"
 *
 * @property string $id UUID
 * @property string $assinatura_id UUID
 * @property string $usuario_id UUID
 * @property float $valor
 * @property string|null $forma_pagamento
 * @property string $status
 * @property string|null $data_pagamento
 * @property string|null $comprovante
 * @property string|null $observacoes
 * @property string|null $data_criacao
 *
 * @property SisAssinaturas $assinatura
 * @property PrestUsuarios $usuario
 */
class SisPagamentos extends ActiveRecord
{
    const STATUS_PENDENTE = 'pendente';
    const STATUS_APROVADO = 'aprovado';
    const STATUS_RECUSADO = 'recusado';
    const STATUS_ESTORNADO = 'estornado';

    const FORMA_PIX = 'pix';
    const FORMA_BOLETO = 'boleto';
    const FORMA_CARTAO_CREDITO = 'cartao_credito';
    const FORMA_CARTAO_DEBITO = 'cartao_debito';
    const FORMA_TRANSFERENCIA = 'transferencia';
    const FORMA_DINHEIRO = 'dinheiro';

    public $comprovante_file;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sis_pagamentos';
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
            [['assinatura_id', 'usuario_id', 'valor'], 'required'],
            [['assinatura_id', 'usuario_id'], 'string'],
            [['valor'], 'number', 'min' => 0],
            [['data_pagamento', 'data_criacao'], 'safe'],
            [['comprovante', 'observacoes'], 'string'],
            [['forma_pagamento'], 'string', 'max' => 50],
            [['forma_pagamento'], 'in', 'range' => [
                self::FORMA_PIX,
                self::FORMA_BOLETO,
                self::FORMA_CARTAO_CREDITO,
                self::FORMA_CARTAO_DEBITO,
                self::FORMA_TRANSFERENCIA,
                self::FORMA_DINHEIRO,
            ]],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDENTE,
                self::STATUS_APROVADO,
                self::STATUS_RECUSADO,
                self::STATUS_ESTORNADO,
            ]],
            [['status'], 'default', 'value' => self::STATUS_PENDENTE],
            [['comprovante_file'], 'file', 'extensions' => 'png, jpg, jpeg, pdf', 'maxSize' => 1024 * 1024 * 5],
            [['assinatura_id'], 'exist', 'skipOnError' => true, 'targetClass' => SisAssinaturas::class, 'targetAttribute' => ['assinatura_id' => 'id']],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => PrestUsuarios::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'assinatura_id' => 'Assinatura',
            'usuario_id' => 'Usuário',
            'valor' => 'Valor',
            'forma_pagamento' => 'Forma de Pagamento',
            'status' => 'Status',
            'data_pagamento' => 'Data do Pagamento',
            'comprovante' => 'Comprovante',
            'comprovante_file' => 'Anexar Comprovante',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
        ];
    }

    /**
     * Gets query for [[Assinatura]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAssinatura()
    {
        return $this->hasOne(SisAssinaturas::class, ['id' => 'assinatura_id']);
    }

    /**
     * Gets query for [[Usuario]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(PrestUsuarios::class, ['id' => 'usuario_id']);
    }

    /**
     * Retorna as formas de pagamento disponíveis
     * 
     * @return array
     */
    public static function getFormasPagamento()
    {
        return [
            self::FORMA_PIX => 'PIX',
            self::FORMA_BOLETO => 'Boleto',
            self::FORMA_CARTAO_CREDITO => 'Cartão de Crédito',
            self::FORMA_CARTAO_DEBITO => 'Cartão de Débito',
            self::FORMA_TRANSFERENCIA => 'Transferência Bancária',
            self::FORMA_DINHEIRO => 'Dinheiro',
        ];
    }

    /**
     * Retorna os status de pagamento disponíveis
     * 
     * @return array
     */
    public static function getStatusPagamento()
    {
        return [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_APROVADO => 'Aprovado',
            self::STATUS_RECUSADO => 'Recusado',
            self::STATUS_ESTORNADO => 'Estornado',
        ];
    }

    /**
     * Retorna o label da forma de pagamento
     * 
     * @return string
     */
    public function getFormaPagamentoLabel()
    {
        $formas = self::getFormasPagamento();
        return $formas[$this->forma_pagamento] ?? $this->forma_pagamento;
    }

    /**
     * Retorna o label do status
     * 
     * @return string
     */
    public function getStatusLabel()
    {
        $status = self::getStatusPagamento();
        return $status[$this->status] ?? $this->status;
    }

    /**
     * Retorna a classe CSS para o badge de status
     * 
     * @return string
     */
    public function getStatusBadgeClass()
    {
        $classes = [
            self::STATUS_PENDENTE => 'bg-yellow-100 text-yellow-800',
            self::STATUS_APROVADO => 'bg-green-100 text-green-800',
            self::STATUS_RECUSADO => 'bg-red-100 text-red-800',
            self::STATUS_ESTORNADO => 'bg-gray-100 text-gray-800',
        ];
        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Retorna o ícone do status
     * 
     * @return string
     */
    public function getStatusIcon()
    {
        $icons = [
            self::STATUS_PENDENTE => 'clock',
            self::STATUS_APROVADO => 'check-circle',
            self::STATUS_RECUSADO => 'x-circle',
            self::STATUS_ESTORNADO => 'arrow-left-circle',
        ];
        return $icons[$this->status] ?? 'circle';
    }

    /**
     * Aprova o pagamento
     * 
     * @param string|null $observacoes
     * @return bool
     */
    public function aprovar($observacoes = null)
    {
        $this->status = self::STATUS_APROVADO;
        $this->data_pagamento = date('Y-m-d');
        
        if ($observacoes) {
            $this->observacoes = ($this->observacoes ? $this->observacoes . "\n" : '') . 
                                 "Aprovado em " . date('d/m/Y H:i:s') . ": " . $observacoes;
        }
        
        return $this->save();
    }

    /**
     * Recusa o pagamento
     * 
     * @param string|null $motivo
     * @return bool
     */
    public function recusar($motivo = null)
    {
        $this->status = self::STATUS_RECUSADO;
        
        if ($motivo) {
            $this->observacoes = ($this->observacoes ? $this->observacoes . "\n" : '') . 
                                 "Recusado em " . date('d/m/Y H:i:s') . ": " . $motivo;
        }
        
        return $this->save();
    }

    /**
     * Estorna o pagamento
     * 
     * @param string|null $motivo
     * @return bool
     */
    public function estornar($motivo = null)
    {
        if ($this->status !== self::STATUS_APROVADO) {
            return false;
        }
        
        $this->status = self::STATUS_ESTORNADO;
        
        if ($motivo) {
            $this->observacoes = ($this->observacoes ? $this->observacoes . "\n" : '') . 
                                 "Estornado em " . date('d/m/Y H:i:s') . ": " . $motivo;
        }
        
        return $this->save();
    }

    /**
     * Verifica se o pagamento pode ser aprovado
     * 
     * @return bool
     */
    public function podeAprovar()
    {
        return $this->status === self::STATUS_PENDENTE;
    }

    /**
     * Verifica se o pagamento pode ser recusado
     * 
     * @return bool
     */
    public function podeRecusar()
    {
        return $this->status === self::STATUS_PENDENTE;
    }

    /**
     * Verifica se o pagamento pode ser estornado
     * 
     * @return bool
     */
    public function podeEstornar()
    {
        return $this->status === self::STATUS_APROVADO;
    }

    /**
     * Formata o valor para exibição
     * 
     * @return string
     */
    public function getValorFormatado()
    {
        return 'R$ ' . number_format($this->valor, 2, ',', '.');
    }

    /**
     * Retorna o nome do usuário
     * 
     * @return string
     */
    public function getNomeUsuario()
    {
        return $this->usuario ? $this->usuario->nome : '-';
    }

    /**
     * Retorna informações da assinatura
     * 
     * @return string
     */
    public function getInfoAssinatura()
    {
        if (!$this->assinatura) {
            return '-';
        }
        
        return $this->assinatura->plano->nome ?? 'Plano não identificado';
    }

    /**
     * Before save - gera UUID se necessário
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && empty($this->id)) {
                $this->id = new Expression('gen_random_uuid()');
            }
            return true;
        }
        return false;
    }
}