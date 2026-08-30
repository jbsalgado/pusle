<?php

namespace app\modules\admin\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Usuario;

/**
 * SaasFatura Model - Fatura de Cobrança do SaaS contra o Lojista
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $mes_referencia
 * @property string $data_fechamento
 * @property string $data_vencimento
 * @property float $gmv_marketplace
 * @property float $gmv_catalogo
 * @property int $total_pedidos_marketplace
 * @property int $total_pedidos_catalogo
 * @property float $valor_mensalidade
 * @property float $valor_comissao_marketplace
 * @property float $valor_comissao_catalogo
 * @property float $valor_pedidos_excedentes
 * @property float $valor_descontos
 * @property float $valor_total
 * @property string $status
 * @property string|null $data_pagamento
 * @property string|null $metodo_pagamento
 * @property string|null $qr_code_pix
 * @property string|null $codigo_pix
 * @property string|null $link_pagamento
 * @property string|null $transacao_gateway_id
 * @property array $detalhes_json
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Usuario $usuario
 */
class SaasFatura extends ActiveRecord
{
    const STATUS_PENDENTE = 'pendente';
    const STATUS_PAGA = 'paga';
    const STATUS_ATRASADA = 'atrasada';
    const STATUS_CANCELADA = 'cancelada';

    public static function tableName()
    {
        return 'prest_saas_faturas';
    }

    public function rules()
    {
        return [
            [['usuario_id', 'mes_referencia', 'data_fechamento', 'data_vencimento', 'valor_total'], 'required'],
            [['usuario_id'], 'string'],
            [['mes_referencia'], 'string', 'max' => 7],
            [['data_fechamento', 'data_vencimento', 'data_pagamento'], 'safe'],
            [['gmv_marketplace', 'gmv_catalogo', 'valor_mensalidade', 'valor_comissao_marketplace', 'valor_comissao_catalogo', 'valor_pedidos_excedentes', 'valor_descontos', 'valor_total'], 'number'],
            [['total_pedidos_marketplace', 'total_pedidos_catalogo'], 'integer'],
            [['status'], 'in', 'range' => [self::STATUS_PENDENTE, self::STATUS_PAGA, self::STATUS_ATRASADA, self::STATUS_CANCELADA]],
            [['metodo_pagamento', 'transacao_gateway_id'], 'string', 'max' => 150],
            [['qr_code_pix', 'codigo_pix', 'link_pagamento'], 'string'],
            [['detalhes_json'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'Número da Fatura',
            'usuario_id' => 'Lojista',
            'mes_referencia' => 'Mês de Referência',
            'data_fechamento' => 'Data de Fechamento',
            'data_vencimento' => 'Data de Vencimento',
            'gmv_marketplace' => 'GMV Marketplaces (R$)',
            'gmv_catalogo' => 'GMV Catálogo Próprio (R$)',
            'total_pedidos_marketplace' => 'Qtd. Pedidos Mktp',
            'total_pedidos_catalogo' => 'Qtd. Pedidos Catálogo',
            'valor_mensalidade' => 'Mensalidade Fixa (R$)',
            'valor_comissao_marketplace' => 'Comissão Marketplaces (R$)',
            'valor_comissao_catalogo' => 'Comissão Catálogo (R$)',
            'valor_pedidos_excedentes' => 'Tarifas Excedentes (R$)',
            'valor_descontos' => 'Descontos (R$)',
            'valor_total' => 'Valor Total da Fatura (R$)',
            'status' => 'Status',
            'data_pagamento' => 'Data do Pagamento',
            'metodo_pagamento' => 'Método de Pagamento',
        ];
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function isAtrasada(): bool
    {
        if ($this->status === self::STATUS_PAGA || $this->status === self::STATUS_CANCELADA) {
            return false;
        }
        return strtotime($this->data_vencimento) < strtotime(date('Y-m-d'));
    }
}
