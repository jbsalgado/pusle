<?php

namespace app\modules\prestanista\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;
use yii\data\Pagination;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\HistoricoCobranca;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\StatusParcela;
use app\modules\vendas\models\StatusVenda;
use app\modules\vendas\models\CarteiraCobranca;
use app\modules\vendas\models\PeriodoCobranca;

/**
 * Gestão de Cartões de Crediário Prestanista
 */
class CartaoController extends Controller
{
    public function actionIndex(
        $q = null,
        $status = 'ABERTO',
        $vendedor_id = null,
        $cobrador_id = null,
        $cidade = null,
        $bairro = null,
        $data_inicio = null,
        $data_fim = null
    ) {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        $query = Venda::findPrestanista($usuarioId)
            ->leftJoin('prest_clientes c', 'c.id = v.cliente_id')
            ->with(['cliente', 'itens.produto', 'parcelas.formaPagamento', 'usuario', 'vendedor'])
            ->orderBy(['v.data_venda' => SORT_DESC, 'v.id' => SORT_DESC]);

        if ($q) {
            $qTrim = trim($q);
            $query->andWhere([
                'or',
                ['ilike', 'c.nome_completo', $qTrim],
                ['ilike', 'c.cpf', $qTrim],
                ['ilike', 'c.telefone', $qTrim],
                ['ilike', 'c.endereco_logradouro', $qTrim],
                ['ilike', 'c.endereco_bairro', $qTrim],
                ['ilike', 'c.endereco_cidade', $qTrim],
                ['cast(v.id as text)' => $qTrim],
            ]);
        }

        if ($status === 'ABERTO') {
            $query->andWhere(['v.status_venda_codigo' => ['EM_ABERTO', 'PARCIALMENTE_PAGA']]);
        } elseif ($status === 'QUITADO') {
            $query->andWhere(['v.status_venda_codigo' => ['FINALIZADA', 'QUITADA']]);
        } elseif ($status === 'CANCELADO') {
            $query->andWhere(['v.status_venda_codigo' => 'CANCELADA']);
        }

        if ($vendedor_id) {
            $query->andWhere(['v.colaborador_vendedor_id' => $vendedor_id]);
        }

        if ($cobrador_id) {
            $query->andWhere([
                'or',
                [
                    'exists',
                    (new \yii\db\Query())
                        ->from('prest_parcelas p')
                        ->where('p.venda_id = v.id')
                        ->andWhere(['p.cobrador_id' => $cobrador_id])
                ],
                [
                    'exists',
                    (new \yii\db\Query())
                        ->from('prest_carteira_cobranca cc')
                        ->where('cc.cliente_id = v.cliente_id')
                        ->andWhere(['cc.cobrador_id' => $cobrador_id, 'cc.ativo' => true])
                ],
                [
                    'exists',
                    (new \yii\db\Query())
                        ->from('prest_historico_cobranca hc')
                        ->where('hc.cliente_id = v.cliente_id')
                        ->andWhere(['hc.cobrador_id' => $cobrador_id])
                ]
            ]);
        }

        if ($cidade) {
            $query->andWhere(['ilike', 'c.endereco_cidade', trim($cidade)]);
        }

        if ($bairro) {
            $query->andWhere(['ilike', 'c.endereco_bairro', trim($bairro)]);
        }

        if ($data_inicio) {
            $query->andWhere(['>=', 'v.data_venda', $data_inicio]);
        }

        if ($data_fim) {
            $query->andWhere(['<=', 'v.data_venda', $data_fim]);
        }

        $cidades = Cliente::find()
            ->select('endereco_cidade')
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['not', ['endereco_cidade' => null]])
            ->andWhere(['!=', 'trim(endereco_cidade)', ''])
            ->distinct()
            ->orderBy(['endereco_cidade' => SORT_ASC])
            ->column();

