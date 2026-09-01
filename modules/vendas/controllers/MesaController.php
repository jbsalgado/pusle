<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Mesa;
use app\modules\vendas\models\Comanda;
use app\modules\vendas\models\ComandaItem;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\Colaborador;
use app\models\Usuario;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * MesaController — Gestão Interativa do Mapa de Mesas & Comandas (Food Service)
 */
class MesaController extends Controller
{
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
                    'abrir-mesa' => ['POST'],
                    'solicitar-conta' => ['POST'],
                    'liberar-mesa' => ['POST'],
                    'reverter-mesa' => ['POST'],
                    'adicionar-item' => ['POST'],
                    'remover-item' => ['POST'],
                    'processar-fechamento' => ['POST'],
                    'transferir-mesa' => ['POST'],
                    'criar-mesa' => ['POST'],
                    'excluir-mesa' => ['POST'],
                    'adicionar-mesa-rapida' => ['POST'],
                    'gerar-lote-mesas' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (in_array($action->id, ['atender-chamado', 'atender-mesa', 'atender-todos-chamados', 'chamados-pendentes', 'responder-mensagem-mesa', 'limpar-chat-mesa', 'mensagens-mesa-admin', 'liberar-mesa-direct'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Exibe o Grid Gráfico do Mapa de Mesas & Comandas
     */
    public function actionIndex()
    {
        $tenantId = \app\components\TenantHelper::getId();

        // Busca todas as mesas do tenant
        $mesas = Mesa::find()
            ->where(['usuario_id' => $tenantId])
            ->orderBy(['numero_mesa' => SORT_ASC])
            ->all();

        // Se for o primeiro acesso e a loja ainda não tiver mesas salvas, gera 10 mesas padrão
        if (empty($mesas)) {
            for ($i = 1; $i <= 10; $i++) {
                $numStr = sprintf('%02d', $i);
                $mesa = new Mesa();
                $mesa->usuario_id = $tenantId;
                $mesa->numero_mesa = $numStr;
                $mesa->nome_identificador = 'Salão Principal';
                $mesa->lugares = 4;
                $mesa->status = Mesa::STATUS_LIVRE;
                $mesa->save(false);
            }

            $mesas = Mesa::find()
                ->where(['usuario_id' => $tenantId])
                ->orderBy(['numero_mesa' => SORT_ASC])
                ->all();
        }

        // Estatísticas para o topo do painel
        $totalMesas = count($mesas);
        $livres = 0;
        $ocupadas = 0;
        $aguardandoConta = 0;
        $reservadas = 0;
        $faturamentoAcumulado = 0.00;

        foreach ($mesas as $m) {
            switch ($m->status) {
                case Mesa::STATUS_LIVRE:
                    $livres++;
                    break;
                case Mesa::STATUS_OCUPADA:
                    $ocupadas++;
                    $faturamentoAcumulado += $m->getConsumoTotal();
                    break;
                case Mesa::STATUS_AGUARDANDO_CONTA:
                    $aguardandoConta++;
                    $faturamentoAcumulado += $m->getConsumoTotal();
                    break;
                case Mesa::STATUS_RESERVADA:
                    $reservadas++;
                    break;
            }
        }

        $colaboradores = Colaborador::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'mesas' => $mesas,
            'colaboradores' => $colaboradores,
            'totalMesas' => $totalMesas,
            'livres' => $livres,
            'ocupadas' => $ocupadas,
            'aguardandoConta' => $aguardandoConta,
            'reservadas' => $reservadas,
            'faturamentoAcumulado' => $faturamentoAcumulado,
        ]);
    }

    /**
     * Ação: Abrir Mesa / Iniciar Atendimento
     */
    public function actionAbrirMesa()
    {
        $tenantId = \app\components\TenantHelper::getId();
        $request = Yii::$app->request->post();

        $mesaId = $request['mesa_id'] ?? null;
        $clienteNome = $request['cliente_nome'] ?? 'Cliente';
        $garcomNome = trim($request['garcom_nome'] ?? '');

        if (!$mesaId) {
            Yii::$app->session->setFlash('error', 'Mesa não informada.');
            return $this->redirect(['index']);
        }

        $mesa = Mesa::findOne(['id' => $mesaId, 'usuario_id' => $tenantId]);
        if (!$mesa) {
            Yii::$app->session->setFlash('error', 'Mesa não encontrada.');
            return $this->redirect(['index']);
        }

        // Atualiza status da mesa
        $mesa->status = Mesa::STATUS_OCUPADA;
        $mesa->save(false);

        // Cria a comanda atrelada
        $comanda = new Comanda();
        $comanda->usuario_id = $tenantId;
        $comanda->mesa_id = $mesa->id;
        $comanda->numero_comanda = 'MESA-' . $mesa->numero_mesa;
        $comanda->cliente_nome = $clienteNome;
        if (empty($garcomNome)) {
            $colabLogado = Colaborador::getColaboradorLogado();
            if ($colabLogado) {
                $garcomNome = $colabLogado->nome_completo;
            }
        }

        if (!empty($garcomNome)) {
            $comanda->garcom_nome = $garcomNome;
        }
        $comanda->status = Comanda::STATUS_ABERTA;
        $comanda->save(false);

        Yii::$app->session->setFlash('success', "Mesa {$mesa->numero_mesa} aberta com sucesso para {$clienteNome}!");
        return $this->redirect(['index']);
    }

    /**
     * Ação: Adicionar Item/Pedido à Mesa (AJAX / POST)
     */
    public function actionAdicionarItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();
        $request = Yii::$app->request->post();

        $mesaId = $request['mesa_id'] ?? null;
        $produtoId = $request['produto_id'] ?? null;
        $quantidade = (float)($request['quantidade'] ?? 1);
        $observacoes = trim($request['observacoes'] ?? '');
        $destino = $request['destino_preparo'] ?? ComandaItem::DESTINO_COZINHA;

        if (!$mesaId || !$produtoId) {
            return ['success' => false, 'message' => 'Mesa e Produto são obrigatórios.'];
        }

        $mesa = Mesa::findOne(['id' => $mesaId, 'usuario_id' => $tenantId]);
        if (!$mesa) {
            return ['success' => false, 'message' => 'Mesa não encontrada.'];
        }

        // Se a mesa estiver livre por algum motivo, abre automaticamente
        if (!$mesa->comandaAtiva) {
            $mesa->status = Mesa::STATUS_OCUPADA;
            $mesa->save(false);

            $comanda = new Comanda();
            $comanda->usuario_id = $tenantId;
            $comanda->mesa_id = $mesa->id;
            $comanda->numero_comanda = 'MESA-' . $mesa->numero_mesa;
            $comanda->cliente_nome = 'Cliente';
            $comanda->status = Comanda::STATUS_ABERTA;
            $comanda->save(false);
        } else {
            $comanda = $mesa->comandaAtiva;
        }

        $produto = Produto::findOne(['id' => $produtoId, 'usuario_id' => $tenantId]);
        if (!$produto) {
            return ['success' => false, 'message' => 'Produto não encontrado.'];
        }

        $destino = !empty($request['destino_preparo']) ? $request['destino_preparo'] : $produto->getDestinoPreparo();

        // Cria o item da comanda
        $item = new ComandaItem();
        $item->comanda_id = $comanda->id;
        $item->produto_id = $produto->id;
        $item->quantidade = $quantidade > 0 ? $quantidade : 1;
        $valorAdicional = (float)($request['valor_adicional'] ?? 0);
        $item->valor_unitario = (float)$produto->getPrecoFinal() + $valorAdicional;
        $item->observacoes = $observacoes;
        $item->destino_preparo = $destino;
        $item->status_preparo = ComandaItem::STATUS_PENDENTE;

        if ($item->save()) {
            return [
                'success' => true,
                'message' => "{$produto->nome} adicionado à Mesa {$mesa->numero_mesa}!",
                'consumo_total' => number_format($mesa->getConsumoTotal(), 2, ',', '.')
            ];
        }

        return ['success' => false, 'message' => 'Erro ao salvar item da comanda: ' . implode(', ', $item->getFirstErrors())];
    }

