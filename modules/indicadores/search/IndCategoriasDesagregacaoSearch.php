<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndCategoriasDesagregacao;

/**
 * IndCategoriasDesagregacaoSearch represents the model behind the search form about `app\modules\indicadores\models\IndCategoriasDesagregacao`.
 */
class IndCategoriasDesagregacaoSearch extends IndCategoriasDesagregacao
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_categoria_desagregacao'], 'integer'],
            [['nome_categoria', 'descricao', 'data_criacao', 'data_atualizacao'], 'safe'],
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
        $query = IndCategoriasDesagregacao::find();

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
            'id_categoria_desagregacao' => $this->id_categoria_desagregacao,
            'data_criacao' => $this->data_criacao,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'nome_categoria', $this->nome_categoria])
            ->andFilterWhere(['like', 'descricao', $this->descricao]);

        return $dataProvider;
    }
}
