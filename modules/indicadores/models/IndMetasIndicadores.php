<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_metas_indicadores".
 *
 * @property int $id_meta
 * @property int $id_indicador
 * @property string $descricao_meta
 * @property string $valor_meta_referencia_1
 * @property string $valor_meta_referencia_2
 * @property string $tipo_de_meta
 * @property string $data_inicio_vigencia
 * @property string $data_fim_vigencia
 * @property int $id_nivel_abrangencia_aplicavel
 * @property string $justificativa_meta
 * @property string $fonte_meta
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property IndDefinicoesIndicadores $indicador
 * @property IndNiveisAbrangencia $nivelAbrangenciaAplicavel
 */
class IndMetasIndicadores extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_metas_indicadores';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_indicador', 'valor_meta_referencia_1', 'tipo_de_meta', 'data_inicio_vigencia'], 'required'],
            [['id_indicador', 'id_nivel_abrangencia_aplicavel'], 'default', 'value' => null],
            [['id_indicador', 'id_nivel_abrangencia_aplicavel'], 'integer'],
            [['valor_meta_referencia_1', 'valor_meta_referencia_2'], 'number'],
            [['tipo_de_meta', 'justificativa_meta'], 'string'],
            [['data_inicio_vigencia', 'data_fim_vigencia', 'data_criacao', 'data_atualizacao'], 'safe'],
            [['descricao_meta'], 'string', 'max' => 512],
            [['fonte_meta'], 'string', 'max' => 255],
            [['id_indicador', 'tipo_de_meta', 'data_inicio_vigencia', 'id_nivel_abrangencia_aplicavel', 'valor_meta_referencia_1'], 'unique', 'targetAttribute' => ['id_indicador', 'tipo_de_meta', 'data_inicio_vigencia', 'id_nivel_abrangencia_aplicavel', 'valor_meta_referencia_1']],
            [['id_indicador'], 'exist', 'skipOnError' => true, 'targetClass' => IndDefinicoesIndicadores::className(), 'targetAttribute' => ['id_indicador' => 'id_indicador']],
            [['id_nivel_abrangencia_aplicavel'], 'exist', 'skipOnError' => true, 'targetClass' => IndNiveisAbrangencia::className(), 'targetAttribute' => ['id_nivel_abrangencia_aplicavel' => 'id_nivel_abrangencia']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_meta' => 'Id Meta',
            'id_indicador' => 'Id Indicador',
            'descricao_meta' => 'Descricao Meta',
            'valor_meta_referencia_1' => 'Valor Meta Referencia 1',
            'valor_meta_referencia_2' => 'Valor Meta Referencia 2',
            'tipo_de_meta' => 'Tipo De Meta',
            'data_inicio_vigencia' => 'Data Inicio Vigencia',
            'data_fim_vigencia' => 'Data Fim Vigencia',
            'id_nivel_abrangencia_aplicavel' => 'Id Nivel Abrangencia Aplicavel',
            'justificativa_meta' => 'Justificativa Meta',
            'fonte_meta' => 'Fonte Meta',
            'data_criacao' => 'Data Criacao',
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
    public function getNivelAbrangenciaAplicavel()
    {
        return $this->hasOne(IndNiveisAbrangencia::className(), ['id_nivel_abrangencia' => 'id_nivel_abrangencia_aplicavel']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndMetasIndicadoresQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndMetasIndicadoresQuery(get_called_class());
    }
}
