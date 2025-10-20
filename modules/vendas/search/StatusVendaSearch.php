<?php

namespace app\modules\vendas\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\vendas\models\StatusVenda;

class StatusVendaSearch extends StatusVenda
{
    public function rules()
    {
        return [
            [['codigo', 'descricao'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = StatusVenda::find();
        $dataProvider = new ActiveDataProvider(['query' => $query, 'sort' => ['defaultOrder' => ['codigo' => SORT_ASC]]]);
        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }
        $query->andFilterWhere(['ilike', 'codigo', $this->codigo])
            ->andFilterWhere(['ilike', 'descricao', $this->descricao]);
        return $dataProvider;
    }
}