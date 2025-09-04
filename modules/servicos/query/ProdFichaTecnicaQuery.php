<?php

namespace app\modules\servicos\query;

/**
 * This is the ActiveQuery class for [[\app\modules\servicos\models\ProdFichaTecnica]].
 *
 * @see \app\modules\servicos\models\ProdFichaTecnica
 */
class ProdFichaTecnicaQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\models\ProdFichaTecnica[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\servicos\models\ProdFichaTecnica|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
