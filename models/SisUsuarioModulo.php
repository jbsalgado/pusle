<?php
/**
 * Model: UsuarioModulo
 * Localização: app/models/UsuarioModulo.php
 * 
 * Controla acesso direto de usuários a módulos específicos (sem plano)
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * UsuarioModulo model
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $modulo_id
 * @property string $data_inicio
 * @property string $data_fim
 * @property boolean $ativo
 * @property string $observacoes
 * @property string $data_criacao
 * 
 * @property Usuario $usuario
 * @property Modulo $modulo
 */
class SisUsuarioModulo extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sis_usuario_modulos';
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
                'updatedAtAttribute' => false, // Não tem data_atualizacao
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
            [['usuario_id', 'modulo_id'], 'required'],
            [['usuario_id', 'modulo_id'], 'string'],
            [['data_inicio', 'data_fim'], 'safe'],
            [['data_inicio'], 'default', 'value' => date('Y-m-d')],
            [['ativo'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['observacoes'], 'string'],
            
            // Validação de unicidade
            [['modulo_id'], 'unique', 
                'targetAttribute' => ['usuario_id', 'modulo_id'],
                'message' => 'Este usuário já possui acesso a este módulo.'
            ],
            
            // Validação de datas
            ['data_fim', 'compare', 'compareAttribute' => 'data_inicio', 'operator' => '>=', 
                'message' => 'Data fim deve ser maior ou igual à data início.'],
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
            'modulo_id' => 'Módulo',
            'data_inicio' => 'Data de Início',
            'data_fim' => 'Data de Fim',
            'ativo' => 'Ativo',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
        ];
    }

    /**
     * Relacionamento com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Relacionamento com Modulo
     */
    public function getModulo()
    {
        return $this->hasOne(Modulo::class, ['id' => 'modulo_id']);
    }

    /**
     * Verifica se o acesso está ativo
     */
    public function isAcessoAtivo()
    {
        if (!$this->ativo) {
            return false;
        }

        // Se não tem data_fim, é permanente
        if (!$this->data_fim) {
            return true;
        }

        // Verifica se ainda não expirou
        return strtotime($this->data_fim) >= time();
    }

    /**
     * Verifica se o acesso está expirado
     */
    public function isExpirado()
    {
        if (!$this->data_fim) {
            return false;
        }

        return strtotime($this->data_fim) < time();
    }

    /**
     * Retorna quantidade de dias restantes
     */
    public function getDiasRestantes()
    {
        if (!$this->data_fim) {
            return null; // Permanente
        }

        $hoje = strtotime(date('Y-m-d'));
        $fim = strtotime($this->data_fim);
        
        $dias = ($fim - $hoje) / (60 * 60 * 24);
        
        return (int) $dias;
    }

    /**
     * Concede acesso a um usuário para um módulo
     * 
     * @param string $usuarioId
     * @param string $moduloCodigo
     * @param string|null $dataFim
     * @param string|null $observacoes
     * @return UsuarioModulo|false
     */
    public static function concederAcesso($usuarioId, $moduloCodigo, $dataFim = null, $observacoes = null)
    {
        $modulo = Modulo::findByCodigo($moduloCodigo);
        
        if (!$modulo) {
            return false;
        }

        // Verifica se já existe
        $acesso = self::findOne([
            'usuario_id' => $usuarioId,
            'modulo_id' => $modulo->id,
        ]);

        if ($acesso) {
            // Atualiza se já existe
            $acesso->ativo = true;
            $acesso->data_fim = $dataFim;
            $acesso->observacoes = $observacoes;
        } else {
            // Cria novo
            $acesso = new self();
            $acesso->usuario_id = $usuarioId;
            $acesso->modulo_id = $modulo->id;
            $acesso->data_inicio = date('Y-m-d');
            $acesso->data_fim = $dataFim;
            $acesso->ativo = true;
            $acesso->observacoes = $observacoes;
        }

        return $acesso->save() ? $acesso : false;
    }

    /**
     * Revoga acesso de um usuário a um módulo
     * 
     * @param string $usuarioId
     * @param string $moduloCodigo
     * @return boolean
     */
    public static function revogarAcesso($usuarioId, $moduloCodigo)
    {
        $modulo = Modulo::findByCodigo($moduloCodigo);
        
        if (!$modulo) {
            return false;
        }

        $acesso = self::findOne([
            'usuario_id' => $usuarioId,
            'modulo_id' => $modulo->id,
        ]);

        if (!$acesso) {
            return true; // Já não tem acesso
        }

        $acesso->ativo = false;
        return $acesso->save();
    }

    /**
     * Busca acessos ativos de um usuário
     * 
     * @param string $usuarioId
     * @return UsuarioModulo[]
     */
    public static function getAcessosAtivos($usuarioId)
    {
        return self::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['ativo' => true])
            ->andWhere(['or',
                ['data_fim' => null],
                ['>=', 'data_fim', new Expression('CURRENT_DATE')]
            ])
            ->all();
    }

    /**
     * Busca módulos com acesso direto de um usuário
     * 
     * @param string $usuarioId
     * @return Modulo[]
     */
    public static function getModulosComAcesso($usuarioId)
    {
        return Modulo::find()
            ->joinWith('usuarioModulos')
            ->where([
                'sys_usuario_modulos.usuario_id' => $usuarioId,
                'sys_usuario_modulos.ativo' => true,
            ])
            ->andWhere(['or',
                ['sys_usuario_modulos.data_fim' => null],
                ['>=', 'sys_usuario_modulos.data_fim', new Expression('CURRENT_DATE')]
            ])
            ->all();
    }

    /**
     * Verifica se usuário tem acesso direto a um módulo
     * 
     * @param string $usuarioId
     * @param string $moduloCodigo
     * @return boolean
     */
    public static function verificarAcesso($usuarioId, $moduloCodigo)
    {
        $modulo = Modulo::findByCodigo($moduloCodigo);
        
        if (!$modulo) {
            return false;
        }

        $acesso = self::find()
            ->where([
                'usuario_id' => $usuarioId,
                'modulo_id' => $modulo->id,
                'ativo' => true,
            ])
            ->andWhere(['or',
                ['data_fim' => null],
                ['>=', 'data_fim', new Expression('CURRENT_DATE')]
            ])
            ->exists();

        return $acesso;
    }

    /**
     * Desativa acessos expirados automaticamente
     */
    public static function desativarExpirados()
    {
        return self::updateAll(
            ['ativo' => false],
            ['and',
                ['ativo' => true],
                ['<', 'data_fim', new Expression('CURRENT_DATE')],
                ['not', ['data_fim' => null]]
            ]
        );
    }
}