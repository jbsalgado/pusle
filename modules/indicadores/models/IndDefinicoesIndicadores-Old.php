<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_definicoes_indicadores".
 *
 * @property int $id_indicador
 * @property string $cod_indicador
 * @property string $nome_indicador
 * @property string $descricao_completa
 * @property string $conceito
 * @property string $justificativa
 * @property string $metodo_calculo
 * @property string $interpretacao
 * @property string $limitacoes
 * @property string $observacoes_gerais
 * @property int $id_dimensao
 * @property int $id_unidade_medida
 * @property int $id_periodicidade_ideal_medicao
 * @property int $id_periodicidade_ideal_divulgacao
 * @property int $id_fonte_padrao
 * @property string $tipo_especifico
 * @property string $polaridade Indica a direção desejável do valor do indicador para melhor desempenho.
 * @property string $data_inicio_validade
 * @property string $data_fim_validade
 * @property string $responsavel_tecnico
 * @property string $nota_tecnica_url
 * @property string $palavras_chave
 * @property int $versao Controla versões da ficha técnica do indicador caso haja mudanças metodológicas.
 * @property bool $ativo
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property IndAtributosQualidadeDesempenho $indAtributosQualidadeDesempenho
 * @property IndDimensoesIndicadores $dimensao
 * @property IndFontesDados $fontePadrao
 * @property IndPeriodicidades $periodicidadeIdealMedicao
 * @property IndPeriodicidades $periodicidadeIdealDivulgacao
 * @property IndUnidadesMedida $unidadeMedida
 * @property IndMetasIndicadores[] $indMetasIndicadores
 * @property IndRelacoesIndicadores[] $indRelacoesIndicadores
 * @property IndRelacoesIndicadores[] $indRelacoesIndicadores0
 * @property IndValoresIndicadores[] $indValoresIndicadores
 */
