<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "prod_status_lote".
 *
 * @property int $id
 * @property string $descricao
 *
 * @property ProdLotes[] $prodLotes
 */
class ProdStatusLote extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prod_status_lote';
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
    public function getProdLotes()
    {
        return $this->hasMany(ProdLotes::className(), ['status_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\ProdStatusLoteQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\ProdStatusLoteQuery(get_called_class());
    }
}
