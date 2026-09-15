<?php

namespace app\components\fiscal;

use Yii;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Configuracao;
use NFePHP\NFe\Make;

/**
 * Construtor de XML para NF-e Modelo 55 (Nota Fiscal Eletrônica Mercantil)
 * Especializado para empresas optantes pelo Simples Nacional e MEI.
 *
 * Compatível com NFePHP >= 5.x que exige stdClass em todas as chamadas tag*()
 */
class NFe55Builder
{
    /**
     * Helper: converte array associativo em stdClass recursivamente.
     * NFePHP 5.x requer stdClass em todas as chamadas tag*().
     */
    private static function std(array $data): \stdClass
    {
        $obj = new \stdClass();
        foreach ($data as $k => $v) {
            $obj->$k = $v;
        }
        return $obj;
    }

    /**
     * Gera o XML da NF-e Modelo 55 para uma Venda
     *
     * @param Venda $venda Objeto da venda
     * @param Configuracao $config Configurações fiscais do tenant/vendedor
     * @param int $numeroNF Número sequencial da NF-e a emitir
     * @param int $serie Série da nota fiscal
     * @return string XML gerado não assinado
     * @throws \Exception Em caso de inconsistência cadastral ou tributária
     */
    public static function build(Venda $venda, Configuracao $config, int $numeroNF, int $serie = 1): string
    {
        $make = new Make();

        // Dados do Emitente
        $ufEmit = strtoupper(trim($config->uf_sigla ?: ($config->usuario->estado ?? 'SP')));
        $cUFEmit = IbgeHelper::getUfCode($ufEmit);
        $cMunFGEmit = $config->ibge_municipio ?: '3550308'; // Default SP se não informado

        // Dados do Destinatário
        $cliente = $venda->cliente;
        // Atributo real é endereco_estado no modelo prest_clientes
        $ufDest = $cliente ? strtoupper(trim($cliente->endereco_estado ?: $ufEmit)) : $ufEmit;
        $isInterestadual = ($ufEmit !== $ufDest);
        $idDest = $isInterestadual ? 2 : 1;

        // CFOP padrão para revenda de mercadorias no Simples Nacional / MEI:
        // 5.102 (Operação interna) / 6.102 (Operação interestadual)
        $cfopPadrao = $isInterestadual ? '6102' : '5102';

        $ambiente = (int)($config->nfe_ambiente ?: 2); // 1 = Produção, 2 = Homologação

        // --- 1. Tag infNFe (Versão 4.00) ---
        $std = self::std(['versao' => '4.00', 'Id' => null]);
        $make->taginfNFe($std);

        // --- 2. Tag ide (Identificação da NF-e) ---
        $dhEmi = date('Y-m-d\TH:i:sP');
        $make->tagide(self::std([
            'cUF'     => $cUFEmit,
            'cNF'     => str_pad((string)rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'natOp'   => $isInterestadual ? 'VENDA DE MERCADORIA INTERESTADUAL' : 'VENDA DE MERCADORIA',
            'mod'     => 55,
            'serie'   => $serie,
            'nNF'     => $numeroNF,
            'dhEmi'   => $dhEmi,
            'dhSaiEnt' => $dhEmi,
            'tpNF'    => 1,  // 1 = Saída
            'idDest'  => $idDest,
            'cMunFG'  => $cMunFGEmit,
            'tpImp'   => 1,  // 1 = Retrato
            'tpEmis'  => 1,  // 1 = Normal
            'tpAmb'   => $ambiente,
            'finNFe'  => 1,  // 1 = Normal
            'indFinal' => 1, // 1 = Consumidor final
            'indPres'  => 2, // 2 = Não presencial, pela Internet
            'procEmi'  => 0,
            'verProc'  => 'PULSE_ERP_1.0',
        ]));

        // --- 3. Tag emit (Emitente) ---
        $cnpjLimpo = IbgeHelper::sanitizeDoc($config->cnpj);
        if (empty($cnpjLimpo)) {
            throw new \Exception("CNPJ da loja não configurado nas configurações fiscais.");
        }

        $razaoSocial = $config->razao_social ?: ($config->nome_loja ?: 'EMPRESA MEI');
        $crt = (int)($config->crt ?: 1); // 1 = Simples Nacional / MEI

        $make->tagemit(self::std([
            'CNPJ'  => $cnpjLimpo,
            'xNome' => $razaoSocial,
            'xFant' => $config->nome_loja ?: $razaoSocial,
            'IE'    => !empty($config->ie) ? IbgeHelper::sanitizeDoc($config->ie) : 'ISENTO',
            'CRT'   => $crt,
        ]));

        // Endereço do Emitente
        $endLoja    = $config->endereco_completo ?: ($config->usuario->endereco ?? 'RUA PRINCIPAL, 100');
        $cidadeLoja = $config->usuario->cidade ?? 'SAO PAULO';
        $bairroLoja = $config->usuario->bairro ?? 'CENTRO';

        $make->tagenderEmit(self::std([
            'xLgr'   => substr($endLoja, 0, 60),
            'nro'    => 'S/N',
            'xBairro' => substr($bairroLoja, 0, 60),
            'cMun'   => $cMunFGEmit,
            'xMun'   => strtoupper($cidadeLoja),
            'UF'     => $ufEmit,
            'CEP'    => IbgeHelper::sanitizeCep($config->usuario->cep ?? '01001000'),
            'cPais'  => '1058',
            'xPais'  => 'BRASIL',
        ]));

        // --- 4. Tag dest (Destinatário) ---
        if ($cliente) {
            // O modelo prest_clientes usa coluna 'cpf' (11 dígitos)
            $docDest  = IbgeHelper::sanitizeDoc($cliente->cpf ?? ($cliente->cpf_cnpj ?? ''));
            $nomeDest = !empty($cliente->nome_completo) ? $cliente->nome_completo : 'CLIENTE MERCADO LIVRE';

            // Em homologação a SEFAZ exige razão social específica
            if ($ambiente === 2) {
                $nomeDest = 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';
            }

            $tagDestData = [
                'xNome'     => substr($nomeDest, 0, 60),
                'indIEDest' => 9, // Não Contribuinte
            ];

            if (!empty($docDest) && strlen($docDest) === 14) {
                $tagDestData['CNPJ'] = $docDest;
            } elseif (!empty($docDest)) {
                $tagDestData['CPF'] = $docDest;
            }

            if (!empty($cliente->email)) {
                $tagDestData['email'] = $cliente->email;
            }

            $make->tagdest(self::std($tagDestData));

            // Tag enderDest (Obrigatória na NF-e Modelo 55)
            // Atributos reais do modelo Cliente (prest_clientes):
            // endereco_logradouro, endereco_bairro, endereco_cidade, endereco_estado, endereco_cep
            $endDest    = $cliente->endereco_logradouro ?: 'RUA PRINCIPAL';
            $cidadeDest = $cliente->endereco_cidade ?: $cidadeLoja;
            $bairroDest = $cliente->endereco_bairro ?: 'CENTRO';
            $cepDest    = IbgeHelper::sanitizeCep($cliente->endereco_cep ?? '01001000');
            $nroDest    = $cliente->endereco_numero ?: 'S/N';
            $cMunDest   = $cMunFGEmit; // Usar cMun do emitente como fallback (SEFAZ valida, não bloqueia)

            $make->tagenderDest(self::std([
                'xLgr'   => substr($endDest, 0, 60),
                'nro'    => $nroDest,
                'xBairro' => substr($bairroDest, 0, 60),
                'cMun'   => $cMunDest,
                'xMun'   => strtoupper($cidadeDest),
                'UF'     => $ufDest,
                'CEP'    => $cepDest,
                'cPais'  => '1058',
                'xPais'  => 'BRASIL',
                'fone'   => IbgeHelper::sanitizeDoc($cliente->telefone ?? ''),
            ]));
        } else {
            throw new \Exception("Destinatário é obrigatório para emissão de NF-e Modelo 55 no e-commerce.");
        }

        // --- 5. Itens e Produtos ---
        $nItem         = 1;
        $totalProdutos = 0.0;

        foreach ($venda->itens as $item) {
            $quantidade    = (float)$item->quantidade;
            $precoUnitario = (float)($item->preco_unitario_venda ?? ($item->preco_unitario ?? 0));
            $valorTotalItem = (float)($item->valor_total_item ?? ($quantidade * $precoUnitario));
            $nomeProduto   = !empty($item->nome_item_manual)
                ? $item->nome_item_manual
                : ($item->produto->nome ?? 'PRODUTO');

            // Validação de NCM
            $ncm = preg_replace('/\D/', '', (string)($item->produto->ncm ?? ''));
            if (empty($ncm) || strlen($ncm) < 8) {
                $ncm = '39269090'; // Fallback seguro (outras obras de plástico/material diverso)
            }

            $make->tagprod(self::std([
                'item'    => $nItem,
                'cProd'   => substr((string)($item->produto_id ?: "ITEM_{$nItem}"), 0, 60),
                'cEAN'    => 'SEM GTIN',
                'xProd'   => substr($nomeProduto, 0, 120),
                'NCM'     => substr($ncm, 0, 8),
                'CFOP'    => $cfopPadrao,
                'uCom'    => 'UN',
                'qCom'    => number_format($quantidade, 4, '.', ''),
                'vUnCom'  => number_format($precoUnitario, 4, '.', ''),
                'vProd'   => number_format($valorTotalItem, 2, '.', ''),
                'cEANTrib' => 'SEM GTIN',
                'uTrib'   => 'UN',
                'qTrib'   => number_format($quantidade, 4, '.', ''),
                'vUnTrib' => number_format($precoUnitario, 4, '.', ''),
                'indTot'  => 1,
            ]));

            // Impostos: MEI/Simples Nacional — CSOSN 102 (sem crédito)
            $make->tagimposto(self::std(['item' => $nItem]));

            // tagICMSSN é o método unificado para Simples Nacional (NFePHP 5.x)
            $make->tagICMSSN(self::std([
                'item'  => $nItem,
                'orig'  => 0,     // 0 = Nacional
                'CSOSN' => '102', // Tributada pelo Simples Nacional sem permissão de crédito
            ]));

            // PIS não tributado (NT) para MEI — CST 07 = Operação Isenta
            $make->tagPIS(self::std([
                'item' => $nItem,
                'CST'  => '07',
            ]));

            // COFINS não tributado (NT) para MEI — CST 07 = Operação Isenta
            $make->tagCOFINS(self::std([
                'item' => $nItem,
                'CST'  => '07',
            ]));
            $totalProdutos += $valorTotalItem;
            $nItem++;
        }

        $valorFrete   = (float)($venda->valor_frete ?? 0);
        $valorDesconto = (float)($venda->valor_desconto ?? 0);
        $valorTotalNF = max(0.01, round($totalProdutos + $valorFrete - $valorDesconto, 2));

        // --- 6. Totais da NF-e (ICMSTot) ---
        $make->tagICMSTot(self::std([
            'vBC'         => '0.00',
            'vICMS'       => '0.00',
            'vICMSDeson'  => '0.00',
            'vFCPUFDest'  => '0.00',
            'vICMSUFDest' => '0.00',
            'vICMSUFRemet' => '0.00',
            'vFCP'        => '0.00',
            'vBCST'       => '0.00',
            'vST'         => '0.00',
            'vFCPST'      => '0.00',
            'vFCPSTRet'   => '0.00',
            'vProd'       => number_format($totalProdutos, 2, '.', ''),
            'vFrete'      => number_format($valorFrete, 2, '.', ''),
            'vSeg'        => '0.00',
            'vDesc'       => number_format($valorDesconto, 2, '.', ''),
            'vII'         => '0.00',
            'vIPI'        => '0.00',
            'vIPIDevol'   => '0.00',
            'vPIS'        => '0.00',
            'vCOFINS'     => '0.00',
            'vOutro'      => '0.00',
            'vNF'         => number_format($valorTotalNF, 2, '.', ''),
            'vTotTrib'    => '0.00',
        ]));

        // --- 7. Transporte ---
        $modFrete = ($valorFrete > 0) ? 0 : 9; // 0 = CIF (Remetente), 9 = Sem frete
        $make->tagtransp(self::std(['modFrete' => $modFrete]));

        // --- 8. Pagamento ---
        $make->tagpag(self::std(['vTroco' => '0.00']));
        $make->tagdetPag(self::std([
            'indPag' => 0,   // 0 = À vista
            'tPag'   => '99', // 99 = Outros (Marketplace/Plataforma Online)
            'vPag'   => number_format($valorTotalNF, 2, '.', ''),
        ]));

        // --- 9. Informações Adicionais ---
        $infComplementar = "Documento emitido por ME ou EPP optante pelo Simples Nacional. "
            . "Nao gera direito a credito fiscal de IPI e ICMS. "
            . "Venda realizada via Marketplace.";
        if (!empty($venda->observacoes)) {
            $infComplementar .= " Obs: " . substr(strip_tags($venda->observacoes), 0, 200);
        }

        $make->taginfAdic(self::std(['infCpl' => $infComplementar]));

        // --- 10. Responsável Técnico (Only Code) ---
        $make->taginfRespTec(self::std([
            'CNPJ'      => '47037952000143',
            'xContato'  => 'Suporte Only Code Pulse ERP',
            'email'     => 'suporte@oncode.app.br',
            'fone'      => '81999999999',
        ]));

        // --- Gera XML final ---
        $xml = $make->getXML();
        if (empty($xml)) {
            $errors = $make->getErrors();
            throw new \Exception("Erro ao construir XML da NF-e 55: " . implode('; ', $errors));
        }

        return $xml;
    }
}
