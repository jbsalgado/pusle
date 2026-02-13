<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\modules\vendas\models\Venda;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Cliente;
use app\models\Usuario;
use app\modules\vendas\models\OrcamentoItem;

/**
 * ============================================================================================================
 * Model: Orcamento
 * ============================================================================================================
 * Tabela: prest_orcamentos
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $cliente_id
 * @property string $nome_cliente
 * @property string $telefone_cliente
 * @property string $email_cliente
 * @property float $valor_total
 * @property string $status
 * @property string $venda_id
 * @property string $validade_ate
 * @property string $observacoes
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 * @property Cliente $cliente
 * @property Venda $venda
 * @property OrcamentoItem[] $itens
 */
class Orcamento extends ActiveRecord
{
    const STATUS_PENDENTE = 'PENDENTE';
    const STATUS_APROVADO = 'APROVADO';
    const STATUS_REJEITADO = 'REJEITADO';
    const STATUS_CONVERTIDO = 'CONVERTIDO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'orcamentos';
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id', 'valor_total'], 'required'],
            [['usuario_id', 'cliente_id', 'venda_id'], 'string'],
            [['valor_total'], 'number', 'min' => 0],
            [['nome_cliente'], 'string', 'max' => 150],
            [['telefone_cliente'], 'string', 'max' => 20],
            [['email_cliente'], 'string', 'max' => 100],
            [['email_cliente'], 'email'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDENTE,
                self::STATUS_APROVADO,
                self::STATUS_REJEITADO,
                self::STATUS_CONVERTIDO
            ]],
            [['status'], 'default', 'value' => self::STATUS_PENDENTE],
            [['validade_ate'], 'date', 'format' => 'php:Y-m-d'],
            [['observacoes'], 'string'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['cliente_id'], 'exist', 'skipOnError' => true, 'targetClass' => Cliente::class, 'targetAttribute' => ['cliente_id' => 'id']],
            [['venda_id'], 'exist', 'skipOnError' => true, 'targetClass' => Venda::class, 'targetAttribute' => ['venda_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'cliente_id' => 'Cliente',
            'nome_cliente' => 'Nome do Cliente',
            'telefone_cliente' => 'Telefone',
            'email_cliente' => 'E-mail',
            'valor_total' => 'Valor Total',
            'status' => 'Status',
            'venda_id' => 'Venda Gerada',
            'validade_ate' => 'Válido Até',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Verifica se orçamento está vencido
     */
    public function getEstaVencido()
    {
        if (!$this->validade_ate) return false;
        return strtotime($this->validade_ate) < time();
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getCliente()
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getVenda()
    {
        return $this->hasOne(Venda::class, ['id' => 'venda_id']);
    }

    public function getItens()
    {
        return $this->hasMany(OrcamentoItem::class, ['orcamento_id' => 'id']);
    }
}
