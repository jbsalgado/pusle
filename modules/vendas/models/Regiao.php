<?php 

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Cliente;
use app\models\Usuario;

/* ============================================================================================================
 * Model: Regiao
 * ============================================================================================================
 * Tabela: prest_regioes
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $nome
 * @property string $descricao
 * @property string $cor_identificacao
 * @property boolean $ativo
 * @property string $data_criacao
 * 
 * @property Usuario $usuario
 * @property Cliente[] $clientes
 */
class Regiao extends ActiveRecord
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
            [['usuario_id', 'nome'], 'required'],
            [['usuario_id'], 'string'],
            [['descricao'], 'string'],
            [['ativo'], 'boolean'],
            [['nome'], 'string', 'max' => 100],
            [['cor_identificacao'], 'string', 'max' => 7],
            [['cor_identificacao'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/'],
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
            'cor_identificacao' => 'Cor de Identificação',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data de Criação',
        ];
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getClientes()
    {
        return $this->hasMany(Cliente::class, ['regiao_id' => 'id']);
    }

    /**
     * Retorna regiões ativas para dropdown
     */
    public static function getListaDropdown($usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        return self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->select(['nome', 'id'])
            ->indexBy('id')
            ->column();
    }
}