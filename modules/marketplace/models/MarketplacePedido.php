<?php

namespace app\modules\marketplace\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\models\Usuario;
use app\modules\vendas\models\Venda;

/**
 * Model: MarketplacePedido
 * Tabela: prest_marketplace_pedido
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $marketplace
 * @property string $marketplace_pedido_id
 * @property string $cliente_nome
 * @property string $cliente_email
 * @property string $cliente_telefone
 * @property string $cliente_documento
 * @property string $endereco_completo
 * @property string $endereco_cep
 * @property string $endereco_cidade
 * @property string $endereco_estado
 * @property float $valor_total
 * @property float $valor_frete
 * @property float $valor_desconto
 * @property float $valor_produtos
 * @property string $status
 * @property string $status_pagamento
 * @property string $status_envio
 * @property string $codigo_rastreio
 * @property string $transportadora
 * @property string $data_envio
 * @property string $data_entrega_prevista
 * @property string $venda_id
 * @property boolean $importado
 * @property string $erro_importacao
 * @property array $dados_completos
 * @property string $data_pedido
 * @property string $data_importacao
 * @property string $data_atualizacao
 *
 * @property Usuario $usuario
 * @property Venda $venda
 * @property MarketplacePedidoItem[] $itens
 */
class MarketplacePedido extends ActiveRecord
{
    public const SCENARIO_MANUAL = 'manual';

