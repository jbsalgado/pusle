<?php

namespace app\modules\indicadores\controllers;

use yii\web\Controller;
// Certifique-se de que o namespace do seu modelo está correto
use app\modules\indicadores\models\IndDefinicoesIndicadores as IndicadorModel;
use Yii;

class DashboardController extends Controller
{
    public function actionIndex()
    {
        // Lista de códigos dos indicadores da APS que queremos exibir
        $codigosIndicadoresAPS = [
            'APS_C2', // Cuidado no Desenvolvimento Infantil
            'APS_C3', // Cuidado com a Gestante e Puérpera
            'APS_C4', // Cuidado da pessoa com Diabetes
            'APS_C5', // Cuidado da pessoa com Hipertensão
            'APS_C6', // Cuidado integral da Pessoa Idosa
            'APS_C7', // Prevenção do Câncer na mulher
            'APS_B1', // 1ª Consulta Odontológica
            'APS_B2', // Tratamento Odontológico concluído
            'APS_B3', // Taxa de Exodontias
            'APS_M1', // Média de atendimentos eMulti
            'APS_M2', // Ações Interprofissionais eMulti
        ];

        $dadosIndicadores = [];

        // Loop para buscar os dados de cada indicador da lista
        foreach ($codigosIndicadoresAPS as $codigo) {
            // A função getDashboardData (a ser criada ou ajustada no seu Model)
            // deve retornar todos os dados necessários para um indicador específico.
            $data = IndicadorModel::getDashboardData($codigo);

            if (!empty($data)) {
                // Prepara os dados especificamente para o gráfico de evolução
                $historicoChart = [
                    'labels' => [],
                    'values' => [],
                ];
                if (!empty($data['historico'])) {
                    foreach ($data['historico'] as $ponto) {
                        // Formata a data para Mês/Ano para o eixo X do gráfico
                        $historicoChart['labels'][] = Yii::$app->formatter->asDate($ponto['data_referencia'], 'MMM/yy');
                        $historicoChart['values'][] = (float) $ponto['valor'];
                    }
                }
                // Adiciona os dados do gráfico ao array principal do indicador
                $data['historicoChart'] = $historicoChart;
                
                // Armazena todos os dados do indicador, usando o código como chave
                $dadosIndicadores[$codigo] = $data;
            }
        }

        return $this->render('index', [
            // Passa o array completo com todos os indicadores para a view
            'dadosIndicadores' => $dadosIndicadores,
        ]);
    }
}