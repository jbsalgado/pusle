<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Produto;
use app\models\Usuario;


/* ============================================================================================================
 * Model: Categoria
 * ============================================================================================================
 * Tabela: prest_categorias
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $nome
 * @property string $descricao
 * @property boolean $ativo
 * @property integer $ordem
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 * @property Produto[] $produtos
 */
class Categoria extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_categorias';
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
                'updatedAtAttribute' => 'data_atualizacao',
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
            [['usuario_id', 'nome'], 'required'],
            [['usuario_id'], 'string'],
            [['descricao'], 'string'],
            [['ativo'], 'boolean'],
            [['ordem'], 'integer'],
            [['ordem'], 'default', 'value' => 0],
            [['nome'], 'string', 'max' => 100],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'nome' => 'Nome',
            'descricao' => 'Descrição',
            'ativo' => 'Ativo',
            'ordem' => 'Ordem de Exibição',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getProdutos()
    {
        return $this->hasMany(Produto::class, ['categoria_id' => 'id']);
    }

    /**
     * Conta produtos na categoria
     */
    public function getTotalProdutos()
    {
        return $this->getProdutos()->count();
    }

    /**
     * Retorna categorias ativas para dropdown
     */
    public static function getListaDropdown($usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        return self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->select(['nome', 'id'])
            ->indexBy('id')
            ->orderBy(['ordem' => SORT_ASC, 'nome' => SORT_ASC])
            ->column();
    }
}