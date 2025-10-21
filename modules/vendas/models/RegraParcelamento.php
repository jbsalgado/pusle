<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;

/**
 * ============================================================================================================
 * Model: RegraParcelamento
 * ============================================================================================================
 * Tabela: prest_regras_parcelamento
 * 
 * Define as regras de acréscimo por faixa de parcelamento para cada usuário/loja
 * 
 * @property string $id
 * @property string $usuario_id
 * @property integer $min_parcelas
 * @property integer $max_parcelas
 * @property float $percentual_acrescimo
 * @property string $descricao
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 */
class RegraParcelamento extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_regras_parcelamento';
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
            [['usuario_id', 'min_parcelas', 'max_parcelas', 'percentual_acrescimo'], 'required'],
            [['usuario_id'], 'string', 'max' => 36],
            [['min_parcelas', 'max_parcelas'], 'integer', 'min' => 1],
            [['percentual_acrescimo'], 'number', 'min' => 0, 'max' => 999.99],
            [['descricao'], 'string', 'max' => 255],
            
            // Validação personalizada: min_parcelas deve ser menor ou igual a max_parcelas
            ['max_parcelas', 'compare', 'compareAttribute' => 'min_parcelas', 'operator' => '>=', 
                'message' => 'O número máximo de parcelas deve ser maior ou igual ao mínimo.'],
            
            // Verifica se o usuário existe
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 
                'targetAttribute' => ['usuario_id' => 'id']],
            
            // Validação para evitar sobreposição de faixas do mesmo usuário
            ['min_parcelas', 'validarSobreposicaoFaixas'],
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
            'min_parcelas' => 'Parcelas Mínimas',
            'max_parcelas' => 'Parcelas Máximas',
            'percentual_acrescimo' => 'Percentual de Acréscimo (%)',
            'descricao' => 'Descrição',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Valida se não há sobreposição de faixas de parcelas para o mesmo usuário
     */
    public function validarSobreposicaoFaixas($attribute, $params)
    {
        $query = self::find()
            ->where(['usuario_id' => $this->usuario_id])
            ->andWhere([
                'or',
                // Nova faixa começa dentro de uma faixa existente
                ['and', 
                    ['<=', 'min_parcelas', $this->min_parcelas],
                    ['>=', 'max_parcelas', $this->min_parcelas]
                ],
                // Nova faixa termina dentro de uma faixa existente
                ['and',
                    ['<=', 'min_parcelas', $this->max_parcelas],
                    ['>=', 'max_parcelas', $this->max_parcelas]
                ],
                // Nova faixa engloba completamente uma faixa existente
                ['and',
                    ['>=', 'min_parcelas', $this->min_parcelas],
                    ['<=', 'max_parcelas', $this->max_parcelas]
                ],
            ]);

        // Se estiver editando, exclui o próprio registro da validação
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', $this->id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'Já existe uma regra que se sobrepõe a esta faixa de parcelas.');
        }
    }

    /**
     * Retorna o relacionamento com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Busca a regra aplicável para um número específico de parcelas
     * 
     * @param string $usuarioId ID do usuário
     * @param integer $numeroParcelas Número de parcelas da venda
     * @return RegraParcelamento|null
     */
    public static function buscarRegraAplicavel($usuarioId, $numeroParcelas)
    {
        return self::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['<=', 'min_parcelas', $numeroParcelas])
            ->andWhere(['>=', 'max_parcelas', $numeroParcelas])
            ->one();
    }

    /**
     * Calcula o valor com acréscimo baseado na regra
     * 
     * @param float $valorBase Valor base da venda
     * @return float Valor com acréscimo aplicado
     */
    public function calcularValorComAcrescimo($valorBase)
    {
        $acrescimo = ($valorBase * $this->percentual_acrescimo) / 100;
        return $valorBase + $acrescimo;
    }

    /**
     * Retorna todas as regras de um usuário ordenadas por faixa
     * 
     * @param string $usuarioId ID do usuário
     * @return RegraParcelamento[]
     */
    public static function buscarRegrasUsuario($usuarioId)
    {
        return self::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['min_parcelas' => SORT_ASC])
            ->all();
    }

    /**
     * Formata o percentual para exibição
     * 
     * @return string
     */
    public function getPercentualFormatado()
    {
        return number_format($this->percentual_acrescimo, 2, ',', '.') . '%';
    }

    /**
     * Retorna a descrição da faixa de parcelas
     * 
     * @return string
     */
    public function getFaixaParcelas()
    {
        if ($this->min_parcelas == $this->max_parcelas) {
            return $this->min_parcelas . 'x';
        }
        return $this->min_parcelas . 'x a ' . $this->max_parcelas . 'x';
    }
}