    /**
     * Retorna o Extrato do Consumo de uma Mesa (JSON para o Modal)
     */
    public function actionVerConsumoJson($mesa_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();

        $mesa = Mesa::findOne(['id' => $mesa_id, 'usuario_id' => $tenantId]);
        if (!$mesa) {
            return ['success' => false, 'message' => 'Mesa não encontrada.'];
        }

        $comanda = $mesa->getOuCriarComandaAtiva();
        $itensData = [];

        foreach ($comanda->itens as $item) {
            $itensData[] = [
                'id' => $item->id,
                'produto_nome' => $item->produto ? $item->produto->nome : 'Produto Removido',
                'quantidade' => (float)$item->quantidade,
                'valor_unitario' => (float)$item->valor_unitario,
                'valor_unitario_formatado' => number_format($item->valor_unitario, 2, ',', '.'),
                'subtotal' => (float)$item->getSubtotal(),
                'subtotal_formatado' => number_format($item->getSubtotal(), 2, ',', '.'),
                'observacoes' => $item->observacoes,
                'destino_preparo' => $item->destino_preparo,
                'status_preparo' => $item->status_preparo,
                'hora_pedido' => date('H:i', strtotime($item->data_pedido)),
            ];
        }

        return [
            'success' => true,
            'mesa_numero' => $mesa->numero_mesa,
            'cliente_nome' => $comanda->cliente_nome ?: 'Cliente',
            'comanda_numero' => $comanda->numero_comanda,
            'valor_total' => (float)$comanda->getValorTotal(),
            'valor_total_formatado' => number_format($comanda->getValorTotal(), 2, ',', '.'),
            'itens' => $itensData,
        ];
    }

    /**
     * Remove um item da comanda
     */
    public function actionRemoverItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();
        $itemId = Yii::$app->request->post('item_id');

        $item = ComandaItem::find()
            ->alias('ci')
            ->joinWith(['comanda c'])
            ->where(['ci.id' => $itemId, 'c.usuario_id' => $tenantId])
            ->one();

        if ($item) {
            $comandaId = $item->comanda_id;
            $item->delete();

            $comanda = Comanda::findOne($comandaId);
            $total = $comanda ? $comanda->getValorTotal() : 0.00;

            return [
                'success' => true,
                'message' => 'Item removido do consumo.',
                'valor_total_formatado' => number_format($total, 2, ',', '.')
            ];
        }

