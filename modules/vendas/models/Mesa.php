<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

/**
 * Model: Mesa (Restaurante, Bar e Lanchonete)
 * Tabela: prest_mesas
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $numero_mesa
 * @property string|null $nome_identificador
 * @property string $status
 * @property int $lugares
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Comanda|null $comandaAtiva
 */
class Mesa extends ActiveRecord
{
    const STATUS_LIVRE = 'livre';
    const STATUS_OCUPADA = 'ocupada';
    const STATUS_AGUARDANDO_CONTA = 'aguardando_conta';
    const STATUS_RESERVADA = 'reservada';

    public static function tableName()
    {
        return 'prest_mesas';
    }

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

    public function rules()
    {
        return [
            [['usuario_id', 'numero_mesa'], 'required'],
            [['usuario_id', 'id'], 'string'],
            [['lugares'], 'integer', 'min' => 1],
            [['lugares'], 'default', 'value' => 4],
            [['status'], 'string', 'max' => 30],
            [['status'], 'default', 'value' => self::STATUS_LIVRE],
            [['numero_mesa'], 'string', 'max' => 20],
            [['nome_identificador'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Dono da Loja',
            'numero_mesa' => 'Número da Mesa',
            'nome_identificador' => 'Identificador / Local',
            'status' => 'Status',
            'lugares' => 'Capacidade (Lugares)',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Relacionamento com a Comanda Aberta/Ativa vinculada à Mesa
     */
    public function getComandaAtiva()
    {
        return $this->hasOne(Comanda::class, ['mesa_id' => 'id'])
            ->andWhere(['!=', 'status', Comanda::STATUS_FECHADA])
            ->andWhere(['!=', 'status', Comanda::STATUS_CANCELADA])
            ->orderBy(['data_abertura' => SORT_DESC]);
    }

    /**
     * Retorna a comanda ativa existente ou cria uma comanda automatica se a mesa estiver em atendimento
     */
    public function getOuCriarComandaAtiva()
    {
        $comanda = $this->comandaAtiva;
        if (!$comanda) {
            $comanda = new Comanda();
            $comanda->usuario_id = $this->usuario_id;
            $comanda->mesa_id = $this->id;
            $comanda->numero_comanda = 'MESA-' . $this->numero_mesa;
            $comanda->cliente_nome = 'Cliente';
            $comanda->status = Comanda::STATUS_ABERTA;
            $comanda->save(false);
        }
        return $comanda;
    }

    /**
     * Retorna o valor total consumido na mesa no momento
     */
    public function getConsumoTotal()
    {
        $comanda = $this->comandaAtiva;
        if (!$comanda) {
            return 0.00;
        }
        return $comanda->getValorTotal();
    }

    /**
     * Configurações visuais de badge/cor do status da mesa
     */
    public function getStatusBadge()
    {
        switch ($this->status) {
            case self::STATUS_LIVRE:
                return [
                    'label' => 'Livre',
                    'bg' => 'bg-emerald-500',
                    'border' => 'border-emerald-500',
                    'badge' => 'bg-emerald-100 text-emerald-800',
                    'icon' => '🟢'
                ];
            case self::STATUS_OCUPADA:
                return [
                    'label' => 'Ocupada',
                    'bg' => 'bg-rose-500',
                    'border' => 'border-rose-500',
                    'badge' => 'bg-rose-100 text-rose-800',
                    'icon' => '🔴'
                ];
            case self::STATUS_AGUARDANDO_CONTA:
                return [
                    'label' => 'Conta Solicitada',
                    'bg' => 'bg-amber-500',
                    'border' => 'border-amber-500',
                    'badge' => 'bg-amber-100 text-amber-800',
                    'icon' => '🟡'
                ];
            case self::STATUS_RESERVADA:
                return [
                    'label' => 'Reservada',
                    'bg' => 'bg-sky-500',
                    'border' => 'border-sky-500',
                    'badge' => 'bg-sky-100 text-sky-800',
                    'icon' => '🔵'
                ];
            default:
                return [
                    'label' => 'Indefinido',
                    'bg' => 'bg-gray-500',
                    'border' => 'border-gray-500',
                    'badge' => 'bg-gray-100 text-gray-800',
                    'icon' => '⚪'
                ];
        }
    }
}