class IndDefinicoesIndicadoresOld extends \yii\db\ActiveRecord
{
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
    public function rules()
    {
        return [
            [['nome_indicador', 'descricao_completa', 'id_unidade_medida'], 'required'],
            [['descricao_completa', 'conceito', 'justificativa', 'metodo_calculo', 'interpretacao', 'limitacoes', 'observacoes_gerais', 'tipo_especifico', 'palavras_chave'], 'string'],
            [['id_dimensao', 'id_unidade_medida', 'id_periodicidade_ideal_medicao', 'id_periodicidade_ideal_divulgacao', 'id_fonte_padrao', 'versao'], 'default', 'value' => null],
            [['id_dimensao', 'id_unidade_medida', 'id_periodicidade_ideal_medicao', 'id_periodicidade_ideal_divulgacao', 'id_fonte_padrao', 'versao'], 'integer'],
            [['data_inicio_validade', 'data_fim_validade', 'data_criacao', 'data_atualizacao'], 'safe'],
            [['ativo'], 'boolean'],
            [['cod_indicador', 'polaridade'], 'string', 'max' => 50],
            [['nome_indicador', 'nota_tecnica_url'], 'string', 'max' => 512],
            [['responsavel_tecnico'], 'string', 'max' => 255],
            [['cod_indicador'], 'unique'],
            [['id_dimensao'], 'exist', 'skipOnError' => true, 'targetClass' => IndDimensoesIndicadores::className(), 'targetAttribute' => ['id_dimensao' => 'id_dimensao']],
            [['id_fonte_padrao'], 'exist', 'skipOnError' => true, 'targetClass' => IndFontesDados::className(), 'targetAttribute' => ['id_fonte_padrao' => 'id_fonte']],
            [['id_periodicidade_ideal_medicao'], 'exist', 'skipOnError' => true, 'targetClass' => IndPeriodicidades::className(), 'targetAttribute' => ['id_periodicidade_ideal_medicao' => 'id_periodicidade']],
            [['id_periodicidade_ideal_divulgacao'], 'exist', 'skipOnError' => true, 'targetClass' => IndPeriodicidades::className(), 'targetAttribute' => ['id_periodicidade_ideal_divulgacao' => 'id_periodicidade']],
            [['id_unidade_medida'], 'exist', 'skipOnError' => true, 'targetClass' => IndUnidadesMedida::className(), 'targetAttribute' => ['id_unidade_medida' => 'id_unidade']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_indicador' => 'Id Indicador',
            'cod_indicador' => 'Cod Indicador',
            'nome_indicador' => 'Nome Indicador',
            'descricao_completa' => 'Descricao Completa',
            'conceito' => 'Conceito',
            'justificativa' => 'Justificativa',
            'metodo_calculo' => 'Metodo Calculo',
            'interpretacao' => 'Interpretacao',
            'limitacoes' => 'Limitacoes',
            'observacoes_gerais' => 'Observacoes Gerais',
            'id_dimensao' => 'Id Dimensao',
            'id_unidade_medida' => 'Id Unidade Medida',
            'id_periodicidade_ideal_medicao' => 'Id Periodicidade Ideal Medicao',
            'id_periodicidade_ideal_divulgacao' => 'Id Periodicidade Ideal Divulgacao',
            'id_fonte_padrao' => 'Id Fonte Padrao',
            'tipo_especifico' => 'Tipo Especifico',
            'polaridade' => 'Polaridade',
            'data_inicio_validade' => 'Data Inicio Validade',
            'data_fim_validade' => 'Data Fim Validade',
            'responsavel_tecnico' => 'Responsavel Tecnico',
            'nota_tecnica_url' => 'Nota Tecnica Url',
            'palavras_chave' => 'Palavras Chave',
            'versao' => 'Versao',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndAtributosQualidadeDesempenho()
    {
        return $this->hasOne(IndAtributosQualidadeDesempenho::className(), ['id_indicador' => 'id_indicador']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDimensao()
    {
        return $this->hasOne(IndDimensoesIndicadores::className(), ['id_dimensao' => 'id_dimensao']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFontePadrao()
    {
        return $this->hasOne(IndFontesDados::className(), ['id_fonte' => 'id_fonte_padrao']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPeriodicidadeIdealMedicao()
    {
        return $this->hasOne(IndPeriodicidades::className(), ['id_periodicidade' => 'id_periodicidade_ideal_medicao']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPeriodicidadeIdealDivulgacao()
    {
        return $this->hasOne(IndPeriodicidades::className(), ['id_periodicidade' => 'id_periodicidade_ideal_divulgacao']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUnidadeMedida()
    {
        return $this->hasOne(IndUnidadesMedida::className(), ['id_unidade' => 'id_unidade_medida']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndMetasIndicadores()
    {
        return $this->hasMany(IndMetasIndicadores::className(), ['id_indicador' => 'id_indicador']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndRelacoesIndicadores()
    {
        return $this->hasMany(IndRelacoesIndicadores::className(), ['id_indicador_origem' => 'id_indicador']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndRelacoesIndicadores0()
    {
        return $this->hasMany(IndRelacoesIndicadores::className(), ['id_indicador_destino' => 'id_indicador']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndValoresIndicadores()
    {
        return $this->hasMany(IndValoresIndicadores::className(), ['id_indicador' => 'id_indicador']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndDefinicoesIndicadoresQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndDefinicoesIndicadoresQuery(get_called_class());
    }

    /**
     * Busca os dados consolidados de um indicador para o dashboard.
     * @param string $cod_indicador O código do indicador (ex: 'SC_KPI01')
     * @return array|null
     */
    public static function getDashboardData(string $cod_indicador): ?array
    {
        $indicador = self::find()
            ->where(['cod_indicador' => $cod_indicador, 'ativo' => true])
            ->asArray() // Pega os dados como array para performance
            ->one();

        if (!$indicador) {
            return null;
        }

        // Busca o valor mais recente
        $ultimoValor = IndValoresIndicadores::find()
            ->where(['id_indicador' => $indicador['id_indicador']])
            ->orderBy(['data_referencia' => SORT_DESC])
            ->asArray()
            ->one();

        // Busca a meta ativa para a data do último valor
        $metaAtiva = IndMetasIndicadores::find()
            ->where(['id_indicador' => $indicador['id_indicador']])
            ->andWhere(['<=', 'data_inicio_vigencia', $ultimoValor['data_referencia'] ?? date('Y-m-d')])
            ->andWhere(['or',
                ['data_fim_vigencia' => null],
                ['>=', 'data_fim_vigencia', $ultimoValor['data_referencia'] ?? date('Y-m-d')]
            ])
            ->asArray()
            ->one();
        
        // Busca o histórico para o gráfico (últimos 12 meses)
        $historico = IndValoresIndicadores::find()
            ->where(['id_indicador' => $indicador['id_indicador']])
            ->orderBy(['data_referencia' => SORT_ASC])
            ->limit(12)
            ->asArray()
            ->all();

        return [
            'definicao' => $indicador,
            'ultimoValor' => $ultimoValor,
            'meta' => $metaAtiva,
            'historico' => $historico
        ];
    }
}
