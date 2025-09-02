<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndDimensoesIndicadores;

/**
 * IndDimensoesIndicadoresSearch represents the model behind the search form about `app\modules\indicadores\models\IndDimensoesIndicadores`.
 */
class IndDimensoesIndicadoresSearch extends IndDimensoesIndicadores
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_dimensao', 'id_dimensao_pai'], 'integer'],
            [['nome_dimensao', 'descricao', 'data_criacao', 'data_atualizacao'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
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
        $query = IndDimensoesIndicadores::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id_dimensao' => $this->id_dimensao,
            'id_dimensao_pai' => $this->id_dimensao_pai,
            'data_criacao' => $this->data_criacao,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'nome_dimensao', $this->nome_dimensao])
            ->andFilterWhere(['like', 'descricao', $this->descricao]);

        return $dataProvider;
    }
}
