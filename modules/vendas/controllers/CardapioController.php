<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\modules\vendas\models\Mesa;
use app\modules\vendas\models\Comanda;
use app\modules\vendas\models\ComandaItem;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Categoria;
use app\models\Usuario;
use app\modules\vendas\models\ClienteInbox;
use app\modules\evolution\services\EvolutionService;

class CardapioController extends Controller
{
    public $layout = false; // Layout limpo para dispositivos móveis
    public $enableCsrfValidation = false; // Endpoint público para clientes de mesas via QR Code

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'chamar-garcom' => ['POST'],
                    'pedir-conta' => ['POST'],
                    'fazer-pedido-mesa' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Interface Pública do Cardápio Digital da Mesa por QR Code
     */
    public function actionMesa($id)
    {
        $mesa = Mesa::findOne($id);
        if (!$mesa) {
            throw new NotFoundHttpException('Mesa não encontrada.');
        }

        $tenantId = $mesa->usuario_id;
        $loja = Usuario::findOne($tenantId);

        $categorias = Categoria::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        $produtos = Produto::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->with(['opcionais'])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        $comanda = $mesa->comandaAtiva;

        return $this->render('mesa', [
            'mesa' => $mesa,
            'loja' => $loja,
            'categorias' => $categorias,
            'produtos' => $produtos,
            'comanda' => $comanda,
        ]);
    }

    /**
     * Ação Pública: Cliente clica em "Chamar Garçom"
     */
    public function actionChamarGarcom()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $mesaId = Yii::$app->request->post('mesa_id') ?: Yii::$app->request->get('mesa_id');
        if (empty($mesaId)) {
            $raw = json_decode(Yii::$app->request->getRawBody(), true);
            $mesaId = $raw['mesa_id'] ?? null;
        }

        $mesa = Mesa::findOne($mesaId);
        if (!$mesa) {
            return ['success' => false, 'message' => 'Mesa não encontrada.'];
        }

        // Registra o chamado no Direct Hub / Painel em tempo real do restaurante
        ClienteInbox::postar(
            $mesa->usuario_id,
            null,
            ClienteInbox::TIPO_CHAMADO,
            "🔔 Chamado de Garçom (Mesa {$mesa->numero_mesa})",
            "O cliente na Mesa {$mesa->numero_mesa} solicitou atendimento do garçom.",
            null,
            ['mesa_id' => $mesa->id, 'status' => 'aguardando'],
            $mesa->id
        );

        // Notificação de WhatsApp (Opcional)
        try {
            $loja = Usuario::findOne($mesa->usuario_id);
            if ($loja && !empty($loja->telefone)) {
                $evolution = new EvolutionService();
                $evolution->sendMessage($mesa->usuario_id, $loja->telefone, "🔔 *ATENÇÃO*: O cliente na *Mesa {$mesa->numero_mesa}* chamou o garçom!");
            }
        } catch (\Exception $e) {}

        return ['success' => true, 'message' => 'Garçom chamado! Atendente a caminho.'];
    }

    /**
     * Ação Pública: Cliente clica em "Pedir a Conta"
     */
    public function actionPedirConta()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $mesaId = Yii::$app->request->post('mesa_id') ?: Yii::$app->request->get('mesa_id');
        if (empty($mesaId)) {
            $raw = json_decode(Yii::$app->request->getRawBody(), true);
            $mesaId = $raw['mesa_id'] ?? null;
        }

        $mesa = Mesa::findOne($mesaId);
        if (!$mesa) {
            return ['success' => false, 'message' => 'Mesa não encontrada.'];
        }

        if ($mesa->getConsumoTotal() <= 0) {
            return ['success' => false, 'message' => 'A mesa ainda não possui consumo para fechar a conta.'];
        }

        $mesa->status = Mesa::STATUS_AGUARDANDO_CONTA;
        $mesa->save(false);

        // Registra o pedido de conta no Direct Hub / Painel em tempo real do restaurante
        ClienteInbox::postar(
            $mesa->usuario_id,
            null,
            ClienteInbox::TIPO_CONTA,
            "🧾 Fechamento de Conta (Mesa {$mesa->numero_mesa})",
            "O cliente na Mesa {$mesa->numero_mesa} solicitou a conta.",
            null,
            ['mesa_id' => $mesa->id, 'status' => 'fechamento_solicitado'],
            $mesa->id
        );

        return ['success' => true, 'message' => 'Solicitação enviada! O garçom trará a conta em instantes.'];
    }

    /**
     * Ação Pública: Cliente envia pedido do carrinho direto para a Mesa
     */
    public function actionFazerPedidoMesa()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request->post();
        if (empty($request)) {
            $request = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        }

        $mesaId = $request['mesa_id'] ?? Yii::$app->request->get('mesa_id');
        $itensRaw = $request['itens'] ?? '[]';
        $clienteNome = trim($request['cliente_nome'] ?? 'Cliente QR Code');

        $mesa = Mesa::findOne($mesaId);
        if (!$mesa) {
            return ['success' => false, 'message' => 'Mesa não encontrada.'];
        }

        $itens = json_decode($itensRaw, true);
        if (empty($itens)) {
            return ['success' => false, 'message' => 'Seu carrinho está vazio!'];
        }

        $tenantId = $mesa->usuario_id;
        $comanda = $mesa->getOuCriarComandaAtiva();
        if ($comanda->cliente_nome === 'Cliente' && !empty($clienteNome)) {
            $comanda->cliente_nome = $clienteNome;
            $comanda->save(false);
        }

        $salvos = 0;
        foreach ($itens as $it) {
            $prodId = $it['produto_id'] ?? null;
            $qtd = (float)($it['quantidade'] ?? 1);
            $valorAdicional = (float)($it['valor_adicional'] ?? 0);
            $obs = trim($it['observacoes'] ?? '');

            $produto = Produto::findOne(['id' => $prodId, 'usuario_id' => $tenantId]);
            if ($produto) {
                $item = new ComandaItem();
                $item->comanda_id = $comanda->id;
                $item->produto_id = $produto->id;
                $item->quantidade = $qtd > 0 ? $qtd : 1;
                $item->valor_unitario = (float)$produto->getPrecoFinal() + $valorAdicional;
                $item->observacoes = $obs;
                $item->destino_preparo = 'cozinha';
                $item->status_preparo = ComandaItem::STATUS_PENDENTE;
                if ($item->save(false)) {
                    $salvos++;
                }
            }
        }

        return ['success' => true, 'message' => "{$salvos} pedido(s) enviado(s) para a cozinha com sucesso!"];
    }
}
