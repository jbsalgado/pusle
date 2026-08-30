<?php

namespace app\modules\marketplace\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\models\Usuario;

/**
 * Model: MarketplaceConfig
 * Tabela: prest_marketplace_config
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $marketplace
 * @property string $seller_id_externo
 * @property string $apelido_conta
 * @property boolean $ativo
 * @property string $client_id
 * @property string $client_secret
 * @property string $access_token
 * @property string $refresh_token
 * @property string $token_expira_em
 * @property boolean $sincronizar_produtos
 * @property boolean $sincronizar_estoque
 * @property boolean $sincronizar_pedidos
 * @property integer $intervalo_sync_minutos
 * @property float $markup_percentual
 * @property float $markup_valor_fixo
 * @property boolean $arredondar_centavos_99
 * @property string $ultima_sync
 * @property array $dados_adicionais
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Usuario $usuario
 */
class MarketplaceConfig extends ActiveRecord
{
    // Constantes de marketplaces
    const MARKETPLACE_MERCADO_LIVRE = 'MERCADO_LIVRE';
    const MARKETPLACE_SHOPEE = 'SHOPEE';
    const MARKETPLACE_MAGAZINE_LUIZA = 'MAGAZINE_LUIZA';
    const MARKETPLACE_TEMU = 'TEMU';
    const MARKETPLACE_AMAZON = 'AMAZON';
    const MARKETPLACE_IFOOD = 'IFOOD';

    const ENC_PREFIX = 'enc:';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_marketplace_config';
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
            [['usuario_id', 'marketplace'], 'required'],
            [['usuario_id'], 'string'],
            [['ativo', 'sincronizar_produtos', 'sincronizar_estoque', 'sincronizar_pedidos', 'arredondar_centavos_99'], 'boolean'],
            [['ativo', 'arredondar_centavos_99'], 'default', 'value' => false],
            [['sincronizar_produtos', 'sincronizar_estoque', 'sincronizar_pedidos'], 'default', 'value' => true],
            [['intervalo_sync_minutos'], 'integer', 'min' => 1, 'max' => 1440],
            [['intervalo_sync_minutos'], 'default', 'value' => 15],
            [['markup_percentual', 'markup_valor_fixo'], 'number', 'min' => 0],
            [['markup_percentual', 'markup_valor_fixo'], 'default', 'value' => 0.00],
            [['marketplace'], 'string', 'max' => 50],
            [['marketplace'], 'in', 'range' => [
                self::MARKETPLACE_MERCADO_LIVRE,
                self::MARKETPLACE_SHOPEE,
                self::MARKETPLACE_MAGAZINE_LUIZA,
                self::MARKETPLACE_TEMU,
                self::MARKETPLACE_AMAZON,
                self::MARKETPLACE_IFOOD,
            ]],
            [['client_id', 'client_secret', 'seller_id_externo', 'apelido_conta'], 'string', 'max' => 255],
            [['access_token', 'refresh_token'], 'string'],
            [['token_expira_em', 'ultima_sync', 'dados_adicionais'], 'safe'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
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
            'marketplace' => 'Marketplace',
            'seller_id_externo' => 'ID Seller Externo',
            'apelido_conta' => 'Nome da Conta / Loja',
            'ativo' => 'Ativo',
            'client_id' => 'Client ID / App Key',
            'client_secret' => 'Client Secret',
            'access_token' => 'Access Token',
            'refresh_token' => 'Refresh Token',
            'token_expira_em' => 'Token Expira Em',
            'sincronizar_produtos' => 'Sincronizar Produtos',
            'sincronizar_estoque' => 'Sincronizar Estoque',
            'sincronizar_pedidos' => 'Sincronizar Pedidos',
            'intervalo_sync_minutos' => 'Intervalo de Sincronização (minutos)',
            'markup_percentual' => 'Margem / Markup Adicional (%)',
            'markup_valor_fixo' => 'Acréscimo Fixo por Produto (R$)',
            'arredondar_centavos_99' => 'Arredondar preço para R$ 0,99',
            'ultima_sync' => 'Última Sincronização',
            'dados_adicionais' => 'Dados Adicionais',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Criptografa dados sensíveis antes de salvar no banco
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $encryptionKey = $this->getEncryptionKey();

        if (!empty($this->client_secret) && !str_starts_with($this->client_secret, self::ENC_PREFIX)) {
            $encrypted = Yii::$app->security->encryptByKey($this->client_secret, $encryptionKey);
            $this->client_secret = self::ENC_PREFIX . base64_encode($encrypted);
        }

        if (!empty($this->refresh_token) && !str_starts_with($this->refresh_token, self::ENC_PREFIX)) {
            $encrypted = Yii::$app->security->encryptByKey($this->refresh_token, $encryptionKey);
            $this->refresh_token = self::ENC_PREFIX . base64_encode($encrypted);
        }

        return true;
    }

