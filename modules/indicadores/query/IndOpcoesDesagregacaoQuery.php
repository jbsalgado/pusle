<?php

namespace app\modules\indicadores\query;

/**
 * This is the ActiveQuery class for [[\app\modules\indicadores\models\IndOpcoesDesagregacao]].
 *
 * @see \app\modules\indicadores\models\IndOpcoesDesagregacao
 */
class IndOpcoesDesagregacaoQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\IndOpcoesDesagregacao[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\indicadores\models\IndOpcoesDesagregacao|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
