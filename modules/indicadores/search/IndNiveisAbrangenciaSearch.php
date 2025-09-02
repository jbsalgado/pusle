<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndNiveisAbrangencia;

/**
 * IndNiveisAbrangenciaSearch represents the model behind the search form about `app\modules\indicadores\models\IndNiveisAbrangencia`.
 */
class IndNiveisAbrangenciaSearch extends IndNiveisAbrangencia
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_nivel_abrangencia', 'id_nivel_pai'], 'integer'],
            [['nome_nivel', 'descricao', 'tipo_nivel', 'data_criacao', 'data_atualizacao'], 'safe'],
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
        $query = IndNiveisAbrangencia::find();

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
            'id_nivel_abrangencia' => $this->id_nivel_abrangencia,
            'id_nivel_pai' => $this->id_nivel_pai,
            'data_criacao' => $this->data_criacao,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'nome_nivel', $this->nome_nivel])
            ->andFilterWhere(['like', 'descricao', $this->descricao])
            ->andFilterWhere(['like', 'tipo_nivel', $this->tipo_nivel]);

        return $dataProvider;
    }
}
