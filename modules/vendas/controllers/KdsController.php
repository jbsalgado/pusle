<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\modules\vendas\models\ComandaItem;
use app\modules\vendas\models\Comanda;
use app\modules\vendas\models\Mesa;

/**
 * KdsController — Kitchen Display System (Monitor de Cozinha & Bar em Tempo Real)
 */
class KdsController extends Controller
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
                    'atualizar-status' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Tela Principal do KDS (Monitor de Cozinha / Bar)
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Retorna lista de pedidos ativos em formato JSON para atualização via Polling
     */
    public function actionListarPedidosJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();
        $destinoFilter = Yii::$app->request->get('destino', 'todos');

        // Busca itens de comandas abertas do tenant
        $query = ComandaItem::find()
            ->alias('i')
            ->innerJoin(['c' => 'prest_comandas'], 'c.id = i.comanda_id')
            ->where(['c.usuario_id' => $tenantId])
            ->andWhere(['!=', 'c.status', Comanda::STATUS_FECHADA])
            ->andWhere(['!=', 'c.status', Comanda::STATUS_CANCELADA])
            ->andWhere(['in', 'i.status_preparo', [ComandaItem::STATUS_PENDENTE, ComandaItem::STATUS_EM_PREPARO, ComandaItem::STATUS_PRONTO]]);

        if ($destinoFilter !== 'todos') {
            $query->andWhere(['i.destino_preparo' => $destinoFilter]);
        }

        $query->orderBy(['i.data_pedido' => SORT_ASC]);
        $itens = $query->all();

        // Agrupa os itens por Comanda / Mesa
        $comandasAgrupadas = [];
        foreach ($itens as $item) {
            $comanda = $item->comanda;
            if (!$comanda) continue;

            $comandaId = $comanda->id;
            if (!isset($comandasAgrupadas[$comandaId])) {
                $mesaNumero = 'Balcão/Avulso';
                if ($comanda->mesa) {
                    $mesaNumero = 'Mesa ' . $comanda->mesa->numero_mesa;
                }

                $comandasAgrupadas[$comandaId] = [
                    'comanda_id' => $comanda->id,
                    'numero_comanda' => $comanda->numero_comanda,
                    'mesa_numero' => $mesaNumero,
                    'cliente_nome' => $comanda->cliente_nome ?: 'Cliente',
                    'data_abertura' => $comanda->data_abertura,
                    'itens' => [],
                ];
            }

            $prodNome = $item->produto ? $item->produto->nome : 'Produto';

            // Calcula minutos decorridos desde a inclusão do pedido
            $dataPedidoTimestamp = strtotime($item->data_pedido ?: $comanda->data_abertura);
            $minutosDecorridos = floor((time() - $dataPedidoTimestamp) / 60);

            $comandasAgrupadas[$comandaId]['itens'][] = [
                'item_id' => $item->id,
                'produto_nome' => $prodNome,
                'quantidade' => (float)$item->quantidade,
                'observacoes' => $item->observacoes,
                'destino' => $item->destino_preparo,
                'status' => $item->status_preparo,
                'hora_pedido' => date('H:i', $dataPedidoTimestamp),
                'minutos_decorridos' => max(0, $minutosDecorridos),
            ];
        }

        return [
            'success' => true,
            'total_comandas' => count($comandasAgrupadas),
            'comandas' => array_values($comandasAgrupadas),
        ];
    }

    /**
     * Altera o status de preparo de um item (pendente ➡️ em_preparo ➡️ pronto ➡️ entregue)
     */
    public function actionAtualizarStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();

        $itemId = Yii::$app->request->post('item_id');
        $novoStatus = Yii::$app->request->post('novo_status');

        $item = ComandaItem::find()
            ->alias('i')
            ->innerJoin(['c' => 'prest_comandas'], 'c.id = i.comanda_id')
            ->where(['i.id' => $itemId, 'c.usuario_id' => $tenantId])
            ->one();

        if (!$item) {
            return ['success' => false, 'message' => 'Item do pedido não encontrado.'];
        }

        $item->status_preparo = $novoStatus;
        if ($item->save(false)) {
            return ['success' => true, 'novo_status' => $novoStatus];
        }

        return ['success' => false, 'message' => 'Erro ao atualizar status do pedido.'];
    }

    /**
     * Tela Fullscreen de Chamada de Senhas para exibição na TV do Salão
     */
    public function actionPainelSenhas()
    {
        $this->layout = false;
        return $this->render('painel_senhas');
    }

    /**
     * Retorna a lista de senhas em preparo e prontas para a TV
     */
    public function actionSenhasJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = \app\components\TenantHelper::getId();

        $comandas = Comanda::find()
            ->where(['usuario_id' => $tenantId])
            ->andWhere(['not', ['senha_balcao' => null]])
            ->andWhere(['>=', 'data_abertura', date('Y-m-d 00:00:00')])
            ->orderBy(['data_abertura' => SORT_DESC])
            ->all();

        $preparo = [];
        $pronto = [];

        foreach ($comandas as $c) {
            $temPendente = false;
            foreach ($c->itens as $it) {
                if ($it->status_preparo === ComandaItem::STATUS_PENDENTE || $it->status_preparo === ComandaItem::STATUS_EM_PREPARO) {
                    $temPendente = true;
                    break;
                }
            }

            if ($temPendente || (empty($c->itens) && $c->status === Comanda::STATUS_ABERTA)) {
                $preparo[] = [
                    'senha' => $c->senha_balcao,
                    'cliente' => $c->cliente_nome,
                ];
            } else if ($c->status !== Comanda::STATUS_FECHADA) {
                $pronto[] = [
                    'senha' => $c->senha_balcao,
                    'cliente' => $c->cliente_nome,
                ];
            }
        }

        return [
            'success' => true,
            'preparo' => array_slice($preparo, 0, 8),
            'pronto' => array_slice($pronto, 0, 6),
        ];
    }
}
