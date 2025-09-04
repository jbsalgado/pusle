<?php

namespace app\modules\indicadores\query;

/**
 * This is the ActiveQuery class for [[\app\modules\indicadores\models\AuthAssignment]].
 *
 * @see \app\modules\indicadores\models\AuthAssignment
 */
class AuthAssignmentQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\AuthAssignment[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\AuthAssignment|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
