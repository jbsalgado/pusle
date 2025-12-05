<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Compra;
use app\modules\vendas\models\ItemCompra;
use app\modules\vendas\models\Fornecedor;
use app\modules\vendas\models\Produto;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\db\Transaction;

/**
 * CompraController implementa as ações CRUD para o model Compra.
 */
class CompraController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'concluir' => ['POST'],
                    'cancelar' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista todas as Compras com filtros e paginação.
     * @return string
     */
    public function actionIndex()
    {
        $query = Compra::find()
            ->where(['usuario_id' => Yii::$app->user->id])
            ->with(['fornecedor', 'itens.produto']);

        // Filtros
        $fornecedorId = Yii::$app->request->get('fornecedor_id');
        $status = Yii::$app->request->get('status');
        $dataInicio = Yii::$app->request->get('data_inicio');
        $dataFim = Yii::$app->request->get('data_fim');

        if ($fornecedorId) {
            $query->andWhere(['fornecedor_id' => $fornecedorId]);
        }

        if ($status) {
            $query->andWhere(['status_compra' => $status]);
        }

        if ($dataInicio) {
            $query->andWhere(['>=', 'data_compra', $dataInicio]);
        }

        if ($dataFim) {
            $query->andWhere(['<=', 'data_compra', $dataFim]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'data_compra' => SORT_DESC,
                ]
            ],
        ]);

        $fornecedores = Fornecedor::getListaDropdownArray(Yii::$app->user->id);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'fornecedores' => $fornecedores,
        ]);
    }

    /**
     * Exibe um único modelo Compra.
     * @param string $id
     * @return string
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Cria um novo modelo Compra.
     * Se a criação for bem-sucedida, o navegador será redirecionado para a página 'view'.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Compra();
        $model->usuario_id = Yii::$app->user->id;
        $model->data_compra = date('Y-m-d');
        $model->status_compra = Compra::STATUS_PENDENTE;
        $model->valor_total = 0;
        $model->valor_frete = 0;
        $model->valor_desconto = 0;

        $itens = [];
        $post = Yii::$app->request->post();

        if ($model->load($post)) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    // Processa itens
                    if (isset($post['ItemCompra']) && is_array($post['ItemCompra'])) {
                        foreach ($post['ItemCompra'] as $itemData) {
                            $item = new ItemCompra();
                            $item->compra_id = $model->id;
                            $item->load(['ItemCompra' => $itemData]);
                            if (!$item->save()) {
                                throw new \Exception('Erro ao salvar item: ' . json_encode($item->errors));
                            }
                        }
                    }
                    
                    // Recalcula valor total
                    $model->recalcularValorTotal();
                    $model->save(false);

                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Compra cadastrada com sucesso!');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Erro ao salvar compra: ' . $e->getMessage());
            }
        }

        $fornecedores = Fornecedor::getListaDropdownArray(Yii::$app->user->id);
        $produtos = Produto::find()
            ->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])
            ->orderBy('nome')
            ->all();

        return $this->render('create', [
            'model' => $model,
            'itens' => $itens,
            'fornecedores' => $fornecedores,
            'produtos' => $produtos,
        ]);
    }

    /**
     * Atualiza um modelo Compra existente.
     * @param string $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // Não permite editar compras concluídas ou canceladas
        if ($model->status_compra === Compra::STATUS_CONCLUIDA) {
            Yii::$app->session->setFlash('error', 'Não é possível editar uma compra concluída. O estoque já foi atualizado.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->status_compra === Compra::STATUS_CANCELADA) {
            Yii::$app->session->setFlash('error', 'Não é possível editar uma compra cancelada.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $post = Yii::$app->request->post();

        if ($model->load($post)) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    // Remove itens antigos
                    ItemCompra::deleteAll(['compra_id' => $model->id]);

                    // Adiciona novos itens
                    if (isset($post['ItemCompra']) && is_array($post['ItemCompra'])) {
                        foreach ($post['ItemCompra'] as $itemData) {
                            $item = new ItemCompra();
                            $item->compra_id = $model->id;
                            $item->load(['ItemCompra' => $itemData]);
                            if (!$item->save()) {
                                throw new \Exception('Erro ao salvar item: ' . json_encode($item->errors));
                            }
                        }
                    }

                    // Recalcula valor total
                    $model->recalcularValorTotal();
                    $model->save(false);

                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Compra atualizada com sucesso!');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Erro ao atualizar compra: ' . $e->getMessage());
            }
        }

        $itens = $model->itens;
        $fornecedores = Fornecedor::getListaDropdownArray(Yii::$app->user->id);
        $produtos = Produto::find()
            ->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])
            ->orderBy('nome')
            ->all();

        return $this->render('update', [
            'model' => $model,
            'itens' => $itens,
            'fornecedores' => $fornecedores,
            'produtos' => $produtos,
        ]);
    }

    /**
     * Conclui uma compra (atualiza estoque)
     * IMPORTANTE: O estoque só é atualizado quando a compra é concluída explicitamente
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    public function actionConcluir($id)
    {
        $model = $this->findModel($id);

        if ($model->status_compra === Compra::STATUS_CONCLUIDA) {
            Yii::$app->session->setFlash('warning', 'Esta compra já está concluída.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if (empty($model->itens)) {
            Yii::$app->session->setFlash('error', 'Não é possível concluir uma compra sem itens.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Muda o status para CONCLUIDA
            $model->status_compra = Compra::STATUS_CONCLUIDA;
            if (!$model->save()) {
                throw new \Exception('Erro ao salvar compra: ' . json_encode($model->errors));
            }

            // Atualiza estoque dos produtos
            $itensAtualizados = 0;
            $errosEstoque = [];
            
            foreach ($model->itens as $item) {
                if ($item->atualizarEstoque()) {
                    $itensAtualizados++;
                } else {
                    $errosEstoque[] = $item->produto->nome ?? 'Produto desconhecido';
                }
            }

            if (!empty($errosEstoque)) {
                throw new \Exception('Erro ao atualizar estoque dos produtos: ' . implode(', ', $errosEstoque));
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', "Compra concluída com sucesso! O estoque de {$itensAtualizados} produto(s) foi atualizado.");
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Erro ao concluir compra: ' . $e->getMessage());
            Yii::error('Erro ao concluir compra: ' . $e->getMessage(), __METHOD__);
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Cancela uma compra
     * IMPORTANTE: Se a compra já estava concluída, reverte o estoque
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    public function actionCancelar($id)
    {
        $model = $this->findModel($id);

        if ($model->status_compra === Compra::STATUS_CANCELADA) {
            Yii::$app->session->setFlash('warning', 'Esta compra já está cancelada.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Se a compra estava concluída, precisa reverter o estoque
            $estavaConcluida = ($model->status_compra === Compra::STATUS_CONCLUIDA);
            
            $model->status_compra = Compra::STATUS_CANCELADA;
            if (!$model->save()) {
                throw new \Exception('Erro ao salvar compra: ' . json_encode($model->errors));
            }

            // Se estava concluída, reverte o estoque
            if ($estavaConcluida && !empty($model->itens)) {
                $itensRevertidos = 0;
                $errosEstoque = [];
                
                foreach ($model->itens as $item) {
                    if ($item->reverterEstoque()) {
                        $itensRevertidos++;
                    } else {
                        $errosEstoque[] = $item->produto->nome ?? 'Produto desconhecido';
                    }
                }

                if (!empty($errosEstoque)) {
                    throw new \Exception('Erro ao reverter estoque dos produtos: ' . implode(', ', $errosEstoque));
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', "Compra cancelada com sucesso! O estoque de {$itensRevertidos} produto(s) foi revertido.");
            } else {
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Compra cancelada com sucesso!');
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Erro ao cancelar compra: ' . $e->getMessage());
            Yii::error('Erro ao cancelar compra: ' . $e->getMessage(), __METHOD__);
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Deleta um modelo Compra existente.
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Não permite excluir compras concluídas
        if ($model->status_compra === Compra::STATUS_CONCLUIDA) {
            Yii::$app->session->setFlash('error', 'Não é possível excluir uma compra concluída.');
            return $this->redirect(['index']);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Compra excluída com sucesso!');
        return $this->redirect(['index']);
    }

    /**
     * Histórico de compras por produto (com comparação de preços)
     * @param string $produto_id
     * @return string
     */
    public function actionHistoricoProduto($produto_id = null)
    {
        $produto = null;
        $historico = [];

        if ($produto_id) {
            $produto = Produto::findOne(['id' => $produto_id, 'usuario_id' => Yii::$app->user->id]);
            
            if ($produto) {
                // Busca histórico usando a view ou query direta
                $historico = Yii::$app->db->createCommand("
                    SELECT 
                        ic.produto_id,
                        p.nome AS nome_produto,
                        ic.compra_id,
                        c.data_compra,
                        c.fornecedor_id,
                        f.nome_fantasia AS nome_fornecedor,
                        ic.preco_unitario,
                        ic.quantidade,
                        ic.valor_total_item,
                        c.numero_nota_fiscal,
                        c.status_compra,
                        ROW_NUMBER() OVER (
                            PARTITION BY ic.produto_id, c.fornecedor_id 
                            ORDER BY c.data_compra DESC
                        ) AS ordem_compra_fornecedor
                    FROM prest_itens_compra ic
                    INNER JOIN prest_compras c ON ic.compra_id = c.id
                    INNER JOIN prest_produtos p ON ic.produto_id = p.id
                    INNER JOIN prest_fornecedores f ON c.fornecedor_id = f.id
                    WHERE ic.produto_id = :produto_id
                        AND c.usuario_id = :usuario_id
                        AND c.status_compra != 'CANCELADA'
                    ORDER BY c.data_compra DESC
                ", [
                    ':produto_id' => $produto_id,
                    ':usuario_id' => Yii::$app->user->id
                ])->queryAll();
            }
        }

        $produtos = Produto::find()
            ->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])
            ->orderBy('nome')
            ->all();

        return $this->render('historico-produto', [
            'produto' => $produto,
            'produtos' => $produtos,
            'historico' => $historico,
        ]);
    }

    /**
     * Encontra o modelo Compra baseado no valor da chave primária.
     * @param string $id
     * @return Compra o modelo carregado
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    protected function findModel($id)
    {
        if (($model = Compra::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A compra solicitada não existe.');
    }
}

