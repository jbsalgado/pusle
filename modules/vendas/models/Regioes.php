<?php

namespace app\modules\vendas\models;

use Yii;

/**
 * This is the model class for table "prest_regioes".
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $nome
 * @property string|null $descricao
 * @property string|null $cor_identificacao
 * @property bool $ativo
 * @property string $data_criacao
 *
 * @property PrestClientes[] $prestClientes
 * @property PrestUsuarios $usuario
 */
class Regioes extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_regioes';
    }

    /**
     * Gera UUID antes de salvar se for novo registro
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && empty($this->id)) {
                try {
                    $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                    $this->id = $uuid;
                } catch (\Exception $e) {
                    if (function_exists('uuid_create')) {
                        $this->id = uuid_create(UUID_TYPE_RANDOM);
                    } else {
                        $this->id = sprintf(
                            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000,
                            mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descricao', 'cor_identificacao'], 'default', 'value' => null],
            [['ativo'], 'default', 'value' => 1],
            [['usuario_id', 'nome'], 'required'],
            [['id', 'usuario_id', 'descricao'], 'string'],
            [['ativo'], 'boolean'],
            [['data_criacao'], 'safe'],
            [['nome'], 'string', 'max' => 100],
            [['cor_identificacao'], 'string', 'max' => 7],
            [['id'], 'unique'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => PrestUsuarios::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuario ID',
            'nome' => 'Nome',
            'descricao' => 'Descricao',
            'cor_identificacao' => 'Cor Identificacao',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data Criacao',
        ];
    }

    /**
     * Gets query for [[PrestClientes]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestClientesQuery
     */
    public function getPrestClientes()
    {
        return $this->hasMany(PrestClientes::class, ['regiao_id' => 'id']);
    }

    /**
     * Gets query for [[Usuario]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestUsuariosQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(PrestUsuarios::class, ['id' => 'usuario_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\query\RegioesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\vendas\query\RegioesQuery(get_called_class());
    }

}
