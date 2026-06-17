<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\models\Usuario;

/**
 * This is the model class for table "prest_financeiro_mensal".
 *
 * @property int $id
 * @property string $usuario_id
 * @property string $mes_referencia
 * @property float|null $faturamento_total
 * @property float|null $despesas_fixas_total
 * @property float|null $despesas_variaveis_total
 * @property float|null $custo_mercadoria_vendida
 * @property string|null $data_criacao
 * @property string|null $data_atualizacao
 */
class FinanceiroMensal extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_financeiro_mensal';
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
            [['usuario_id', 'mes_referencia'], 'required'],
            [['usuario_id'], 'string', 'max' => 255],
            [['mes_referencia', 'data_criacao', 'data_atualizacao'], 'safe'],
            [['faturamento_total', 'despesas_fixas_total', 'despesas_variaveis_total', 'custo_mercadoria_vendida'], 'number'],
            [['usuario_id', 'mes_referencia'], 'unique', 'targetAttribute' => ['usuario_id', 'mes_referencia'], 'message' => 'Já existe um registro financeiro para este mês.'],
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
            'mes_referencia' => 'Mês de Referência',
            'faturamento_total' => 'Faturamento Total (R$)',
            'despesas_fixas_total' => 'Despesas Fixas (R$)',
            'despesas_variaveis_total' => 'Despesas Variáveis (R$)',
            'custo_mercadoria_vendida' => 'Custo de Mercadoria (CMV)',
            'data_criacao' => 'Data Criação',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Calcula indicadores financeiros com base nos dados
     */
    public function getIndicadores()
    {
        $fat = $this->faturamento_total > 0 ? $this->faturamento_total : 1;

        return [
            'taxa_fixa_percentual' => ($this->despesas_fixas_total / $fat) * 100,
            'taxa_variavel_percentual' => ($this->despesas_variaveis_total / $fat) * 100,
            'margem_lucro_real' => (($fat - ($this->despesas_fixas_total + $this->despesas_variaveis_total + $this->custo_mercadoria_vendida)) / $fat) * 100
        ];
    }
}
