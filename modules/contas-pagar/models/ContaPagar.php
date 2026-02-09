<?php

namespace app\modules\contas_pagar\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Fornecedor;
use app\modules\vendas\models\Compra;
use app\modules\vendas\models\FormaPagamento;

/**
 * ============================================================================================================
 * Model: ContaPagar
 * ============================================================================================================
 * Tabela: prest_contas_pagar
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string|null $fornecedor_id
 * @property string|null $compra_id
 * @property string $descricao
 * @property float $valor
 * @property string $data_vencimento
 * @property string|null $data_pagamento
 * @property string $status (PENDENTE, PAGA, VENCIDA, CANCELADA)
 * @property string|null $forma_pagamento_id
 * @property string|null $observacoes
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 * @property Fornecedor|null $fornecedor
 * @property Compra|null $compra
 * @property FormaPagamento|null $formaPagamento
 */
class ContaPagar extends ActiveRecord
{
    const STATUS_PENDENTE = 'PENDENTE';
    const STATUS_PAGA = 'PAGA';
    const STATUS_VENCIDA = 'VENCIDA';
    const STATUS_CANCELADA = 'CANCELADA';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_contas_pagar';
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
     * @var \yii\web\UploadedFile|null
     */
    public $comprovanteFile;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id', 'descricao', 'valor', 'data_vencimento'], 'required'],
            [['usuario_id', 'fornecedor_id', 'compra_id', 'status', 'forma_pagamento_id'], 'string'],
            [['valor'], 'number', 'min' => 0.01],
            [['descricao', 'arquivo_comprovante'], 'string', 'max' => 255],
            [['data_vencimento', 'data_pagamento'], 'date', 'format' => 'php:Y-m-d'],
            [['observacoes'], 'string'],
            [['status'], 'in', 'range' => [self::STATUS_PENDENTE, self::STATUS_PAGA, self::STATUS_VENCIDA, self::STATUS_CANCELADA]],
            [['status'], 'default', 'value' => self::STATUS_PENDENTE],
            [['comprovanteFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, pdf', 'maxSize' => 1024 * 1024 * 5], // 5MB
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['fornecedor_id'], 'exist', 'skipOnError' => true, 'skipOnEmpty' => true, 'targetClass' => Fornecedor::class, 'targetAttribute' => ['fornecedor_id' => 'id']],
            [['compra_id'], 'exist', 'skipOnError' => true, 'skipOnEmpty' => true, 'targetClass' => Compra::class, 'targetAttribute' => ['compra_id' => 'id']],
            [['forma_pagamento_id'], 'exist', 'skipOnError' => true, 'skipOnEmpty' => true, 'targetClass' => FormaPagamento::class, 'targetAttribute' => ['forma_pagamento_id' => 'id']],
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
            'fornecedor_id' => 'Fornecedor',
            'compra_id' => 'Compra',
            'descricao' => 'Descrição',
            'valor' => 'Valor',
            'data_vencimento' => 'Data de Vencimento',
            'data_pagamento' => 'Data de Pagamento',
            'status' => 'Status',
            'forma_pagamento_id' => 'Forma de Pagamento',
            'observacoes' => 'Observações',
            'arquivo_comprovante' => 'Comprovante/Anexo',
            'comprovanteFile' => 'Upload de Comprovante',
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
     * Relação com Fornecedor
     */
    public function getFornecedor()
    {
        return $this->hasOne(Fornecedor::class, ['id' => 'fornecedor_id']);
    }

    /**
     * Relação com Compra
     */
    public function getCompra()
    {
        return $this->hasOne(Compra::class, ['id' => 'compra_id']);
    }

    /**
     * Relação com FormaPagamento
     */
    public function getFormaPagamento()
    {
        return $this->hasOne(FormaPagamento::class, ['id' => 'forma_pagamento_id']);
    }

    /**
     * Verifica se a conta está pendente
     * @return bool
     */
    public function isPendente()
    {
        return $this->status === self::STATUS_PENDENTE;
    }

    /**
     * Verifica se a conta está paga
     * @return bool
     */
    public function isPaga()
    {
        return $this->status === self::STATUS_PAGA;
    }

    /**
     * Verifica se a conta está vencida
     * @return bool
     */
    public function isVencida()
    {
        if ($this->status === self::STATUS_PAGA || $this->status === self::STATUS_CANCELADA) {
            return false;
        }

        return strtotime($this->data_vencimento) < strtotime(date('Y-m-d'));
    }

    /**
     * Calcula dias de atraso
     * @return int|null
     */
    public function getDiasAtraso()
    {
        if ($this->isPaga() || $this->isVencida() === false) {
            return null;
        }

        $hoje = new \DateTime();
        $vencimento = new \DateTime($this->data_vencimento);
        $diferenca = $hoje->diff($vencimento);

        return $diferenca->days;
    }

    /**
     * Marca a conta como paga
     * @param string|null $dataPagamento
     * @return bool
     */
    public function marcarComoPaga($dataPagamento = null)
    {
        $this->status = self::STATUS_PAGA;
        $this->data_pagamento = $dataPagamento ?: date('Y-m-d');
        return $this->save(false);
    }
}
