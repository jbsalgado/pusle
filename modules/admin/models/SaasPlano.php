<?php

namespace app\modules\admin\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * SaasPlano Model - Planos comerciais do SaaS
 *
 * @property int $id
 * @property string $nome
 * @property string $descricao
 * @property float $valor_mensalidade
 * @property float $percentual_comissao_catalogo
 * @property float $percentual_comissao_marketplace
 * @property int $limite_pedidos_inclusos
 * @property float $valor_pedido_excedente
 * @property bool $ativo
 * @property bool $destaque
 * @property string $data_criacao
 * @property string $data_atualizacao
 */
class SaasPlano extends ActiveRecord
{
    public static function tableName()
    {
        return 'prest_saas_planos';
    }

    public function rules()
    {
        return [
            [['nome', 'valor_mensalidade'], 'required'],
            [['nome'], 'string', 'max' => 100],
            [['descricao'], 'string'],
            [['valor_mensalidade', 'percentual_comissao_catalogo', 'percentual_comissao_marketplace', 'valor_pedido_excedente'], 'number', 'min' => 0],
            [['limite_pedidos_inclusos'], 'integer', 'min' => 0],
            [['ativo', 'destaque'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nome' => 'Nome do Plano',
            'descricao' => 'Descrição / Benefícios',
            'valor_mensalidade' => 'Mensalidade Fixa (R$)',
            'percentual_comissao_catalogo' => 'Comissão Catálogo (%)',
            'percentual_comissao_marketplace' => 'Comissão Marketplace (%)',
            'limite_pedidos_inclusos' => 'Pedidos Inclusos / Mês',
            'valor_pedido_excedente' => 'Valor por Pedido Excedente (R$)',
            'ativo' => 'Ativo',
            'destaque' => 'Destaque Comercial',
        ];
    }

    public static function getDropdown()
    {
        return static::find()
            ->where(['ativo' => true])
            ->select(['nome', 'id'])
            ->indexBy('id')
            ->column();
    }
}
