<?php

namespace app\modules\servicos\models;

use Yii;

/**
 * This is the model class for table "finan_contas_pagar".
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $terceiro_id
 * @property int $status_id
 * @property int $lote_id
 * @property string $descricao
 * @property string $valor
 * @property string $data_vencimento
 *
 * @property CadastEmpresas $empresa
 * @property CadastTerceiros $terceiro
 * @property FinanStatusConta $status
 * @property ProdLotes $lote
 */
class FinanContasPagar extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'finan_contas_pagar';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['empresa_id', 'terceiro_id', 'status_id', 'descricao', 'valor', 'data_vencimento'], 'required'],
            [['empresa_id', 'terceiro_id', 'status_id', 'lote_id'], 'default', 'value' => null],
            [['empresa_id', 'terceiro_id', 'status_id', 'lote_id'], 'integer'],
            [['valor'], 'number'],
            [['data_vencimento'], 'safe'],
            [['descricao'], 'string', 'max' => 255],
            [['lote_id'], 'unique'],
            [['empresa_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastEmpresas::className(), 'targetAttribute' => ['empresa_id' => 'id']],
            [['terceiro_id'], 'exist', 'skipOnError' => true, 'targetClass' => CadastTerceiros::className(), 'targetAttribute' => ['terceiro_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => FinanStatusConta::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['lote_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProdLotes::className(), 'targetAttribute' => ['lote_id' => 'id']],
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
            'terceiro_id' => 'Terceiro ID',
            'status_id' => 'Status ID',
            'lote_id' => 'Lote ID',
            'descricao' => 'Descricao',
            'valor' => 'Valor',
            'data_vencimento' => 'Data Vencimento',
        ];
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
    public function getStatus()
    {
        return $this->hasOne(FinanStatusConta::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLote()
    {
        return $this->hasOne(ProdLotes::className(), ['id' => 'lote_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\query\FinanContasPagarQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\servicos\query\FinanContasPagarQuery(get_called_class());
    }
}
