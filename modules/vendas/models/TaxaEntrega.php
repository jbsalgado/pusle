<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\models\Usuario;

/**
 * This is the model class for table "prest_taxas_entrega".
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $cidade
 * @property string $bairro
 * @property string $cep
 * @property float $valor
 * @property float|null $valor_minimo_frete_gratis
 * @property string|null $observacoes
 * @property bool $ativo
 * @property string $data_criacao
 * @property string $data_atualizacao
 */
class TaxaEntrega extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_taxas_entrega';
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
            [['usuario_id', 'valor'], 'required'],
            [['usuario_id', 'observacoes'], 'string'],
            [['valor', 'valor_minimo_frete_gratis', 'faixa_preco_min', 'faixa_preco_max'], 'number', 'min' => 0],
            [['prazo_dias_min', 'prazo_dias_max'], 'integer', 'min' => 0],
            [['ativo'], 'boolean'],
            [['cidade', 'bairro', 'nome_servico'], 'string', 'max' => 100],
            [['tipo_regra'], 'string', 'max' => 20],
            [['estado'], 'string', 'max' => 2],
            [['cep'], 'string', 'max' => 10],
            [['porte'], 'string', 'max' => 1],
            [['porte'], 'default', 'value' => 'P'],
            [['tipo_regra'], 'default', 'value' => 'ESTADO'],
            [['nome_servico'], 'default', 'value' => 'Entrega Padrão'],
            [['prazo_dias_min'], 'default', 'value' => 1],
            [['prazo_dias_max'], 'default', 'value' => 5],
            [['faixa_preco_min'], 'default', 'value' => 0.00],
            [['data_criacao', 'data_atualizacao'], 'safe'],
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
            'estado' => 'Estado (UF)',
            'cidade' => 'Cidade',
            'bairro' => 'Bairro',
            'cep' => 'CEP',
            'porte' => 'Porte/Volume',
            'nome_servico' => 'Nome do Serviço / Modalidade',
            'tipo_regra' => 'Tipo de Regra',
            'valor' => 'Valor do Frete (R$)',
            'faixa_preco_min' => 'Preço Mín. Pedido (R$)',
            'faixa_preco_max' => 'Preço Máx. Pedido (R$)',
            'prazo_dias_min' => 'Prazo Mínimo (Dias Úteis)',
            'prazo_dias_max' => 'Prazo Máximo (Dias Úteis)',
            'valor_minimo_frete_gratis' => 'Frete Grátis acima de (R$)',
            'observacoes' => 'Observações Internas',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data Criação',
            'data_atualizacao' => 'Data Atualização',
        ];
    }

    /**
     * Lista de Estados Brasileiros (UF)
     */
    public static function getEstadosList()
    {
        return [
            'AC' => 'Acre (AC)',
            'AL' => 'Alagoas (AL)',
            'AP' => 'Amapá (AP)',
            'AM' => 'Amazonas (AM)',
            'BA' => 'Bahia (BA)',
            'CE' => 'Ceará (CE)',
            'DF' => 'Distrito Federal (DF)',
            'ES' => 'Espírito Santo (ES)',
            'GO' => 'Goiás (GO)',
            'MA' => 'Maranhão (MA)',
            'MT' => 'Mato Grosso (MT)',
            'MS' => 'Mato Grosso do Sul (MS)',
            'MG' => 'Minas Gerais (MG)',
            'PA' => 'Pará (PA)',
            'PB' => 'Paraíba (PB)',
            'PR' => 'Paraná (PR)',
            'PE' => 'Pernambuco (PE)',
            'PI' => 'Piauí (PI)',
            'RJ' => 'Rio de Janeiro (RJ)',
            'RN' => 'Rio Grande do Norte (RN)',
            'RS' => 'Rio Grande do Sul (RS)',
            'RO' => 'Rondônia (RO)',
            'RR' => 'Roraima (RR)',
            'SC' => 'Santa Catarina (SC)',
            'SP' => 'São Paulo (SP)',
            'SE' => 'Sergipe (SE)',
            'TO' => 'Tocantins (TO)',
        ];
    }

    /**
     * Tabela de médias de mercado padrão por Estado (UF) para auto-preenchimento
     */
    public static function getTabelaPadraoEstados()
    {
        return [
            // Sudeste
            'SP' => ['valor' => 16.90, 'min' => 2, 'max' => 4, 'gratis' => 199.00],
            'RJ' => ['valor' => 22.90, 'min' => 3, 'max' => 5, 'gratis' => 249.00],
            'MG' => ['valor' => 24.90, 'min' => 3, 'max' => 6, 'gratis' => 249.00],
            'ES' => ['valor' => 26.90, 'min' => 3, 'max' => 6, 'gratis' => 249.00],
            // Sul
            'PR' => ['valor' => 24.90, 'min' => 3, 'max' => 6, 'gratis' => 279.00],
            'SC' => ['valor' => 26.90, 'min' => 3, 'max' => 6, 'gratis' => 279.00],
            'RS' => ['valor' => 28.90, 'min' => 4, 'max' => 7, 'gratis' => 279.00],
            // Centro-Oeste
            'DF' => ['valor' => 28.90, 'min' => 3, 'max' => 6, 'gratis' => 299.00],
            'GO' => ['valor' => 29.90, 'min' => 4, 'max' => 7, 'gratis' => 299.00],
            'MS' => ['valor' => 32.90, 'min' => 4, 'max' => 8, 'gratis' => 299.00],
            'MT' => ['valor' => 34.90, 'min' => 5, 'max' => 9, 'gratis' => 299.00],
            // Nordeste
            'BA' => ['valor' => 32.90, 'min' => 5, 'max' => 9, 'gratis' => 349.00],
            'PE' => ['valor' => 36.90, 'min' => 6, 'max' => 10, 'gratis' => 349.00],
            'CE' => ['valor' => 38.90, 'min' => 6, 'max' => 11, 'gratis' => 349.00],
            'PB' => ['valor' => 38.90, 'min' => 6, 'max' => 11, 'gratis' => 349.00],
            'RN' => ['valor' => 39.90, 'min' => 6, 'max' => 11, 'gratis' => 349.00],
            'AL' => ['valor' => 39.90, 'min' => 6, 'max' => 11, 'gratis' => 349.00],
            'SE' => ['valor' => 39.90, 'min' => 6, 'max' => 11, 'gratis' => 349.00],
            'MA' => ['valor' => 42.90, 'min' => 7, 'max' => 12, 'gratis' => 349.00],
            'PI' => ['valor' => 42.90, 'min' => 7, 'max' => 12, 'gratis' => 349.00],
            // Norte
            'PA' => ['valor' => 44.90, 'min' => 7, 'max' => 14, 'gratis' => 399.00],
            'AM' => ['valor' => 48.90, 'min' => 8, 'max' => 16, 'gratis' => 399.00],
            'TO' => ['valor' => 38.90, 'min' => 6, 'max' => 11, 'gratis' => 349.00],
            'RO' => ['valor' => 46.90, 'min' => 8, 'max' => 15, 'gratis' => 399.00],
            'AC' => ['valor' => 52.90, 'min' => 9, 'max' => 18, 'gratis' => 449.00],
            'AP' => ['valor' => 52.90, 'min' => 9, 'max' => 18, 'gratis' => 449.00],
            'RR' => ['valor' => 56.90, 'min' => 10, 'max' => 20, 'gratis' => 449.00],
        ];
    }

    /**
     * Busca a regra de taxa de entrega mais específica.
     * Prioridade: CEP > Bairro+Cidade > Bairro > Cidade > Estado (com faixa de preço) > Estado geral > Nacional.
     * 
     * @return self|null
     */
    public static function findRegra($usuarioId, $cidade = null, $bairro = null, $cep = null, $porte = 'P', $estado = null, $subtotal = 0.0)
    {
        $baseQuery = self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true]);

        if ($porte) {
            $baseQuery->andWhere(['porte' => [$porte, 'P', null]]);
        }

        $subtotal = (float)$subtotal;

        // 1. Busca por CEP exato
        if ($cep) {
            $limpoCep = preg_replace('/[^0-9]/', '', $cep);
            $regra = (clone $baseQuery)
                ->andWhere(['cep' => [$cep, $limpoCep]])
                ->one();
            if ($regra) return $regra;
        }

        // 2. Busca por Bairro + Cidade
        if ($bairro && $cidade) {
            $regra = (clone $baseQuery)
                ->andWhere(['ilike', 'bairro', trim($bairro)])
                ->andWhere(['ilike', 'cidade', trim($cidade)])
                ->one();
            if ($regra) return $regra;
        }

        // 3. Busca por Bairro apenas
        if ($bairro) {
            $regra = (clone $baseQuery)
                ->andWhere(['ilike', 'bairro', trim($bairro)])
                ->one();
            if ($regra) return $regra;
        }

        // 4. Busca por Cidade apenas
        if ($cidade) {
            $regra = (clone $baseQuery)
                ->andWhere(['ilike', 'cidade', trim($cidade)])
                ->one();
            if ($regra) return $regra;
        }

        // 5. Busca por Estado (UF) considerando faixa de preço do pedido
        if ($estado) {
            $uf = strtoupper(trim($estado));
            
            // 5a. Regra do Estado com faixa de preço correspondente
            if ($subtotal > 0) {
                $regraFaixa = (clone $baseQuery)
                    ->andWhere(['estado' => $uf])
                    ->andWhere(['<=', 'faixa_preco_min', $subtotal])
                    ->andWhere([
                        'OR',
                        ['>=', 'faixa_preco_max', $subtotal],
                        ['faixa_preco_max' => null]
                    ])
                    ->orderBy(['faixa_preco_min' => SORT_DESC])
                    ->one();
                if ($regraFaixa) return $regraFaixa;
            }

            // 5b. Regra geral do Estado (sem faixa restritiva)
            $regraEstado = (clone $baseQuery)
                ->andWhere(['estado' => $uf])
                ->one();
            if ($regraEstado) return $regraEstado;
        }

        // 6. Regra padrão geral / nacional (sem cidade e sem estado definidos)
        $regraGeral = (clone $baseQuery)
            ->andWhere(['estado' => null, 'cidade' => null, 'bairro' => null, 'cep' => null])
            ->one();
        if ($regraGeral) return $regraGeral;

        return null; 
    }

    /**
     * Retorna todas as opções de entrega aplicáveis para o local e carrinho.
     * 
     * @return array
     */
    public static function findOpcoesEntrega($usuarioId, $cidade = null, $bairro = null, $cep = null, $porte = 'P', $estado = null, $subtotal = 0.0)
    {
        $opcoes = [];
        $regra = self::findRegra($usuarioId, $cidade, $bairro, $cep, $porte, $estado, $subtotal);

        if ($regra) {
            $valorFinal = (float)$regra->valor;
            $isGratis = false;

            $avisoPromocional = null;
            $motivoGratis = null;
            $economia = 0.00;
            $faltaParaGratis = null;

            if ($regra->valor_minimo_frete_gratis) {
                $minGratis = (float)$regra->valor_minimo_frete_gratis;
                if ($subtotal >= $minGratis) {
                    $valorFinal = 0.00;
                    $isGratis = true;
                    $economia = (float)$regra->valor;
                    $motivoGratis = "Frete Grátis por valor de compra (pedidos acima de R$ " . number_format($minGratis, 2, ',', '.') . ")";
                    $avisoPromocional = "🎉 Frete Grátis por valor de compra • Economizou R$ " . number_format($economia, 2, ',', '.');
                } else {
                    $faltaParaGratis = $minGratis - $subtotal;
                    $avisoPromocional = "Faltam R$ " . number_format($faltaParaGratis, 2, ',', '.') . " para ganhar Frete Grátis!";
                }
            }

            $prazoTexto = $regra->prazo_dias_min == $regra->prazo_dias_max 
                ? "{$regra->prazo_dias_min} dias úteis" 
                : "{$regra->prazo_dias_min} a {$regra->prazo_dias_max} dias úteis";

            $opcoes[] = [
                'id' => 'interno_' . $regra->id,
                'servico' => $regra->nome_servico ?: 'Entrega Padrão',
                'transportadora' => 'Loja',
                'valor' => $valorFinal,
                'valor_original' => (float)$regra->valor,
                'gratis' => $isGratis,
                'valor_minimo_frete_gratis' => $regra->valor_minimo_frete_gratis ? (float)$regra->valor_minimo_frete_gratis : null,
                'falta_para_frete_gratis' => $faltaParaGratis,
                'aviso_promocional' => $avisoPromocional,
                'motivo_gratis' => $motivoGratis,
                'economia' => $economia,
                'prazo_dias_min' => (int)$regra->prazo_dias_min,
                'prazo_dias_max' => (int)$regra->prazo_dias_max,
                'prazo_descricao' => $prazoTexto,
                'tipo' => 'INTERNO',
            ];
        }

        // Opção padrão de Retirada na Loja (sempre gratuita)
        $opcoes[] = [
            'id' => 'retirada_loja',
            'servico' => 'Retirar na Loja',
            'transportadora' => 'Retirada no Balcão',
            'valor' => 0.00,
            'valor_original' => 0.00,
            'gratis' => true,
            'prazo_dias_min' => 0,
            'prazo_dias_max' => 1,
            'prazo_descricao' => 'Disponível após confirmação',
            'tipo' => 'RETIRADA',
        ];

        return $opcoes;
    }

    /**
     * Mantido para retrocompatibilidade
     */
    public static function findTaxa($usuarioId, $cidade = null, $bairro = null, $cep = null, $porte = 'P')
    {
        $regra = self::findRegra($usuarioId, $cidade, $bairro, $cep, $porte);
        return $regra ? (float)$regra->valor : 0.00;
    }
}
