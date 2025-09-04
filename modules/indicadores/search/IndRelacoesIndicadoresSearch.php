<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndRelacoesIndicadores;

/**
 * IndRelacoesIndicadoresSearch represents the model behind the search form about `app\modules\indicadores\models\IndRelacoesIndicadores`.
 */
class IndRelacoesIndicadoresSearch extends IndRelacoesIndicadores
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_relacao', 'id_indicador_origem', 'id_indicador_destino'], 'integer'],
            [['tipo_relacao', 'descricao_relacao', 'data_criacao', 'data_atualizacao'], 'safe'],
            [['peso_relacao'], 'number'],
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
        $query = IndRelacoesIndicadores::find();

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
            'id_relacao' => $this->id_relacao,
            'id_indicador_origem' => $this->id_indicador_origem,
            'id_indicador_destino' => $this->id_indicador_destino,
            'peso_relacao' => $this->peso_relacao,
            'data_criacao' => $this->data_criacao,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'tipo_relacao', $this->tipo_relacao])
            ->andFilterWhere(['like', 'descricao_relacao', $this->descricao_relacao]);

        return $dataProvider;
    }
}
