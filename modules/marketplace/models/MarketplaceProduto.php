<?php

namespace app\modules\marketplace\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\models\Usuario;
use app\modules\vendas\models\Produto;

/**
 * Model: MarketplaceProduto
 * Tabela: prest_marketplace_produto
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $produto_id
 * @property string $marketplace
 * @property string $marketplace_produto_id
 * @property string $marketplace_variacao_id
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
 * @property Usuario $usuario
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
            [['usuario_id', 'produto_id'], 'string'],
            [['marketplace'], 'string', 'max' => 50],
            [['marketplace_produto_id', 'marketplace_variacao_id', 'titulo_marketplace'], 'string', 'max' => 255],
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
            [['ultima_sync', 'dados_completos'], 'safe'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
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
            'usuario_id' => 'Usuário / Tenant',
            'produto_id' => 'Produto Interno',
            'marketplace' => 'Marketplace',
            'marketplace_produto_id' => 'ID no Marketplace',
            'marketplace_variacao_id' => 'ID da Variação no Marketplace',
            'titulo_marketplace' => 'Título do Anúncio',
            'descricao_marketplace' => 'Descrição no Marketplace',
            'preco_marketplace' => 'Preço Customizado no Canal (R$)',
            'estoque_marketplace' => 'Estoque Sincronizado',
            'sku_marketplace' => 'SKU no Marketplace',
            'url_marketplace' => 'URL do Anúncio',
            'categoria_marketplace' => 'Categoria no Marketplace',
            'status' => 'Status',
            'ultima_sync' => 'Última Sincronização',
            'erro_sync' => 'Último Erro',
            'dados_completos' => 'Dados Completos (JSON)',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Relação com usuário / tenant
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Relação com produto interno do Pulse
     */
    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    /**
     * Calcula o preço final de venda para o marketplace considerando o markup configurado
     */
    public function getPrecoFinal(): float
    {
        if ($this->preco_marketplace > 0) {
            return (float) $this->preco_marketplace;
        }

        $precoBase = $this->produto ? (float)$this->produto->preco_venda : 0.0;
        
        $config = MarketplaceConfig::find()
            ->where(['marketplace' => $this->marketplace, 'usuario_id' => $this->usuario_id, 'ativo' => true])
            ->one();

        if ($config) {
            return $config->calcularPrecoComMarkup($precoBase);
        }

        return $precoBase;
    }
}
