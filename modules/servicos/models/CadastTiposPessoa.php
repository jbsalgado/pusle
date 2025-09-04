<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "cadast_tipos_pessoa".
 *
 * @property int $id
 * @property string $descricao
 *
 * @property CadastClientes[] $cadastClientes
 * @property CadastTerceiros[] $cadastTerceiros
 */
class CadastTiposPessoa extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cadast_tipos_pessoa';
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
    public function getCadastClientes()
    {
        return $this->hasMany(CadastClientes::className(), ['tipo_pessoa_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCadastTerceiros()
    {
        return $this->hasMany(CadastTerceiros::className(), ['tipo_pessoa_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\CadastTiposPessoaQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\CadastTiposPessoaQuery(get_called_class());
    }
}
