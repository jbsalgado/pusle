<?php

namespace app\modules\indicadores\models;

use app\modules\indicadores\models\IndDimensoesIndicadores;
use app\modules\indicadores\models\IndFontesDados;
use app\modules\indicadores\models\IndPeriodicidades;
use app\modules\indicadores\models\IndUnidadesMedida;
use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ind_definicoes_indicadores".
 *
 * @property int $id_indicador
 * @property string|null $cod_indicador
 * @property string $nome_indicador
 * @property string $descricao_completa
 * @property string|null $conceito
 * @property string|null $justificativa
 * @property string|null $metodo_calculo
 * @property string|null $interpretacao
 * @property string|null $limitacoes
 * @property string|null $observacoes_gerais
 * @property int|null $id_dimensao
 * @property int $id_unidade_medida
 * @property int|null $id_periodicidade_ideal_medicao
 * @property int|null $id_periodicidade_ideal_divulgacao
 * @property int|null $id_fonte_padrao
 * @property string|null $tipo_especifico
 * @property string|null $polaridade
 * @property string|null $data_inicio_validade
 * @property string|null $data_fim_validade
 * @property string|null $responsavel_tecnico
 * @property string|null $nota_tecnica_url
 * @property string|null $palavras_chave
 * @property int|null $versao
 * @property bool|null $ativo
 * @property string|null $data_criacao
 * @property string|null $data_atualizacao
 * @property string|null $descricao_numerador
 * @property string|null $descricao_denominador
 */
class IndDefinicoesIndicadores extends ActiveRecord
{
    const TIPO_ESPECIFICO_OUTRO = 'OUTRO';
    const TIPO_ESPECIFICO_ESTRUTURA = 'ESTRUTURA';
    const TIPO_ESPECIFICO_PROCESSO = 'PROCESSO';
    const TIPO_ESPECIFICO_RESULTADO = 'RESULTADO';
    const TIPO_ESPECIFICO_IMPACTO = 'IMPACTO';

