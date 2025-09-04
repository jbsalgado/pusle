<?php

namespace app\modules\indicadores\query;

/**
 * This is the ActiveQuery class for [[\app\modules\indicadores\models\IndCategoriasDesagregacao]].
 *
 * @see \app\modules\indicadores\models\IndCategoriasDesagregacao
 */
class IndCategoriasDesagregacaoQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\IndCategoriasDesagregacao[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\IndCategoriasDesagregacao|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
