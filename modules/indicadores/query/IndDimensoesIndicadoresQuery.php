<?php

namespace app\modules\indicadores\query;

/**
 * This is the ActiveQuery class for [[\app\modules\indicadores\models\IndDimensoesIndicadores]].
 *
 * @see \app\modules\indicadores\models\IndDimensoesIndicadores
 */
class IndDimensoesIndicadoresQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\IndDimensoesIndicadores[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\IndDimensoesIndicadores|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
