<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\modules\vendas\models\Venda;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Parcela;
use app\models\Usuario;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\ComissaoConfig;

/**
 * ============================================================================================================
 * Model: Comissao
 * ============================================================================================================
 * Tabela: prest_comissoes
 * 
 * @property string $id
 * @property string $colaborador_id
 * @property string $venda_id
 * @property string $parcela_id
 * @property string $usuario_id
 * @property string $tipo_comissao
 * @property float $percentual_aplicado
 * @property float $valor_base
 * @property float $valor_comissao
 * @property string $status
 * @property string $data_pagamento
 * @property string $observacoes
 * @property string $data_criacao
 * 
 * @property string|null $comissao_config_id
 * 
 * @property Colaborador $colaborador
 * @property Venda $venda
 * @property Parcela $parcela
 * @property Usuario $usuario
 * @property ComissaoConfig|null $comissaoConfig
 */
class Comissao extends ActiveRecord
{
    const TIPO_VENDA = 'VENDA';
    const TIPO_COBRANCA = 'COBRANCA';
    
    const STATUS_PENDENTE = 'PENDENTE';
    const STATUS_PAGA = 'PAGA';
    const STATUS_CANCELADA = 'CANCELADA';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_comissoes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['colaborador_id', 'usuario_id', 'percentual_aplicado', 'valor_base', 'valor_comissao'], 'required'],
            [['colaborador_id', 'venda_id', 'parcela_id', 'usuario_id', 'comissao_config_id'], 'string'],
            [['percentual_aplicado'], 'number', 'min' => 0, 'max' => 100],
            [['valor_base', 'valor_comissao'], 'number', 'min' => 0],
            [['tipo_comissao', 'status'], 'string', 'max' => 20],
            [['tipo_comissao'], 'in', 'range' => [self::TIPO_VENDA, self::TIPO_COBRANCA]],
            [['tipo_comissao'], 'default', 'value' => self::TIPO_VENDA],
            [['status'], 'in', 'range' => [self::STATUS_PENDENTE, self::STATUS_PAGA, self::STATUS_CANCELADA]],
            [['status'], 'default', 'value' => self::STATUS_PENDENTE],
            [['data_pagamento'], 'date', 'format' => 'php:Y-m-d'],
            [['observacoes'], 'string'],
            [['colaborador_id'], 'exist', 'skipOnError' => true, 'targetClass' => Colaborador::class, 'targetAttribute' => ['colaborador_id' => 'id']],
            [['venda_id'], 'exist', 'skipOnError' => true, 'targetClass' => Venda::class, 'targetAttribute' => ['venda_id' => 'id']],
            [['parcela_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parcela::class, 'targetAttribute' => ['parcela_id' => 'id']],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['comissao_config_id'], 'exist', 'skipOnError' => true, 'targetClass' => ComissaoConfig::class, 'targetAttribute' => ['comissao_config_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'colaborador_id' => 'Colaborador',
            'venda_id' => 'Venda',
            'parcela_id' => 'Parcela',
            'usuario_id' => 'Usuário',
            'tipo_comissao' => 'Tipo',
            'percentual_aplicado' => 'Percentual (%)',
            'valor_base' => 'Valor Base',
            'valor_comissao' => 'Valor da Comissão',
            'status' => 'Status',
            'data_pagamento' => 'Data de Pagamento',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
            'comissao_config_id' => 'Configuração de Comissão',
        ];
    }

    /**
     * Marca comissão como paga
     */
    public function marcarComoPaga()
    {
        $this->status = self::STATUS_PAGA;
        $this->data_pagamento = date('Y-m-d');
        return $this->save();
    }

    public function getColaborador()
    {
        return $this->hasOne(Colaborador::class, ['id' => 'colaborador_id']);
    }

    public function getVenda()
    {
        return $this->hasOne(Venda::class, ['id' => 'venda_id']);
    }

    public function getParcela()
    {
        return $this->hasOne(Parcela::class, ['id' => 'parcela_id']);
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Retorna relação com ComissaoConfig
     */
    public function getComissaoConfig()
    {
        return $this->hasOne(ComissaoConfig::class, ['id' => 'comissao_config_id']);
    }

    /**
     * Calcula comissão de venda
     */
    public static function calcularComissaoVenda($vendaId, $colaboradorId)
    {
        $venda = Venda::findOne($vendaId);
        $colaborador = Colaborador::findOne($colaboradorId);
        
        if (!$venda || !$colaborador) {
            return false;
        }
        
        $comissao = new self();
        $comissao->colaborador_id = $colaboradorId;
        $comissao->venda_id = $vendaId;
        $comissao->usuario_id = $venda->usuario_id;
        $comissao->tipo_comissao = self::TIPO_VENDA;
        $comissao->percentual_aplicado = $colaborador->percentual_comissao_venda;
        $comissao->valor_base = $venda->valor_total;
        $comissao->valor_comissao = ($venda->valor_total * $colaborador->percentual_comissao_venda) / 100;
        $comissao->status = self::STATUS_PENDENTE;
        
        return $comissao->save() ? $comissao : false;
    }

    /**
     * Calcula comissão de cobrança
     */
    public static function calcularComissaoCobranca($parcelaId, $colaboradorId)
    {
        $parcela = Parcela::findOne($parcelaId);
        $colaborador = Colaborador::findOne($colaboradorId);
        
        if (!$parcela || !$colaborador || $parcela->status_parcela_codigo != StatusParcela::PAGA) {
            return false;
        }
        
        $comissao = new self();
        $comissao->colaborador_id = $colaboradorId;
        $comissao->parcela_id = $parcelaId;
        $comissao->usuario_id = $parcela->usuario_id;
        $comissao->tipo_comissao = self::TIPO_COBRANCA;
        $comissao->percentual_aplicado = $colaborador->percentual_comissao_cobranca;
        $comissao->valor_base = $parcela->valor_pago;
        $comissao->valor_comissao = ($parcela->valor_pago * $colaborador->percentual_comissao_cobranca) / 100;
        $comissao->status = self::STATUS_PENDENTE;
        
        return $comissao->save() ? $comissao : false;
    }
}