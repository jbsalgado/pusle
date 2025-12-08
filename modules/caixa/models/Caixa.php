<?php
namespace app\modules\caixa\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Colaborador;

/**
 * ============================================================================================================
 * Model: Caixa
 * ============================================================================================================
 * Tabela: prest_caixa
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string|null $colaborador_id
 * @property string $data_abertura
 * @property string|null $data_fechamento
 * @property float $valor_inicial
 * @property float|null $valor_final
 * @property float|null $valor_esperado
 * @property float|null $diferenca
 * @property string $status (ABERTO, FECHADO, CANCELADO)
 * @property string|null $observacoes
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 * @property Colaborador|null $colaborador
 * @property CaixaMovimentacao[] $movimentacoes
 */
class Caixa extends ActiveRecord
{
    const STATUS_ABERTO = 'ABERTO';
    const STATUS_FECHADO = 'FECHADO';
    const STATUS_CANCELADO = 'CANCELADO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_caixa';
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
            [['usuario_id', 'valor_inicial'], 'required'],
            [['usuario_id', 'colaborador_id', 'status'], 'string'],
            [['valor_inicial', 'valor_final', 'valor_esperado', 'diferenca'], 'number', 'min' => 0],
            [['data_abertura', 'data_fechamento'], 'safe'],
            [['observacoes'], 'string'],
            [['status'], 'in', 'range' => [self::STATUS_ABERTO, self::STATUS_FECHADO, self::STATUS_CANCELADO]],
            [['status'], 'default', 'value' => self::STATUS_ABERTO],
            [['valor_inicial'], 'default', 'value' => 0],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['colaborador_id'], 'exist', 'skipOnError' => true, 'targetClass' => Colaborador::class, 'targetAttribute' => ['colaborador_id' => 'id']],
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
            'colaborador_id' => 'Colaborador',
            'data_abertura' => 'Data de Abertura',
            'data_fechamento' => 'Data de Fechamento',
            'valor_inicial' => 'Valor Inicial',
            'valor_final' => 'Valor Final',
            'valor_esperado' => 'Valor Esperado',
            'diferenca' => 'Diferença',
            'status' => 'Status',
            'observacoes' => 'Observações',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Relação com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Relação com Colaborador
     */
    public function getColaborador()
    {
        return $this->hasOne(Colaborador::class, ['id' => 'colaborador_id']);
    }

    /**
     * Relação com Movimentações
     */
    public function getMovimentacoes()
    {
        return $this->hasMany(CaixaMovimentacao::class, ['caixa_id' => 'id']);
    }

    /**
     * Calcula o valor esperado do caixa
     * @return float
     */
    public function calcularValorEsperado()
    {
        $entradas = CaixaMovimentacao::find()
            ->where(['caixa_id' => $this->id, 'tipo' => CaixaMovimentacao::TIPO_ENTRADA])
            ->sum('valor') ?: 0;

        $saidas = CaixaMovimentacao::find()
            ->where(['caixa_id' => $this->id, 'tipo' => CaixaMovimentacao::TIPO_SAIDA])
            ->sum('valor') ?: 0;

        return $this->valor_inicial + $entradas - $saidas;
    }

    /**
     * Verifica se o caixa está aberto
     * @return bool
     */
    public function isAberto()
    {
        return $this->status === self::STATUS_ABERTO;
    }

    /**
     * Verifica se o caixa está fechado
     * @return bool
     */
    public function isFechado()
    {
        return $this->status === self::STATUS_FECHADO;
    }

    /**
     * Verifica se o caixa foi aberto hoje
     * @return bool
     */
    public function isAbertoHoje()
    {
        if (!$this->data_abertura) {
            return false;
        }
        
        $dataAbertura = new \DateTime($this->data_abertura);
        $hoje = new \DateTime('today');
        
        return $dataAbertura->format('Y-m-d') === $hoje->format('Y-m-d');
    }

    /**
     * Verifica se o caixa foi aberto em data anterior (não é de hoje)
     * @return bool
     */
    public function isAbertoDiaAnterior()
    {
        if (!$this->isAberto() || !$this->data_abertura) {
            return false;
        }
        
        return !$this->isAbertoHoje();
    }

    /**
     * Fecha o caixa automaticamente
     * @param string|null $observacoes Observações sobre o fechamento
     * @return bool
     */
    public function fecharAutomaticamente($observacoes = null)
    {
        if (!$this->isAberto()) {
            return false;
        }

        $this->valor_esperado = $this->calcularValorEsperado();
        $this->valor_final = $this->valor_esperado;
        $this->diferenca = 0;
        $this->data_fechamento = date('Y-m-d H:i:s');
        $this->status = self::STATUS_FECHADO;
        
        if ($observacoes) {
            $this->observacoes = ($this->observacoes ? $this->observacoes . "\n" : '') . $observacoes;
        }

        return $this->save(false);
    }
}

