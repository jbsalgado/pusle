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

        $dataProvider = new ActiveDataProvider([
            'query' => Venda::find()
                ->where(['usuario_id' => $usuarioId])
                ->with(['cliente', 'formaPagamento']),
            'pagination' => ['pageSize' => 20],
            'sort' => ['defaultOrder' => ['data_criacao' => SORT_DESC]],
        ]);

        // Registra assets específicos (serão criados)
        $this->view->registerCssFile(
            '@web/css/venda-list.css',
            ['depends' => [\yii\web\YiiAsset::class]]
        );

        $this->view->registerJsFile(
            '@web/js/venda-list.js',
            ['depends' => [\yii\web\JqueryAsset::class]]
        );

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
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
                'nome' => $item->produto ? $item->produto->nome : 'Produto',
                'quantidade' => (float) $item->quantidade,
                'preco' => (float) $item->preco_unitario_venda,
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

        return [
            'id' => $model->id,
            'usuario_id' => $model->usuario_id, // Identificador da loja
            'valor_total' => (float) $model->valor_total,
            'data_criacao' => $model->data_criacao,
            'status' => $model->status_venda_codigo, // Ex: QUITADA
            'forma_pagamento' => $model->formaPagamento ? $model->formaPagamento->nome : 'N/A',
            'cliente' => $cliente,
            'itens' => $itens,
        ];
    }

    /**
     * Gera PDF customizado (80mm) para bobina térmica usando FPDF (NFePHP Legacy)
     * Proporciona controle total sobre margens (0mm) e layout profissional de PDV
     */
    public function actionImprimir($id)
    {
        $venda = $this->findModel($id);
        $apiCmd = new \app\modules\api\controllers\UsuarioController('api', $this->module);
        $empresa = $apiCmd->actionDadosLoja($venda->usuario_id);

        // Instancia PDF customizado (80mm x 250mm - altura dinâmica seria ideal, mas fixamos uma segura)
        $pdf = new \NFePHP\DA\Legacy\Pdf('P', 'mm', [80, 250]);
        $pdf->SetMargins(0, 5, 0); // Margens Laterais ZERO, Superior 5mm
        $pdf->SetAutoPageBreak(true, 5);
        $pdf->AddPage();

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);

        // --- CABEÇALHO ---
        $pdf->SetFont('Courier', 'B', 12);
        $pdf->Cell(80, 6, mb_convert_encoding($empresa['nome_loja'] ?? $empresa['nome'] ?? 'LOJA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        $pdf->SetFont('Courier', '', 9);
        if (!empty($empresa['cpf_cnpj'])) {
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
        $pdf->Cell(80, 6, 'COMPROVANTE DE VENDA', 0, 1, 'C');

        $pdf->SetFont('Courier', '', 10);
        $pdf->Cell(80, 4, date('d/m/Y H:i:s', strtotime($venda->data_criacao)), 0, 1, 'C');

        $pdf->SetFont('Courier', 'B', 10);
        $pdf->Cell(80, 6, mb_convert_encoding('VENDA N°: ' . strtoupper(substr($venda->id, 0, 8)), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');
        $pdf->Ln(2);

        // --- ITENS ---
        $pdf->SetFont('Courier', 'B', 10);
        foreach ($venda->itens as $index => $item) {
            // Nome do Produto (Negrito, ocupa toda linha)
            $pdf->SetFont('Courier', 'B', 10);
            $nome = strtoupper(mb_convert_encoding($item->produto ? $item->produto->nome : 'PRODUTO', 'ISO-8859-1', 'UTF-8'));
            $pdf->MultiCell(80, 4, $nome, 0, 'L');

            // Detalhes (Quantidade x Preço e Total na direita)
            $pdf->SetFont('Courier', '', 9);
            $detalhes = number_format($item->quantidade, 2, ',', '.') . ' x R$ ' . number_format($item->preco_unitario_venda, 2, ',', '.');
            $totalItem = 'R$ ' . number_format($item->valor_total_item, 2, ',', '.');

            $pdf->Cell(40, 4, $detalhes, 0, 0, 'L');
            $pdf->Cell(40, 4, $totalItem, 0, 1, 'R');
            $pdf->Ln(1);
        }

        $pdf->Ln(2);
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');

        // --- TOTAIS ---
        $pdf->SetFont('Courier', '', 10);
        $pdf->Cell(40, 6, 'SUBTOTAL:', 0, 0, 'L');
        $pdf->Cell(40, 6, 'R$ ' . number_format($venda->valor_total, 2, ',', '.'), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetFont('Courier', 'B', 12);
        $pdf->Cell(80, 6, 'TOTAL: R$ ' . number_format($venda->valor_total, 2, ',', '.'), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetFont('Courier', '', 10);
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');

        // --- PAGAMENTO ---
        $pdf->Ln(2);
        $pdf->SetFont('Courier', 'B', 10);
        $pdf->Cell(80, 5, mb_convert_encoding('FORMA DE PAGAMENTO: ' . ($venda->formaPagamento ? $venda->formaPagamento->nome : 'DINHEIRO'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

        $pdf->SetFont('Courier', 'U', 10);
        $pdf->Cell(80, 5, mb_convert_encoding('VALOR PAGO: R$ ' . number_format($venda->valor_total, 2, ',', '.'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

        $pdf->Ln(2);
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');
        $pdf->Cell(80, 2, '------------------------------------------', 0, 1, 'C');

        // --- RODAPÉ ---
        $pdf->Ln(4);
        $pdf->SetFont('Courier', '', 10);
        $pdf->Cell(80, 5, mb_convert_encoding('Obrigado pela preferência!', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Cell(80, 5, mb_convert_encoding($empresa['nome_loja'] ?? $empresa['nome'] ?? '', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Cell(80, 5, mb_convert_encoding($empresa['nome_loja'] ?? $empresa['nome'] ?? '', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->Cell(80, 2, '==========================================', 0, 1, 'C');

        // Gera o PDF como string - Corrigido assinatura para esta versão do FPDF
        $pdfData = $pdf->output('', 'S');

        // Limpa qualquer buffer de saída para evitar corromper o PDF
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Output direto para garantir integridade do binário
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="comprovante.pdf"');
        header('Content-Length: ' . strlen($pdfData));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfData;
        exit;
    }

    protected function findModel($id)
    {
        if (($model = Venda::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Venda não encontrada.');
    }
}
