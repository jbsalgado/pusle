<?php
namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\modules\vendas\models\Venda;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Comissao;
use app\modules\vendas\models\CarteiraCobranca;

/**
 * ============================================================================================================
 * Model: Colaborador (Vendedor/Cobrador)
 * ============================================================================================================
 * Tabela: prest_colaboradores
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $nome_completo
 * @property string $cpf
 * @property string $telefone
 * @property string $email
 * @property boolean $eh_vendedor
 * @property boolean $eh_cobrador
 * @property float $percentual_comissao_venda
 * @property float $percentual_comissao_cobranca
 * @property boolean $ativo
 * @property string $data_admissao
 * @property string $observacoes
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 * @property Venda[] $vendas
 * @property CarteiraCobranca[] $carteirasCobranca
 * @property Comissao[] $comissoes
 */
class Colaborador extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_colaboradores';
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
            [['usuario_id', 'nome_completo'], 'required'],
            [['usuario_id'], 'string'],
            [['eh_vendedor', 'eh_cobrador', 'ativo'], 'boolean'],
            [['percentual_comissao_venda', 'percentual_comissao_cobranca'], 'number', 'min' => 0, 'max' => 100],
            [['percentual_comissao_venda', 'percentual_comissao_cobranca'], 'default', 'value' => 0],
            [['data_admissao'], 'date', 'format' => 'php:Y-m-d'],
            [['observacoes'], 'string'],
            [['nome_completo'], 'string', 'max' => 150],
            [['cpf'], 'string', 'max' => 11],
            [['cpf'], 'match', 'pattern' => '/^[0-9]{11}$/'],
            [['telefone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            // Pelo menos um papel deve estar marcado
            ['eh_vendedor', 'required', 'when' => function($model) {
                return !$model->eh_cobrador;
            }, 'message' => 'O colaborador deve ser vendedor e/ou cobrador'],
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
            'nome_completo' => 'Nome Completo',
            'cpf' => 'CPF',
            'telefone' => 'Telefone',
            'email' => 'E-mail',
            'eh_vendedor' => 'É Vendedor?',
            'eh_cobrador' => 'É Cobrador?',
            'percentual_comissao_venda' => 'Comissão Venda (%)',
            'percentual_comissao_cobranca' => 'Comissão Cobrança (%)',
            'ativo' => 'Ativo',
            'data_admissao' => 'Data de Admissão',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Cadastro',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Retorna papel do colaborador
     */
    public function getPapel()
    {
        if ($this->eh_vendedor && $this->eh_cobrador) {
            return 'Vendedor e Cobrador';
        } elseif ($this->eh_vendedor) {
            return 'Vendedor';
        } elseif ($this->eh_cobrador) {
            return 'Cobrador';
        }
        return 'Indefinido';
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getVendas()
    {
        return $this->hasMany(Venda::class, ['colaborador_vendedor_id' => 'id']);
    }

    public function getCarteirasCobranca()
    {
        return $this->hasMany(CarteiraCobranca::class, ['cobrador_id' => 'id']);
    }

    public function getComissoes()
    {
        return $this->hasMany(Comissao::class, ['colaborador_id' => 'id']);
    }

    /**
     * Retorna vendedores ativos para dropdown
     */
    public static function getListaVendedores($usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        return self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true, 'eh_vendedor' => true])
            ->select(['nome_completo', 'id'])
            ->indexBy('id')
            ->orderBy(['nome_completo' => SORT_ASC])
            ->column();
    }

    /**
     * Retorna cobradores ativos para dropdown
     */
    public static function getListaCobradores($usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        return self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true, 'eh_cobrador' => true])
            ->select(['nome_completo', 'id'])
            ->indexBy('id')
            ->orderBy(['nome_completo' => SORT_ASC])
            ->column();
    }
}
