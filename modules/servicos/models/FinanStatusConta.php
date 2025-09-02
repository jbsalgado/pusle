<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "finan_status_conta".
 *
 * @property int $id
 * @property string $descricao
 *
 * @property FinanContasPagar[] $finanContasPagars
 * @property FinanContasReceber[] $finanContasRecebers
 */
class FinanStatusConta extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'finan_status_conta';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descricao'], 'required'],
            [['descricao'], 'string', 'max' => 50],
            [['descricao'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'descricao' => 'Descricao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFinanContasPagars()
    {
        return $this->hasMany(FinanContasPagar::className(), ['status_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFinanContasRecebers()
    {
        return $this->hasMany(FinanContasReceber::className(), ['status_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\FinanStatusContaQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\FinanStatusContaQuery(get_called_class());
    }
}
