<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use app\models\Usuario;
use app\modules\vendas\models\Venda;

/**
 * Model: SaasFinancialLog
 * Tabela: saas_financial_logs
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $order_id
 * @property string|null $mp_payment_id
 * @property float $total_amount
 * @property float $platform_fee
 * @property string $status
 * @property string $created_at
 *
 * @property Usuario $tenant
 * @property Venda $venda
 */
class SaasFinancialLog extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_CHARGED_BACK = 'charged_back';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%saas_financial_logs}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tenant_id', 'order_id', 'total_amount', 'platform_fee'], 'required'],
            [['total_amount', 'platform_fee'], 'number'],
            [['created_at'], 'safe'],
            [['mp_payment_id'], 'string', 'max' => 100],
            [['status'], 'string', 'max' => 20],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['tenant_id', 'order_id'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tenant_id' => 'Lojista / Tenant',
            'order_id' => 'Venda / Pedido',
            'mp_payment_id' => 'ID Pagamento MP',
            'total_amount' => 'Valor Total',
            'platform_fee' => 'Taxa da Plataforma (SaaS)',
            'status' => 'Status',
            'created_at' => 'Data Criação',
        ];
    }

    /**
     * Retorna o valor líquido creditado ao lojista (Bruto - Taxa SaaS)
     * @return float
     */
    public function getLiquidoLojista(): float
    {
        return (float)max(0, $this->total_amount - $this->platform_fee);
    }

    /**
     * Retorna a porcentagem efetiva retida pela plataforma
     * @return float
     */
    public function getPercentualTaxa(): float
    {
        if ($this->total_amount <= 0) {
            return 0.0;
        }
        return (float)(($this->platform_fee / $this->total_amount) * 100);
    }

    /**
     * Verifica se a transação está aprovada
     * @return bool
     */
    public function isAprovado(): bool
    {
        return in_array(strtolower($this->status), ['approved', 'confirmed', 'received']);
    }

    /**
     * Verifica se a transação foi estornada
     * @return bool
     */
    public function isEstornado(): bool
    {
        return in_array(strtolower($this->status), ['refunded', 'charged_back']);
    }

    /**
     * Relacionamento com a Venda
     * @return \yii\db\ActiveQuery
     */
    public function getVenda()
    {
        return $this->hasOne(Venda::class, ['id' => 'order_id']);
    }

    /**
     * Relacionamento com o Tenant (Lojista)
     * @return \yii\db\ActiveQuery
     */
    public function getTenant()
    {
        return $this->hasOne(Usuario::class, ['id' => 'tenant_id']);
    }
}