    const POLARIDADE_MAIOR_MELHOR = 'QUANTO_MAIOR_MELHOR';
    const POLARIDADE_MENOR_MELHOR = 'QUANTO_MENOR_MELHOR';
    const POLARIDADE_FAIXA_MELHOR = 'DENTRO_DA_FAIXA_MELHOR';
    const POLARIDADE_NEUTRO = 'NEUTRO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_definicoes_indicadores';
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
            [['nome_indicador', 'descricao_completa', 'id_unidade_medida'], 'required'],
            [['descricao_completa', 'conceito', 'justificativa', 'metodo_calculo', 'interpretacao', 'limitacoes', 'observacoes_gerais', 'palavras_chave', 'descricao_numerador', 'descricao_denominador'], 'string'],
            [['id_dimensao', 'id_unidade_medida', 'id_periodicidade_ideal_medicao', 'id_periodicidade_ideal_divulgacao', 'id_fonte_padrao', 'versao'], 'integer'],
            [['data_inicio_validade', 'data_fim_validade'], 'safe'],
            [['ativo'], 'boolean'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['cod_indicador'], 'string', 'max' => 50],
            [['nome_indicador'], 'string', 'max' => 512],
            [['polaridade'], 'string', 'max' => 50],
            [['responsavel_tecnico'], 'string', 'max' => 255],
            [['nota_tecnica_url'], 'string', 'max' => 512],
            [['cod_indicador'], 'unique'],
            [['polaridade'], 'in', 'range' => [
                self::POLARIDADE_MAIOR_MELHOR,
                self::POLARIDADE_MENOR_MELHOR,
                self::POLARIDADE_FAIXA_MELHOR,
                self::POLARIDADE_NEUTRO
            ]],
            [['tipo_especifico'], 'in', 'range' => [
                self::TIPO_ESPECIFICO_OUTRO,
                self::TIPO_ESPECIFICO_ESTRUTURA,
                self::TIPO_ESPECIFICO_PROCESSO,
                self::TIPO_ESPECIFICO_RESULTADO,
                self::TIPO_ESPECIFICO_IMPACTO
            ]],
            [['nota_tecnica_url'], 'url'],
            [['versao'], 'default', 'value' => 1],
            [['ativo'], 'default', 'value' => true],
            [['data_inicio_validade'], 'default', 'value' => date('Y-m-d')],
            [['tipo_especifico'], 'default', 'value' => self::TIPO_ESPECIFICO_OUTRO],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_indicador' => 'ID',
            'cod_indicador' => 'Código do Indicador',
            'nome_indicador' => 'Nome do Indicador',
            'descricao_completa' => 'Descrição Completa',
            'conceito' => 'Conceito',
            'justificativa' => 'Justificativa',
            'metodo_calculo' => 'Método de Cálculo',
            'interpretacao' => 'Interpretação',
            'limitacoes' => 'Limitações',
            'observacoes_gerais' => 'Observações Gerais',
            'id_dimensao' => 'Dimensão',
            'id_unidade_medida' => 'Unidade de Medida',
            'id_periodicidade_ideal_medicao' => 'Periodicidade Ideal de Medição',
            'id_periodicidade_ideal_divulgacao' => 'Periodicidade Ideal de Divulgação',
            'id_fonte_padrao' => 'Fonte Padrão',
            'tipo_especifico' => 'Tipo Específico',
            'polaridade' => 'Polaridade',
            'data_inicio_validade' => 'Data de Início de Validade',
            'data_fim_validade' => 'Data de Fim de Validade',
            'responsavel_tecnico' => 'Responsável Técnico',
            'nota_tecnica_url' => 'URL da Nota Técnica',
            'palavras_chave' => 'Palavras-chave',
            'versao' => 'Versão',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
            'descricao_numerador' => 'Descrição do Numerador',
            'descricao_denominador' => 'Descrição do Denominador',
        ];
    }

    /**
     * Gets query for [[Dimensao]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDimensao()
    {
        return $this->hasOne(IndDimensoesIndicadores::class, ['id_dimensao' => 'id_dimensao']);
    }

    /**
     * Gets query for [[UnidadeMedida]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUnidadeMedida()
    {
        return $this->hasOne(IndUnidadesMedida::class, ['id_unidade' => 'id_unidade_medida']);
    }

    /**
     * Gets query for [[PeriodicidadeMedicao]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPeriodicidadeMedicao()
    {
        return $this->hasOne(IndPeriodicidades::class, ['id_periodicidade' => 'id_periodicidade_ideal_medicao']);
    }

    /**
     * Gets query for [[PeriodicidadeDivulgacao]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPeriodicidadeDivulgacao()
    {
        return $this->hasOne(IndPeriodicidades::class, ['id_periodicidade' => 'id_periodicidade_ideal_divulgacao']);
    }

    /**
     * Gets query for [[FontePadrao]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFontePadrao()
    {
        return $this->hasOne(IndFontesDados::class, ['id_fonte' => 'id_fonte_padrao']);
    }

    /**
     * Lista de opções para tipo específico
     */
    public static function getTipoEspecificoOptions()
    {
        return [
            self::TIPO_ESPECIFICO_OUTRO => 'Outro',
            self::TIPO_ESPECIFICO_ESTRUTURA => 'Estrutura',
            self::TIPO_ESPECIFICO_PROCESSO => 'Processo',
            self::TIPO_ESPECIFICO_RESULTADO => 'Resultado',
            self::TIPO_ESPECIFICO_IMPACTO => 'Impacto',
        ];
    }

    /**
     * Lista de opções para polaridade
     */
    public static function getPolaridadeOptions()
    {
        return [
            self::POLARIDADE_MAIOR_MELHOR => 'Quanto Maior Melhor',
            self::POLARIDADE_MENOR_MELHOR => 'Quanto Menor Melhor',
            self::POLARIDADE_FAIXA_MELHOR => 'Dentro da Faixa Melhor',
            self::POLARIDADE_NEUTRO => 'Neutro',
        ];
    }

    /**
     * Lista de opções para status ativo
     */
    public static function getAtivoOptions()
    {
        return [
            1 => 'Ativo',
            0 => 'Inativo',
        ];
    }

    /**
     * Retorna um array com as opções de Dimensões para filtros e formulários.
     * As dimensões são buscadas da tabela relacionada 'ind_dimensoes_indicadores'.
     *
     * @return array
     */
    public static function getDimensaoOptions()
    {
        // Busca todas as dimensões, ordenadas pelo nome
        $dimensoes = IndDimensoesIndicadores::find()
            ->orderBy(['nome_dimensao' => SORT_ASC])
            ->asArray()
            ->all();
        
        // Mapeia o resultado para um array no formato [id => nome]
        return ArrayHelper::map($dimensoes, 'id_dimensao', 'nome_dimensao');
    }
}