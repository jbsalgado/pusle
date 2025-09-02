<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndOpcoesDesagregacao;

/**
 * IndOpcoesDesagregacaoSearch represents the model behind the search form about `app\modules\indicadores\models\IndOpcoesDesagregacao`.
 */
class IndOpcoesDesagregacaoSearch extends IndOpcoesDesagregacao
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_opcao_desagregacao', 'id_categoria_desagregacao', 'ordem_apresentacao'], 'integer'],
            [['valor_opcao', 'codigo_opcao', 'descricao_opcao', 'data_criacao', 'data_atualizacao'], 'safe'],
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
        $query = IndOpcoesDesagregacao::find();

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
            'id_opcao_desagregacao' => $this->id_opcao_desagregacao,
            'id_categoria_desagregacao' => $this->id_categoria_desagregacao,
            'ordem_apresentacao' => $this->ordem_apresentacao,
            'data_criacao' => $this->data_criacao,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'valor_opcao', $this->valor_opcao])
            ->andFilterWhere(['like', 'codigo_opcao', $this->codigo_opcao])
            ->andFilterWhere(['like', 'descricao_opcao', $this->descricao_opcao]);

        return $dataProvider;
    }
}
