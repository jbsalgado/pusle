<?php 

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/* ============================================================================================================
 * Model: StatusParcela (Lookup Table)
 * ============================================================================================================
 * Tabela: prest_status_parcela
 * 
 * @property string $codigo
 * @property string $descricao
 * 
 * @property Parcela[] $parcelas
 */
class StatusParcela extends ActiveRecord
{
    const PENDENTE = 'PENDENTE';
    const PAGA = 'PAGA';
    const ATRASADA = 'ATRASADA';
    const CANCELADA = 'CANCELADA';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_status_parcela';
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

    public function getParcelas()
    {
        return $this->hasMany(Parcela::class, ['status_parcela_codigo' => 'codigo']);
    }
}