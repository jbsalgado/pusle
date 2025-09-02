<?php

namespace app\modules\indicadores\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "many_sys_modulos_has_many_user".
 *
 * @property int $id_sys_modulos
 * @property int $id_user
 *
 * @property SysModulos $sysModulos
 * @property User $user
 */
class ManySysModulosHasManyUser extends \yii\db\ActiveRecord
{
    /**
     * @var array IDs dos módulos selecionados - propriedade virtual para o formulário
     */
    public $modulos_selecionados;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'many_sys_modulos_has_many_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_user'], 'required'],
            [['id_sys_modulos', 'id_user'], 'integer'],
            // Removida a validação de 'unique' composta para permitir o salvamento múltiplo.
            // A lógica de associação tratará disso.
            [['id_sys_modulos'], 'exist', 'skipOnError' => true, 'targetClass' => SysModulos::class, 'targetAttribute' => ['id_sys_modulos' => 'id']],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['id_user' => 'id']],
            [['modulos_selecionados'], 'safe'], // Propriedade virtual para o formulário
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_sys_modulos' => 'Módulo',
            'id_user' => 'Usuário',
            'modulos_selecionados' => 'Módulos Associados',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSysModulos()
    {
        return $this->hasOne(SysModulos::class, ['id' => 'id_sys_modulos']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'id_user']);
    }
    
    /**
     * Retorna um array com todos os usuários para uso em um Select2.
     * @return array
     */
    public static function getUsersForSelect()
    {
        $users = User::find()->orderBy('username ASC')->all();
        return ArrayHelper::map($users, 'id', 'username');
    }

    /**
     * Retorna um array com todos os módulos ativos para uso em um Select2.
     * @return array
     */
    public static function getModulosForSelect()
    {
        $modulos = SysModulos::find()->where(['status' => true])->orderBy('modulo ASC')->all();
        return ArrayHelper::map($modulos, 'id', 'modulo');
    }

    /**
     * Carrega os módulos associados a um usuário específico.
     * @param int $userId
     */
    public function loadUserModules($userId)
    {
        $this->id_user = $userId;
        $this->modulos_selecionados = static::find()
            ->select('id_sys_modulos')
            ->where(['id_user' => $userId])
            ->column();
    }

    /**
     * Salva as associações para um usuário.
     * @return bool
     */
    public function saveAssociations()
    {
        // Garante que a seleção seja um array
        $selectedModules = is_array($this->modulos_selecionados) ? $this->modulos_selecionados : [];
        
        // Remove associações antigas
        static::deleteAll(['id_user' => $this->id_user]);

        if (!empty($selectedModules)) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                foreach ($selectedModules as $moduleId) {
                    $association = new static();
                    $association->id_user = $this->id_user;
                    $association->id_sys_modulos = $moduleId;
                    if (!$association->save()) {
                        // Lança exceção para acionar o rollback
                        throw new \Exception(implode('; ', $association->getFirstErrors()));
                    }
                }
                $transaction->commit();
                return true;
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error("Erro ao salvar associações de módulos para o usuário ID {$this->id_user}: " . $e->getMessage());
                return false;
            }
        }
        
        // Se nenhum módulo foi selecionado, as associações já foram limpas
        return true;
    }
}