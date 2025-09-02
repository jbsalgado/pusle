<?php

namespace app\modules\indicadores\query;

/**
 * This is the ActiveQuery class for [[\app\modules\indicadores\models\IndRelacoesIndicadores]].
 *
 * @see \app\modules\indicadores\models\IndRelacoesIndicadores
 */
class IndRelacoesIndicadoresQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\IndRelacoesIndicadores[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\IndRelacoesIndicadores|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
