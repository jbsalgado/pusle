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
        $usuarioId = \app\components\TenantHelper::getId();

        // Estatísticas gerais
        $caixaAberto = Caixa::find()
            ->where(['usuario_id' => $usuarioId, 'status' => Caixa::STATUS_ABERTO])
            ->one();

        // 1. Faturamento / Vendas Reais Hoje (Operacional: Vendas e Recebimentos de Clientes)
        $vendasHoje = (float)(CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_ENTRADA])
            ->andWhere(['in', 'prest_caixa_movimentacoes.categoria', [CaixaMovimentacao::CATEGORIA_VENDA, CaixaMovimentacao::CATEGORIA_PAGAMENTO]])
            ->andWhere(['is', 'prest_caixa_movimentacoes.conta_pagar_id', null])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', date('Y-m-d 00:00:00')])
            ->sum('prest_caixa_movimentacoes.valor') ?: 0);

        // 2. Aportes de Cobertura para Contas a Pagar Hoje (Não Operacional - Não é venda)
        $aportesHoje = (float)(CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_ENTRADA])
            ->andWhere(['or',
                ['prest_caixa_movimentacoes.categoria' => CaixaMovimentacao::CATEGORIA_APORTE_CONTA],
                ['is not', 'prest_caixa_movimentacoes.conta_pagar_id', null]
            ])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', date('Y-m-d 00:00:00')])
            ->sum('prest_caixa_movimentacoes.valor') ?: 0);

        // 3. Total Geral de Entradas Hoje
        $entradasHoje = (float)(CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_ENTRADA])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', date('Y-m-d 00:00:00')])
            ->sum('prest_caixa_movimentacoes.valor') ?: 0);

        // 4. Saídas Hoje
        $saidasHoje = (float)(CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_SAIDA])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', date('Y-m-d 00:00:00')])
            ->sum('prest_caixa_movimentacoes.valor') ?: 0);

        // 5. Total de Vendas no Mês (Operacional)
        $totalVendasMes = (float)(CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_ENTRADA])
            ->andWhere(['in', 'prest_caixa_movimentacoes.categoria', [CaixaMovimentacao::CATEGORIA_VENDA, CaixaMovimentacao::CATEGORIA_PAGAMENTO]])
            ->andWhere(['is', 'prest_caixa_movimentacoes.conta_pagar_id', null])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', date('Y-m-01 00:00:00')])
            ->sum('prest_caixa_movimentacoes.valor') ?: 0);

        $stats = [
            'caixa_aberto' => $caixaAberto ? true : false,
            'saldo_atual' => $caixaAberto ? $caixaAberto->calcularValorEsperado() : 0,
            'vendas_hoje' => $vendasHoje,
            'aportes_hoje' => $aportesHoje,
            'entradas_hoje' => $entradasHoje,
            'saidas_hoje' => $saidasHoje,
            'total_mes' => $totalVendasMes,
            'total_entradas_mes_geral' => (float)(CaixaMovimentacao::find()
                ->joinWith('caixa')
                ->where(['prest_caixa.usuario_id' => $usuarioId])
                ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_ENTRADA])
                ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', date('Y-m-01 00:00:00')])
                ->sum('prest_caixa_movimentacoes.valor') ?: 0),
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
        $usuarioId = \app\components\TenantHelper::getId();

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
            ->orderBy(['data_movimento' => SORT_ASC])
            ->all();

        // Totais segregados
        $totalVendas = 0;
        $totalAportes = 0;
        $totalSuprimentos = 0;
        $totalEntradas = 0;
        $totalSaidas = 0;
        $totalContasPagas = 0;
        $totalSangrias = 0;

        foreach ($movimentacoes as $mov) {
            $val = (float)$mov->valor;
            if ($mov->tipo === CaixaMovimentacao::TIPO_ENTRADA) {
                $totalEntradas += $val;
                if ($mov->isAporteConta()) {
                    $totalAportes += $val;
                } elseif ($mov->categoria === CaixaMovimentacao::CATEGORIA_SUPRIMENTO) {
                    $totalSuprimentos += $val;
                } else {
                    $totalVendas += $val;
                }
            } else {
                $totalSaidas += $val;
                if ($mov->categoria === CaixaMovimentacao::CATEGORIA_CONTA_PAGAR || !empty($mov->conta_pagar_id)) {
                    $totalContasPagas += $val;
                } elseif ($mov->categoria === CaixaMovimentacao::CATEGORIA_SANGRIA) {
                    $totalSangrias += $val;
                }
            }
        }

        return $this->render('fechamento', [
            'caixa' => $caixa,
            'movimentacoes' => $movimentacoes,
            'totalVendas' => $totalVendas,
            'totalAportes' => $totalAportes,
            'totalSuprimentos' => $totalSuprimentos,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'totalContasPagas' => $totalContasPagas,
            'totalSangrias' => $totalSangrias,
        ]);
    }

    /**
     * Relatório de movimentações por período
     * @return string
     */
    public function actionMovimentacoes()
    {
        $usuarioId = \app\components\TenantHelper::getId();
        $dataInicio = Yii::$app->request->get('data_inicio', date('Y-m-01'));
        $dataFim = Yii::$app->request->get('data_fim', date('Y-m-d'));

        $query = CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', $dataInicio . ' 00:00:00'])
            ->andWhere(['<=', 'prest_caixa_movimentacoes.data_movimento', $dataFim . ' 23:59:59'])
            ->orderBy(['prest_caixa_movimentacoes.data_movimento' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        // Totais segregados
        $vendasReal = (float)(CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_ENTRADA])
            ->andWhere(['in', 'prest_caixa_movimentacoes.categoria', [CaixaMovimentacao::CATEGORIA_VENDA, CaixaMovimentacao::CATEGORIA_PAGAMENTO]])
            ->andWhere(['is', 'prest_caixa_movimentacoes.conta_pagar_id', null])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', $dataInicio . ' 00:00:00'])
            ->andWhere(['<=', 'prest_caixa_movimentacoes.data_movimento', $dataFim . ' 23:59:59'])
            ->sum('prest_caixa_movimentacoes.valor') ?: 0);

        $aportesNaoOperacional = (float)(CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_ENTRADA])
            ->andWhere(['or',
                ['prest_caixa_movimentacoes.categoria' => CaixaMovimentacao::CATEGORIA_APORTE_CONTA],
                ['is not', 'prest_caixa_movimentacoes.conta_pagar_id', null]
            ])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', $dataInicio . ' 00:00:00'])
            ->andWhere(['<=', 'prest_caixa_movimentacoes.data_movimento', $dataFim . ' 23:59:59'])
            ->sum('prest_caixa_movimentacoes.valor') ?: 0);

        $totalEntradas = (float)(CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_ENTRADA])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', $dataInicio . ' 00:00:00'])
            ->andWhere(['<=', 'prest_caixa_movimentacoes.data_movimento', $dataFim . ' 23:59:59'])
            ->sum('prest_caixa_movimentacoes.valor') ?: 0);

        $totalSaidas = (float)(CaixaMovimentacao::find()
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_SAIDA])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', $dataInicio . ' 00:00:00'])
            ->andWhere(['<=', 'prest_caixa_movimentacoes.data_movimento', $dataFim . ' 23:59:59'])
            ->sum('prest_caixa_movimentacoes.valor') ?: 0);

        return $this->render('movimentacoes', [
            'dataProvider' => $dataProvider,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'totalVendas' => $vendasReal,
            'totalAportes' => $aportesNaoOperacional,
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
        $usuarioId = \app\components\TenantHelper::getId();
        $mes = Yii::$app->request->get('mes', date('Y-m'));

        $dataInicio = $mes . '-01 00:00:00';
        $dataFim = date('Y-m-t 23:59:59', strtotime($mes . '-01'));

        // Agrupamento por categoria
        $entradas = CaixaMovimentacao::find()
            ->select([
                'categoria',
                'COUNT(*) as total_movimentacoes',
                'SUM(valor) as total_valor',
            ])
            ->joinWith('caixa')
            ->where(['prest_caixa.usuario_id' => $usuarioId])
            ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_ENTRADA])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', $dataInicio])
            ->andWhere(['<=', 'prest_caixa_movimentacoes.data_movimento', $dataFim])
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
            ->andWhere(['prest_caixa_movimentacoes.tipo' => CaixaMovimentacao::TIPO_SAIDA])
            ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', $dataInicio])
            ->andWhere(['<=', 'prest_caixa_movimentacoes.data_movimento', $dataFim])
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
        $usuarioId = \app\components\TenantHelper::getId();

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
                    ->orderBy(['data_movimento' => SORT_ASC])
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
                    ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', $dataInicio . ' 00:00:00'])
                    ->andWhere(['<=', 'prest_caixa_movimentacoes.data_movimento', $dataFim . ' 23:59:59'])
                    ->orderBy(['prest_caixa_movimentacoes.data_movimento' => SORT_DESC])
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
        $usuarioId = \app\components\TenantHelper::getId();

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
                    ->andWhere(['>=', 'prest_caixa_movimentacoes.data_movimento', $dataInicio . ' 00:00:00'])
                    ->andWhere(['<=', 'prest_caixa_movimentacoes.data_movimento', $dataFim . ' 23:59:59'])
                    ->orderBy(['prest_caixa_movimentacoes.data_movimento' => SORT_DESC])
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
                    $sheet->setCellValue('A' . $row, Yii::$app->formatter->asDatetime($mov->data_movimento));
                    $sheet->setCellValue('B' . $row, $mov->tipo === CaixaMovimentacao::TIPO_ENTRADA ? 'ENTRADA' : 'SAÍDA');
                    $sheet->setCellValue('C' . $row, $mov->getCategoriaNome());
                    $sheet->setCellValue('D' . $row, $mov->descricao);
                    $sheet->setCellValue('E' . $row, $mov->valor);
                    $sheet->setCellValue('F' . $row, $mov->formaPagamento ? $mov->formaPagamento->nome : 'N/A');

                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('R$ #,##0.00');

                    // Colorir linha
                    if ($mov->tipo === CaixaMovimentacao::TIPO_ENTRADA) {
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
