<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_fontes_dados".
 *
 * @property int $id_fonte
 * @property string $nome_fonte
 * @property string $descricao
 * @property string $url_referencia
 * @property int $confiabilidade_estimada Uma estimativa subjetiva da confiabilidade da fonte.
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property IndDefinicoesIndicadores[] $indDefinicoesIndicadores
 * @property IndValoresIndicadores[] $indValoresIndicadores
 */
class IndFontesDados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_fontes_dados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome_fonte'], 'required'],
            [['descricao'], 'string'],
            [['confiabilidade_estimada'], 'default', 'value' => null],
            [['confiabilidade_estimada'], 'integer'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['nome_fonte'], 'string', 'max' => 255],
            [['url_referencia'], 'string', 'max' => 512],
            [['nome_fonte'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_fonte' => 'Id Fonte',
            'nome_fonte' => 'Nome Fonte',
            'descricao' => 'Descricao',
            'url_referencia' => 'Url Referencia',
            'confiabilidade_estimada' => 'Confiabilidade Estimada',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndDefinicoesIndicadores()
    {
        return $this->hasMany(IndDefinicoesIndicadores::className(), ['id_fonte_padrao' => 'id_fonte']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndValoresIndicadores()
    {
        return $this->hasMany(IndValoresIndicadores::className(), ['id_fonte_dado_especifica' => 'id_fonte']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndFontesDadosQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndFontesDadosQuery(get_called_class());
    }
}
