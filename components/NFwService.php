<?php

namespace app\components; // <--- Namespace mudou para 'app'

use Yii;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use Exception;

class NFeService
{
    private $tools;

    public function __construct()
    {
        $params = Yii::$app->params['nfe'];
        
        // Resolve o caminho absoluto usando o Alias do Yii2
        $pathCertificado = Yii::getAlias($params['path_certs']) . $params['certificado_nome'];

        if (!file_exists($pathCertificado)) {
            throw new Exception("Certificado não encontrado em: $pathCertificado");
        }

        $configJson = json_encode([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => $params['ambiente'],
            "razaosocial" => "SUA RAZAO SOCIAL", // Pode vir do banco
            "siglaUF" => $params['uf_emitente'],
            "cnpj" => $params['cnpj_emitente'],
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "AAAAAAA",
            "CSC" => "GPB0J108...",
            "CSCid" => "000001",
        ]);

        $pfxContent = file_get_contents($pathCertificado);
        $certificate = Certificate::readPfx($pfxContent, $params['certificado_senha']);

        $this->tools = new Tools($configJson, $certificate);
        $this->tools->model('55'); // 55 = NFe, 65 = NFCe
    }

    public function emitir($nfeMake)
    {
        try {
            $xml = $nfeMake->getXML(); 
            $xmlAssinado = $this->tools->signNFe($xml);

            $idLote = str_replace('.', '', microtime(true));
            
            // Envia para SEFAZ
            $resp = $this->tools->sefazEnviaLote([$xmlAssinado], $idLote);

            $st = new Standardize();
            $std = $st->toStd($resp);

            // Verifica Erro de Comunicação ou Rejeição do Lote
            if (isset($std->cStat) && $std->cStat != 103 && $std->cStat != 104) {
                 return ['sucesso' => false, 'motivo' => "Erro Lote: {$std->xMotivo} (Cód: {$std->cStat})"];
            }
            
            // Captura o Recibo para consulta (Em produção deve-se consultar o recibo depois)
            // Para simplificar aqui, assumimos o retorno síncrono (comum em homologação/NFCe)
            if (isset($std->protNFe->infProt)) {
                $cStat = $std->protNFe->infProt->cStat;
                if ($cStat == 100) { // Autorizado
                     // Salva o XML Autorizado na pasta
                     $chave = $std->protNFe->infProt->chNFe;
                     $xmlProc = $this->tools->protocolo($xmlAssinado, $st->toXml($std->protNFe));
                     
                     $pathSalvar = Yii::getAlias(Yii::$app->params['nfe']['path_xmls']) . 
                                   ($this->tools->model() == 55 ? 'nfe/' : 'nfce/') . 
                                   date('Ym') . '/';
                                   
                     if (!is_dir($pathSalvar)) mkdir($pathSalvar, 0777, true);
                     file_put_contents($pathSalvar . $chave . '-procNFe.xml', $xmlProc);

                     return ['sucesso' => true, 'chave' => $chave, 'xml' => $xmlProc];
                } else {
                    return ['sucesso' => false, 'motivo' => "Rejeição: {$std->protNFe->infProt->xMotivo}"];
                }
            }
            
            return ['sucesso' => false, 'motivo' => 'Erro desconhecido ao processar retorno.'];

        } catch (Exception $e) {
            return ['sucesso' => false, 'motivo' => $e->getMessage()];
        }
    }
}