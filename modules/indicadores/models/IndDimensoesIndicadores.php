<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "ind_dimensoes_indicadores".
 *
 * @property int $id_dimensao
 * @property string $nome_dimensao
 * @property string $descricao
 * @property int $id_dimensao_pai Permite criar hierarquias, como subdimensões.
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property IndDefinicoesIndicadores[] $indDefinicoesIndicadores
 * @property IndDimensoesIndicadores $dimensaoPai
 * @property IndDimensoesIndicadores[] $indDimensoesIndicadores
 */
class IndDimensoesIndicadores extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ind_dimensoes_indicadores';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome_dimensao'], 'required'],
            [['descricao'], 'string'],
            [['id_dimensao_pai'], 'default', 'value' => null],
            [['id_dimensao_pai'], 'integer'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['nome_dimensao'], 'string', 'max' => 255],
            [['nome_dimensao'], 'unique'],
            [['id_dimensao_pai'], 'exist', 'skipOnError' => true, 'targetClass' => IndDimensoesIndicadores::className(), 'targetAttribute' => ['id_dimensao_pai' => 'id_dimensao']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_dimensao' => 'Id Dimensao',
            'nome_dimensao' => 'Nome Dimensao',
            'descricao' => 'Descricao',
            'id_dimensao_pai' => 'Id Dimensao Pai',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndDefinicoesIndicadores()
    {
        return $this->hasMany(IndDefinicoesIndicadores::className(), ['id_dimensao' => 'id_dimensao']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDimensaoPai()
    {
        return $this->hasOne(IndDimensoesIndicadores::className(), ['id_dimensao' => 'id_dimensao_pai']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndDimensoesIndicadores()
    {
        return $this->hasMany(IndDimensoesIndicadores::className(), ['id_dimensao_pai' => 'id_dimensao']);
    }

    /**
     * Gets query for [[DimensoesFilhas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDimensoesFilhas()
    {
        return $this->hasMany(IndDimensoesIndicadores::class, ['id_dimensao_pai' => 'id_dimensao']);
    }

    /**
     * Gets query for [[ManySysModulosHasManyIndDimensoesIndicadores]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getManySysModulosHasManyIndDimensoesIndicadores()
    {
        return $this->hasMany(ManySysModulosHasManyIndDimensoesIndicadores::class, ['id_dimensao_ind_dimensoes_indicadores' => 'id_dimensao']);
    }

    /**
     * Gets query for [[SysModulos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSysModulos()
    {
        return $this->hasMany(SysModulos::class, ['id' => 'id_sys_modulos'])
            ->viaTable('many_sys_modulos_has_many_ind_dimensoes_indicadores', ['id_dimensao_ind_dimensoes_indicadores' => 'id_dimensao']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\IndDimensoesIndicadoresQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\IndDimensoesIndicadoresQuery(get_called_class());
    }
}
