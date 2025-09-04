<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_atributos_qualidade_desempenho".
 *
 * @property int $id_atributo_qd
 * @property int $id_indicador
 * @property string $padrao_ouro_referencia
 * @property string $faixa_critica_inferior
 * @property string $faixa_critica_superior
 * @property string $faixa_alerta_inferior
 * @property string $faixa_alerta_superior
 * @property string $faixa_satisfatoria_inferior
 * @property string $faixa_satisfatoria_superior
 * @property string $metodo_pontuacao
 * @property string $peso_indicador
 * @property int $fator_impacto
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property IndDefinicoesIndicadores $indicador
 */
class IndAtributosQualidadeDesempenho extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_atributos_qualidade_desempenho';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_indicador'], 'required'],
            [['id_indicador', 'fator_impacto'], 'default', 'value' => null],
            [['id_indicador', 'fator_impacto'], 'integer'],
            [['faixa_critica_inferior', 'faixa_critica_superior', 'faixa_alerta_inferior', 'faixa_alerta_superior', 'faixa_satisfatoria_inferior', 'faixa_satisfatoria_superior', 'peso_indicador'], 'number'],
            [['metodo_pontuacao'], 'string'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['padrao_ouro_referencia'], 'string', 'max' => 255],
            [['id_indicador'], 'unique'],
            [['id_indicador'], 'exist', 'skipOnError' => true, 'targetClass' => IndDefinicoesIndicadores::className(), 'targetAttribute' => ['id_indicador' => 'id_indicador']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_atributo_qd' => 'Id Atributo Qd',
            'id_indicador' => 'Id Indicador',
            'padrao_ouro_referencia' => 'Padrao Ouro Referencia',
            'faixa_critica_inferior' => 'Faixa Critica Inferior',
            'faixa_critica_superior' => 'Faixa Critica Superior',
            'faixa_alerta_inferior' => 'Faixa Alerta Inferior',
            'faixa_alerta_superior' => 'Faixa Alerta Superior',
            'faixa_satisfatoria_inferior' => 'Faixa Satisfatoria Inferior',
            'faixa_satisfatoria_superior' => 'Faixa Satisfatoria Superior',
            'metodo_pontuacao' => 'Metodo Pontuacao',
            'peso_indicador' => 'Peso Indicador',
            'fator_impacto' => 'Fator Impacto',
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
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndAtributosQualidadeDesempenhoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndAtributosQualidadeDesempenhoQuery(get_called_class());
    }
}
