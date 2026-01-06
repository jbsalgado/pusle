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
 * @property string $usuario_id (FK para dono da loja)
 * @property string|null $prest_usuario_login_id (FK para login do colaborador em prest_usuarios)
 * @property string $nome_completo
 * @property string $cpf
 * @property string $telefone
 * @property string $email
 * @property boolean $eh_vendedor
 * @property boolean $eh_cobrador
 * @property boolean $eh_administrador
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
     * @var string|null Senha para acesso ao sistema (virtual)
     */
    public $senha_usuario;

    /**
     * @var boolean Indita se deve conceder/manter acesso ao sistema (virtual)
     */
    public $acesso_sistema;

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
            [['usuario_id', 'prest_usuario_login_id'], 'string'],
            [['eh_vendedor', 'eh_cobrador', 'eh_administrador', 'ativo'], 'boolean'],
            [['percentual_comissao_venda', 'percentual_comissao_cobranca'], 'number', 'min' => 0, 'max' => 100],
            [['percentual_comissao_venda', 'percentual_comissao_cobranca'], 'default', 'value' => 0],
            [['data_admissao'], 'date', 'format' => 'php:Y-m-d'],
            [['observacoes'], 'string'],
            [['nome_completo'], 'string', 'max' => 150],
            [['cpf'], 'string', 'max' => 11],
            [['cpf'], 'match', 'pattern' => '/^[0-9]{11}$/', 'skipOnEmpty' => true],
            // Validação: CPF único por loja (usuario_id) - apenas se CPF estiver preenchido
            [['cpf'], 'validateCpfUnico', 'skipOnEmpty' => true],
            [['telefone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            // prest_usuario_login_id é opcional e só é validado se preenchido (pode ser NULL para colaboradores sem login próprio)
            [['prest_usuario_login_id'], 'exist', 'skipOnError' => true, 'skipOnEmpty' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['prest_usuario_login_id' => 'id']],
            // Pelo menos um papel deve estar marcado
            ['eh_vendedor', 'required', 'when' => function ($model) {
                return !$model->eh_cobrador;
            }, 'message' => 'O colaborador deve ser vendedor e/ou cobrador'],

            // Validação de Acesso ao Sistema
            [['acesso_sistema'], 'boolean'],
            [['senha_usuario'], 'string', 'min' => 6],
            ['senha_usuario', 'required', 'when' => function ($model) {
                // Obrigatório se marcou acesso e é um novo registro OU se marcou acesso e não tem login vinculado ainda
                return $model->acesso_sistema && ($model->isNewRecord || !$model->prest_usuario_login_id);
            }, 'enableClientValidation' => false, 'message' => 'Senha é obrigatória para conceder acesso.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Dono da Loja',
            'prest_usuario_login_id' => 'Login do Colaborador',
            'nome_completo' => 'Nome Completo',
            'cpf' => 'CPF',
            'telefone' => 'Telefone',
            'email' => 'E-mail',
            'eh_vendedor' => 'É Vendedor?',
            'eh_cobrador' => 'É Cobrador?',
            'eh_administrador' => 'É Administrador?',
            'percentual_comissao_venda' => 'Comissão Venda (%)',
            'percentual_comissao_cobranca' => 'Comissão Cobrança (%)',
            'ativo' => 'Ativo',
            'data_admissao' => 'Data de Admissão',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Cadastro',
            'data_criacao' => 'Data de Cadastro',
            'data_atualizacao' => 'Última Atualização',
            'senha_usuario' => 'Senha de Acesso',
            'acesso_sistema' => 'Conceder Acesso ao Sistema?',
        ];
    }

    /**
     * Validação customizada: CPF único por loja (usuario_id)
     */
    public function validateCpfUnico($attribute, $params)
    {
        if (empty($this->cpf) || empty($this->usuario_id)) {
            return;
        }

        $query = self::find()
            ->where(['cpf' => $this->cpf, 'usuario_id' => $this->usuario_id]);

        // Ao editar, exclui o próprio registro
        if (!$this->isNewRecord) {
            $query->andWhere(['!=', 'id', $this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'Este CPF já está cadastrado para esta loja.');
        }
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

    /**
     * Relacionamento com o dono da loja
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Relacionamento com o login do colaborador (se tiver login próprio)
     */
    public function getUsuarioLogin()
    {
        return $this->hasOne(Usuario::class, ['id' => 'prest_usuario_login_id']);
    }

    /**
     * Retorna o ID da loja (sempre o usuario_id, que aponta para o dono)
     */
    public function getLojaId()
    {
        return $this->usuario_id;
    }

    public function afterFind()
    {
        parent::afterFind();
        // Se tem ID de login vinculado, marca como tendo acesso
        $this->acesso_sistema = !empty($this->prest_usuario_login_id);
    }

    /**
     * Verifica se o colaborador tem login próprio
     */
    public function temLoginProprio()
    {
        return $this->prest_usuario_login_id !== null;
    }

    /**
     * Busca colaborador associado ao usuário logado
     * Suporta dois cenários:
     * 1. Colaborador COM login próprio: busca por prest_usuario_login_id
     * 2. Colaborador SEM login próprio (usa login do dono): busca por usuario_id
     * 
     * @return Colaborador|null
     */
    public static function getColaboradorLogado()
    {
        $usuarioLogado = Yii::$app->user->identity;

        if (!$usuarioLogado) {
            return null;
        }

        // Se é dono, não é colaborador
        if ($usuarioLogado->eh_dono_loja === true || $usuarioLogado->eh_dono_loja === 't' || $usuarioLogado->eh_dono_loja === 1) {
            return null;
        }

        // Tenta buscar por prest_usuario_login_id primeiro (colaborador com login próprio)
        $colaborador = static::find()
            ->where(['prest_usuario_login_id' => $usuarioLogado->id])
            ->andWhere(['ativo' => true])
            ->one();

        // Se não encontrou, tenta buscar por usuario_id (colaborador sem login próprio que usa login do dono)
        if (!$colaborador) {
            $colaborador = static::find()
                ->where(['usuario_id' => $usuarioLogado->id])
                ->andWhere(['ativo' => true])
                ->one();
        }

        return $colaborador;
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
