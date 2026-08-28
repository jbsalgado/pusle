<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Mesa;
use app\modules\vendas\models\Comanda;
use app\modules\vendas\models\ComandaItem;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\FormaPagamento;
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

        return $this->render('index', [
            'mesas' => $mesas,
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

        // Cria o item da comanda
        $item = new ComandaItem();
        $item->comanda_id = $comanda->id;
        $item->produto_id = $produto->id;
        $item->quantidade = $quantidade > 0 ? $quantidade : 1;
        $item->valor_unitario = (float)$produto->getPrecoFinal();
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

        return [
            'success' => true,
            'mesa_numero' => $mesa->numero_mesa,
            'cliente_nome' => $comanda->cliente_nome ?: 'Cliente',
            'valor_total' => (float)$total,
            'valor_total_formatado' => number_format($total, 2, ',', '.'),
            'formas_pagamento' => $formasData,
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

        // 2. Fecha a Comanda e libera a Mesa
        $comanda->status = Comanda::STATUS_FECHADA;
        $comanda->data_fechamento = date('Y-m-d H:i:s');
        $comanda->save(false);

        $mesa->status = Mesa::STATUS_LIVRE;
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

        // 4. Envio via WhatsApp (Evolution API) se solicitado
        $wpEnviado = false;
        if ($enviarWhatsapp == 1 && !empty($whatsapp)) {
            $numLimpo = preg_replace('/[^0-9]/', '', $whatsapp);
            if (!empty($numLimpo)) {
                try {
                    $evolution = new \app\modules\evolution\services\EvolutionService();
                    $wpEnviado = $evolution->sendMessage($tenantId, $numLimpo, $msgComprovante);
                } catch (\Exception $e) {
                    Yii::error("Erro ao enviar recibo por WhatsApp: " . $e->getMessage(), __METHOD__);
                }
            }
        }

        $msgFinal = "Mesa {$mesa->numero_mesa} fechada e liberada com sucesso!";
        if ($wpEnviado) {
            $msgFinal .= " 📲 Comprovante enviado via WhatsApp!";
        }

        return [
            'success' => true,
            'message' => $msgFinal,
            'recibo_texto' => $msgComprovante,
        ];
    }
}