    /**
     * Flag indicando se a alteração de rastreio está sendo feita manualmente
     * @var bool
     */
    public bool $isManualTrackingUpdate = false;

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_MANUAL] = $scenarios[self::SCENARIO_DEFAULT] ?? array_keys($this->attributes);
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_marketplace_pedido';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'data_importacao',
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
            [['usuario_id', 'marketplace', 'marketplace_pedido_id', 'valor_total', 'valor_produtos', 'data_pedido'], 'required'],
            [['usuario_id', 'venda_id'], 'string'],
            [['marketplace'], 'string', 'max' => 50],
            [['marketplace_pedido_id'], 'string', 'max' => 255],
            [['cliente_nome', 'transportadora'], 'string', 'max' => 255],
            [['cliente_email'], 'email'],
            [['cliente_telefone'], 'string', 'max' => 50],
            [['cliente_documento'], 'string', 'max' => 20],
            [['endereco_completo', 'erro_importacao'], 'string'],
            [['endereco_cep'], 'string', 'max' => 10],
            [['endereco_cidade'], 'string', 'max' => 100],
            [['endereco_estado'], 'string', 'max' => 2],
            [['valor_total', 'valor_frete', 'valor_desconto', 'valor_produtos'], 'number', 'min' => 0],
            [['valor_frete', 'valor_desconto'], 'default', 'value' => 0],
            [['status', 'status_pagamento', 'status_envio'], 'string', 'max' => 50],
            [['codigo_rastreio'], 'string', 'max' => 100],
            [['codigo_rastreio'], 'validateCodigoRastreioManual'],
            [['importado'], 'boolean'],
            [['importado'], 'default', 'value' => false],
            [['data_pedido', 'data_envio', 'data_entrega_prevista'], 'safe'],
            [['dados_completos'], 'safe'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['venda_id'], 'exist', 'skipOnError' => true, 'targetClass' => Venda::class, 'targetAttribute' => ['venda_id' => 'id']],
        ];
    }

    /**
     * Valida se a inserção ou alteração manual de rastreio é permitida.
     * No caso de Magalu Entregas, a etiqueta/PLP deve ser gerada pelo marketplace, bloqueando inserção manual arbitrária.
     */
    public function validateCodigoRastreioManual($attribute, $params)
    {
        if (($this->scenario === self::SCENARIO_MANUAL || $this->isManualTrackingUpdate) && !$this->permiteRastreioManual() && !empty($this->$attribute)) {
            $this->addError($attribute, 'Pedidos com modalidade Magalu Entregas utilizam PLP oficial. O rastreio é gerado automaticamente pelo marketplace e não permite inserção manual.');
        }
    }

    /**
     * Retorna o nome formatado do marketplace
     * 
     * @return string
     */
    public function getMarketplaceNome(): string
    {
        $nomes = [
            MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE => 'Mercado Livre',
            MarketplaceConfig::MARKETPLACE_SHOPEE => 'Shopee',
            MarketplaceConfig::MARKETPLACE_MAGAZINE_LUIZA => 'Magazine Luiza',
            MarketplaceConfig::MARKETPLACE_AMAZON => 'Amazon',
            'SHEIN' => 'Shein',
            MarketplaceConfig::MARKETPLACE_TEMU => 'Temu',
            MarketplaceConfig::MARKETPLACE_IFOOD => 'iFood',
        ];

        return $nomes[$this->marketplace] ?? $this->marketplace;
    }

    /**
     * Verifica se o pedido utiliza a modalidade Magalu Entregas (PLP / Etiqueta gerada pelo marketplace)
     * 
     * @return bool
     */
    public function isMagaluEntregas(): bool
    {
        if ($this->marketplace !== MarketplaceConfig::MARKETPLACE_MAGAZINE_LUIZA) {
            return false;
        }

        if (stripos((string)$this->transportadora, 'Magalu') !== false) {
            return true;
        }

        $dados = is_array($this->dados_completos) 
            ? $this->dados_completos 
            : (json_decode((string)$this->dados_completos, true) ?: []);

        $deliveryType = strtoupper(
            $dados['delivery_type'] 
            ?? $dados['shipping_type'] 
            ?? $dados['logistic_type'] 
            ?? $dados['carrier'] 
            ?? $dados['shipping']['carrier'] 
            ?? ''
        );

        return in_array($deliveryType, ['MAGALU', 'MAGALU_ENTREGAS', 'FULFILLMENT', 'MAGALU_FULFILLMENT', 'PLP'], true);
    }

    /**
     * Verifica se o pedido permite edição ou inserção manual de rastreio/transportadora.
     * Na modalidade Magalu Entregas, o rastreio é gerado exclusivamente via PLP/etiqueta oficial.
     * 
     * @return bool
     */
    public function permiteRastreioManual(): bool
    {
        if ($this->isMagaluEntregas()) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'marketplace' => 'Marketplace',
            'marketplace_pedido_id' => 'ID do Pedido',
            'cliente_nome' => 'Cliente',
            'cliente_email' => 'E-mail',
            'cliente_telefone' => 'Telefone',
            'cliente_documento' => 'CPF/CNPJ',
            'endereco_completo' => 'Endereço',
            'endereco_cep' => 'CEP',
            'endereco_cidade' => 'Cidade',
            'endereco_estado' => 'Estado',
            'valor_total' => 'Valor Total',
            'valor_frete' => 'Frete',
            'valor_desconto' => 'Desconto',
            'valor_produtos' => 'Valor dos Produtos',
            'status' => 'Status',
            'status_pagamento' => 'Status Pagamento',
            'status_envio' => 'Status Envio',
            'codigo_rastreio' => 'Código de Rastreio',
            'transportadora' => 'Transportadora',
            'data_envio' => 'Data de Envio',
            'data_entrega_prevista' => 'Entrega Prevista',
            'venda_id' => 'Venda',
            'importado' => 'Importado',
            'erro_importacao' => 'Erro de Importação',
            'data_pedido' => 'Data do Pedido',
            'data_importacao' => 'Data de Importação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Relação com usuário
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Relação com venda
     */
    public function getVenda()
    {
        return $this->hasOne(Venda::class, ['id' => 'venda_id']);
    }

    /**
     * Relação com itens do pedido
     */
    public function getItens()
    {
        return $this->hasMany(MarketplacePedidoItem::class, ['pedido_id' => 'id']);
    }
}
