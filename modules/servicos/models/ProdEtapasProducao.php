<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "prod_etapas_producao".
 *
 * @property int $id
 * @property int $empresa_id
 * @property string $descricao
 * @property int $ordem
 *
 * @property IndicaTemposProducao[] $indicaTemposProducaos
 * @property CadastEmpresas $empresa
 * @property ProdLotes[] $prodLotes
 */
class ProdEtapasProducao extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prod_etapas_producao';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'descricao', 'ordem'], 'required'],
            [['empresa_id', 'ordem'], 'default', 'value' => null],
            [['empresa_id', 'ordem'], 'integer'],
            [['descricao'], 'string', 'max' => 100],
            [['empresa_id', 'descricao'], 'unique', 'targetAttribute' => ['empresa_id', 'descricao']],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'empresa_id' => 'Empresa ID',
            'descricao' => 'Descricao',
            'ordem' => 'Ordem',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndicaTemposProducaos()
    {
        return $this->hasMany(IndicaTemposProducao::className(), ['etapa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmpresa()
    {
        return $this->hasOne(CadastEmpresas::className(), ['id' => 'empresa_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProdLotes()
    {
        return $this->hasMany(ProdLotes::className(), ['etapa_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\ProdEtapasProducaoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\ProdEtapasProducaoQuery(get_called_class());
    }
}
