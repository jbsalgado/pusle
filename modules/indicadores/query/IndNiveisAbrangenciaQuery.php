<?php

namespace app\modules\indicadores\query;

/**
 * This is the ActiveQuery class for [[\app\modules\indicadores\models\IndNiveisAbrangencia]].
 *
 * @see \app\modules\indicadores\models\IndNiveisAbrangencia
 */
class IndNiveisAbrangenciaQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\IndNiveisAbrangencia[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\IndNiveisAbrangencia|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
