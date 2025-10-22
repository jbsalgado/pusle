<?php

namespace app\modules\vendas\models;

use Yii;

/**
 * This is the model class for table "prest_periodos_cobranca".
 *
 * @property string $id
 * @property string $usuario_id
 * @property int $mes_referencia
 * @property int $ano_referencia
 * @property string|null $descricao
 * @property string $data_inicio
 * @property string $data_fim
 * @property string $status
 * @property string $data_criacao
 *
 * @property PrestClientes[] $clientes
 * @property PrestCarteiraCobranca[] $prestCarteiraCobrancas
 * @property PrestRotasCobranca[] $prestRotasCobrancas
 * @property PrestUsuarios $usuario
 */
class PeriodosCobranca extends \yii\db\ActiveRecord
{


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
            [['descricao'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 'ABERTO'],
            [['id', 'usuario_id', 'mes_referencia', 'ano_referencia', 'data_inicio', 'data_fim'], 'required'],
            [['id', 'usuario_id'], 'string'],
            [['mes_referencia', 'ano_referencia'], 'default', 'value' => null],
            [['mes_referencia', 'ano_referencia'], 'integer'],
            [['data_inicio', 'data_fim', 'data_criacao'], 'safe'],
            [['descricao'], 'string', 'max' => 100],
            [['status'], 'string', 'max' => 20],
            [['usuario_id', 'mes_referencia', 'ano_referencia'], 'unique', 'targetAttribute' => ['usuario_id', 'mes_referencia', 'ano_referencia']],
            [['id'], 'unique'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => PrestUsuarios::class, 'targetAttribute' => ['usuario_id' => 'id']],
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
            'mes_referencia' => 'Mes Referencia',
            'ano_referencia' => 'Ano Referencia',
            'descricao' => 'Descricao',
            'data_inicio' => 'Data Inicio',
            'data_fim' => 'Data Fim',
            'status' => 'Status',
            'data_criacao' => 'Data Criacao',
        ];
    }

    /**
     * Gets query for [[Clientes]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestClientesQuery
     */
    public function getClientes()
    {
        return $this->hasMany(PrestClientes::class, ['id' => 'cliente_id'])->viaTable('prest_carteira_cobranca', ['periodo_id' => 'id']);
    }

    /**
     * Gets query for [[PrestCarteiraCobrancas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestCarteiraCobrancaQuery
     */
    public function getPrestCarteiraCobrancas()
    {
        return $this->hasMany(PrestCarteiraCobranca::class, ['periodo_id' => 'id']);
    }

    /**
     * Gets query for [[PrestRotasCobrancas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestRotasCobrancaQuery
     */
    public function getPrestRotasCobrancas()
    {
        return $this->hasMany(PrestRotasCobranca::class, ['periodo_id' => 'id']);
    }

    /**
     * Gets query for [[Usuario]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestUsuariosQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(PrestUsuarios::class, ['id' => 'usuario_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\query\PrestPeriodosCobrancaQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\vendas\query\PrestPeriodosCobrancaQuery(get_called_class());
    }

}
