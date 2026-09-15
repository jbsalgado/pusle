<?php

namespace app\components\fiscal;

use Yii;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Configuracao;
use app\modules\vendas\models\NotaFiscal;
use app\modules\marketplace\models\MarketplacePedido;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use NFePHP\DA\NFe\Danfe;

/**
 * Serviço central multitenant para orquestração fiscal com a SEFAZ
 */
class FiscalService
{
    /**
     * Emite uma NF-e Modelo 55 para uma Venda
     *
     * @param string $vendaId ID da venda
     * @param string $modelo '55' (NF-e Mercantil) ou '65' (NFC-e)
     * @return NotaFiscal Instância atualizada da NotaFiscal
     * @throws \Exception Em caso de erro irrecuperável
     */
    public static function emitirParaVenda(string $vendaId, string $modelo = '55'): NotaFiscal
    {
        $venda = Venda::findOne($vendaId);
        if (!$venda) {
            throw new \Exception("Venda '{$vendaId}' não encontrada.");
        }

        $usuarioId = $venda->usuario_id;
        $config = Configuracao::findOne(['usuario_id' => $usuarioId]);
        if (!$config || empty($config->cnpj)) {
            throw new \Exception("Configuração fiscal da loja não encontrada ou incompleta.");
        }

        // 1. Obter e descriptografar o Certificado Digital A1
        $pfxBinary = $config->getCertificadoBinarioDescriptografado();
        $pfxPassword = $config->getCertificadoSenhaDescriptografada();

        if (empty($pfxBinary) || empty($pfxPassword)) {
            throw new \Exception("Certificado Digital A1 (.pfx) ou senha não configurados para a loja.");
        }

        try {
            $certificate = Certificate::readPfx($pfxBinary, $pfxPassword);
        } catch (\Throwable $e) {
            Yii::error("[FiscalService] Falha ao carregar certificado A1 do lojista {$usuarioId}: " . $e->getMessage(), 'fiscal');
            throw new \Exception("Falha ao abrir Certificado Digital A1: " . $e->getMessage());
        }

        // 2. Configurar o Tools da NFePHP
        $ufSigla = strtoupper(trim($config->uf_sigla ?: ($config->usuario->estado ?? 'SP')));
        $ambiente = (int)($config->nfe_ambiente ?: 2); // 1 = Produção, 2 = Homologação

        $configArray = [
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => $ambiente,
            "razaosocial" => $config->razao_social ?: $config->nome_loja,
            "siglaUF" => $ufSigla,
            "cnpj" => IbgeHelper::sanitizeDoc($config->cnpj),
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "",
            "CSC" => $config->nfce_csc ?? '',
            "CSCid" => $config->nfce_csc_id ?? '',
        ];

        $tools = new Tools(json_encode($configArray), $certificate);
        $tools->model($modelo);

        // 3. Controle Atômico do Número Sequencial
        $serie = (int)($config->nfe_serie ?: 1);
        $numeroNF = (int)Yii::$app->db->createCommand("
            UPDATE prest_configuracoes 
            SET nfe_numero_atual = COALESCE(nfe_numero_atual, 0) + 1 
            WHERE usuario_id = :uid 
            RETURNING nfe_numero_atual
        ", [':uid' => $usuarioId])->queryScalar();

        // 4. Identificar vínculo de Marketplace se houver
        $mpPedido = MarketplacePedido::findOne(['venda_id' => $vendaId]);
        $marketplace = $mpPedido ? $mpPedido->marketplace : null;
        $mpPedidoId = $mpPedido ? $mpPedido->marketplace_pedido_id : null;

        // 5. Construir o XML da NF-e 55
        $xmlBruto = NFe55Builder::build($venda, $config, $numeroNF, $serie);

        // 6. Assinar Digitalmente o XML
        try {
            $xmlAssinado = $tools->signNFe($xmlBruto);
        } catch (\Throwable $e) {
            Yii::error("[FiscalService] Erro na assinatura digital: " . $e->getMessage(), 'fiscal');
            throw new \Exception("Erro ao assinar XML da NF-e: " . $e->getMessage());
        }

        // Extrair chave de acesso do XML assinado
        preg_match('/Id="NFe([0-9]{44})"/', $xmlAssinado, $matches);
        $chaveAcesso = $matches[1] ?? null;

        // 7. Criar ou recuperar registro em prest_notas_fiscais
        $nota = NotaFiscal::findOne(['venda_id' => $vendaId]);
        if (!$nota) {
            $nota = new NotaFiscal();
            $nota->usuario_id = $usuarioId;
            $nota->venda_id = $vendaId;
        }

        $nota->marketplace = $marketplace;
        $nota->marketplace_pedido_id = $mpPedidoId;
        $nota->modelo = $modelo;
        $nota->serie = $serie;
        $nota->numero = $numeroNF;
        $nota->chave_acesso = $chaveAcesso;
        $nota->ambiente = $ambiente;
        $nota->status_sefaz = NotaFiscal::STATUS_PROCESSANDO;
        $nota->xml_envio = $xmlAssinado;
        $nota->save(false);

        // 8. Transmitir para a SEFAZ
        try {
            $idLote = str_pad((string)$numeroNF, 15, '0', STR_PAD_LEFT);
            // Modo síncrono = 1
            $respEnvio = $tools->sefazEnviaLote([$xmlAssinado], $idLote, 1);
            $standardize = new Standardize();
            $std = $standardize->toStd($respEnvio);

            $cStat = (string)($std->cStat ?? '');
            $xMotivo = (string)($std->xMotivo ?? '');

            // SEFAZ processou em modo síncrono (cStat 104)
            if ($cStat === '104' && isset($std->protNFe->infProt)) {
                $infProt = $std->protNFe->infProt;
                $itemStat = (string)$infProt->cStat;
                $itemMotivo = (string)$infProt->xMotivo;

                if ($itemStat === '100') {
                    // AUTORIZADA COM SUCESSO!
                    $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $tools->lastResponse);

                    // Gerar DANFE PDF
                    $pdfDanfePath = self::salvarDanfePdf($xmlProtocolado, $usuarioId, $chaveAcesso);

                    $nota->status_sefaz = NotaFiscal::STATUS_AUTORIZADA;
                    $nota->protocolo_autorizacao = (string)$infProt->nProt;
                    $nota->cstat = $itemStat;
                    $nota->xmotivo = $itemMotivo;
                    $nota->data_autorizacao = date('Y-m-d H:i:s');
                    $nota->xml_autorizado = $xmlProtocolado;
                    $nota->pdf_danfe_path = $pdfDanfePath;
                    $nota->save(false);

                    Yii::info("[FiscalService] NF-e {$numeroNF} autorizada para venda {$vendaId}. Chave: {$chaveAcesso}", 'fiscal');

                    // Se a venda for do Mercado Livre, dispara o job para subir a NF-e no Mercado Envios
                    if ($marketplace === 'MERCADO_LIVRE' && !empty($mpPedido)) {
                        self::agendarVinculoMercadoLivre($nota->id);
                    }

                    return $nota;
                } else {
                    // Rejeição da SEFAZ
                    $nota->status_sefaz = NotaFiscal::STATUS_REJEITADA;
                    $nota->cstat = $itemStat;
                    $nota->xmotivo = $itemMotivo;
                    $nota->save(false);

                    Yii::warning("[FiscalService] NF-e {$numeroNF} rejeitada pela SEFAZ: [{$itemStat}] {$itemMotivo}", 'fiscal');
                    return $nota;
                }
            } elseif ($cStat === '103') {
                // Lote recebido assíncrono (aguardando consulta de recibo)
                $nRec = (string)($std->infRec->nRec ?? '');
                Yii::info("[FiscalService] Lote assíncrono recebido com recibo {$nRec}. Consultando...", 'fiscal');

                // Aguarda 1 segundo e consulta o recibo
                usleep(1000000);
                $respRecibo = $tools->sefazConsultaRecibo($nRec);
                $stdRecibo = $standardize->toStd($respRecibo);

                if (isset($stdRecibo->protNFe->infProt)) {
                    $infProt = $stdRecibo->protNFe->infProt;
                    $itemStat = (string)$infProt->cStat;
                    $itemMotivo = (string)$infProt->xMotivo;

                    if ($itemStat === '100') {
                        $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $tools->lastResponse);
                        $pdfDanfePath = self::salvarDanfePdf($xmlProtocolado, $usuarioId, $chaveAcesso);

                        $nota->status_sefaz = NotaFiscal::STATUS_AUTORIZADA;
                        $nota->protocolo_autorizacao = (string)$infProt->nProt;
                        $nota->cstat = $itemStat;
                        $nota->xmotivo = $itemMotivo;
                        $nota->data_autorizacao = date('Y-m-d H:i:s');
                        $nota->xml_autorizado = $xmlProtocolado;
                        $nota->pdf_danfe_path = $pdfDanfePath;
                        $nota->save(false);

                        if ($marketplace === 'MERCADO_LIVRE' && !empty($mpPedido)) {
                            self::agendarVinculoMercadoLivre($nota->id);
                        }

                        return $nota;
                    } else {
                        $nota->status_sefaz = NotaFiscal::STATUS_REJEITADA;
                        $nota->cstat = $itemStat;
                        $nota->xmotivo = $itemMotivo;
                        $nota->save(false);
                        return $nota;
                    }
                }
            }

            // Outras respostas de lote
            $nota->status_sefaz = NotaFiscal::STATUS_REJEITADA;
            $nota->cstat = $cStat;
            $nota->xmotivo = $xMotivo;
            $nota->save(false);

            return $nota;
        } catch (\Throwable $e) {
            Yii::error("[FiscalService] Erro na transmissão para a SEFAZ: " . $e->getMessage(), 'fiscal');
            $nota->status_sefaz = NotaFiscal::STATUS_PENDENTE;
            $nota->xmotivo = "Erro de conexão/SEFAZ: " . $e->getMessage();
            $nota->save(false);

            throw $e;
        }
    }

    /**
     * Salva o DANFE PDF no disco e retorna o caminho relativo
     */
    protected static function salvarDanfePdf(string $xmlProtocolado, string $usuarioId, string $chaveAcesso): ?string
    {
        try {
            $danfe = new Danfe($xmlProtocolado, 'P', 'A4', '', 'I', '');
            $danfe->monta();
            $pdfContent = $danfe->render();

            $dir = Yii::getAlias("@app/web/uploads/nfe/{$usuarioId}");
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $pdfPath = "{$dir}/{$chaveAcesso}.pdf";
            file_put_contents($pdfPath, $pdfContent);

            return "/uploads/nfe/{$usuarioId}/{$chaveAcesso}.pdf";
        } catch (\Throwable $e) {
            Yii::error("[FiscalService] Erro ao renderizar DANFE PDF: " . $e->getMessage(), 'fiscal');
            return null;
        }
    }

    /**
     * Enfileira o envio da NF-e para o Mercado Livre
     */
    protected static function agendarVinculoMercadoLivre(string $notaFiscalId): void
    {
        try {
            if (Yii::$app->has('queue')) {
                Yii::$app->queue->push(new \app\jobs\EnviarNFeMercadoLivreJob([
                    'notaFiscalId' => $notaFiscalId,
                ]));
                Yii::info("[FiscalService] EnviarNFeMercadoLivreJob enfileirado para nota {$notaFiscalId}", 'marketplace');
            }
        } catch (\Throwable $e) {
            Yii::error("[FiscalService] Falha ao enfileirar EnviarNFeMercadoLivreJob: " . $e->getMessage(), 'marketplace');
        }
    }
}
