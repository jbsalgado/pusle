<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "indica_qualidade_defeitos".
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $lote_id
 * @property string $data_registro
 * @property string $tipo_defeito
 * @property int $quantidade
 *
 * @property CadastEmpresas $empresa
 * @property ProdLotes $lote
 */
class IndicaQualidadeDefeitos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'indica_qualidade_defeitos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'lote_id', 'tipo_defeito', 'quantidade'], 'required'],
            [['empresa_id', 'lote_id', 'quantidade'], 'default', 'value' => null],
            [['empresa_id', 'lote_id', 'quantidade'], 'integer'],
            [['data_registro'], 'safe'],
            [['tipo_defeito'], 'string', 'max' => 100],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
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
            'empresa_id' => 'Empresa ID',
            'lote_id' => 'Lote ID',
            'data_registro' => 'Data Registro',
            'tipo_defeito' => 'Tipo Defeito',
            'quantidade' => 'Quantidade',
        ];
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
    public function getLote()
    {
        return $this->hasOne(ProdLotes::className(), ['id' => 'lote_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\IndicaQualidadeDefeitosQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\IndicaQualidadeDefeitosQuery(get_called_class());
    }
}
