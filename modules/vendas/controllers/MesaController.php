<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Mesa;
use app\modules\vendas\models\Comanda;
use app\modules\vendas\models\ComandaItem;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
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
     * Ação: Solicitar Pré-Conta da Mesa
     */
    public function actionSolicitarConta()
    {
        $tenantId = \app\components\TenantHelper::getId();
        $mesaId = Yii::$app->request->post('mesa_id');

        $mesa = Mesa::findOne(['id' => $mesaId, 'usuario_id' => $tenantId]);
        if ($mesa) {
            $mesa->status = Mesa::STATUS_AGUARDANDO_CONTA;
            $mesa->save(false);
            Yii::$app->session->setFlash('info', "Pré-conta solicitada para a Mesa {$mesa->numero_mesa}.");
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
}
