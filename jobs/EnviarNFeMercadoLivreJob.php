<?php

namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;
use app\modules\vendas\models\NotaFiscal;
use app\modules\marketplace\models\MarketplacePedido;
use app\modules\marketplace\models\MarketplaceConfig;
use app\modules\marketplace\components\MercadoLivreService;

/**
 * Job assíncrono para enviar a NF-e autorizada ao Mercado Livre (Mercado Envios)
 * e obter a etiqueta de despacho para liberação do envio.
 */
class EnviarNFeMercadoLivreJob extends BaseObject implements JobInterface, RetryableJobInterface
{
    /**
     * @var string UUID da NotaFiscal autorizada
     */
    public $notaFiscalId;

    /**
     * Tempo de espera entre retentativas (segundos)
     */
    public function getTTime()
    {
        return 60;
    }

    /**
     * Permite até 3 tentativas
     */
    public function canRetry($attempt, $error)
    {
        return $attempt < 3;
    }

    /**
     * Executa a vinculação da nota fiscal ao envio do Mercado Livre
     *
     * @param \yii\queue\Queue $queue
     */
    public function execute($queue)
    {
        Yii::info("[EnviarNFeMercadoLivreJob] Iniciando processamento para NotaFiscal ID: {$this->notaFiscalId}", 'marketplace');

        $nota = NotaFiscal::findOne($this->notaFiscalId);
        if (!$nota || $nota->status_sefaz !== NotaFiscal::STATUS_AUTORIZADA || empty($nota->chave_acesso)) {
            Yii::warning("[EnviarNFeMercadoLivreJob] Nota fiscal {$this->notaFiscalId} não está autorizada ou não possui chave de acesso. Abortando envio.", 'marketplace');
            return;
        }

        // Localizar o pedido do Marketplace
        $mpPedido = null;
        if ($nota->venda_id) {
            $mpPedido = MarketplacePedido::findOne(['venda_id' => $nota->venda_id]);
        }
        if (!$mpPedido && $nota->marketplace_pedido_id) {
            $mpPedido = MarketplacePedido::findOne(['marketplace_pedido_id' => $nota->marketplace_pedido_id]);
        }

        if (!$mpPedido) {
            Yii::warning("[EnviarNFeMercadoLivreJob] Nenhum MarketplacePedido vinculado à venda {$nota->venda_id}.", 'marketplace');
            return;
        }

        // Carregar configurações de marketplace do tenant
        $mpConfig = MarketplaceConfig::findOne([
            'usuario_id' => $nota->usuario_id,
            'marketplace' => MarketplaceConfig::MARKETPLACE_MERCADO_LIVRE,
            'ativo' => true,
        ]);

        if (!$mpConfig) {
            Yii::error("[EnviarNFeMercadoLivreJob] Configuração ativa do Mercado Livre não encontrada para tenant {$nota->usuario_id}.", 'marketplace');
            return;
        }

        $service = new MercadoLivreService();
        $service->setConfig($mpConfig->attributes);

        // Obter shipment_id
        $dadosCompletos = is_array($mpPedido->dados_completos) ? $mpPedido->dados_completos : json_decode($mpPedido->dados_completos ?: '{}', true);
        $shipmentId = $dadosCompletos['shipping']['id'] ?? ($dadosCompletos['shipment_id'] ?? null);

        if (!$shipmentId) {
            // Tenta consultar o pedido na API do ML para extrair o shipment_id
            try {
                $orderApi = $service->fetchOrder($mpPedido->marketplace_pedido_id);
                $shipmentId = $orderApi['shipping']['id'] ?? null;
            } catch (\Throwable $e) {
                Yii::warning("[EnviarNFeMercadoLivreJob] Falha ao consultar order na API: " . $e->getMessage(), 'marketplace');
            }
        }

        $sucesso = false;
        if ($shipmentId) {
            Yii::info("[EnviarNFeMercadoLivreJob] Enviando dados fiscais para shipment {$shipmentId}...", 'marketplace');
            $sucesso = $service->postShipmentInvoiceData($shipmentId, $nota->chave_acesso, $nota->xml_autorizado);
        } else {
            Yii::info("[EnviarNFeMercadoLivreJob] Shipment ID não localizado. Tentando vincular via order {$mpPedido->marketplace_pedido_id}...", 'marketplace');
            $sucesso = $service->uploadNfe($mpPedido->marketplace_pedido_id, $nota->chave_acesso, $nota->xml_autorizado);
        }

        if ($sucesso) {
            Yii::info("[EnviarNFeMercadoLivreJob] NF-e vinculada com sucesso ao Mercado Livre!", 'marketplace');

            // Marcar nota como enviada ao ML (rastreabilidade e prevenção de reenvio duplo)
            $nota->enviada_ml    = true;
            $nota->data_envio_ml = date('Y-m-d H:i:s');
            $nota->save(false);

            // Tenta baixar a etiqueta de frete liberada se houver shipmentId
            if ($shipmentId) {
                try {
                    $pdfContent = $service->getShippingLabelPdf($shipmentId);
                    if (!empty($pdfContent)) {
                        $dir = Yii::getAlias("@app/web/uploads/etiquetas/{$nota->usuario_id}");
                        if (!is_dir($dir)) {
                            @mkdir($dir, 0775, true);
                        }

                        $labelPath = "{$dir}/etiqueta_{$shipmentId}.pdf";
                        file_put_contents($labelPath, $pdfContent);

                        $mpPedido->status_envio = 'pronto_para_envio';
                        $mpPedido->save(false);

                        Yii::info("[EnviarNFeMercadoLivreJob] Etiqueta de frete salva em {$labelPath}", 'marketplace');
                    }
                } catch (\Throwable $e) {
                    Yii::warning("[EnviarNFeMercadoLivreJob] Falha ao baixar etiqueta de frete: " . $e->getMessage(), 'marketplace');
                }
            }
        } else {
            throw new \Exception("Falha na API do Mercado Livre ao vincular NF-e ({$nota->chave_acesso}).");
        }
    }
}
