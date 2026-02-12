<?php

namespace app\modules\marketplace\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\models\MarketplaceProduto;
use app\modules\marketplace\models\MarketplacePedido;
use app\modules\marketplace\models\MarketplaceSyncLog;

/**
 * Dashboard Controller para o módulo Marketplace
 */
class DashboardController extends Controller
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
                        'roles' => ['@'], // Apenas usuários autenticados
                    ],
                ],
            ],
        ];
    }

    /**
     * Dashboard principal do módulo marketplace
     * @return string
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        // Verificar se o módulo está habilitado
        if (!\app\modules\marketplace\Module::isEnabled()) {
            Yii::$app->session->setFlash('warning', 'O módulo de Marketplaces está desabilitado. Configure em config/params.php');
        }

        // Buscar configurações de marketplaces
        $configs = MarketplaceConfig::find()
            ->where(['usuario_id' => $usuarioId])
            ->all();

        // Estatísticas gerais
        $stats = [
            'total_produtos' => MarketplaceProduto::find()
                ->joinWith('produto')
                ->where(['prest_produtos.usuario_id' => $usuarioId])
                ->count(),

            'total_pedidos' => MarketplacePedido::find()
                ->where(['usuario_id' => $usuarioId])
                ->count(),

            'pedidos_pendentes' => MarketplacePedido::find()
                ->where(['usuario_id' => $usuarioId, 'importado' => false])
                ->count(),

            'pedidos_hoje' => MarketplacePedido::find()
                ->where(['usuario_id' => $usuarioId])
                ->andWhere(['>=', 'data_pedido', date('Y-m-d 00:00:00')])
                ->count(),
        ];

        // Últimos logs de sincronização
        $ultimosLogs = MarketplaceSyncLog::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['data_inicio' => SORT_DESC])
            ->limit(10)
            ->all();

        // Pedidos recentes não importados
        $pedidosPendentes = MarketplacePedido::find()
            ->where(['usuario_id' => $usuarioId, 'importado' => false])
            ->orderBy(['data_pedido' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->render('index', [
            'configs' => $configs,
            'stats' => $stats,
            'ultimosLogs' => $ultimosLogs,
            'pedidosPendentes' => $pedidosPendentes,
        ]);
    }

    /**
     * Página de status de sincronização
     * @return string
     */
    public function actionSync()
    {
        $usuarioId = Yii::$app->user->id;

        $logs = MarketplaceSyncLog::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['data_inicio' => SORT_DESC])
            ->limit(50)
            ->all();

        return $this->render('sync', [
            'logs' => $logs,
        ]);
    }
}
