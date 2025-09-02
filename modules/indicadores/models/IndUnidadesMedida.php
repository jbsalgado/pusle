<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_unidades_medida".
 *
 * @property int $id_unidade
 * @property string $sigla_unidade
 * @property string $descricao_unidade
 * @property string $tipo_dado_associado
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property IndDefinicoesIndicadores[] $indDefinicoesIndicadores
 */
class IndUnidadesMedida extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_unidades_medida';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sigla_unidade', 'descricao_unidade'], 'required'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['sigla_unidade', 'tipo_dado_associado'], 'string', 'max' => 50],
            [['descricao_unidade'], 'string', 'max' => 255],
            [['sigla_unidade'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_unidade' => 'Id Unidade',
            'sigla_unidade' => 'Sigla Unidade',
            'descricao_unidade' => 'Descricao Unidade',
            'tipo_dado_associado' => 'Tipo Dado Associado',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndDefinicoesIndicadores()
    {
        return $this->hasMany(IndDefinicoesIndicadores::className(), ['id_unidade_medida' => 'id_unidade']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndUnidadesMedidaQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndUnidadesMedidaQuery(get_called_class());
    }
}
