<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Model class for table "sis_plano_modulos"
 *
 * Tabela de junção (pivot) entre planos e módulos
 *
 * @property string $id UUID
 * @property string $plano_id UUID
 * @property string $modulo_id UUID
 * @property string|null $data_criacao
 *
 * @property SisPlanos $plano
 * @property SisModulos $modulo
 */
class SisPlanoModulos extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sis_plano_modulos';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'data_criacao',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['plano_id', 'modulo_id'], 'required'],
            [['plano_id', 'modulo_id'], 'string'],
            [['data_criacao'], 'safe'],
            // Garante que não exista duplicata de plano_id + modulo_id
            [['plano_id', 'modulo_id'], 'unique', 
                'targetAttribute' => ['plano_id', 'modulo_id'], 
                'message' => 'Este módulo já está associado a este plano.'
            ],
            [['plano_id'], 'exist', 'skipOnError' => true, 
                'targetClass' => SisPlanos::class, 
                'targetAttribute' => ['plano_id' => 'id']
            ],
            [['modulo_id'], 'exist', 'skipOnError' => true, 
                'targetClass' => SisModulos::class, 
                'targetAttribute' => ['modulo_id' => 'id']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'plano_id' => 'Plano',
            'modulo_id' => 'Módulo',
            'data_criacao' => 'Data de Criação',
        ];
    }

    /**
     * Gets query for [[Plano]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlano()
    {
        return $this->hasOne(SisPlanos::class, ['id' => 'plano_id']);
    }

    /**
     * Gets query for [[Modulo]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getModulo()
    {
        return $this->hasOne(SisModulos::class, ['id' => 'modulo_id']);
    }

    /**
     * Retorna o nome do plano
     * 
     * @return string
     */
    public function getNomePlano()
    {
        return $this->plano ? $this->plano->nome : '-';
    }

    /**
     * Retorna o nome do módulo
     * 
     * @return string
     */
    public function getNomeModulo()
    {
        return $this->modulo ? $this->modulo->nome : '-';
    }

    /**
     * Associa um módulo a um plano
     * 
     * @param string $planoId
     * @param string $moduloId
     * @return bool|SisPlanoModulos
     */
    public static function associar($planoId, $moduloId)
    {
        // Verifica se já existe a associação
        $existe = self::find()
            ->where(['plano_id' => $planoId, 'modulo_id' => $moduloId])
            ->exists();
        
        if ($existe) {
            return false;
        }
        
        $model = new self();
        $model->plano_id = $planoId;
        $model->modulo_id = $moduloId;
        
        if ($model->save()) {
            return $model;
        }
        
        return false;
    }

    /**
     * Desassocia um módulo de um plano
     * 
     * @param string $planoId
     * @param string $moduloId
     * @return bool
     */
    public static function desassociar($planoId, $moduloId)
    {
        $model = self::find()
            ->where(['plano_id' => $planoId, 'modulo_id' => $moduloId])
            ->one();
        
        if ($model) {
            return $model->delete();
        }
        
        return false;
    }

    /**
     * Sincroniza os módulos de um plano
     * Remove associações antigas e cria novas
     * 
     * @param string $planoId
     * @param array $modulosIds Array com IDs dos módulos
     * @return bool
     */
    public static function sincronizarModulos($planoId, array $modulosIds)
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Remove todas as associações antigas
            self::deleteAll(['plano_id' => $planoId]);
            
            // Adiciona as novas associações
            foreach ($modulosIds as $moduloId) {
                $model = new self();
                $model->plano_id = $planoId;
                $model->modulo_id = $moduloId;
                
                if (!$model->save()) {
                    throw new \Exception('Erro ao salvar módulo: ' . json_encode($model->errors));
                }
            }
            
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage());
            return false;
        }
    }

    /**
     * Retorna os IDs dos módulos associados a um plano
     * 
     * @param string $planoId
     * @return array
     */
    public static function getModulosIdsPorPlano($planoId)
    {
        return self::find()
            ->select('modulo_id')
            ->where(['plano_id' => $planoId])
            ->column();
    }

    /**
     * Retorna os módulos associados a um plano (objetos completos)
     * 
     * @param string $planoId
     * @return array|SisModulos[]
     */
    public static function getModulosPorPlano($planoId)
    {
        return SisModulos::find()
            ->innerJoin('sis_plano_modulos', 'sis_plano_modulos.modulo_id = sis_modulos.id')
            ->where(['sis_plano_modulos.plano_id' => $planoId])
            ->orderBy(['sis_modulos.nome' => SORT_ASC])
            ->all();
    }

    /**
     * Retorna os planos que possuem um determinado módulo
     * 
     * @param string $moduloId
     * @return array|SisPlanos[]
     */
    public static function getPlanosPorModulo($moduloId)
    {
        return SisPlanos::find()
            ->innerJoin('sis_plano_modulos', 'sis_plano_modulos.plano_id = sis_planos.id')
            ->where(['sis_plano_modulos.modulo_id' => $moduloId])
            ->orderBy(['sis_planos.nome' => SORT_ASC])
            ->all();
    }

    /**
     * Verifica se um plano possui um módulo específico
     * 
     * @param string $planoId
     * @param string $moduloId
     * @return bool
     */
    public static function planoTemModulo($planoId, $moduloId)
    {
        return self::find()
            ->where(['plano_id' => $planoId, 'modulo_id' => $moduloId])
            ->exists();
    }

    /**
     * Conta quantos módulos um plano possui
     * 
     * @param string $planoId
     * @return int
     */
    public static function contarModulosDePlano($planoId)
    {
        return self::find()
            ->where(['plano_id' => $planoId])
            ->count();
    }

    /**
     * Conta quantos planos usam um módulo
     * 
     * @param string $moduloId
     * @return int
     */
    public static function contarPlanosDeModulo($moduloId)
    {
        return self::find()
            ->where(['modulo_id' => $moduloId])
            ->count();
    }

    /**
     * Duplica os módulos de um plano para outro
     * 
     * @param string $planoOrigemId
     * @param string $planoDestinoId
     * @return bool
     */
    public static function duplicarModulos($planoOrigemId, $planoDestinoId)
    {
        $modulosIds = self::getModulosIdsPorPlano($planoOrigemId);
        return self::sincronizarModulos($planoDestinoId, $modulosIds);
    }

    /**
     * Before save - gera UUID se necessário
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && empty($this->id)) {
                $this->id = new Expression('gen_random_uuid()');
            }
            return true;
        }
        return false;
    }
}