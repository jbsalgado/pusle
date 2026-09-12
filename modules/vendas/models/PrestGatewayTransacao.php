<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use app\models\Usuario;
use app\modules\vendas\models\Venda;

/**
 * Model: PrestGatewayTransacao
 * Tabela: prest_gateway_transacoes
 *
 * Registro completo, auditável e estruturado de todas as transações
 * de vendas via gateway de pagamento por tenant.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $venda_id
 * @property string $gateway
 * @property string $transacao_id
 * @property string|null $tipo_pagamento
 * @property float $valor_bruto
 * @property float $taxa_gateway
 * @property float $taxa_saas
 * @property float $valor_liquido
 * @property string $status
 * @property string|null $status_detail
 * @property string|null $cartao_bandeira
 * @property string|null $cartao_ultimos_digitos
 * @property int $parcelas
 * @property array|string|null $payload_requisicao
 * @property array|string|null $payload_resposta
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Usuario $tenant
 * @property Venda|null $venda
 */
class PrestGatewayTransacao extends ActiveRecord
{
    const GATEWAY_MERCADOPAGO = 'mercadopago';
    const GATEWAY_ASAAS       = 'asaas';

    const TIPO_PIX         = 'pix';
    const TIPO_CREDIT_CARD = 'credit_card';
    const TIPO_DEBIT_CARD  = 'debit_card';
    const TIPO_POINT       = 'point';
    const TIPO_WALLET      = 'wallet';
    const TIPO_BOLETO      = 'boleto';

