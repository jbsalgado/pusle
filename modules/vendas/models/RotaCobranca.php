<?php 
namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\PeriodoCobranca;
use app\modules\vendas\models\CarteiraCobranca;
/**
 * ============================================================================================================
 * Model: RotaCobranca
 * ============================================================================================================
 * Tabela: prest_rotas_cobranca
 * 
 * @property string $id
 * @property string $periodo_id
 * @property string $cobrador_id
 * @property string $usuario_id
 * @property integer $dia_semana
 * @property string $nome_rota
 * @property string $descricao
 * @property integer $ordem_execucao
 * @property string $data_criacao
 * 
 * @property PeriodoCobranca $periodo
 * @property Colaborador $cobrador
 * @property Usuario $usuario
 * @property CarteiraCobranca[] $carteiras
 */
class RotaCobranca extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_rotas_cobranca';
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
                'updatedAtAttribute' => false, // Não há campo de atualização
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Gera UUID se for um novo registro
            if ($insert && empty($this->id)) {
                // Usa comando SQL direto para gerar UUID no PostgreSQL
                $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                $this->id = $uuid;
            }
            // Converte dia_semana vazio para null
            if ($this->dia_semana === '' || $this->dia_semana === null) {
                $this->dia_semana = null;
            }
            // Garante que ordem_execucao seja um inteiro
            if ($this->ordem_execucao === '' || $this->ordem_execucao === null) {
                $this->ordem_execucao = 0;
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
            [['periodo_id', 'cobrador_id', 'usuario_id', 'nome_rota'], 'required'],
            [['periodo_id', 'cobrador_id', 'usuario_id'], 'string'],
            [['dia_semana', 'ordem_execucao'], 'integer'],
            [['dia_semana'], 'in', 'range' => [0, 1, 2, 3, 4, 5, 6], 'skipOnEmpty' => true],
            [['dia_semana'], 'default', 'value' => null],
            [['ordem_execucao'], 'default', 'value' => 0],
            [['nome_rota'], 'string', 'max' => 100],
            [['descricao'], 'string', 'skipOnEmpty' => true],
            // Validações exist - removido skipOnError para mostrar erros claros
            [['periodo_id'], 'exist', 'targetClass' => PeriodoCobranca::class, 'targetAttribute' => ['periodo_id' => 'id'], 'message' => 'O período selecionado não existe.'],
            [['cobrador_id'], 'exist', 'targetClass' => Colaborador::class, 'targetAttribute' => ['cobrador_id' => 'id'], 'message' => 'O cobrador selecionado não existe.'],
            [['usuario_id'], 'exist', 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id'], 'message' => 'O usuário não existe.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'periodo_id' => 'Período',
            'cobrador_id' => 'Cobrador',
            'usuario_id' => 'Usuário',
            'dia_semana' => 'Dia da Semana',
            'nome_rota' => 'Nome da Rota',
            'descricao' => 'Descrição',
            'ordem_execucao' => 'Ordem de Execução',
            'data_criacao' => 'Data de Criação',
        ];
    }

    /**
     * Retorna nome do dia da semana
     */
    public function getNomeDiaSemana()
    {
        $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        return isset($dias[$this->dia_semana]) ? $dias[$this->dia_semana] : '';
    }

    /**
     * Retorna total de clientes na rota
     */
    public function getTotalClientes()
    {
        return $this->getCarteiras()->count();
    }

    public function getPeriodo()
    {
        return $this->hasOne(PeriodoCobranca::class, ['id' => 'periodo_id']);
    }

    public function getCobrador()
    {
        return $this->hasOne(Colaborador::class, ['id' => 'cobrador_id']);
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getCarteiras()
    {
        return $this->hasMany(CarteiraCobranca::class, ['rota_id' => 'id']);
    }
}