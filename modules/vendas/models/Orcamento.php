<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\modules\vendas\models\Venda;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Cliente;
use app\models\Usuario;
use app\modules\vendas\models\OrcamentoItem;

/**
 * ============================================================================================================
 * Model: Orcamento
 * ============================================================================================================
 * Tabela: orcamentos
 * 
 * @property int $id
 * @property string $usuario_id
 * @property string $cliente_id
 * @property float $valor_total
 * @property float $desconto_valor
 * @property float $acrescimo_valor
 * @property string $acrescimo_tipo
 * @property string $observacao_acrescimo
 * @property string $forma_pagamento_id
 * @property string $colaborador_vendedor_id
 * @property string $status
 * @property string $data_validade
 * @property string $observacoes
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 * @property Cliente $cliente
 * @property OrcamentoItem[] $itens
 */
class Orcamento extends ActiveRecord
{
    const STATUS_PENDENTE = 'PENDENTE';
    const STATUS_APROVADO = 'APROVADO';
    const STATUS_REJEITADO = 'REJEITADO';
    const STATUS_CONVERTIDO = 'CONVERTIDO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'orcamentos';
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
                'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Se não houver data de validade, define para +7 dias por padrão
            if ($insert && empty($this->data_validade)) {
                $this->data_validade = date('Y-m-d', strtotime('+7 days'));
            }
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id', 'valor_total'], 'required'],
            [['usuario_id', 'cliente_id', 'colaborador_vendedor_id', 'forma_pagamento_id'], 'string'],
            [['valor_total', 'desconto_valor', 'acrescimo_valor'], 'number', 'min' => 0],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDENTE,
                self::STATUS_APROVADO,
                self::STATUS_REJEITADO,
                self::STATUS_CONVERTIDO
            ]],
            [['status'], 'default', 'value' => self::STATUS_PENDENTE],
            [['data_validade'], 'date', 'format' => 'php:Y-m-d'],
            [['observacoes', 'acrescimo_tipo', 'observacao_acrescimo'], 'string'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['cliente_id'], 'exist', 'skipOnError' => true, 'targetClass' => Cliente::class, 'targetAttribute' => ['cliente_id' => 'id']],
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
            'cliente_id' => 'Cliente',
            'valor_total' => 'Valor Total',
            'status' => 'Status',
            'data_validade' => 'Válido Até',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Verifica se orçamento está vencido
     */
    public function getEstaVencido()
    {
        if (!$this->data_validade) return false;
        return strtotime($this->data_validade) < time();
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getCliente()
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getItens()
    {
        return $this->hasMany(OrcamentoItem::class, ['orcamento_id' => 'id']);
    }
}
