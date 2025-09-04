<?php

namespace app\modules\servicos\query;

/**
 * This is the ActiveQuery class for [[\app\modules\servicos\models\EstoqTiposMovimento]].
 *
 * @see \app\modules\servicos\models\EstoqTiposMovimento
 */
class EstoqTiposMovimentoQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\models\EstoqTiposMovimento[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\models\EstoqTiposMovimento|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
