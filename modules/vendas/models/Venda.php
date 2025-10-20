<?php
namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Cliente;
use app\models\Usuario;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\StatusVenda;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\Parcela;
/**
 * ============================================================================================================
 * Model: Venda
 * ============================================================================================================
 * Tabela: prest_vendas
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $cliente_id
 * @property string $colaborador_vendedor_id
 * @property string $data_venda
 * @property float $valor_total
 * @property integer $numero_parcelas
 * @property string $status_venda_codigo
 * @property string $observacoes
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 * @property Cliente $cliente
 * @property Colaborador $vendedor
 * @property StatusVenda $statusVenda
 * @property VendaItem[] $itens
 * @property Parcela[] $parcelas
 */
class Venda extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_vendas';
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
            [['usuario_id', 'cliente_id', 'valor_total'], 'required'],
            [['usuario_id', 'cliente_id', 'colaborador_vendedor_id', 'status_venda_codigo'], 'string'],
            [['valor_total'], 'number', 'min' => 0],
            [['numero_parcelas'], 'integer', 'min' => 1],
            [['numero_parcelas'], 'default', 'value' => 1],
            [['data_venda'], 'safe'],
            [['observacoes'], 'string'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['cliente_id'], 'exist', 'skipOnError' => true, 'targetClass' => Cliente::class, 'targetAttribute' => ['cliente_id' => 'id']],
            [['colaborador_vendedor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Colaborador::class, 'targetAttribute' => ['colaborador_vendedor_id' => 'id']],
            [['status_venda_codigo'], 'exist', 'skipOnError' => true, 'targetClass' => StatusVenda::class, 'targetAttribute' => ['status_venda_codigo' => 'codigo']],
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
            'colaborador_vendedor_id' => 'Vendedor',
            'data_venda' => 'Data da Venda',
            'valor_total' => 'Valor Total',
            'numero_parcelas' => 'Número de Parcelas',
            'status_venda_codigo' => 'Status',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    // /**
    //  * Gera parcelas da venda
    //  */
    // public function gerarParcelas()
    // {
    //     $valorParcela = $this->valor_total / $this->numero_parcelas;
    //     $dataVencimento = new \DateTime($this->data_venda);
        
    //     for ($i = 1; $i <= $this->numero_parcelas; $i++) {
    //         $parcela = new Parcela();
    //         $parcela->venda_id = $this->id;
    //         $parcela->usuario_id = $this->usuario_id;
    //         $parcela->numero_parcela = $i;
            
    //         // Ajuste para a última parcela (arredondamento)
    //         if ($i == $this->numero_parcelas) {
    //             $totalParcelas = Parcela::find()
    //                 ->where(['venda_id' => $this->id])
    //                 ->sum('valor_parcela');
    //             $parcela->valor_parcela = $this->valor_total - $totalParcelas;
    //         } else {
    //             $parcela->valor_parcela = round($valorParcela, 2);
    //         }
            
    //         // Vencimento: +30 dias por parcela
    //         $dataVencimento->modify('+30 days');
    //         $parcela->data_vencimento = $dataVencimento->format('Y-m-d');
    //         $parcela->status_parcela_codigo = StatusParcela::PENDENTE;
            
    //         $parcela->save();
    //     }
    // }

    public function gerarParcelas($formaPagamentoId = null) // Adiciona parâmetro opcional
    {
        // Deleta parcelas existentes para esta venda, caso existam (para evitar duplicatas se chamado mais de uma vez)
        Parcela::deleteAll(['venda_id' => $this->id]);

        if ($this->numero_parcelas <= 0) {
            $this->numero_parcelas = 1; // Garante pelo menos uma parcela
        }

        $valorParcelaBase = $this->valor_total / $this->numero_parcelas;
        $valorParcelaArredondado = round($valorParcelaBase, 2);
        $dataVencimento = new \DateTime($this->data_venda ?: 'now'); // Usa data da venda ou data atual
        $valorTotalGerado = 0;

        for ($i = 1; $i <= $this->numero_parcelas; $i++) {
            $parcela = new Parcela();
            $parcela->venda_id = $this->id;
            $parcela->usuario_id = $this->usuario_id;
            $parcela->numero_parcela = $i;

            // Ajuste para a última parcela (evita problemas de arredondamento)
            if ($i == $this->numero_parcelas) {
                $parcela->valor_parcela = $this->valor_total - $valorTotalGerado;
            } else {
                $parcela->valor_parcela = $valorParcelaArredondado;
                $valorTotalGerado += $valorParcelaArredondado;
            }

            // Vencimento: Primeira parcela +30 dias, as seguintes +30 dias da anterior
            if ($i > 1) { // Só incrementa a partir da segunda parcela
                $dataVencimento->modify('+30 days');
            } else if ($this->numero_parcelas > 1) { // Se for a primeira de várias, vence em 30 dias
                 $dataVencimento->modify('+30 days');
            } // Se for parcela única (à vista), o vencimento pode ser a data da venda ou hoje


            $parcela->data_vencimento = $dataVencimento->format('Y-m-d');
            $parcela->status_parcela_codigo = StatusParcela::PENDENTE; //
            $parcela->forma_pagamento_id = $formaPagamentoId; // ✅ Define a forma de pagamento

            if (!$parcela->save()) {
                 // Lançar uma exceção ou logar o erro é melhor que falhar silenciosamente
                 Yii::error("Erro ao salvar parcela {$i} para venda {$this->id}: " . print_r($parcela->errors, true));
                 throw new \yii\db\Exception("Não foi possível salvar a parcela {$i}.");
            }
        }
    }

    /**
     * Calcula valor já pago
     */
    public function getValorPago()
    {
        return $this->getParcelas()
            ->where(['status_parcela_codigo' => StatusParcela::PAGA])
            ->sum('valor_pago') ?: 0;
    }

    /**
     * Calcula valor pendente
     */
    public function getValorPendente()
    {
        return $this->valor_total - $this->getValorPago();
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getCliente()
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getVendedor()
    {
        return $this->hasOne(Colaborador::class, ['id' => 'colaborador_vendedor_id']);
    }

    public function getStatusVenda()
    {
        return $this->hasOne(StatusVenda::class, ['codigo' => 'status_venda_codigo']);
    }

    public function getItens()
    {
        return $this->hasMany(VendaItem::class, ['venda_id' => 'id']);
    }

    public function getParcelas()
    {
        return $this->hasMany(Parcela::class, ['venda_id' => 'id'])
            ->orderBy(['numero_parcela' => SORT_ASC]);
    }
}