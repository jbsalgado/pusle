<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;

/**
 * ============================================================================================================
 * Model: Compra
 * ============================================================================================================
 * Tabela: prest_compras
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $fornecedor_id
 * @property string $numero_nota_fiscal
 * @property string $serie_nota_fiscal
 * @property string $data_compra
 * @property string $data_vencimento
 * @property float $valor_total
 * @property float $valor_frete
 * @property float $valor_desconto
 * @property string $forma_pagamento
 * @property string $status_compra
 * @property string $observacoes
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Usuario $usuario
 * @property Fornecedor $fornecedor
 * @property ItemCompra[] $itens
 */
class Compra extends ActiveRecord
{
    const STATUS_PENDENTE = 'PENDENTE';
    const STATUS_CONCLUIDA = 'CONCLUIDA';
    const STATUS_CANCELADA = 'CANCELADA';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_compras';
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
            [['usuario_id', 'fornecedor_id', 'data_compra', 'valor_total'], 'required'],
            [['usuario_id', 'fornecedor_id'], 'string'],
            [['numero_nota_fiscal'], 'string', 'max' => 50],
            [['serie_nota_fiscal'], 'string', 'max' => 10],
            [['data_compra', 'data_vencimento'], 'safe'],
            [['data_compra'], 'date', 'format' => 'php:Y-m-d'],
            [['data_vencimento'], 'date', 'format' => 'php:Y-m-d'],
            [['valor_total', 'valor_frete', 'valor_desconto'], 'number', 'min' => 0],
            [['valor_total'], 'number', 'min' => 0.01],
            [['forma_pagamento'], 'string', 'max' => 50],
            [['status_compra'], 'string', 'max' => 20],
            [['status_compra'], 'default', 'value' => self::STATUS_PENDENTE],
            [['status_compra'], 'in', 'range' => [self::STATUS_PENDENTE, self::STATUS_CONCLUIDA, self::STATUS_CANCELADA]],
            [['observacoes'], 'string'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['fornecedor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Fornecedor::class, 'targetAttribute' => ['fornecedor_id' => 'id']],
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
            'numero_nota_fiscal' => 'Número da Nota Fiscal',
            'serie_nota_fiscal' => 'Série da Nota Fiscal',
            'data_compra' => 'Data da Compra',
            'data_vencimento' => 'Data de Vencimento',
            'valor_total' => 'Valor Total',
            'valor_frete' => 'Valor do Frete',
            'valor_desconto' => 'Valor do Desconto',
            'forma_pagamento' => 'Forma de Pagamento',
            'status_compra' => 'Status',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Relacionamento com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Relacionamento com Fornecedor
     */
    public function getFornecedor()
    {
        return $this->hasOne(Fornecedor::class, ['id' => 'fornecedor_id']);
    }

    /**
     * Relacionamento com Itens de Compra
     */
    public function getItens()
    {
        return $this->hasMany(ItemCompra::class, ['compra_id' => 'id']);
    }

    /**
     * Retorna o valor líquido (total - desconto + frete)
     */
    public function getValorLiquido()
    {
        return $this->valor_total - $this->valor_desconto + $this->valor_frete;
    }

    /**
     * Retorna array de status disponíveis
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_CONCLUIDA => 'Concluída',
            self::STATUS_CANCELADA => 'Cancelada',
        ];
    }

    /**
     * Retorna o label do status
     */
    public function getStatusLabel()
    {
        $statusList = self::getStatusList();
        return $statusList[$this->status_compra] ?? $this->status_compra;
    }

    /**
     * Recalcula o valor total baseado nos itens
     */
    public function recalcularValorTotal()
    {
        $total = 0;
        foreach ($this->itens as $item) {
            $total += $item->valor_total_item;
        }
        $this->valor_total = $total;
        return $this->valor_total;
    }

    /**
     * Antes de salvar, recalcula o valor total se houver itens
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Se não for inserção e houver itens, recalcula
            if (!$insert && $this->itens) {
                $this->recalcularValorTotal();
            }
            return true;
        }
        return false;
    }
}

