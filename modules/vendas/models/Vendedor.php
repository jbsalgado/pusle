<?php

namespace app\modules\vendas\models;

use Yii;

/**
 * This is the model class for table "prest_vendedores".
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $nome_completo
 * @property string|null $cpf
 * @property string|null $telefone
 * @property string|null $email
 * @property float $percentual_comissao
 * @property bool $ativo
 * @property string|null $data_admissao
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property PrestComissoes[] $prestComissoes
 * @property PrestUsuarios $usuario
 */
class Vendedor extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_vendedores';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cpf', 'telefone', 'email', 'data_admissao'], 'default', 'value' => null],
            [['percentual_comissao'], 'default', 'value' => 0.00],
            [['ativo'], 'default', 'value' => 1],
            [['id', 'usuario_id', 'nome_completo'], 'required'],
            [['id', 'usuario_id'], 'string'],
            [['percentual_comissao'], 'number'],
            [['ativo'], 'boolean'],
            [['data_admissao', 'data_criacao', 'data_atualizacao'], 'safe'],
            [['nome_completo'], 'string', 'max' => 150],
            [['cpf'], 'string', 'max' => 11],
            [['telefone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
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
            'nome_completo' => 'Nome Completo',
            'cpf' => 'Cpf',
            'telefone' => 'Telefone',
            'email' => 'Email',
            'percentual_comissao' => 'Percentual Comissao',
            'ativo' => 'Ativo',
            'data_admissao' => 'Data Admissao',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * Gets query for [[PrestComissoes]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestComissoesQuery
     */
    public function getPrestComissoes()
    {
        return $this->hasMany(PrestComissoes::class, ['colaborador_id' => 'id']);
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
     * @return \app\modules\vendas\query\VendedorQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\vendas\query\VendedorQuery(get_called_class());
    }

}
