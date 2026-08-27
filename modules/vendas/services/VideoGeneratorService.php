<?php

namespace app\modules\vendas\services;

use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use Symfony\Component\Process\Process;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\ProdutoFoto;
use app\modules\vendas\models\ProdutoVideo;
use app\modules\vendas\models\LojaConfiguracao;
use app\modules\vendas\models\RegraParcelamento;
use app\jobs\GenerateProductVideoJob;

/**
 * Service para orquestrar a geração automatizada de vídeos promocionais curtos (9:16 vertical).
 */
class VideoGeneratorService
{
    /**
     * Enfileira ou solicita a geração de um vídeo promocional para o produto.
     *
     * @param string|Produto $produtoOuId Model ou ID do Produto
     * @param int $duracao Duração em segundos (5, 10 ou 15)
     * @param array $options Opções adicionais (template, corTema, etc)
     * @param bool $executarSincrono Se true, executa imediatamente sem aguardar fila
     * @return ProdutoVideo
     * @throws \Exception
     */
    public function solicitarGeracaoVideo($produtoOuId, $duracao = 15, $options = [], $executarSincrono = false)
    {
        $duracao = in_array((int)$duracao, [5, 10, 15, 30, 60]) ? (int)$duracao : 15;

        /** @var Produto $produto */
        if ($produtoOuId instanceof Produto) {
            $produto = $produtoOuId;
        } else {
            $idClean = trim((string)$produtoOuId);
            $produto = Produto::findOne($idClean);
            if (!$produto) {
                $produto = Produto::find()->where(['id' => $idClean])->one();
            }
        }

        if (!$produto) {
            throw new \Exception("Produto não encontrado para o ID fornecido.");
        }

        // Valida se o tenant tem cota de armazenamento disponível para vídeos
        MediaStorageService::validarEspacoVideos($produto->usuario_id);

        $formatoVal = strtolower($options['formato'] ?? 'stories') === 'feed' ? ProdutoVideo::FORMATO_FEED : ProdutoVideo::FORMATO_STORIES;
        $videoModel = new ProdutoVideo();
        $videoModel->produto_id = $produto->id;
        $videoModel->usuario_id = $produto->usuario_id;
        $videoModel->duracao = $duracao;
        $videoModel->formato = $formatoVal;
        $videoModel->status = ProdutoVideo::STATUS_PENDENTE;
        $metaParams = [
            'formato' => $formatoVal,
            'template' => $options['template'] ?? 'modern_dark',
            'cor_tema' => $options['corTema'] ?? 'dark',
            'fundo_estilo' => $options['fundoEstilo'] ?? 'gradient',
            'trilha_sonora' => $options['trilhaSonora'] ?? 'promo_bg.mp3',
            'efeito_visual' => $options['efeitoVisual'] ?? $options['efeito_visual'] ?? 'none',
            'modo_composicao' => $options['modoComposicao'] ?? 'hibrido',
            'ajuste_duracao' => $options['ajusteDuracao'] ?? 'trim',
            'ajuste_proporcao' => $options['ajusteProporcao'] ?? 'smart_blur',
        ];
        $metaParams['resumo_recursos'] = ProdutoVideo::gerarResumoRecursosTexto($metaParams);
        $metaParams['solicitado_em'] = date('Y-m-d H:i:s');

        $videoModel->metadata = $metaParams;

        if (!$videoModel->save()) {
            Yii::error("Erro ao salvar ProdutoVideo no banco: " . json_encode($videoModel->getErrors()), __METHOD__);
            throw new \Exception("Erro ao registrar solicitação de vídeo no banco de dados.");
        }

        if ($executarSincrono) {
            return $this->processarGeracaoVideo($videoModel, $options);
        }

        // Dispara Job na fila do Yii2 (processado em segundo plano pelo daemon pulse-queue)
        try {
            Yii::$app->queue->push(new GenerateProductVideoJob([
                'videoId' => $videoModel->id,
                'options' => $options,
            ]));
        } catch (\Exception $e) {
            Yii::error("Falha ao agendar Job no yii2-queue: " . $e->getMessage(), __METHOD__);
            // Fallback: tenta executar diretamente se a fila falhar
            return $this->processarGeracaoVideo($videoModel, $options);
        }

        return $videoModel;
    }

