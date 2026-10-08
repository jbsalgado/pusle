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
    const CATEGORIA_APORTE_CONTA = 'APORTE_CONTA';
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
            [['caixa_id', 'forma_pagamento_id', 'venda_id', 'parcela_id', 'conta_pagar_id', 'categoria'], 'filter', 'filter' => function ($value) {
                return $value === '' ? null : $value;
            }],
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

    /**
     * Verifica se é receita real de venda/recebimento de cliente (Operacional)
     * @return bool
     */
    public function isReceitaVenda()
    {
        return $this->tipo === self::TIPO_ENTRADA 
            && in_array($this->categoria, [self::CATEGORIA_VENDA, self::CATEGORIA_PAGAMENTO]) 
            && empty($this->conta_pagar_id);
    }

    /**
     * Verifica se é um aporte/cobertura contábil para pagar conta (Não Operacional / Sem impacto em faturamento)
     * @return bool
     */
    public function isAporteConta()
    {
        return $this->tipo === self::TIPO_ENTRADA 
            && ($this->categoria === self::CATEGORIA_APORTE_CONTA || !empty($this->conta_pagar_id));
    }

    /**
     * Verifica se a movimentação é operacional comercial
     * @return bool
     */
    public function isOperacional()
    {
        return !$this->isAporteConta();
    }

    /**
     * Lista de categorias com rótulos amigáveis
     * @return array
     */
    public static function getCategoriasList()
    {
        return [
            self::CATEGORIA_VENDA => 'Venda',
            self::CATEGORIA_PAGAMENTO => 'Recebimento de Parcela',
            self::CATEGORIA_SUPRIMENTO => 'Suprimento / Troco',
            self::CATEGORIA_SANGRIA => 'Sangria / Retirada',
            self::CATEGORIA_CONTA_PAGAR => 'Pagamento de Conta',
            self::CATEGORIA_APORTE_CONTA => 'Aporte p/ Conta (Não Operacional)',
            self::CATEGORIA_OUTRO => 'Outro',
        ];
    }

    /**
     * Retorna o nome amigável da categoria
     * @return string
     */
    public function getCategoriaNome()
    {
        if ($this->isAporteConta()) {
            return 'Aporte p/ Conta (Não Operacional)';
        }
        $lista = self::getCategoriasList();
        return $lista[$this->categoria] ?? ($this->categoria ?: 'Não Especificado');
    }
}

