<?php

namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use app\modules\vendas\services\VideoGeneratorService;

/**
 * Job assíncrono para renderizar vídeo promocional de produto em background.
 */
class GenerateProductVideoJob extends BaseObject implements JobInterface
{
    /**
     * @var string ID do registro ProdutoVideo (UUID)
     */
    public $videoId;

    /**
     * @var array Opções visuais adicionais (template, corTema, etc)
     */
    public $options = [];

    /**
     * Executado pelo worker do yii2-queue em segundo plano.
     *
     * @param \yii\queue\Queue $queue
     */
    public function execute($queue)
    {
        Yii::info("Iniciando Job de geração de vídeo para ProdutoVideo ID: {$this->videoId}", __METHOD__);

        try {
            $service = new VideoGeneratorService();
            $service->processarGeracaoVideo($this->videoId, $this->options);
            Yii::info("Job de geração de vídeo concluído com sucesso para ID: {$this->videoId}", __METHOD__);
        } catch (\Exception $e) {
            Yii::error("Erro durante execução do Job GenerateProductVideoJob para ID {$this->videoId}: " . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }
}