    const STATUS_APPROVED        = 'approved';
    const STATUS_PENDING         = 'pending';
    const STATUS_IN_PROCESS      = 'in_process';
    const STATUS_REJECTED        = 'rejected';
    const STATUS_REFUNDED        = 'refunded';
    const STATUS_CANCELLED       = 'cancelled';
    const STATUS_REQUIRES_ACTION = 'requires_action';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%prest_gateway_transacoes}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tenant_id', 'gateway', 'transacao_id', 'status'], 'required'],
            [['tenant_id', 'venda_id'], 'string'],
            [['valor_bruto', 'taxa_gateway', 'taxa_saas', 'valor_liquido'], 'number'],
            [['parcelas'], 'integer'],
            [['gateway', 'tipo_pagamento', 'status', 'cartao_bandeira'], 'string', 'max' => 50],
            [['transacao_id', 'status_detail'], 'string', 'max' => 100],
            [['cartao_ultimos_digitos'], 'string', 'max' => 4],
            [['payload_requisicao', 'payload_resposta', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * Relação com tenant (prest_usuarios)
     */
    public function getTenant()
    {
        return $this->hasOne(Usuario::class, ['id' => 'tenant_id']);
    }

    /**
     * Relação com pedido de venda (prest_vendas)
     */
    public function getVenda()
    {
        return $this->hasOne(Venda::class, ['id' => 'venda_id']);
    }

    /**
     * Registra ou atualiza uma transação no banco com tratamento fail-safe (nunca interrompe o fluxo).
     *
     * @param array $dados
     * @return static|null
     */
    public static function registrar(array $dados)
    {
        try {
            $gateway     = $dados['gateway'] ?? self::GATEWAY_MERCADOPAGO;
            $transacaoId = (string)($dados['transacao_id'] ?? '');

            if (empty($transacaoId) || empty($dados['tenant_id'])) {
                Yii::warning('PrestGatewayTransacao::registrar ignorado: tenant_id ou transacao_id ausentes.', 'gateway');
                return null;
            }

            // Tenta localizar registro prévio por gateway e transacao_id
            $model = static::findOne([
                'gateway'      => $gateway,
                'transacao_id' => $transacaoId,
            ]);

            $isNew = false;
            if (!$model) {
                $model = new static();
                $model->gateway      = $gateway;
                $model->transacao_id = $transacaoId;
                $isNew = true;
            }

            // Mapeamento dos campos obrigatórios e opcionais
            if (!empty($dados['tenant_id'])) {
                $model->tenant_id = $dados['tenant_id'];
            }
            if (!empty($dados['venda_id'])) {
                $model->venda_id = $dados['venda_id'];
            }
            if (isset($dados['tipo_pagamento'])) {
                $model->tipo_pagamento = $dados['tipo_pagamento'];
            }
            if (isset($dados['valor_bruto'])) {
                $model->valor_bruto = (float)$dados['valor_bruto'];
            }
            if (isset($dados['taxa_gateway'])) {
                $model->taxa_gateway = (float)$dados['taxa_gateway'];
            }
            if (isset($dados['taxa_saas'])) {
                $model->taxa_saas = (float)$dados['taxa_saas'];
            }

            // Cálculo do valor líquido se não fornecido
            if (isset($dados['valor_liquido'])) {
                $model->valor_liquido = (float)$dados['valor_liquido'];
            } elseif (isset($model->valor_bruto)) {
                $model->valor_liquido = round((float)$model->valor_bruto - (float)$model->taxa_gateway - (float)$model->taxa_saas, 2);
            }

            if (!empty($dados['status'])) {
                $model->status = $dados['status'];
            }
            if (isset($dados['status_detail'])) {
                $model->status_detail = $dados['status_detail'];
            }
            if (isset($dados['cartao_bandeira'])) {
                $model->cartao_bandeira = $dados['cartao_bandeira'];
            }
            if (isset($dados['cartao_ultimos_digitos'])) {
                $model->cartao_ultimos_digitos = $dados['cartao_ultimos_digitos'];
            }
            if (isset($dados['parcelas'])) {
                $model->parcelas = (int)$dados['parcelas'];
            }

            // Tratamento de payloads JSONB
            if (isset($dados['payload_requisicao'])) {
                $model->payload_requisicao = is_array($dados['payload_requisicao'])
                    ? json_encode($dados['payload_requisicao'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $dados['payload_requisicao'];
            }
            if (isset($dados['payload_resposta'])) {
                $model->payload_resposta = is_array($dados['payload_resposta']) || is_object($dados['payload_resposta'])
                    ? json_encode($dados['payload_resposta'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $dados['payload_resposta'];
            }

            $model->updated_at = new Expression('NOW()');
            if ($isNew) {
                $model->created_at = new Expression('NOW()');
            }

            // Uso do DAO para inserção/atualização direta de JSONB compatível com PostgreSQL
            $db = Yii::$app->db;
            if ($isNew) {
                $sql = "
                    INSERT INTO prest_gateway_transacoes (
                        tenant_id,
                        venda_id,
                        gateway,
                        transacao_id,
                        tipo_pagamento,
                        valor_bruto,
                        taxa_gateway,
                        taxa_saas,
                        valor_liquido,
                        status,
                        status_detail,
                        cartao_bandeira,
                        cartao_ultimos_digitos,
                        parcelas,
                        payload_requisicao,
                        payload_resposta,
                        created_at,
                        updated_at
                    ) VALUES (
                        :tenant_id::uuid,
                        " . ($model->venda_id ? ":venda_id::uuid" : "NULL") . ",
                        :gateway,
                        :transacao_id,
                        :tipo_pagamento,
                        :valor_bruto,
                        :taxa_gateway,
                        :taxa_saas,
                        :valor_liquido,
                        :status,
                        :status_detail,
                        :cartao_bandeira,
                        :cartao_ultimos_digitos,
                        :parcelas,
                        " . ($model->payload_requisicao ? ":payload_requisicao::jsonb" : "NULL") . ",
                        " . ($model->payload_resposta ? ":payload_resposta::jsonb" : "NULL") . ",
                        NOW(),
                        NOW()
                    )
                    RETURNING id
                ";

                $params = [
                    ':tenant_id'              => $model->tenant_id,
                    ':gateway'                => $model->gateway,
                    ':transacao_id'           => $model->transacao_id,
                    ':tipo_pagamento'         => $model->tipo_pagamento,
                    ':valor_bruto'            => $model->valor_bruto ?? 0.00,
                    ':taxa_gateway'           => $model->taxa_gateway ?? 0.00,
                    ':taxa_saas'              => $model->taxa_saas ?? 0.00,
                    ':valor_liquido'          => $model->valor_liquido ?? 0.00,
                    ':status'                 => $model->status ?? 'pending',
                    ':status_detail'          => $model->status_detail,
                    ':cartao_bandeira'        => $model->cartao_bandeira,
                    ':cartao_ultimos_digitos' => $model->cartao_ultimos_digitos,
                    ':parcelas'               => $model->parcelas ?? 1,
                ];

                if ($model->venda_id) {
                    $params[':venda_id'] = $model->venda_id;
                }
                if ($model->payload_requisicao) {
                    $params[':payload_requisicao'] = $model->payload_requisicao;
                }
                if ($model->payload_resposta) {
                    $params[':payload_resposta'] = $model->payload_resposta;
                }

                $model->id = $db->createCommand($sql, $params)->queryScalar();
            } else {
                $sql = "
                    UPDATE prest_gateway_transacoes
                    SET
                        venda_id               = COALESCE(" . ($model->venda_id ? ":venda_id::uuid" : "NULL") . ", venda_id),
                        tipo_pagamento         = COALESCE(:tipo_pagamento, tipo_pagamento),
                        valor_bruto            = COALESCE(:valor_bruto, valor_bruto),
                        taxa_gateway           = COALESCE(:taxa_gateway, taxa_gateway),
                        taxa_saas              = COALESCE(:taxa_saas, taxa_saas),
                        valor_liquido          = COALESCE(:valor_liquido, valor_liquido),
                        status                 = COALESCE(:status, status),
                        status_detail          = COALESCE(:status_detail, status_detail),
                        cartao_bandeira        = COALESCE(:cartao_bandeira, cartao_bandeira),
                        cartao_ultimos_digitos = COALESCE(:cartao_ultimos_digitos, cartao_ultimos_digitos),
                        parcelas               = COALESCE(:parcelas, parcelas),
                        payload_resposta       = COALESCE(" . ($model->payload_resposta ? ":payload_resposta::jsonb" : "NULL") . ", payload_resposta),
                        updated_at             = NOW()
                    WHERE id = :id::uuid
                ";

                $params = [
                    ':id'                     => $model->id,
                    ':tipo_pagamento'         => $model->tipo_pagamento,
                    ':valor_bruto'            => $model->valor_bruto,
                    ':taxa_gateway'           => $model->taxa_gateway,
                    ':taxa_saas'              => $model->taxa_saas,
                    ':valor_liquido'          => $model->valor_liquido,
                    ':status'                 => $model->status,
                    ':status_detail'          => $model->status_detail,
                    ':cartao_bandeira'        => $model->cartao_bandeira,
                    ':cartao_ultimos_digitos' => $model->cartao_ultimos_digitos,
                    ':parcelas'               => $model->parcelas,
                ];

                if ($model->venda_id) {
                    $params[':venda_id'] = $model->venda_id;
                }
                if ($model->payload_resposta) {
                    $params[':payload_resposta'] = $model->payload_resposta;
                }

                $db->createCommand($sql, $params)->execute();
            }

            return $model;
        } catch (\Throwable $e) {
            Yii::error('Erro ao salvar PrestGatewayTransacao (fail-safe ativado): ' . $e->getMessage(), 'gateway');
            return null;
        }
    }
}
