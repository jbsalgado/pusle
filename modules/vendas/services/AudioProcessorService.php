<?php

namespace app\modules\vendas\services;

use Yii;
use yii\helpers\FileHelper;
use app\components\TenantHelper;
use app\modules\vendas\models\TrilhaSonora;

/**
 * AudioProcessorService
 * Gerencia download de áudio do YouTube, recorte aleatório por duração do vídeo
 * e síntese de voz neural (Text-to-Speech) para o Studio de Vídeos.
 * 100% nativo para Linux (Arch Linux).
 */
class AudioProcessorService
{
    /**
     * Extrai o ID do vídeo a partir de links comuns do YouTube
     */
    public static function extrairYoutubeId($url)
    {
        $url = trim($url);
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/)([^"&?\/\s]{11})/i', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Faz download do áudio do YouTube e registra na biblioteca de trilhas sonoras.
     * Possui sistema de cache local permanente para não baixar a mesma música duas vezes.
     * 
     * @param string $url Link público do YouTube
     * @param string|null $usuarioId ID do lojista
     * @return array
     */
    public static function downloadYoutubeAudio($url, $usuarioId = null)
    {
        $youtubeId = self::extrairYoutubeId($url);
        if (!$youtubeId) {
            throw new \InvalidArgumentException("URL do YouTube inválida ou não reconhecida. Forneça um link público válido (ex: https://www.youtube.com/watch?v=... ou https://youtu.be/...).");
        }

        if (empty($usuarioId)) {
            $usuarioId = TenantHelper::getId();
        }
        if (empty($usuarioId)) {
            $u = \app\models\Usuario::find()->one();
            $usuarioId = $u ? $u->id : null;
        }

        $diretorioRelativo = 'uploads/audio/youtube';
        $diretorioAbsoluto = Yii::getAlias('@app/web/' . $diretorioRelativo);
        if (!is_dir($diretorioAbsoluto)) {
            FileHelper::createDirectory($diretorioAbsoluto, 0777, true);
        }

        $nomeArquivo = 'yt_' . $youtubeId . '.mp3';
        $caminhoArquivoAbsoluto = $diretorioAbsoluto . DIRECTORY_SEPARATOR . $nomeArquivo;
        $caminhoArquivoRelativo = $diretorioRelativo . '/' . $nomeArquivo;

        // 1. Verifica se já existe em cache local no disco (mp3 ou m4a)
        $arquivoCache = null;
        foreach (['m4a', 'mp3'] as $f) {
            $testAbs = $diretorioAbsoluto . DIRECTORY_SEPARATOR . 'yt_' . $youtubeId . '.' . $f;
            if (file_exists($testAbs) && filesize($testAbs) > 1024) {
                $arquivoCache = [
                    'absoluto' => $testAbs,
                    'relativo' => $diretorioRelativo . '/yt_' . $youtubeId . '.' . $f,
                    'nome' => 'yt_' . $youtubeId . '.' . $f,
                    'formato' => $f,
                ];
                break;
            }
        }

        if ($arquivoCache) {
            $duracao = self::obterDuracaoAudio($arquivoCache['absoluto']);
            $trilhaExistente = TrilhaSonora::find()
                ->where(['arquivo_path' => $arquivoCache['relativo']])
                ->andFilterWhere(['usuario_id' => $usuarioId])
                ->one();

            if (!$trilhaExistente) {
                // Registra para o usuário atual
                $trilhaExistente = new TrilhaSonora();
                $trilhaExistente->usuario_id = $usuarioId;
                $trilhaExistente->titulo = 'YouTube: ' . $youtubeId;
                $trilhaExistente->descricao = 'Áudio extraído do YouTube (' . $url . ')';
                $trilhaExistente->arquivo_nome = $arquivoCache['nome'];
                $trilhaExistente->arquivo_path = $arquivoCache['relativo'];
                $trilhaExistente->formato = $arquivoCache['formato'];
                $trilhaExistente->tipo = 'youtube';
                $trilhaExistente->tamanho_bytes = filesize($arquivoCache['absoluto']);
                $trilhaExistente->save(false);
            }

            return [
                'success' => true,
                'cached' => true,
                'id' => $trilhaExistente->id,
                'titulo' => $trilhaExistente->titulo,
                'arquivo' => $trilhaExistente->arquivo_path,
                'duracao' => $duracao,
                'url' => $trilhaExistente->getUrl(),
                'youtube_id' => $youtubeId
            ];
        }

        // 2. PRIORIDADE 1: Verifica se a Bridge Go residencial está online
        if (\app\modules\api\controllers\BridgeController::isBridgeOnline()) {
            Yii::info("Pulse Bridge Go residencial ativa! Despachando download para o worker...", __METHOD__);
            $bridgeResult = \app\modules\api\controllers\BridgeController::dispatchJob($youtubeId, $url, 90);
            
            if ($bridgeResult) {
                if (($bridgeResult['status'] ?? '') === 'error') {
                    $errorMsg = $bridgeResult['error'] ?? 'Falha desconhecida no worker residencial.';
                    throw new \RuntimeException("❌ Erro ao baixar pelo Motor Residencial: " . $errorMsg);
                }

                $caminhoBridgeAbsoluto = $bridgeResult['caminho_absoluto'] ?? $caminhoArquivoAbsoluto;
                if (!empty($bridgeResult['arquivo_path']) && file_exists($caminhoBridgeAbsoluto) && filesize($caminhoBridgeAbsoluto) > 1024) {
                    $titulo = $bridgeResult['titulo'] ?? ('YouTube: ' . $youtubeId);
                    $duracaoTotal = (float)($bridgeResult['duracao'] ?? 0);
                    if ($duracaoTotal <= 0) {
                        $duracaoTotal = self::obterDuracaoAudio($caminhoBridgeAbsoluto);
                    }
                    $formatoAudio = $bridgeResult['formato'] ?? pathinfo($caminhoBridgeAbsoluto, PATHINFO_EXTENSION) ?: 'm4a';

                    // Registra na tabela prest_trilhas_sonoras
                    $trilha = new TrilhaSonora();
                    $trilha->usuario_id = $usuarioId;
                    $trilha->titulo = mb_substr($titulo, 0, 250);
                    $trilha->descricao = 'Áudio extraído via Pulse Bridge Residencial (' . $url . ')';
                    $trilha->arquivo_nome = $bridgeResult['arquivo_nome'] ?? basename($caminhoBridgeAbsoluto);
                    $trilha->arquivo_path = $bridgeResult['arquivo_path'] ?? ($diretorioRelativo . '/' . basename($caminhoBridgeAbsoluto));
                    $trilha->formato = $formatoAudio;
                    $trilha->tipo = 'youtube';
                    $trilha->tamanho_bytes = filesize($caminhoBridgeAbsoluto);
                    $trilha->save(false);

                    return [
                        'success' => true,
                        'cached' => false,
                        'via_bridge' => true,
                        'id' => $trilha->id,
                        'titulo' => $trilha->titulo,
                        'arquivo' => $trilha->arquivo_path,
                        'duracao' => $duracaoTotal,
                        'url' => $trilha->getUrl(),
                        'youtube_id' => $youtubeId
                    ];
                }
            }

            // Se a Bridge estava online mas não concluiu em 90s
            throw new \RuntimeException("⚠️ O download pelo Motor Residencial excedeu o tempo limite de 90 segundos. Tente novamente ou utilize outro link do YouTube.");
        }

        // 3. PRIORIDADE 2: Fallback local na VPS (usando cookies se configurado)
        $cookiesPath = getenv('YOUTUBE_COOKIES_PATH') ?: Yii::getAlias('@app/config/youtube_cookies.txt');
        $cookiesArg = (file_exists($cookiesPath) && filesize($cookiesPath) > 50) 
            ? ' --cookies ' . escapeshellarg($cookiesPath) 
            : '';

        // Extrai metadados (Título e Duração) usando yt-dlp
        $escapedUrl = escapeshellarg($url);
        $cmdInfo = "yt-dlp --print \"%(id)s||%(title)s||%(duration)s\" --no-playlist --extractor-args \"youtube:player_client=android,web\"{$cookiesArg} {$escapedUrl} 2>&1";
        $infoOutput = shell_exec($cmdInfo);
        
        $titulo = 'YouTube: ' . $youtubeId;
        $duracaoTotal = 0;
        if (!empty($infoOutput)) {
            $linhas = explode("\n", trim($infoOutput));
            foreach ($linhas as $linha) {
                if (strpos($linha, '||') !== false) {
                    $partes = explode('||', $linha);
                    if (count($partes) >= 2) {
                        $titulo = trim($partes[1]) ?: $titulo;
                    }
                    if (count($partes) >= 3) {
                        $duracaoTotal = (float)trim($partes[2]);
                    }
                    break;
                }
            }
        }

        // 3. Executa o download de áudio via yt-dlp
        $templateSaida = escapeshellarg($diretorioAbsoluto . '/yt_%(id)s.%(ext)s');
        $cmdDownload = "yt-dlp -x --audio-format mp3 -o {$templateSaida} --no-playlist --extractor-args \"youtube:player_client=android,web\"{$cookiesArg} {$escapedUrl} 2>&1";
        $downloadOutput = shell_exec($cmdDownload);

        if (!file_exists($caminhoArquivoAbsoluto) || filesize($caminhoArquivoAbsoluto) < 1024) {
            Yii::error("Falha ao baixar áudio do YouTube [{$url}]: " . $downloadOutput, __METHOD__);
            
            if (stripos($downloadOutput, 'Sign in to confirm') !== false || stripos($downloadOutput, 'bot') !== false) {
                throw new \RuntimeException("O YouTube bloqueou o download a partir deste servidor (IP de Data Center detectado como bot). Para contornar, faça o upload direto do arquivo MP3 no Studio ou adicione o arquivo youtube_cookies.txt no servidor.");
            }
            
            throw new \RuntimeException("Não foi possível extrair o áudio do link fornecido. O YouTube pode ter restrito o acesso. Mensagem: " . substr(strip_tags($downloadOutput), 0, 300));
        }

        if ($duracaoTotal <= 0) {
            $duracaoTotal = self::obterDuracaoAudio($caminhoArquivoAbsoluto);
        }

        // 4. Registra na tabela prest_trilhas_sonoras
        $trilha = new TrilhaSonora();
        $trilha->usuario_id = $usuarioId;
        $trilha->titulo = mb_substr($titulo, 0, 250);
        $trilha->descricao = 'Áudio extraído do YouTube (' . $url . ')';
        $trilha->arquivo_nome = $nomeArquivo;
        $trilha->arquivo_path = $caminhoArquivoRelativo;
        $trilha->formato = 'mp3';
        $trilha->tipo = 'youtube';
        $trilha->tamanho_bytes = filesize($caminhoArquivoAbsoluto);
        $trilha->save(false);

        return [
            'success' => true,
            'cached' => false,
            'id' => $trilha->id,
            'titulo' => $trilha->titulo,
            'arquivo' => $trilha->arquivo_path,
            'duracao' => $duracaoTotal,
            'url' => $trilha->getUrl(),
            'youtube_id' => $youtubeId
        ];
    }

    /**
     * Recorta um trecho aleatório do áudio para corresponder exatamente à duração do vídeo.
     * Aplica fade-in de 0.5s e fade-out de 1.5s automaticamente.
     * 
     * @param string $caminhoAudio Caminho relativo ou absoluto do áudio
     * @param int $duracaoDesejada Duração do vídeo em segundos (ex: 5, 10, 15, 30, 60)
     * @return string Caminho relativo do áudio fatiado
     */
    public static function extrairTrechoAleatorio($caminhoAudio, $duracaoDesejada)
    {
        $duracaoDesejada = max(3, (int)$duracaoDesejada);
        $caminhoAbsoluto = self::resolverCaminhoAbsoluto($caminhoAudio);

        if (!file_exists($caminhoAbsoluto)) {
            Yii::warning("Áudio original não encontrado para recorte: {$caminhoAbsoluto}", __METHOD__);
            return $caminhoAudio;
        }

        $duracaoTotal = self::obterDuracaoAudio($caminhoAbsoluto);

        // Se o áudio for menor ou igual à duração desejada, retorna o original (o renderizador faz loop)
        if ($duracaoTotal <= ($duracaoDesejada + 2)) {
            return $caminhoAudio;
        }

        // Sorteia o offset inicial evitando vinhetas iniciais e fade final
        $margemInicio = 5;
        $margemFim = 3;
        $maxOffset = (int)floor($duracaoTotal - $duracaoDesejada - $margemFim);
        $startOffset = ($maxOffset > $margemInicio) ? rand($margemInicio, $maxOffset) : 0;

        $diretorioRelativo = 'uploads/audio/slices';
        $diretorioAbsoluto = Yii::getAlias('@app/web/' . $diretorioRelativo);
        if (!is_dir($diretorioAbsoluto)) {
            FileHelper::createDirectory($diretorioAbsoluto, 0777, true);
        }

        $hashOrigem = substr(md5($caminhoAbsoluto), 0, 8);
        $nomeSlice = sprintf('slice_%s_%ds_%d_%s.mp3', $hashOrigem, $duracaoDesejada, $startOffset, uniqid());
        $caminhoSliceAbsoluto = $diretorioAbsoluto . DIRECTORY_SEPARATOR . $nomeSlice;
        $caminhoSliceRelativo = $diretorioRelativo . '/' . $nomeSlice;

        $fadeStart = max(0, $duracaoDesejada - 1.5);
        $escapedIn = escapeshellarg($caminhoAbsoluto);
        $escapedOut = escapeshellarg($caminhoSliceAbsoluto);

        $cmd = "ffmpeg -y -ss {$startOffset} -t {$duracaoDesejada} -i {$escapedIn} "
             . "-af \"afade=t=in:ss=0:d=0.5,afade=t=out:st={$fadeStart}:d=1.5\" "
             . "-c:a libmp3lame -q:a 2 {$escapedOut} 2>&1";

        shell_exec($cmd);

        if (file_exists($caminhoSliceAbsoluto) && filesize($caminhoSliceAbsoluto) > 1024) {
            return $caminhoSliceRelativo;
        }

        return $caminhoAudio;
    }

    /**
     * Gera arquivo de locução em áudio a partir de um texto usando IA Neural (Edge TTS).
     * 
     * @param string $texto Mensagem promocional
     * @param string $voz Nome da voz (ex: pt-BR-FranciscaNeural ou pt-BR-AntonioNeural)
     * @param string $velocidade Taxa de velocidade (ex: '+0%', '+10%')
     * @param string|null $usuarioId
     * @return array
     */
    public static function gerarLocucaoTts($texto, $voz = 'pt-BR-FranciscaNeural', $velocidade = '+0%', $usuarioId = null)
    {
        $texto = trim(strip_tags($texto));
        if (empty($texto)) {
            throw new \InvalidArgumentException("O texto da locução não pode ficar vazio.");
        }

        if (empty($usuarioId)) {
            $usuarioId = TenantHelper::getId();
        }
        if (empty($usuarioId)) {
            $u = \app\models\Usuario::find()->one();
            $usuarioId = $u ? $u->id : null;
        }

        $diretorioRelativo = 'uploads/audio/tts';
        $diretorioAbsoluto = Yii::getAlias('@app/web/' . $diretorioRelativo);
        if (!is_dir($diretorioAbsoluto)) {
            FileHelper::createDirectory($diretorioAbsoluto, 0777, true);
        }

        $nomeArquivo = sprintf('tts_%s_%s.mp3', time(), uniqid());
        $caminhoAbsoluto = $diretorioAbsoluto . DIRECTORY_SEPARATOR . $nomeArquivo;
        $caminhoRelativo = $diretorioRelativo . '/' . $nomeArquivo;

        $scriptTts = Yii::getAlias('@app/scripts/tts_generator.js');
        $escapedText = escapeshellarg($texto);
        $escapedVoice = escapeshellarg($voz ?: 'pt-BR-FranciscaNeural');
        $escapedRate = escapeshellarg($velocidade ?: '+0%');
        $escapedOut = escapeshellarg($caminhoAbsoluto);

        $cmd = "node {$scriptTts} --text {$escapedText} --voice {$escapedVoice} --rate {$escapedRate} --output {$escapedOut} 2>&1";
        $output = shell_exec($cmd);

        if (!file_exists($caminhoAbsoluto) || filesize($caminhoAbsoluto) < 512) {
            Yii::error("Erro na síntese de voz TTS: " . $output, __METHOD__);
            throw new \RuntimeException("Falha ao sintetizar o áudio da locução. Detalhes: " . substr($output, 0, 200));
        }

        $duracao = self::obterDuracaoAudio($caminhoAbsoluto);

        // Salva na biblioteca de trilhas para reutilização
        $trilha = new TrilhaSonora();
        $trilha->usuario_id = $usuarioId;
        $trilha->titulo = 'Locução: ' . mb_substr($texto, 0, 40) . (mb_strlen($texto) > 40 ? '...' : '');
        $trilha->descricao = $texto;
        $trilha->arquivo_nome = $nomeArquivo;
        $trilha->arquivo_path = $caminhoRelativo;
        $trilha->formato = 'mp3';
        $trilha->tipo = 'voz_ia';
        $trilha->tamanho_bytes = filesize($caminhoAbsoluto);
        $trilha->save(false);

        return [
            'success' => true,
            'id' => $trilha->id,
            'titulo' => $trilha->titulo,
            'arquivo' => $trilha->arquivo_path,
            'duracao' => $duracao,
            'url' => $trilha->getUrl(),
            'texto' => $texto,
            'voz' => $voz
        ];
    }

    /**
     * Obtém a duração precisa de um arquivo de áudio usando ffprobe
     */
    public static function obterDuracaoAudio($caminhoAbsoluto)
    {
        if (!file_exists($caminhoAbsoluto)) return 0;
        $escaped = escapeshellarg($caminhoAbsoluto);
        $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$escaped} 2>/dev/null";
        $val = trim((string)shell_exec($cmd));
        return (float)$val;
    }

    /**
     * Resolve caminho relativo do web para absoluto no disco
     */
    public static function resolverCaminhoAbsoluto($caminho)
    {
        if (empty($caminho)) return '';
        if (file_exists($caminho)) return $caminho;
        
        $tentativaWeb = Yii::getAlias('@app/web/' . ltrim($caminho, '/'));
        if (file_exists($tentativaWeb)) return $tentativaWeb;

        $tentativaAssets = Yii::getAlias('@app/assets/audio/' . basename($caminho));
        if (file_exists($tentativaAssets)) return $tentativaAssets;

        return $tentativaWeb;
    }
}
