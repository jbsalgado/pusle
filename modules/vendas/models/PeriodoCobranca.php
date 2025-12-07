<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\models\Usuario;
use app\modules\vendas\models\RotaCobranca;
use app\modules\vendas\models\CarteiraCobranca;

/**
 * ============================================================================================================
 * Model: PeriodoCobranca
 * ============================================================================================================
 * Tabela: prest_periodos_cobranca
 * 
 * @property string $id
 * @property string $usuario_id
 * @property integer $mes_referencia
 * @property integer $ano_referencia
 * @property string $descricao
 * @property string $data_inicio
 * @property string $data_fim
 * @property string $status
 * @property string $data_criacao
 * 
 * @property Usuario $usuario
 * @property CarteiraCobranca[] $carteiras
 * @property RotaCobranca[] $rotas
 */
class PeriodoCobranca extends ActiveRecord
{
    const STATUS_ABERTO = 'ABERTO';
    const STATUS_EM_COBRANCA = 'EM_COBRANCA';
    const STATUS_FECHADO = 'FECHADO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_periodos_cobranca';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id', 'mes_referencia', 'ano_referencia', 'data_inicio', 'data_fim'], 'required'],
            [['usuario_id'], 'string'],
            [['mes_referencia'], 'integer', 'min' => 1, 'max' => 12],
            [['ano_referencia'], 'integer', 'min' => 2020, 'max' => 2099],
            [['data_inicio', 'data_fim'], 'date', 'format' => 'php:Y-m-d'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_ABERTO, self::STATUS_EM_COBRANCA, self::STATUS_FECHADO]],
            [['status'], 'default', 'value' => self::STATUS_ABERTO],
            [['descricao'], 'string', 'max' => 100],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            // Validação: periodo único por usuário/mes/ano
            [['mes_referencia'], 'unique', 'targetAttribute' => ['usuario_id', 'mes_referencia', 'ano_referencia']],
            // Validação: data_fim deve ser maior ou igual a data_inicio
            [['data_fim'], 'compare', 'compareAttribute' => 'data_inicio', 'operator' => '>=', 'message' => 'A data de fim deve ser maior ou igual à data de início.'],
        ];
    }

    /**
     * Validações customizadas após validação básica
     */
    public function afterValidate()
    {
        parent::afterValidate();
        
        // Armazena se havia erros antes da validação de cruzamento de anos
        $tinhaErrosAntes = $this->hasErrors();
        
        // Validação adicional: alerta se período cruzar anos (não bloqueia, apenas adiciona warning)
        if (!$tinhaErrosAntes && $this->data_inicio && $this->data_fim) {
            try {
                $dataInicio = new \DateTime($this->data_inicio);
                $dataFim = new \DateTime($this->data_fim);
                $anoInicio = (int)$dataInicio->format('Y');
                $anoFim = (int)$dataFim->format('Y');
                
                if ($anoInicio != $anoFim) {
                    // Adiciona warning apenas como informação (não bloqueia salvamento)
                    // Usamos addError mas não retornamos false, apenas para mostrar na tela
                    Yii::warning("Período cruza anos: {$anoInicio} → {$anoFim}", __METHOD__);
                    // Não adiciona erro aqui para não bloquear - apenas loga o warning
                }
            } catch (\Exception $e) {
                // Ignora erros de parsing de data (já foram validados nas rules)
            }
        }
        
        // Retorna true se não havia erros antes (permite salvar mesmo com warnings)
        return !$tinhaErrosAntes;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'mes_referencia' => 'Mês',
            'ano_referencia' => 'Ano',
            'descricao' => 'Descrição',
            'data_inicio' => 'Data de Início',
            'data_fim' => 'Data de Fim',
            'status' => 'Status',
            'data_criacao' => 'Data de Criação',
        ];
    }

    /**
     * Antes de salvar, gera UUID e descrição automática se não informada
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Gera UUID se for um novo registro
            if ($insert && empty($this->id)) {
                $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                $this->id = $uuid;
            }
            
            // Gera descrição automática se não informada
            if (empty($this->descricao)) {
                $meses = [
                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                ];
                $this->descricao = $meses[$this->mes_referencia] . '/' . $this->ano_referencia;
            }
            
            return true;
        }
        return false;
    }

    /**
     * Retorna total de clientes distribuídos
     */
    public function getTotalClientes()
    {
        return $this->getCarteiras()->count();
    }

    /**
     * Retorna total de cobradores alocados
     */
    public function getTotalCobradores()
    {
        return $this->getCarteiras()
            ->select('cobrador_id')
            ->distinct()
            ->count();
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getCarteiras()
    {
        return $this->hasMany(CarteiraCobranca::class, ['periodo_id' => 'id']);
    }

    public function getRotas()
    {
        return $this->hasMany(RotaCobranca::class, ['periodo_id' => 'id']);
    }

    /**
     * Retorna periodo ativo para dropdown
     */
    public static function getListaDropdown($usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        return self::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['!=', 'status', self::STATUS_FECHADO])
            ->select(['descricao', 'id'])
            ->indexBy('id')
            ->orderBy(['ano_referencia' => SORT_DESC, 'mes_referencia' => SORT_DESC])
            ->column();
    }

    /**
     * Retorna período atual (em cobrança ou aberto mais recente)
     */
    public static function getPeriodoAtual($usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        return self::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['status' => [self::STATUS_ABERTO, self::STATUS_EM_COBRANCA]])
            ->orderBy(['ano_referencia' => SORT_DESC, 'mes_referencia' => SORT_DESC])
            ->one();
    }
}