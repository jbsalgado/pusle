<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\modules\admin\services\SaasBillingService;

/**
 * SaasBillingController - Comandos de Console para Faturamento do SaaS e Cobrança
 */
class SaasBillingController extends Controller
{
    /**
     * Roda o fechamento de faturas do SaaS para todas as lojas
     * Uso: php yii saas-billing/fechar-mes [2026-08]
     */
    public function actionFecharMes($mes = null)
    {
        $service = new SaasBillingService();
        $this->stdout("Iniciando fechamento de faturas do SaaS para o mês: " . ($mes ?: 'mês anterior') . "...\n");
        
        $faturas = $service->fecharMesGlobal($mes);
        $totalFaturado = 0.0;
        
        foreach ($faturas as $f) {
            $totalFaturado += (float) $f->valor_total;
            $lojaNome = $f->usuario ? $f->usuario->nome : $f->usuario_id;
            $this->stdout("  [FATURA] Loja: {$lojaNome} | Mês: {$f->mes_referencia} | Total: R$ " . number_format($f->valor_total, 2, ',', '.') . "\n");
        }

        $this->stdout("Fechamento concluído. Total de lojas processadas: " . count($faturas) . " | Total a receber: R$ " . number_format($totalFaturado, 2, ',', '.') . "\n");
        return ExitCode::OK;
    }

    /**
     * Processa carências e bloqueia inadimplentes
     * Uso no crontab diário: php yii saas-billing/verificar-inadimplencia
     */
    public function actionVerificarInadimplencia()
    {
        $service = new SaasBillingService();
        $this->stdout("Processando régua de cobrança e inadimplência do SaaS...\n");
        
        $res = $service->processarInadimplencia();
        $this->stdout("Resultado: {$res['total_atrasadas']} faturas atrasadas | {$res['bloqueadas']} lojas bloqueadas | {$res['notificadas']} notificadas.\n");
        return ExitCode::OK;
    }
}