        $bairrosQuery = Cliente::find()
            ->select('endereco_bairro')
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['not', ['endereco_bairro' => null]])
            ->andWhere(['!=', 'trim(endereco_bairro)', '']);
        if ($cidade) {
            $bairrosQuery->andWhere(['ilike', 'endereco_cidade', trim($cidade)]);
        }
        $bairros = $bairrosQuery->distinct()->orderBy(['endereco_bairro' => SORT_ASC])->column();

        $vendedores = Colaborador::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->andWhere(['or', ['eh_vendedor' => true], ['eh_vendedor' => null]])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->all();

        $cobradores = Colaborador::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->andWhere(['or', ['eh_cobrador' => true], ['eh_cobrador' => null]])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->all();

        $countQuery = clone $query;
        $totalCartoes = $countQuery->count();

        $pages = new Pagination([
            'totalCount' => $totalCartoes,
            'pageSize' => 12,
            'defaultPageSize' => 12,
        ]);

        $cartoes = $query
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->all();

        return $this->render('index', [
            'cartoes' => $cartoes,
            'pages' => $pages,
            'q' => $q,
            'status' => $status,
            'vendedor_id' => $vendedor_id,
            'cobrador_id' => $cobrador_id,
            'cidade' => $cidade,
            'bairro' => $bairro,
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'cidades' => $cidades,
            'bairros' => $bairros,
            'vendedores' => $vendedores,
            'cobradores' => $cobradores,
            'totalCartoes' => $totalCartoes,
            'totalCount' => $totalCartoes,
        ]);
    }

    public function actionView($id)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario->loja_id ?? $usuario->id;

        $cartao = Venda::find()
            ->where(['id' => $id, 'usuario_id' => $usuarioId])
            ->with(['cliente', 'itens.produto', 'parcelas.formaPagamento', 'parcelas.cobrador'])
            ->one();

        if (!$cartao) {
            throw new NotFoundHttpException('Cartão de crediário não encontrado.');
        }

        // Histórico de cobranças e baixas
        $historico = HistoricoCobranca::find()
            ->where(['usuario_id' => $usuarioId, 'cliente_id' => $cartao->cliente_id])
            ->with('cobrador')
            ->orderBy(['data_acao' => SORT_ASC])
            ->all();

        // Identifica cobrador atual das parcelas pendentes
        $cobradorAtual = null;
        if (!empty($cartao->parcelas)) {
            foreach ($cartao->parcelas as $p) {
                if ($p->status_parcela_codigo === 'PENDENTE' && $p->cobrador) {
                    $cobradorAtual = $p->cobrador;
                    break;
                }
            }
        }

        // Lista de cobradores disponíveis para atribuição
        $cobradores = Colaborador::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->andWhere(['or', ['eh_cobrador' => true], ['eh_cobrador' => null]])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->all();

        return $this->render('view', [
            'cartao' => $cartao,
            'historico' => $historico,
            'cobradores' => $cobradores,
            'cobradorAtual' => $cobradorAtual,
        ]);
    }

    /**
     * Atribui ou altera o cobrador de rua responsável pelas parcelas pendentes deste cartão
     */
    public function actionAtribuirCobrador($id)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        $cartao = Venda::find()
            ->where(['id' => $id, 'usuario_id' => $usuarioId])
            ->one();

        if (!$cartao) {
            throw new NotFoundHttpException('Cartão de crediário não encontrado.');
        }

        if (Yii::$app->request->isPost) {
            $cobradorId = Yii::$app->request->post('cobrador_id');
            $cobrador = null;
            if ($cobradorId) {
                $cobrador = Colaborador::findOne(['id' => $cobradorId, 'usuario_id' => $usuarioId, 'ativo' => true]);
            }

            $periodo = PeriodoCobranca::getOuCriarPeriodoAtual($usuarioId);
            $periodoId = $periodo ? $periodo->id : null;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Atualiza ou cria a carteira de cobrança do cliente
                $carteira = null;
                if ($periodoId) {
                    $carteira = CarteiraCobranca::findOne(['usuario_id' => $usuarioId, 'cliente_id' => $cartao->cliente_id, 'periodo_id' => $periodoId]);
                }
                if (!$carteira) {
                    $carteira = CarteiraCobranca::findOne(['usuario_id' => $usuarioId, 'cliente_id' => $cartao->cliente_id]);
                }

                if ($cobrador) {
                    if ($carteira) {
                        $carteira->cobrador_id = $cobrador->id;
                        if ($periodoId) {
                            $carteira->periodo_id = $periodoId;
                        }
                        $carteira->ativo = true;
                        $carteira->save(false);
                    } else {
                        $carteira = new CarteiraCobranca();
                        $carteira->usuario_id = $usuarioId;
                        $carteira->cliente_id = $cartao->cliente_id;
                        $carteira->periodo_id = $periodoId;
                        $carteira->cobrador_id = $cobrador->id;
                        $carteira->data_distribuicao = date('Y-m-d H:i:s');
                        $carteira->ativo = true;
                        $carteira->total_parcelas = (int)$cartao->numero_parcelas;
                        $carteira->valor_total = (float)$cartao->valor_total;
                        $carteira->save(false);
                    }

                    // Atualiza todas as parcelas pendentes com o cobrador e carteira selecionados
                    Parcela::updateAll(
                        [
                            'cobrador_id' => $cobrador->id,
                            'carteira_cobranca_id' => $carteira->id,
                        ],
                        [
                            'venda_id' => $cartao->id,
                            'status_parcela_codigo' => StatusParcela::PENDENTE,
                        ]
                    );

                    Yii::$app->session->setFlash('success', "✓ Cartão atribuído com sucesso ao cobrador {$cobrador->nome_completo}!");
                } else {
                    if ($carteira) {
                        $carteira->cobrador_id = null;
                        $carteira->save(false);
                    }
                    Parcela::updateAll(
                        [
                            'cobrador_id' => null,
                            'carteira_cobranca_id' => null,
                        ],
                        [
                            'venda_id' => $cartao->id,
                            'status_parcela_codigo' => StatusParcela::PENDENTE,
                        ]
                    );
                    Yii::$app->session->setFlash('info', "Cartão desvinculado de cobrador.");
                }

                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Erro ao atribuir cobrador: ' . $e->getMessage());
            }
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $id]);
    }

    /**
     * Retorna o token público assinado para visualização externa do cartão
     *
     * @param string $cartaoId
     * @return string
     */
    public static function getPublicToken($cartaoId)
    {
        return substr(hash('sha256', (string)$cartaoId . 'pulse_prestanista_public_2026'), 0, 16);
    }

    /**
     * Retorna a URL pública absoluta para o cliente visualizar o cartão
     *
     * @param string $cartaoId
     * @return string
     */
    public static function getPublicUrl($cartaoId)
    {
        $token = self::getPublicToken($cartaoId);
        return Url::to(['/prestanista/cartao/publico', 'id' => $cartaoId, 'token' => $token], true);
    }

    /**
     * Ação pública de visualização do cartão de crediário
     * Exclusiva para consulta (somente leitura), sem permissão de alteração de dados
     * e com layout isolado sem navegação administrativa.
     *
     * @param string $id
     * @param string|null $token
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionPublico($id, $token = null)
    {
        $expectedToken = self::getPublicToken($id);
        if ($token !== null && $token !== $expectedToken) {
            throw new NotFoundHttpException('Link de visualização do crediário inválido ou expirado.');
        }

        $cartao = Venda::find()
            ->where(['id' => $id])
            ->with(['cliente', 'itens.produto', 'parcelas.formaPagamento', 'usuario', 'vendedor'])
            ->one();

        if (!$cartao) {
            throw new NotFoundHttpException('Cartão de crediário não encontrado.');
        }

        // Histórico de baixas e pagamentos efetuados
        $historico = HistoricoCobranca::find()
            ->where(['cliente_id' => $cartao->cliente_id, 'tipo_acao' => HistoricoCobranca::TIPO_PAGAMENTO])
            ->with('cobrador')
            ->orderBy(['data_acao' => SORT_ASC])
            ->all();

        $this->layout = false;

        return $this->render('publico', [
            'cartao' => $cartao,
            'historico' => $historico,
            'token' => $expectedToken,
        ]);
    }

    /**
     * Permite ajustar a frequência de cobrança do cartão e recalcular as datas das parcelas pendentes
     */
    public function actionAjustarFrequencia($id)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        $cartao = Venda::find()
            ->where(['id' => $id, 'usuario_id' => $usuarioId])
            ->with(['parcelas'])
            ->one();

        if (!$cartao) {
            throw new NotFoundHttpException('Cartão de crediário não encontrado.');
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $novaFrequencia = (int)($post['nova_frequencia'] ?? 7);
            if (!in_array($novaFrequencia, [1, 7, 15, 30])) {
                $novaFrequencia = 7;
            }
            $dataBase = !empty($post['data_base_vencimento']) ? $post['data_base_vencimento'] : ($cartao->data_primeiro_vencimento ?: date('Y-m-d'));

            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Atualiza observações com a nova tag de frequência
                $obs = $cartao->observacoes ?: '';
                if (preg_match('/\[FREQ:\d+\]/i', $obs)) {
                    $obs = preg_replace('/\[FREQ:\d+\]/i', "[FREQ:{$novaFrequencia}]", $obs);
                } else {
                    $obs = "[FREQ:{$novaFrequencia}] " . $obs;
                }
                $cartao->observacoes = trim($obs);
                $cartao->data_primeiro_vencimento = $dataBase;
                $cartao->save(false);

                // Recalcula datas das parcelas pendentes
                $parcelas = Parcela::find()
                    ->where(['venda_id' => $cartao->id])
                    ->orderBy(['numero_parcela' => SORT_ASC])
                    ->all();

                $dtAtual = new \DateTime($dataBase);
                $idxPendente = 0;
                foreach ($parcelas as $p) {
                    if ($p->status_parcela_codigo !== 'PAGA') {
                        if ($idxPendente === 0) {
                            $p->data_vencimento = $dtAtual->format('Y-m-d');
                        } else {
                            $dtAtual->modify("+{$novaFrequencia} days");
                            $p->data_vencimento = $dtAtual->format('Y-m-d');
                        }
                        $p->save(false);
                        $idxPendente++;
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Frequência do cartão e parcelas atualizadas com sucesso!');
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Erro ao atualizar frequência: ' . $e->getMessage());
            }
        }

        return $this->redirect(['view', 'id' => $cartao->id]);
    }

    /**
     * Registra o recebimento/baixa de uma parcela do cartão
     */
    public function actionReceberParcela($id)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        $cartao = Venda::find()
            ->where(['id' => $id, 'usuario_id' => $usuarioId])
            ->one();

        if (!$cartao) {
            throw new NotFoundHttpException('Cartão de crediário não encontrado.');
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $parcelaId = $post['parcela_id'] ?? null;
            $valorPago = (float)str_replace(',', '.', str_replace('.', '', $post['valor_pago'] ?? '0'));
            $dataPagamento = !empty($post['data_pagamento']) ? $post['data_pagamento'] : date('Y-m-d');
            $tipoPagamento = trim($post['tipo_pagamento'] ?? 'DINHEIRO');

            $parcela = Parcela::find()
                ->where(['id' => $parcelaId, 'venda_id' => $cartao->id])
                ->one();

            if (!$parcela) {
                Yii::$app->session->setFlash('error', 'Parcela não encontrada.');
                return $this->redirect(['view', 'id' => $cartao->id]);
            }

            if ($valorPago <= 0) {
                $valorPago = (float)$parcela->valor_parcela;
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Localiza ou cria FormaPagamento correspondente ao tipo (PIX, DINHEIRO, CARTAO, etc.)
                $formaPagamento = FormaPagamento::find()
                    ->where(['usuario_id' => $usuarioId, 'ativo' => true])
                    ->andWhere(['or',
                        ['ilike', 'nome', $tipoPagamento],
                        ['tipo' => $tipoPagamento]
                    ])
                    ->one();

                if (!$formaPagamento) {
                    $formaPagamento = new FormaPagamento();
                    $formaPagamento->usuario_id = $usuarioId;
                    $formaPagamento->nome = mb_strtoupper($tipoPagamento, 'UTF-8');
                    $formaPagamento->tipo = in_array($tipoPagamento, [FormaPagamento::TIPO_PIX, FormaPagamento::TIPO_DINHEIRO, FormaPagamento::TIPO_CARTAO, FormaPagamento::TIPO_BOLETO]) ? $tipoPagamento : FormaPagamento::TIPO_OUTRO;
                    $formaPagamento->ativo = true;
                    $formaPagamento->save(false);
                }

                $parcela->status_parcela_codigo = StatusParcela::PAGA;
                $parcela->data_pagamento = $dataPagamento;
                $parcela->valor_pago = $valorPago;
                $parcela->forma_pagamento_id = $formaPagamento->id;
                $parcela->save(false);

                // Registra Histórico de Cobrança / Baixa
                $hist = new HistoricoCobranca();
                $hist->usuario_id = $usuarioId;
                $hist->parcela_id = $parcela->id;
                $hist->cliente_id = $cartao->cliente_id;

                // Resolve cobrador_id de forma segura — deve ser ID válido em prest_colaboradores ou NULL.
                // NUNCA usar $cartao->usuario_id como fallback: ele é ID de prest_usuarios e
                // viola a FK prest_historico_cobranca.cobrador_id → prest_colaboradores(id).
                $cobradorIdHistorico = null;
                if (!empty($parcela->cobrador_id)) {
                    // Prioridade 1: cobrador atribuído diretamente à parcela (rota de cobrança)
                    $cobradorIdHistorico = $parcela->cobrador_id;
                } elseif (!empty($cartao->colaborador_vendedor_id)) {
                    // Prioridade 2: vendedor do cartão (é um Colaborador válido)
                    $cobradorIdHistorico = $cartao->colaborador_vendedor_id;
                } else {
                    // Prioridade 3: usuário logado que seja Colaborador (não dono da loja)
                    $colaboradorLogado = Colaborador::findOne([
                        'prest_usuario_login_id' => $usuario->id,
                        'usuario_id'             => $usuarioId,
                        'ativo'                  => true,
                    ]);
                    if ($colaboradorLogado) {
                        $cobradorIdHistorico = $colaboradorLogado->id;
                    }
                    // Se nenhum dos casos acima, cobrador_id fica NULL (pagamento direto pelo dono da loja)
                }
                $hist->cobrador_id = $cobradorIdHistorico;

                $hist->tipo_acao = HistoricoCobranca::TIPO_PAGAMENTO;
                $hist->valor_recebido = $valorPago;
                $hist->observacao = "Recebimento da {$parcela->numero_parcela}ª prestação via {$formaPagamento->nome}";
                $hist->data_acao = $dataPagamento . ' ' . date('H:i:s');
                $hist->save(false);

                // Atualiza status da venda
                $pendentes = Parcela::find()
                    ->where(['venda_id' => $cartao->id])
                    ->andWhere(['!=', 'status_parcela_codigo', StatusParcela::PAGA])
                    ->count();

                if ($pendentes == 0) {
                    $cartao->status_venda_codigo = StatusVenda::QUITADA;
                } else {
                    $cartao->status_venda_codigo = StatusVenda::PARCIALMENTE_PAGA;
                }
                $cartao->save(false);

                $transaction->commit();
                Yii::$app->session->setFlash('success', "Pagamento de R$ " . number_format($valorPago, 2, ',', '.') . " da {$parcela->numero_parcela}ª parcela registrado com sucesso via {$formaPagamento->nome}!");
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Erro ao registrar pagamento: ' . $e->getMessage());
            }
        }

        return $this->redirect(['view', 'id' => $cartao->id]);
    }

    /**
     * Estorna o pagamento de uma parcela voltando-a para PENDENTE
     */
    public function actionEstornarParcela($id, $parcela_id)
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        $cartao = Venda::find()
            ->where(['id' => $id, 'usuario_id' => $usuarioId])
            ->one();

        if (!$cartao) {
            throw new NotFoundHttpException('Cartão de crediário não encontrado.');
        }

        $parcela = Parcela::find()
            ->where(['id' => $parcela_id, 'venda_id' => $cartao->id])
            ->one();

        if ($parcela) {
            $parcela->status_parcela_codigo = StatusParcela::PENDENTE;
            $parcela->data_pagamento = null;
            $parcela->valor_pago = null;
            $parcela->save(false);

            HistoricoCobranca::deleteAll(['parcela_id' => $parcela->id, 'tipo_acao' => HistoricoCobranca::TIPO_PAGAMENTO]);

            $cartao->status_venda_codigo = StatusVenda::EM_ABERTO;
            $cartao->save(false);

            Yii::$app->session->setFlash('success', "Baixa da {$parcela->numero_parcela}ª parcela estornada com sucesso!");
        }

        return $this->redirect(['view', 'id' => $cartao->id]);
    }

    public function actionImprimir($id, $formato = 'a4')
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        $cartao = Venda::find()
            ->where(['id' => $id, 'usuario_id' => $usuarioId])
            ->with(['cliente', 'itens.produto', 'parcelas'])
            ->one();

        if (!$cartao) {
            throw new NotFoundHttpException('Cartão de crediário não encontrado.');
        }

        $this->layout = false; // Layout limpo para impressão direta

        return $this->render('imprimir', [
            'cartao' => $cartao,
            'formato' => $formato,
        ]);
    }

    public function actionImprimirLote($vendedor_id = null, $status = 'ABERTO')
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        $query = Venda::findPrestanista($usuarioId)
            ->with(['cliente', 'itens.produto', 'parcelas', 'vendedor'])
            ->orderBy(['v.id' => SORT_DESC]);

        if ($status === 'ABERTO') {
            $query->andWhere(['v.status_venda_codigo' => 'EM_ABERTO']);
        } elseif ($status === 'QUITADO') {
            $query->andWhere(['v.status_venda_codigo' => ['FINALIZADA', 'QUITADA']]);
        }

        if ($vendedor_id) {
            $query->andWhere(['v.colaborador_vendedor_id' => $vendedor_id]);
        }

        $cartoes = $query->limit(100)->all();
        $vendedores = Colaborador::find()->where(['usuario_id' => $usuarioId, 'ativo' => true])->all();

        // Se solicitado modo direto para impressão
        if (Yii::$app->request->get('modo') === 'papel') {
            $this->layout = false;
        }

        return $this->render('imprimir_lote', [
            'cartoes' => $cartoes,
            'vendedores' => $vendedores,
            'vendedor_id' => $vendedor_id,
            'status' => $status,
        ]);
    }

    public function actionNovo()
    {
        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $clienteId = $post['cliente_id'] ?? null;
                $vendedorId = $post['vendedor_id'] ?? null;
                $frequencia = (int)($post['frequencia'] ?? 7);
                $parcelasQtd = max(1, (int)($post['numero_parcelas'] ?? 1));
                $itens = $post['itens'] ?? [];
                $entrada = (float)str_replace(',', '.', str_replace('.', '', $post['valor_entrada'] ?? '0'));

                if (!$clienteId) {
                    throw new \Exception('Selecione um cliente.');
                }
                if (empty($itens)) {
                    throw new \Exception('Adicione ao menos um produto no cartão.');
                }

                $valorTotal = 0;
                foreach ($itens as $item) {
                    $valorTotal += ((float)$item['preco']) * ((int)$item['quantidade']);
                }

                // Busca ou cria forma de pagamento do tipo Crediário / Prestanista para a loja
                $formaPagamento = FormaPagamento::find()
                    ->where(['usuario_id' => $usuarioId, 'ativo' => true])
                    ->andWhere(['or',
                        ['ilike', 'nome', 'crediario'],
                        ['ilike', 'nome', 'carnê'],
                        ['ilike', 'nome', 'carne'],
                        ['ilike', 'nome', 'prestanista'],
                        ['tipo' => [FormaPagamento::TIPO_BOLETO, FormaPagamento::TIPO_OUTRO]]
                    ])
                    ->one();

                if (!$formaPagamento) {
                    $formaPagamento = FormaPagamento::find()
                        ->where(['usuario_id' => $usuarioId, 'ativo' => true])
                        ->one();
                }

                if (!$formaPagamento) {
                    $formaPagamento = new FormaPagamento();
                    $formaPagamento->usuario_id = $usuarioId;
                    $formaPagamento->nome = 'Crediário / Prestanista';
                    $formaPagamento->tipo = FormaPagamento::TIPO_BOLETO;
                    $formaPagamento->ativo = true;
                    $formaPagamento->aceita_parcelamento = true;
                    $formaPagamento->save(false);
                }

                $dataVendaInput = !empty($post['data_venda']) ? $post['data_venda'] : date('Y-m-d');
                $dataVenda = $dataVendaInput . ' ' . date('H:i:s');
                $primeiroVencimento = !empty($post['primeiro_vencimento']) 
                    ? $post['primeiro_vencimento'] 
                    : (!empty($post['data_primeiro_vencimento']) ? $post['data_primeiro_vencimento'] : date('Y-m-d', strtotime("{$dataVendaInput} +{$frequencia} days")));

                $venda = new Venda();
                $venda->usuario_id = $usuarioId;
                $venda->cliente_id = $clienteId;
                $venda->colaborador_vendedor_id = $vendedorId;
                $venda->forma_pagamento_id = $formaPagamento->id;
                $venda->valor_total = $valorTotal;
                $venda->numero_parcelas = $parcelasQtd;
                $venda->data_venda = $dataVenda;
                $venda->data_primeiro_vencimento = $primeiroVencimento;
                $venda->status_venda_codigo = 'EM_ABERTO';
                $venda->tipo_venda = Venda::TIPO_PRESTANISTA;
                $venda->observacoes = "[PRESTANISTA] [FREQ:{$frequencia}] Cartão de Crediário emitido via Gestão";

                if (!$venda->save()) {
                    throw new \Exception('Erro ao criar cartão: ' . json_encode($venda->errors));
                }

                foreach ($itens as $item) {
                    $vi = new VendaItem();
                    $vi->venda_id = $venda->id;
                    $vi->produto_id = $item['produto_id'];
                    $vi->quantidade = $item['quantidade'];
                    $vi->preco_unitario_venda = $item['preco'];
                    $vi->valor_total_item = $vi->quantidade * $vi->preco_unitario_venda;
                    if (!$vi->save()) {
                        throw new \Exception('Erro ao salvar item: ' . json_encode($vi->errors));
                    }
                }

                // Gera parcelas com base na Data da 1ª Parcela e na Frequência (intervalo em dias)
                $saldoAFinanciar = max(0, $valorTotal - $entrada);
                
                $venda->gerarParcelas($formaPagamento->id, $primeiroVencimento, $frequencia);

                // Se teve entrada, dá baixa na 1ª parcela ou cria registro de entrada
                if ($entrada > 0) {
                    $p1 = Parcela::find()->where(['venda_id' => $venda->id])->orderBy(['numero_parcela' => SORT_ASC])->one();
                    if ($p1) {
                        $p1->valor_pago = $entrada;
                        if ($entrada >= $p1->valor_parcela) {
                            $p1->status_parcela_codigo = 'PAGA';
                            $p1->data_pagamento = $dataVendaInput;
                        }
                        $p1->save(false);
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', "Cartão #{$venda->id} emitido com sucesso!");
                return $this->redirect(['view', 'id' => $venda->id]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        $clientes = Cliente::find()->where(['usuario_id' => $usuarioId])->orderBy(['nome_completo' => SORT_ASC])->limit(100)->all();
        $produtos = Produto::find()->where(['usuario_id' => $usuarioId])->orderBy(['nome' => SORT_ASC])->limit(100)->all();
        $vendedores = Colaborador::find()->where(['usuario_id' => $usuarioId, 'ativo' => true])->all();

        return $this->render('novo', [
            'clientes' => $clientes,
            'produtos' => $produtos,
            'vendedores' => $vendedores,
        ]);
    }

    /**
     * Cadastro Rápido de Cliente inline (AJAX) sem sair da tela de novo cartão
     */
    public function actionCadastrarClienteRapido()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $usuario = Yii::$app->user->identity;
        $usuarioId = $usuario ? $usuario->getTenantId() : null;

        if (!$usuarioId) {
            return [
                'success' => false,
                'message' => 'Sessão expirada ou usuário não autenticado.',
            ];
        }

        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => 'Método inválido. Requisição deve ser POST.',
            ];
        }

        $post = Yii::$app->request->post();

        $nomeCompleto = trim($post['nome_completo'] ?? '');
        $telefone = trim($post['telefone'] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', $post['cpf'] ?? '');
        $logradouro = trim($post['endereco_logradouro'] ?? '');
        $numero = trim($post['endereco_numero'] ?? '');
        $bairro = trim($post['endereco_bairro'] ?? '');
        $cidade = trim($post['endereco_cidade'] ?? '');
        $pontoReferencia = trim($post['ponto_referencia'] ?? '');
        $observacoes = trim($post['observacoes'] ?? '');

        if (empty($nomeCompleto)) {
            return [
                'success' => false,
                'message' => 'O nome completo do cliente é obrigatório.',
            ];
        }

        if (empty($telefone)) {
            return [
                'success' => false,
                'message' => 'O telefone/WhatsApp do cliente é obrigatório.',
            ];
        }

        if (empty($logradouro)) {
            return [
                'success' => false,
                'message' => 'O logradouro/rua é obrigatório para localização na rota.',
            ];
        }

        // Se número estiver vazio, definir como S/N
        if (empty($numero)) {
            $numero = 'S/N';
        }

        // Se bairro estiver vazio, definir como Centro
        if (empty($bairro)) {
            $bairro = 'Centro';
        }

        // Se cidade estiver vazia, buscar cidade de outro cliente ou da loja
        if (empty($cidade)) {
            $clienteAnterior = Cliente::find()->where(['usuario_id' => $usuarioId])->andWhere(['not', ['endereco_cidade' => null]])->orderBy(['data_criacao' => SORT_DESC])->one();
            $cidade = $clienteAnterior ? $clienteAnterior->endereco_cidade : 'Cidade';
        }

        $cliente = new Cliente();
        $cliente->usuario_id = $usuarioId;
        $cliente->nome_completo = $nomeCompleto;
        $cliente->telefone = $telefone;
        $cliente->endereco_logradouro = $logradouro;
        $cliente->endereco_numero = $numero;
        $cliente->endereco_bairro = $bairro;
        $cliente->endereco_cidade = $cidade;
        $cliente->ponto_referencia = !empty($pontoReferencia) ? $pontoReferencia : null;
        $cliente->observacoes = !empty($observacoes) ? $observacoes : '[Cadastro Rápido - Prestanista]';
        $cliente->ativo = true;

        if (!empty($cpf)) {
            if (strlen($cpf) !== 11) {
                return [
                    'success' => false,
                    'message' => 'O CPF deve ter 11 dígitos numéricos ou ser deixado em branco.',
                ];
            }
            $cliente->cpf = $cpf;
        } else {
            $cliente->cpf = null;
        }

        // Senha para satisfazer rules() do model e permitir futuro login no PWA
        $ultimosDigitos = preg_replace('/\D/', '', $telefone);
        $cliente->senha = strlen($ultimosDigitos) >= 4 ? substr($ultimosDigitos, -4) : '1234';

        if ($cliente->save()) {
            return [
                'success' => true,
                'message' => 'Cliente cadastrado com sucesso!',
                'cliente' => [
                    'id' => $cliente->id,
                    'nome' => $cliente->nome_completo,
                    'bairro' => $cliente->endereco_bairro,
                    'telefone' => $cliente->telefone,
                    'endereco_completo' => $cliente->getEnderecoCompleto(),
                ],
            ];
        }

        $erros = [];
        foreach ($cliente->errors as $campo => $mensagens) {
            $erros[] = implode(', ', $mensagens);
        }

        return [
            'success' => false,
            'message' => 'Erro ao salvar cliente: ' . implode(' | ', $erros),
            'errors' => $cliente->errors,
        ];
    }
}
