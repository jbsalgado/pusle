<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_periodicidades".
 *
 * @property int $id_periodicidade
 * @property string $nome_periodicidade
 * @property string $descricao
 * @property int $intervalo_em_dias
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property IndDefinicoesIndicadores[] $indDefinicoesIndicadores
 * @property IndDefinicoesIndicadores[] $indDefinicoesIndicadores0
 */
class IndPeriodicidades extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_periodicidades';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome_periodicidade'], 'required'],
            [['descricao'], 'string'],
            [['intervalo_em_dias'], 'default', 'value' => null],
            [['intervalo_em_dias'], 'integer'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['nome_periodicidade'], 'string', 'max' => 100],
            [['nome_periodicidade'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_periodicidade' => 'Id Periodicidade',
            'nome_periodicidade' => 'Nome Periodicidade',
            'descricao' => 'Descricao',
            'intervalo_em_dias' => 'Intervalo Em Dias',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndDefinicoesIndicadores()
    {
        return $this->hasMany(IndDefinicoesIndicadores::className(), ['id_periodicidade_ideal_medicao' => 'id_periodicidade']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndDefinicoesIndicadores0()
    {
        return $this->hasMany(IndDefinicoesIndicadores::className(), ['id_periodicidade_ideal_divulgacao' => 'id_periodicidade']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndPeriodicidadesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndPeriodicidadesQuery(get_called_class());
    }
}
