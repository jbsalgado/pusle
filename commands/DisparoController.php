<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\modules\vendas\services\DisparoMassaService;

/**
 * Comando de console para processar a fila de disparos em massa de cards.
 */
class DisparoController extends Controller
{
    /**
     * Processa itens pendentes na fila de disparo.
     * Uso: php yii disparo/processar [disparo_id] [limit]
     *
     * @param string|null $disparoId ID opcional da campanha
     * @param int $limit Número de itens por lote (padrão 50)
     * @return int ExitCode
     */
    public function actionProcessar($disparoId = null, $limit = 50)
    {
        $this->stdout("🚀 Iniciando processamento da fila de disparo em massa...\n");

        try {
            $service = new DisparoMassaService();
            $processados = $service->processarFilaDisparo($disparoId, (int)$limit);

            $this->stdout("✅ Total de itens processados nesta rodada: {$processados}\n", \yii\helpers\Console::FG_GREEN);
            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("❌ Erro ao processar fila de disparo: " . $e->getMessage() . "\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
