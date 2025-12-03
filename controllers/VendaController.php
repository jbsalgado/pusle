<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Venda; // Seus models
use app\components\NFeService; // Seu componente
use NFePHP\NFe\Make;

class VendaController extends Controller
{
    public function actionEmitir($id)
    {
        $venda = Venda::findOne($id);
        if (!$venda) throw new \yii\web\NotFoundHttpException();

        $nfe = new Make();
        
        // --- 1. Dados Básicos da Nota ---
        $inf = new \stdClass();
        $inf->versao = '4.00';
        $inf->Id = null; // Deixe null, a lib gera
        $inf->pk_nItem = null; 
        $nfe->taginfNFe($inf);

        // Identificação
        $ide = new \stdClass();
        $ide->cUF = 35; // SP
        $ide->cNF = rand(10000000, 99999999);
        $ide->natOp = 'VENDA DE MERCADORIA';
        $ide->mod = 55;
        $ide->serie = 1;
        $ide->nNF = $venda->id; // Número da nota = ID da venda (exemplo)
        $ide->dhEmi = date('Y-m-d\TH:i:sP');
        $ide->tpNF = 1; // Saída
        $ide->idDest = 1; // 1=Interna, 2=Interestadual
        $ide->cMunFG = 3550308; // Código IBGE do seu Município
        $ide->tpImp = 1; // Retrato
        $ide->tpEmis = 1; // Normal
        $ide->finNFe = 1; // Normal
        $ide->indFinal = 1; // Consumidor Final
        $ide->indPres = 1; // Presencial
        $ide->procEmi = 0;
        $ide->verProc = '1.0';
        $nfe->tagide($ide);

        // Emitente (Busque do config ou banco)
        $emit = new \stdClass();
        $emit->CNPJ = Yii::$app->params['nfe']['cnpj_emitente'];
        $emit->xNome = 'MINHA EMPRESA LTDA';
        $emit->IE = '123456789';
        $emit->CRT = 1; // 1=Simples Nacional, 3=Regime Normal
        $nfe->tagemit($emit);
        
        $enderEmit = new \stdClass();
        $enderEmit->xLgr = 'Rua Teste';
        $enderEmit->nro = '100';
        $enderEmit->xBairro = 'Centro';
        $enderEmit->cMun = 3550308;
        $enderEmit->xMun = 'Sao Paulo';
        $enderEmit->UF = 'SP';
        $enderEmit->CEP = '88000000';
        $enderEmit->cPais = 1058;
        $enderEmit->xPais = 'BRASIL';
        $nfe->tagenderEmit($enderEmit);

        // Destinatário (Do seu Model Venda -> Cliente)
        $dest = new \stdClass();
        $dest->CPF = $venda->cliente_documento; // Ou CNPJ
        $dest->xNome = $venda->cliente_nome;
        $dest->indIEDest = 9; // 9=Não Contribuinte
        $nfe->tagdest($dest);
        
        // --- 2. Loop dos Produtos ---
        $i = 1;
        foreach ($venda->itens as $item) {
            $prod = new \stdClass();
            $prod->item = $i;
            $prod->cProd = $item->produto_codigo;
            $prod->xProd = $item->produto_nome;
            $prod->NCM = $item->ncm; // Obrigatório!
            $prod->CFOP = '5102';    // Venda mercadoria
            $prod->uCom = 'UN';
            $prod->qCom = number_format($item->quantidade, 4, '.', '');
            $prod->vUnCom = number_format($item->valor_unitario, 10, '.', '');
            $prod->vProd = number_format($item->valor_total, 2, '.', '');
            $prod->cEAN = "SEM GTIN";
            $prod->cEANTrib = "SEM GTIN";
            $prod->uTrib = 'UN';
            $prod->qTrib = $prod->qCom;
            $prod->vUnTrib = $prod->vUnCom;
            $prod->indTot = 1;
            $nfe->tagprod($prod);

            // Impostos (Exemplo Simples Nacional)
            $imposto = new \stdClass();
            $imposto->item = $i;
            $nfe->tagimposto($imposto);

            $icms = new \stdClass();
            $icms->item = $i;
            $icms->orig = 0; // 0=Nacional
            $icms->CSOSN = '102'; // Tributada pelo Simples sem permissão de crédito
            $nfe->tagICMSSN102($icms);
            
            // PIS (Simples Nacional geralmente é 99 ou isento dependendo da faixa, aqui exemplo 99)
            $pis = new \stdClass();
            $pis->item = $i;
            $pis->CST = '99';
            $pis->vBC = 0.00;
            $pis->pPIS = 0.00;
            $pis->vPIS = 0.00;
            $nfe->tagPISOutr($pis);

            // COFINS
            $cofins = new \stdClass();
            $cofins->item = $i;
            $cofins->CST = '99';
            $cofins->vBC = 0.00;
            $cofins->pCOFINS = 0.00;
            $cofins->vCOFINS = 0.00;
            $nfe->tagCOFINSOutr($cofins);

            $i++;
        }

        // Totais (Obrigatório calcular e informar)
        $total = new \stdClass();
        $total->vBC = 0.00;
        $total->vICMS = 0.00;
        $total->vProd = number_format($venda->valor_total, 2, '.', '');
        $total->vNF = number_format($venda->valor_total, 2, '.', ''); // + frete + etc
        // ... preencher outros campos com 0.00 se necessário
        $nfe->tagICMSTot($total);
        
        // Frete
        $transp = new \stdClass();
        $transp->modFrete = 9; // Sem frete
        $nfe->tagtransp($transp);

        // --- 3. Processar ---
        try {
            $service = new NFeService();
            $retorno = $service->emitir($nfe);

            if ($retorno['sucesso']) {
                // Sucesso! Atualize seu banco com a chave
                $venda->nfe_chave = $retorno['chave'];
                $venda->status = 'EMITIDA';
                $venda->save(false);
                
                Yii::$app->session->setFlash('success', "Nota Autorizada! Chave: " . $retorno['chave']);
            } else {
                Yii::$app->session->setFlash('error', $retorno['motivo']);
            }

        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', "Erro no sistema: " . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }
}