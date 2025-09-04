<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndUnidadesMedida;

/**
 * IndUnidadesMedidaSearch represents the model behind the search form about `app\modules\indicadores\models\IndUnidadesMedida`.
 */
class IndUnidadesMedidaSearch extends IndUnidadesMedida
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_unidade'], 'integer'],
            [['sigla_unidade', 'descricao_unidade', 'tipo_dado_associado', 'data_criacao', 'data_atualizacao'], 'safe'],
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
        $query = IndUnidadesMedida::find();

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
            'id_unidade' => $this->id_unidade,
            'data_criacao' => $this->data_criacao,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'sigla_unidade', $this->sigla_unidade])
            ->andFilterWhere(['like', 'descricao_unidade', $this->descricao_unidade])
            ->andFilterWhere(['like', 'tipo_dado_associado', $this->tipo_dado_associado]);

        return $dataProvider;
    }
}
