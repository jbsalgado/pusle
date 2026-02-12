<?php

namespace app\modules\contas_pagar\controllers;

use Yii;
use app\modules\contas_pagar\models\ContaPagar;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

/**
 * RelatorioController - Relatórios de Contas a Pagar
 */
class RelatorioController extends Controller
{
    /**
     * {@inheritdoc}
     */
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
        ];
    }

    /**
     * Página principal de relatórios
     * @return string
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        // Estatísticas gerais
        $stats = [
            'total_pendente' => ContaPagar::find()
                ->where(['usuario_id' => $usuarioId, 'status' => ContaPagar::STATUS_PENDENTE])
                ->sum('valor') ?: 0,

            'total_vencidas' => ContaPagar::find()
                ->where(['usuario_id' => $usuarioId])
                ->andWhere(['<', 'data_vencimento', date('Y-m-d')])
                ->andWhere(['status' => ContaPagar::STATUS_PENDENTE])
                ->sum('valor') ?: 0,

            'qtd_vencidas' => ContaPagar::find()
                ->where(['usuario_id' => $usuarioId])
                ->andWhere(['<', 'data_vencimento', date('Y-m-d')])
                ->andWhere(['status' => ContaPagar::STATUS_PENDENTE])
                ->count(),

            'proximos_7_dias' => ContaPagar::find()
                ->where(['usuario_id' => $usuarioId, 'status' => ContaPagar::STATUS_PENDENTE])
                ->andWhere(['>=', 'data_vencimento', date('Y-m-d')])
                ->andWhere(['<=', 'data_vencimento', date('Y-m-d', strtotime('+7 days'))])
                ->sum('valor') ?: 0,

            'total_pago_mes' => ContaPagar::find()
                ->where(['usuario_id' => $usuarioId, 'status' => ContaPagar::STATUS_PAGA])
                ->andWhere(['>=', 'data_pagamento', date('Y-m-01')])
                ->andWhere(['<=', 'data_pagamento', date('Y-m-t')])
                ->sum('valor') ?: 0,
        ];

        return $this->render('index', [
            'stats' => $stats,
        ]);
    }

    /**
     * Relatório de contas a vencer
     * @return string
     */
    public function actionAVencer()
    {
        $usuarioId = Yii::$app->user->id;
        $dias = Yii::$app->request->get('dias', 30); // Padrão: 30 dias

        $dataLimite = date('Y-m-d', strtotime("+{$dias} days"));

        $query = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['status' => ContaPagar::STATUS_PENDENTE])
            ->andWhere(['>=', 'data_vencimento', date('Y-m-d')])
            ->andWhere(['<=', 'data_vencimento', $dataLimite])
            ->orderBy(['data_vencimento' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        $totalValor = $query->sum('valor') ?: 0;

        return $this->render('a-vencer', [
            'dataProvider' => $dataProvider,
            'dias' => $dias,
            'totalValor' => $totalValor,
        ]);
    }

    /**
     * Relatório de contas vencidas
     * @return string
     */
    public function actionVencidas()
    {
        $usuarioId = Yii::$app->user->id;

        $query = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['<', 'data_vencimento', date('Y-m-d')])
            ->andWhere(['status' => ContaPagar::STATUS_PENDENTE])
            ->orderBy(['data_vencimento' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        $totalValor = $query->sum('valor') ?: 0;

        return $this->render('vencidas', [
            'dataProvider' => $dataProvider,
            'totalValor' => $totalValor,
        ]);
    }

    /**
     * Relatório por fornecedor
     * @return string
     */
    public function actionPorFornecedor()
    {
        $usuarioId = Yii::$app->user->id;
        $fornecedorId = Yii::$app->request->get('fornecedor_id');

        $query = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['data_vencimento' => SORT_DESC]);

        if ($fornecedorId) {
            $query->andWhere(['fornecedor_id' => $fornecedorId]);
        }

        // Agrupamento por fornecedor
        $resumo = ContaPagar::find()
            ->select([
                'fornecedor_id',
                'COUNT(*) as total_contas',
                'SUM(CASE WHEN status = \'PENDENTE\' THEN valor ELSE 0 END) as total_pendente',
                'SUM(CASE WHEN status = \'PAGA\' THEN valor ELSE 0 END) as total_pago',
                'SUM(valor) as total_geral',
            ])
            ->where(['usuario_id' => $usuarioId])
            ->groupBy('fornecedor_id')
            ->asArray()
            ->all();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('por-fornecedor', [
            'dataProvider' => $dataProvider,
            'resumo' => $resumo,
            'fornecedorId' => $fornecedorId,
        ]);
    }

    /**
     * Relatório de fluxo de pagamentos (mensal)
     * @return string
     */
    public function actionFluxo()
    {
        $usuarioId = Yii::$app->user->id;
        $ano = Yii::$app->request->get('ano', date('Y'));
        $mes = Yii::$app->request->get('mes', date('m'));

        $dataInicio = "{$ano}-{$mes}-01";
        $dataFim = date('Y-m-t', strtotime($dataInicio));

        // Contas pagas no período
        $contasPagas = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId, 'status' => ContaPagar::STATUS_PAGA])
            ->andWhere(['>=', 'data_pagamento', $dataInicio])
            ->andWhere(['<=', 'data_pagamento', $dataFim])
            ->orderBy(['data_pagamento' => SORT_ASC])
            ->all();

        // Contas com vencimento no período
        $contasVencimento = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId])
            ->andWhere(['>=', 'data_vencimento', $dataInicio])
            ->andWhere(['<=', 'data_vencimento', $dataFim])
            ->orderBy(['data_vencimento' => SORT_ASC])
            ->all();

        $totalPago = array_sum(array_column($contasPagas, 'valor'));
        $totalPrevisto = array_sum(array_column($contasVencimento, 'valor'));

        return $this->render('fluxo', [
            'contasPagas' => $contasPagas,
            'contasVencimento' => $contasVencimento,
            'totalPago' => $totalPago,
            'totalPrevisto' => $totalPrevisto,
            'ano' => $ano,
            'mes' => $mes,
        ]);
    }

    /**
     * Exporta relatório para PDF
     * @param string $tipo Tipo de relatório (a-vencer, vencidas, por-fornecedor)
     * @return mixed
     */
    public function actionExportPdf($tipo = 'a-vencer')
    {
        $usuarioId = Yii::$app->user->id;

        // Configuração básica para PDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
        ]);

        $html = '';
        $filename = '';

        switch ($tipo) {
            case 'a-vencer':
                $dias = Yii::$app->request->get('dias', 30);
                $dataLimite = date('Y-m-d', strtotime("+{$dias} days"));

                $contas = ContaPagar::find()
                    ->where(['usuario_id' => $usuarioId, 'status' => ContaPagar::STATUS_PENDENTE])
                    ->andWhere(['>=', 'data_vencimento', date('Y-m-d')])
                    ->andWhere(['<=', 'data_vencimento', $dataLimite])
                    ->orderBy(['data_vencimento' => SORT_ASC])
                    ->all();

                $html = $this->renderPartial('pdf/a-vencer', [
                    'contas' => $contas,
                    'dias' => $dias,
                ]);
                $filename = "contas_a_vencer_{$dias}dias_" . date('Y-m-d') . ".pdf";
                break;

            case 'vencidas':
                $contas = ContaPagar::find()
                    ->where(['usuario_id' => $usuarioId])
                    ->andWhere(['<', 'data_vencimento', date('Y-m-d')])
                    ->andWhere(['status' => ContaPagar::STATUS_PENDENTE])
                    ->orderBy(['data_vencimento' => SORT_ASC])
                    ->all();

                $html = $this->renderPartial('pdf/vencidas', [
                    'contas' => $contas,
                ]);
                $filename = "contas_vencidas_" . date('Y-m-d') . ".pdf";
                break;

            case 'por-fornecedor':
                $fornecedorId = Yii::$app->request->get('fornecedor_id');

                $query = ContaPagar::find()
                    ->where(['usuario_id' => $usuarioId])
                    ->orderBy(['data_vencimento' => SORT_DESC]);

                if ($fornecedorId) {
                    $query->andWhere(['fornecedor_id' => $fornecedorId]);
                }

                $contas = $query->all();

                $html = $this->renderPartial('pdf/por-fornecedor', [
                    'contas' => $contas,
                    'fornecedorId' => $fornecedorId,
                ]);
                $filename = "contas_por_fornecedor_" . date('Y-m-d') . ".pdf";
                break;
        }

        $mpdf->WriteHTML($html);
        return $mpdf->Output($filename, 'D'); // D = Download
    }

    /**
     * Exporta relatório para Excel
     * @param string $tipo Tipo de relatório (a-vencer, vencidas, por-fornecedor)
     * @return mixed
     */
    public function actionExportExcel($tipo = 'a-vencer')
    {
        $usuarioId = Yii::$app->user->id;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Estilo do cabeçalho
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $filename = '';

        switch ($tipo) {
            case 'a-vencer':
                $dias = Yii::$app->request->get('dias', 30);
                $dataLimite = date('Y-m-d', strtotime("+{$dias} days"));

                $contas = ContaPagar::find()
                    ->where(['usuario_id' => $usuarioId, 'status' => ContaPagar::STATUS_PENDENTE])
                    ->andWhere(['>=', 'data_vencimento', date('Y-m-d')])
                    ->andWhere(['<=', 'data_vencimento', $dataLimite])
                    ->orderBy(['data_vencimento' => SORT_ASC])
                    ->all();

                // Título
                $sheet->setCellValue('A1', "Contas a Vencer - Próximos {$dias} dias");
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                // Cabeçalhos
                $sheet->setCellValue('A3', 'Descrição');
                $sheet->setCellValue('B3', 'Fornecedor');
                $sheet->setCellValue('C3', 'Vencimento');
                $sheet->setCellValue('D3', 'Valor');
                $sheet->setCellValue('E3', 'Categoria');
                $sheet->setCellValue('F3', 'Status');
                $sheet->getStyle('A3:F3')->applyFromArray($headerStyle);

                // Dados
                $row = 4;
                $total = 0;
                foreach ($contas as $conta) {
                    $sheet->setCellValue('A' . $row, $conta->descricao);
                    $sheet->setCellValue('B' . $row, $conta->fornecedor->nome ?? 'N/A');
                    $sheet->setCellValue('C' . $row, Yii::$app->formatter->asDate($conta->data_vencimento));
                    $sheet->setCellValue('D' . $row, $conta->valor);
                    $sheet->setCellValue('E' . $row, $conta->categoria ?? 'N/A');
                    $sheet->setCellValue('F' . $row, $conta->status);

                    $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
                    $total += $conta->valor;
                    $row++;
                }

                // Total
                $sheet->setCellValue('C' . $row, 'TOTAL:');
                $sheet->setCellValue('D' . $row, $total);
                $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');

                $filename = "contas_a_vencer_{$dias}dias_" . date('Y-m-d') . ".xlsx";
                break;

            case 'vencidas':
                $contas = ContaPagar::find()
                    ->where(['usuario_id' => $usuarioId])
                    ->andWhere(['<', 'data_vencimento', date('Y-m-d')])
                    ->andWhere(['status' => ContaPagar::STATUS_PENDENTE])
                    ->orderBy(['data_vencimento' => SORT_ASC])
                    ->all();

                // Título
                $sheet->setCellValue('A1', 'Contas Vencidas');
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                // Cabeçalhos
                $sheet->setCellValue('A3', 'Descrição');
                $sheet->setCellValue('B3', 'Fornecedor');
                $sheet->setCellValue('C3', 'Vencimento');
                $sheet->setCellValue('D3', 'Dias Atraso');
                $sheet->setCellValue('E3', 'Valor');
                $sheet->setCellValue('F3', 'Categoria');
                $sheet->setCellValue('G3', 'Status');
                $sheet->getStyle('A3:G3')->applyFromArray($headerStyle);

                // Dados
                $row = 4;
                $total = 0;
                foreach ($contas as $conta) {
                    $diasAtraso = (new \DateTime())->diff(new \DateTime($conta->data_vencimento))->days;

                    $sheet->setCellValue('A' . $row, $conta->descricao);
                    $sheet->setCellValue('B' . $row, $conta->fornecedor->nome ?? 'N/A');
                    $sheet->setCellValue('C' . $row, Yii::$app->formatter->asDate($conta->data_vencimento));
                    $sheet->setCellValue('D' . $row, $diasAtraso);
                    $sheet->setCellValue('E' . $row, $conta->valor);
                    $sheet->setCellValue('F' . $row, $conta->categoria ?? 'N/A');
                    $sheet->setCellValue('G' . $row, $conta->status);

                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');

                    // Destacar em vermelho se muito atrasado
                    if ($diasAtraso > 30) {
                        $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->getColor()->setRGB('FF0000');
                    }

                    $total += $conta->valor;
                    $row++;
                }

                // Total
                $sheet->setCellValue('D' . $row, 'TOTAL:');
                $sheet->setCellValue('E' . $row, $total);
                $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');

                $filename = "contas_vencidas_" . date('Y-m-d') . ".xlsx";
                break;

            case 'por-fornecedor':
                $fornecedorId = Yii::$app->request->get('fornecedor_id');

                $query = ContaPagar::find()
                    ->where(['usuario_id' => $usuarioId])
                    ->orderBy(['data_vencimento' => SORT_DESC]);

                if ($fornecedorId) {
                    $query->andWhere(['fornecedor_id' => $fornecedorId]);
                }

                $contas = $query->all();

                // Título
                $sheet->setCellValue('A1', 'Relatório por Fornecedor');
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                // Cabeçalhos
                $sheet->setCellValue('A3', 'Fornecedor');
                $sheet->setCellValue('B3', 'Descrição');
                $sheet->setCellValue('C3', 'Vencimento');
                $sheet->setCellValue('D3', 'Valor');
                $sheet->setCellValue('E3', 'Status');
                $sheet->setCellValue('F3', 'Data Pagamento');
                $sheet->getStyle('A3:F3')->applyFromArray($headerStyle);

                // Dados
                $row = 4;
                $totalPendente = 0;
                $totalPago = 0;
                foreach ($contas as $conta) {
                    $sheet->setCellValue('A' . $row, $conta->fornecedor->nome ?? 'N/A');
                    $sheet->setCellValue('B' . $row, $conta->descricao);
                    $sheet->setCellValue('C' . $row, Yii::$app->formatter->asDate($conta->data_vencimento));
                    $sheet->setCellValue('D' . $row, $conta->valor);
                    $sheet->setCellValue('E' . $row, $conta->status);
                    $sheet->setCellValue('F' . $row, $conta->data_pagamento ? Yii::$app->formatter->asDate($conta->data_pagamento) : '-');

                    $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');

                    if ($conta->status === ContaPagar::STATUS_PENDENTE) {
                        $totalPendente += $conta->valor;
                    } else {
                        $totalPago += $conta->valor;
                    }

                    $row++;
                }

                // Totais
                $row++;
                $sheet->setCellValue('C' . $row, 'Total Pendente:');
                $sheet->setCellValue('D' . $row, $totalPendente);
                $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');

                $row++;
                $sheet->setCellValue('C' . $row, 'Total Pago:');
                $sheet->setCellValue('D' . $row, $totalPago);
                $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');

                $filename = "contas_por_fornecedor_" . date('Y-m-d') . ".xlsx";
                break;
        }

        // Auto-ajustar largura das colunas
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Gerar arquivo
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
