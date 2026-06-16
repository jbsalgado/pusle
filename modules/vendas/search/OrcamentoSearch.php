<?php

namespace app\modules\vendas\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\vendas\models\Orcamento;

/**
 * OrcamentoSearch represents the model behind the search form of `app\modules\vendas\models\Orcamento`.
 */
class OrcamentoSearch extends Orcamento
{
    public $data_inicio;
    public $data_fim;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'venda_id', 'cliente_id', 'usuario_id'], 'safe'],
            [['valor_total'], 'number'],
            [['status'], 'string'],
            [['data_inicio', 'data_fim', 'data_criacao'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Orcamento::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['data_criacao' => SORT_DESC]],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere(['ilike', 'status', $this->status])
              ->andFilterWhere(['cliente_id' => $this->cliente_id])
              ->andFilterWhere(['usuario_id' => $this->usuario_id]);

        if ($this->valor_total) {
            $query->andFilterWhere(['>=', 'valor_total', $this->valor_total]);
        }

        if ($this->data_inicio) {
            $query->andFilterWhere(['>=', 'data_criacao', $this->data_inicio . ' 00:00:00']);
        }

        if ($this->data_fim) {
            $query->andFilterWhere(['<=', 'data_criacao', $this->data_fim . ' 23:59:59']);
        }

        return $dataProvider;
    }
}
