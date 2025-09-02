<?php

namespace app\modules\indicadores\models;

use Yii;

/**
 * This is the model class for table "many_sys_modulos_has_many_ind_dimensoes_indicadores".
 *
 * @property int $id_sys_modulos
 * @property int $id_dimensao_ind_dimensoes_indicadores
 *
 * @property IndDimensoesIndicadores $dimensaoIndDimensoesIndicadores
 * @property SysModulos $sysModulos
 */
class ManySysModulosHasManyIndDimensoesIndicadores extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'many_sys_modulos_has_many_ind_dimensoes_indicadores';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_sys_modulos', 'id_dimensao_ind_dimensoes_indicadores'], 'required'],
            [['id_sys_modulos', 'id_dimensao_ind_dimensoes_indicadores'], 'default', 'value' => null],
            [['id_sys_modulos', 'id_dimensao_ind_dimensoes_indicadores'], 'integer'],
            [['id_sys_modulos', 'id_dimensao_ind_dimensoes_indicadores'], 'unique', 'targetAttribute' => ['id_sys_modulos', 'id_dimensao_ind_dimensoes_indicadores']],
            [['id_dimensao_ind_dimensoes_indicadores'], 'exist', 'skipOnError' => true, 'targetClass' => IndDimensoesIndicadores::className(), 'targetAttribute' => ['id_dimensao_ind_dimensoes_indicadores' => 'id_dimensao']],
            [['id_sys_modulos'], 'exist', 'skipOnError' => true, 'targetClass' => SysModulos::className(), 'targetAttribute' => ['id_sys_modulos' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_sys_modulos' => 'Id Sys Modulos',
            'id_dimensao_ind_dimensoes_indicadores' => 'Id Dimensao Ind Dimensoes Indicadores',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDimensaoIndDimensoesIndicadores()
    {
        return $this->hasOne(IndDimensoesIndicadores::className(), ['id_dimensao' => 'id_dimensao_ind_dimensoes_indicadores']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSysModulos()
    {
        return $this->hasOne(SysModulos::className(), ['id' => 'id_sys_modulos']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\ManySysModulosHasManyIndDimensoesIndicadoresQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\ManySysModulosHasManyIndDimensoesIndicadoresQuery(get_called_class());
    }
}
