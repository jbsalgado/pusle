<?php

namespace app\modules\indicadores\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\indicadores\models\IndDefinicoesIndicadores;
use Yii;

/**
 * IndDefinicoesIndicadoresSearch represents the model behind the search form of `app\modules\indicadores\models\IndDefinicoesIndicadores`.
 */
class IndDefinicoesIndicadoresSearch extends IndDefinicoesIndicadores
{
    /**
     * @var string Atributo para a busca textual genérica
     */
    public $q;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Define que os atributos dos filtros são seguros para serem atribuídos em massa
            [['q', 'tipo_especifico', 'polaridade'], 'string'],
            [['id_dimensao', 'ativo'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
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
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'nome_indicador' => SORT_ASC,
                ]
            ]
        ]);

        // Carrega os parâmetros da requisição (ex: ?q=teste&dimensao=1) nos atributos do modelo
        $this->load($params, '');

        if (!$this->validate()) {
            // descomente a linha a seguir se você não quiser retornar nenhum registro quando a validação falhar
            // $query->where('0=1');
            return $dataProvider;
        }

        // Aplica a condição de filtro para cada atributo
        // O andFilterWhere ignora a condição se o valor do filtro for nulo ou vazio
        $query->andFilterWhere([
            'id_dimensao' => $this->id_dimensao,
            'tipo_especifico' => $this->tipo_especifico,
            'polaridade' => $this->polaridade,
            'ativo' => $this->ativo,
        ]);

        // Aplica o filtro de busca textual em múltiplos campos
        $query->andFilterWhere(['or',
            ['ilike', 'nome_indicador', $this->q],
            ['ilike', 'cod_indicador', $this->q],
            ['ilike', 'descricao_completa', $this->q],
            ['ilike', 'palavras_chave', $this->q],
        ]);

        return $dataProvider;
    }
}