        return ['success' => false, 'message' => 'Item não encontrado.'];
    }

    /**
     * Busca rápida de produtos ativos em JSON para o autocompletar do modal
     */
    public function actionBuscarProdutosJson($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();

        $query = Produto::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->limit(20);

        if (!empty($q)) {
            $query->andWhere(['or',
                ['ilike', 'nome', $q],
                ['ilike', 'codigo_barras', $q]
            ]);
        }

        $produtos = $query->orderBy(['nome' => SORT_ASC])->all();
        $res = [];

        foreach ($produtos as $p) {
            $precoFinal = (float)$p->getPrecoFinal();
            $res[] = [
                'id' => $p->id,
                'nome' => $p->nome,
                'preco' => $precoFinal,
                'preco_formatado' => number_format($precoFinal, 2, ',', '.'),
                'codigo' => $p->codigo_barras,
            ];
        }

        return $res;
    }

    /**
     * Ação: Solicitar Pré-Conta da Mesa
     */
    public function actionSolicitarConta()
    {
        $tenantId = \app\components\TenantHelper::getId();
        $mesaId = Yii::$app->request->post('mesa_id');

        $mesa = Mesa::findOne(['id' => $mesaId, 'usuario_id' => $tenantId]);
        if ($mesa) {
            // Impedir pedir conta se a comanda estiver zerada/sem pedidos
            if (!$mesa->comandaAtiva || $mesa->getConsumoTotal() <= 0) {
                Yii::$app->session->setFlash('error', "Não é possível solicitar a conta da Mesa {$mesa->numero_mesa} pois ela não possui pedidos/consumo lançados.");
                return $this->redirect(['index']);
            }

            $mesa->status = Mesa::STATUS_AGUARDANDO_CONTA;
            $mesa->save(false);
            Yii::$app->session->setFlash('info', "Pré-conta solicitada para a Mesa {$mesa->numero_mesa}.");
        }

        return $this->redirect(['index']);
    }

    /**
     * Ação: Reverter Mesa (Voltar de Aguardando Conta para Ocupada para continuar consumindo)
     */
    public function actionReverterMesa()
    {
        $tenantId = \app\components\TenantHelper::getId();
        $mesaId = Yii::$app->request->post('mesa_id');

        $mesa = Mesa::findOne(['id' => $mesaId, 'usuario_id' => $tenantId]);
        if ($mesa && $mesa->status === Mesa::STATUS_AGUARDANDO_CONTA) {
            $mesa->status = Mesa::STATUS_OCUPADA;
            $mesa->save(false);
            Yii::$app->session->setFlash('success', "Mesa {$mesa->numero_mesa} reaberta para continuar consumindo!");
        }

        return $this->redirect(['index']);
    }

    /**
     * Ação: Liberar Mesa (Fechar Comanda)
     */
    public function actionLiberarMesa()
    {
        $tenantId = \app\components\TenantHelper::getId();
        $mesaId = Yii::$app->request->post('mesa_id');

        $mesa = Mesa::findOne(['id' => $mesaId, 'usuario_id' => $tenantId]);
        if ($mesa) {
            // Fecha a comanda ativa
            if ($mesa->comandaAtiva) {
                $comanda = $mesa->comandaAtiva;
                $comanda->status = Comanda::STATUS_FECHADA;
                $comanda->data_fechamento = date('Y-m-d H:i:s');
                $comanda->save(false);
            }

            $mesa->status = Mesa::STATUS_LIVRE;
            $mesa->save(false);

            Yii::$app->session->setFlash('success', "Mesa {$mesa->numero_mesa} foi liberada!");
        }

        return $this->redirect(['index']);
    }

    /**
     * Ação: Transferir Consumo de uma Mesa para Outra
     */
    public function actionTransferirMesa()
    {
        $tenantId = \app\components\TenantHelper::getId();
        $request = Yii::$app->request->post();

        $origemId = $request['mesa_origem_id'] ?? null;
        $destinoId = $request['mesa_destino_id'] ?? null;

        if ($origemId === $destinoId) {
            Yii::$app->session->setFlash('error', "Selecione uma mesa de destino diferente da mesa de origem.");
            return $this->redirect(['index']);
        }

        $mesaOrigem = Mesa::findOne(['id' => $origemId, 'usuario_id' => $tenantId]);
        $mesaDestino = Mesa::findOne(['id' => $destinoId, 'usuario_id' => $tenantId]);

        if ($mesaOrigem && $mesaDestino) {
            $comandaOrigem = $mesaOrigem->comandaAtiva;
            if ($comandaOrigem) {
                $comandaDestino = $mesaDestino->getOuCriarComandaAtiva();

                // Move todos os itens da comanda de origem para a comanda de destino
                foreach ($comandaOrigem->itens as $item) {
                    $item->comanda_id = $comandaDestino->id;
                    $item->save(false);
                }

                // Move mensagens ativas do chat para a nova mesa/comanda destino
                \app\modules\vendas\models\ClienteInbox::updateAll(
                    ['mesa_id' => $mesaDestino->id, 'comanda_id' => $comandaDestino->id],
                    ['comanda_id' => $comandaOrigem->id, 'usuario_id' => $tenantId]
                );

                // Marca chamados pendentes da mesa de origem como atendidos
                \app\modules\vendas\models\ClienteInbox::updateAll(
                    ['lido' => true],
                    ['mesa_id' => $mesaOrigem->id, 'usuario_id' => $tenantId, 'lido' => false]
                );

                // Cancela/fecha comanda origem zerada
                $comandaOrigem->status = Comanda::STATUS_CANCELADA;
                $comandaOrigem->save(false);

                // Libera mesa origem e ocupa mesa destino
                $mesaOrigem->status = Mesa::STATUS_LIVRE;
                $mesaOrigem->save(false);

                $mesaDestino->status = Mesa::STATUS_OCUPADA;
                $mesaDestino->save(false);

                Yii::$app->session->setFlash('success', "Consumo transferido da Mesa {$mesaOrigem->numero_mesa} para a Mesa {$mesaDestino->numero_mesa} com sucesso!");
            }
        }

        return $this->redirect(['index']);
    }

    /**
     * Ação: Criar / Cadastrar Nova Mesa no Salão
     */
    public function actionCriarMesa()
    {
        $tenantId = \app\components\TenantHelper::getId();
        $request = Yii::$app->request->post();

        $numeroMesa = trim($request['numero_mesa'] ?? '');
        $lugares = (int)($request['lugares'] ?? 4);
        $identificador = trim($request['nome_identificador'] ?? '');

        if (empty($numeroMesa)) {
            Yii::$app->session->setFlash('error', "Informe o número da mesa.");
            return $this->redirect(['index']);
        }

        // Verifica se a mesa já existe
        $existe = Mesa::findOne(['usuario_id' => $tenantId, 'numero_mesa' => $numeroMesa]);
        if ($existe) {
            Yii::$app->session->setFlash('error', "A Mesa {$numeroMesa} já está cadastrada no sistema.");
            return $this->redirect(['index']);
        }

        $mesa = new Mesa();
        $mesa->usuario_id = $tenantId;
        $mesa->numero_mesa = $numeroMesa;
        $mesa->lugares = $lugares > 0 ? $lugares : 4;
        $mesa->nome_identificador = $identificador;
        $mesa->status = Mesa::STATUS_LIVRE;

        if ($mesa->save(false)) {
            Yii::$app->session->setFlash('success', "Mesa {$numeroMesa} cadastrada com sucesso!");
        } else {
            Yii::$app->session->setFlash('error', "Erro ao cadastrar mesa.");
        }

        return $this->redirect(['index']);
    }

    /**
     * Ação: Excluir / Remover Mesa do Salão
     */
    public function actionExcluirMesa()
    {
        $tenantId = \app\components\TenantHelper::getId();
        $mesaId = Yii::$app->request->post('mesa_id');

        $mesa = Mesa::findOne(['id' => $mesaId, 'usuario_id' => $tenantId]);
        if (!$mesa) {
            Yii::$app->session->setFlash('error', "Mesa não encontrada.");
            return $this->redirect(['index']);
        }

        if ($mesa->getConsumoTotal() > 0 || $mesa->status !== Mesa::STATUS_LIVRE) {
            Yii::$app->session->setFlash('error', "Não é possível excluir a Mesa {$mesa->numero_mesa} pois ela está ocupada ou possui consumo ativo.");
            return $this->redirect(['index']);
        }

        $numero = $mesa->numero_mesa;
        if ($mesa->delete()) {
            Yii::$app->session->setFlash('success', "Mesa {$numero} excluída com sucesso!");
        } else {
            Yii::$app->session->setFlash('error', "Erro ao excluir mesa.");
        }

        return $this->redirect(['index']);
    }

    /**
     * Ação: Adicionar +1 Mesa Rápida ao mapa (1-clique)
     */
    public function actionAdicionarMesaRapida()
    {
        $tenantId = \app\components\TenantHelper::getId();

        // Encontra o maior número de mesa numérico existente
        $mesas = Mesa::find()->where(['usuario_id' => $tenantId])->all();
        $maxNum = 0;
        foreach ($mesas as $m) {
            $num = (int)preg_replace('/[^0-9]/', '', $m->numero_mesa);
            if ($num > $maxNum) $maxNum = $num;
        }

        $novoNumero = str_pad($maxNum + 1, 2, '0', STR_PAD_LEFT);

        $mesa = new Mesa();
        $mesa->usuario_id = $tenantId;
        $mesa->numero_mesa = (string)$novoNumero;
        $mesa->lugares = 4;
        $mesa->status = Mesa::STATUS_LIVRE;

        if ($mesa->save(false)) {
            Yii::$app->session->setFlash('success', "Mesa {$novoNumero} acrescentada ao mapa!");
        } else {
            Yii::$app->session->setFlash('error', "Erro ao acrescentar mesa.");
        }

        return $this->redirect(['index']);
    }

    /**
     * Ação: Gerar Lote de Múltiplas Mesas (Ex: 5 ou 10 mesas de uma vez)
     */
    public function actionGerarLoteMesas()
    {
        $tenantId = \app\components\TenantHelper::getId();
        $request = Yii::$app->request->post();
        $qtd = (int)($request['quantidade'] ?? 1);

        if ($qtd < 1) $qtd = 1;
        if ($qtd > 50) $qtd = 50;

        $mesas = Mesa::find()->where(['usuario_id' => $tenantId])->all();
        $maxNum = 0;
        foreach ($mesas as $m) {
            $num = (int)preg_replace('/[^0-9]/', '', $m->numero_mesa);
            if ($num > $maxNum) $maxNum = $num;
        }

        $criadas = 0;
        for ($i = 1; $i <= $qtd; $i++) {
            $maxNum++;
            $novoNumero = str_pad($maxNum, 2, '0', STR_PAD_LEFT);

            $mesa = new Mesa();
            $mesa->usuario_id = $tenantId;
            $mesa->numero_mesa = (string)$novoNumero;
            $mesa->lugares = 4;
            $mesa->status = Mesa::STATUS_LIVRE;
            if ($mesa->save(false)) {
                $criadas++;
            }
        }

        Yii::$app->session->setFlash('success', "{$criadas} novas mesas acrescentadas ao mapa com sucesso!");
        return $this->redirect(['index']);
    }

    /**
     * Exibe o Cupom Térmico Não Fiscal para Impressão (80mm / 58mm)
     */
    public function actionImprimirComprovante($mesa_id)
    {
        $tenantId = \app\components\TenantHelper::getId();

        $mesa = Mesa::findOne(['id' => $mesa_id, 'usuario_id' => $tenantId]);
        if (!$mesa) {
            throw new NotFoundHttpException('Mesa não encontrada.');
        }

        $comanda = $mesa->getOuCriarComandaAtiva();
        $loja = Usuario::findOne($tenantId);

        return $this->renderPartial('imprimir_comprovante', [
            'mesa' => $mesa,
            'comanda' => $comanda,
            'loja' => $loja,
        ]);
    }

    /**
     * Relatório Analítico do Food Service (Ticket Médio, Permanência & Horário de Pico)
     */
    public function actionRelatorio()
    {
        $tenantId = \app\components\TenantHelper::getId();

        // 1. Total de Comandas Fechadas
        $comandas = Comanda::find()
            ->where(['usuario_id' => $tenantId, 'status' => Comanda::STATUS_FECHADA])
            ->all();

        $totalComandas = count($comandas);
        $faturamentoTotal = 0.00;
        $tempoTotalMinutos = 0;
        $mesasCount = 0;
        $deliveryCount = 0;

        foreach ($comandas as $c) {
            $totalVal = $c->getValorTotal() + (float)$c->taxa_entrega;
            $faturamentoTotal += $totalVal;

            if ($c->data_abertura && $c->data_fechamento) {
                $diff = (strtotime($c->data_fechamento) - strtotime($c->data_abertura)) / 60;
                if ($diff > 0 && $diff < 720) {
                    $tempoTotalMinutos += $diff;
                }
            }

            if ($c->tipo_atendimento === 'delivery') {
                $deliveryCount++;
            } else {
                $mesasCount++;
            }
        }

        $ticketMedio = $totalComandas > 0 ? ($faturamentoTotal / $totalComandas) : 0.00;
        $tempoMedioPermanencia = $totalComandas > 0 ? round($tempoTotalMinutos / $totalComandas) : 0;

        // 2. Vendas por Faixa Horária (Horário de Pico)
        $faixasHorarias = [
            'Manhã (06h - 11h)' => 0,
            'Almoço (11h - 15h)' => 0,
            'Tarde (15h - 18h)' => 0,
            'Jantar (18h - 23h)' => 0,
            'Madrugada (23h - 06h)' => 0,
        ];

        foreach ($comandas as $c) {
            $hora = (int)date('H', strtotime($c->data_abertura));
            if ($hora >= 6 && $hora < 11) $faixasHorarias['Manhã (06h - 11h)']++;
            elseif ($hora >= 11 && $hora < 15) $faixasHorarias['Almoço (11h - 15h)']++;
            elseif ($hora >= 15 && $hora < 18) $faixasHorarias['Tarde (15h - 18h)']++;
            elseif ($hora >= 18 && $hora < 23) $faixasHorarias['Jantar (18h - 23h)']++;
            else $faixasHorarias['Madrugada (23h - 06h)']++;
        }

        // 3. Top 5 Produtos Mais Vendidos
        $topProdutos = Yii::$app->db->createCommand("
            SELECT p.nome, SUM(ci.quantidade) as qtd_total, SUM(ci.quantidade * ci.valor_unitario) as total_vendas
            FROM prest_comanda_itens ci
            JOIN prest_comandas c ON c.id = ci.comanda_id
            JOIN prest_produtos p ON p.id = ci.produto_id
            WHERE c.usuario_id = :tenantId AND c.status = 'fechada'
            GROUP BY p.nome
            ORDER BY qtd_total DESC
            LIMIT 5
        ", [':tenantId' => $tenantId])->queryAll();

        return $this->render('relatorio', [
            'totalComandas' => $totalComandas,
            'faturamentoTotal' => $faturamentoTotal,
            'ticketMedio' => $ticketMedio,
            'tempoMedioPermanencia' => $tempoMedioPermanencia,
            'mesasCount' => $mesasCount,
            'deliveryCount' => $deliveryCount,
            'faixasHorarias' => $faixasHorarias,
            'topProdutos' => $topProdutos,
        ]);
    }

    /**
     * Relatório de Apuração de Comissões de Garçons e Diárias/Taxas de Motoboys
     */
    public function actionComissoes()
    {
        $tenantId = \app\components\TenantHelper::getId();

        $comandas = Comanda::find()
            ->where(['usuario_id' => $tenantId, 'status' => Comanda::STATUS_FECHADA])
            ->all();

        $garconsData = [];
        $motoboysData = [];

        foreach ($comandas as $c) {
            // Garçom
            $garcom = $c->garcom_nome ?: 'Garçom Geral';
            if (!isset($garconsData[$garcom])) {
                $garconsData[$garcom] = [
                    'nome' => $garcom,
                    'qtd_atendimentos' => 0,
                    'total_consumo' => 0.00,
                    'total_comissao' => 0.00,
                ];
            }
            $garconsData[$garcom]['qtd_atendimentos']++;
            $consumoVal = $c->getValorTotal();
            $garconsData[$garcom]['total_consumo'] += $consumoVal;
            $garconsData[$garcom]['total_comissao'] += ($c->taxa_servico > 0 ? (float)$c->taxa_servico : ($consumoVal * 0.10));

            // Motoboy
            if ($c->tipo_atendimento === 'delivery' && !empty($c->motoboy_nome)) {
                $mb = $c->motoboy_nome;
                if (!isset($motoboysData[$mb])) {
                    $motoboysData[$mb] = [
                        'nome' => $mb,
                        'qtd_corridas' => 0,
                        'total_taxas_entrega' => 0.00,
                        'total_pedidos_valor' => 0.00,
                    ];
                }
                $motoboysData[$mb]['qtd_corridas']++;
                $motoboysData[$mb]['total_taxas_entrega'] += (float)$c->taxa_entrega;
                $motoboysData[$mb]['total_pedidos_valor'] += $consumoVal;
            }
        }

        return $this->render('comissoes', [
            'garconsData' => array_values($garconsData),
            'motoboysData' => array_values($motoboysData),
        ]);
    }

    /**
     * Retorna dados da mesa e lista de formas de pagamento para o modal de fechamento
     */
    public function actionDadosFechamentoJson($mesa_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();

        $mesa = Mesa::findOne(['id' => $mesa_id, 'usuario_id' => $tenantId]);
        if (!$mesa) {
            return ['success' => false, 'message' => 'Mesa não encontrada.'];
        }

        $comanda = $mesa->getOuCriarComandaAtiva();
        $total = $comanda->getValorTotal();

        $formas = FormaPagamento::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        $formasData = [];
        foreach ($formas as $f) {
            $formasData[] = [
                'id' => $f->id,
                'nome' => $f->nome,
            ];
        }

        $wpConfig = \app\modules\evolution\models\WhatsappConfig::findByEmpresa($tenantId);
        $wpConectado = ($wpConfig !== null && $wpConfig->status === 'CONNECTED');

        return [
            'success' => true,
            'mesa_numero' => $mesa->numero_mesa,
            'cliente_nome' => $comanda->cliente_nome ?: 'Cliente',
            'valor_total' => (float)$total,
            'valor_total_formatado' => number_format($total, 2, ',', '.'),
            'formas_pagamento' => $formasData,
            'whatsapp_conectado' => $wpConectado,
            'whatsapp_status' => $wpConfig ? $wpConfig->status : 'NOT_CONFIGURED',
        ];
    }

    /**
     * Processa o Fechamento, Divisão de Conta, Baixa no Caixa e Envio de Comprovante via WhatsApp
     */
    public function actionProcessarFechamento()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();
        $request = Yii::$app->request->post();

        $mesaId = $request['mesa_id'] ?? null;
        $pagamentosRaw = $request['pagamentos'] ?? '[]';
        $numPessoas = (int)($request['num_pessoas'] ?? 1);
        $whatsapp = trim($request['whatsapp'] ?? '');
        $enviarWhatsapp = (int)($request['enviar_whatsapp'] ?? 0);

        $mesa = Mesa::findOne(['id' => $mesaId, 'usuario_id' => $tenantId]);
        if (!$mesa) {
            return ['success' => false, 'message' => 'Mesa não encontrada.'];
        }

        $comanda = $mesa->getOuCriarComandaAtiva();
        $totalConsumo = $comanda->getValorTotal();

        if ($totalConsumo <= 0) {
            return ['success' => false, 'message' => 'Não é possível fechar uma mesa sem consumo.'];
        }

        $pagamentos = json_decode($pagamentosRaw, true);
        if (empty($pagamentos)) {
            return ['success' => false, 'message' => 'Nenhum pagamento registrado.'];
        }

        // 1. Registra entradas no Caixa
        $userId = Yii::$app->user->id;
        foreach ($pagamentos as $p) {
            $formaId = $p['forma_pagamento_id'] ?? null;
            $val = (float)($p['valor'] ?? 0);
            if ($val > 0) {
                if (class_exists('app\modules\caixa\helpers\CaixaHelper')) {
                    \app\modules\caixa\helpers\CaixaHelper::registrarEntrada(
                        $val,
                        "Fechamento Mesa {$mesa->numero_mesa} ({$comanda->numero_comanda})",
                        $formaId,
                        $userId
                    );
                }
            }
        }

        // 2. Fecha a Comanda e define o status da Mesa
        $comanda->status = Comanda::STATUS_FECHADA;
        $comanda->data_fechamento = date('Y-m-d H:i:s');
        $comanda->save(false);

        $desocuparMesa = (int)($request['desocupar_mesa'] ?? 0);
        if ($desocuparMesa == 1) {
            $mesa->status = Mesa::STATUS_LIVRE;
        } else {
            $mesa->status = Mesa::STATUS_PAGA;
        }
        $mesa->save(false);

        // 3. Formata texto do Comprovante de Consumo
        $loja = Usuario::findOne($tenantId);
        $nomeLoja = ($loja && !empty($loja->nome)) ? $loja->nome : 'PULSE Food Service';

        $msgComprovante = "🧾 *{$nomeLoja}*\n";
        $msgComprovante .= "-----------------------------------\n";
        $msgComprovante .= "📌 *RECIBO DE CONSUMO — MESA {$mesa->numero_mesa}*\n";
        $msgComprovante .= "👤 *Cliente:* " . ($comanda->cliente_nome ?: 'Cliente') . "\n";
        $msgComprovante .= "📅 *Data:* " . date('d/m/Y H:i') . "\n";
        $msgComprovante .= "-----------------------------------\n";
        $msgComprovante .= "📦 *ITENS CONSUMIDOS:*\n";

        foreach ($comanda->itens as $item) {
            $prodNome = $item->produto ? $item->produto->nome : 'Produto';
            $msgComprovante .= "• {$item->quantidade}x {$prodNome} - R$ " . number_format($item->getSubtotal(), 2, ',', '.') . "\n";
            if ($item->observacoes) {
                $msgComprovante .= "  _(Obs: {$item->observacoes})_\n";
            }
        }

        $msgComprovante .= "-----------------------------------\n";
        $msgComprovante .= "💰 *TOTAL DA CONTA:* R$ " . number_format($totalConsumo, 2, ',', '.') . "\n";

        if ($numPessoas > 1) {
            $valPessoa = $totalConsumo / $numPessoas;
            $msgComprovante .= "🧮 *Divisão por {$numPessoas} pessoas:* R$ " . number_format($valPessoa, 2, ',', '.') . " cada\n";
        }

        $msgComprovante .= "-----------------------------------\n";
        $msgComprovante .= "💳 *PAGAMENTOS REGISTRADOS:*\n";
        foreach ($pagamentos as $p) {
            $fModel = FormaPagamento::findOne($p['forma_pagamento_id'] ?? null);
            $fNome = $fModel ? $fModel->nome : 'Pagamento';
            $msgComprovante .= "• {$fNome}: R$ " . number_format((float)$p['valor'], 2, ',', '.') . "\n";
        }
        $msgComprovante .= "-----------------------------------\n";
        $msgComprovante .= "Obrigado pela preferência e volte sempre! 😊🚀";

        // Marca chamados pendentes anteriores dessa mesa como lidos/atendidos ao encerrar
        \app\modules\vendas\models\ClienteInbox::updateAll(
            ['lido' => true],
            ['mesa_id' => $mesa->id, 'usuario_id' => $tenantId, 'lido' => false, 'tipo' => ['chamado', 'chat_cliente']]
        );

        // 4. Salva no Canal Próprio (Direct Hub / Inbox Digital da Mesa)
        $hubPostado = false;
        $enviarHub = (int)($request['enviar_hub'] ?? 1);
        if ($enviarHub == 1) {
            $inbox = new \app\modules\vendas\models\ClienteInbox();
            $inbox->usuario_id = $tenantId;
            $inbox->mesa_id = $mesa->id;
            $inbox->comanda_id = $comanda->id;
            $inbox->tipo = 'conta';
            $inbox->titulo = "🧾 Recibo de Fechamento — Mesa {$mesa->numero_mesa}";
            $inbox->conteudo_texto = $msgComprovante;
            $inbox->lido = false;
            $inbox->created_at = date('Y-m-d H:i:s');
            if ($inbox->save(false)) {
                $hubPostado = true;
            } else {
                Yii::error("Erro ao salvar comprovante no ClienteInbox: " . json_encode($inbox->errors), __METHOD__);
            }
        }

        // 5. Envio via WhatsApp (Evolution API) se solicitado
        $wpEnviado = false;
        $wpMensagemStatus = null;
        if ($enviarWhatsapp == 1) {
            if (empty($whatsapp)) {
                $wpMensagemStatus = "Número de WhatsApp não informado.";
            } else {
                $numLimpo = preg_replace('/[^0-9]/', '', $whatsapp);
                if (strlen($numLimpo) < 10) {
                    $wpMensagemStatus = "Número de telefone inválido ({$whatsapp}).";
                } else {
                    $wpConfig = \app\modules\evolution\models\WhatsappConfig::findByEmpresa($tenantId);
                    if ($wpConfig === null || $wpConfig->status !== 'CONNECTED') {
                        $wpMensagemStatus = "WhatsApp da empresa está desconectado. Conecte o aparelho no menu WhatsApp.";
                    } else {
                        try {
                            $evolution = new \app\modules\evolution\services\EvolutionService();
                            $wpEnviado = $evolution->sendMessage($tenantId, $numLimpo, $msgComprovante);
                            if ($wpEnviado) {
                                $wpMensagemStatus = "Enviado com sucesso para {$whatsapp}!";
                            } else {
                                $wpMensagemStatus = $evolution->lastError ?: "Falha ao enviar via Evolution API.";
                            }
                        } catch (\Throwable $e) {
                            $wpMensagemStatus = "Erro ao disparar: " . $e->getMessage();
                            Yii::error("Erro ao enviar recibo por WhatsApp: " . $e->getMessage(), __METHOD__);
                        }
                    }
                }
            }
        }

        $mensagensAviso = [];
        $mensagensAviso[] = "Mesa {$mesa->numero_mesa} fechada e liberada com sucesso!";
        if ($hubPostado) {
            $mensagensAviso[] = "🌐 Comprovante publicado no Direct Hub / Chat da Mesa!";
        }
        if ($enviarWhatsapp == 1) {
            if ($wpEnviado) {
                $mensagensAviso[] = "📲 WhatsApp: {$wpMensagemStatus}";
            } else {
                $mensagensAviso[] = "⚠️ WhatsApp: {$wpMensagemStatus}";
            }
        }

        $msgFinal = implode("\n", $mensagensAviso);
        $slugLoja = $loja ? ($loja->slug ?? $loja->id) : '';

        return [
            'success' => true,
            'message' => $msgFinal,
            'recibo_texto' => $msgComprovante,
            'hub_postado' => $hubPostado,
            'whatsapp_enviado' => $wpEnviado,
            'whatsapp_status' => $wpMensagemStatus,
            'hub_url' => "/hub?slug={$slugLoja}&mesa={$mesa->numero_mesa}",
        ];
    }

    /**
     * Exibe a página pronta para impressão com os displays / plaquinhas QR Code de todas as mesas
     */
    public function actionImprimirQrcodes()
    {
        $tenantId = Yii::$app->user->identity->getTenantId();
        $usuario = Usuario::findOne($tenantId);
        $mesas = Mesa::find()
            ->where(['usuario_id' => $tenantId])
            ->orderBy(['numero_mesa' => SORT_ASC])
            ->all();

        $slug = $usuario->slug ?? $usuario->id;
        $baseUrl = Yii::$app->params['domain'] ?? 'https://catalogos.oncode.app.br';

        return $this->renderPartial('imprimir_qrcodes', [
            'usuario' => $usuario,
            'mesas'   => $mesas,
            'baseUrl' => rtrim($baseUrl, '/'),
            'slug'    => $slug,
        ]);
    }

    /**
     * Endpoint Ajax para polling de chamados de garçom e pedidos de conta em tempo real (Agrupado por Mesa)
     */
    public function actionChamadosPendentes(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = Yii::$app->user->identity->getTenantId();

        $chamados = \app\modules\vendas\models\ClienteInbox::find()
            ->where(['usuario_id' => $tenantId, 'lido' => false])
            ->andWhere(['in', 'tipo', [
                \app\modules\vendas\models\ClienteInbox::TIPO_CHAMADO, 
                \app\modules\vendas\models\ClienteInbox::TIPO_CONTA,
                'chat_cliente'
            ]])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $grupos = [];
        $garcomTotal = 0;
        $contaTotal = 0;
        $chatTotal = 0;
        $totalMensagens = 0;

        foreach ($chamados as $ch) {
            $totalMensagens++;
            $mesaIdKey = $ch->mesa_id ?: ('sem_mesa_' . $ch->id);

            if (!isset($grupos[$mesaIdKey])) {
                $mesa = $ch->mesa;
                $grupos[$mesaIdKey] = [
                    'mesa_id' => $mesa ? $mesa->id : null,
                    'mesa_numero' => $mesa ? $mesa->numero_mesa : 'Balcão',
                    'qtd_mensagens' => 0,
                    'tem_conta' => false,
                    'tem_chamado' => false,
                    'tem_chat' => false,
                    'ultima_mensagem' => $ch->conteudo_texto,
                    'ultima_midia_url' => $ch->midia_url,
                    'ultimo_tipo' => $ch->tipo,
                    'ultimo_chamado_id' => $ch->id,
                    'ids' => [],
                    'created_at' => Yii::$app->formatter->asRelativeTime($ch->created_at),
                    'hora' => date('H:i', strtotime($ch->created_at)),
                ];
            }

            $grupos[$mesaIdKey]['qtd_mensagens']++;
            $grupos[$mesaIdKey]['ids'][] = $ch->id;

            if ($ch->tipo === \app\modules\vendas\models\ClienteInbox::TIPO_CONTA) {
                $grupos[$mesaIdKey]['tem_conta'] = true;
            } elseif ($ch->tipo === 'chat_cliente') {
                $grupos[$mesaIdKey]['tem_chat'] = true;
            } else {
                $grupos[$mesaIdKey]['tem_chamado'] = true;
            }
        }

        $data = [];
        foreach ($grupos as $g) {
            if ($g['tem_conta']) {
                $contaTotal++;
                $tipoLabel = 'Pediu Conta';
                $tipoIcon = '💳';
                $tipoPrioritario = 'conta';
            } elseif ($g['tem_chamado']) {
                $garcomTotal++;
                $tipoLabel = 'Garçom';
                $tipoIcon = '👋';
                $tipoPrioritario = 'chamado';
            } else {
                $chatTotal++;
                $tipoLabel = 'Mensagem no Chat';
                $tipoIcon = '💬';
                $tipoPrioritario = 'chat_cliente';
            }

            $data[] = [
                'id'          => $g['ultimo_chamado_id'],
                'mesa_id'     => $g['mesa_id'],
                'mesa_numero' => $g['mesa_numero'],
                'tipo'        => $tipoPrioritario,
                'tipo_label'  => $tipoLabel,
                'tipo_icon'   => $tipoIcon,
                'texto'       => $g['ultima_mensagem'],
                'midia_url'   => $g['ultima_midia_url'],
                'qtd_novas'   => $g['qtd_mensagens'],
                'ids'         => $g['ids'],
                'created_at'  => $g['created_at'],
                'hora'        => $g['hora'],
            ];
        }

        return [
            'total'           => count($data),
            'total_mensagens' => $totalMensagens,
            'garcom_total'    => $garcomTotal,
            'conta_total'     => $contaTotal,
            'chat_total'      => $chatTotal,
            'chamados'        => $data,
        ];
    }

    /**
     * Marca todas as notificações de uma mesa específica como lidas
     */
    public function actionAtenderMesa($id = null): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = $id ?: Yii::$app->request->get('id') ?: Yii::$app->request->post('id');
        if (empty($id)) {
            $raw = json_decode(Yii::$app->request->getRawBody(), true);
            $id = $raw['id'] ?? null;
        }

        $tenantId = Yii::$app->user->identity->getTenantId();

        \app\modules\vendas\models\ClienteInbox::updateAll(
            ['lido' => true],
            [
                'mesa_id' => $id, 
                'usuario_id' => $tenantId, 
                'lido' => false,
                'tipo' => [
                    \app\modules\vendas\models\ClienteInbox::TIPO_CHAMADO, 
                    \app\modules\vendas\models\ClienteInbox::TIPO_CONTA,
                    'chat_cliente'
                ]
            ]
        );

        return ['success' => true];
    }

    /**
     * Retorna todo o histórico de mensagens da mesa para a janela de chat do Garçom
     */
    public function actionMensagensMesaAdmin($id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = Yii::$app->user->identity->getTenantId();
        $mesa = Mesa::findOne(['id' => $id, 'usuario_id' => $tenantId]);

        if (!$mesa) {
            return ['success' => false, 'message' => 'Mesa não encontrada.', 'mensagens' => []];
        }

        $comanda = $mesa->comandaAtiva;
        if ($comanda === null) {
            $comanda = Comanda::find()
                ->where(['mesa_id' => $mesa->id, 'usuario_id' => $tenantId])
                ->orderBy(['data_abertura' => SORT_DESC])
                ->one();
        }

        $query = \app\modules\vendas\models\ClienteInbox::find()
            ->where(['mesa_id' => $mesa->id, 'usuario_id' => $tenantId])
            ->andWhere(['in', 'tipo', ['chat_cliente', 'chat_garcom', 'chamado', 'conta', 'card']]);

        if ($comanda) {
            $query->andWhere([
                'or',
                ['comanda_id' => $comanda->id],
                ['and', ['comanda_id' => null], ['>=', 'created_at', $comanda->data_abertura ?: date('Y-m-d 00:00:00')]]
            ]);
        }

        $mensagens = $query->orderBy(['created_at' => SORT_ASC])
            ->limit(80)
            ->all();

        $data = [];
        foreach ($mensagens as $m) {
            $isCliente = ($m->tipo === 'chat_cliente' || $m->tipo === 'chamado' || $m->tipo === 'conta');
            $data[] = [
                'id' => $m->id,
                'tipo' => $m->tipo,
                'remetente' => $isCliente ? 'cliente' : 'garcom',
                'autor' => $isCliente ? ('Mesa ' . $mesa->numero_mesa) : 'Você (Garçom)',
                'texto' => $m->conteudo_texto,
                'midia_url' => $m->midia_url,
                'hora' => date('H:i', strtotime($m->created_at)),
                'created_at' => Yii::$app->formatter->asRelativeTime($m->created_at),
                'lido' => $m->lido,
            ];
        }

        return [
            'success' => true,
            'mesa_numero' => $mesa->numero_mesa,
            'mesa_id' => $mesa->id,
            'mensagens' => $data,
            'count' => count($data),
        ];
    }

    /**
     * Marca um chamado de garçom ou conta como atendido/lido
     */
    public function actionAtenderChamado($id = null): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = $id ?: Yii::$app->request->get('id') ?: Yii::$app->request->post('id');
        if (empty($id)) {
            $raw = json_decode(Yii::$app->request->getRawBody(), true);
            $id = $raw['id'] ?? null;
        }

        $tenantId = Yii::$app->user->identity->getTenantId();

        $chamado = \app\modules\vendas\models\ClienteInbox::findOne(['id' => $id, 'usuario_id' => $tenantId]);
        if ($chamado) {
            $chamado->lido = true;
            $chamado->save(false, ['lido']);
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Chamado não encontrado.'];
    }

    /**
     * Garçom responde diretamente a uma mensagem da mesa via Direct Hub
     */
    public function actionResponderMensagemMesa(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request->post() ?: json_decode(Yii::$app->request->getRawBody(), true) ?: [];

        $mesaId = $request['mesa_id'] ?? Yii::$app->request->get('mesa_id');
        $mensagem = trim($request['mensagem'] ?? '');
        $chamadoId = $request['chamado_id'] ?? null;

        // Salva imagem se enviada pelo garçom
        $midiaUrl = \app\modules\vendas\helpers\ChatMediaHelper::salvarUpload('imagem') ?: ($request['midia_url'] ?? null);

        if (empty($mesaId) || (empty($mensagem) && empty($midiaUrl))) {
            return ['success' => false, 'message' => 'Informe a resposta ou anexe uma foto.'];
        }

        $tenantId = Yii::$app->user->identity->getTenantId();
        $mesa = Mesa::findOne(['id' => $mesaId, 'usuario_id' => $tenantId]);
        if (!$mesa) {
            return ['success' => false, 'message' => 'Mesa não encontrada.'];
        }

        // Posta a resposta do garçom para a mesa no Direct Hub
        \app\modules\vendas\models\ClienteInbox::postar(
            $tenantId,
            null,
            'chat_garcom',
            "🧑‍🍳 Resposta do Garçom",
            $mensagem ?: ($midiaUrl ? '📷 [Foto enviada pelo Garçom]' : ''),
            $midiaUrl,
            [
                'mesa_id' => $mesa->id,
                'mesa_numero' => $mesa->numero_mesa,
                'origem' => 'garcom'
            ],
            $mesa->id
        );

        // Marca a mensagem do cliente como atendida
        if ($chamadoId) {
            $ch = \app\modules\vendas\models\ClienteInbox::findOne(['id' => $chamadoId, 'usuario_id' => $tenantId]);
            if ($ch) {
                $ch->lido = true;
                $ch->save(false, ['lido']);
            }
        } else {
            \app\modules\vendas\models\ClienteInbox::updateAll(
                ['lido' => true],
                ['mesa_id' => $mesa->id, 'usuario_id' => $tenantId, 'lido' => false, 'tipo' => ['chat_cliente', 'chamado']]
            );
        }

        return [
            'success' => true,
            'message' => 'Resposta enviada para a Mesa ' . $mesa->numero_mesa . ' com sucesso!',
            'hora' => date('H:i')
        ];
    }

    /**
     * Marca todos os chamados pendentes da loja como atendidos com 1 clique
     */
    public function actionAtenderTodosChamados(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = Yii::$app->user->identity->getTenantId();

        \app\modules\vendas\models\ClienteInbox::updateAll(
            ['lido' => true],
            [
                'usuario_id' => $tenantId,
                'lido' => false,
                'tipo' => [
                    \app\modules\vendas\models\ClienteInbox::TIPO_CHAMADO, 
                    \app\modules\vendas\models\ClienteInbox::TIPO_CONTA,
                    'chat_cliente'
                ]
            ]
        );

        return ['success' => true];
    }

    /**
     * Limpa o histórico de mensagens do chat de uma mesa pelo Garçom
     */
    public function actionLimparChatMesa(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request->post() ?: json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $mesaId = $request['mesa_id'] ?? Yii::$app->request->get('mesa_id');

        if (empty($mesaId)) {
            return ['success' => false, 'message' => 'Mesa não informada.'];
        }

        $tenantId = Yii::$app->user->identity->getTenantId();

        \app\modules\vendas\models\ClienteInbox::deleteAll([
            'mesa_id' => $mesaId,
            'usuario_id' => $tenantId,
            'tipo' => ['chat_cliente', 'chat_garcom', 'chamado', 'conta', 'card']
        ]);

        return [
            'success' => true,
            'message' => 'Histórico do chat da mesa limpo com sucesso!'
        ];
    }

    /**
     * Ação: Liberar / Desocupar Mesa Física (marca status como LIVRE)
     */
    public function actionLiberarMesaDirect(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();
        $request = Yii::$app->request->post() ?: json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $id = $request['mesa_id'] ?? Yii::$app->request->get('id') ?? Yii::$app->request->post('id');

        $mesa = Mesa::findOne(['id' => $id, 'usuario_id' => $tenantId]);
        if ($mesa) {
            $mesa->status = Mesa::STATUS_LIVRE;
            $mesa->save(false);
            return [
                'success' => true,
                'message' => "Mesa {$mesa->numero_mesa} desocupada e liberada para o próximo cliente!"
            ];
        }

        return ['success' => false, 'message' => 'Mesa não encontrada.'];
    }
}
