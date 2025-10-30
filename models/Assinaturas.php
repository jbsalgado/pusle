<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "sis_assinaturas".
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $plano_id
 * @property string $status
 * @property string $data_inicio
 * @property string|null $data_fim
 * @property string|null $data_cancelamento
 * @property float|null $valor_pago
 * @property string|null $forma_pagamento
 * @property string|null $observacoes
 * @property string|null $data_criacao
 * @property string|null $data_atualizacao
 *
 * @property SisPlanos $plano
 * @property SisPagamentos[] $sisPagamentos
 * @property PrestUsuarios $usuario
 */
class Assinaturas extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sis_assinaturas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['data_fim', 'data_cancelamento', 'valor_pago', 'forma_pagamento', 'observacoes'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 'ativa'],
            [['id', 'usuario_id', 'plano_id'], 'required'],
            [['id', 'usuario_id', 'plano_id', 'observacoes'], 'string'],
            [['data_inicio', 'data_fim', 'data_cancelamento', 'data_criacao', 'data_atualizacao'], 'safe'],
            [['valor_pago'], 'number'],
            [['status'], 'string', 'max' => 20],
            [['forma_pagamento'], 'string', 'max' => 50],
            [['id'], 'unique'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => PrestUsuarios::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['plano_id'], 'exist', 'skipOnError' => true, 'targetClass' => SisPlanos::class, 'targetAttribute' => ['plano_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuario ID',
            'plano_id' => 'Plano ID',
            'status' => 'Status',
            'data_inicio' => 'Data Inicio',
            'data_fim' => 'Data Fim',
            'data_cancelamento' => 'Data Cancelamento',
            'valor_pago' => 'Valor Pago',
            'forma_pagamento' => 'Forma Pagamento',
            'observacoes' => 'Observacoes',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
        ];
    }

    /**
     * Gets query for [[Plano]].
     *
     * @return \yii\db\ActiveQuery|SisPlanosQuery
     */
    public function getPlano()
    {
        return $this->hasOne(SisPlanos::class, ['id' => 'plano_id']);
    }

    /**
     * Gets query for [[SisPagamentos]].
     *
     * @return \yii\db\ActiveQuery|yii\db\ActiveQuery
     */
    public function getSisPagamentos()
    {
        return $this->hasMany(SisPagamentos::class, ['assinatura_id' => 'id']);
    }

    /**
     * Gets query for [[Usuario]].
     *
     * @return \yii\db\ActiveQuery|PrestUsuariosQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(PrestUsuarios::class, ['id' => 'usuario_id']);
    }

    /**
     * {@inheritdoc}
     * @return AssinaturasQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AssinaturasQuery(get_called_class());
    }

}
