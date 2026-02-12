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
 * @property string $ultima_sync
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
    const MARKETPLACE_AMAZON = 'AMAZON';

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
            [['ativo', 'sincronizar_produtos', 'sincronizar_estoque', 'sincronizar_pedidos'], 'boolean'],
            [['ativo'], 'default', 'value' => false],
            [['sincronizar_produtos', 'sincronizar_estoque', 'sincronizar_pedidos'], 'default', 'value' => true],
            [['intervalo_sync_minutos'], 'integer', 'min' => 5, 'max' => 1440],
            [['intervalo_sync_minutos'], 'default', 'value' => 15],
            [['marketplace'], 'string', 'max' => 50],
            [['marketplace'], 'in', 'range' => [
                self::MARKETPLACE_MERCADO_LIVRE,
                self::MARKETPLACE_SHOPEE,
                self::MARKETPLACE_MAGAZINE_LUIZA,
                self::MARKETPLACE_AMAZON,
            ]],
            [['client_id', 'client_secret'], 'string', 'max' => 255],
            [['access_token', 'refresh_token'], 'string'],
            [['token_expira_em', 'ultima_sync'], 'safe'],
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
            'ativo' => 'Ativo',
            'client_id' => 'Client ID',
            'client_secret' => 'Client Secret',
            'access_token' => 'Access Token',
            'refresh_token' => 'Refresh Token',
            'token_expira_em' => 'Token Expira Em',
            'sincronizar_produtos' => 'Sincronizar Produtos',
            'sincronizar_estoque' => 'Sincronizar Estoque',
            'sincronizar_pedidos' => 'Sincronizar Pedidos',
            'intervalo_sync_minutos' => 'Intervalo de Sincronização (minutos)',
            'ultima_sync' => 'Última Sincronização',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
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
     * Retorna nome amigável do marketplace
     * @return string
     */
    public function getMarketplaceNome()
    {
        $nomes = [
            self::MARKETPLACE_MERCADO_LIVRE => 'Mercado Livre',
            self::MARKETPLACE_SHOPEE => 'Shopee',
            self::MARKETPLACE_MAGAZINE_LUIZA => 'Magazine Luiza',
            self::MARKETPLACE_AMAZON => 'Amazon',
        ];

        return $nomes[$this->marketplace] ?? $this->marketplace;
    }

    /**
     * Verifica se o token está expirado
     * @return bool
     */
    public function isTokenExpired()
    {
        if (empty($this->token_expira_em)) {
            return true;
        }

        $expiraEm = new \DateTime($this->token_expira_em);
        $agora = new \DateTime();

        // Considera expirado se faltar menos de 5 minutos
        $expiraEm->modify('-5 minutes');

        return $agora >= $expiraEm;
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
            self::MARKETPLACE_AMAZON => 'Amazon',
        ];
    }
}
