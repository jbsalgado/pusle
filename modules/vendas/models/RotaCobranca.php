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
    public function rules()
    {
        return [
            [['periodo_id', 'cobrador_id', 'usuario_id', 'nome_rota'], 'required'],
            [['periodo_id', 'cobrador_id', 'usuario_id'], 'string'],
            [['dia_semana', 'ordem_execucao'], 'integer'],
            [['dia_semana'], 'in', 'range' => [0, 1, 2, 3, 4, 5, 6]],
            [['ordem_execucao'], 'default', 'value' => 0],
            [['nome_rota'], 'string', 'max' => 100],
            [['descricao'], 'string'],
            [['periodo_id'], 'exist', 'skipOnError' => true, 'targetClass' => PeriodoCobranca::class, 'targetAttribute' => ['periodo_id' => 'id']],
            [['cobrador_id'], 'exist', 'skipOnError' => true, 'targetClass' => Colaborador::class, 'targetAttribute' => ['cobrador_id' => 'id']],
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