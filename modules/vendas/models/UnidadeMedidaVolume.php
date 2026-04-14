<?php

namespace app\modules\vendas\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "prest_unidade_medida_volume".
 *
 * @property string $nome
 * @property string $descricao
 * @property bool|null $ativo
 */
class UnidadeMedidaVolume extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_unidade_medida_volume';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome', 'descricao'], 'required'],
            [['ativo'], 'boolean'],
            [['nome'], 'string', 'max' => 10],
            [['descricao'], 'string', 'max' => 100],
            [['nome'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'nome' => 'Código/Nome',
            'descricao' => 'Descrição',
            'ativo' => 'Ativo',
        ];
    }

    /**
     * Retorna lista para dropdown do Select2
     * Formato: ['UN' => 'UN - Unidade', 'KG' => 'KG - Quilograma', ...]
     */
    public static function getListaDropdown()
    {
        $models = self::find()
            ->where(['ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        return ArrayHelper::map($models, 'nome', function($model) {
            return $model->nome . ' - ' . $model->descricao;
        });
    }
}
