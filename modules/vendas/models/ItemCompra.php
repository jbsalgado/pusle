<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * ============================================================================================================
 * Model: ItemCompra
 * ============================================================================================================
 * Tabela: prest_itens_compra
 *
 * @property string $id
 * @property string $compra_id
 * @property string $produto_id
 * @property string|null $marca
 * @property string|null $ncm
 * @property string|null $cfop
 * @property float $quantidade
 * @property float $preco_unitario
 * @property float $valor_total_item
 * @property float $valor_desconto
 * @property float $valor_frete
 * @property float $valor_seguro
 * @property float $valor_outras_despesas
 * @property float $valor_ipi
 * @property float $valor_icms_st
 * @property float $custo_unitario_real
 * @property string $data_criacao
 *
 * @property Compra $compra
 * @property Produto $produto
 */
class ItemCompra extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_itens_compra';
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
                'updatedAtAttribute' => false, // Não tem data_atualizacao
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * Propriedades virtuais/temporárias usadas na interface e auto-cadastro
     */
    public $nome_produto_temp;
    public $categoria_id;
    public $codigo_barras;
    public $codigo_referencia_temp;
    public $preco_venda_sugerido_temp;
    public $estoque_minimo_temp;
    public $estoque_maximo_temp;
    public $ponto_corte_temp;
    public $venda_fracionada_temp;
    public $unidade_medida_temp;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['compra_id', 'produto_id', 'quantidade', 'preco_unitario'], 'required'],
            [['compra_id', 'produto_id', 'nome_produto_temp', 'categoria_id', 'codigo_barras', 'marca', 'ncm', 'cfop'], 'string'],
            [['quantidade'], 'number', 'min' => 0.001],
            [['preco_unitario', 'custo_unitario_real'], 'number', 'min' => 0],
            [[
                'valor_total_item', 'valor_desconto', 'valor_frete', 'valor_seguro',
                'valor_outras_despesas', 'valor_ipi', 'valor_icms_st'
            ], 'safe'],
            [[
                'valor_total_item', 'valor_desconto', 'valor_frete', 'valor_seguro',
                'valor_outras_despesas', 'valor_ipi', 'valor_icms_st', 'custo_unitario_real'
            ], 'default', 'value' => 0],
            [['compra_id'], 'exist', 'skipOnError' => true, 'targetClass' => Compra::class, 'targetAttribute' => ['compra_id' => 'id']],
            [['nome_produto_temp', 'categoria_id', 'codigo_barras', 'codigo_referencia_temp', 'preco_venda_sugerido_temp', 'estoque_minimo_temp', 'estoque_maximo_temp', 'ponto_corte_temp', 'venda_fracionada_temp', 'unidade_medida_temp'], 'safe'],
        ];
    }

    /**
     * Converte preco_unitario, quantidade e valores do formato BRL ou XML para float antes da validação
     */
    public function beforeValidate()
    {
        if (is_string($this->preco_unitario) && $this->preco_unitario !== '') {
            $this->preco_unitario = self::parseDecimal($this->preco_unitario);
        }
        if (is_string($this->quantidade) && $this->quantidade !== '') {
            $this->quantidade = self::parseDecimal($this->quantidade);
        }

        $camposDecimais = [
            'valor_total_item', 'valor_desconto', 'valor_frete', 'valor_seguro',
            'valor_outras_despesas', 'valor_ipi', 'valor_icms_st', 'custo_unitario_real'
        ];
        foreach ($camposDecimais as $campo) {
            if (is_string($this->$campo) && $this->$campo !== '') {
                $this->$campo = self::parseDecimal($this->$campo);
            }
        }

        return parent::beforeValidate();
    }

    /**
     * Converte strings numéricas (BR ou internacional/XML) para float com segurança
     * Suporta '1.234,56789', '12,34567', '12.34567' e '0.04561'
     */
    public static function parseDecimal($value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        $val = trim((string)$value);
        if (strpos($val, ',') !== false) {
            // Formato com vírgula: remove pontos de milhares e troca vírgula por ponto
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        }
        return (float)$val;
    }

    /**
     * Retorna o preço unitário formatado com 2 a 5 casas decimais (sem zeros inúteis após a 2ª casa)
     */
    public function getPrecoUnitarioFormatado(): string
    {
        return self::formatarPrecoUnitario($this->preco_unitario);
    }

    /**
     * Formata qualquer valor de preço unitário com 5 casas decimais (estilo calculadora)
     */
    public static function formatarPrecoUnitario($valor): string
    {
        if ($valor === null || $valor === '') {
            return '0,00000';
        }
        $val = is_numeric($valor) ? (float)$valor : self::parseDecimal($valor);
        return number_format($val, 5, ',', '.');
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'compra_id' => 'Compra',
            'produto_id' => 'Produto',
            'marca' => 'Marca',
            'ncm' => 'NCM',
            'cfop' => 'CFOP',
            'quantidade' => 'Quantidade',
            'preco_unitario' => 'Preço Unitário',
            'valor_total_item' => 'Valor Total',
            'valor_desconto' => 'Desconto',
            'valor_frete' => 'Frete',
            'valor_seguro' => 'Seguro',
            'valor_outras_despesas' => 'Outras Despesas',
            'valor_ipi' => 'IPI',
            'valor_icms_st' => 'ICMS ST',
            'custo_unitario_real' => 'Custo Real Unit.',
            'data_criacao' => 'Data de Criação',
        ];
    }

    /**
     * Relacionamento com Compra
     */
    public function getCompra()
    {
        return $this->hasOne(Compra::class, ['id' => 'compra_id']);
    }

    /**
     * Relacionamento com Produto
     */
    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    /**
     * Calcula o valor total bruto do item (arredondado para 2 casas decimais)
     */
    public function calcularValorTotal()
    {
        $this->valor_total_item = round((float)$this->quantidade * (float)$this->preco_unitario, 2);
        return $this->valor_total_item;
    }

    /**
     * Calcula o custo unitário real de aquisição do item (incluindo rateios de impostos e fretes)
     */
    public function calcularCustoUnitarioReal(): float
    {
        $qtd = (float)($this->quantidade ?: 1);
        if ($qtd <= 0) $qtd = 1;

        $subtotal = (float)$this->valor_total_item ?: ((float)$this->preco_unitario * $qtd);
        $frete = (float)($this->valor_frete ?? 0);
        $seguro = (float)($this->valor_seguro ?? 0);
        $outras = (float)($this->valor_outras_despesas ?? 0);
        $ipi = (float)($this->valor_ipi ?? 0);
        $icmsSt = (float)($this->valor_icms_st ?? 0);
        $desc = (float)($this->valor_desconto ?? 0);

        $custoTotal = $subtotal + $frete + $seguro + $outras + $ipi + $icmsSt - $desc;
        $custoRealUnit = round($custoTotal / $qtd, 5);

        $this->custo_unitario_real = $custoRealUnit > 0 ? $custoRealUnit : (float)$this->preco_unitario;
        return $this->custo_unitario_real;
    }

    /**
     * Antes de salvar, gera UUID, calcula valor total e custo real
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Gera UUID se for um novo registro
            if ($insert && empty($this->id)) {
                $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                $this->id = $uuid;
            }

            // Calcula o valor total e o custo real do item
            $this->calcularValorTotal();
            $this->calcularCustoUnitarioReal();

            return true;
        }
        return false;
    }

    /**
     * Atualiza o estoque do produto (adiciona quantidade comprada)
     * IMPORTANTE: Este método deve ser chamado apenas quando a compra for concluída
     */
    public function atualizarEstoque()
    {
        if (!$this->produto) {
            Yii::error("ItemCompra {$this->id}: Produto não encontrado", __METHOD__);
            return false;
        }

        try {
            // Adiciona a quantidade comprada ao estoque atual
            $quantidadeAnterior = $this->produto->estoque_atual;
            $this->produto->estoque_atual += $this->quantidade;

            // Atualiza o preço de custo com o custo real (se calculado) ou preço unitário
            $this->produto->preco_custo = $this->custo_unitario_real > 0 ? $this->custo_unitario_real : $this->preco_unitario;

            // Sincroniza marca e NCM se o produto ainda não tiver
            $atributosUpdate = ['estoque_atual', 'preco_custo', 'com_nota'];
            if (!empty($this->marca) && empty($this->produto->marca)) {
                $this->produto->marca = $this->marca;
                $atributosUpdate[] = 'marca';
            }
            if (!empty($this->ncm) && empty($this->produto->ncm)) {
                $this->produto->ncm = $this->ncm;
                $atributosUpdate[] = 'ncm';
            }

            // Sincroniza o status de nota fiscal da compra (NF-e)
            $this->produto->com_nota = (bool)$this->compra->com_nota;

            if (!$this->produto->save(false, $atributosUpdate)) {
                Yii::error("ItemCompra {$this->id}: Erro ao salvar produto após atualizar estoque", __METHOD__);
                return false;
            }

            Yii::info("ItemCompra {$this->id}: Estoque atualizado. Produto: {$this->produto->nome}, Estoque anterior: {$quantidadeAnterior}, Quantidade adicionada: {$this->quantidade}, Estoque novo: {$this->produto->estoque_atual}", __METHOD__);
            return true;
        } catch (\Exception $e) {
            Yii::error("ItemCompra {$this->id}: Exceção ao atualizar estoque: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Reverte o estoque do produto (remove quantidade comprada)
     * Usado quando uma compra concluída é cancelada
     */
    public function reverterEstoque()
    {
        if (!$this->produto) {
            Yii::error("ItemCompra {$this->id}: Produto não encontrado para reverter estoque", __METHOD__);
            return false;
        }

        try {
            // Remove a quantidade comprada do estoque atual
            $quantidadeAnterior = $this->produto->estoque_atual;
            $this->produto->estoque_atual -= $this->quantidade;

            // Garante que o estoque não fique negativo
            if ($this->produto->estoque_atual < 0) {
                Yii::warning("ItemCompra {$this->id}: Estoque ficaria negativo ({$this->produto->estoque_atual}), ajustando para 0", __METHOD__);
                $this->produto->estoque_atual = 0;
            }

            if (!$this->produto->save(false, ['estoque_atual'])) {
                Yii::error("ItemCompra {$this->id}: Erro ao salvar produto após reverter estoque", __METHOD__);
                return false;
            }

            Yii::info("ItemCompra {$this->id}: Estoque revertido. Produto: {$this->produto->nome}, Estoque anterior: {$quantidadeAnterior}, Quantidade removida: {$this->quantidade}, Estoque novo: {$this->produto->estoque_atual}", __METHOD__);
            return true;
        } catch (\Exception $e) {
            Yii::error("ItemCompra {$this->id}: Exceção ao reverter estoque: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
