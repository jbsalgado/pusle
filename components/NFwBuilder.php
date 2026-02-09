<?php

namespace app\components;

use Yii;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use NFePHP\NFe\Make;

/**
 * NFwBuilder - Construtor de XML para NFe/NFCe
 */
class NFwBuilder
{
    protected $make;

    public function __construct()
    {
        $this->make = new Make();
    }

    /**
     * Monta o XML da NFCe a partir de uma Venda
     * 
     * @param Venda $venda
     * @param array $config Configurações do usuário
     * @return string XML gerado
     */
    public function buildNFCe(Venda $venda, $config)
    {
        $this->make->taginfNFe(['versao' => '4.00', 'Id' => null]);

        // Dados da Identificação
        $this->make->tagide([
            'cUF' => 35, // São Paulo - TODO: Dinâmico
            'cNF' => rand(10000000, 99999999),
            'natOp' => 'VENDA',
            'mod' => 65,
            'serie' => 1,
            'nNF' => 1, // TODO: Sequencial do banco
            'dhEmi' => date('Y-m-d\TH:i:sP'),
            'tpNF' => 1,
            'idDest' => 1,
            'cMunFG' => 3550308, // São Paulo - TODO: Dinâmico
            'tpImp' => 4,
            'tpEmis' => 1,
            'tpAmb' => $config['tpAmb'],
            'finNFe' => 1,
            'indFinal' => 1,
            'indPres' => 1,
            'procEmi' => 0,
            'verProc' => '1.0',
        ]);

        // Emitente
        $this->make->tagemit([
            'CNPJ' => $config['cnpj'],
            'xNome' => $config['razaosocial'],
            'IE' => $venda->usuario->configuracao->ie ?? '',
            'CRT' => $venda->usuario->configuracao->crt ?? 1,
        ]);

        // Destinatário (Opcional para NFCe de baixo valor)
        if ($venda->cliente) {
            $this->make->tagdest([
                'CNPJ' => strlen($venda->cliente->cpf_cnpj) > 11 ? $venda->cliente->cpf_cnpj : null,
                'CPF' => strlen($venda->cliente->cpf_cnpj) <= 11 ? $venda->cliente->cpf_cnpj : null,
                'xNome' => $venda->cliente->nome,
                'indIEDest' => 9,
            ]);
        }

        // Itens
        $nItem = 1;
        foreach ($venda->itens as $item) {
            $this->make->tagprod([
                'item' => $nItem,
                'cProd' => $item->produto_id,
                'cEAN' => 'SEM GTIN',
                'xProd' => $item->produto->nome ?? 'PRODUTO',
                'NCM' => '00000000', // TODO: Do produto
                'CFOP' => '5102', // Intraestadual
                'uCom' => 'UN',
                'qCom' => $item->quantidade,
                'vUnCom' => $item->valor_unitario,
                'vProd' => number_format($item->quantidade * $item->valor_unitario, 2, '.', ''),
                'cEANTrib' => 'SEM GTIN',
                'uTrib' => 'UN',
                'qTrib' => $item->quantidade,
                'vUnTrib' => $item->valor_unitario,
                'indTot' => 1,
            ]);

            // Impostos (Simplificado para Simples Nacional)
            $this->make->tagimposto(['item' => $nItem]);
            $this->make->tagICMS(['item' => $nItem]);
            $this->make->tagICMSSN([
                'item' => $nItem,
                'orig' => 0,
                'CSOSN' => '102',
            ]);

            $this->make->tagPIS(['item' => $nItem]);
            $this->make->tagPISNT(['item' => $nItem, 'CST' => '07']);

            $this->make->tagCOFINS(['item' => $nItem]);
            $this->make->tagCOFINSNT(['item' => $nItem, 'CST' => '07']);

            $nItem++;
        }

        // Totais
        $this->make->tagICMSTot([
            'vBC' => 0.00,
            'vICMS' => 0.00,
            'vICMSDeson' => 0.00,
            'vFCP' => 0.00,
            'vBCST' => 0.00,
            'vST' => 0.00,
            'vFCPST' => 0.00,
            'vFCPSTRet' => 0.00,
            'vProd' => number_format($venda->valor_total, 2, '.', ''),
            'vFrete' => 0.00,
            'vSeg' => 0.00,
            'vDesc' => 0.00,
            'vII' => 0.00,
            'vIPI' => 0.00,
            'vIPIDevol' => 0.00,
            'vPIS' => 0.00,
            'vCOFINS' => 0.00,
            'vOutro' => 0.00,
            'vNF' => number_format($venda->valor_total, 2, '.', ''),
        ]);

        // Transmissão
        $this->make->tagtransp(['modFrete' => 9]);

        // Pagamento
        $this->make->tagpag(['vTPag' => number_format($venda->valor_total, 2, '.', '')]);
        $this->make->tagdetPag([
            'tPag' => '01', // Dinheiro por padrão - TODO: Mapear FormaPagamento
            'vPag' => number_format($venda->valor_total, 2, '.', ''),
        ]);

        return $this->make->getXML();
    }
}
