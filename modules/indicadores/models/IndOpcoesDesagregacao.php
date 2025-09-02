<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_opcoes_desagregacao".
 *
 * @property int $id_opcao_desagregacao
 * @property int $id_categoria_desagregacao
 * @property string $valor_opcao
 * @property string $codigo_opcao
 * @property string $descricao_opcao
 * @property int $ordem_apresentacao
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property IndCategoriasDesagregacao $categoriaDesagregacao
 * @property IndValoresIndicadoresDesagregacoes[] $indValoresIndicadoresDesagregacoes
 * @property IndValoresIndicadores[] $valorIndicadors
 */
class IndOpcoesDesagregacao extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_opcoes_desagregacao';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_categoria_desagregacao', 'valor_opcao'], 'required'],
            [['id_categoria_desagregacao', 'ordem_apresentacao'], 'default', 'value' => null],
            [['id_categoria_desagregacao', 'ordem_apresentacao'], 'integer'],
            [['descricao_opcao'], 'string'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['valor_opcao'], 'string', 'max' => 255],
            [['codigo_opcao'], 'string', 'max' => 50],
            [['id_categoria_desagregacao', 'valor_opcao'], 'unique', 'targetAttribute' => ['id_categoria_desagregacao', 'valor_opcao']],
            [['id_categoria_desagregacao'], 'exist', 'skipOnError' => true, 'targetClass' => IndCategoriasDesagregacao::className(), 'targetAttribute' => ['id_categoria_desagregacao' => 'id_categoria_desagregacao']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_opcao_desagregacao' => 'Id Opcao Desagregacao',
            'id_categoria_desagregacao' => 'Id Categoria Desagregacao',
            'valor_opcao' => 'Valor Opcao',
            'codigo_opcao' => 'Codigo Opcao',
            'descricao_opcao' => 'Descricao Opcao',
            'ordem_apresentacao' => 'Ordem Apresentacao',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategoriaDesagregacao()
    {
        return $this->hasOne(IndCategoriasDesagregacao::className(), ['id_categoria_desagregacao' => 'id_categoria_desagregacao']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndValoresIndicadoresDesagregacoes()
    {
        return $this->hasMany(IndValoresIndicadoresDesagregacoes::className(), ['id_opcao_desagregacao' => 'id_opcao_desagregacao']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getValorIndicadors()
    {
        return $this->hasMany(IndValoresIndicadores::className(), ['id_valor' => 'id_valor_indicador'])->viaTable('ind_valores_indicadores_desagregacoes', ['id_opcao_desagregacao' => 'id_opcao_desagregacao']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndOpcoesDesagregacaoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndOpcoesDesagregacaoQuery(get_called_class());
    }
}
