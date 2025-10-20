<?php 

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;

/* ============================================================================================================
 * Model: Configuracao
 * ============================================================================================================
 * Tabela: prest_configuracoes
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $nome_loja
 * @property string $logo_path
 * @property string $cor_primaria
 * @property string $cor_secundaria
 * @property boolean $catalogo_publico
 * @property boolean $aceita_orcamentos
 * @property string $whatsapp
 * @property string $instagram
 * @property string $facebook
 * @property string $endereco_completo
 * @property string $mensagem_boas_vindas
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 */
class Configuracao extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_configuracoes';
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
            [['usuario_id'], 'required'],
            [['usuario_id'], 'string'],
            [['catalogo_publico', 'aceita_orcamentos'], 'boolean'],
            [['endereco_completo', 'mensagem_boas_vindas'], 'string'],
            [['nome_loja'], 'string', 'max' => 150],
            [['logo_path'], 'string', 'max' => 500],
            [['cor_primaria', 'cor_secundaria'], 'string', 'max' => 7],
            [['cor_primaria', 'cor_secundaria'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/'],
            [['whatsapp'], 'string', 'max' => 20],
            [['instagram', 'facebook'], 'string', 'max' => 100],
            [['usuario_id'], 'unique'],
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
            'nome_loja' => 'Nome da Loja',
            'logo_path' => 'Logo',
            'cor_primaria' => 'Cor Primária',
            'cor_secundaria' => 'Cor Secundária',
            'catalogo_publico' => 'Catálogo Público',
            'aceita_orcamentos' => 'Aceita Orçamentos',
            'whatsapp' => 'WhatsApp',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'endereco_completo' => 'Endereço Completo',
            'mensagem_boas_vindas' => 'Mensagem de Boas-Vindas',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Retorna configuração do usuário logado
     */
    public static function getConfiguracaoAtual()
    {
        $usuarioId = Yii::$app->user->id;
        $config = self::findOne(['usuario_id' => $usuarioId]);
        
        if (!$config) {
            // Criar configuração padrão
            $config = new self();
            $config->usuario_id = $usuarioId;
            $config->cor_primaria = '#3B82F6';
            $config->cor_secundaria = '#10B981';
            $config->catalogo_publico = false;
            $config->aceita_orcamentos = true;
            $config->save();
        }
        
        return $config;
    }
}