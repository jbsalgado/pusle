<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\modules\vendas\models\Comanda;
use app\modules\vendas\models\ComandaItem;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\Usuario;
use app\modules\evolution\services\EvolutionService;

class DeliveryController extends Controller
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
                    'novo-pedido' => ['POST'],
                    'atualizar-status' => ['POST'],
                    'cancelar-pedido' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Tela Principal da Gestão de Delivery (Painel Kanban)
     */
    public function actionIndex()
    {
        $tenantId = \app\components\TenantHelper::getId();

        $formasPagamento = FormaPagamento::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'formasPagamento' => $formasPagamento,
        ]);
    }

    /**
     * Endpoint JSON: Listar todos os pedidos de delivery ativos
     */
    public function actionListarPedidosJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();

        $pedidos = Comanda::find()
            ->where(['usuario_id' => $tenantId, 'tipo_atendimento' => 'delivery'])
            ->andWhere(['!=', 'status', Comanda::STATUS_CANCELADA])
            ->orderBy(['data_abertura' => SORT_DESC])
            ->all();

        $data = [];
        foreach ($pedidos as $p) {
            $itensData = [];
            foreach ($p->itens as $item) {
                $itensData[] = [
                    'id' => $item->id,
                    'produto_nome' => $item->produto ? $item->produto->nome : 'Produto',
                    'quantidade' => (float)$item->quantidade,
                    'valor_unitario' => (float)$item->valor_unitario,
                    'subtotal' => (float)$item->getSubtotal(),
                    'observacoes' => $item->observacoes,
                ];
            }

            $data[] = [
                'id' => $p->id,
                'numero_comanda' => $p->numero_comanda,
                'cliente_nome' => $p->cliente_nome ?: 'Cliente',
                'cliente_telefone' => $p->cliente_telefone ?: '',
                'endereco_entrega' => $p->endereco_entrega ?: 'Retirada no Balcão',
                'status_delivery' => $p->status_delivery ?: 'recebido',
                'status_comanda' => $p->status,
                'taxa_entrega' => (float)$p->taxa_entrega,
                'valor_subtotal' => (float)$p->getValorTotal(),
                'valor_total' => (float)$p->getValorTotal() + (float)$p->taxa_entrega,
                'motoboy_nome' => $p->motoboy_nome ?: '',
                'data_abertura' => date('d/m/H:i', strtotime($p->data_abertura)),
                'minutos_decorridos' => round((time() - strtotime($p->data_abertura)) / 60),
                'itens' => $itensData,
            ];
        }

        return [
            'success' => true,
            'pedidos' => $data,
        ];
    }

    /**
     * Ação: Criar Novo Pedido de Delivery
     */
    public function actionNovoPedido()
    {
        $tenantId = \app\components\TenantHelper::getId();
        $request = Yii::$app->request->post();

        $clienteNome = trim($request['cliente_nome'] ?? '');
        $clienteTelefone = trim($request['cliente_telefone'] ?? '');
        $endereco = trim($request['endereco_entrega'] ?? '');
        $taxaEntrega = (float)str_replace(',', '.', trim($request['taxa_entrega'] ?? '0'));
        $motoboy = trim($request['motoboy_nome'] ?? '');

        if (empty($clienteNome)) {
            Yii::$app->session->setFlash('error', "Informe o nome do cliente.");
            return $this->redirect(['index']);
        }

        // Gera número de pedido sequencial DEL-001...
        $totalHoje = Comanda::find()
            ->where(['usuario_id' => $tenantId, 'tipo_atendimento' => 'delivery'])
            ->count();
        $numeroPedido = 'DEL-' . str_pad($totalHoje + 1, 3, '0', STR_PAD_LEFT);

        $comanda = new Comanda();
        $comanda->id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        $comanda->usuario_id = $tenantId;
        $comanda->numero_comanda = $numeroPedido;
        $comanda->cliente_nome = $clienteNome;
        $comanda->cliente_telefone = $clienteTelefone;
        $comanda->endereco_entrega = $endereco;
        $comanda->tipo_atendimento = 'delivery';
        $comanda->status_delivery = 'recebido';
        $comanda->taxa_entrega = $taxaEntrega;
        $comanda->motoboy_nome = $motoboy;
        $comanda->status = Comanda::STATUS_ABERTA;

        if ($comanda->save(false)) {
            // Dispara notificação via WhatsApp se possuir telefone
            $this->notificarClienteWhatsApp($comanda, 'recebido');
            Yii::$app->session->setFlash('success', "Pedido {$numeroPedido} registrado com sucesso!");
        } else {
            Yii::$app->session->setFlash('error', "Erro ao criar pedido de delivery.");
        }

        return $this->redirect(['index']);
    }

    /**
     * Ação: Atualizar Status do Pedido de Delivery & Notificar WhatsApp
     */
    public function actionAtualizarStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();
        $request = Yii::$app->request->post();

        $pedidoId = $request['pedido_id'] ?? null;
        $novoStatus = $request['novo_status'] ?? null;
        $motoboy = trim($request['motoboy_nome'] ?? '');

        $comanda = Comanda::findOne(['id' => $pedidoId, 'usuario_id' => $tenantId]);
        if (!$comanda) {
            return ['success' => false, 'message' => 'Pedido não encontrado.'];
        }

        $comanda->status_delivery = $novoStatus;
        if (!empty($motoboy)) {
            $comanda->motoboy_nome = $motoboy;
        }

        if ($novoStatus === 'entregue') {
            $comanda->status = Comanda::STATUS_FECHADA;
            $comanda->data_fechamento = new \yii\db\Expression('NOW()');
        }

        if ($comanda->save(false)) {
            // Notifica via WhatsApp
            $this->notificarClienteWhatsApp($comanda, $novoStatus);
            return ['success' => true, 'message' => 'Status atualizado com sucesso!'];
        }

        return ['success' => false, 'message' => 'Erro ao atualizar status.'];
    }

    /**
     * Auxiliar: Envia notificação automática de status via WhatsApp (Evolution API)
     */
    private function notificarClienteWhatsApp($comanda, $status)
    {
        if (empty($comanda->cliente_telefone)) {
            return;
        }

        $tenantId = $comanda->usuario_id;
        $loja = Usuario::findOne($tenantId);
        $nomeLoja = $loja ? ($loja->nome ?: 'Nosso Estabelecimento') : 'Nosso Estabelecimento';

        $mensagens = [
            'recebido' => "👋 Olá, *{$comanda->cliente_nome}*! Seu pedido *{$comanda->numero_comanda}* foi recebido com sucesso no *{$nomeLoja}* e já está na fila!",
            'em_preparo' => "🍳 *{$comanda->cliente_nome}*, seu pedido *{$comanda->numero_comanda}* acabou de entrar em preparo na cozinha!",
            'pronto' => "📦 *{$comanda->cliente_nome}*, seu pedido *{$comanda->numero_comanda}* está pronto e embalado aguardando o entregador!",
            'em_rota' => "🛵 *{$comanda->cliente_nome}*, SEU PEDIDO SAIU PARA ENTREGA! " . ($comanda->motoboy_nome ? "Entregador: {$comanda->motoboy_nome}." : "") . " Prepare-se para receber!",
            'entregue' => "✅ *{$comanda->cliente_nome}*, pedido *{$comanda->numero_comanda}* entregue com sucesso! Bom apetite e obrigado pela preferência no *{$nomeLoja}*! ❤️",
        ];

        if (isset($mensagens[$status])) {
            try {
                $evolution = new EvolutionService();
                $evolution->sendMessage($tenantId, $comanda->cliente_telefone, $mensagens[$status]);
            } catch (\Exception $e) {
                Yii::warning("Erro ao enviar mensagem WhatsApp de delivery: " . $e->getMessage(), __METHOD__);
            }
        }
    }
}
