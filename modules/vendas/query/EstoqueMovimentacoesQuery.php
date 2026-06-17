<?php

namespace app\modules\vendas\query;

/**
 * This is the ActiveQuery class for [[\app\modules\vendas\models\EstoqueMovimentacoes]].
 *
 * @see \app\modules\vendas\models\EstoqueMovimentacoes
 */
class EstoqueMovimentacoesQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\models\EstoqueMovimentacoes[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\models\EstoqueMovimentacoes|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
