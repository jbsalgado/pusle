<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Cliente;
use app\models\Usuario;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\RotaCobranca;
use app\modules\vendas\models\PeriodoCobranca;

/**
 * ============================================================================================================
 * Model: CarteiraCobranca (CENTRAL DO SISTEMA DE COBRANÇA)
 * ============================================================================================================
 * Tabela: prest_carteira_cobranca
 * 
 * @property string $id
 * @property string $periodo_id
 * @property string $cobrador_id
 * @property string $cliente_id
 * @property string $usuario_id
 * @property string $rota_id
 * @property string $data_distribuicao
 * @property boolean $ativo
 * @property integer $total_parcelas
 * @property integer $parcelas_pagas
 * @property float $valor_total
 * @property float $valor_recebido
 * @property string $observacoes
 * 
 * @property PeriodoCobranca $periodo
 * @property Colaborador $cobrador
 * @property Cliente $cliente
 * @property Usuario $usuario
 * @property RotaCobranca $rota
 */
class CarteiraCobranca extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_carteira_cobranca';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['periodo_id', 'cobrador_id', 'cliente_id', 'usuario_id'], 'required'],
            [['periodo_id', 'cobrador_id', 'cliente_id', 'usuario_id', 'rota_id'], 'string'],
            [['ativo'], 'boolean'],
            [['total_parcelas', 'parcelas_pagas'], 'integer', 'min' => 0],
            [['total_parcelas', 'parcelas_pagas'], 'default', 'value' => 0],
            [['valor_total', 'valor_recebido'], 'number', 'min' => 0],
            [['valor_total', 'valor_recebido'], 'default', 'value' => 0],
            [['observacoes'], 'string'],
            [['periodo_id'], 'exist', 'skipOnError' => true, 'targetClass' => PeriodoCobranca::class, 'targetAttribute' => ['periodo_id' => 'id']],
            [['cobrador_id'], 'exist', 'skipOnError' => true, 'targetClass' => Colaborador::class, 'targetAttribute' => ['cobrador_id' => 'id']],
            [['cliente_id'], 'exist', 'skipOnError' => true, 'targetClass' => Cliente::class, 'targetAttribute' => ['cliente_id' => 'id']],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['rota_id'], 'exist', 'skipOnError' => true, 'targetClass' => RotaCobranca::class, 'targetAttribute' => ['rota_id' => 'id']],
            // Validação: cliente único por período
            [['cliente_id'], 'unique', 'targetAttribute' => ['periodo_id', 'cliente_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'periodo_id' => 'Período',
            'cobrador_id' => 'Cobrador',
            'cliente_id' => 'Cliente',
            'usuario_id' => 'Usuário',
            'rota_id' => 'Rota',
            'data_distribuicao' => 'Data de Distribuição',
            'ativo' => 'Ativo',
            'total_parcelas' => 'Total de Parcelas',
            'parcelas_pagas' => 'Parcelas Pagas',
            'valor_total' => 'Valor Total',
            'valor_recebido' => 'Valor Recebido',
            'observacoes' => 'Observações',
        ];
    }

    /**
     * Retorna percentual pago
     */
    public function getPercentualPago()
    {
        if ($this->total_parcelas == 0) return 0;
        return ($this->parcelas_pagas / $this->total_parcelas) * 100;
    }

    /**
     * Retorna saldo pendente
     */
    public function getSaldoPendente()
    {
        return $this->valor_total - $this->valor_recebido;
    }

    /**
     * Retorna status da carteira
     */
    public function getStatusCobranca()
    {
        if ($this->parcelas_pagas >= $this->total_parcelas) {
            return 'QUITADO';
        } elseif ($this->parcelas_pagas == 0) {
            return 'PENDENTE';
        } else {
            return 'PARCIAL';
        }
    }

    public function getPeriodo()
    {
        return $this->hasOne(PeriodoCobranca::class, ['id' => 'periodo_id']);
    }

    public function getCobrador()
    {
        return $this->hasOne(Colaborador::class, ['id' => 'cobrador_id']);
    }

    public function getCliente()
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getRota()
    {
        return $this->hasOne(RotaCobranca::class, ['id' => 'rota_id']);
    }
}
