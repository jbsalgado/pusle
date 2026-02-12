<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;

/**
 * ============================================================================================================
 * Model: Compra
 * ============================================================================================================
 * Tabela: prest_compras
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $fornecedor_id
 * @property string $numero_nota_fiscal
 * @property string $serie_nota_fiscal
 * @property string $data_compra
 * @property string $data_vencimento
 * @property float $valor_total
 * @property float $valor_frete
 * @property float $valor_desconto
 * @property string $forma_pagamento
 * @property string $status_compra
 * @property string $observacoes
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Usuario $usuario
 * @property Fornecedor $fornecedor
 * @property ItemCompra[] $itens
 */
class Compra extends ActiveRecord
{
    const STATUS_PENDENTE = 'PENDENTE';
    const STATUS_CONCLUIDA = 'CONCLUIDA';
    const STATUS_CANCELADA = 'CANCELADA';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_compras';
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
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id', 'fornecedor_id', 'data_compra'], 'required'],
            [['usuario_id', 'fornecedor_id'], 'string'],
            [['numero_nota_fiscal'], 'string', 'max' => 50],
            [['serie_nota_fiscal'], 'string', 'max' => 10],
            [['data_compra', 'data_vencimento'], 'safe'],
            [['data_compra'], 'date', 'format' => 'php:Y-m-d'],
            [['data_vencimento'], 'date', 'format' => 'php:Y-m-d'],
            [['valor_total'], 'number', 'min' => 0],
            [['valor_frete', 'valor_desconto'], 'safe'],
            [['valor_total'], 'default', 'value' => 0],
            [['valor_frete'], 'default', 'value' => 0],
            [['valor_desconto'], 'default', 'value' => 0],
            [['num_parcelas'], 'integer', 'min' => 1, 'max' => 120],
            [['num_parcelas'], 'default', 'value' => 1],
            [['intervalo_parcelas'], 'integer', 'min' => 1, 'max' => 365],
            [['intervalo_parcelas'], 'default', 'value' => 30],
            [['forma_pagamento'], 'string', 'max' => 50],
            [['status_compra'], 'string', 'max' => 20],
            [['status_compra'], 'default', 'value' => self::STATUS_PENDENTE],
            [['status_compra'], 'in', 'range' => [self::STATUS_PENDENTE, self::STATUS_CONCLUIDA, self::STATUS_CANCELADA]],
            [['observacoes'], 'string'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['fornecedor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Fornecedor::class, 'targetAttribute' => ['fornecedor_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            // Converte formato BRL (1.234,56) para float (1234.56)
            foreach (['valor_frete', 'valor_desconto'] as $attribute) {
                if (!empty($this->$attribute) && is_string($this->$attribute)) {
                    $this->$attribute = str_replace(',', '.', str_replace('.', '', $this->$attribute));
                }
            }
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'fornecedor_id' => 'Fornecedor',
            'numero_nota_fiscal' => 'Número da Nota Fiscal',
            'serie_nota_fiscal' => 'Série da Nota Fiscal',
            'data_compra' => 'Data da Compra',
            'data_vencimento' => 'Data de Vencimento (1ª Parcela)',
            'valor_total' => 'Valor Total',
            'valor_frete' => 'Valor do Frete',
            'valor_desconto' => 'Valor do Desconto',
            'num_parcelas' => 'Número de Parcelas',
            'intervalo_parcelas' => 'Intervalo entre Parcelas (dias)',
            'forma_pagamento' => 'Forma de Pagamento',
            'status_compra' => 'Status',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Relacionamento com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Relacionamento com Fornecedor
     */
    public function getFornecedor()
    {
        return $this->hasOne(Fornecedor::class, ['id' => 'fornecedor_id']);
    }

    /**
     * Relacionamento com Itens de Compra
     */
    public function getItens()
    {
        return $this->hasMany(ItemCompra::class, ['compra_id' => 'id']);
    }

    /**
     * Retorna o valor líquido (total - desconto + frete)
     */
    public function getValorLiquido()
    {
        return $this->valor_total - $this->valor_desconto + $this->valor_frete;
    }

    /**
     * Retorna array de status disponíveis
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_CONCLUIDA => 'Concluída',
            self::STATUS_CANCELADA => 'Cancelada',
        ];
    }

    /**
     * Retorna o label do status
     */
    public function getStatusLabel()
    {
        $statusList = self::getStatusList();
        return $statusList[$this->status_compra] ?? $this->status_compra;
    }

    /**
     * Recalcula o valor total baseado nos itens
     */
    public function recalcularValorTotal()
    {
        $total = 0;
        foreach ($this->itens as $item) {
            $total += $item->valor_total_item;
        }
        $this->valor_total = $total;
        return $this->valor_total;
    }

    /**
     * Gera contas a pagar automaticamente baseado na compra
     * 
     * @param bool $regenerar Se true, remove contas existentes e gera novamente
     * @return array Array com as contas criadas ou erros
     */
    public function gerarContasPagar($regenerar = false)
    {
        // Importa o model ContaPagar
        $contaPagarClass = 'app\\modules\\contas_pagar\\models\\ContaPagar';
        if (!class_exists($contaPagarClass)) {
            return ['success' => false, 'message' => 'Módulo Contas a Pagar não encontrado'];
        }

        $resultado = [
            'success' => true,
            'contas_criadas' => 0,
            'contas' => [],
            'erros' => []
        ];

        // Se regenerar, remove contas existentes
        if ($regenerar) {
            $contasExistentes = $contaPagarClass::find()
                ->where(['compra_id' => $this->id])
                ->all();

            foreach ($contasExistentes as $conta) {
                if ($conta->status == 'PENDENTE') {
                    $conta->delete();
                }
            }
        }

        // Verifica se já existem contas para esta compra
        $contasExistentes = $contaPagarClass::find()
            ->where(['compra_id' => $this->id])
            ->count();

        if ($contasExistentes > 0 && !$regenerar) {
            return [
                'success' => false,
                'message' => 'Já existem contas a pagar para esta compra. Use $regenerar=true para recriar.'
            ];
        }

        // Calcula valor líquido (total - desconto + frete)
        $valorLiquido = $this->getValorLiquido();

        // Define número de parcelas (padrão: 1)
        $numParcelas = $this->num_parcelas ?? 1;
        $intervaloParcelas = $this->intervalo_parcelas ?? 30; // dias

        // Calcula valor de cada parcela
        $valorParcela = $valorLiquido / $numParcelas;

        // Data base para vencimento (usa data_vencimento ou data_compra + 30 dias)
        $dataBase = $this->data_vencimento ?? date('Y-m-d', strtotime($this->data_compra . ' +30 days'));

        // Gera as contas
        for ($i = 1; $i <= $numParcelas; $i++) {
            $conta = new $contaPagarClass();
            $conta->usuario_id = $this->usuario_id;
            $conta->fornecedor_id = $this->fornecedor_id;
            $conta->compra_id = $this->id;

            // Descrição da conta
            if ($numParcelas == 1) {
                $conta->descricao = sprintf(
                    'Compra NF %s - %s',
                    $this->numero_nota_fiscal ?? 'S/N',
                    $this->fornecedor->nome_fantasia ?? 'Fornecedor'
                );
            } else {
                $conta->descricao = sprintf(
                    'Compra NF %s - Parcela %d/%d - %s',
                    $this->numero_nota_fiscal ?? 'S/N',
                    $i,
                    $numParcelas,
                    $this->fornecedor->nome_fantasia ?? 'Fornecedor'
                );
            }

            $conta->valor = round($valorParcela, 2);

            // Calcula data de vencimento (primeira parcela usa dataBase, demais somam intervalo)
            if ($i == 1) {
                $conta->data_vencimento = $dataBase;
            } else {
                $diasAdicionar = ($i - 1) * $intervaloParcelas;
                $conta->data_vencimento = date('Y-m-d', strtotime($dataBase . " +{$diasAdicionar} days"));
            }

            $conta->status = 'PENDENTE';
            $conta->categoria = 'COMPRA';

            // Observações
            if ($this->observacoes) {
                $conta->observacoes = $this->observacoes;
            }

            if ($conta->save()) {
                $resultado['contas_criadas']++;
                $resultado['contas'][] = $conta;
            } else {
                $resultado['success'] = false;
                $resultado['erros'][] = [
                    'parcela' => $i,
                    'erros' => $conta->errors
                ];
            }
        }

        return $resultado;
    }

    /**
     * Antes de salvar, gera UUID e garante valores padrão
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Gera UUID se for um novo registro
            if ($insert && empty($this->id)) {
                $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                $this->id = $uuid;
            }

            // Garante valores padrão se não foram definidos
            if ($this->valor_total === null || $this->valor_total === '') {
                $this->valor_total = 0;
            }
            if ($this->valor_frete === null || $this->valor_frete === '') {
                $this->valor_frete = 0;
            }
            if ($this->valor_desconto === null || $this->valor_desconto === '') {
                $this->valor_desconto = 0;
            }

            // Recalcula valor total apenas na atualização se houver itens já salvos
            // Na criação, o controller faz o recálculo após salvar os itens
            if (!$insert && $this->itens && count($this->itens) > 0) {
                $this->recalcularValorTotal();
            }

            return true;
        }
        return false;
    }
}
