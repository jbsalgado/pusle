<?php
namespace app\modules\caixa\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Parcela;

/**
 * ============================================================================================================
 * Model: CaixaMovimentacao
 * ============================================================================================================
 * Tabela: prest_caixa_movimentacoes
 * 
 * @property string $id
 * @property string $caixa_id
 * @property string $tipo (ENTRADA, SAIDA)
 * @property string|null $categoria
 * @property float $valor
 * @property string $descricao
 * @property string|null $forma_pagamento_id
 * @property string|null $venda_id
 * @property string|null $parcela_id
 * @property string|null $conta_pagar_id
 * @property string $data_movimento
 * @property string|null $observacoes
 * @property string $data_criacao
 * 
 * @property Caixa $caixa
 * @property FormaPagamento|null $formaPagamento
 * @property Venda|null $venda
 * @property Parcela|null $parcela
 */
class CaixaMovimentacao extends ActiveRecord
{
    const TIPO_ENTRADA = 'ENTRADA';
    const TIPO_SAIDA = 'SAIDA';

    const CATEGORIA_VENDA = 'VENDA';
    const CATEGORIA_PAGAMENTO = 'PAGAMENTO';
    const CATEGORIA_SUPRIMENTO = 'SUPRIMENTO';
    const CATEGORIA_SANGRIA = 'SANGRIA';
    const CATEGORIA_CONTA_PAGAR = 'CONTA_PAGAR';
    const CATEGORIA_OUTRO = 'OUTRO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_caixa_movimentacoes';
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
            [['caixa_id', 'tipo', 'valor', 'descricao'], 'required'],
            [['caixa_id', 'forma_pagamento_id', 'venda_id', 'parcela_id', 'conta_pagar_id'], 'string'],
            [['tipo'], 'in', 'range' => [self::TIPO_ENTRADA, self::TIPO_SAIDA]],
            [['categoria'], 'string', 'max' => 50],
            [['valor'], 'number', 'min' => 0.01],
            [['descricao', 'observacoes'], 'string'],
            [['data_movimento'], 'safe'],
            [['data_movimento'], 'default', 'value' => new Expression('NOW()')],
            [['caixa_id'], 'exist', 'skipOnError' => true, 'targetClass' => Caixa::class, 'targetAttribute' => ['caixa_id' => 'id']],
            [['forma_pagamento_id'], 'exist', 'skipOnError' => true, 'skipOnEmpty' => true, 'targetClass' => FormaPagamento::class, 'targetAttribute' => ['forma_pagamento_id' => 'id']],
            [['venda_id'], 'exist', 'skipOnError' => true, 'skipOnEmpty' => true, 'targetClass' => Venda::class, 'targetAttribute' => ['venda_id' => 'id']],
            [['parcela_id'], 'exist', 'skipOnError' => true, 'skipOnEmpty' => true, 'targetClass' => Parcela::class, 'targetAttribute' => ['parcela_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'caixa_id' => 'Caixa',
            'tipo' => 'Tipo',
            'categoria' => 'Categoria',
            'valor' => 'Valor',
            'descricao' => 'Descrição',
            'forma_pagamento_id' => 'Forma de Pagamento',
            'venda_id' => 'Venda',
            'parcela_id' => 'Parcela',
            'conta_pagar_id' => 'Conta a Pagar',
            'data_movimento' => 'Data do Movimento',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
        ];
    }

    /**
     * Relação com Caixa
     */
    public function getCaixa()
    {
        return $this->hasOne(Caixa::class, ['id' => 'caixa_id']);
    }

    /**
     * Relação com FormaPagamento
     */
    public function getFormaPagamento()
    {
        return $this->hasOne(FormaPagamento::class, ['id' => 'forma_pagamento_id']);
    }

    /**
     * Relação com Venda
     */
    public function getVenda()
    {
        return $this->hasOne(Venda::class, ['id' => 'venda_id']);
    }

    /**
     * Relação com Parcela
     */
    public function getParcela()
    {
        return $this->hasOne(Parcela::class, ['id' => 'parcela_id']);
    }

    /**
     * Verifica se é uma entrada
     * @return bool
     */
    public function isEntrada()
    {
        return $this->tipo === self::TIPO_ENTRADA;
    }

    /**
     * Verifica se é uma saída
     * @return bool
     */
    public function isSaida()
    {
        return $this->tipo === self::TIPO_SAIDA;
    }
}

