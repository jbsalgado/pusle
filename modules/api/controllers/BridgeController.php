<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

/**
 * Controller da Bridge Go para download de áudios do YouTube via IP residencial
 */
class BridgeController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    /**
     * Retorna o diretório de jobs temporários da Bridge
     */
    public static function getJobsDir()
    {
        $dir = Yii::getAlias('@app/runtime/bridge_jobs');
        if (!is_dir($dir)) {
            FileHelper::createDirectory($dir, 0777, true);
        }
        return $dir;
    }

    /**
     * Valida o secret da Bridge (via Header ou Query/Post)
     */
    protected function validarSecret()
    {
        $configuredSecret = Yii::$app->params['pulse_bridge_secret'] ?? 'pulse_bridge_sec_7a8f9c2d1e0b5';
        $providedSecret = Yii::$app->request->headers->get('X-Bridge-Secret') 
            ?: Yii::$app->request->get('secret') 
            ?: Yii::$app->request->post('secret');

        if (empty($providedSecret) || !hash_equals($configuredSecret, (string)$providedSecret)) {
            Yii::$app->response->statusCode = 401;
            return false;
        }
        return true;
    }

    /**
     * Atualiza o timestamp de heartbeat da Bridge
     */
    protected static function tocarHeartbeat()
    {
        $dir = self::getJobsDir();
        $pingFile = $dir . DIRECTORY_SEPARATOR . 'last_ping.txt';
        @file_put_contents($pingFile, (string)time(), LOCK_EX);
    }

    /**
     * Verifica se há algum worker Go conectado e ativo nos últimos 30 segundos
     */
    public static function isBridgeOnline()
    {
        $dir = self::getJobsDir();
        $pingFile = $dir . DIRECTORY_SEPARATOR . 'last_ping.txt';
        if (!file_exists($pingFile)) {
            return false;
        }
        $lastPing = (int)@file_get_contents($pingFile);
        return (time() - $lastPing) <= 35;
    }

    /**
     * Retorna o tempo em segundos desde o último heartbeat do worker
     */
    public static function getBridgeLastSeenSeconds()
    {
        $dir = self::getJobsDir();
        $pingFile = $dir . DIRECTORY_SEPARATOR . 'last_ping.txt';
        if (!file_exists($pingFile)) {
            return null;
        }
        $lastPing = (int)@file_get_contents($pingFile);
        return max(0, time() - $lastPing);
    }

    /**
     * Endpoint para consulta de status do worker Bridge
     * GET /api/bridge/status
     */
    public function actionStatus()
    {
        $online = self::isBridgeOnline();
        $lastSeen = self::getBridgeLastSeenSeconds();

        return [
            'success' => true,
            'online' => $online,
            'last_seen_seconds_ago' => $lastSeen,
            'message' => $online ? 'Bridge Go residencial está online e pronta.' : 'Bridge Go offline.'
        ];
    }

    /**
     * Endpoint de Ping/Heartbeat do worker Go
     * GET/POST /api/bridge/ping
     */
    public function actionPing()
    {
        if (!$this->validarSecret()) {
            return ['success' => false, 'message' => 'Não autorizado. Secret inválido.'];
        }

        self::tocarHeartbeat();
        return [
            'success' => true,
            'server_time' => time(),
            'message' => 'Heartbeat recebido com sucesso.'
        ];
    }

    /**
     * Endpoint de Long-Polling para o worker Go buscar tarefas pendentes
     * GET /api/bridge/poll
     */
    public function actionPoll()
    {
        if (!$this->validarSecret()) {
            return ['success' => false, 'message' => 'Não autorizado. Secret inválido.'];
        }

        self::tocarHeartbeat();
        $dir = self::getJobsDir();

        // Aguarda até 8 segundos por um job (long-polling)
        $limite = time() + 8;
        while (time() <= $limite) {
            $files = glob($dir . DIRECTORY_SEPARATOR . 'job_*.json');
            foreach ($files as $file) {
                if (strpos($file, '_done.json') !== false) {
                    continue;
                }
                
                $content = @file_get_contents($file);
                if (!$content) continue;
                $job = json_decode($content, true);
                if ($job && ($job['status'] ?? '') === 'pending') {
                    // Marca como processando para não enviar a outro worker
                    $job['status'] = 'processing';
                    $job['claimed_at'] = time();
                    @file_put_contents($file, json_encode($job), LOCK_EX);

                    return [
                        'success' => true,
                        'job' => $job
                    ];
                }
            }
            usleep(500000); // 500ms
        }

        return [
            'success' => true,
            'job' => null
        ];
    }

    /**
     * Endpoint para o worker Go enviar o arquivo MP3 e os metadados baixados
     * POST /api/bridge/submit
     */
    public function actionSubmit()
    {
        if (!$this->validarSecret()) {
            return ['success' => false, 'message' => 'Não autorizado. Secret inválido.'];
        }

        self::tocarHeartbeat();

        $jobId = Yii::$app->request->post('job_id');
        $youtubeId = Yii::$app->request->post('youtube_id');
        $titulo = Yii::$app->request->post('titulo', 'YouTube Audio');
        $duracao = (float)Yii::$app->request->post('duracao', 0);

        if (empty($jobId) || empty($youtubeId)) {
            return ['success' => false, 'message' => 'job_id e youtube_id são obrigatórios.'];
        }

        if (empty($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['audio']['error'] ?? 'MISSING';
            Yii::error("Bridge actionSubmit: erro de upload código $errCode", __METHOD__);
            return ['success' => false, 'message' => "Arquivo de áudio não recebido ou corrompido (código de erro: $errCode)."];
        }

        $diretorioRelativo = 'uploads/audio/youtube';
        $diretorioAbsoluto = Yii::getAlias('@app/web/' . $diretorioRelativo);
        if (!is_dir($diretorioAbsoluto)) {
            FileHelper::createDirectory($diretorioAbsoluto, 0777, true);
        }

        $nomeArquivo = 'yt_' . $youtubeId . '.mp3';
        $caminhoArquivoAbsoluto = $diretorioAbsoluto . DIRECTORY_SEPARATOR . $nomeArquivo;
        $caminhoArquivoRelativo = $diretorioRelativo . '/' . $nomeArquivo;

        // Move o arquivo enviado para o destino final
        if (!move_uploaded_file($_FILES['audio']['tmp_name'], $caminhoArquivoAbsoluto)) {
            return ['success' => false, 'message' => 'Falha ao salvar arquivo no disco da VPS.'];
        }

        chmod($caminhoArquivoAbsoluto, 0664);

        // Notifica que o job foi concluído gravando o arquivo _done.json
        $dir = self::getJobsDir();
        $doneData = [
            'status' => 'done',
            'job_id' => $jobId,
            'youtube_id' => $youtubeId,
            'titulo' => $titulo,
            'duracao' => $duracao,
            'arquivo_nome' => $nomeArquivo,
            'arquivo_path' => $caminhoArquivoRelativo,
            'caminho_absoluto' => $caminhoArquivoAbsoluto,
            'tamanho_bytes' => filesize($caminhoArquivoAbsoluto),
            'completed_at' => time()
        ];

        @file_put_contents($dir . DIRECTORY_SEPARATOR . $jobId . '_done.json', json_encode($doneData), LOCK_EX);
        @unlink($dir . DIRECTORY_SEPARATOR . $jobId . '.json');

        return [
            'success' => true,
            'message' => 'Áudio recebido e disponibilizado com sucesso na VPS.',
            'data' => $doneData
        ];
    }

    /**
     * Endpoint para o worker Go reportar falha no processamento de um job
     * POST /api/bridge/fail
     */
    public function actionFail()
    {
        if (!$this->validarSecret()) {
            return ['success' => false, 'message' => 'Não autorizado. Secret inválido.'];
        }

        self::tocarHeartbeat();

        $jobId = Yii::$app->request->post('job_id');
        $error = Yii::$app->request->post('error', 'Falha no processamento');

        if (empty($jobId)) {
            return ['success' => false, 'message' => 'job_id é obrigatório.'];
        }

        $dir = self::getJobsDir();
        $doneData = [
            'status' => 'error',
            'job_id' => $jobId,
            'error' => $error,
            'completed_at' => time()
        ];

        @file_put_contents($dir . DIRECTORY_SEPARATOR . $jobId . '_done.json', json_encode($doneData), LOCK_EX);
        @unlink($dir . DIRECTORY_SEPARATOR . $jobId . '.json');

        return ['success' => true, 'message' => 'Falha registrada.'];
    }

    /**
     * Despacha uma tarefa de download para a fila da Bridge e aguarda o retorno
     * Chamado internamente por AudioProcessorService
     *
     * @param string $youtubeId
     * @param string $url
     * @param int $timeoutSeconds Tempo máximo de espera pelo worker
     * @return array|null Dados do áudio processado ou null em caso de timeout/falha
     */
    public static function dispatchJob($youtubeId, $url, $timeoutSeconds = 90)
    {
        $dir = self::getJobsDir();
        $jobId = 'job_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $youtubeId) . '_' . time();

        $jobData = [
            'id' => $jobId,
            'youtube_id' => $youtubeId,
            'url' => $url,
            'status' => 'pending',
            'created_at' => time()
        ];

        $jobFile = $dir . DIRECTORY_SEPARATOR . $jobId . '.json';
        $doneFile = $dir . DIRECTORY_SEPARATOR . $jobId . '_done.json';

        if (!@file_put_contents($jobFile, json_encode($jobData), LOCK_EX)) {
            Yii::error("Falha ao criar arquivo de job da bridge: $jobFile", __METHOD__);
            return null;
        }

        // Aguarda a Bridge concluir
        $limite = time() + $timeoutSeconds;
        while (time() <= $limite) {
            if (file_exists($doneFile)) {
                $doneContent = @file_get_contents($doneFile);
                $doneData = json_decode($doneContent, true);
                @unlink($doneFile);
                @unlink($jobFile);
                if ($doneData && in_array($doneData['status'] ?? '', ['done', 'error'], true)) {
                    return $doneData;
                }
            }
            usleep(300000); // 300ms
        }

        // Se deu timeout, remove o job pendente
        @unlink($jobFile);
        Yii::warning("Timeout aguardando processamento da bridge para o job $jobId", __METHOD__);
        return null;
    }
}
