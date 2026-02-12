<?php

namespace app\components\nfe;

use Yii;
use app\modules\vendas\models\Venda;
use NFePHP\NFe\Make;
use NFePHP\Common\Standardize;

/**
 * NFeBuilder - Construtor de XML para NFe/NFCe
 * 
 * Responsável por gerar o XML da Nota Fiscal a partir de uma Venda
 */
class NFeBuilder
{
    /**
     * Constrói XML da NFe/NFCe a partir de uma Venda
     * 
     * @param Venda $venda Venda a ser convertida em NFe
     * @param string $modelo '55' para NFe, '65' para NFCe
     * @return string XML gerado
     * @throws \Exception
     */
    public static function buildFromVenda(Venda $venda, string $modelo = '65'): string
    {
        $config = Yii::$app->params['nfe'];
        $make = new Make();

        try {
            // 1. Identificação da Nota
            $identificacao = self::buildIdentificacao($venda, $modelo);
            $make->taginfNFe($identificacao);

            // 2. Emitente
            $emitente = self::buildEmitente();
            $make->tagemit($emitente);

            // 3. Destinatário (se houver cliente)
            if ($venda->cliente_id && $venda->cliente) {
                $destinatario = self::buildDestinatario($venda);
                $make->tagdest($destinatario);
            }

            // 4. Produtos/Itens
            foreach ($venda->itens as $index => $item) {
                $produto = self::buildProduto($item, $index + 1);
                $make->tagprod($produto);

                // Impostos do produto
                $imposto = self::buildImposto($item, $modelo);
                $make->tagimposto($imposto);
            }

            // 5. Totais
            $totais = self::buildTotais($venda);
            $make->tagICMSTot($totais);

            // 6. Transporte
            $transporte = self::buildTransporte($venda);
            $make->tagtransp($transporte);

            // 7. Pagamento
            $pagamentos = self::buildPagamento($venda);
            foreach ($pagamentos as $pag) {
                $make->tagpag($pag);
            }

            // 8. Informações Adicionais
            $infAdic = self::buildInformacoesAdicionais($venda);
            $make->taginfAdic($infAdic);

            // 9. Gerar XML
            $xml = $make->getXML();

            return $xml;
        } catch (\Exception $e) {
            Yii::error("Erro ao gerar XML NFe: " . $e->getMessage(), __METHOD__);
            throw new \Exception("Erro ao gerar XML: " . $e->getMessage());
        }
    }

