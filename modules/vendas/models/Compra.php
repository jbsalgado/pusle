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
 * @property string $chave_acesso
 * @property string $data_compra
 * @property string $data_vencimento
 * @property float $valor_produtos
 * @property float $valor_frete
 * @property float $valor_seguro
 * @property float $valor_outras_despesas
 * @property float $valor_desconto
 * @property float $valor_ipi
 * @property float $valor_icms_st
 * @property float $valor_fcp_st
 * @property float $valor_icms
 * @property float $valor_base_icms
 * @property float $valor_pis
 * @property float $valor_cofins
 * @property float $valor_total
 * @property string $forma_pagamento
 * @property string $status_compra
 * @property string $observacoes
 * @property boolean $com_nota
 * @property integer $num_parcelas
 * @property integer $intervalo_parcelas
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
    public function rules()
    {
        return [
            [['usuario_id', 'fornecedor_id', 'data_compra'], 'required'],
            [['usuario_id', 'fornecedor_id'], 'string'],
            [['numero_nota_fiscal'], 'string', 'max' => 50],
            [['serie_nota_fiscal'], 'string', 'max' => 10],
            [['chave_acesso'], 'string', 'max' => 44],
            [['data_compra', 'data_vencimento', 'com_nota'], 'safe'],
            [['data_compra'], 'date', 'format' => 'php:Y-m-d'],
            [['data_vencimento'], 'date', 'format' => 'php:Y-m-d'],
            [['com_nota'], 'boolean'],
            [['com_nota'], 'default', 'value' => false],
            [[
                'valor_total', 'valor_produtos', 'valor_frete', 'valor_seguro',
                'valor_outras_despesas', 'valor_desconto', 'valor_ipi', 'valor_icms_st',
                'valor_fcp_st', 'valor_icms', 'valor_base_icms', 'valor_pis', 'valor_cofins'
            ], 'safe'],
            [[
                'valor_total', 'valor_produtos', 'valor_frete', 'valor_seguro',
                'valor_outras_despesas', 'valor_desconto', 'valor_ipi', 'valor_icms_st',
                'valor_fcp_st', 'valor_icms', 'valor_base_icms', 'valor_pis', 'valor_cofins'
            ], 'default', 'value' => 0],
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
            $currencyAttributes = [
                'valor_total', 'valor_produtos', 'valor_frete', 'valor_seguro',
                'valor_outras_despesas', 'valor_desconto', 'valor_ipi', 'valor_icms_st',
                'valor_fcp_st', 'valor_icms', 'valor_base_icms', 'valor_pis', 'valor_cofins'
            ];
            foreach ($currencyAttributes as $attribute) {
                if ($this->$attribute !== null && $this->$attribute !== '') {
                    $this->$attribute = ItemCompra::parseDecimal($this->$attribute);
                } else {
                    $this->$attribute = 0;
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
            'chave_acesso' => 'Chave de Acesso da NF-e',
            'data_compra' => 'Data da Compra',
            'data_vencimento' => 'Data de Vencimento (1ª Parcela)',
            'valor_produtos' => 'Subtotal dos Produtos',
            'valor_frete' => 'Valor do Frete',
            'valor_seguro' => 'Valor do Seguro',
            'valor_outras_despesas' => 'Outras Despesas Acessórias',
            'valor_desconto' => 'Valor do Desconto',
            'valor_ipi' => 'Valor do IPI',
            'valor_icms_st' => 'Valor do ICMS ST',
            'valor_fcp_st' => 'Valor do FCP ST',
            'valor_icms' => 'Valor do ICMS',
            'valor_base_icms' => 'Base de Cálculo ICMS',
            'valor_pis' => 'Valor do PIS',
            'valor_cofins' => 'Valor do COFINS',
            'valor_total' => 'Total da Nota Fiscal',
            'num_parcelas' => 'Número de Parcelas',
            'intervalo_parcelas' => 'Intervalo entre Parcelas (dias)',
            'forma_pagamento' => 'Forma de Pagamento',
            'status_compra' => 'Status',
            'observacoes' => 'Observações',
            'com_nota' => 'Com Nota Fiscal',
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
     * Retorna o valor líquido total da nota fiscal (vNF)
     */
    public function getValorLiquido()
    {
        if ($this->valor_total !== null && (float)$this->valor_total > 0) {
            return (float)$this->valor_total;
        }

        // Fallback: calcula pela fórmula completa
        $produtos = (float)($this->valor_produtos ?? 0);
        $frete = (float)($this->valor_frete ?? 0);
        $seguro = (float)($this->valor_seguro ?? 0);
        $outras = (float)($this->valor_outras_despesas ?? 0);
        $ipi = (float)($this->valor_ipi ?? 0);
        $icmsSt = (float)($this->valor_icms_st ?? 0);
        $fcpSt = (float)($this->valor_fcp_st ?? 0);
        $desconto = (float)($this->valor_desconto ?? 0);

        return round(max(0, $produtos + $frete + $seguro + $outras + $ipi + $icmsSt + $fcpSt - $desconto), 2);
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
     * Recalcula o valor total da nota fiscal baseado nos itens e nos valores adicionais/impostos:
     * Total da Nota = (Soma dos Itens) + Frete + Seguro + Outras Despesas + IPI + ICMS ST + FCP ST - Desconto
     */
    public function recalcularValorTotal()
    {
        $totalProdutos = 0;
        if ($this->itens) {
            foreach ($this->itens as $item) {
                $totalProdutos += (float)$item->valor_total_item;
            }
        }
        $this->valor_produtos = round($totalProdutos, 2);

        $frete = (float)($this->valor_frete ?? 0);
        $seguro = (float)($this->valor_seguro ?? 0);
        $outras = (float)($this->valor_outras_despesas ?? 0);
        $ipi = (float)($this->valor_ipi ?? 0);
        $icmsSt = (float)($this->valor_icms_st ?? 0);
        $fcpSt = (float)($this->valor_fcp_st ?? 0);
        $desconto = (float)($this->valor_desconto ?? 0);

        $totalNota = $this->valor_produtos + $frete + $seguro + $outras + $ipi + $icmsSt + $fcpSt - $desconto;
        if ($totalNota < 0) {
            $totalNota = 0;
        }

        $this->valor_total = round($totalNota, 2);
        return $this->valor_total;
    }

    /**
     * Gera contas a pagar automaticamente baseado na compra
     * 
     * @param bool $regenerar Se true, remove contas existentes e gera novamente
     * @return array Array com as contas criadas ou erros
     */
    public function gerarContasPagar($regenerar = false, $parcelasManuais = [])
    {
        // Importa o model ContaPagar e TipoDespesa
        $contaPagarClass = 'app\\modules\\contas_pagar\\models\\ContaPagar';
        $tipoDespesaClass = 'app\\modules\\contas_pagar\\models\\TipoDespesa';
        
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

        // Localiza ou cria o tipo de despesa default para compras (MERCADORIA)
        $tipoDespesaId = null;
        if (class_exists($tipoDespesaClass)) {
            $tipoDespesa = $tipoDespesaClass::find()
                ->where(['usuario_id' => $this->usuario_id, 'grupo' => $tipoDespesaClass::GRUPO_MERCADORIA, 'ativo' => true])
                ->one();
            
            if (!$tipoDespesa) {
                $tipoDespesa = new $tipoDespesaClass();
                $tipoDespesa->usuario_id = $this->usuario_id;
                $tipoDespesa->nome = 'Compra de Mercadoria';
                $tipoDespesa->grupo = $tipoDespesaClass::GRUPO_MERCADORIA;
                $tipoDespesa->ativo = true;
                $tipoDespesa->descricao = 'Gerado automaticamente para compras de mercadorias';
                if ($tipoDespesa->save()) {
                    $tipoDespesaId = $tipoDespesa->id;
                } else {
                    Yii::error('Erro ao auto-criar TipoDespesa para compras: ' . json_encode($tipoDespesa->errors), __METHOD__);
                }
            } else {
                $tipoDespesaId = $tipoDespesa->id;
            }
        }

        // Caso tenhamos parcelas manuais especificadas
        if (!empty($parcelasManuais) && is_array($parcelasManuais)) {
            $numParcelas = count($parcelasManuais);
            
            foreach ($parcelasManuais as $i => $pData) {
                $idx = $i + 1;
                $conta = new $contaPagarClass();
                $conta->usuario_id = $this->usuario_id;
                $conta->fornecedor_id = $this->fornecedor_id;
                $conta->compra_id = $this->id;

                if ($tipoDespesaId) {
                    $conta->tipo_despesa_id = $tipoDespesaId;
                }

                $conta->descricao = sprintf(
                    'Compra NF %s - Parcela %d/%d - %s',
                    $this->numero_nota_fiscal ?? 'S/N',
                    $idx,
                    $numParcelas,
                    $this->fornecedor->nome_fantasia ?? 'Fornecedor'
                );

                // Converte formato BRL se necessário
                $val = $pData['valor'];
                if (is_string($val) && strpos($val, ',') !== false) {
                    $val = str_replace(',', '.', str_replace('.', '', $val));
                }
                $conta->valor = round((float)$val, 2);
                $conta->data_vencimento = $pData['data_vencimento'];
                $conta->status = 'PENDENTE';

                if ($this->observacoes) {
                    $conta->observacoes = $this->observacoes;
                }

                if ($conta->save()) {
                    $resultado['contas_criadas']++;
                    $resultado['contas'][] = $conta;
                } else {
                    $resultado['success'] = false;
                    $resultado['erros'][] = [
                        'parcela' => $idx,
                        'erros' => $conta->errors
                    ];
                }
            }
            return $resultado;
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

            if ($tipoDespesaId) {
                $conta->tipo_despesa_id = $tipoDespesaId;
            }

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
            $numericDefaults = [
                'valor_total', 'valor_produtos', 'valor_frete', 'valor_seguro',
                'valor_outras_despesas', 'valor_desconto', 'valor_ipi', 'valor_icms_st',
                'valor_fcp_st', 'valor_icms', 'valor_base_icms', 'valor_pis', 'valor_cofins'
            ];
            foreach ($numericDefaults as $field) {
                if ($this->$field === null || $this->$field === '') {
                    $this->$field = 0;
                }
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
