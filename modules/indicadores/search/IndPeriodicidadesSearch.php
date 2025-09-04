<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndPeriodicidades;

/**
 * IndPeriodicidadesSearch represents the model behind the search form about `app\modules\indicadores\models\IndPeriodicidades`.
 */
class IndPeriodicidadesSearch extends IndPeriodicidades
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_periodicidade', 'intervalo_em_dias'], 'integer'],
            [['nome_periodicidade', 'descricao', 'data_criacao', 'data_atualizacao'], 'safe'],
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
        $query = IndPeriodicidades::find();

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
            'id_periodicidade' => $this->id_periodicidade,
            'intervalo_em_dias' => $this->intervalo_em_dias,
            'data_criacao' => $this->data_criacao,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'nome_periodicidade', $this->nome_periodicidade])
            ->andFilterWhere(['like', 'descricao', $this->descricao]);

        return $dataProvider;
    }
}
