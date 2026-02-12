<?php

namespace app\modules\caixa\controllers;

use Yii;
use app\modules\caixa\models\Caixa;
use app\modules\caixa\models\CaixaMovimentacao;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

/**
 * RelatorioController - Relatórios de Caixa
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
        $caixaAberto = Caixa::find()
            ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
            ->one();

        $stats = [
            'caixa_aberto' => $caixaAberto ? true : false,
            'saldo_atual' => $caixaAberto ? $caixaAberto->saldo_atual : 0,

            'entradas_hoje' => CaixaMovimentacao::find()
                ->joinWith('caixa')
                ->where(['prest_caixa.usuario_id' => $usuarioId])
                ->andWhere(['tipo_movimentacao' => CaixaMovimentacao::TIPO_ENTRADA])
                ->andWhere(['>=', 'prest_caixa_movimentacao.data_movimentacao', date('Y-m-d 00:00:00')])
                ->sum('valor') ?: 0,

            'saidas_hoje' => CaixaMovimentacao::find()
                ->joinWith('caixa')
                ->where(['prest_caixa.usuario_id' => $usuarioId])
                ->andWhere(['tipo_movimentacao' => CaixaMovimentacao::TIPO_SAIDA])
                ->andWhere(['>=', 'prest_caixa_movimentacao.data_movimentacao', date('Y-m-d 00:00:00')])
                ->sum('valor') ?: 0,

            'total_mes' => CaixaMovimentacao::find()
                ->joinWith('caixa')
                ->where(['prest_caixa.usuario_id' => $usuarioId])
                ->andWhere(['tipo_movimentacao' => CaixaMovimentacao::TIPO_ENTRADA])
                ->andWhere(['>=', 'prest_caixa_movimentacao.data_movimentacao', date('Y-m-01')])
                ->sum('valor') ?: 0,
        ];

        return $this->render('index', [
            'stats' => $stats,
            'caixaAberto' => $caixaAberto,
        ]);
    }

    /**
     * Relatório de fechamento de caixa
     * @param string $id ID do caixa
     * @return string
     */
    public function actionFechamento($id = null)
    {
        $usuarioId = Yii::$app->user->id;

        if ($id) {
            $caixa = Caixa::findOne(['id' => $id, 'usuario_id' => $usuarioId]);
        } else {
            // Busca último caixa fechado
            $caixa = Caixa::find()
                ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_FECHADO])
                ->orderBy(['data_fechamento' => SORT_DESC])
                ->one();
        }

        if (!$caixa) {
            Yii::$app->session->setFlash('error', 'Nenhum caixa encontrado.');
            return $this->redirect(['index']);
        }

        // Movimentações do caixa
        $movimentacoes = CaixaMovimentacao::find()
            ->where(['caixa_id' => $caixa->id])
            ->orderBy(['data_movimentacao' => SORT_ASC])
            ->all();

        // Totais por tipo
        $totalEntradas = 0;
        $totalSaidas = 0;
        foreach ($movimentacoes as $mov) {
            if ($mov->tipo_movimentacao === CaixaMovimentacao::TIPO_ENTRADA) {
                $totalEntradas += $mov->valor;
            } else {
                $totalSaidas += $mov->valor;
            }
        }

        return $this->render('fechamento', [
            'caixa' => $caixa,
            'movimentacoes' => $movimentacoes,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
        ]);
    }

    /**
     * Relatório de movimentações por período
     * @return string
     */
    public function actionMovimentacoes()
    {
        $usuarioId = Yii::$app->user->id;
        $dataInicio = Yii::$app->request->get('data_inicio', date('Y-m-01'));
        $dataFim = Yii::$app->request->get('data_fim', date('Y-m-d'));

        $query = CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['>=', 'prest_caixa_movimentacao.data_movimentacao', $dataInicio . ' 00:00:00'])
            ->andWhere(['<=', 'prest_caixa_movimentacao.data_movimentacao', $dataFim . ' 23:59:59'])
            ->orderBy(['prest_caixa_movimentacao.data_movimentacao' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        // Totais
        $totalEntradas = CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['tipo_movimentacao' => CaixaMovimentacao::TIPO_ENTRADA])
            ->andWhere(['>=', 'prest_caixa_movimentacao.data_movimentacao', $dataInicio . ' 00:00:00'])
            ->andWhere(['<=', 'prest_caixa_movimentacao.data_movimentacao', $dataFim . ' 23:59:59'])
            ->sum('valor') ?: 0;

        $totalSaidas = CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['tipo_movimentacao' => CaixaMovimentacao::TIPO_SAIDA])
            ->andWhere(['>=', 'prest_caixa_movimentacao.data_movimentacao', $dataInicio . ' 00:00:00'])
            ->andWhere(['<=', 'prest_caixa_movimentacao.data_movimentacao', $dataFim . ' 23:59:59'])
            ->sum('valor') ?: 0;

        return $this->render('movimentacoes', [
            'dataProvider' => $dataProvider,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
        ]);
    }

    /**
     * Relatório por categoria
     * @return string
     */
    public function actionPorCategoria()
    {
        $usuarioId = Yii::$app->user->id;
        $mes = Yii::$app->request->get('mes', date('Y-m'));

        $dataInicio = $mes . '-01';
        $dataFim = date('Y-m-t', strtotime($dataInicio));

        // Agrupamento por categoria
        $entradas = CaixaMovimentacao::find()
            ->select([
                'categoria',
                'COUNT(*) as total_movimentacoes',
                'SUM(valor) as total_valor',
            ])
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['tipo_movimentacao' => CaixaMovimentacao::TIPO_ENTRADA])
            ->andWhere(['>=', 'prest_caixa_movimentacao.data_movimentacao', $dataInicio])
            ->andWhere(['<=', 'prest_caixa_movimentacao.data_movimentacao', $dataFim])
            ->groupBy('categoria')
            ->asArray()
            ->all();

        $saidas = CaixaMovimentacao::find()
            ->select([
                'categoria',
                'COUNT(*) as total_movimentacoes',
                'SUM(valor) as total_valor',
            ])
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['tipo_movimentacao' => CaixaMovimentacao::TIPO_SAIDA])
            ->andWhere(['>=', 'prest_caixa_movimentacao.data_movimentacao', $dataInicio])
            ->andWhere(['<=', 'prest_caixa_movimentacao.data_movimentacao', $dataFim])
            ->groupBy('categoria')
            ->asArray()
            ->all();

        return $this->render('por-categoria', [
            'entradas' => $entradas,
            'saidas' => $saidas,
            'mes' => $mes,
        ]);
    }

    /**
     * Exporta relatório para PDF
     * @param string $tipo Tipo de relatório
     * @return mixed
     */
    public function actionExportPdf($tipo = 'fechamento')
    {
        $usuarioId = Yii::$app->user->id;

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
            case 'fechamento':
                $caixaId = Yii::$app->request->get('id');
                $caixa = Caixa::findOne(['id' => $caixaId, 'usuario_id' => $usuarioId]);

                if (!$caixa) {
                    throw new \yii\web\NotFoundHttpException('Caixa não encontrado.');
                }

                $movimentacoes = CaixaMovimentacao::find()
                    ->where(['caixa_id' => $caixa->id])
                    ->orderBy(['data_movimentacao' => SORT_ASC])
                    ->all();

                $html = $this->renderPartial('pdf/fechamento', [
                    'caixa' => $caixa,
                    'movimentacoes' => $movimentacoes,
                ]);
                $filename = "fechamento_caixa_" . date('Y-m-d', strtotime($caixa->data_abertura)) . ".pdf";
                break;

            case 'movimentacoes':
                $dataInicio = Yii::$app->request->get('data_inicio', date('Y-m-01'));
                $dataFim = Yii::$app->request->get('data_fim', date('Y-m-d'));

                $movimentacoes = CaixaMovimentacao::find()
                    ->joinWith('caixa')
                    ->where(['prest_caixa.usuario_id' => $usuarioId])
                    ->andWhere(['>=', 'prest_caixa_movimentacao.data_movimentacao', $dataInicio])
                    ->andWhere(['<=', 'prest_caixa_movimentacao.data_movimentacao', $dataFim])
                    ->orderBy(['prest_caixa_movimentacao.data_movimentacao' => SORT_DESC])
                    ->all();

                $html = $this->renderPartial('pdf/movimentacoes', [
                    'movimentacoes' => $movimentacoes,
                    'dataInicio' => $dataInicio,
                    'dataFim' => $dataFim,
                ]);
                $filename = "movimentacoes_caixa_{$dataInicio}_a_{$dataFim}.pdf";
                break;
        }

        $mpdf->WriteHTML($html);
        return $mpdf->Output($filename, 'D');
    }

    /**
     * Exporta relatório para Excel
     * @param string $tipo Tipo de relatório
     * @return mixed
     */
    public function actionExportExcel($tipo = 'movimentacoes')
    {
        $usuarioId = Yii::$app->user->id;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $filename = '';

        switch ($tipo) {
            case 'movimentacoes':
                $dataInicio = Yii::$app->request->get('data_inicio', date('Y-m-01'));
                $dataFim = Yii::$app->request->get('data_fim', date('Y-m-d'));

                $movimentacoes = CaixaMovimentacao::find()
                    ->joinWith('caixa')
                    ->where(['prest_caixa.usuario_id' => $usuarioId])
                    ->andWhere(['>=', 'prest_caixa_movimentacao.data_movimentacao', $dataInicio])
                    ->andWhere(['<=', 'prest_caixa_movimentacao.data_movimentacao', $dataFim])
                    ->orderBy(['prest_caixa_movimentacao.data_movimentacao' => SORT_DESC])
                    ->all();

                // Título
                $sheet->setCellValue('A1', "Movimentações de Caixa - {$dataInicio} a {$dataFim}");
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                // Cabeçalhos
                $sheet->setCellValue('A3', 'Data');
                $sheet->setCellValue('B3', 'Tipo');
                $sheet->setCellValue('C3', 'Categoria');
                $sheet->setCellValue('D3', 'Descrição');
                $sheet->setCellValue('E3', 'Valor');
                $sheet->setCellValue('F3', 'Forma Pagamento');
                $sheet->getStyle('A3:F3')->applyFromArray($headerStyle);

                // Dados
                $row = 4;
                $totalEntradas = 0;
                $totalSaidas = 0;
                foreach ($movimentacoes as $mov) {
                    $sheet->setCellValue('A' . $row, Yii::$app->formatter->asDatetime($mov->data_movimentacao));
                    $sheet->setCellValue('B' . $row, $mov->tipo_movimentacao === CaixaMovimentacao::TIPO_ENTRADA ? 'ENTRADA' : 'SAÍDA');
                    $sheet->setCellValue('C' . $row, $mov->categoria ?? 'N/A');
                    $sheet->setCellValue('D' . $row, $mov->descricao);
                    $sheet->setCellValue('E' . $row, $mov->valor);
                    $sheet->setCellValue('F' . $row, $mov->formaPagamento->nome ?? 'N/A');

                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');

                    // Colorir linha
                    if ($mov->tipo_movimentacao === CaixaMovimentacao::TIPO_ENTRADA) {
                        $sheet->getStyle('B' . $row)->getFont()->getColor()->setRGB('008000');
                        $totalEntradas += $mov->valor;
                    } else {
                        $sheet->getStyle('B' . $row)->getFont()->getColor()->setRGB('FF0000');
                        $totalSaidas += $mov->valor;
                    }

                    $row++;
                }

                // Totais
                $row++;
                $sheet->setCellValue('D' . $row, 'Total Entradas:');
                $sheet->setCellValue('E' . $row, $totalEntradas);
                $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
                $sheet->getStyle('E' . $row)->getFont()->getColor()->setRGB('008000');

                $row++;
                $sheet->setCellValue('D' . $row, 'Total Saídas:');
                $sheet->setCellValue('E' . $row, $totalSaidas);
                $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');
                $sheet->getStyle('E' . $row)->getFont()->getColor()->setRGB('FF0000');

                $row++;
                $sheet->setCellValue('D' . $row, 'Saldo:');
                $sheet->setCellValue('E' . $row, $totalEntradas - $totalSaidas);
                $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');

                $filename = "movimentacoes_caixa_{$dataInicio}_a_{$dataFim}.xlsx";
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
