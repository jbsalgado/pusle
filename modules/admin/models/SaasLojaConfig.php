<?php

namespace app\modules\admin\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Usuario;

/**
 * SaasLojaConfig Model - Configuração de cobrança por Tenant
 *
 * @property int $id
 * @property string $usuario_id
 * @property int|null $plano_id
 * @property int $dia_vencimento
 * @property float|null $percentual_custom_catalogo
 * @property float|null $percentual_custom_marketplace
 * @property float|null $valor_custom_mensalidade
 * @property string $status_cobranca
 * @property int $dias_carencia_bloqueio
 * @property string|null $observacoes
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Usuario $usuario
 * @property SaasPlano $plano
 */
class SaasLojaConfig extends ActiveRecord
{
    const STATUS_ADIMPLENTE = 'adimplente';
    const STATUS_INADIMPLENTE = 'inadimplente';
    const STATUS_BLOQUEADO = 'bloqueado';
    const STATUS_ISENTO = 'isento';

    public static function tableName()
    {
        return 'prest_saas_loja_config';
    }

    public function rules()
    {
        return [
            [['usuario_id'], 'required'],
            [['usuario_id'], 'string'],
            [['plano_id', 'dia_vencimento', 'dias_carencia_bloqueio'], 'integer'],
            [['percentual_custom_catalogo', 'percentual_custom_marketplace', 'valor_custom_mensalidade'], 'number', 'min' => 0],
            [['status_cobranca'], 'string', 'max' => 30],
            [['status_cobranca'], 'in', 'range' => [self::STATUS_ADIMPLENTE, self::STATUS_INADIMPLENTE, self::STATUS_BLOQUEADO, self::STATUS_ISENTO]],
            [['observacoes'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Lojista / Tenant',
            'plano_id' => 'Plano Comercial',
            'dia_vencimento' => 'Dia de Vencimento',
            'percentual_custom_catalogo' => 'Taxa Catálogo Custom (%)',
            'percentual_custom_marketplace' => 'Taxa Marketplace Custom (%)',
            'valor_custom_mensalidade' => 'Mensalidade Custom (R$)',
            'status_cobranca' => 'Status Financeiro',
            'dias_carencia_bloqueio' => 'Dias de Carência antes do Bloqueio',
            'observacoes' => 'Observações Internas',
        ];
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getPlano()
    {
        return $this->hasOne(SaasPlano::class, ['id' => 'plano_id']);
    }

    /**
     * Retorna a taxa efetiva de comissão do catálogo para esta loja
     */
    public function getTaxaCatalogoEfetiva(): float
    {
        if ($this->percentual_custom_catalogo !== null) {
            return (float) $this->percentual_custom_catalogo;
        }
        if ($this->plano) {
            return (float) $this->plano->percentual_comissao_catalogo;
        }
        return (float) SaasConfigGlobal::getValor('taxa_padrao_catalogo_split', 2.50);
    }

    /**
     * Retorna a taxa efetiva de comissão de marketplace para esta loja
     */
    public function getTaxaMarketplaceEfetiva(): float
    {
        if ($this->percentual_custom_marketplace !== null) {
            return (float) $this->percentual_custom_marketplace;
        }
        if ($this->plano) {
            return (float) $this->plano->percentual_comissao_marketplace;
        }
        return (float) SaasConfigGlobal::getValor('taxa_padrao_marketplace_gmv', 1.00);
    }

    /**
     * Retorna a mensalidade efetiva para esta loja
     */
    public function getMensalidadeEfetiva(): float
    {
        if ($this->valor_custom_mensalidade !== null) {
            return (float) $this->valor_custom_mensalidade;
        }
        if ($this->plano) {
            return (float) $this->plano->valor_mensalidade;
        }
        return 0.00;
    }

    /**
     * Obtém ou inicializa a configuração de uma loja
     */
    public static function getOrCreateForUser(string $usuarioId): self
    {
        $config = static::findOne(['usuario_id' => $usuarioId]);
        if (!$config) {
            $planoPadrao = SaasPlano::findOne(['destaque' => true, 'ativo' => true]) ?: SaasPlano::find()->where(['ativo' => true])->one();
            $config = new static();
            $config->usuario_id = $usuarioId;
            $config->plano_id = $planoPadrao ? $planoPadrao->id : null;
            $config->dia_vencimento = 10;
            $config->status_cobranca = self::STATUS_ADIMPLENTE;
            $config->save(false);
        }
        return $config;
    }
}
