<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\models\Usuario;

/**
 * This is the model class for table "prest_taxas_entrega".
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $cidade
 * @property string $bairro
 * @property string $cep
 * @property float $valor
 * @property float|null $valor_minimo_frete_gratis
 * @property string|null $observacoes
 * @property bool $ativo
 * @property string $data_criacao
 * @property string $data_atualizacao
 */
class TaxaEntrega extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_taxas_entrega';
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
            [['usuario_id', 'valor'], 'required'],
            [['usuario_id', 'observacoes'], 'string'],
            [['valor', 'valor_minimo_frete_gratis'], 'number', 'min' => 0],
            [['ativo'], 'boolean'],
            [['cidade', 'bairro'], 'string', 'max' => 100],
            [['cep'], 'string', 'max' => 10],
            [['porte'], 'string', 'max' => 1],
            [['porte'], 'default', 'value' => 'P'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
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
            'cidade' => 'Cidade',
            'bairro' => 'Bairro',
            'cep' => 'CEP',
            'porte' => 'Porte/Volume',
            'valor' => 'Valor da Taxa (R$)',
            'valor_minimo_frete_gratis' => 'Frete Grátis acima de (R$)',
            'observacoes' => 'Observações Internas',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data Criação',
            'data_atualizacao' => 'Data Atualização',
        ];
    }

    /**
     * Busca a regra de taxa de entrega mais específica.
     * Prioridade: CEP > Bairro+Cidade > Bairro > Cidade.
     * 
     * @return self|null
     */
    public static function findRegra($usuarioId, $cidade = null, $bairro = null, $cep = null, $porte = 'P')
    {
        $baseQuery = self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true, 'porte' => $porte]);

        // 1. Busca por CEP exato
        if ($cep) {
            $limpoCep = preg_replace('/[^0-9]/', '', $cep);
            $regra = (clone $baseQuery)
                ->andWhere(['cep' => [$cep, $limpoCep]])
                ->one();
            if ($regra) return $regra;
        }

        // 2. Busca por Bairro + Cidade
        if ($bairro && $cidade) {
            $regra = (clone $baseQuery)
                ->andWhere(['ilike', 'bairro', trim($bairro)])
                ->andWhere(['ilike', 'cidade', trim($cidade)])
                ->one();
            if ($regra) return $regra;
        }

        // 3. Busca por Bairro apenas
        if ($bairro) {
            $regra = (clone $baseQuery)
                ->andWhere(['ilike', 'bairro', trim($bairro)])
                ->one();
            if ($regra) return $regra;
        }

        // 4. Busca por Cidade apenas
        if ($cidade) {
            $regra = (clone $baseQuery)
                ->andWhere(['ilike', 'cidade', trim($cidade)])
                ->one();
            if ($regra) return $regra;
        }

        return null; 
    }

    /**
     * Mantido para retrocompatibilidade
     */
    public static function findTaxa($usuarioId, $cidade = null, $bairro = null, $cep = null, $porte = 'P')
    {
        $regra = self::findRegra($usuarioId, $cidade, $bairro, $cep, $porte);
        return $regra ? (float)$regra->valor : 0.00;
    }
}
