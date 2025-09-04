<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "estoq_tipos_movimento".
 *
 * @property int $id
 * @property string $descricao
 * @property int $fator Define se o movimento adiciona ou remove do estoque.
 *
 * @property EstoqMovimentacoes[] $estoqMovimentacoes
 */
class EstoqTiposMovimento extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'estoq_tipos_movimento';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descricao', 'fator'], 'required'],
            [['fator'], 'default', 'value' => null],
            [['fator'], 'integer'],
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
            'fator' => 'Fator',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEstoqMovimentacoes()
    {
        return $this->hasMany(EstoqMovimentacoes::className(), ['tipo_movimento_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\EstoqTiposMovimentoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\EstoqTiposMovimentoQuery(get_called_class());
    }
}
