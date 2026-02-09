<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


class StatusVenda extends ActiveRecord
{
    const EM_ABERTO = 'EM_ABERTO';
    const QUITADA = 'QUITADA';
    const CANCELADA = 'CANCELADA';
    const PARCIALMENTE_PAGA = 'PARCIALMENTE_PAGA';
    const ORCAMENTO = 'ORCAMENTO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_status_venda';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['codigo', 'descricao'], 'required'],
            [['codigo'], 'string', 'max' => 20],
            [['descricao'], 'string', 'max' => 100],
            [['codigo'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'codigo' => 'Código',
            'descricao' => 'Descrição',
        ];
    }

    /**
     * Retorna array para dropdown
     */
    public static function getListaDropdown()
    {
        return self::find()
            ->select(['descricao', 'codigo'])
            ->indexBy('codigo')
            ->column();
    }

    public function getVendas()
    {
        return $this->hasMany(Venda::class, ['status_venda_codigo' => 'codigo']);
    }
}
