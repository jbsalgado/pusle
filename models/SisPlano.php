<?php
/**
 * Model: Plano
 * Localização: app/models/Plano.php
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Plano model
 *
 * @property string $id
 * @property string $nome
 * @property string $descricao
 * @property string $tipo
 * @property float $valor
 * @property integer $dias_duracao
 * @property integer $dias_trial
 * @property boolean $ativo
 * @property array $recursos
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Assinatura[] $assinaturas
 * @property Modulo[] $modulos
 */
class SisPlano extends ActiveRecord
{
    const TIPO_MENSAL = 'mensal';
    const TIPO_ANUAL = 'anual';
    const TIPO_VITALICIO = 'vitalicio';
    const TIPO_TRIAL = 'trial';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sis_planos';
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
            [['nome', 'tipo', 'valor'], 'required'],
            [['nome'], 'string', 'max' => 100],
            [['descricao'], 'string'],
            [['tipo'], 'string', 'max' => 20],
            [['tipo'], 'in', 'range' => [
                self::TIPO_MENSAL,
                self::TIPO_ANUAL,
                self::TIPO_VITALICIO,
                self::TIPO_TRIAL
            ]],
            [['valor'], 'number', 'min' => 0],
            [['dias_duracao', 'dias_trial'], 'integer', 'min' => 0],
            [['ativo'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['recursos'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nome' => 'Nome',
            'descricao' => 'Descrição',
            'tipo' => 'Tipo',
            'valor' => 'Valor',
            'dias_duracao' => 'Dias de Duração',
            'dias_trial' => 'Dias de Trial',
            'ativo' => 'Ativo',
            'recursos' => 'Recursos',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Relacionamento com Assinaturas
     */
    public function getAssinaturas()
    {
        return $this->hasMany(Assinatura::class, ['plano_id' => 'id']);
    }

    /**
     * Relacionamento com Módulos (através de plano_modulos)
     */
    public function getModulos()
    {
        return $this->hasMany(Modulo::class, ['id' => 'modulo_id'])
            ->viaTable('sys_plano_modulos', ['plano_id' => 'id']);
    }

    /**
     * Retorna array de tipos disponíveis
     */
    public static function getTipos()
    {
        return [
            self::TIPO_TRIAL => 'Trial',
            self::TIPO_MENSAL => 'Mensal',
            self::TIPO_ANUAL => 'Anual',
            self::TIPO_VITALICIO => 'Vitalício',
        ];
    }

    /**
     * Retorna label do tipo
     */
    public function getTipoLabel()
    {
        $tipos = self::getTipos();
        return $tipos[$this->tipo] ?? $this->tipo;
    }

    /**
     * Busca planos ativos
     */
    public static function getPlanosAtivos()
    {
        return self::find()
            ->where(['ativo' => true])
            ->orderBy(['valor' => SORT_ASC])
            ->all();
    }

    /**
     * Busca plano trial
     */
    public static function getPlanoTrial()
    {
        return self::findOne(['tipo' => self::TIPO_TRIAL, 'ativo' => true]);
    }

    /**
     * Verifica se é plano gratuito
     */
    public function isGratuito()
    {
        return $this->valor == 0 || $this->tipo === self::TIPO_TRIAL;
    }

    /**
     * Verifica se é vitalício
     */
    public function isVitalicio()
    {
        return $this->tipo === self::TIPO_VITALICIO;
    }

    /**
     * Retorna valor formatado
     */
    public function getValorFormatado()
    {
        if ($this->valor == 0) {
            return 'Gratuito';
        }
        
        return 'R$ ' . number_format($this->valor, 2, ',', '.');
    }

    /**
     * Retorna descrição da duração
     */
    public function getDuracaoDescricao()
    {
        if ($this->tipo === self::TIPO_VITALICIO) {
            return 'Acesso Vitalício';
        }
        
        if (!$this->dias_duracao) {
            return 'Sem limite';
        }
        
        if ($this->dias_duracao >= 365) {
            $anos = floor($this->dias_duracao / 365);
            return $anos . ' ' . ($anos == 1 ? 'ano' : 'anos');
        }
        
        if ($this->dias_duracao >= 30) {
            $meses = floor($this->dias_duracao / 30);
            return $meses . ' ' . ($meses == 1 ? 'mês' : 'meses');
        }
        
        return $this->dias_duracao . ' dias';
    }

    /**
     * Retorna quantidade de módulos inclusos
     */
    public function getQuantidadeModulos()
    {
        return $this->getModulos()->count();
    }

    /**
     * Retorna recursos em formato legível
     */
    public function getRecursosFormatados()
    {
        if (empty($this->recursos)) {
            return [];
        }
        
        // Se recursos está como string JSON, decodifica
        if (is_string($this->recursos)) {
            return json_decode($this->recursos, true) ?? [];
        }
        
        return $this->recursos;
    }

    /**
     * Verifica se plano tem um recurso específico
     */
    public function temRecurso($recurso)
    {
        $recursos = $this->getRecursosFormatados();
        return isset($recursos[$recurso]);
    }

    /**
     * Retorna valor de um recurso
     */
    public function getRecurso($recurso, $default = null)
    {
        $recursos = $this->getRecursosFormatados();
        return $recursos[$recurso] ?? $default;
    }
}