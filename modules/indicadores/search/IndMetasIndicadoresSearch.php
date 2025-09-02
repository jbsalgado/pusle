<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndMetasIndicadores;

/**
 * IndMetasIndicadoresSearch represents the model behind the search form about `app\modules\indicadores\models\IndMetasIndicadores`.
 */
class IndMetasIndicadoresSearch extends IndMetasIndicadores
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_meta', 'id_indicador', 'id_nivel_abrangencia_aplicavel'], 'integer'],
            [['descricao_meta', 'tipo_de_meta', 'data_inicio_vigencia', 'data_fim_vigencia', 'justificativa_meta', 'fonte_meta', 'data_criacao', 'data_atualizacao'], 'safe'],
            [['valor_meta_referencia_1', 'valor_meta_referencia_2'], 'number'],
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
        $query = IndMetasIndicadores::find();

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
            'id_meta' => $this->id_meta,
            'id_indicador' => $this->id_indicador,
            'valor_meta_referencia_1' => $this->valor_meta_referencia_1,
            'valor_meta_referencia_2' => $this->valor_meta_referencia_2,
            'data_inicio_vigencia' => $this->data_inicio_vigencia,
            'data_fim_vigencia' => $this->data_fim_vigencia,
            'id_nivel_abrangencia_aplicavel' => $this->id_nivel_abrangencia_aplicavel,
            'data_criacao' => $this->data_criacao,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'descricao_meta', $this->descricao_meta])
            ->andFilterWhere(['like', 'tipo_de_meta', $this->tipo_de_meta])
            ->andFilterWhere(['like', 'justificativa_meta', $this->justificativa_meta])
            ->andFilterWhere(['like', 'fonte_meta', $this->fonte_meta]);

        return $dataProvider;
    }
}
