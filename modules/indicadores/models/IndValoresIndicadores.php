<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_valores_indicadores".
 *
 * @property int $id_valor
 * @property int $id_indicador
 * @property string $data_referencia Data de competência do valor do indicador.
 * @property int $id_nivel_abrangencia
 * @property string $codigo_especifico_abrangencia
 * @property string $localidade_especifica_nome
 * @property string $valor
 * @property string $numerador
 * @property string $denominador
 * @property int $id_fonte_dado_especifica
 * @property string $data_coleta_dado
 * @property string $confianca_intervalo_inferior
 * @property string $confianca_intervalo_superior
 * @property string $analise_qualitativa_valor
 * @property string $data_publicacao_valor
 * @property string $data_atualizacao
 *
 * @property IndDefinicoesIndicadores $indicador
 * @property IndFontesDados $fonteDadoEspecifica
 * @property IndNiveisAbrangencia $nivelAbrangencia
 * @property IndValoresIndicadoresDesagregacoes[] $indValoresIndicadoresDesagregacoes
 * @property IndOpcoesDesagregacao[] $opcaoDesagregacaos
 */
class IndValoresIndicadores extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_valores_indicadores';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_indicador', 'data_referencia', 'id_nivel_abrangencia', 'valor'], 'required'],
            [['id_indicador', 'id_nivel_abrangencia', 'id_fonte_dado_especifica'], 'default', 'value' => null],
            [['id_indicador', 'id_nivel_abrangencia', 'id_fonte_dado_especifica'], 'integer'],
            [['data_referencia', 'data_coleta_dado', 'data_publicacao_valor', 'data_atualizacao'], 'safe'],
            [['valor', 'numerador', 'denominador', 'confianca_intervalo_inferior', 'confianca_intervalo_superior'], 'number'],
            [['analise_qualitativa_valor'], 'string'],
            [['codigo_especifico_abrangencia'], 'string', 'max' => 100],
            [['localidade_especifica_nome'], 'string', 'max' => 255],
            [['id_indicador', 'data_referencia', 'id_nivel_abrangencia', 'codigo_especifico_abrangencia'], 'unique', 'targetAttribute' => ['id_indicador', 'data_referencia', 'id_nivel_abrangencia', 'codigo_especifico_abrangencia']],
            [['id_indicador'], 'exist', 'skipOnError' => true, 'targetClass' => IndDefinicoesIndicadores::className(), 'targetAttribute' => ['id_indicador' => 'id_indicador']],
            [['id_fonte_dado_especifica'], 'exist', 'skipOnError' => true, 'targetClass' => IndFontesDados::className(), 'targetAttribute' => ['id_fonte_dado_especifica' => 'id_fonte']],
            [['id_nivel_abrangencia'], 'exist', 'skipOnError' => true, 'targetClass' => IndNiveisAbrangencia::className(), 'targetAttribute' => ['id_nivel_abrangencia' => 'id_nivel_abrangencia']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_valor' => 'Id Valor',
            'id_indicador' => 'Id Indicador',
            'data_referencia' => 'Data Referencia',
            'id_nivel_abrangencia' => 'Id Nivel Abrangencia',
            'codigo_especifico_abrangencia' => 'Codigo Especifico Abrangencia',
            'localidade_especifica_nome' => 'Localidade Especifica Nome',
            'valor' => 'Valor',
            'numerador' => 'Numerador',
            'denominador' => 'Denominador',
            'id_fonte_dado_especifica' => 'Id Fonte Dado Especifica',
            'data_coleta_dado' => 'Data Coleta Dado',
            'confianca_intervalo_inferior' => 'Confianca Intervalo Inferior',
            'confianca_intervalo_superior' => 'Confianca Intervalo Superior',
            'analise_qualitativa_valor' => 'Analise Qualitativa Valor',
            'data_publicacao_valor' => 'Data Publicacao Valor',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndicador()
    {
        return $this->hasOne(IndDefinicoesIndicadores::className(), ['id_indicador' => 'id_indicador']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFonteDadoEspecifica()
    {
        return $this->hasOne(IndFontesDados::className(), ['id_fonte' => 'id_fonte_dado_especifica']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNivelAbrangencia()
    {
        return $this->hasOne(IndNiveisAbrangencia::className(), ['id_nivel_abrangencia' => 'id_nivel_abrangencia']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndValoresIndicadoresDesagregacoes()
    {
        return $this->hasMany(IndValoresIndicadoresDesagregacoes::className(), ['id_valor_indicador' => 'id_valor']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOpcaoDesagregacaos()
    {
        return $this->hasMany(IndOpcoesDesagregacao::className(), ['id_opcao_desagregacao' => 'id_opcao_desagregacao'])->viaTable('ind_valores_indicadores_desagregacoes', ['id_valor_indicador' => 'id_valor']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndValoresIndicadoresQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndValoresIndicadoresQuery(get_called_class());
    }
}