    /**
     * Constrói tag de identificação da NFe
     */
    protected static function buildIdentificacao(Venda $venda, string $modelo): array
    {
        $config = Yii::$app->params['nfe'];
        $emitente = $config['emitente'];

        // Obter próximo número da nota
        $numero = self::getProximoNumero($modelo);
        $serie = $modelo === '65' ? $config['nfce']['serie'] : $config['nfe']['serie'];

        return [
            'cUF' => self::getCodigoUF($emitente['endereco']['uf']),
            'cNF' => str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT), // Código numérico aleatório
            'natOp' => 'VENDA', // Natureza da operação
            'mod' => $modelo, // 55=NFe, 65=NFCe
            'serie' => $serie,
            'nNF' => $numero,
            'dhEmi' => date('Y-m-d\TH:i:sP'), // Data/hora de emissão
            'dhSaiEnt' => date('Y-m-d\TH:i:sP'), // Data/hora de saída
            'tpNF' => '1', // 0=Entrada, 1=Saída
            'idDest' => '1', // 1=Operação interna, 2=Interestadual, 3=Exterior
            'cMunFG' => $emitente['endereco']['codigo_municipio'],
            'tpImp' => $modelo === '65' ? '4' : '1', // 1=Retrato, 4=NFCe
            'tpEmis' => '1', // 1=Normal
            'tpAmb' => $config['ambiente'] === 'producao' ? '1' : '2', // 1=Produção, 2=Homologação
            'finNFe' => '1', // 1=Normal, 2=Complementar, 3=Ajuste, 4=Devolução
            'indFinal' => '1', // 0=Normal, 1=Consumidor final
            'indPres' => '1', // 1=Presencial
            'procEmi' => '0', // 0=Aplicativo do contribuinte
            'verProc' => '1.0.0', // Versão do aplicativo
        ];
    }

    /**
     * Constrói tag do emitente
     */
    protected static function buildEmitente(): array
    {
        $config = Yii::$app->params['nfe']['emitente'];

        return [
            'xNome' => $config['razao_social'],
            'xFant' => $config['nome_fantasia'],
            'IE' => $config['ie'],
            'CNPJ' => $config['cnpj'],
            'CRT' => $config['crt'], // Código de Regime Tributário
            'enderEmit' => [
                'xLgr' => $config['endereco']['logradouro'],
                'nro' => $config['endereco']['numero'],
                'xCpl' => $config['endereco']['complemento'] ?? '',
                'xBairro' => $config['endereco']['bairro'],
                'cMun' => $config['endereco']['codigo_municipio'],
                'xMun' => $config['endereco']['municipio'],
                'UF' => $config['endereco']['uf'],
                'CEP' => preg_replace('/[^0-9]/', '', $config['endereco']['cep']),
                'cPais' => '1058', // Brasil
                'xPais' => 'BRASIL',
                'fone' => preg_replace('/[^0-9]/', '', $config['endereco']['telefone']),
            ],
        ];
    }

    /**
     * Constrói tag do destinatário
     */
    protected static function buildDestinatario(Venda $venda): array
    {
        $cliente = $venda->cliente;

        // Limpar CPF/CNPJ
        $cpfCnpj = preg_replace('/[^0-9]/', '', $cliente->cpf_cnpj);

        $dest = [
            'xNome' => $cliente->nome,
            'indIEDest' => '9', // 9=Não contribuinte
        ];

        // CPF ou CNPJ
        if (strlen($cpfCnpj) === 11) {
            $dest['CPF'] = $cpfCnpj;
        } else {
            $dest['CNPJ'] = $cpfCnpj;
        }

        // Endereço (se houver)
        if ($cliente->endereco) {
            $dest['enderDest'] = [
                'xLgr' => $cliente->endereco ?? 'Não informado',
                'nro' => $cliente->numero ?? 'S/N',
                'xBairro' => $cliente->bairro ?? 'Não informado',
                'cMun' => $cliente->codigo_municipio ?? '2611606', // Recife padrão
                'xMun' => $cliente->cidade ?? 'Recife',
                'UF' => $cliente->uf ?? 'PE',
                'CEP' => preg_replace('/[^0-9]/', '', $cliente->cep ?? '00000000'),
                'cPais' => '1058',
                'xPais' => 'BRASIL',
            ];
        }

        return $dest;
    }

    /**
     * Constrói tag de produto
     */
    protected static function buildProduto($item, int $numero): array
    {
        $produto = $item->produto;

        return [
            'nItem' => $numero,
            'cProd' => $produto->id, // Código do produto
            'cEAN' => $produto->codigo_barras ?? 'SEM GTIN',
            'xProd' => $produto->nome,
            'NCM' => $produto->ncm ?? '00000000', // NCM do produto
            'CFOP' => '5102', // CFOP padrão - ajustar conforme necessário
            'uCom' => $produto->unidade ?? 'UN',
            'qCom' => $item->quantidade,
            'vUnCom' => number_format($item->valor_unitario, 2, '.', ''),
            'vProd' => number_format($item->valor_total, 2, '.', ''),
            'cEANTrib' => $produto->codigo_barras ?? 'SEM GTIN',
            'uTrib' => $produto->unidade ?? 'UN',
            'qTrib' => $item->quantidade,
            'vUnTrib' => number_format($item->valor_unitario, 2, '.', ''),
            'indTot' => '1', // 1=Compõe total da NFe
        ];
    }

    /**
     * Constrói tag de imposto
     */
    protected static function buildImposto($item, string $modelo): array
    {
        $config = Yii::$app->params['nfe']['emitente'];

        // Simples Nacional - CSOSN 102 (sem permissão de crédito)
        $imposto = [
            'nItem' => $item->numero ?? 1,
            'vTotTrib' => '0.00', // Valor aproximado dos tributos
            'ICMS' => [
                'CSOSN' => '102', // Tributada pelo Simples Nacional sem permissão de crédito
                'orig' => '0', // 0=Nacional
            ],
            'PIS' => [
                'CST' => '99', // Outras operações
                'vBC' => '0.00',
                'pPIS' => '0.00',
                'vPIS' => '0.00',
            ],
            'COFINS' => [
                'CST' => '99', // Outras operações
                'vBC' => '0.00',
                'pCOFINS' => '0.00',
                'vCOFINS' => '0.00',
            ],
        ];

        return $imposto;
    }

    /**
     * Constrói tag de totais
     */
    protected static function buildTotais(Venda $venda): array
    {
        return [
            'vBC' => '0.00', // Base de cálculo ICMS
            'vICMS' => '0.00', // Valor ICMS
            'vICMSDeson' => '0.00', // Valor ICMS desonerado
            'vFCP' => '0.00', // Valor FCP
            'vBCST' => '0.00', // Base ST
            'vST' => '0.00', // Valor ST
            'vFCPST' => '0.00', // Valor FCP ST
            'vFCPSTRet' => '0.00', // Valor FCP retido ST
            'vProd' => number_format($venda->valor_total, 2, '.', ''),
            'vFrete' => '0.00',
            'vSeg' => '0.00',
            'vDesc' => number_format($venda->desconto ?? 0, 2, '.', ''),
            'vII' => '0.00',
            'vIPI' => '0.00',
            'vIPIDevol' => '0.00',
            'vPIS' => '0.00',
            'vCOFINS' => '0.00',
            'vOutro' => '0.00',
            'vNF' => number_format($venda->valor_total - ($venda->desconto ?? 0), 2, '.', ''),
            'vTotTrib' => '0.00',
        ];
    }

    /**
     * Constrói tag de transporte
     */
    protected static function buildTransporte(Venda $venda): array
    {
        return [
            'modFrete' => '9', // 9=Sem frete
        ];
    }

    /**
     * Constrói tag de pagamento
     */
    protected static function buildPagamento(Venda $venda): array
    {
        $pagamentos = [];

        // Pagamento à vista
        $pagamentos[] = [
            'tPag' => '01', // 01=Dinheiro, 03=Cartão Crédito, 04=Cartão Débito
            'vPag' => number_format($venda->valor_total - ($venda->desconto ?? 0), 2, '.', ''),
        ];

        return $pagamentos;
    }

    /**
     * Constrói informações adicionais
     */
    protected static function buildInformacoesAdicionais(Venda $venda): array
    {
        $info = "Venda ID: {$venda->id}";

        if ($venda->observacoes) {
            $info .= " | " . $venda->observacoes;
        }

        return [
            'infCpl' => $info,
        ];
    }

    /**
     * Obtém próximo número da nota
     */
    protected static function getProximoNumero(string $modelo): int
    {
        $ultimaNota = \app\modules\vendas\models\CupomFiscal::find()
            ->where(['modelo' => $modelo])
            ->orderBy(['numero' => SORT_DESC])
            ->one();

        return $ultimaNota ? ($ultimaNota->numero + 1) : 1;
    }

    /**
     * Obtém código da UF
     */
    protected static function getCodigoUF(string $uf): string
    {
        $ufs = [
            'AC' => '12',
            'AL' => '27',
            'AP' => '16',
            'AM' => '13',
            'BA' => '29',
            'CE' => '23',
            'DF' => '53',
            'ES' => '32',
            'GO' => '52',
            'MA' => '21',
            'MT' => '51',
            'MS' => '50',
            'MG' => '31',
            'PA' => '15',
            'PB' => '25',
            'PR' => '41',
            'PE' => '26',
            'PI' => '22',
            'RJ' => '33',
            'RN' => '24',
            'RS' => '43',
            'RO' => '11',
            'RR' => '14',
            'SC' => '42',
            'SP' => '35',
            'SE' => '28',
            'TO' => '17',
        ];

        return $ufs[$uf] ?? '26'; // PE por padrão
    }
}
