<?php

namespace app\modules\indicadores\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndDefinicoesIndicadores;

/**
 * IndDefinicoesIndicadoresSearch represents the model behind the search form about `app\modules\indicadores\models\IndDefinicoesIndicadores`.
 */
class IndDefinicoesIndicadoresSearch extends IndDefinicoesIndicadores
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_indicador', 'id_dimensao', 'id_unidade_medida', 'id_periodicidade_ideal_medicao', 'id_periodicidade_ideal_divulgacao', 'id_fonte_padrao', 'versao'], 'integer'],
            [['cod_indicador', 'nome_indicador', 'descricao_completa', 'conceito', 'justificativa', 'metodo_calculo', 'interpretacao', 'limitacoes', 'observacoes_gerais', 'tipo_especifico', 'polaridade', 'data_inicio_validade', 'data_fim_validade', 'responsavel_tecnico', 'nota_tecnica_url', 'palavras_chave', 'data_criacao', 'data_atualizacao'], 'safe'],
            [['ativo'], 'boolean'],
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
        $query = IndDefinicoesIndicadores::find();

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
            'id_indicador' => $this->id_indicador,
            'id_dimensao' => $this->id_dimensao,
            'id_unidade_medida' => $this->id_unidade_medida,
            'id_periodicidade_ideal_medicao' => $this->id_periodicidade_ideal_medicao,
            'id_periodicidade_ideal_divulgacao' => $this->id_periodicidade_ideal_divulgacao,
            'id_fonte_padrao' => $this->id_fonte_padrao,
            'data_inicio_validade' => $this->data_inicio_validade,
            'data_fim_validade' => $this->data_fim_validade,
            'versao' => $this->versao,
            'ativo' => $this->ativo,
            'data_criacao' => $this->data_criacao,
            'data_atualizacao' => $this->data_atualizacao,
        ]);

        $query->andFilterWhere(['like', 'cod_indicador', $this->cod_indicador])
            ->andFilterWhere(['like', 'nome_indicador', $this->nome_indicador])
            ->andFilterWhere(['like', 'descricao_completa', $this->descricao_completa])
            ->andFilterWhere(['like', 'conceito', $this->conceito])
            ->andFilterWhere(['like', 'justificativa', $this->justificativa])
            ->andFilterWhere(['like', 'metodo_calculo', $this->metodo_calculo])
            ->andFilterWhere(['like', 'interpretacao', $this->interpretacao])
            ->andFilterWhere(['like', 'limitacoes', $this->limitacoes])
            ->andFilterWhere(['like', 'observacoes_gerais', $this->observacoes_gerais])
            ->andFilterWhere(['like', 'tipo_especifico', $this->tipo_especifico])
            ->andFilterWhere(['like', 'polaridade', $this->polaridade])
            ->andFilterWhere(['like', 'responsavel_tecnico', $this->responsavel_tecnico])
            ->andFilterWhere(['like', 'nota_tecnica_url', $this->nota_tecnica_url])
            ->andFilterWhere(['like', 'palavras_chave', $this->palavras_chave]);

        return $dataProvider;
    }
}
