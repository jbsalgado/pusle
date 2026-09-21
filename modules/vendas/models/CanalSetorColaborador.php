<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Usuario;

/**
 * Model CanalSetorColaborador — Associação entre Setor e Colaborador
 *
 * @property string $id
 * @property string $setor_id
 * @property string $colaborador_id
 * @property string $usuario_id
 * @property string $created_at
 *
 * @property CanalSetor $setor
 * @property Colaborador $colaborador
 * @property Usuario $loja
 */
class CanalSetorColaborador extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'prest_canal_setor_colaboradores';
    }

    public function rules(): array
    {
        return [
            [['setor_id', 'colaborador_id', 'usuario_id'], 'required'],
            [['setor_id', 'colaborador_id', 'usuario_id'], 'string'],
            [['setor_id', 'colaborador_id'], 'unique', 'targetAttribute' => ['setor_id', 'colaborador_id']],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'setor_id' => 'Setor',
            'colaborador_id' => 'Colaborador',
            'usuario_id' => 'Loja / Tenant',
            'created_at' => 'Vinculado em',
        ];
    }

    public function getSetor()
    {
        return $this->hasOne(CanalSetor::class, ['id' => 'setor_id']);
    }

    public function getColaborador()
    {
        return $this->hasOne(Colaborador::class, ['id' => 'colaborador_id']);
    }

    public function getLoja()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
}
