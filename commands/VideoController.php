<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use app\modules\vendas\services\VideoGeneratorService;

/**
 * Comando de console para geração automatizada de vídeos promocionais curtos de produtos.
 */
class VideoController extends Controller
{
    public $template = 'modern_dark';
    public $cor = 'dark';
    public $sync = 1; // 1 = Síncrono (executa direto no terminal), 0 = Enfileirar no yii2-queue

    /**
     * Mapeamento de opções CLI aceitas pelo comando.
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'template',
            'cor',
            'sync'
        ]);
    }

    /**
     * Gera um vídeo promocional curto no formato vertical 9:16 (1080x1920) para o produto.
     * Uso: php yii video/generate <id_produto> [duracao] [--template=modern_dark] [--cor=dark] [--sync=1]
     *
     * @param string $id ID (UUID) do produto
     * @param int $duracao Duração em segundos (5, 10 ou 15)
     * @return int ExitCode
     */
    public function actionGenerate($id, $duracao = 15)
    {
        $duracaoInt = in_array((int)$duracao, [5, 10, 15]) ? (int)$duracao : 15;
        $idClean = trim((string)$id);

        $this->stdout("----------------------------------------------------------------------\n", Console::FG_CYAN);
        $this->stdout("🎬 INICIANDO GERAÇÃO DE VÍDEO PROMOCIONAL (9:16 VERTICAL)\n", Console::FG_CYAN, Console::BOLD);
        $this->stdout("----------------------------------------------------------------------\n", Console::FG_CYAN);
        $this->stdout("ID do Produto: {$id}\n");
        $this->stdout("Duração: {$duracaoInt}s | Template: {$this->template} | Tema: {$this->cor}\n");
        $this->stdout("Modo: " . ($this->sync ? "Síncrono (Terminal Direct)" : "Assíncrono (Yii2 Queue)") . "\n\n");

        $options = [
            'template' => $this->template,
            'corTema' => $this->cor,
        ];

        try {
            $service = new VideoGeneratorService();
            $videoModel = $service->solicitarGeracaoVideo($id, $duracaoInt, $options, (bool)$this->sync);

            $this->stdout("✅ Operação concluída com sucesso!\n\n", Console::FG_GREEN, Console::BOLD);
            $this->stdout("ID do Vídeo: " . $videoModel->id . "\n");
            $this->stdout("Status: " . $videoModel->status . "\n");
            $this->stdout("Duração: " . $videoModel->duracao . "s\n");

            if (!empty($videoModel->video_path)) {
                $this->stdout("Caminho do Arquivo MP4: " . $videoModel->video_path . "\n", Console::FG_YELLOW);
                $this->stdout("URL Pública: " . $videoModel->getUrlCompleta() . "\n", Console::FG_BLUE);
            } else if ($videoModel->status === 'pendente') {
                $this->stdout("ℹ️ Vídeo enfileirado no yii2-queue. Execute `php yii queue/run` para processar a fila.\n", Console::FG_YELLOW);
            }

            return ExitCode::OK;

        } catch (\Exception $e) {
            $this->stderr("❌ Erro ao gerar vídeo promocional: " . $e->getMessage() . "\n", Console::FG_RED, Console::BOLD);
            if (YII_DEBUG) {
                $this->stderr($e->getTraceAsString() . "\n");
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
