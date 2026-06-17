<?php

namespace app\modules\vendas\query;

/**
 * This is the ActiveQuery class for [[\app\modules\vendas\models\MercadopagoPreferencias]].
 *
 * @see \app\modules\vendas\models\MercadopagoPreferencias
 */
class MercadopagoPreferenciasQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\models\MercadopagoPreferencias[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\models\MercadopagoPreferencias|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