    /**
     * Executa a renderização física do vídeo via Node.js + Puppeteer + FFmpeg.
     *
     * @param string|ProdutoVideo $videoOuId
     * @param array $options
     * @return ProdutoVideo
     * @throws \Exception
     */
    public function processarGeracaoVideo($videoOuId, $options = [])
    {
        /** @var ProdutoVideo $videoModel */
        if ($videoOuId instanceof ProdutoVideo) {
            $videoModel = $videoOuId;
        } else {
            $videoModel = ProdutoVideo::findOne($videoOuId);
        }

        if (!$videoModel) {
            throw new \Exception("Registro de ProdutoVideo não encontrado.");
        }

        $videoModel->status = ProdutoVideo::STATUS_PROCESSANDO;
        $videoModel->save(false, ['status', 'data_atualizacao']);

        try {
            $produto = $videoModel->produto;
            if (!$produto) {
                throw new \Exception("Produto associado ao vídeo não existe.");
            }

            // 1. Carregar Configurações da Loja
            $loja = LojaConfiguracao::findOne(['usuario_id' => $produto->usuario_id]);

            // 2. Coletar Fotos do Produto (Principal + Galeria) respeitando o ritmo visual por duração
            $duracaoSec = (int)($videoModel->duracao ?: 15);
            $limiteFotos = match ($duracaoSec) {
                5 => 2,
                10 => 3,
                15 => 4,
                30 => 8,
                60 => 12,
                default => 4
            };

            $fotosArray = [];
            $fotoPrincipal = $produto->fotoPrincipal;
            if ($fotoPrincipal && !empty($fotoPrincipal->arquivo_path)) {
                $b64 = $this->converterImagemParaBase64($fotoPrincipal->arquivo_path);
                if ($b64) $fotosArray[] = $b64;
            }

            $outrasFotos = $produto->getFotos()->all();
            foreach ($outrasFotos as $foto) {
                if ($fotoPrincipal && $foto->id === $fotoPrincipal->id) continue;
                if (!empty($foto->arquivo_path)) {
                    $b64 = $this->converterImagemParaBase64($foto->arquivo_path);
                    if ($b64 && count($fotosArray) < $limiteFotos) {
                        $fotosArray[] = $b64;
                    }
                }
            }

            // Subsituto SVG caso não haja fotos
            if (empty($fotosArray)) {
                $fotosArray[] = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 24 24" fill="none" stroke="%23cccccc" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
            }

            // 3. Logo da Loja
            $logoBase64 = null;
            if ($loja && !empty($loja->logo_path)) {
                $logoBase64 = $this->converterImagemParaBase64($loja->logo_path);
            }

            // 4. Preço e Promoção
            $emPromocao = $produto->getEmPromocao();
            $precoOriginal = $produto->preco_venda_sugerido > 0 ? $this->formatarMoeda($produto->preco_venda_sugerido) : null;
            $precoFinalValor = $produto->getPrecoFinal();
            $precoPromocionalStr = $this->formatarMoeda($precoFinalValor);

            $descontoPercentual = 0;
            $badgeTexto = null;
            if ($emPromocao) {
                $descontoPercentual = round($produto->getDescontoPromocional());
                $badgeTexto = $descontoPercentual > 0 ? "-{$descontoPercentual}% OFF" : "OFERTA";
            }

            // 5. Parcelamento
            $parcelamentoText = '';
            if ($produto->permite_parcelamento && $precoFinalValor > 0) {
                $regra = RegraParcelamento::find()
                    ->where(['usuario_id' => $produto->usuario_id])
                    ->orderBy(['max_parcelas' => SORT_DESC])
                    ->one();

                $maxParcelas = $regra ? (int)$regra->max_parcelas : 10;
                if ($maxParcelas > 1) {
                    $valorParcela = $precoFinalValor / $maxParcelas;
                    $valorParcelaStr = $this->formatarMoeda($valorParcela);
                    $parcelamentoText = "ou {$maxParcelas}x de {$valorParcelaStr}";
                }
            }

            // 6. Caminhos de Saída
            $nomeArquivo = "video_{$videoModel->id}.mp4";
            $diretorioUpload = Yii::getAlias('@app/web/uploads/videos');
            if (!is_dir($diretorioUpload)) {
                FileHelper::createDirectory($diretorioUpload, 0777, true);
            }

            $caminhoAbsolutoSaida = $diretorioUpload . DIRECTORY_SEPARATOR . $nomeArquivo;
            $caminhoRelativo = "uploads/videos/" . $nomeArquivo;

            // 7. Montar Payload JSON
            $trilhaKey = $options['trilhaSonora'] ?? ($videoModel->metadata['trilha_sonora'] ?? 'promo_bg.mp3');
            $musicasMap = self::getMusicasDisponiveis();
            $trilhaSonora = null;

            if (isset($musicasMap[$trilhaKey])) {
                $trilhaSonora = $musicasMap[$trilhaKey]['arquivo'];
            } else if (strpos($trilhaKey, 'custom_') === 0) {
                $customId = substr($trilhaKey, 7);
                $trilhaModel = \app\modules\vendas\models\TrilhaSonora::findOne($customId);
                if ($trilhaModel) {
                    $trilhaSonora = $trilhaModel->arquivo_path;
                }
            }

            if (!$trilhaSonora) {
                $trilhaSonora = $trilhaKey;
            }

            // Coletar Vídeos do Produto
            $videosArray = [];
            $videosCadastrados = $produto->videos;
            foreach ($videosCadastrados as $vItem) {
                if (!empty($vItem->video_path)) {
                    $caminhoRel = ltrim($vItem->video_path, '/');
                    $absPathV = Yii::getAlias('@app/web/' . $caminhoRel);
                    if (!file_exists($absPathV)) {
                        $absPathV = Yii::getAlias('@webroot/' . $caminhoRel);
                    }
                    if (file_exists($absPathV)) {
                        $videosArray[] = [
                            'id' => $vItem->id,
                            'url' => $vItem->getUrl(),
                            'path' => $absPathV,
                            'duracao' => (int)($vItem->duracao ?: 15)
                        ];
                    }
                }
            }

            $payload = [
                'duracao' => (int)$videoModel->duracao,
                'formato' => $options['formato'] ?? ($videoModel->metadata['formato'] ?? ($videoModel->formato ?: 'stories')),
                'template' => $options['template'] ?? ($videoModel->metadata['template'] ?? 'modern_dark'),
                'corTema' => $options['corTema'] ?? ($videoModel->metadata['cor_tema'] ?? 'dark'),
                'fundoEstilo' => $options['fundoEstilo'] ?? ($videoModel->metadata['fundo_estilo'] ?? 'gradient'),
                'trilhaSonora' => $trilhaSonora,
                'efeitoVisual' => $options['efeitoVisual'] ?? ($options['efeito_visual'] ?? ($videoModel->metadata['efeito_visual'] ?? 'none')),
                'modoComposicao' => $options['modoComposicao'] ?? ($videoModel->metadata['modo_composicao'] ?? 'hibrido'),
                'ajusteDuracao' => $options['ajusteDuracao'] ?? ($videoModel->metadata['ajuste_duracao'] ?? 'trim'),
                'ajusteProporcao' => $options['ajusteProporcao'] ?? ($videoModel->metadata['ajuste_proporcao'] ?? 'smart_blur'),
                'outputPath' => $caminhoAbsolutoSaida,
                'produto' => [
                    'id' => $produto->id,
                    'nome' => $produto->nome,
                    'descricao' => $produto->descricao,
                    'marca' => $produto->marca ?: ($produto->categoria ? $produto->categoria->nome : ''),
                    'precoOriginal' => $precoOriginal,
                    'precoPromocional' => $precoPromocionalStr,
                    'emPromocao' => $emPromocao,
                    'descontoPercentual' => $descontoPercentual > 0 ? "{$descontoPercentual}%" : '',
                    'badgeTexto' => $badgeTexto,
                    'parcelamento' => $parcelamentoText,
                    'fotosBase64' => $fotosArray,
                    'videos' => $videosArray,
                    'unidade' => $produto->unidade_medida
                ],
                'loja' => [
                    'nome' => $loja ? ($loja->nome_fantasia ?: $loja->nome_loja) : 'PULSE',
                    'logoBase64' => $logoBase64,
                    'corPrimaria' => $loja ? ($loja->aparencia_cor_primaria ?: '#0E8CE9') : '#0E8CE9',
                    'corSecundaria' => $loja ? ($loja->aparencia_cor_secundaria ?: '#026EC7') : '#026EC7',
                    'telefone' => $loja ? $loja->getTelefoneFormatado() : '',
                    'site' => $loja ? $loja->site : ''
                ]
            ];

            $payloadTmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "video_payload_{$videoModel->id}.json";
            file_put_contents($payloadTmpFile, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            // 8. Executar Script Node.js via Process (Timeout de 3 minutos)
            $scriptPath = Yii::getAlias('@app/scripts/video_renderer.js');
            $nodeBin = 'node';

            $process = new Process([$nodeBin, $scriptPath, $payloadTmpFile]);
            $process->setTimeout(600); // 600 segundos (10 minutos) de timeout para renderizações mais longas
            $process->run();

            if (file_exists($payloadTmpFile)) {
                @unlink($payloadTmpFile);
            }

            if (!$process->isSuccessful() || !file_exists($caminhoAbsolutoSaida)) {
                $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
                Yii::error("Falha ao renderizar vídeo com Node.js/FFmpeg: {$errorOutput}", __METHOD__);
                throw new \Exception("Erro na renderização do vídeo: " . ($errorOutput ?: "Falha ao gerar arquivo MP4 final."));
            }

            // 9. Atualizar Registro de Sucesso no Banco
            $baseUrl = '/';
            if (Yii::$app->has('request') && method_exists(Yii::$app->request, 'getHostInfo') && Yii::$app->request->getHostInfo()) {
                $baseUrl = Yii::$app->request->getHostInfo() . '/';
            }

            $videoModel->status = ProdutoVideo::STATUS_CONCLUIDO;
            $videoModel->video_path = $caminhoRelativo;
            $videoModel->video_url = $baseUrl . $caminhoRelativo;
            $videoModel->erro_mensagem = null;

            $meta = is_array($videoModel->metadata) ? $videoModel->metadata : [];
            $meta['concluido_em'] = date('Y-m-d H:i:s');
            $meta['tamanho_bytes'] = filesize($caminhoAbsolutoSaida);
            $videoModel->metadata = $meta;

            $videoModel->save(false);
            return $videoModel;

        } catch (\Exception $e) {
            $videoModel->status = ProdutoVideo::STATUS_ERRO;
            $videoModel->erro_mensagem = $e->getMessage();
            $videoModel->save(false);
            throw $e;
        }
    }

    /**
     * Converte um caminho local ou URL de imagem para Data URI Base64
     */
    public function converterImagemParaBase64($caminhoOuUrl)
    {
        if (empty($caminhoOuUrl)) {
            return null;
        }

        if (strpos($caminhoOuUrl, 'data:image/') === 0) {
            return $caminhoOuUrl;
        }

        $caminhoAbsoluto = null;
        $pathLimpo = ltrim($caminhoOuUrl, '/');

        $possiveisCaminhos = [
            Yii::getAlias('@app/web/' . $pathLimpo),
            Yii::getAlias('@app/' . $pathLimpo),
            $caminhoOuUrl
        ];

        foreach ($possiveisCaminhos as $caminhoTestar) {
            if (file_exists($caminhoTestar) && is_file($caminhoTestar)) {
                $caminhoAbsoluto = $caminhoTestar;
                break;
            }
        }

        $conteudo = null;
        $mimeType = 'image/png';

        if ($caminhoAbsoluto) {
            $conteudo = file_get_contents($caminhoAbsoluto);
            if (function_exists('mime_content_type')) {
                $mimeType = mime_content_type($caminhoAbsoluto) ?: 'image/png';
            } else {
                $ext = strtolower(pathinfo($caminhoAbsoluto, PATHINFO_EXTENSION));
                $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'];
                $mimeType = $mimes[$ext] ?? 'image/png';
            }
        } else if (filter_var($caminhoOuUrl, FILTER_VALIDATE_URL)) {
            try {
                $conteudo = @file_get_contents($caminhoOuUrl);
            } catch (\Exception $e) {
                Yii::warning("Não foi possível carregar imagem externa: {$caminhoOuUrl}", __METHOD__);
            }
        }

        if ($conteudo) {
            return 'data:' . $mimeType . ';base64,' . base64_encode($conteudo);
        }

        return null;
    }

    /**
     * Formata valor numérico em moeda BRL
     */
    public function formatarMoeda($valor)
    {
        return 'R$ ' . number_format((float)$valor, 2, ',', '.');
    }

    /**
     * Retorna a lista de trilhas sonoras disponíveis na biblioteca.
     * @param bool $apenasPadrao Se true, retorna apenas as 4 trilhas padrão nativas do sistema.
     */
    public static function getMusicasDisponiveis($apenasPadrao = false)
    {
        $baseUrl = '/';
        if (Yii::$app->has('request') && method_exists(Yii::$app->request, 'getHostInfo') && Yii::$app->request->getHostInfo()) {
            $baseUrl = Yii::$app->request->getHostInfo() . '/';
        }
        $baseUrlAudio = $baseUrl . 'assets/audio/';

        $padrao = [
            'promo_bg.mp3' => [
                'nome' => 'Pop Animado (Padrão)',
                'descricao' => 'Trilha pop leve e alegre para ofertas',
                'arquivo' => 'promo_bg.mp3',
                'tipo' => 'padrao',
                'tipo_audio' => \app\modules\vendas\models\TrilhaSonora::TIPO_MUSICA,
                'url' => $baseUrlAudio . 'promo_bg.mp3',
            ],
            'upbeat_retail.mp3' => [
                'nome' => 'Varejo & Vendas Dynamic',
                'descricao' => 'Ritmo dinâmico para produtos de alto impacto',
                'arquivo' => 'upbeat_retail.mp3',
                'tipo' => 'padrao',
                'tipo_audio' => \app\modules\vendas\models\TrilhaSonora::TIPO_MUSICA,
                'url' => $baseUrlAudio . 'upbeat_retail.mp3',
            ],
            'corporate_chic.mp3' => [
                'nome' => 'Corporativo & Elegante',
                'descricao' => 'Estilo suave e sofisticado para produtos premium',
                'arquivo' => 'corporate_chic.mp3',
                'tipo' => 'padrao',
                'tipo_audio' => \app\modules\vendas\models\TrilhaSonora::TIPO_MUSICA,
                'url' => $baseUrlAudio . 'corporate_chic.mp3',
            ],
            'energy_beat.mp3' => [
                'nome' => 'Batida Eletrônica Energy',
                'descricao' => 'Energia jovem e futurista',
                'arquivo' => 'energy_beat.mp3',
                'tipo' => 'padrao',
                'tipo_audio' => \app\modules\vendas\models\TrilhaSonora::TIPO_MUSICA,
                'url' => $baseUrlAudio . 'energy_beat.mp3',
            ],
        ];

        if ($apenasPadrao) {
            return $padrao;
        }

        try {
            $usuarioId = \app\components\TenantHelper::getId();
            if ($usuarioId) {
                $customizadas = \app\modules\vendas\models\TrilhaSonora::find()
                    ->where(['usuario_id' => $usuarioId, 'ativo' => true])
                    ->orderBy(['created_at' => SORT_DESC])
                    ->all();

                foreach ($customizadas as $trilha) {
                    $key = 'custom_' . $trilha->id;
                    $prefixoIcone = ($trilha->tipo === \app\modules\vendas\models\TrilhaSonora::TIPO_EFEITO) ? '🔊 ' : '✨ ';
                    $item = [
                        'nome' => $prefixoIcone . $trilha->titulo,
                        'descricao' => $trilha->descricao ?: ($trilha->tipo === \app\modules\vendas\models\TrilhaSonora::TIPO_EFEITO ? 'Efeito especial enviado pelo usuário' : 'Música própria enviada pelo usuário'),
                        'arquivo' => $trilha->arquivo_path,
                        'tipo' => 'custom',
                        'tipo_audio' => $trilha->tipo ?: \app\modules\vendas\models\TrilhaSonora::TIPO_MUSICA,
                        'url' => $trilha->getUrl(),
                    ];
                    // Insere APENAS UMA VEZ indexado pela chave única custom_<id>
                    $padrao[$key] = $item;
                }
            }
        } catch (\Exception $e) {
            Yii::error("Erro ao carregar trilhas customizadas: " . $e->getMessage(), __METHOD__);
        }

        return $padrao;
    }
}
