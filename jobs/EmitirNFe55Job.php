<?php

namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;
use app\components\fiscal\FiscalService;
use app\modules\vendas\models\NotaFiscal;

/**
 * Job assíncrono para autorização de NF-e 55 junto à SEFAZ via fila do Pulse ERP.
 */
class EmitirNFe55Job extends BaseObject implements JobInterface, RetryableJobInterface
{
    /**
     * @var string UUID da Venda
     */
    public $vendaId;

    /**
     * @var string Modelo fiscal ('55' para NF-e ou '65' para NFC-e)
     */
    public $modelo = '55';

    /**
     * Tempo de espera em segundos entre tentativas de reenvio à SEFAZ
     */
    public function getTTime()
    {
        return 30;
    }

    /**
     * Define se o job pode ser retentado em caso de falha de conexão ou timeout da SEFAZ
     */
    public function canRetry($attempt, $error)
    {
        // Se já excedeu 3 tentativas, desiste
        if ($attempt >= 3) {
            return false;
        }

        // Se o erro foi uma rejeição de regras de validação cadastral ou NCM, não adianta retentar
        $msg = $error ? $error->getMessage() : '';
        if (stripos($msg, 'NCM') !== false || stripos($msg, 'Rejeição') !== false) {
            return false;
        }

        // Erros de timeout HTTP, conexão ou indisponibilidade da SEFAZ podem ser retentados
        return true;
    }

    /**
     * Executa a emissão fiscal em background
     *
     * @param \yii\queue\Queue $queue
     */
    public function execute($queue)
    {
        Yii::info("[EmitirNFe55Job] Iniciando emissão de NF-e {$this->modelo} para venda: {$this->vendaId}", 'fiscal');

        // Idempotência: Se já houver nota fiscal autorizada para esta venda, ignora
        $notaExistente = NotaFiscal::findOne([
            'venda_id' => $this->vendaId,
            'status_sefaz' => NotaFiscal::STATUS_AUTORIZADA,
        ]);

        if ($notaExistente) {
            Yii::info("[EmitirNFe55Job] Venda {$this->vendaId} já possui NF-e autorizada (Chave: {$notaExistente->chave_acesso}). Ignorando reemissão.", 'fiscal');
            return;
        }

        try {
            $nota = FiscalService::emitirParaVenda($this->vendaId, $this->modelo);
            Yii::info("[EmitirNFe55Job] Emissão finalizada com status [{$nota->status_sefaz}] para venda {$this->vendaId}", 'fiscal');
        } catch (\Throwable $e) {
            Yii::error("[EmitirNFe55Job] Erro na emissão da NF-e para venda {$this->vendaId}: " . $e->getMessage(), 'fiscal');
            throw $e;
        }
    }
}
