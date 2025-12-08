<?php
namespace app\modules\vendas\models;



use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\modules\vendas\models\Venda;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Cliente;
use app\models\Usuario;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\CarteiraCobranca;

/**
 * ============================================================================================================
 * Model: Parcela
 * ============================================================================================================
 * Tabela: prest_parcelas
 * 
 * @property string $id
 * @property string $venda_id
 * @property string $usuario_id
 * @property integer $numero_parcela
 * @property float $valor_parcela
 * @property string $data_vencimento
 * @property string $status_parcela_codigo
 * @property string $data_pagamento
 * @property float $valor_pago
 * @property string $observacoes
 * @property string $forma_pagamento_id
 * @property string $cobrador_id
 * @property string $carteira_cobranca_id
 * 
 * @property Venda $venda
 * @property Usuario $usuario
 * @property StatusParcela $statusParcela
 * @property FormaPagamento $formaPagamento
 * @property Colaborador $cobrador
 * @property CarteiraCobranca $carteiraCobranca
 */
class Parcela extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_parcelas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['venda_id', 'usuario_id', 'numero_parcela', 'valor_parcela', 'data_vencimento'], 'required'],
            [['venda_id', 'usuario_id', 'status_parcela_codigo', 'forma_pagamento_id', 'cobrador_id', 'carteira_cobranca_id'], 'string'],
            [['numero_parcela'], 'integer', 'min' => 1],
            [['valor_parcela', 'valor_pago'], 'number', 'min' => 0],
            [['data_vencimento', 'data_pagamento'], 'date', 'format' => 'php:Y-m-d'],
            [['observacoes'], 'string'],
            [['venda_id'], 'exist', 'skipOnError' => true, 'targetClass' => Venda::class, 'targetAttribute' => ['venda_id' => 'id']],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['status_parcela_codigo'], 'exist', 'skipOnError' => true, 'targetClass' => StatusParcela::class, 'targetAttribute' => ['status_parcela_codigo' => 'codigo']],
            [['cobrador_id'], 'exist', 'skipOnError' => true, 'targetClass' => Colaborador::class, 'targetAttribute' => ['cobrador_id' => 'id']],
            // Validação opcional de forma_pagamento_id (pode ser null, mas se preenchido deve existir)
            [['forma_pagamento_id'], 'exist', 'skipOnError' => true, 'skipOnEmpty' => true, 'targetClass' => FormaPagamento::class, 'targetAttribute' => ['forma_pagamento_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Gera UUID se for um novo registro e não tiver ID definido
            if ($insert && empty($this->id)) {
                $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                $this->id = $uuid;
            }
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'venda_id' => 'Venda',
            'usuario_id' => 'Usuário',
            'numero_parcela' => 'Parcela Nº',
            'valor_parcela' => 'Valor da Parcela',
            'data_vencimento' => 'Vencimento',
            'status_parcela_codigo' => 'Status',
            'data_pagamento' => 'Data Pagamento',
            'valor_pago' => 'Valor Pago',
            'observacoes' => 'Observações',
            'forma_pagamento_id' => 'Forma de Pagamento',
            'cobrador_id' => 'Cobrador',
            'carteira_cobranca_id' => 'Carteira',
        ];
    }

    /**
     * Calcula dias de atraso
     */
    public function getDiasAtraso()
    {
        if ($this->status_parcela_codigo == StatusParcela::PAGA) {
            return 0;
        }
        
        $hoje = new \DateTime();
        $vencimento = new \DateTime($this->data_vencimento);
        
        if ($vencimento < $hoje) {
            return $hoje->diff($vencimento)->days;
        }
        
        return 0;
    }

    /**
     * Verifica se está vencida
     */
    public function getEstaVencida()
    {
        return $this->getDiasAtraso() > 0;
    }

    /**
     * Registra pagamento
     */
    public function registrarPagamento($valorPago, $cobradorId = null, $formaPagamentoId = null)
    {
        $this->valor_pago = $valorPago;
        $this->data_pagamento = date('Y-m-d');
        $this->status_parcela_codigo = StatusParcela::PAGA;
        
        if ($cobradorId) {
            $this->cobrador_id = $cobradorId;
        }
        
        if ($formaPagamentoId) {
            $this->forma_pagamento_id = $formaPagamentoId;
        }
        
        $saved = $this->save();
        
        // ===== INTEGRAÇÃO COM CAIXA =====
        // Registra entrada no caixa quando parcela é paga (apenas se salvou com sucesso)
        if ($saved) {
            try {
                $movimentacao = \app\modules\caixa\helpers\CaixaHelper::registrarEntradaParcela(
                    $this->id,
                    $this->valor_pago,
                    $this->forma_pagamento_id,
                    $this->usuario_id
                );
                
                if ($movimentacao) {
                    Yii::info("✅ Entrada registrada no caixa para Parcela ID {$this->id}", 'Parcela');
                } else {
                    // Não falha o pagamento se não houver caixa aberto, apenas registra no log
                    Yii::warning("⚠️ Não foi possível registrar entrada no caixa para Parcela ID {$this->id} (caixa pode não estar aberto)", 'Parcela');
                }
            } catch (\Exception $e) {
                // Não falha o pagamento se houver erro no caixa, apenas registra no log
                Yii::error("Erro ao registrar entrada no caixa (não crítico): " . $e->getMessage(), 'Parcela');
            }
        }
        
        return $saved;
    }

    public function getVenda()
    {
        return $this->hasOne(Venda::class, ['id' => 'venda_id']);
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getStatusParcela()
    {
        return $this->hasOne(StatusParcela::class, ['codigo' => 'status_parcela_codigo']);
    }

    public function getFormaPagamento()
    {
        return $this->hasOne(FormaPagamento::class, ['id' => 'forma_pagamento_id']);
    }

    public function getCobrador()
    {
        return $this->hasOne(Colaborador::class, ['id' => 'cobrador_id']);
    }

    public function getCarteiraCobranca()
    {
        return $this->hasOne(CarteiraCobranca::class, ['id' => 'carteira_cobranca_id']);
    }

    public function getCliente()
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id'])
            ->via('venda');
    }
}