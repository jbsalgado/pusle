<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "adm_contas".
 *
 * @property int $id
 * @property string $nome
 * @property string $email
 * @property string $senha
 * @property bool $is_superadmin
 */
class AdmContas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'adm_contas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome', 'email', 'senha'], 'required'],
            [['is_superadmin'], 'boolean'],
            [['nome'], 'string', 'max' => 150],
            [['email'], 'string', 'max' => 100],
            [['senha'], 'string', 'max' => 255],
            [['email'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nome' => 'Nome',
            'email' => 'Email',
            'senha' => 'Senha',
            'is_superadmin' => 'Is Superadmin',
        ];
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\AdmContasQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\AdmContasQuery(get_called_class());
    }
}
