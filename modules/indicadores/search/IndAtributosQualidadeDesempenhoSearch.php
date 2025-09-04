<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndAtributosQualidadeDesempenho;

/**
 * IndAtributosQualidadeDesempenhoSearch represents the model behind the search form about `app\modules\indicadores\models\IndAtributosQualidadeDesempenho`.
 */
class IndAtributosQualidadeDesempenhoSearch extends IndAtributosQualidadeDesempenho
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_atributo_qd', 'id_indicador', 'fator_impacto'], 'integer'],
            [['padrao_ouro_referencia', 'metodo_pontuacao', 'data_criacao', 'data_atualizacao'], 'safe'],
            [['faixa_critica_inferior', 'faixa_critica_superior', 'faixa_alerta_inferior', 'faixa_alerta_superior', 'faixa_satisfatoria_inferior', 'faixa_satisfatoria_superior', 'peso_indicador'], 'number'],
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
        $query = IndAtributosQualidadeDesempenho::find();

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
            'id_atributo_qd' => $this->id_atributo_qd,
            'id_indicador' => $this->id_indicador,
            'faixa_critica_inferior' => $this->faixa_critica_inferior,
            'faixa_critica_superior' => $this->faixa_critica_superior,
            'faixa_alerta_inferior' => $this->faixa_alerta_inferior,
            'faixa_alerta_superior' => $this->faixa_alerta_superior,
            'faixa_satisfatoria_inferior' => $this->faixa_satisfatoria_inferior,
            'faixa_satisfatoria_superior' => $this->faixa_satisfatoria_superior,
            'peso_indicador' => $this->peso_indicador,
            'fator_impacto' => $this->fator_impacto,
            'data_criacao' => $this->data_criacao,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'padrao_ouro_referencia', $this->padrao_ouro_referencia])
            ->andFilterWhere(['like', 'metodo_pontuacao', $this->metodo_pontuacao]);

        return $dataProvider;
    }
}
