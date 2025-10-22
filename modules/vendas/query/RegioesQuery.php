<?php

namespace app\modules\vendas\query;

/**
 * This is the ActiveQuery class for [[\app\modules\vendas\models\Regioes]].
 *
 * @see \app\modules\vendas\models\Regioes
 */
class RegioesQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\models\Regioes[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\models\Regioes|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
