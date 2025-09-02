<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndFontesDados;

/**
 * IndFontesDadosSearch represents the model behind the search form about `app\modules\indicadores\models\IndFontesDados`.
 */
class IndFontesDadosSearch extends IndFontesDados
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_fonte', 'confiabilidade_estimada'], 'integer'],
            [['nome_fonte', 'descricao', 'url_referencia', 'data_criacao', 'data_atualizacao'], 'safe'],
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
        $query = IndFontesDados::find();

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
            'id_fonte' => $this->id_fonte,
            'confiabilidade_estimada' => $this->confiabilidade_estimada,
            'data_criacao' => $this->data_criacao,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'nome_fonte', $this->nome_fonte])
            ->andFilterWhere(['like', 'descricao', $this->descricao])
            ->andFilterWhere(['like', 'url_referencia', $this->url_referencia]);

        return $dataProvider;
    }
}
