<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Assinaturas]].
 *
 * @see Assinaturas
 */
class AssinaturasQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return Assinaturas[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Assinaturas|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
