<?php
// Salve em: app/modules/indicadores/controllers/BusinessDashboardController.php

namespace app\modules\indicadores\controllers;

use yii\web\Controller;
use app\modules\indicadores\models\IndDefinicoesIndicadores as IndicadorModel;
use Yii;

class BusinessDashboardController extends Controller
{
    /**
     * Função auxiliar genérica para buscar dados de um conjunto de indicadores.
     * @param array $codigos
     * @return array
     */
    private function getDadosParaDashboard(array $codigos): array
    {
        $dadosIndicadores = [];

        foreach ($codigos as $codigo) {
            // Assumimos que a função getDashboardData no seu Model busca os dados
            // necessários (definição, último valor, histórico, metas).
            $data = IndicadorModel::getDashboardData($codigo);

            if (!empty($data)) {
                // Prepara os dados especificamente para o gráfico de evolução
                $historicoChart = [
                    'labels' => [],
                    'values' => [],
                ];
                if (!empty($data['historico'])) {
                    foreach ($data['historico'] as $ponto) {
                        $historicoChart['labels'][] = Yii::$app->formatter->asDate($ponto['data_referencia'], 'MMM/yy');
                        $historicoChart['values'][] = (float) $ponto['valor'];
                    }
                }
                $data['historicoChart'] = $historicoChart;
                
                // Armazena todos os dados do indicador, usando o código como chave
                $dadosIndicadores[$codigo] = $data;
            }
        }
        
        return $dadosIndicadores;
    }

    /**
     * Action para o Dashboard de Farmácia.
     */
    public function actionFarmacia()
    {
        $codigos = [
            'FARM_VT',      // Vendas Totais
            'FARM_TM',      // Ticket Médio
            'FARM_ML',      // Margem de Lucro
            'FARM_GE',      // Giro de Estoque
            // Adicione outros códigos de farmácia aqui se necessário
        ];

        $dadosIndicadores = $this->getDadosParaDashboard($codigos);

        return $this->render('farmacia', [
            'dadosIndicadores' => $dadosIndicadores,
        ]);
    }

    /**
     * Action para o Dashboard de Loja de Tecidos.
     */
    public function actionLojaTecidos()
    {
        $codigos = [
            'TEC_VT',       // Vendas Totais
            'TEC_MV',       // Número de Metros Vendidos
            'TEC_IRC',      // Índice de Retorno de Clientes
             // Adicione outros códigos de tecidos aqui se necessário
        ];

        $dadosIndicadores = $this->getDadosParaDashboard($codigos);

        return $this->render('loja-tecidos', [
            'dadosIndicadores' => $dadosIndicadores,
        ]);
    }

    /**
     * Action para o Dashboard de Lavanderia Industrial.
     */
    public function actionLavanderia()
    {
        $codigos = [
            'LAV_VRP',      // Volume de Roupas Processadas
            'LAV_CKRL',     // Custo por Quilo de Roupa Lavada
            'LAV_TR',       // Taxa de Retrabalho
            'LAV_CA',       // Consumo de Água por Quilo de Roupa
             // Adicione outros códigos de lavanderia aqui se necessário
        ];

        $dadosIndicadores = $this->getDadosParaDashboard($codigos);

        return $this->render('lavanderia', [
            'dadosIndicadores' => $dadosIndicadores,
        ]);
    }
}