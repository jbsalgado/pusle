<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_valores_indicadores_desagregacoes".
 *
 * @property int $id_valor_indicador
 * @property int $id_opcao_desagregacao
 *
 * @property IndOpcoesDesagregacao $opcaoDesagregacao
 * @property IndValoresIndicadores $valorIndicador
 */
class IndValoresIndicadoresDesagregacoes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_valores_indicadores_desagregacoes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_valor_indicador', 'id_opcao_desagregacao'], 'required'],
            [['id_valor_indicador', 'id_opcao_desagregacao'], 'default', 'value' => null],
            [['id_valor_indicador', 'id_opcao_desagregacao'], 'integer'],
            [['id_valor_indicador', 'id_opcao_desagregacao'], 'unique', 'targetAttribute' => ['id_valor_indicador', 'id_opcao_desagregacao']],
            [['id_opcao_desagregacao'], 'exist', 'skipOnError' => true, 'targetClass' => IndOpcoesDesagregacao::className(), 'targetAttribute' => ['id_opcao_desagregacao' => 'id_opcao_desagregacao']],
            [['id_valor_indicador'], 'exist', 'skipOnError' => true, 'targetClass' => IndValoresIndicadores::className(), 'targetAttribute' => ['id_valor_indicador' => 'id_valor']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_valor_indicador' => 'Id Valor Indicador',
            'id_opcao_desagregacao' => 'Id Opcao Desagregacao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOpcaoDesagregacao()
    {
        return $this->hasOne(IndOpcoesDesagregacao::className(), ['id_opcao_desagregacao' => 'id_opcao_desagregacao']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getValorIndicador()
    {
        return $this->hasOne(IndValoresIndicadores::className(), ['id_valor' => 'id_valor_indicador']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndValoresIndicadoresDesagregacoesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndValoresIndicadoresDesagregacoesQuery(get_called_class());
    }
}
