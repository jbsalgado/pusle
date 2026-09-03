<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

/**
 * Model: ProdutoVariante
 * Tabela: prest_produto_variantes
 *
 * @property string $id
 * @property string $produto_id
 * @property string $cor
 * @property string $tamanho
 * @property float $estoque_atual
 * @property float|null $preco_venda_sugerido
 * @property float|null $preco_custo
 * @property string|null $codigo_barras
 * @property string|null $codigo_referencia
 * @property boolean $ativo
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Produto $produto
 * @property ProdutoFoto[] $fotos
 */
class ProdutoVariante extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_produto_variantes';
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
            [['produto_id', 'cor', 'tamanho'], 'required'],
            [['produto_id'], 'string'],
            [['cor'], 'string', 'max' => 50],
            [['tamanho'], 'string', 'max' => 20],
            [['codigo_barras', 'codigo_referencia'], 'string', 'max' => 100],
            [['codigo_barras', 'codigo_referencia'], 'trim'],
            [['estoque_atual', 'preco_venda_sugerido', 'preco_custo'], 'number', 'min' => 0],
            [['estoque_atual'], 'default', 'value' => 0],
            [['ativo'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Produto::class, 'targetAttribute' => ['produto_id' => 'id']],
            [['cor', 'tamanho'], 'filter', 'filter' => function ($value) {
                return mb_strtoupper(trim($value), 'UTF-8');
            }],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'produto_id' => 'Produto Mestre',
            'cor' => 'Modelo / Cor',
            'tamanho' => 'Tamanho',
            'estoque_atual' => 'Estoque Atual',
            'preco_venda_sugerido' => 'Preço de Venda',
            'preco_custo' => 'Preço de Custo',
            'codigo_barras' => 'Código de Barras (EAN)',
            'codigo_referencia' => 'Código de Referência',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Relacionamento com o Produto Mestre
     * @return \yii\db\ActiveQuery
     */
    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    /**
     * Relacionamento com as fotos vinculadas diretamente a esta variante
     * @return \yii\db\ActiveQuery
     */
    public function getFotos()
    {
        return $this->hasMany(ProdutoFoto::class, ['variante_id' => 'id'])->orderBy(['ordem' => SORT_ASC]);
    }

    /**
     * Retorna o preço efetivo de venda (herda do pai se não houver preço específico na variante)
     * @return float
     */
    public function getPrecoVendaEfetivo()
    {
        if ($this->preco_venda_sugerido !== null && (float)$this->preco_venda_sugerido > 0) {
            return (float)$this->preco_venda_sugerido;
        }
        return $this->produto ? (float)$this->produto->preco_venda_sugerido : 0.0;
    }

    /**
     * Retorna o nome formatado para vitrine e etiquetas: Ex: Tênis Runner (PRETO 38)
     * @return string
     */
    public function getNomeFormatado()
    {
        $nomeBase = $this->produto ? $this->produto->nome : 'Produto';
        return "{$nomeBase} ({$this->cor} {$this->tamanho})";
    }

    /**
     * Baixa de estoque da variante com sincronização atômica do produto pai
     * @param float $quantidade Quantidade a baixar
     * @return bool Sucesso na operação
     */
    public function baixarEstoque($quantidade)
    {
        $this->refresh();
        $this->estoque_atual -= (float)$quantidade;
        $salvou = $this->save(false, ['estoque_atual', 'data_atualizacao']);

        if ($salvou && $this->produto) {
            $this->produto->recalculateStockSum();
        }

        return $salvou;
    }

    /**
     * Hook executado após salvar: recalcula estoque consolidado do produto mestre
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert || isset($changedAttributes['estoque_atual']) || isset($changedAttributes['ativo'])) {
            if ($this->produto) {
                $this->produto->recalculateStockSum();
            }
        }
    }

    /**
     * Hook executado após exclusão: recalcula estoque consolidado do produto mestre
     */
    public function afterDelete()
    {
        parent::afterDelete();

        if ($this->produto) {
            $this->produto->recalculateStockSum();
        }
    }
}
