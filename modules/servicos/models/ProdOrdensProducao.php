<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "prod_ordens_producao".
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $produto_id
 * @property int $status_id
 * @property int $quantidade_planejada
 * @property string $data_inicio
 * @property string $data_previsao_termino
 *
 * @property ProdLotes[] $prodLotes
 * @property CadastEmpresas $empresa
 * @property CadastProdutos $produto
 * @property ProdStatusOrdem $status
 */
class ProdOrdensProducao extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prod_ordens_producao';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'produto_id', 'status_id', 'quantidade_planejada', 'data_inicio'], 'required'],
            [['empresa_id', 'produto_id', 'status_id', 'quantidade_planejada'], 'default', 'value' => null],
            [['empresa_id', 'produto_id', 'status_id', 'quantidade_planejada'], 'integer'],
            [['data_inicio', 'data_previsao_termino'], 'safe'],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
            [['produto_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastProdutos::className(), 'targetAttribute' => ['produto_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProdStatusOrdem::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'empresa_id' => 'Empresa ID',
            'produto_id' => 'Produto ID',
            'status_id' => 'Status ID',
            'quantidade_planejada' => 'Quantidade Planejada',
            'data_inicio' => 'Data Inicio',
            'data_previsao_termino' => 'Data Previsao Termino',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProdLotes()
    {
        return $this->hasMany(ProdLotes::className(), ['ordem_producao_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmpresa()
    {
        return $this->hasOne(CadastEmpresas::className(), ['id' => 'empresa_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduto()
    {
        return $this->hasOne(CadastProdutos::className(), ['id' => 'produto_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(ProdStatusOrdem::className(), ['id' => 'status_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\ProdOrdensProducaoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\ProdOrdensProducaoQuery(get_called_class());
    }
}
