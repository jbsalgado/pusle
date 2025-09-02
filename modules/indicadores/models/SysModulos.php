<?php

namespace app\modules\indicadores\models;

use app\modules\indicadores\models\IndDimensoesIndicadores;
use app\modules\indicadores\models\ManySysModulosHasManyIndDimensoesIndicadores;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "sys_modulos".
 *
 * @property int $id
 * @property string $modulo
 * @property string $path
 * @property bool $status
 *
 * @property ManySysModulosHasManyIndDimensoesIndicadores[] $manySysModulosHasManyIndDimensoesIndicadores
 * @property IndDimensoesIndicadores[] $dimensoesIndicadores
 */
class SysModulos extends ActiveRecord
{
    /**
     * @var array IDs das dimensões selecionadas - propriedade virtual para o formulário
     */
    public $dimensoes_selecionadas;

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
            [['status'], 'default', 'value' => false],
            [['modulo', 'path'], 'string', 'max' => 250],
            [['dimensoes_selecionadas'], 'safe'], // Propriedade virtual para formulário
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'modulo' => 'Nome do Módulo',
            'path' => 'Caminho/URL',
            'status' => 'Status Ativo',
            'dimensoes_selecionadas' => 'Dimensões Associadas',
        ];
    }

    /**
     * Inicializa propriedades após instanciação
     */
    public function init()
    {
        parent::init();
        if ($this->dimensoes_selecionadas === null) {
            $this->dimensoes_selecionadas = [];
        }
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
     * Retorna IDs das dimensões associadas ao módulo
     */
    public function getDimensoesSelecionadasIds()
    {
        return ArrayHelper::getColumn($this->dimensoesIndicadores, 'id_dimensao');
    }

    /**
     * Salva as associações entre módulo e dimensões
     */
    public function saveDimensoesAssociation($dimensoes_ids = null)
    {
        if ($dimensoes_ids === null) {
            $dimensoes_ids = $this->dimensoes_selecionadas;
        }

        // Garante que seja array
        if (!is_array($dimensoes_ids)) {
            $dimensoes_ids = $dimensoes_ids ? [$dimensoes_ids] : [];
        }

        // Remove valores vazios
        $dimensoes_ids = array_filter($dimensoes_ids, function($value) {
            return !empty($value) && is_numeric($value);
        });

        // Remove associações existentes
        ManySysModulosHasManyIndDimensoesIndicadores::deleteAll(['id_sys_modulos' => $this->id]);

        // Adiciona novas associações
        if (!empty($dimensoes_ids)) {
            foreach ($dimensoes_ids as $dimensao_id) {
                $association = new ManySysModulosHasManyIndDimensoesIndicadores();
                $association->id_sys_modulos = $this->id;
                $association->id_dimensao_ind_dimensoes_indicadores = (int)$dimensao_id;
                
                if (!$association->save()) {
                    Yii::error("Erro ao salvar associação: " . print_r($association->errors, true));
                }
            }
        }
    }

    /**
     * After find - carrega dimensões selecionadas
     */
    public function afterFind()
    {
        parent::afterFind();
        // Carrega os IDs das dimensões associadas na propriedade virtual
        $this->dimensoes_selecionadas = $this->getDimensoesSelecionadasIds();
    }

    /**
     * Before save - prepara dados se necessário
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        
        // Garante que dimensoes_selecionadas seja um array válido
        if (!is_array($this->dimensoes_selecionadas)) {
            $this->dimensoes_selecionadas = $this->dimensoes_selecionadas ? [$this->dimensoes_selecionadas] : [];
        }
        
        return true;
    }

    /**
     * After save - salva associações com dimensões
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        // Salva as associações apenas se o modelo foi salvo com sucesso
        if ($this->id) {
            $this->saveDimensoesAssociation();
        }
    }

    /**
     * Before delete - limpa associações
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        
        // Remove todas as associações antes de deletar o módulo
        ManySysModulosHasManyIndDimensoesIndicadores::deleteAll(['id_sys_modulos' => $this->id]);
        
        return true;
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