<?php

namespace app\modules\marketplace\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\modules\vendas\models\Produto;

/**
 * Model: MarketplaceProduto
 * Tabela: prest_marketplace_produto
 *
 * @property string $id
 * @property string $produto_id
 * @property string $marketplace
 * @property string $marketplace_produto_id
 * @property string $titulo_marketplace
 * @property string $descricao_marketplace
 * @property float $preco_marketplace
 * @property integer $estoque_marketplace
 * @property string $sku_marketplace
 * @property string $url_marketplace
 * @property string $categoria_marketplace
 * @property string $status
 * @property string $ultima_sync
 * @property string $erro_sync
 * @property array $dados_completos
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Produto $produto
 */
class MarketplaceProduto extends ActiveRecord
{
    // Status
    const STATUS_ATIVO = 'ATIVO';
    const STATUS_PAUSADO = 'PAUSADO';
    const STATUS_ERRO = 'ERRO';
    const STATUS_REMOVIDO = 'REMOVIDO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_marketplace_produto';
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
            [['produto_id', 'marketplace', 'marketplace_produto_id'], 'required'],
            [['produto_id'], 'string'],
            [['marketplace'], 'string', 'max' => 50],
            [['marketplace_produto_id'], 'string', 'max' => 255],
            [['titulo_marketplace'], 'string', 'max' => 255],
            [['descricao_marketplace', 'erro_sync', 'url_marketplace'], 'string'],
            [['preco_marketplace'], 'number', 'min' => 0],
            [['estoque_marketplace'], 'integer', 'min' => 0],
            [['sku_marketplace'], 'string', 'max' => 100],
            [['categoria_marketplace'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [
                self::STATUS_ATIVO,
                self::STATUS_PAUSADO,
                self::STATUS_ERRO,
                self::STATUS_REMOVIDO,
            ]],
            [['status'], 'default', 'value' => self::STATUS_ATIVO],
            [['ultima_sync'], 'safe'],
            [['dados_completos'], 'safe'],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Produto::class, 'targetAttribute' => ['produto_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'produto_id' => 'Produto',
            'marketplace' => 'Marketplace',
            'marketplace_produto_id' => 'ID no Marketplace',
            'titulo_marketplace' => 'Título',
            'descricao_marketplace' => 'Descrição',
            'preco_marketplace' => 'Preço',
            'estoque_marketplace' => 'Estoque',
            'sku_marketplace' => 'SKU',
            'url_marketplace' => 'URL',
            'categoria_marketplace' => 'Categoria',
            'status' => 'Status',
            'ultima_sync' => 'Última Sincronização',
            'erro_sync' => 'Erro de Sincronização',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Relação com produto
     */
    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    /**
     * Retorna badge HTML do status
     * @return string
     */
    public function getStatusBadge()
    {
        $badges = [
            self::STATUS_ATIVO => '<span class="badge badge-success">Ativo</span>',
            self::STATUS_PAUSADO => '<span class="badge badge-warning">Pausado</span>',
            self::STATUS_ERRO => '<span class="badge badge-danger">Erro</span>',
            self::STATUS_REMOVIDO => '<span class="badge badge-secondary">Removido</span>',
        ];

        return $badges[$this->status] ?? $this->status;
    }
}
