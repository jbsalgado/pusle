<?php

namespace app\components;

use Yii;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Configuracao;
use app\modules\vendas\models\CupomFiscal;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\NFe\Danfce;

/**
 * NFwService - Serviço para emissão de NFe e NFCe
 */
class NFwService extends \yii\base\Component
{
    protected $tools;
    protected $config;
    protected $usuarioConfig;

    /**
     * Inicializa o serviço com as configurações do usuário
     */
    public function loadConfig($usuarioId)
    {
        $this->usuarioConfig = Configuracao::findOne(['usuario_id' => $usuarioId]);

        if (!$this->usuarioConfig || !$this->usuarioConfig->cnpj) {
            throw new \Exception("Configuração fiscal não encontrada para o usuário.");
        }

        $config = [
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => (int)$this->usuarioConfig->nfe_ambiente ?: 2,
            "razaosocial" => $this->usuarioConfig->razao_social,
            "siglaUF" => "SP", // TODO: Buscar da configuração de endereço
            "cnpj" => $this->usuarioConfig->cnpj,
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "",
            "CSC" => $this->usuarioConfig->nfce_csc,
            "CSCid" => $this->usuarioConfig->nfce_csc_id,
        ];

        $this->config = json_encode($config);

        if ($this->usuarioConfig->certificado_pfx) {
            $pfxContent = base64_decode($this->usuarioConfig->certificado_pfx);
            $password = $this->usuarioConfig->certificado_senha;
            $certificate = Certificate::readPfx($pfxContent, $password);
            $this->tools = new Tools($this->config, $certificate);
            $this->tools->model('65'); // Default NFCe
        }
    }

    /**
     * Emite uma NFCe para uma venda
     */
    public function emitirCupom($vendaId)
    {
        $venda = Venda::findOne($vendaId);
        if (!$venda) {
            throw new \Exception("Venda não encontrada.");
        }

        $this->loadConfig($venda->usuario_id);

        if (!$this->tools) {
            throw new \Exception("Certificado digital não configurado.");
        }

        try {
            // 1. Gerar XML
            $builder = new NFwBuilder();
            $xml = $builder->buildNFCe($venda, json_decode($this->config, true));

            // 2. Assinar XML
            $xmlAssinado = $this->tools->signNFe($xml);

            // 3. Validar XML (Opcional mas recomendado)
            // $this->tools->model('65');

            // 4. Enviar para SEFAZ
            $idLote = str_pad(rand(1, 999999999), 15, '0', STR_PAD_LEFT);
            $resp = $this->tools->sefazEnviaLote([$xmlAssinado], $idLote);

            $st = new Standardize();
            $std = $st->toStd($resp);

            if ($std->cStat != '103') { // Lote recebido
                throw new \Exception("Erro ao enviar lote: " . ($std->xMotivo ?? 'Erro desconhecido'));
            }

            $recibo = $std->infRec->nRec;

            // 5. Consultar Recibo (Emissão síncrona/assíncrona)
            // Para simplificar, vamos tentar consultar imediatamente
            sleep(2); // Pequena pausa para processamento SEFAZ
            $protocolo = $this->tools->sefazConsultaRecibo($recibo);
            $stdProt = $st->toStd($protocolo);

            if ($stdProt->cStat == '104') { // Lote processado
                $infProt = $stdProt->protNFe->infProt;

                if ($infProt->cStat == '100') { // Autorizado
                    // 6. Protocolar XML
                    $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $protocolo);

                    // 7. Salvar no Banco
                    $cupom = new CupomFiscal();
                    $cupom->venda_id = $venda->id;
                    $cupom->usuario_id = $venda->usuario_id;
                    $cupom->numero = (int)$infProt->chNFe; // Simplificado, ideal é pegar do XML
                    $cupom->chave_acesso = (string)$infProt->chNFe;
                    $cupom->status = CupomFiscal::STATUS_AUTORIZADA;
                    $cupom->data_emissao = date('Y-m-d H:i:s');
                    $cupom->mensagem_retorno = (string)$infProt->xMotivo;

                    // Salvar XML em disco
                    $path = 'uploads/fiscal/' . $venda->usuario_id . '/' . date('Y-m');
                    if (!is_dir($path)) mkdir($path, 0755, true);
                    $fileName = $cupom->chave_acesso . '.xml';
                    file_put_contents($path . '/' . $fileName, $xmlProtocolado);
                    $cupom->xml_path = $path . '/' . $fileName;

                    if ($cupom->save()) {
                        return [
                            'success' => true,
                            'message' => 'NFCe autorizada com sucesso!',
                            'cupom_id' => $cupom->id,
                        ];
                    }
                }
            }

            return [
                'success' => false,
                'message' => 'Lote processado, mas não autorizado. Verifique os logs.',
                'std' => $stdProt
            ];
        } catch (\Exception $e) {
            Yii::error("Erro na emissão fiscal: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Gera o DANFE em PDF
     */
    public function gerarDanfe($xmlContent, $modelo = '65')
    {
        if ($modelo == '65') {
            $danfe = new Danfce($xmlContent);
        } else {
            $danfe = new Danfe($xmlContent);
        }

        $danfe->monta();
        return $danfe->render();
    }
}
