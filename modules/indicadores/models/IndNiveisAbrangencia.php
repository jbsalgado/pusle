<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_niveis_abrangencia".
 *
 * @property int $id_nivel_abrangencia
 * @property string $nome_nivel
 * @property string $descricao
 * @property string $tipo_nivel
 * @property int $id_nivel_pai
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property IndMetasIndicadores[] $indMetasIndicadores
 * @property IndNiveisAbrangencia $nivelPai
 * @property IndNiveisAbrangencia[] $indNiveisAbrangencias
 * @property IndValoresIndicadores[] $indValoresIndicadores
 */
class IndNiveisAbrangencia extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_niveis_abrangencia';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome_nivel'], 'required'],
            [['descricao'], 'string'],
            [['id_nivel_pai'], 'default', 'value' => null],
            [['id_nivel_pai'], 'integer'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['nome_nivel'], 'string', 'max' => 150],
            [['tipo_nivel'], 'string', 'max' => 50],
            [['nome_nivel'], 'unique'],
            [['id_nivel_pai'], 'exist', 'skipOnError' => true, 'targetClass' => IndNiveisAbrangencia::className(), 'targetAttribute' => ['id_nivel_pai' => 'id_nivel_abrangencia']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_nivel_abrangencia' => 'Id Nivel Abrangencia',
            'nome_nivel' => 'Nome Nivel',
            'descricao' => 'Descricao',
            'tipo_nivel' => 'Tipo Nivel',
            'id_nivel_pai' => 'Id Nivel Pai',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndMetasIndicadores()
    {
        return $this->hasMany(IndMetasIndicadores::className(), ['id_nivel_abrangencia_aplicavel' => 'id_nivel_abrangencia']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNivelPai()
    {
        return $this->hasOne(IndNiveisAbrangencia::className(), ['id_nivel_abrangencia' => 'id_nivel_pai']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndNiveisAbrangencias()
    {
        return $this->hasMany(IndNiveisAbrangencia::className(), ['id_nivel_pai' => 'id_nivel_abrangencia']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndValoresIndicadores()
    {
        return $this->hasMany(IndValoresIndicadores::className(), ['id_nivel_abrangencia' => 'id_nivel_abrangencia']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndNiveisAbrangenciaQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndNiveisAbrangenciaQuery(get_called_class());
    }
}
