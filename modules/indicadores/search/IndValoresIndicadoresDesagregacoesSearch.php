<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndValoresIndicadoresDesagregacoes;

/**
 * IndValoresIndicadoresDesagregacoesSearch represents the model behind the search form about `app\modules\indicadores\models\IndValoresIndicadoresDesagregacoes`.
 */
class IndValoresIndicadoresDesagregacoesSearch extends IndValoresIndicadoresDesagregacoes
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_valor_indicador', 'id_opcao_desagregacao'], 'integer'],
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
        $query = IndValoresIndicadoresDesagregacoes::find();

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
            'id_valor_indicador' => $this->id_valor_indicador,
            'id_opcao_desagregacao' => $this->id_opcao_desagregacao,
        ]);

        return $dataProvider;
    }
}
