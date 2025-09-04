<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "prod_lotes".
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $ordem_producao_id
 * @property int $terceiro_id
 * @property int $etapa_id
 * @property int $status_id
 * @property string $data_envio
 * @property int $quantidade_enviada
 * @property int $quantidade_recebida
 * @property int $quantidade_rejeitada
 * @property string $valor_servico_unitario
 *
 * @property FinanContasPagar $finanContasPagar
 * @property IndicaQualidadeDefeitos[] $indicaQualidadeDefeitos
 * @property IndicaTemposProducao[] $indicaTemposProducaos
 * @property CadastEmpresas $empresa
 * @property CadastTerceiros $terceiro
 * @property ProdEtapasProducao $etapa
 * @property ProdOrdensProducao $ordemProducao
 * @property ProdStatusLote $status
 */
class ProdLotes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prod_lotes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'ordem_producao_id', 'terceiro_id', 'etapa_id', 'status_id', 'quantidade_enviada', 'valor_servico_unitario'], 'required'],
            [['empresa_id', 'ordem_producao_id', 'terceiro_id', 'etapa_id', 'status_id', 'quantidade_enviada', 'quantidade_recebida', 'quantidade_rejeitada'], 'default', 'value' => null],
            [['empresa_id', 'ordem_producao_id', 'terceiro_id', 'etapa_id', 'status_id', 'quantidade_enviada', 'quantidade_recebida', 'quantidade_rejeitada'], 'integer'],
            [['data_envio'], 'safe'],
            [['valor_servico_unitario'], 'number'],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
            [['terceiro_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastTerceiros::className(), 'targetAttribute' => ['terceiro_id' => 'id']],
            [['etapa_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProdEtapasProducao::className(), 'targetAttribute' => ['etapa_id' => 'id']],
            [['ordem_producao_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProdOrdensProducao::className(), 'targetAttribute' => ['ordem_producao_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProdStatusLote::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'ordem_producao_id' => 'Ordem Producao ID',
            'terceiro_id' => 'Terceiro ID',
            'etapa_id' => 'Etapa ID',
            'status_id' => 'Status ID',
            'data_envio' => 'Data Envio',
            'quantidade_enviada' => 'Quantidade Enviada',
            'quantidade_recebida' => 'Quantidade Recebida',
            'quantidade_rejeitada' => 'Quantidade Rejeitada',
            'valor_servico_unitario' => 'Valor Servico Unitario',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFinanContasPagar()
    {
        return $this->hasOne(FinanContasPagar::className(), ['lote_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndicaQualidadeDefeitos()
    {
        return $this->hasMany(IndicaQualidadeDefeitos::className(), ['lote_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndicaTemposProducaos()
    {
        return $this->hasMany(IndicaTemposProducao::className(), ['lote_id' => 'id']);
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
    public function getTerceiro()
    {
        return $this->hasOne(CadastTerceiros::className(), ['id' => 'terceiro_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEtapa()
    {
        return $this->hasOne(ProdEtapasProducao::className(), ['id' => 'etapa_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrdemProducao()
    {
        return $this->hasOne(ProdOrdensProducao::className(), ['id' => 'ordem_producao_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(ProdStatusLote::className(), ['id' => 'status_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\ProdLotesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\ProdLotesQuery(get_called_class());
    }
}
