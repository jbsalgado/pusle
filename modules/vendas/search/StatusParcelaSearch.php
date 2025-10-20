<?php

namespace app\modules\vendas\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\vendas\models\StatusParcela;

/**
 * StatusParcelaSearch representa o modelo por trás do formulário de pesquisa de `app\modules\vendas\models\StatusParcela`.
 */
class StatusParcelaSearch extends StatusParcela
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['codigo', 'descricao'], 'safe'],
        ];
    }

    /**
     * Cria uma instância de data provider com a query de pesquisa aplicada
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = StatusParcela::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['codigo' => SORT_ASC]]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Condições de filtro da grelha
        $query->andFilterWhere(['ilike', 'codigo', $this->codigo])
            ->andFilterWhere(['ilike', 'descricao', $this->descricao]);

        return $dataProvider;
    }
}