<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Parcela;
use app\models\Usuario;
use app\modules\vendas\models\Colaborador;
/**
 * ============================================================================================================
 * Model: HistoricoCobranca
 * ============================================================================================================
 * Tabela: prest_historico_cobranca
 * 
 * @property string $id
 * @property string $parcela_id
 * @property string $cobrador_id
 * @property string $cliente_id
 * @property string $usuario_id
 * @property string $tipo_acao
 * @property float $valor_recebido
 * @property string $observacao
 * @property float $localizacao_lat
 * @property float $localizacao_lng
 * @property string $data_acao
 * 
 * @property Parcela $parcela
 * @property Colaborador $cobrador
 * @property Cliente $cliente
 * @property Usuario $usuario
 */
class HistoricoCobranca extends ActiveRecord
{
    const TIPO_VISITA = 'VISITA';
    const TIPO_PAGAMENTO = 'PAGAMENTO';
    const TIPO_AUSENTE = 'AUSENTE';
    const TIPO_RECUSA = 'RECUSA';
    const TIPO_NEGOCIACAO = 'NEGOCIACAO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_historico_cobranca';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parcela_id', 'cobrador_id', 'cliente_id', 'usuario_id', 'tipo_acao'], 'required'],
            [['parcela_id', 'cobrador_id', 'cliente_id', 'usuario_id'], 'string'],
            [['valor_recebido'], 'number', 'min' => 0],
            [['observacao'], 'string'],
            [['localizacao_lat', 'localizacao_lng'], 'number'],
            [['tipo_acao'], 'string', 'max' => 20],
            [['tipo_acao'], 'in', 'range' => [
                self::TIPO_VISITA,
                self::TIPO_PAGAMENTO,
                self::TIPO_AUSENTE,
                self::TIPO_RECUSA,
                self::TIPO_NEGOCIACAO
            ]],
            [['parcela_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parcela::class, 'targetAttribute' => ['parcela_id' => 'id']],
            [['cobrador_id'], 'exist', 'skipOnError' => true, 'targetClass' => Colaborador::class, 'targetAttribute' => ['cobrador_id' => 'id']],
            [['cliente_id'], 'exist', 'skipOnError' => true, 'targetClass' => Cliente::class, 'targetAttribute' => ['cliente_id' => 'id']],
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
            'parcela_id' => 'Parcela',
            'cobrador_id' => 'Cobrador',
            'cliente_id' => 'Cliente',
            'usuario_id' => 'Usuário',
            'tipo_acao' => 'Tipo de Ação',
            'valor_recebido' => 'Valor Recebido',
            'observacao' => 'Observação',
            'localizacao_lat' => 'Latitude',
            'localizacao_lng' => 'Longitude',
            'data_acao' => 'Data/Hora',
        ];
    }

    /**
     * Retorna descrição do tipo de ação
     */
    public function getDescricaoTipoAcao()
    {
        $tipos = [
            self::TIPO_VISITA => 'Visita',
            self::TIPO_PAGAMENTO => 'Pagamento',
            self::TIPO_AUSENTE => 'Cliente Ausente',
            self::TIPO_RECUSA => 'Recusa de Pagamento',
            self::TIPO_NEGOCIACAO => 'Negociação',
        ];
        return isset($tipos[$this->tipo_acao]) ? $tipos[$this->tipo_acao] : $this->tipo_acao;
    }

    public function getParcela()
    {
        return $this->hasOne(Parcela::class, ['id' => 'parcela_id']);
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
}