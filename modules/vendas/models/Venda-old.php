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
use app\modules\vendas\models\RegraParcelamento;

/**
 * ============================================================================================================
 * Model: Venda
 * ============================================================================================================
 * Tabela: prest_vendas
 * @property string $id
 * @property string $usuario_id
 * @property string $cliente_id
 * @property string $colaborador_vendedor_id
 * @property string $data_venda
 * @property float $valor_total // Este é o VALOR BASE (soma dos itens)
 * @property integer $numero_parcelas
 * @property string $status_venda_codigo
 * @property string $observacoes
 * @property string $data_primeiro_vencimento
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
            [['data_venda','data_primeiro_vencimento'], 'safe'],
            [['data_primeiro_vencimento'], 'date', 'format' => 'php:Y-m-d'],
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
            'valor_total' => 'Valor Total (Base)',
            'numero_parcelas' => 'Número de Parcelas',
            'status_venda_codigo' => 'Status',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
            'data_primeiro_vencimento' => 'Data 1º Venc.',
        ];
    }

    // =========================================================================
    // === FUNÇÃO GERAR PARCELAS ATUALIZADA ====================================
    // =========================================================================
    /**
     * Gera parcelas da venda com lógica de acréscimo e data personalizável
     * 
     * @param int|null $formaPagamentoId ID da forma de pagamento
     * @param string|null $dataPrimeiroPagamento Data do primeiro vencimento (formato Y-m-d)
     *                                           Se null, usa data_primeiro_vencimento do modelo
     *                                           ou calcula +30 dias da data da venda
     * @return bool
     * @throws \yii\db\Exception
     */
    public function gerarParcelas($formaPagamentoId = null, $dataPrimeiroPagamento = null)
    {
        // Deleta parcelas existentes para esta venda
        Parcela::deleteAll(['venda_id' => $this->id]);

        if ($this->numero_parcelas <= 0) {
            $this->numero_parcelas = 1; // Garante pelo menos uma parcela
        }

        // --- INÍCIO DA LÓGICA DE ACRÉSCIMO ---
        $valorBaseTotal = (float)$this->valor_total; // Valor original sem acréscimo
        $valorTotalAPrazo = $valorBaseTotal;        // Assume 0% acréscimo por padrão (para 1x)
        $percentualAcrescimo = 0;

        // Se for mais de 1 parcela, busca a regra de acréscimo na nova tabela
        if ($this->numero_parcelas > 1) {
            $regra = RegraParcelamento::find()
                ->where(['usuario_id' => $this->usuario_id])
                ->andWhere(['<=', 'min_parcelas', $this->numero_parcelas])
                ->andWhere(['>=', 'max_parcelas', $this->numero_parcelas])
                ->one();

            if ($regra) {
                $percentualAcrescimo = (float)$regra->percentual_acrescimo;
                if ($percentualAcrescimo > 0) {
                    // Calcula o valor total com o acréscimo
                    $valorTotalAPrazo = $valorBaseTotal * (1 + ($percentualAcrescimo / 100));
                }
                Yii::info("Regra de parcelamento encontrada para {$this->numero_parcelas}x: {$percentualAcrescimo}%. Valor base: {$valorBaseTotal}, Valor a prazo: {$valorTotalAPrazo}", 'Venda');
            } else {
                 Yii::warning("Nenhuma regra de parcelamento encontrada para Venda ID {$this->id}, Usuario ID {$this->usuario_id} e {$this->numero_parcelas} parcelas. Usando valor base.", 'Venda');
                 // Mantém $valorTotalAPrazo = $valorBaseTotal
            }
        } else {
             Yii::info("Venda ID {$this->id} em 1 parcela. Sem acréscimo.", 'Venda');
        }

        // Calcula valor da parcela COM BASE NO VALOR TOTAL A PRAZO (que pode ter acréscimo)
        $valorParcelaBase = ($this->numero_parcelas > 0) ? ($valorTotalAPrazo / $this->numero_parcelas) : $valorTotalAPrazo;
        // --- FIM DA LÓGICA DE ACRÉSCIMO ---

        $valorParcelaArredondado = round($valorParcelaBase, 2);
        
        // --- LÓGICA ATUALIZADA DE DATA DO PRIMEIRO VENCIMENTO ---
        // Prioridade:
        // 1. Parâmetro $dataPrimeiroPagamento passado para o método
        // 2. Campo data_primeiro_vencimento do modelo (se preenchido)
        // 3. Data da venda + 30 dias (para parcelas > 1) ou data da venda (para parcela única)
        
        if ($dataPrimeiroPagamento !== null) {
            // Se foi passado como parâmetro, usa essa data
            $dataVencimento = new \DateTime($dataPrimeiroPagamento);
            Yii::info("Usando data do primeiro pagamento informada: {$dataPrimeiroPagamento}", 'Venda');
        } elseif (!empty($this->data_primeiro_vencimento)) {
            // Se tem no modelo, usa essa
            $dataVencimento = new \DateTime($this->data_primeiro_vencimento);
            Yii::info("Usando data_primeiro_vencimento do modelo: {$this->data_primeiro_vencimento}", 'Venda');
        } else {
            // Senão, usa a data da venda
            $dataVencimento = new \DateTime($this->data_venda ?: 'now');
            
            // Se tiver mais de 1 parcela, a primeira vence em +30 dias
            if ($this->numero_parcelas > 1) {
                $dataVencimento->modify('+30 days');
                Yii::info("Usando data da venda + 30 dias para primeira parcela", 'Venda');
            } else {
                Yii::info("Venda à vista - usando data da venda como vencimento", 'Venda');
            }
        }
        // --- FIM DA LÓGICA DE DATA ---
        
        $valorTotalGerado = 0;

        for ($i = 1; $i <= $this->numero_parcelas; $i++) {
            $parcela = new Parcela();
            $parcela->venda_id = $this->id;
            $parcela->usuario_id = $this->usuario_id; // Garante que usuario_id está na parcela
            $parcela->numero_parcela = $i;

            // Ajuste para a última parcela (evita problemas de arredondamento)
            // Usa o VALOR TOTAL A PRAZO para o cálculo final
            if ($i == $this->numero_parcelas) {
                $parcela->valor_parcela = $valorTotalAPrazo - $valorTotalGerado;
            } else {
                $parcela->valor_parcela = $valorParcelaArredondado;
                $valorTotalGerado += $valorParcelaArredondado;
            }

            // --- Lógica de Vencimento das Parcelas Subsequentes ---
            // A primeira parcela já tem a data definida acima
            // As demais vencem +30 dias após a anterior
            if ($i > 1) {
                $dataVencimento->modify('+30 days');
            }

            $parcela->data_vencimento = $dataVencimento->format('Y-m-d');
            $parcela->status_parcela_codigo = StatusParcela::PENDENTE;
            $parcela->forma_pagamento_id = $formaPagamentoId; // Define a forma de pagamento

            if (!$parcela->save()) {
                 Yii::error("Erro ao salvar parcela {$i} para venda {$this->id}: " . print_r($parcela->errors, true), 'Venda');
                 throw new \yii\db\Exception("Não foi possível salvar a parcela {$i}.");
            }
            
            Yii::info("Parcela {$i}/{$this->numero_parcelas} gerada: R$ {$parcela->valor_parcela}, vencimento: {$parcela->data_vencimento}", 'Venda');
        }
        
        // IMPORTANTE: Não alteramos $this->valor_total aqui. Ele permanece o valor base.
        // A soma das $parcela->valor_parcela refletirá o valor total a prazo.
        
        Yii::info("Geração de parcelas concluída para Venda ID {$this->id}. Total de parcelas: {$this->numero_parcelas}", 'Venda');
        return true;
    }
    // =========================================================================
    // === FIM DA FUNÇÃO GERAR PARCELAS ATUALIZADA =============================
    // =========================================================================


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
     * Calcula o valor total pendente SOMANDO as parcelas pendentes
     * É mais preciso que (Valor Total Base - Valor Pago) quando há juros.
     */
    public function getValorPendente()
    {
        return $this->getParcelas()
            ->where(['status_parcela_codigo' => StatusParcela::PENDENTE])
            ->sum('valor_parcela') ?: 0;
    }
    
    /**
     * Retorna o valor total real da venda (soma das parcelas),
     * que pode incluir acréscimos.
     */
    public function getValorTotalAPrazo()
    {
        // Se já tiver carregado as parcelas, soma elas para evitar nova query
        if ($this->isRelationPopulated('parcelas')) {
            $total = 0;
            foreach ($this->parcelas as $parcela) {
                $total += $parcela->valor_parcela;
            }
            return $total;
        } else {
            // Senão, faz a query para somar
            return $this->getParcelas()->sum('valor_parcela') ?: $this->valor_total; // Fallback para valor_total se não houver parcelas
        }
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