    /**
     * Descriptografa dados sensíveis após leitura
     */
    public function afterFind()
    {
        parent::afterFind();
        $encryptionKey = $this->getEncryptionKey();

        if (!empty($this->client_secret) && str_starts_with($this->client_secret, self::ENC_PREFIX)) {
            $raw = base64_decode(substr($this->client_secret, strlen(self::ENC_PREFIX)));
            $decrypted = Yii::$app->security->decryptByKey($raw, $encryptionKey);
            if ($decrypted !== false) {
                $this->client_secret = $decrypted;
            }
        }

        if (!empty($this->refresh_token) && str_starts_with($this->refresh_token, self::ENC_PREFIX)) {
            $raw = base64_decode(substr($this->refresh_token, strlen(self::ENC_PREFIX)));
            $decrypted = Yii::$app->security->decryptByKey($raw, $encryptionKey);
            if ($decrypted !== false) {
                $this->refresh_token = $decrypted;
            }
        }
    }

    protected function getEncryptionKey(): string
    {
        return Yii::$app->params['encryptionKey'] ?? Yii::$app->request->cookieValidationKey ?? 'pulse-marketplace-secret-key-2026';
    }

    /**
     * Calcula o preço final do anúncio aplicando a regra de markup do canal
     */
    public function calcularPrecoComMarkup(float $precoBase): float
    {
        $preco = $precoBase;
        
        if ($this->markup_percentual > 0) {
            $preco += ($precoBase * ($this->markup_percentual / 100));
        }

        if ($this->markup_valor_fixo > 0) {
            $preco += (float)$this->markup_valor_fixo;
        }

        if ($this->arredondar_centavos_99) {
            $preco = floor($preco) + 0.99;
        } else {
            $preco = round($preco, 2);
        }

        return max(0.01, $preco);
    }

    /**
     * Relação com usuário / tenant
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Retorna nome amigável do marketplace
     * @return string
     */
    public function getMarketplaceNome()
    {
        $nomes = [
            self::MARKETPLACE_MERCADO_LIVRE => 'Mercado Livre',
            self::MARKETPLACE_SHOPEE => 'Shopee',
            self::MARKETPLACE_MAGAZINE_LUIZA => 'Magazine Luiza',
            self::MARKETPLACE_TEMU => 'Temu',
            self::MARKETPLACE_AMAZON => 'Amazon',
            self::MARKETPLACE_IFOOD => 'iFood',
        ];

        return $nomes[$this->marketplace] ?? $this->marketplace;
    }

    /**
     * Verifica se o token está expirado ou próximo de expirar (5 min de margem)
     * @return bool
     */
    public function isTokenExpired()
    {
        if (empty($this->token_expira_em)) {
            return true;
        }

        $expiraEm = new \DateTime($this->token_expira_em);
        $agora = new \DateTime();

        $expiraEm->modify('-5 minutes');
        return $agora >= $expiraEm;
    }

    /**
     * Busca configuração específica por Marketplace e ID externo do seller
     * @param string $marketplace
     * @param string $sellerIdExterno
     * @return static|null
     */
    public static function findBySellerIdExterno(string $marketplace, string $sellerIdExterno): ?self
    {
        if (empty($sellerIdExterno)) {
            return null;
        }

        return self::find()
            ->where(['marketplace' => strtoupper($marketplace)])
            ->andWhere(['seller_id_externo' => (string)$sellerIdExterno])
            ->andWhere(['ativo' => true])
            ->one();
    }

    /**
     * Retorna lista de marketplaces disponíveis
     * @return array
     */
    public static function getMarketplacesDisponiveis()
    {
        return [
            self::MARKETPLACE_MERCADO_LIVRE => 'Mercado Livre',
            self::MARKETPLACE_SHOPEE => 'Shopee',
            self::MARKETPLACE_MAGAZINE_LUIZA => 'Magazine Luiza',
            self::MARKETPLACE_TEMU => 'Temu',
            self::MARKETPLACE_AMAZON => 'Amazon',
            self::MARKETPLACE_IFOOD => 'iFood',
        ];
    }
}
