<?php

namespace app\components\nfe;

use Yii;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Complements;

/**
 * NFeService - Serviço de comunicação com SEFAZ
 * 
 * Responsável por transmitir, consultar, cancelar NFe/NFCe
 */
class NFeService
{
    /**
     * @var Tools
     */
    private $tools;

    /**
     * @var array Configuração NFe
     */
    private $config;

    /**
     * @var Standardize
     */
    private $standardize;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->config = Yii::$app->params['nfe'];
        $this->standardize = new Standardize();

        try {
            // Carregar certificado
            $pfxContent = file_get_contents($this->config['certificado']['path']);
            $certificate = Certificate::readPfx(
                $pfxContent,
                $this->config['certificado']['senha']
            );

            // Configurar Tools
            $configJson = $this->getConfigJson();
            $this->tools = new Tools($configJson, $certificate);
            $this->tools->model('65'); // Modelo padrão NFCe

        } catch (\Exception $e) {
            Yii::error("Erro ao inicializar NFeService: " . $e->getMessage(), __METHOD__);
            throw new \Exception("Erro ao carregar certificado digital: " . $e->getMessage());
        }
    }

    /**
     * Transmite NFe/NFCe para SEFAZ
     * 
     * @param string $xml XML da nota
     * @param string $modelo '55' ou '65'
     * @return array Resultado da transmissão
     */
    public function transmitir(string $xml, string $modelo = '65'): array
    {
        try {
            // Definir modelo
            $this->tools->model($modelo);

            // Assinar XML
            $xmlAssinado = $this->tools->signNFe($xml);

            Yii::info("XML assinado com sucesso", __METHOD__);

            // Transmitir para SEFAZ
            $response = $this->tools->sefazEnvia($xmlAssinado);

            Yii::info("Resposta SEFAZ recebida", __METHOD__);

            // Processar retorno
            return $this->processarRetornoTransmissao($response, $xmlAssinado);
        } catch (\Exception $e) {
            Yii::error("Erro na transmissão: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'mensagem' => 'Erro na transmissão: ' . $e->getMessage(),
                'codigo' => '999',
            ];
        }
    }

    /**
     * Consulta NFe/NFCe na SEFAZ
     * 
     * @param string $chave Chave de acesso da nota
     * @param string $modelo '55' ou '65'
     * @return array Resultado da consulta
     */
    public function consultar(string $chave, string $modelo = '65'): array
    {
        try {
            $this->tools->model($modelo);

            $response = $this->tools->sefazConsultaChave($chave);

            return $this->processarRetornoConsulta($response);
        } catch (\Exception $e) {
            Yii::error("Erro na consulta: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'mensagem' => 'Erro na consulta: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancela NFe/NFCe
     * 
     * @param string $chave Chave de acesso
     * @param string $protocolo Protocolo de autorização
     * @param string $justificativa Justificativa do cancelamento (mín 15 caracteres)
     * @param string $modelo '55' ou '65'
     * @return array Resultado do cancelamento
     */
    public function cancelar(string $chave, string $protocolo, string $justificativa, string $modelo = '65'): array
    {
        try {
            // Validar justificativa
            if (strlen($justificativa) < 15) {
                throw new \Exception("Justificativa deve ter no mínimo 15 caracteres");
            }

            $this->tools->model($modelo);

            $response = $this->tools->sefazCancela($chave, $justificativa, $protocolo);

            return $this->processarRetornoCancelamento($response);
        } catch (\Exception $e) {
            Yii::error("Erro no cancelamento: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'mensagem' => 'Erro no cancelamento: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Inutiliza numeração de NFe/NFCe
     * 
     * @param int $serie Série da nota
     * @param int $numeroInicial Número inicial
     * @param int $numeroFinal Número final
     * @param string $justificativa Justificativa
     * @param string $modelo '55' ou '65'
     * @return array Resultado da inutilização
     */
    public function inutilizar(int $serie, int $numeroInicial, int $numeroFinal, string $justificativa, string $modelo = '65'): array
    {
        try {
            if (strlen($justificativa) < 15) {
                throw new \Exception("Justificativa deve ter no mínimo 15 caracteres");
            }

            $this->tools->model($modelo);

            $response = $this->tools->sefazInutiliza(
                $serie,
                $numeroInicial,
                $numeroFinal,
                $justificativa
            );

            return $this->processarRetornoInutilizacao($response);
        } catch (\Exception $e) {
            Yii::error("Erro na inutilização: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'mensagem' => 'Erro na inutilização: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Consulta status do serviço SEFAZ
     * 
     * @param string $modelo '55' ou '65'
     * @return array Status do serviço
     */
    public function consultarStatus(string $modelo = '65'): array
    {
        try {
            $this->tools->model($modelo);

            $response = $this->tools->sefazStatus();

            $std = $this->standardize->toStd($response);

            if ($std->cStat == 107) {
                return [
                    'success' => true,
                    'mensagem' => 'Serviço em operação',
                    'codigo' => '107',
                ];
            }

            return [
                'success' => false,
                'mensagem' => $std->xMotivo ?? 'Serviço indisponível',
                'codigo' => $std->cStat ?? '999',
            ];
        } catch (\Exception $e) {
            Yii::error("Erro ao consultar status: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'mensagem' => 'Erro ao consultar status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Processa retorno da transmissão
     */
    protected function processarRetornoTransmissao(string $response, string $xmlAssinado): array
    {
        $std = $this->standardize->toStd($response);

        // Código 100 = Autorizada
        // Código 103 = Lote recebido com sucesso (aguardar processamento)
        if (isset($std->cStat) && in_array($std->cStat, ['100', '103'])) {

            // Se for 103, precisa consultar o recibo
            if ($std->cStat == '103') {
                $recibo = $std->infRec->nRec ?? null;
                if ($recibo) {
                    return $this->consultarRecibo($recibo, $xmlAssinado);
                }
            }

            // Se for 100, já está autorizada
            $chave = $std->protNFe->infProt->chNFe ?? null;
            $protocolo = $std->protNFe->infProt->nProt ?? null;

            // Adicionar protocolo ao XML
            $xmlAutorizado = $xmlAssinado;
            if ($protocolo) {
                try {
                    $xmlAutorizado = Complements::toAuthorize($xmlAssinado, $response);
                } catch (\Exception $e) {
                    Yii::warning("Erro ao adicionar protocolo ao XML: " . $e->getMessage(), __METHOD__);
                }
            }

            return [
                'success' => true,
                'chave' => $chave,
                'protocolo' => $protocolo,
                'xml_assinado' => $xmlAssinado,
                'xml_autorizado' => $xmlAutorizado,
                'mensagem' => $std->protNFe->infProt->xMotivo ?? 'Autorizada',
                'codigo' => '100',
                'data_autorizacao' => $std->protNFe->infProt->dhRecbto ?? date('Y-m-d\TH:i:sP'),
            ];
        }

        // Rejeitada ou erro
        return [
            'success' => false,
            'mensagem' => $std->xMotivo ?? 'Erro desconhecido',
            'codigo' => $std->cStat ?? '999',
            'xml_assinado' => $xmlAssinado,
        ];
    }

    /**
     * Consulta recibo de lote
     */
    protected function consultarRecibo(string $recibo, string $xmlAssinado): array
    {
        try {
            // Aguardar processamento (recomendado: 2-5 segundos)
            sleep(3);

            $response = $this->tools->sefazConsultaRecibo($recibo);

            return $this->processarRetornoTransmissao($response, $xmlAssinado);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'mensagem' => 'Erro ao consultar recibo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Processa retorno da consulta
     */
    protected function processarRetornoConsulta(string $response): array
    {
        $std = $this->standardize->toStd($response);

        if ($std->cStat == 100) {
            return [
                'success' => true,
                'chave' => $std->protNFe->infProt->chNFe ?? null,
                'protocolo' => $std->protNFe->infProt->nProt ?? null,
                'mensagem' => 'Nota autorizada',
                'situacao' => 'AUTORIZADA',
            ];
        }

        if ($std->cStat == 101) {
            return [
                'success' => true,
                'mensagem' => 'Nota cancelada',
                'situacao' => 'CANCELADA',
            ];
        }

        return [
            'success' => false,
            'mensagem' => $std->xMotivo ?? 'Nota não encontrada',
            'codigo' => $std->cStat ?? '999',
        ];
    }

    /**
     * Processa retorno do cancelamento
     */
    protected function processarRetornoCancelamento(string $response): array
    {
        $std = $this->standardize->toStd($response);

        // Código 135 = Cancelamento homologado
        if ($std->cStat == 135) {
            return [
                'success' => true,
                'protocolo' => $std->infCanc->nProt ?? null,
                'mensagem' => 'Cancelamento autorizado',
                'data_cancelamento' => $std->infCanc->dhRecbto ?? date('Y-m-d\TH:i:sP'),
            ];
        }

        return [
            'success' => false,
            'mensagem' => $std->xMotivo ?? 'Erro ao cancelar',
            'codigo' => $std->cStat ?? '999',
        ];
    }

    /**
     * Processa retorno da inutilização
     */
    protected function processarRetornoInutilizacao(string $response): array
    {
        $std = $this->standardize->toStd($response);

        // Código 102 = Inutilização homologada
        if ($std->cStat == 102) {
            return [
                'success' => true,
                'protocolo' => $std->infInut->nProt ?? null,
                'mensagem' => 'Inutilização autorizada',
            ];
        }

        return [
            'success' => false,
            'mensagem' => $std->xMotivo ?? 'Erro ao inutilizar',
            'codigo' => $std->cStat ?? '999',
        ];
    }

    /**
     * Gera configuração JSON para NFePHP
     */
    protected function getConfigJson(): string
    {
        $emitente = $this->config['emitente'];

        $config = [
            'atualizacao' => date('Y-m-d H:i:s'),
            'tpAmb' => $this->config['ambiente'] === 'producao' ? 1 : 2,
            'razaosocial' => $emitente['razao_social'],
            'siglaUF' => $emitente['endereco']['uf'],
            'cnpj' => $emitente['cnpj'],
            'schemes' => 'PL_009_V4',
            'versao' => '4.00',
            'tokenIBPT' => '',
            'CSC' => $this->config['nfce']['token'] ?? '',
            'CSCid' => $this->config['nfce']['id_token'] ?? '',
            'aProxyConf' => [
                'proxyIp' => '',
                'proxyPort' => '',
                'proxyUser' => '',
                'proxyPass' => '',
            ],
        ];

        return json_encode($config);
    }
}
