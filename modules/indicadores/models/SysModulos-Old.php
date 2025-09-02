<?php

namespace app\modules\indicadores\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "sys_modulos".
 *
 * @property int $id
 * @property string $modulo
 * @property string $path
 * @property bool $status
 *
 * @property ManySysModulosHasManyUser[] $manySysModulosHasManyUsers
 * @property User[] $users
 */
class SysModulos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sys_modulos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['modulo', 'path'], 'required'],
            [['status'], 'boolean'],
            [['modulo', 'path'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'modulo' => 'Modulo',
            'path' => 'Path',
            'status' => 'Status',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getManySysModulosHasManyUsers()
    {
        return $this->hasMany(ManySysModulosHasManyUser::className(), ['id_sys_modulos' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['id' => 'id_user'])->viaTable('many_sys_modulos_has_many_user', ['id_sys_modulos' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\query\SysModulosQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\indicadores\query\SysModulosQuery(get_called_class());
    }
    /**
     * Gets query for [[ManySysModulosHasManyIndDimensoesIndicadores]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getManySysModulosHasManyIndDimensoesIndicadores()
    {
        return $this->hasMany(ManySysModulosHasManyIndDimensoesIndicadores::class, ['id_sys_modulos' => 'id']);
    }

    /**
     * Gets query for [[DimensoesIndicadores]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDimensoesIndicadores()
    {
        return $this->hasMany(IndDimensoesIndicadores::class, ['id_dimensao' => 'id_dimensao_ind_dimensoes_indicadores'])
            ->viaTable('many_sys_modulos_has_many_ind_dimensoes_indicadores', ['id_sys_modulos' => 'id']);
    }

    /**
     * Retorna array de dimensões para Select2
     */
    public static function getDimensoesForSelect2()
    {
        $dimensoes = IndDimensoesIndicadores::find()
            ->orderBy('nome_dimensao ASC')
            ->all();

        return ArrayHelper::map($dimensoes, 'id_dimensao', function($model) {
            return $model->nome_dimensao . ($model->descricao ? ' - ' . substr($model->descricao, 0, 50) . '...' : '');
        });
    }

    /**
     * Retorna IDs das dimensões associadas
     */
    public function getDimensoesSelecionadasIds()
    {
        return ArrayHelper::getColumn($this->dimensoesIndicadores, 'id_dimensao');
    }

    /**
     * Salva as associações com dimensões
     */
    public function saveDimensoesAssociation($dimensoes_ids)
    {
        // Remove associações existentes
        ManySysModulosHasManyIndDimensoesIndicadores::deleteAll(['id_sys_modulos' => $this->id]);

        // Adiciona novas associações
        if (!empty($dimensoes_ids)) {
            foreach ($dimensoes_ids as $dimensao_id) {
                $association = new ManySysModulosHasManyIndDimensoesIndicadores();
                $association->id_sys_modulos = $this->id;
                $association->id_dimensao_ind_dimensoes_indicadores = $dimensao_id;
                $association->save();
            }
        }
    }

    /**
     * After find - carrega dimensões selecionadas
     */
    public function afterFind()
    {
        parent::afterFind();
        $this->dimensoes_selecionadas = $this->getDimensoesSelecionadasIds();
    }

    /**
     * After save - salva associações
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $this->saveDimensoesAssociation($this->dimensoes_selecionadas);
    }
    public static function getModulosParaSelect()
    {
        $modulos = self::find()
            ->where(['status' => true])      // Busca apenas os módulos ativos
            ->orderBy('modulo')              // Ordena pelo nome do módulo
            ->all();                         // Retorna objetos Active Record

        // Usa ArrayHelper::map para criar o array no formato adequado
        return ArrayHelper::map($modulos, 'id', 'modulo');
    }
}
