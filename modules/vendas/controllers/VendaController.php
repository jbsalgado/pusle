<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Venda;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\Response;
use app\modules\vendas\models\LojaConfiguracao;
use app\modules\vendas\models\Configuracao;

/**
 * Controller para listagem de vendas efetivadas
 */
class VendaController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
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

    /**
     * Listagem de vendas com Grid e Card
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;
        $searchModel = new \app\modules\vendas\search\VendaSearch();
        $searchModel->usuario_id = $usuarioId;

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Visualiza detalhes de uma venda
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Altera o status de uma venda via AJAX
     */
    public function actionAlterarStatus($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $vendaId = Yii::$app->request->post('venda_id') ?: $id;
        $novoStatus = Yii::$app->request->post('status');

        if (!$novoStatus) {
            return ['success' => false, 'message' => 'Novo status não informado.'];
        }

        try {
            $model = $this->findModel($vendaId);
            if ($model->alterarStatus($novoStatus)) {
                return ['success' => true, 'message' => 'Status alterado com sucesso!'];
            }
            return ['success' => false, 'message' => 'Erro ao alterar status.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Retorna detalhes completos de uma venda para impressão (API JSON)
     */
    public function actionDetalhes($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findModel($id);

        $itens = [];
        foreach ($model->itens as $item) {
            $itens[] = [
                'nome' => $item->getNomeExibicao(),
                'quantidade' => (float) $item->quantidade,
                'preco' => (float) $item->preco_unitario_venda,
                'desconto_valor' => (float) ($item->desconto_valor ?? 0),
                'desconto_percentual' => (float) ($item->desconto_percentual ?? 0),
                'subtotal' => (float) $item->valor_total_item,
            ];
        }

        $cliente = null;
        if ($model->cliente) {
            $cliente = [
                'nome' => $model->cliente->nome_completo ?? '',
                'cpf' => $model->cliente->cpf ?? '',
                'telefone' => $model->cliente->telefone ?? '',
                'endereco' => $model->cliente->endereco_logradouro ?? '',
                'numero' => $model->cliente->endereco_numero ?? '',
                'bairro' => $model->cliente->endereco_bairro ?? '',
                'cidade' => $model->cliente->endereco_cidade ?? '',
                'estado' => $model->cliente->endereco_estado ?? '',
            ];
        }

        // Busca se existe orçamento vinculado de forma segura (verifica se a coluna existe)
        $orcamento = null;
        if (in_array('venda_id', \app\modules\vendas\models\Orcamento::getTableSchema()->columnNames)) {
            $orcamento = \app\modules\vendas\models\Orcamento::findOne(['venda_id' => $model->id]);
        }

        return [
            'id' => $model->id,
            'itens' => $itens,
            // Detalhamento de Pagamentos
            'pagamentos' => array_values(array_reduce($model->parcelas, function ($acc, $p) {
                if ($p->status_parcela_codigo === \app\modules\vendas\models\StatusParcela::PAGA) {
                    $nome = $p->formaPagamento ? $p->formaPagamento->nome : 'Não especificado';
                    if (!isset($acc[$nome])) $acc[$nome] = ['nome' => $nome, 'valor' => 0];
                    $acc[$nome]['valor'] += (float)$p->valor_pago;
                }
                return $acc;
            }, [])),
            'orcamento_id' => $orcamento ? $orcamento->id : null,
            'orcamento_valor_original' => $orcamento ? (float)$orcamento->valor_total : null,
            'usuario_id' => $model->usuario_id, // Identificador da loja
            'valor_total' => (float) $model->valor_total,
            'acrescimo_valor' => (float) ($model->acrescimo_valor ?? 0),
            'acrescimo_tipo' => $model->acrescimo_tipo ?? '',
            'data_criacao' => $model->data_criacao,
            'status' => $model->status_venda_codigo, // Ex: QUITADA
            'forma_pagamento' => $model->formaPagamento ? $model->formaPagamento->nome : 'N/A',
            'cliente' => $cliente,
            'observacoes' => $model->observacoes,
            'itens' => $itens,
        ];
    }

    /**
     * Gera PDF customizado (80mm) para bobina térmica usando FPDF (NFePHP Legacy)
     * Proporciona controle total sobre margens (0mm) e layout profissional de PDV
     */
    public function actionImprimir($id)
    {
        $model = $this->findModel($id);
        $gift = Yii::$app->request->get('gift');

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
        if (!empty($empresa['cpf_cnpj']) && !$gift) {
            $pdf->Cell(80, 4, mb_convert_encoding('CNPJ: ' . $empresa['cpf_cnpj'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        }
        $pdf->Cell(80, 4, mb_convert_encoding($empresa['endereco'] ?? '', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Cell(80, 4, mb_convert_encoding(($empresa['cidade'] ?? '') . ', ' . ($empresa['estado'] ?? ''), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        if (!empty($empresa['telefone'])) {
            $pdf->Cell(80, 4, mb_convert_encoding('Fone: ' . $empresa['telefone'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        }

        $pdf->Ln(2);
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');

        // --- TÍTULO ---
        $pdf->SetFont('Courier', 'B', 11);
        $pdf->Cell(80, 6, $gift ? 'CUPOM DE PRESENTE' : 'COMPROVANTE DE VENDA', 0, 1, 'C');

        $pdf->SetFont('Courier', '', 10);
        $pdf->Cell(80, 4, date('d/m/Y H:i:s', strtotime($model->data_criacao)), 0, 1, 'C');

        $pdf->SetFont('Courier', 'B', 10);
        $pdf->Cell(80, 6, mb_convert_encoding('VENDA N°: ' . strtoupper(substr($model->id, 0, 8)), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        // Rastreabilidade: Orçamento de Origem (Verifica se a coluna existe para evitar erro de SQL)
        $orcamentoOrigem = null;
        if (\app\modules\vendas\models\Orcamento::getTableSchema()->getColumn('venda_id')) {
            $orcamentoOrigem = \app\modules\vendas\models\Orcamento::findOne(['venda_id' => $model->id]);
        }

        if ($orcamentoOrigem) {
            $pdf->SetFont('Courier', 'I', 8);
            $pdf->Cell(80, 4, mb_convert_encoding('ORIGEM: ORÇAMENTO #' . $orcamentoOrigem->id, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

            // Alerta de Divergência no PDF
            if (abs((float)$orcamentoOrigem->valor_total - (float)$model->valor_total) > 0.01) {
                $pdf->SetFont('Courier', 'B', 8);
                $pdf->SetTextColor(200, 0, 0); // Texto em tom avermelhado para alerta
                $pdf->MultiCell(80, 4, mb_convert_encoding('AVISO: VALOR ALTERADO APOS CONVERSAO', 'ISO-8859-1', 'UTF-8'), 0, 'C');
                $pdf->SetFont('Courier', '', 8);
                $pdf->Cell(80, 4, mb_convert_encoding('(Original: R$ ' . number_format($orcamentoOrigem->valor_total, 2, ',', '.') . ')', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
                $pdf->SetTextColor(0, 0, 0);
            }
        }

        if ($model->cliente) {
            $pdf->SetFont('Courier', '', 9);
            $nomeCli = mb_convert_encoding('CLIENTE: ' . ($model->cliente->nome_completo ?? $model->cliente->nome ?? 'N/I'), 'ISO-8859-1', 'UTF-8');
            $pdf->MultiCell(80, 4, strtoupper($nomeCli), 0, 'L');
        }

        $pdf->Ln(2);
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');
        $pdf->Ln(2);

        // --- ITENS ---
        $pdf->SetFont('Courier', 'B', 10);
        $totalBruto = 0;
        $totalDescontos = 0;
        $totalPecas = 0;
        foreach ($model->itens as $item) {
            $subBruto = $item->quantidade * $item->preco_unitario_venda;
            $totalBruto += $subBruto;
            $totalDescontos += (float)$item->desconto_valor;
            $totalPecas += (float)$item->quantidade;

            // Nome do Produto
            $pdf->SetFont('Courier', 'B', 10);
            $nome = strtoupper(mb_convert_encoding($item->getNomeExibicao(), 'ISO-8859-1', 'UTF-8'));
            $pdf->MultiCell(80, 4, $nome, 0, 'L');

            // Detalhes
            $pdf->SetFont('Courier', '', 9);
            $decimais = ($item->produto && $item->produto->venda_fracionada) ? 3 : 0;

            if (!$gift) {
                $detalhes = number_format($item->quantidade, $decimais, ',', '.') . ' x R$ ' . number_format($item->preco_unitario_venda, 2, ',', '.');
                $pdf->Cell(40, 4, $detalhes, 0, 0, 'L');
                $pdf->Cell(40, 4, 'R$ ' . number_format($item->valor_total_item, 2, ',', '.'), 0, 1, 'R');

                if ($item->desconto_valor > 0.01) {
                    $pdf->SetFont('Courier', 'I', 8);
                    $pdf->SetTextColor(100, 100, 100);
                    $labelDesconto = '(-) DESCONTO ' . ($item->desconto_percentual > 0 ? (float)$item->desconto_percentual . '%' : '');
                    $pdf->Cell(40, 4, mb_convert_encoding($labelDesconto, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
                    $pdf->Cell(40, 4, '- R$ ' . number_format($item->desconto_valor, 2, ',', '.'), 0, 1, 'R');
                    $pdf->SetTextColor(0, 0, 0);
                }
            } else {
                $pdf->Cell(80, 4, 'QTD: ' . number_format($item->quantidade, $decimais, ',', '.'), 0, 1, 'L');
            }
            $pdf->Ln(1);
        }
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');

        // --- TOTAIS ---
        if (!$gift) {
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
            $pdf->Cell(40, 6, 'TOTAL LIQUIDO:', 0, 0, 'L');
            $pdf->Cell(40, 6, 'R$ ' . number_format($model->valor_total, 2, ',', '.'), 0, 1, 'R');
        }

        $pdf->Ln(2);
        $pdf->SetFont('Courier', 'B', 9);
        $pdf->Cell(80, 4, mb_convert_encoding('TOTAL DE ITENS: ' . $totalPecas, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

        if (!$gift) {
            $pdf->Ln(2);
            $pdf->SetFont('Courier', '', 10);
            $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');

            // --- PAGAMENTO ---
            $pdf->Ln(2);
            $pdf->SetFont('Courier', 'B', 10);
            $pdf->Cell(80, 5, mb_convert_encoding('FORMA DE PAGAMENTO: ' . ($model->formaPagamento ? $model->formaPagamento->nome : 'DINHEIRO'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
            $pdf->SetFont('Courier', 'U', 10);
            $pdf->Cell(80, 5, mb_convert_encoding('VALOR PAGO: R$ ' . number_format($model->valor_total, 2, ',', '.'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        } else {
            $pdf->Ln(4);
            $pdf->SetFont('Courier', 'I', 8);
            $pdf->MultiCell(80, 4, mb_convert_encoding("Este é um Cupom de Presente. Guarde para trocas. Identificador: " . $model->id, 'ISO-8859-1', 'UTF-8'), 0, 'C');
        }

        $pdf->Ln(2);
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');

        // --- OBSERVAÇÕES ---
        if (!empty($model->observacoes)) {
            $pdf->Ln(2);
            $pdf->SetFont('Courier', 'B', 9);
            $pdf->Cell(80, 4, 'OBSERVACOES:', 0, 1, 'L');
            $pdf->SetFont('Courier', '', 9);
            $pdf->MultiCell(80, 4, mb_convert_encoding($model->observacoes, 'ISO-8859-1', 'UTF-8'), 0, 'L');
            $pdf->Ln(2);
            $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');
        }

        // --- RODAPÉ ---
        $pdf->Ln(4);
        $pdf->SetFont('Courier', '', 10);
        $pdf->Cell(80, 5, mb_convert_encoding('Obrigado pela preferência!', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Cell(80, 5, mb_convert_encoding($empresa['nome_loja'] ?? $empresa['nome'] ?? '', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        $pdfData = $pdf->output('', 'S');
        if (ob_get_length()) ob_end_clean();

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Venda_' . substr($model->id, 0, 8) . '.pdf"');
        header('Content-Length: ' . strlen($pdfData));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfData;
        exit;
    }


    /**
     * Imprime a venda em formato A4
     */
    public function actionImprimirA4($id)
    {
        $model = $this->findModel($id);
        $usuario = $model->usuario;
        $lojaConfig = LojaConfiguracao::findOne(['usuario_id' => $usuario->id]);
        $config = Configuracao::findOne(['usuario_id' => $usuario->id]);

        // Carrega FPDF
        require_once(Yii::getAlias('@vendor') . '/setasign/fpdf/fpdf.php');

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);

        // Cabeçalho
        $nomeLoja = $lojaConfig ? $lojaConfig->nome_loja : ($config ? $config->nome_loja : 'Pulse Vendas');
        $pdf->Cell(190, 10, utf8_decode($nomeLoja), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);

        $infoLoja = ($lojaConfig ? $lojaConfig->getEnderecoCompleto() : ($config ? $config->endereco_completo : '')) . ($lojaConfig && $lojaConfig->telefone ? ' - ' . $lojaConfig->telefone : ($config && $config->whatsapp ? ' - ' . $config->whatsapp : ''));
        $pdf->Cell(190, 5, utf8_decode($infoLoja), 0, 1, 'C');
        $pdf->Ln(5);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);

        // Título e Dados da Venda
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(95, 8, utf8_decode("PEDIDO DE COMPRA Nº: " . strtoupper(substr($model->id, 0, 8))), 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(95, 8, utf8_decode("Data: " . date('d/m/Y H:i', strtotime($model->data_venda))), 0, 1, 'R');
        $pdf->Ln(2);

        // Cliente
        if ($model->cliente) {
            $pdf->SetFillColor(245, 245, 245);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(190, 7, utf8_decode(" DADOS DO CLIENTE"), 0, 1, 'L', true);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(190, 6, utf8_decode(" Nome: " . ($model->cliente->nome_completo ?? 'N/I')), 0, 1);
            if ($model->cliente->cpf) $pdf->Cell(190, 6, utf8_decode(" CPF/CNPJ: " . $model->cliente->cpf), 0, 1);
            if ($model->cliente->telefone) $pdf->Cell(190, 6, utf8_decode(" Fone: " . $model->cliente->telefone), 0, 1);
            $pdf->Ln(4);
        }

        // Rastreabilidade e Alerta de Divergência
        $orcamento = null;
        if (\app\modules\vendas\models\Orcamento::getTableSchema()->getColumn('venda_id')) {
            $orcamento = \app\modules\vendas\models\Orcamento::findOne(['venda_id' => $model->id]);
        }

        if ($orcamento && $orcamento->valor_total != $model->valor_total) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(95, 8, utf8_decode("Origem: Orçamento #" . $orcamento->id), 0, 0);

            if (abs((float)$orcamento->valor_total - (float)$model->valor_total) > 0.01) {
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetTextColor(200, 0, 0);
                $pdf->Cell(95, 8, utf8_decode("DIVERGÊNCIA DE VALOR (Original: R$ " . number_format($orcamento->valor_total, 2, ',', '.') . ")"), 0, 1, 'R');
                $pdf->SetTextColor(0, 0, 0);
            } else {
                $pdf->Ln(8);
            }
        }

        // Tabela de Itens
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(100, 8, utf8_decode(" Descrição do Produto"), 1, 0, 'L', true);
        $pdf->Cell(20, 8, utf8_decode("Qtd"), 1, 0, 'C', true);
        $pdf->Cell(35, 8, utf8_decode("Vlr. Unit"), 1, 0, 'R', true);
        $pdf->Cell(35, 8, utf8_decode("Total"), 1, 1, 'R', true);

        $pdf->SetFont('Arial', '', 10);
        $totalPecas = 0;
        $subtotalBruto = 0;
        $totalDescontos = 0;

        foreach ($model->itens as $item) {
            $h = 7;
            $nomeProduto = $item->getNomeExibicao();
            $subtotalItem = $item->quantidade * $item->preco_unitario_venda;
            $subtotalBruto += $subtotalItem;
            $totalDescontos += (float)$item->desconto_valor;

            $pdf->Cell(100, $h, utf8_decode(" " . $nomeProduto), 'LR', 0, 'L');
            $pdf->Cell(20, $h, number_format($item->quantidade, 2, ',', '.'), 'LR', 0, 'C');
            $pdf->Cell(35, $h, "R$ " . number_format($item->preco_unitario_venda, 2, ',', '.'), 'LR', 0, 'R');
            $pdf->Cell(35, $h, "R$ " . number_format($subtotalItem, 2, ',', '.'), 'LR', 1, 'R');

            // Se houver desconto no item, adiciona linha secundária
            if ($item->desconto_valor > 0) {
                $pdf->SetFont('Arial', 'I', 8);
                $pdf->SetTextColor(100, 100, 100);
                $descTexto = "   (-) Desconto: R$ " . number_format($item->desconto_valor, 2, ',', '.') . " (" . number_format($item->desconto_percentual, 1, ',', '.') . "%)";
                $pdf->Cell(100, 5, utf8_decode($descTexto), 'LR', 0, 'L');
                $pdf->Cell(20, 5, "", 'LR', 0, 'C');
                $pdf->Cell(35, 5, "", 'LR', 0, 'R');
                $pdf->Cell(35, 5, "R$ " . number_format($item->valor_total_item, 2, ',', '.'), 'LR', 1, 'R');
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetTextColor(0, 0, 0);
            }

            // Linha de baixo da borda
            $pdf->Cell(190, 0, '', 'T', 1);

            $totalPecas += $item->quantidade;
        }

        // Totais
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(155, 6, utf8_decode("TOTAL DE ITENS:"), 0, 0, 'R');
        $pdf->Cell(35, 6, number_format($totalPecas, 0), 0, 1, 'R');

        if ($totalDescontos > 0 || $model->acrescimo_valor > 0) {
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(155, 6, utf8_decode("SUBTOTAL BRUTO:"), 0, 0, 'R');
            $pdf->Cell(35, 6, "R$ " . number_format($subtotalBruto, 2, ',', '.'), 0, 1, 'R');

            if ($totalDescontos > 0) {
                $pdf->SetTextColor(150, 0, 0);
                $pdf->Cell(155, 6, utf8_decode("(-) TOTAL DESCONTOS:"), 0, 0, 'R');
                $pdf->Cell(35, 6, "-R$ " . number_format($totalDescontos, 2, ',', '.'), 0, 1, 'R');
                $pdf->SetTextColor(0, 0, 0);
            }

            if ($model->acrescimo_valor > 0) {
                $pdf->SetTextColor(0, 0, 150);
                $tipoAcrescimo = $model->acrescimo_tipo ? " ({$model->acrescimo_tipo})" : "";
                $pdf->Cell(155, 6, utf8_decode("(+) ACRÉSCIMO{$tipoAcrescimo}:"), 0, 0, 'R');
                $pdf->Cell(35, 6, "+R$ " . number_format($model->acrescimo_valor, 2, ',', '.'), 0, 1, 'R');
                $pdf->SetTextColor(0, 0, 0);
            }
        }

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(0, 100, 0);
        $pdf->Cell(155, 10, utf8_decode("VALOR TOTAL LÍQUIDO:"), 0, 0, 'R');
        $pdf->Cell(35, 10, "R$ " . number_format($model->valor_total, 2, ',', '.'), 0, 1, 'R');
        $pdf->SetTextColor(0, 0, 0);

        // Observações
        if (!empty($model->observacoes)) {
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell(190, 7, utf8_decode(" OBSERVAÇÕES"), 0, 1, 'L', true);
            $pdf->SetFont('Arial', '', 10);
            $pdf->MultiCell(190, 6, utf8_decode($model->observacoes), 0, 'L');
        }

        // Assinaturas
        $pdf->SetY(260);
        $pdf->Line(20, $pdf->GetY(), 90, $pdf->GetY());
        $pdf->Line(110, $pdf->GetY(), 180, $pdf->GetY());
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(95, 5, utf8_decode("Assinatura da Loja"), 0, 0, 'C');
        $pdf->Cell(95, 5, utf8_decode("Assinatura do Cliente"), 0, 1, 'C');

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'application/pdf');
        return $pdf->Output('I', 'Venda_' . substr($model->id, 0, 8) . '.pdf');
    }

    protected function findModel($id)
    {
        if (($model = Venda::findOne(['id' => $id, 'usuario_id' => \Yii::$app->user->id])) !== null) {
            return $model;
        }
        throw new \yii\web\NotFoundHttpException('Venda não encontrada.');
    }

    /**
     * Retorna resumo de vendas para o dashboard da listagem
     */
    public function actionResumo()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $today = date('Y-m-d 00:00:00');
        $usuario_id = \Yii::$app->user->id;

        $totalHoje = (float)Venda::find()->where(['>=', 'data_venda', $today])->andWhere(['usuario_id' => $usuario_id])->sum('valor_total');
        $countHoje = (int)Venda::find()->where(['>=', 'data_venda', $today])->andWhere(['usuario_id' => $usuario_id])->count();

        return [
            'hoje_valor' => $totalHoje,
            'hoje_qtd' => $countHoje
        ];
    }
}
