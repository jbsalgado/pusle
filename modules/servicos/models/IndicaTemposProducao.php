<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "indica_tempos_producao".
 *
 * @property int $id
 * @property int $lote_id
 * @property int $etapa_id
 * @property string $inicio
 * @property string $fim
 * @property int $tempo_total_minutos
 *
 * @property ProdEtapasProducao $etapa
 * @property ProdLotes $lote
 */
class IndicaTemposProducao extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'indica_tempos_producao';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['lote_id', 'etapa_id'], 'required'],
            [['lote_id', 'etapa_id', 'tempo_total_minutos'], 'default', 'value' => null],
            [['lote_id', 'etapa_id', 'tempo_total_minutos'], 'integer'],
            [['inicio', 'fim'], 'safe'],
            [['etapa_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProdEtapasProducao::className(), 'targetAttribute' => ['etapa_id' => 'id']],
            [['lote_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProdLotes::className(), 'targetAttribute' => ['lote_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'lote_id' => 'Lote ID',
            'etapa_id' => 'Etapa ID',
            'inicio' => 'Inicio',
            'fim' => 'Fim',
            'tempo_total_minutos' => 'Tempo Total Minutos',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEtapa()
    {
        return $this->hasOne(ProdEtapasProducao::className(), ['id' => 'etapa_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLote()
    {
        return $this->hasOne(ProdLotes::className(), ['id' => 'lote_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\IndicaTemposProducaoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\IndicaTemposProducaoQuery(get_called_class());
    }
}
