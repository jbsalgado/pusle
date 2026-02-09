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
 * @property float $quantidade
 * @property float $preco_unitario
 * @property float $valor_total_item
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
     * {@inheritdoc}
     */
    public $nome_produto_temp;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['compra_id', 'produto_id', 'quantidade', 'preco_unitario'], 'required'],
            [['compra_id', 'produto_id', 'nome_produto_temp'], 'string'],
            [['quantidade'], 'number', 'min' => 0.001],
            [['preco_unitario'], 'number', 'min' => 0],
            [['valor_total_item'], 'number', 'min' => 0],
            [['compra_id'], 'exist', 'skipOnError' => true, 'targetClass' => Compra::class, 'targetAttribute' => ['compra_id' => 'id']],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Produto::class, 'targetAttribute' => ['produto_id' => 'id']],
            [['nome_produto_temp'], 'safe'],
        ];
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
            'quantidade' => 'Quantidade',
            'preco_unitario' => 'Preço Unitário',
            'valor_total_item' => 'Valor Total',
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
     * Calcula o valor total do item
     */
    public function calcularValorTotal()
    {
        $this->valor_total_item = $this->quantidade * $this->preco_unitario;
        return $this->valor_total_item;
    }

    /**
     * Antes de salvar, gera UUID e calcula o valor total
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Gera UUID se for um novo registro
            if ($insert && empty($this->id)) {
                $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                $this->id = $uuid;
            }

            // Calcula o valor total do item
            $this->calcularValorTotal();

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
            $this->produto->estoque_atual += (int)$this->quantidade;

            // Atualiza o preço de custo com o preço unitário da compra
            $this->produto->preco_custo = $this->preco_unitario;

            if (!$this->produto->save(false, ['estoque_atual', 'preco_custo'])) {
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
            $this->produto->estoque_atual -= (int)$this->quantidade;

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
