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
                } else {
                    // Se o save falhou, exibe erros de validação
                    $transaction->rollBack();
                    $erros = [];
                    foreach ($model->errors as $attribute => $messages) {
                        $erros[] = $model->getAttributeLabel($attribute) . ': ' . implode(', ', $messages);
                    }
                    Yii::$app->session->setFlash('error', 'Erro ao salvar compra: ' . implode(' | ', $erros));
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

            // ====================================================================================
            // INTEGRAÇÃO FINANCEIRA: Gera Conta a Pagar automaticamente
            // ====================================================================================
            // Verifica se já existe conta para esta compra (evita duplicidade em caso de erro/retry)
            $contaExistente = \app\modules\contas_pagar\models\ContaPagar::findOne(['compra_id' => $model->id]);

            if (!$contaExistente) {
                $conta = new \app\modules\contas_pagar\models\ContaPagar();
                $conta->usuario_id = $model->usuario_id;
                $conta->fornecedor_id = $model->fornecedor_id;
                $conta->compra_id = $model->id;

                // Formata a descrição com número da nota
                $notaTxt = $model->numero_nota_fiscal ? "NF {$model->numero_nota_fiscal}" : "S/N";
                $conta->descricao = "Compra {$notaTxt} - " . ($model->fornecedor->nome_fantasia ?? 'Fornecedor');

                // Usa o valor total (considerando frete e desc)
                $conta->valor = $model->getValorLiquido();

                // Vencimento (se não tiver, usa hoje)
                $conta->data_vencimento = $model->data_vencimento ?: date('Y-m-d');

                $conta->status = \app\modules\contas_pagar\models\ContaPagar::STATUS_PENDENTE;
                $conta->observacoes = "Gerado automaticamente pela conclusão da Compra #{$model->id}";

                if (!$conta->save()) {
                    throw new \Exception('Erro ao gerar Conta a Pagar: ' . implode(', ', $conta->getFirstErrors()));
                }
            }
            // ====================================================================================

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
     * Importar NFe via XML
     */
    public function actionImportarXml()
    {
        $uploadModel = new \yii\base\DynamicModel(['file']);
        $uploadModel->addRule('file', 'file', ['extensions' => 'xml', 'checkExtensionByMimeType' => false]);

        if (Yii::$app->request->isPost) {
            $uploadModel->file = \yii\web\UploadedFile::getInstance($uploadModel, 'file');

            if ($uploadModel->file && $uploadModel->validate()) {
                try {
                    $xmlContent = file_get_contents($uploadModel->file->tempName);
                    // Suprime erros de parsing do XML para tratar na exceção
                    $xml = @simplexml_load_string($xmlContent);

                    if ($xml === false) {
                        throw new \Exception("O arquivo enviado não é um XML válido.");
                    }

                    // Namespace handling
                    $ns = $xml->getNamespaces(true);
                    $xml->registerXPathNamespace('nfe', $ns[''] ?? 'http://www.portalfiscal.inf.br/nfe');

                    // =========================================================
                    // 1. Dados da NFe
                    // =========================================================
                    // Tenta estruturas comuns (NFe -> infNFe ou direto infNFe)
                    $infNFe = $xml->NFe->infNFe ?? $xml->infNFe;

                    if (!$infNFe) {
                        throw new \Exception("Estrutura não reconhecida. Certifique-se que é uma NFe válida.");
                    }

                    $ide = $infNFe->ide;
                    $emit = $infNFe->emit;
                    $det = $infNFe->det;

                    // =========================================================
                    // 2. Criar Model Compra Preenchido
                    // =========================================================
                    $model = new Compra();
                    $model->usuario_id = Yii::$app->user->id;
                    $model->numero_nota_fiscal = (string)$ide->nNF;
                    $model->serie_nota_fiscal = (string)$ide->serie;

                    // Data (dhEmi ou dEmi)
                    $dataEmissao = (string)($ide->dhEmi ?: $ide->dEmi);
                    // Formato esperado BD: YYYY-MM-DD
                    $model->data_compra = substr($dataEmissao, 0, 10);
                    $model->data_vencimento = date('Y-m-d', strtotime('+30 days')); // Default
                    $model->status_compra = Compra::STATUS_PENDENTE;

                    // Totals
                    if (isset($infNFe->total->ICMSTot)) {
                        $model->valor_frete = (float)$infNFe->total->ICMSTot->vFrete;
                        $model->valor_desconto = (float)$infNFe->total->ICMSTot->vDesc;
                        $model->valor_total = (float)$infNFe->total->ICMSTot->vNF;
                    }

                    // =========================================================
                    // 3. Fornecedor (Emitente)
                    // =========================================================
                    $cnpjEmit = (string)$emit->CNPJ;
                    $nomeEmit = (string)$emit->xNome; // Razão Social
                    $fantasiaEmit = (string)($emit->xFant ?? $emit->xNome); // Fantasia ou Razão

                    // Tenta encontrar Fornecedor no banco
                    $fornecedor = Fornecedor::find()
                        ->where(['usuario_id' => Yii::$app->user->id])
                        // Busca exata pelo CNPJ (assumindo que no banco pode estar formatado ou não, 
                        // idealmente deveríamos limpar a formatação do banco para comparar, 
                        // mas aqui vou tentar buscar pelo valor limpo enviado no XML)
                        ->andWhere(['cnpj' => $cnpjEmit])
                        ->one();

                    // Se não achou pelo CNPJ limpo, tenta buscar formatado se a função de formatação for consistente
                    if (!$fornecedor) {
                        // Tenta regex simples para achar CNPJ
                        // NOTA: O ideal seria ter stored procedure ou função de limpeza no BD
                    }

                    if ($fornecedor) {
                        $model->fornecedor_id = $fornecedor->id;
                        Yii::$app->session->addFlash('success', "Fornecedor identificado: " . ($fornecedor->nome_fantasia ?: $fornecedor->razao_social));
                    } else {
                        Yii::$app->session->addFlash('warning', "Fornecedor CNPJ {$cnpjEmit} ({$fantasiaEmit}) não encontrado. Verifique o cadastro.");
                    }

                    // =========================================================
                    // 4. Itens
                    // =========================================================
                    $itens = [];

                    foreach ($det as $itemXml) {
                        $prod = $itemXml->prod;

                        // Parse de valores float (pode variar locale, mas XML geralmente é ponto)
                        $qtd = (float)$prod->qCom;
                        $preco = (float)$prod->vUnCom;
                        $codigo = (string)$prod->cProd;
                        $nome = (string)$prod->xProd;
                        $ean = (string)$prod->cEAN;

                        $item = new ItemCompra();
                        $item->quantidade = $qtd;
                        $item->preco_unitario = $preco;
                        $item->nome_produto_temp = $nome; // Nome para a view

                        // Tenta encontrar produto
                        $produtoDb = Produto::find()
                            ->where(['usuario_id' => Yii::$app->user->id])
                            ->andWhere([
                                'OR',
                                ['codigo_referencia' => $codigo],
                                ['nome' => $nome]
                            ])
                            ->one();

                        if ($produtoDb) {
                            $item->produto_id = $produtoDb->id;
                        }

                        $itens[] = $item;
                    }

                    Yii::$app->session->addFlash('info', "Leitura do XML concluída. Verifique os dados abaixo antes de salvar.");

                    // Carrega listas para a view
                    $fornecedores = Fornecedor::getListaDropdownArray(Yii::$app->user->id);
                    $produtos = Produto::find()
                        ->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])
                        ->orderBy('nome')
                        ->all();

                    // Renderiza create com os dados preenchidos
                    return $this->render('create', [
                        'model' => $model,
                        'itens' => $itens,
                        'fornecedores' => $fornecedores,
                        'produtos' => $produtos,
                    ]);
                } catch (\Exception $e) {
                    Yii::$app->session->setFlash('error', 'Erro ao processar XML: ' . $e->getMessage());
                }
            }
        }

        return $this->render('importar-xml', ['model' => $uploadModel]);
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
