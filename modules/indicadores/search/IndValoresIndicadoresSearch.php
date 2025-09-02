<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndValoresIndicadores;

/**
 * IndValoresIndicadoresSearch represents the model behind the search form about `app\modules\indicadores\models\IndValoresIndicadores`.
 */
class IndValoresIndicadoresSearch extends IndValoresIndicadores
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_valor', 'id_indicador', 'id_nivel_abrangencia', 'id_fonte_dado_especifica'], 'integer'],
            [['data_referencia', 'codigo_especifico_abrangencia', 'localidade_especifica_nome', 'data_coleta_dado', 'analise_qualitativa_valor', 'data_publicacao_valor', 'data_atualizacao'], 'safe'],
            [['valor', 'numerador', 'denominador', 'confianca_intervalo_inferior', 'confianca_intervalo_superior'], 'number'],
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
        $query = IndValoresIndicadores::find();

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
            'id_valor' => $this->id_valor,
            'id_indicador' => $this->id_indicador,
            'data_referencia' => $this->data_referencia,
            'id_nivel_abrangencia' => $this->id_nivel_abrangencia,
            'valor' => $this->valor,
            'numerador' => $this->numerador,
            'denominador' => $this->denominador,
            'id_fonte_dado_especifica' => $this->id_fonte_dado_especifica,
            'data_coleta_dado' => $this->data_coleta_dado,
            'confianca_intervalo_inferior' => $this->confianca_intervalo_inferior,
            'confianca_intervalo_superior' => $this->confianca_intervalo_superior,
            'data_publicacao_valor' => $this->data_publicacao_valor,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'codigo_especifico_abrangencia', $this->codigo_especifico_abrangencia])
            ->andFilterWhere(['like', 'localidade_especifica_nome', $this->localidade_especifica_nome])
            ->andFilterWhere(['like', 'analise_qualitativa_valor', $this->analise_qualitativa_valor]);

        return $dataProvider;
    }
}
