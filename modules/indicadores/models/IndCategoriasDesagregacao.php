<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_categorias_desagregacao".
 *
 * @property int $id_categoria_desagregacao
 * @property string $nome_categoria
 * @property string $descricao
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property IndOpcoesDesagregacao[] $indOpcoesDesagregacaos
 */
class IndCategoriasDesagregacao extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_categorias_desagregacao';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome_categoria'], 'required'],
            [['descricao'], 'string'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['nome_categoria'], 'string', 'max' => 255],
            [['nome_categoria'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_categoria_desagregacao' => 'Id Categoria Desagregacao',
            'nome_categoria' => 'Nome Categoria',
            'descricao' => 'Descricao',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndOpcoesDesagregacaos()
    {
        return $this->hasMany(IndOpcoesDesagregacao::className(), ['id_categoria_desagregacao' => 'id_categoria_desagregacao']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndCategoriasDesagregacaoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndCategoriasDesagregacaoQuery(get_called_class());
    }
}
