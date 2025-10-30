<?php
/**
 * Model: Modulo
 * Localização: app/models/Modulo.php
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Modulo model
 *
 * @property string $id
 * @property string $codigo
 * @property string $nome
 * @property string $descricao
 * @property string $icone
 * @property string $cor
 * @property string $rota
 * @property boolean $ativo
 * @property integer $ordem
 * @property string $data_criacao
 * @property string $data_atualizacao
 */
class Modulo extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sis_modulos';
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
            [['codigo', 'nome', 'rota'], 'required'],
            [['codigo'], 'string', 'max' => 50],
            [['codigo'], 'unique'],
            [['nome'], 'string', 'max' => 100],
            [['descricao'], 'string'],
            [['icone'], 'string', 'max' => 50],
            [['cor'], 'string', 'max' => 20],
            [['rota'], 'string', 'max' => 100],
            [['ativo'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['ordem'], 'integer'],
            [['ordem'], 'default', 'value' => 0],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'codigo' => 'Código',
            'nome' => 'Nome',
            'descricao' => 'Descrição',
            'icone' => 'Ícone',
            'cor' => 'Cor',
            'rota' => 'Rota',
            'ativo' => 'Ativo',
            'ordem' => 'Ordem',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Busca módulos ativos ordenados
     */
    public static function getModulosAtivos()
    {
        return self::find()
            ->where(['ativo' => true])
            ->orderBy(['ordem' => SORT_ASC, 'nome' => SORT_ASC])
            ->all();
    }

    /**
     * Busca módulo por código
     */
    public static function findByCodigo($codigo)
    {
        return self::findOne(['codigo' => $codigo, 'ativo' => true]);
    }
}