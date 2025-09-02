<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "indica_producao_diaria".
 *
 * @property int $id
 * @property int $terceiro_id
 * @property string $data
 * @property int $pecas_produzidas
 * @property string $horas_trabalhadas
 *
 * @property CadastTerceiros $terceiro
 */
class IndicaProducaoDiaria extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'indica_producao_diaria';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['terceiro_id', 'data', 'pecas_produzidas'], 'required'],
            [['terceiro_id', 'pecas_produzidas'], 'default', 'value' => null],
            [['terceiro_id', 'pecas_produzidas'], 'integer'],
            [['data'], 'safe'],
            [['horas_trabalhadas'], 'number'],
            [['terceiro_id', 'data'], 'unique', 'targetAttribute' => ['terceiro_id', 'data']],
            [['terceiro_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastTerceiros::className(), 'targetAttribute' => ['terceiro_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'terceiro_id' => 'Terceiro ID',
            'data' => 'Data',
            'pecas_produzidas' => 'Pecas Produzidas',
            'horas_trabalhadas' => 'Horas Trabalhadas',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTerceiro()
    {
        return $this->hasOne(CadastTerceiros::className(), ['id' => 'terceiro_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\IndicaProducaoDiariaQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\IndicaProducaoDiariaQuery(get_called_class());
    }
}
