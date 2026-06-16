<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Orcamento;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\modules\vendas\search\OrcamentoSearch;
use yii\helpers\Url;
use app\modules\vendas\models\LojaConfiguracao;
use app\modules\vendas\models\Configuracao;

class OrcamentoController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['imprimir', 'imprimir-a4'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new OrcamentoSearch();
        $searchModel->usuario_id = \app\components\TenantHelper::getId();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Registra CSS
        $this->view->registerCssFile(
            '@web/css/orcamento-list.css',
            ['depends' => [\yii\web\YiiAsset::class]]
        );

        // Registra JS
        $this->view->registerJsFile(
            '@web/js/orcamento-list.js',
            ['depends' => [\yii\web\JqueryAsset::class]]
        );

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Redireciona para o módulo de Venda Direta carregando o orçamento no carrinho
     * @param string $id UUID do orçamento
     */
    public function actionConverter($id)
    {
        $model = $this->findModel($id);

        if ($model->status === Orcamento::STATUS_CONVERTIDO) {
            Yii::$app->session->setFlash('warning', 'Este orçamento já foi convertido em venda.');
            return $this->redirect(['index']);
        }

        // Constrói URL para o PWA de Venda Direta
        // Usa o alias @web para garantir que aponte para a pasta web/venda-direta/index.html
        $urlVendaDireta = Url::to("@web/venda-direta/index.html", true) . "?orcamento_id=" . $id;

        return $this->redirect($urlVendaDireta);
    }

    /**
     * Retorna detalhes completos de um orçamento para impressão
     * @param int $id
     * @return array JSON
     */
    public function actionDetalhes($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = $this->findModel($id);

        // Carrega itens com produtos
        $itens = [];
        foreach ($model->itens as $item) {
            $itens[] = [
                'id' => $item->produto_id, // ID do produto para o PWA
                'nome' => $item->produto ? $item->produto->nome : 'Produto',
                'quantidade' => (float) $item->quantidade,
                'preco' => (float) $item->preco_unitario, // Adicionado campo padrão preco
                'preco_venda_sugerido' => (float) $item->preco_unitario, // Para compatibilidade com cart.js
                'preco_final' => (float) $item->preco_unitario,
                'desconto_valor' => (float) ($item->desconto_valor ?? 0),
                'desconto_percentual' => 0,
                'subtotal' => (float) $item->subtotal,
                'unidade_medida' => $item->produto ? $item->produto->unidade_medida : 'un',
                'venda_fracionada' => $item->produto ? (bool)$item->produto->venda_fracionada : false,
                'fotos' => $item->produto && $item->produto->fotos ? $item->produto->fotos : [],
                'estoque_atual' => $item->produto ? (float)$item->produto->estoque_atual : 0,
                'is_avulso' => $item->produto ? false : true,
                // Escalas de atacado (caso existam no produto e o vendedor altere quantidade no carrinho)
                'qtd_escala_1' => $item->produto ? (float)$item->produto->qtd_escala_1 : null,
                'preco_escala_1' => $item->produto ? (float)$item->produto->preco_escala_1 : null,
                'qtd_escala_2' => $item->produto ? (float)$item->produto->qtd_escala_2 : null,
                'preco_escala_2' => $item->produto ? (float)$item->produto->preco_escala_2 : null,
                'qtd_escala_3' => $item->produto ? (float)$item->produto->qtd_escala_3 : null,
                'preco_escala_3' => $item->produto ? (float)$item->produto->preco_escala_3 : null,
                'qtd_escala_4' => $item->produto ? (float)$item->produto->qtd_escala_4 : null,
                'preco_escala_4' => $item->produto ? (float)$item->produto->preco_escala_4 : null,
                'qtd_escala_5' => $item->produto ? (float)$item->produto->qtd_escala_5 : null,
                'preco_escala_5' => $item->produto ? (float)$item->produto->preco_escala_5 : null,
            ];
        }


        $lojaConfig = LojaConfiguracao::findOne(['usuario_id' => $model->usuario_id]);
        $config = Configuracao::findOne(['usuario_id' => $model->usuario_id]);

        // Dados do cliente se houver
        $cliente = null;
        if ($model->cliente) {
            $cliente = [
                'nome' => $model->cliente->nome_completo ?? $model->cliente->nome ?? '',
                'cpf' => $model->cliente->cpf ?? '',
                'telefone' => $model->cliente->telefone ?? '',
                'endereco' => $model->cliente->endereco_logradouro ?? $model->cliente->endereco ?? '',
                'numero' => $model->cliente->endereco_numero ?? $model->cliente->numero ?? '',
                'complemento' => $model->cliente->endereco_complemento ?? $model->cliente->complemento ?? '',
                'bairro' => $model->cliente->endereco_bairro ?? $model->cliente->bairro ?? '',
                'cidade' => $model->cliente->endereco_cidade ?? $model->cliente->cidade ?? '',
                'estado' => $model->cliente->endereco_estado ?? $model->cliente->estado ?? '',
                'cep' => $model->cliente->endereco_cep ?? $model->cliente->cep ?? '',
            ];
        }

        return [
            'id' => $model->id,
            'hash' => $model->hash,
            'usuario_id' => $model->usuario_id, // ID do Dono da Loja
            'valor_total' => (float) $model->valor_total,
            'acrescimo_valor' => (float) ($model->acrescimo_valor ?? 0),
            'acrescimo_tipo' => $model->acrescimo_tipo ?? '',
            'status' => $model->status,
            'data_criacao' => $model->data_criacao,
            'data_validade' => $model->data_validade,
            'esta_vencido' => $model->EstaVencido,
            'observacoes' => $model->observacoes,
            'cliente' => $cliente,
            'itens' => $itens,
            // Dados PIX para o QR Code
            'pix' => [
                'chave' => $lojaConfig ? $lojaConfig->pix_chave : ($config ? $config->pix_chave : null),
                'nome' => $lojaConfig ? $lojaConfig->pix_nome : ($config ? $config->pix_nome : null),
                'cidade' => $lojaConfig ? $lojaConfig->pix_cidade : ($config ? $config->pix_cidade : null),
            ],
            'forma_pagamento' => 'A Combinar',
            'numero_parcelas' => 1,
        ];
    }

    /**
     * Gera PDF customizado (80mm) para orçamento usando FPDF
     */
    public function actionImprimir($id = null, $hash = null)
    {
        if ($hash) {
            $model = $this->findModelPublico($hash);
        } else {
            $model = $this->findModel($id);
        }
        $apiCmd = new \app\modules\api\controllers\UsuarioController('api', $this->module);
        $empresa = $apiCmd->actionDadosLoja($model->usuario_id);

        // Instancia PDF customizado (80mm x 250mm)
        $pdf = new \NFePHP\DA\Legacy\Pdf('P', 'mm', [80, 250]);
        $pdf->SetMargins(0, 5, 0);
        $pdf->SetAutoPageBreak(true, 5);
        $pdf->AddPage();

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);

        // --- CABEÇALHO ---
        $pdf->SetFont('Courier', 'B', 12);
        $pdf->Cell(80, 6, mb_convert_encoding($empresa['nome_loja'] ?? $empresa['nome'] ?? 'LOJA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        $pdf->SetFont('Courier', '', 9);
        $pdf->Cell(80, 4, mb_convert_encoding($empresa['endereco'] ?? '', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Cell(80, 4, mb_convert_encoding(($empresa['cidade'] ?? '') . ', ' . ($empresa['estado'] ?? ''), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        if (!empty($empresa['telefone'])) {
            $pdf->Cell(80, 4, mb_convert_encoding('Fone: ' . $empresa['telefone'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        }

        $pdf->Ln(2);
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');

        // --- TÍTULO ---
        $pdf->SetFont('Courier', 'B', 11);
        $pdf->Cell(80, 6, mb_convert_encoding('COMPROVANTE DE ORÇAMENTO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        $pdf->SetFont('Courier', '', 10);
        $pdf->Cell(80, 4, date('d/m/Y H:i:s', strtotime($model->data_criacao)), 0, 1, 'C');

        $pdf->SetFont('Courier', 'B', 10);
        $pdf->Cell(80, 6, mb_convert_encoding('ORÇAMENTO N°: ' . $model->id, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        if ($model->cliente) {
            $pdf->SetFont('Courier', '', 9);
            $nomeCli = mb_convert_encoding('CLIENTE: ' . ($model->cliente->nome_completo ?? $model->cliente->nome ?? 'N/I'), 'ISO-8859-1', 'UTF-8');
            $pdf->MultiCell(80, 4, strtoupper($nomeCli), 0, 'L');
        }

        $pdf->Ln(2);
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');
        $pdf->Ln(2);

        // --- ITENS ---
        $totalBruto = 0;
        $totalDescontos = 0;
        $totalPecas = 0;
        foreach ($model->itens as $item) {
            $subBruto = $item->quantidade * $item->preco_unitario;
            $totalBruto += $subBruto;
            $totalDescontos += (float)$item->desconto_valor;
            $totalPecas += (float)$item->quantidade;

            // Nome do Produto
            $pdf->SetFont('Courier', 'B', 10);
            $nome = strtoupper(mb_convert_encoding($item->produto ? $item->produto->nome : 'PRODUTO', 'ISO-8859-1', 'UTF-8'));
            $pdf->MultiCell(80, 4, $nome, 0, 'L');

            // Detalhes
            $pdf->SetFont('Courier', '', 9);
            $decimais = ($item->produto && $item->produto->venda_fracionada) ? 3 : 0;
            $detalhes = number_format($item->quantidade, $decimais, ',', '.') . ' x R$ ' . number_format($item->preco_unitario, 2, ',', '.');

            $pdf->Cell(40, 4, $detalhes, 0, 0, 'L');
            $pdf->Cell(40, 4, 'R$ ' . number_format($subBruto, 2, ',', '.'), 0, 1, 'R');

            if ($item->desconto_valor > 0.01) {
                $pdf->SetFont('Courier', 'I', 8);
                $pdf->SetTextColor(100, 100, 100);
                $labelDesconto = '(-) DESCONTO';
                $pdf->Cell(40, 4, mb_convert_encoding($labelDesconto, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
                $pdf->Cell(40, 4, '- R$ ' . number_format($item->desconto_valor, 2, ',', '.'), 0, 1, 'R');
                $pdf->SetTextColor(0, 0, 0);
            }
            $pdf->Ln(1);
        }

        $pdf->Ln(2);
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');

        // --- TOTAIS ---
        $pdf->SetFont('Courier', '', 10);

        if ($totalDescontos > 0 || (float)$model->acrescimo_valor > 0.01) {
            $pdf->Cell(40, 5, 'SUBTOTAL BRUTO:', 0, 0, 'L');
            $pdf->Cell(40, 5, 'R$ ' . number_format($totalBruto, 2, ',', '.'), 0, 1, 'R');

            if ($totalDescontos > 0.01) {
                $pdf->SetTextColor(150, 0, 0);
                $pdf->Cell(40, 5, 'TOTAL DESCONTOS:', 0, 0, 'L');
                $pdf->Cell(40, 5, '- R$ ' . number_format($totalDescontos, 2, ',', '.'), 0, 1, 'R');
                $pdf->SetTextColor(0, 0, 0);
            }

            if ((float)$model->acrescimo_valor > 0.01) {
                $pdf->SetTextColor(0, 0, 150);
                $labelAcr = 'ACRESCIMO' . ($model->acrescimo_tipo ? ' (' . $model->acrescimo_tipo . ')' : '');
                $pdf->Cell(40, 5, mb_convert_encoding($labelAcr, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
                $pdf->Cell(40, 5, '+ R$ ' . number_format($model->acrescimo_valor, 2, ',', '.'), 0, 1, 'R');
                $pdf->SetTextColor(0, 0, 0);
            }
        }

        $pdf->Ln(2);
        $pdf->SetFont('Courier', 'B', 12);
        $pdf->Cell(40, 6, 'TOTAL LÍQUIDO:', 0, 0, 'L');
        $pdf->Cell(40, 6, 'R$ ' . number_format($model->valor_total, 2, ',', '.'), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetFont('Courier', 'B', 9);
        $pdf->Cell(80, 4, mb_convert_encoding('TOTAL DE ITENS: ' . $totalPecas, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

        $pdf->Ln(2);
        $pdf->SetFont('Courier', '', 10);
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');

        if (!empty($model->observacoes)) {
            $pdf->Ln(2);
            $pdf->SetFont('Courier', 'I', 9);
            $pdf->MultiCell(80, 4, mb_convert_encoding('OBSERVAÇÕES: ' . $model->observacoes, 'ISO-8859-1', 'UTF-8'), 0, 'L');
        }

        $pdfData = $pdf->output('', 'S');
        if (ob_get_length()) ob_end_clean();

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Orcamento_' . $model->id . '.pdf"');
        header('Content-Length: ' . strlen($pdfData));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfData;
        exit;
    }

    /**
     * Imprime o orçamento em formato A4
     */
    public function actionImprimirA4($id = null, $hash = null)
    {
        if ($hash) {
            $model = $this->findModelPublico($hash);
        } else {
            $model = $this->findModel($id);
        }
        $usuario = $model->usuario;
        $lojaConfig = LojaConfiguracao::findOne(['usuario_id' => $usuario->id]);
        $config = Configuracao::findOne(['usuario_id' => $usuario->id]);

        // Carrega FPDF
        require_once(Yii::getAlias('@vendor') . '/setasign/fpdf/fpdf.php');

        // Cria a classe customizada de PDF estendendo FPDF
        $pdf = new class($model, $lojaConfig, $config) extends \FPDF {
            private $model;
            private $lojaConfig;
            private $config;

            public function __construct($model, $lojaConfig, $config) {
                parent::__construct('P', 'mm', 'A4');
                $this->model = $model;
                $this->lojaConfig = $lojaConfig;
                $this->config = $config;
                $this->SetMargins(10, 10, 10);
                $this->SetAutoPageBreak(true, 25); // Margem inferior de 25mm deixa espaço livre para assinaturas dinâmicas
            }

            public function NbLines($w, $txt) {
                // Computes the number of lines a MultiCell of width w will take
                $cw = &$this->CurrentFont['cw'];
                if($w==0)
                    $w = $this->w - $this->rMargin - $this->x;
                $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
                $s = str_replace("\r", '', $txt);
                $nb = strlen($s);
                if($nb>0 && $s[$nb-1]=="\n")
                    $nb--;
                $sep = -1;
                $i = 0;
                $j = 0;
                $l = 0;
                $nl = 1;
                while($i<$nb) {
                    $c = $s[$i];
                    if($c=="\n") {
                        $i++;
                        $sep = -1;
                        $j = $i;
                        $l = 0;
                        $nl++;
                        continue;
                    }
                    if($c==' ')
                        $sep = $i;
                    $l += isset($cw[$c]) ? $cw[$c] : 0;
                    if($l>$wmax) {
                        if($sep==-1) {
                            if($i==$j)
                                $i++;
                        }
                        else
                            $i = $sep+1;
                        $sep = -1;
                        $j = $i;
                        $l = 0;
                        $nl++;
                    }
                    else
                        $i++;
                }
                return $nl;
            }

            public function Header() {
                // Cabeçalho da Loja
                $this->SetFont('Arial', 'B', 14);
                $this->SetTextColor(0, 0, 0);
                $nomeLoja = $this->lojaConfig ? $this->lojaConfig->nome_loja : ($this->config ? $this->config->nome_loja : 'Pulse Vendas');
                $this->Cell(190, 8, utf8_decode($nomeLoja), 0, 1, 'C');
                
                $this->SetFont('Arial', '', 8.5);
                $this->SetTextColor(80, 80, 80);
                $infoLoja = ($this->lojaConfig ? $this->lojaConfig->getEnderecoCompleto() : ($this->config ? $this->config->endereco_completo : '')) . ($this->lojaConfig && $this->lojaConfig->telefone ? ' - ' . $this->lojaConfig->telefone : ($this->config && $this->config->whatsapp ? ' - ' . $this->config->whatsapp : ''));
                $this->Cell(190, 4.5, utf8_decode($infoLoja), 0, 1, 'C');
                
                $this->Ln(2);
                $this->Line(10, $this->GetY(), 200, $this->GetY());
                $this->Ln(3);

                // Título e Dados do Orçamento
                $this->SetFont('Arial', 'B', 11);
                $this->SetTextColor(0, 0, 0);
                $this->Cell(95, 6, utf8_decode("ORÇAMENTO Nº: " . strtoupper($this->model->id)), 0, 0);
                $this->SetFont('Arial', '', 9);
                $this->SetTextColor(80, 80, 80);
                $this->Cell(95, 6, utf8_decode("Data: " . date('d/m/Y H:i', strtotime($this->model->data_criacao))), 0, 1, 'R');
                $this->Ln(2);

                // Dados do Cliente (Apenas na página 1)
                if ($this->PageNo() == 1 && $this->model->cliente) {
                    $this->SetFillColor(245, 245, 245);
                    $this->SetTextColor(0, 0, 0);
                    $this->SetFont('Arial', 'B', 9);
                    $this->Cell(190, 6, utf8_decode(" DADOS DO CLIENTE"), 0, 1, 'L', true);
                    $this->SetFont('Arial', '', 8.5);
                    $this->SetTextColor(50, 50, 50);
                    $this->Cell(190, 5, utf8_decode(" Nome: " . ($this->model->cliente->nome_completo ?? 'N/I')), 0, 1);
                    if ($this->model->cliente->cpf) $this->Cell(190, 5, utf8_decode(" CPF/CNPJ: " . $this->model->cliente->cpf), 0, 1);
                    if ($this->model->cliente->telefone) $this->Cell(190, 5, utf8_decode(" Fone: " . $this->model->cliente->telefone), 0, 1);
                    $this->Ln(3);
                }

                // Cabeçalho da Tabela
                $this->SetFont('Arial', 'B', 8.5);
                $this->SetFillColor(230, 230, 230);
                $this->SetTextColor(0, 0, 0);
                $this->Cell(65, 6, utf8_decode(" Descrição do Produto"), 1, 0, 'L', true);
                $this->Cell(12, 6, utf8_decode("Qtd"), 1, 0, 'C', true);
                $this->Cell(25, 6, utf8_decode("Vl. Unit."), 1, 0, 'R', true);
                $this->Cell(28, 6, utf8_decode("Vlr. S/ Desc."), 1, 0, 'R', true);
                $this->Cell(25, 6, utf8_decode("Vl. Desconto"), 1, 0, 'R', true);
                $this->Cell(35, 6, utf8_decode("Valor com Desconto"), 1, 1, 'R', true);

                $this->SetFont('Arial', '', 8);
            }

            public function Footer() {
                $this->SetY(-22);
                $this->SetFont('Arial', 'I', 7.5);
                $this->SetTextColor(120, 120, 120);
                $this->Cell(190, 4, utf8_decode("Este orçamento é válido por 7 dias. - Este documento não tem valor fiscal."), 0, 1, 'C');
                $this->Cell(190, 4, utf8_decode("Página " . $this->PageNo() . " de {nb}"), 0, 0, 'C');
            }
        };

        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $totalPecas = 0;
        $totalBruto = 0;
        $totalDescontos = 0;

        foreach ($model->itens as $item) {
            $subBruto = $item->quantidade * $item->preco_unitario;
            $totalBruto += $subBruto;
            $descontoValor = (float)$item->desconto_valor;
            $totalDescontos += $descontoValor;
            $totalPecas += (float)$item->quantidade;

            $pdf->SetFont('Arial', '', 8);
            $nomeProduto = $item->produto ? $item->produto->nome : 'Produto não identificado';
            $decimais = ($item->produto && $item->produto->venda_fracionada) ? 3 : 0;
            $subLiquido = $subBruto - $descontoValor;

            // Calcula a altura necessária para a descrição (largura de 65mm)
            $textoDecodificado = utf8_decode(" " . $nomeProduto);
            $nb = $pdf->NbLines(65, $textoDecodificado);
            $lineHeight = 4.2;
            $rowHeight = max($nb * $lineHeight, 5.5);

            // Verifica se a altura do item ultrapassa a página atual para quebrar
            if ($pdf->GetY() + $rowHeight > 265) {
                $pdf->AddPage();
            }

            // Posição inicial para desenhar a linha
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();

            // Desenha a descrição usando MultiCell
            $pdf->MultiCell(65, $rowHeight / $nb, $textoDecodificado, 1, 'L');

            // Reposiciona o cursor à direita do MultiCell
            $pdf->SetXY($startX + 65, $startY);

            // Desenha as demais colunas com a altura da linha calculada ($rowHeight)
            $pdf->Cell(12, $rowHeight, number_format($item->quantidade, $decimais, ',', '.'), 1, 0, 'C');
            $pdf->Cell(25, $rowHeight, "R$ " . number_format($item->preco_unitario, 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(28, $rowHeight, "R$ " . number_format($subBruto, 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(25, $rowHeight, "R$ " . number_format($descontoValor, 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(35, $rowHeight, "R$ " . number_format($subLiquido, 2, ',', '.'), 1, 1, 'R');
        }

        // Totais e Observações
        $pdf->Ln(2);

        $col1 = 155;
        $col2 = 35;

        // Estima a altura para o fechamento
        $linhasTotais = 1; // total peças line
        if ($totalDescontos > 0 || (float)$model->acrescimo_valor > 0.01) {
            $linhasTotais += 1; // subtotal
            if ($totalDescontos > 0.01) $linhasTotais += 1;
            if ((float)$model->acrescimo_valor > 0.01) $linhasTotais += 1;
        }
        $linhasTotais += 1; // total líquido line
        
        $alturaTotais = $linhasTotais * 5.5 + 10;
        
        $alturaObservacoes = 0;
        if (!empty($model->observacoes)) {
            $linhasObs = ceil(strlen($model->observacoes) / 100);
            $alturaObservacoes = 5 + $linhasObs * 4.5 + 5;
        }

        // Assinaturas + rodapé
        $espacoNecessario = $alturaTotais + $alturaObservacoes + 35;
        if ($pdf->GetY() + $espacoNecessario > 265) {
            $pdf->AddPage();
        }

        $pdf->SetFont('Arial', '', 9);
        $hTotal = 5.5;

        if ($totalDescontos > 0 || (float)$model->acrescimo_valor > 0.01) {
            $pdf->Cell($col1, $hTotal, utf8_decode("SUBTOTAL BRUTO:"), 0, 0, 'R');
            $pdf->Cell($col2, $hTotal, "R$ " . number_format($totalBruto, 2, ',', '.'), 0, 1, 'R');

            if ($totalDescontos > 0.01) {
                $pdf->SetTextColor(150, 0, 0);
                $pdf->Cell($col1, $hTotal, utf8_decode("(-) TOTAL DESCONTOS:"), 0, 0, 'R');
                $pdf->Cell($col2, $hTotal, "- R$ " . number_format($totalDescontos, 2, ',', '.'), 0, 1, 'R');
                $pdf->SetTextColor(0, 0, 0);
            }

            if ((float)$model->acrescimo_valor > 0.01) {
                $pdf->SetTextColor(0, 0, 150);
                $labelAcr = " (+) ACRÉSCIMO" . ($model->acrescimo_tipo ? " ({$model->acrescimo_tipo})" : "");
                $pdf->Cell($col1, $hTotal, utf8_decode($labelAcr . ":"), 0, 0, 'R');
                $pdf->Cell($col2, $hTotal, "R$ " . number_format($model->acrescimo_valor, 2, ',', '.'), 0, 1, 'R');
                $pdf->SetTextColor(0, 0, 0);
            }
        }

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell($col1, $hTotal, utf8_decode("TOTAL DE PEÇAS/ITENS:"), 0, 0, 'R');
        $pdf->Cell($col2, $hTotal, number_format($totalPecas, 0), 0, 1, 'R');

        $pdf->Ln(1);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetTextColor(75, 0, 130);
        $pdf->Cell($col1, 8, utf8_decode("VALOR TOTAL LÍQUIDO:"), 0, 0, 'R', true);
        $pdf->Cell($col2, 8, "R$ " . number_format($model->valor_total, 2, ',', '.'), 0, 1, 'R', true);
        $pdf->SetTextColor(0, 0, 0);

        // Observações
        if (!empty($model->observacoes)) {
            $pdf->Ln(3);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(190, 5, utf8_decode("OBSERVAÇÕES:"), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->MultiCell(190, 4.5, utf8_decode($model->observacoes), 0, 'L');
        }

        // Assinaturas
        $pdf->SetAutoPageBreak(false); // Desativa temporariamente a quebra automática para posicionamento livre no rodapé
        $pdf->SetY(-35); // Força as assinaturas a ficarem a 35mm do final da página
        $pdf->Line(20, $pdf->GetY(), 90, $pdf->GetY());
        $pdf->Line(110, $pdf->GetY(), 180, $pdf->GetY());
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(95, 5, utf8_decode("Assinatura da Loja"), 0, 0, 'C');
        $pdf->Cell(95, 5, utf8_decode("Assinatura do Cliente"), 0, 1, 'C');
        $pdf->SetAutoPageBreak(true, 25); // Reativa

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'application/pdf');
        return $pdf->Output('I', 'Orcamento_' . $model->id . '.pdf');
    }

    protected function findModel($id)
    {
        if (($model = Orcamento::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }
        throw new \yii\web\NotFoundHttpException('A página solicitada não existe.');
    }

    /**
     * Busca o orçamento pelo hash (acesso público)
     * @param string $hash
     * @return Orcamento
     */
    protected function findModelPublico($hash)
    {
        if (($model = Orcamento::findOne(['hash' => $hash])) !== null) {
            return $model;
        }
        throw new \yii\web\NotFoundHttpException('O orçamento solicitado não existe.');
    }

    /**
     * Retorna resumo de orçamentos para o dashboard da listagem
     */
    public function actionResumo()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $today = date('Y-m-d 00:00:00');
        $tomorrow = date('Y-m-d 23:59:59', strtotime('+1 day'));
        $usuario_id = \app\components\TenantHelper::getId();

        $totalHoje = (float)Orcamento::find()->where(['>=', 'data_criacao', $today])->andWhere(['usuario_id' => $usuario_id])->sum('valor_total');
        $countHoje = (int)Orcamento::find()->where(['>=', 'data_criacao', $today])->andWhere(['usuario_id' => $usuario_id])->count();
        $totalPendente = (float)Orcamento::find()->where(['status' => Orcamento::STATUS_PENDENTE])->andWhere(['usuario_id' => $usuario_id])->sum('valor_total');

        // Orçamentos que vencem amanhã ou hoje e ainda estão pendentes
        $vencendoAmanha = (int)Orcamento::find()
            ->where(['status' => Orcamento::STATUS_PENDENTE])
            ->andWhere(['usuario_id' => $usuario_id])
            ->andWhere(['>=', 'data_validade', date('Y-m-d')])
            ->andWhere(['<=', 'data_validade', date('Y-m-d', strtotime('+1 day'))])
            ->count();

        return [
            'hoje_valor' => $totalHoje,
            'hoje_qtd' => $countHoje,
            'pendente_valor' => $totalPendente,
            'vencendo_amanha_qtd' => $vencendoAmanha
        ];
    }
}
