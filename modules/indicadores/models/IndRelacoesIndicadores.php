<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_relacoes_indicadores".
 *
 * @property int $id_relacao
 * @property int $id_indicador_origem
 * @property int $id_indicador_destino
 * @property string $tipo_relacao
 * @property string $descricao_relacao
 * @property string $peso_relacao
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property IndDefinicoesIndicadores $indicadorOrigem
 * @property IndDefinicoesIndicadores $indicadorDestino
 */
class IndRelacoesIndicadores extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_relacoes_indicadores';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_indicador_origem', 'id_indicador_destino', 'tipo_relacao'], 'required'],
            [['id_indicador_origem', 'id_indicador_destino'], 'default', 'value' => null],
            [['id_indicador_origem', 'id_indicador_destino'], 'integer'],
            [['descricao_relacao'], 'string'],
            [['peso_relacao'], 'number'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['tipo_relacao'], 'string', 'max' => 100],
            [['id_indicador_origem', 'id_indicador_destino', 'tipo_relacao'], 'unique', 'targetAttribute' => ['id_indicador_origem', 'id_indicador_destino', 'tipo_relacao']],
            [['id_indicador_origem'], 'exist', 'skipOnError' => true, 'targetClass' => IndDefinicoesIndicadores::className(), 'targetAttribute' => ['id_indicador_origem' => 'id_indicador']],
            [['id_indicador_destino'], 'exist', 'skipOnError' => true, 'targetClass' => IndDefinicoesIndicadores::className(), 'targetAttribute' => ['id_indicador_destino' => 'id_indicador']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_relacao' => 'Id Relacao',
            'id_indicador_origem' => 'Id Indicador Origem',
            'id_indicador_destino' => 'Id Indicador Destino',
            'tipo_relacao' => 'Tipo Relacao',
            'descricao_relacao' => 'Descricao Relacao',
            'peso_relacao' => 'Peso Relacao',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndicadorOrigem()
    {
        return $this->hasOne(IndDefinicoesIndicadores::className(), ['id_indicador' => 'id_indicador_origem']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndicadorDestino()
    {
        return $this->hasOne(IndDefinicoesIndicadores::className(), ['id_indicador' => 'id_indicador_destino']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndRelacoesIndicadoresQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndRelacoesIndicadoresQuery(get_called_class());
    }
}
