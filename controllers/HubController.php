<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use app\models\Usuarios;
use app\modules\vendas\models\Clientes;
use app\modules\vendas\models\ClienteInbox;
use app\modules\vendas\models\Mesa;
use app\modules\vendas\models\Comanda;
use app\modules\vendas\models\ComandaItem;
use app\modules\vendas\models\Produtos;
use app\modules\vendas\models\ProdutoCard;

/**
 * HubController — Direct Hub do Cliente & Comanda Digital (Multi-Tenant)
 *
 * Permite a comunicação direta (vídeos, fotos, ofertas, chat) e controle de
 * Comanda Digital para mesas/totens, com acesso via Magic Link ou QR Code.
 */
class HubController extends Controller
{
    public $layout = 'hub_layout';

    /**
     * Tela Principal do Direct Hub / Comanda Digital
     *
     * @param string|null $token  Magic token do cliente
     * @param string|null $slug   Slug da loja/empresa
     * @param string|null $mesa   Número ou ID da mesa
     * @param string|null $comanda Número ou ID da comanda
     */
    public function actionIndex($token = null, $slug = null, $mesa = null, $comanda = null)
    {
        $cliente = null;
        $usuario = null;
        $mesaModel = null;
        $comandaModel = null;

        // 1. Identificação via Magic Token
        if (!empty($token)) {
            $cliente = Clientes::findByMagicToken($token);
            if ($cliente !== null) {
                $usuario = Usuarios::findOne($cliente->usuario_id);
            }
        }

        // 2. Identificação via Slug da Loja
        if ($usuario === null && !empty($slug)) {
            $usuario = Usuarios::find()
                ->where(['or', ['slug' => $slug], ['id' => $slug], ['nome_loja' => $slug]])
                ->one();
        }

        // Se ainda não achou tenant e tem sessão de cliente
        $session = Yii::$app->session;
        if ($cliente === null && $session->has('hub_cliente_id')) {
            $cliente = Clientes::findOne($session->get('hub_cliente_id'));
            if ($cliente !== null && $usuario === null) {
                $usuario = Usuarios::findOne($cliente->usuario_id);
            }
        }

        if ($usuario === null) {
            throw new NotFoundHttpException("Estabelecimento não encontrado ou link expirado.");
        }

        // 3. Resolução de Mesa / Totem (Food Service)
        if (!empty($mesa)) {
            $mesaModel = Mesa::find()
                ->where(['usuario_id' => $usuario->id])
                ->andWhere(['or', ['id' => $mesa], ['numero_mesa' => (string)$mesa]])
                ->one();
        }

        // 4. Resolução de Comanda
        if (!empty($comanda)) {
            $comandaModel = Comanda::find()
                ->where(['usuario_id' => $usuario->id])
                ->andWhere(['or', ['id' => $comanda], ['numero_comanda' => (string)$comanda]])
                ->one();
        } elseif ($mesaModel !== null) {
            $comandaModel = Comanda::find()
                ->where(['usuario_id' => $usuario->id, 'mesa_id' => $mesaModel->id, 'status' => 'aberta'])
                ->orderBy(['data_abertura' => SORT_DESC])
                ->one();
        }

        // 5. Carrega timeline de mensagens, vídeos e ofertas do cliente/loja
        $inboxQuery = ClienteInbox::find()
            ->where(['usuario_id' => $usuario->id])
            ->andWhere(['or',
                ['cliente_id' => $cliente ? $cliente->id : null],
                ['cliente_id' => null] // Broadcasts gerais da loja
            ]);

        if ($mesaModel !== null) {
            $inboxQuery->orWhere(['mesa_id' => $mesaModel->id]);
        }

        $inboxMessages = $inboxQuery
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(20)
            ->all();

        // 6. Itens da comanda aberta (se houver)
        $comandaItens = [];
        $totalComanda = 0.0;
        if ($comandaModel !== null) {
            $comandaItens = ComandaItem::find()
                ->where(['comanda_id' => $comandaModel->id])
                ->orderBy(['data_pedido' => SORT_DESC])
                ->all();

            foreach ($comandaItens as $ci) {
                $totalComanda += ((float)$ci->valor_unitario * (float)$ci->quantidade);
            }
        }

        // 7. Cards de produtos e promoções recentes
        $cardsDestaque = ProdutoCard::find()
            ->where(['usuario_id' => $usuario->id])
            ->orderBy(['criado_em' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->render('index', [
            'usuario'       => $usuario,
            'cliente'       => $cliente,
            'mesa'          => $mesaModel,
            'comanda'       => $comandaModel,
            'comandaItens'  => $comandaItens,
            'totalComanda'  => $totalComanda,
            'inboxMessages' => $inboxMessages,
            'cardsDestaque' => $cardsDestaque,
        ]);
    }

    /**
     * Ação Ajax para identificação rápida por Nome + Telefone
     */
    public function actionIdentificar(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $post = Yii::$app->request->post();
        $usuarioId = $post['usuario_id'] ?? null;
        $nome      = trim((string)($post['nome'] ?? ''));
        $telefone  = trim((string)($post['telefone'] ?? ''));
        $mesaId    = $post['mesa_id'] ?? null;

        if (empty($usuarioId) || empty($telefone)) {
            return ['success' => false, 'message' => 'Por favor, informe seu WhatsApp para continuar.'];
        }

        try {
            $cliente = Clientes::findOrCreateQuick($usuarioId, $nome, $telefone);
            $token   = $cliente->getMagicToken();

            Yii::$app->session->set('hub_cliente_id', $cliente->id);

            // Se estiver em uma mesa, vincula/registra comanda se não houver
            if (!empty($mesaId)) {
                $comanda = Comanda::find()
                    ->where(['usuario_id' => $usuarioId, 'mesa_id' => $mesaId, 'status' => 'aberta'])
                    ->one();

                if ($comanda === null) {
                    $comanda = new Comanda();
                    $comanda->usuario_id = $usuarioId;
                    $comanda->mesa_id = $mesaId;
                    $comanda->numero_comanda = 'M' . substr(str_replace('-', '', $mesaId), 0, 4) . '-' . date('His');
                    $comanda->cliente_nome = $cliente->nome_completo;
                    $comanda->status = 'aberta';
                    $comanda->data_abertura = date('Y-m-d H:i:s');
                    $comanda->save(false);
                }
            }

            return [
                'success' => true,
                'token'   => $token,
                'cliente' => [
                    'id'   => $cliente->id,
                    'nome' => $cliente->nome_completo,
                    'tel'  => $cliente->telefone,
                ],
            ];
        } catch (\Throwable $t) {
            return ['success' => false, 'message' => 'Erro ao identificar: ' . $t->getMessage()];
        }
    }

    /**
     * Ação Ajax para Chamar o Garçom / Atendente
     */
    public function actionChamarGarcom(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $post      = Yii::$app->request->post();
        $usuarioId = $post['usuario_id'] ?? null;
        $clienteId = $post['cliente_id'] ?? null;
        $mesaId    = $post['mesa_id'] ?? null;
        $motivo    = $post['motivo'] ?? 'Atendimento solicitado na mesa';

        if (empty($usuarioId)) {
            return ['success' => false, 'message' => 'Parâmetros inválidos.'];
        }

        $mesa = !empty($mesaId) ? Mesa::findOne($mesaId) : null;
        $mesaNome = $mesa ? "Mesa {$mesa->numero_mesa}" : "Balcão";

        ClienteInbox::postar(
            $usuarioId,
            $clienteId,
            ClienteInbox::TIPO_CHAMADO,
            "🔔 Chamado de Atendimento ({$mesaNome})",
            $motivo,
            null,
            ['mesa_id' => $mesaId, 'status' => 'aguardando'],
            $mesaId
        );

        return [
            'success' => true,
            'message' => "Chamado enviado! Nosso garçom/atendente já está a caminho da sua {$mesaNome}.",
        ];
    }

    /**
     * Ação Ajax para Pedir Fechamento de Conta / PIX
     */
    public function actionPedirConta(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $post      = Yii::$app->request->post();
        $usuarioId = $post['usuario_id'] ?? null;
        $clienteId = $post['cliente_id'] ?? null;
        $comandaId = $post['comanda_id'] ?? null;

        if (empty($usuarioId) || empty($comandaId)) {
            return ['success' => false, 'message' => 'Comanda não informada.'];
        }

        $comanda = Comanda::findOne($comandaId);
        if (!$comanda) {
            return ['success' => false, 'message' => 'Comanda não encontrada.'];
        }

        // Notifica painel do restaurante/loja
        ClienteInbox::postar(
            $usuarioId,
            $clienteId,
            ClienteInbox::TIPO_CONTA,
            "🧾 Fechamento de Conta Solicitado",
            "Cliente da Comanda {$comanda->numero_comanda} solicitou a conta.",
            null,
            ['comanda_id' => $comandaId, 'status' => 'fechamento_solicitado'],
            $comanda->mesa_id,
            $comandaId
        );

        return [
            'success' => true,
            'message' => 'Solicitação de conta enviada ao caixa.',
        ];
    }

    /**
     * Salva assinatura Web Push do navegador do cliente
     */
    public function actionSavePush(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $post = Yii::$app->request->post();
        $token = $post['token'] ?? null;
        $subscription = $post['subscription'] ?? null;

        if (empty($token) || empty($subscription)) {
            return ['success' => false, 'message' => 'Dados inválidos.'];
        }

        $cliente = Clientes::findByMagicToken($token);
        if ($cliente !== null) {
            $current = is_array($cliente->push_subscriptions) ? $cliente->push_subscriptions : [];
            $current[] = $subscription;
            $cliente->push_subscriptions = array_values(array_unique($current, SORT_REGULAR));
            $cliente->save(false, ['push_subscriptions']);
            return ['success' => true, 'message' => 'Notificações Push ativadas com sucesso!'];
        }

        return ['success' => false, 'message' => 'Cliente não localizado.'];
    }
}
