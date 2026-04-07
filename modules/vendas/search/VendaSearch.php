<?php

namespace app\modules\vendas\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\vendas\models\Venda;

/**
 * VendaSearch represents the model behind the search form of `app\modules\vendas\models\Venda`.
 */
class VendaSearch extends Venda
{
    public $data_inicio;
    public $data_fim;
    public $cliente_nome;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'usuario_id', 'cliente_id', 'colaborador_vendedor_id', 'status_venda_codigo', 'forma_pagamento_id'], 'safe'],
            [['valor_total'], 'number'],
            [['data_inicio', 'data_fim', 'cliente_nome'], 'safe'],
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
        $query = Venda::find()
            ->joinWith(['cliente', 'formaPagamento']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['data_criacao' => SORT_DESC]],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere(['ilike', new \yii\db\Expression('prest_vendas.id::text'), $this->id])
            ->andFilterWhere(['prest_vendas.usuario_id' => $this->usuario_id])
            ->andFilterWhere(['prest_vendas.cliente_id' => $this->cliente_id])
            ->andFilterWhere(['prest_vendas.colaborador_vendedor_id' => $this->colaborador_vendedor_id])
            ->andFilterWhere(['prest_vendas.status_venda_codigo' => $this->status_venda_codigo])
            ->andFilterWhere(['prest_vendas.forma_pagamento_id' => $this->forma_pagamento_id]);

        if ($this->cliente_nome) {
            $query->andFilterWhere(['ilike', 'prest_clientes.nome_completo', $this->cliente_nome]);
        }

        if ($this->data_inicio) {
            $query->andFilterWhere(['>=', 'prest_vendas.data_venda', $this->data_inicio . ' 00:00:00']);
        }

        if ($this->data_fim) {
            $query->andFilterWhere(['<=', 'prest_vendas.data_venda', $this->data_fim . ' 23:59:59']);
        }

        return $dataProvider;
    }
}
