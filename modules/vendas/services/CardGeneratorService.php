<?php

namespace app\modules\vendas\services;

use Yii;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\ProdutoFoto;
use app\modules\vendas\models\ProdutoCard;
use app\modules\vendas\models\LojaConfiguracao;
use app\modules\vendas\models\RegraParcelamento;
use app\modules\vendas\models\ProdutoVariante;

/**
 * Service para orquestrar a geração automatizada de cards de produtos para redes sociais (Feed e Stories).
 */
class CardGeneratorService
{
    /**
     * Gera um card para o produto especificado.
     *
     * @param string|Produto $produtoOuId Model do Produto ou ID (UUID)
     * @param string $formato 'feed' (1080x1080) ou 'stories' (1080x1920)
     * @param array $options Opções visuais ['template' => ..., 'corTema' => ..., 'fundoEstilo' => ..., 'imagemFundo' => ...]
     * @return ProdutoCard
     * @throws \Exception
     */
    public function gerarCard($produtoOuId, $formato = 'feed', $options = [])
    {
        $formato = strtolower($formato) === ProdutoCard::FORMATO_STORIES ? ProdutoCard::FORMATO_STORIES : ProdutoCard::FORMATO_FEED;

        $template = $options['template'] ?? 'modern_dark';
        $corTema = $options['corTema'] ?? 'dark';
        $fundoEstilo = $options['fundoEstilo'] ?? 'gradient';
        $imagemFundoInput = $options['imagemFundo'] ?? null;
        $imagemFundoBase64 = null;

        if ($imagemFundoInput) {
            $imagemFundoBase64 = $this->converterImagemParaBase64($imagemFundoInput);
        }

        /** @var Produto $produto */
        if ($produtoOuId instanceof Produto) {
            $produto = $produtoOuId;
        } else {
            $produto = Produto::findOne($produtoOuId);
        }

        if (!$produto) {
            throw new \Exception("Produto não encontrado para o ID fornecido.");
        }

        // Valida cota de armazenamento em MB para cards sociais
        MediaStorageService::validarEspacoCards($produto->usuario_id);

        // 1. Variante da Matriz ou Grupo de Cor
        $variante = null;
        if (isset($options['variante']) && $options['variante'] instanceof ProdutoVariante) {
            $variante = $options['variante'];
        } elseif (!empty($options['varianteId'])) {
            $variante = ProdutoVariante::findOne(['id' => $options['varianteId'], 'produto_id' => $produto->id]);
        }

        $corMatriz = $options['corMatriz'] ?? ($options['cor'] ?? ($variante ? $variante->cor : null));
        $gradeTamanhos = $options['gradeTamanhos'] ?? [];
        $mesmoPreco = isset($options['mesmoPreco']) ? (bool)$options['mesmoPreco'] : true;
        $precoMin = isset($options['precoMin']) ? (float)$options['precoMin'] : null;
        $enquadramentoFoto = $options['enquadramentoFoto'] ?? ($options['enquadramento_foto'] ?? 'auto');
        $rotacaoFoto = $options['rotacaoFoto'] ?? ($options['rotacao_foto'] ?? 'auto');
        $mensagemCard = trim($options['mensagemCard'] ?? ($options['mensagem_card'] ?? ''));

        // 2. Carregar Configuração da Loja / Marca d'água
        $loja = LojaConfiguracao::findOne(['usuario_id' => $produto->usuario_id]);
        
        // 3. Foto do Produto (prioriza foto da cor da matriz ou da variante se houver)
        $fotoId = $options['fotoId'] ?? null;
        $fotoEscolhida = null;
        if ($fotoId) {
            $fotoEscolhida = ProdutoFoto::findOne(['id' => $fotoId, 'produto_id' => $produto->id]);
        }
        if (!$fotoEscolhida && !empty($corMatriz)) {
            $fotosCor = $produto->getFotosPorCor($corMatriz);
            if (!empty($fotosCor)) {
                $fotoEscolhida = $fotosCor[0];
            }
        }
        if (!$fotoEscolhida && $variante) {
            if (!empty($variante->fotos)) {
                $fotoEscolhida = $variante->fotos[0];
            } elseif (!empty($variante->cor)) {
                $fotosCor = $produto->getFotosPorCor($variante->cor);
                if (!empty($fotosCor)) {
                    $fotoEscolhida = $fotosCor[0];
                }
            }
        }
        if (!$fotoEscolhida) {
            $fotoEscolhida = $produto->fotoPrincipal;
        }
        if (!$fotoEscolhida && !empty($produto->fotos)) {
            $fotoEscolhida = $produto->fotos[0] ?? null;
        }

        $imagemBase64 = null;
        if ($fotoEscolhida && !empty($fotoEscolhida->arquivo_path)) {
            $imagemBase64 = $this->converterImagemParaBase64($fotoEscolhida->arquivo_path);
        }

        // 4. Logo da Loja
        $logoBase64 = null;
        if ($loja && !empty($loja->logo_path)) {
            $logoBase64 = $this->converterImagemParaBase64($loja->logo_path);
        }

        // 5. Preço e Promoção
        $emPromocao = $produto->getEmPromocao();
        $precoOriginal = $produto->preco_venda_sugerido > 0 ? $this->formatarMoeda($produto->preco_venda_sugerido) : null;
        
        $priceLabel = $emPromocao ? 'Por apenas' : 'Preço de venda';
        if (!empty($gradeTamanhos) && !$mesmoPreco && $precoMin !== null && $precoMin > 0) {
            $precoFinalValor = $precoMin;
            $precoPromocionalStr = $this->formatarMoeda($precoMin);
            $priceLabel = 'A partir de';
        } else {
            $precoFinalValor = $variante ? $variante->getPrecoVendaEfetivo() : ($precoMin !== null ? $precoMin : $produto->getPrecoFinal());
            $precoPromocionalStr = $this->formatarMoeda($precoFinalValor);
        }

        $descontoPercentual = 0;
        $badgeTexto = null;
        $corRedundante = !empty($corMatriz) && (stripos($produto->nome, $corMatriz) !== false || stripos($corMatriz, $produto->nome) !== false);

        if ($emPromocao) {
            $descontoPercentual = round($produto->getDescontoPromocional());
            $badgeTexto = $descontoPercentual > 0 ? "-{$descontoPercentual}% OFF" : "OFERTA";
        } elseif (!empty($corMatriz) && !$corRedundante) {
            $badgeTexto = "COR: {$corMatriz}";
        } elseif (!empty($gradeTamanhos)) {
            $badgeTexto = "GRADE COMPLETA";
        } elseif ($variante) {
            $partesVar = array_filter([$variante->cor, $variante->tamanho]);
            if (!empty($partesVar)) {
                $badgeTexto = implode(' • ', $partesVar);
            }
        }

        // 6. Parcelamento
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

        // 7. Definir caminhos de saída
        $sufixoId = !empty($corMatriz) 
            ? ('cor_' . preg_replace('/[^a-z0-9]/', '', strtolower($corMatriz))) 
            : ($variante ? ('var_' . substr($variante->id, 0, 8)) : 'base');
        $uniqueId = sprintf('%s_%s_%s_%s_%s', $produto->id, $sufixoId, $formato, $template, time());
        $nomeArquivo = "card_{$uniqueId}.webp";
        $diretorioUpload = Yii::getAlias('@app/web/uploads/cards');
        if (!is_dir($diretorioUpload)) {
            FileHelper::createDirectory($diretorioUpload, 0777, true);
        }

        $caminhoAbsolutoSaida = $diretorioUpload . DIRECTORY_SEPARATOR . $nomeArquivo;
        $caminhoRelativo = "uploads/cards/" . $nomeArquivo;

        // Nome do Produto adaptado para Matriz
        $nomeProduto = $produto->nome;
        if (!empty($corMatriz) && !$corRedundante) {
            $nomeProduto = "{$produto->nome} ({$corMatriz})";
        } elseif ($variante) {
            $nomeProduto = $variante->getNomeFormatado();
        }

        // 8. Montar Payload para o script Node.js com otimização máxima de tamanho
        $payload = [
            'formato' => $formato,
            'template' => $template,
            'corTema' => $corTema,
            'fundoEstilo' => $fundoEstilo,
            'enquadramentoFoto' => $enquadramentoFoto,
            'rotacaoFoto' => $rotacaoFoto,
            'mensagemCard' => $mensagemCard,
            'imagemFundoBase64' => $imagemFundoBase64,
            'outputPath' => $caminhoAbsolutoSaida,
            'scaleFactor' => 1.0,
            'quality' => 80,
            'produto' => [
                'id' => $produto->id,
                'nome' => $nomeProduto,
                'marca' => $produto->marca ?: ($produto->categoria ? $produto->categoria->nome : ''),
                'precoOriginal' => $precoOriginal,
                'precoPromocional' => $precoPromocionalStr,
                'precoVenda' => $precoPromocionalStr,
                'priceLabel' => $priceLabel,
                'emPromocao' => $emPromocao,
                'descontoPercentual' => $descontoPercentual > 0 ? "{$descontoPercentual}%" : '',
                'badgeTexto' => $badgeTexto,
                'mensagemCard' => $mensagemCard,
                'parcelamento' => $parcelamentoText,
                'imagemBase64' => $imagemBase64,
                'unidade' => $produto->unidade_medida,
                'gradeTamanhos' => $gradeTamanhos,
                'mesmoPreco' => $mesmoPreco,
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

        $payloadTmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "card_payload_{$uniqueId}.json";
        file_put_contents($payloadTmpFile, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // 8. Executar script Node.js Puppeteer
        $scriptPath = Yii::getAlias('@app/scripts/card_renderer.js');
        $nodeBin = 'node';

        $envPrefix = '';
        $puppeteerEnv = getenv('PUPPETEER_EXECUTABLE_PATH') ?: ($_ENV['PUPPETEER_EXECUTABLE_PATH'] ?? null);
        if ($puppeteerEnv) {
            $envPrefix = sprintf('PUPPETEER_EXECUTABLE_PATH=%s ', escapeshellarg($puppeteerEnv));
        }

        $cmd = sprintf('%s%s %s %s 2>&1', $envPrefix, escapeshellcmd($nodeBin), escapeshellarg($scriptPath), escapeshellarg($payloadTmpFile));
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        // Limpar arquivo temporário de payload
        if (file_exists($payloadTmpFile)) {
            @unlink($payloadTmpFile);
        }

        $outputStr = implode("\n", $output);

        if ($returnCode !== 0 || !file_exists($caminhoAbsolutoSaida)) {
            Yii::error("Falha ao gerar card com Puppeteer: {$outputStr}", __METHOD__);
            throw new \Exception("Erro na renderização do card: " . ($outputStr ?: "Falha de execução do Puppeteer"));
        }

        // 9. Registrar no Banco de Dados
        $cardModel = new ProdutoCard();
        $cardModel->produto_id = $produto->id;
        $cardModel->usuario_id = $produto->usuario_id;
        $cardModel->formato = $formato;
        $cardModel->card_path = $caminhoRelativo;

        $baseUrl = '/';
        if (Yii::$app->has('request') && method_exists(Yii::$app->request, 'getHostInfo') && Yii::$app->request->getHostInfo()) {
            $baseUrl = Yii::$app->request->getHostInfo() . '/';
        }
        $cardModel->card_url = $baseUrl . $caminhoRelativo;
        $cardModel->metadata = [
            'template' => $template,
            'cor_tema' => $corTema,
            'fundo_estilo' => $fundoEstilo,
            'preco_original' => $precoOriginal,
            'preco_promocional' => $precoPromocionalStr,
            'em_promocao' => $emPromocao,
            'parcelamento' => $parcelamentoText,
            'variante_id' => $variante ? $variante->id : null,
            'cor' => $corMatriz ?: ($variante ? $variante->cor : null),
            'tamanho' => $variante ? $variante->tamanho : null,
            'grade_tamanhos' => $gradeTamanhos,
            'mesmo_preco' => $mesmoPreco,
            'variante_nome' => !empty($corMatriz) ? "{$produto->nome} ({$corMatriz})" : ($variante ? $variante->getNomeFormatado() : null),
            'enquadramento_foto' => $enquadramentoFoto,
            'rotacao_foto' => $rotacaoFoto,
            'mensagem_card' => $mensagemCard,
            'gerado_em' => date('Y-m-d H:i:s')
        ];

        if (!$cardModel->save()) {
            Yii::error("Erro ao salvar ProdutoCard no banco: " . json_encode($cardModel->getErrors()), __METHOD__);
            throw new \Exception("Erro ao salvar registro do card no banco de dados.");
        }

        return $cardModel;
    }

    /**
     * Converte um caminho local ou URL de imagem para Data URI Base64
     *
     * @param string $caminhoOuUrl
     * @return string|null
     */
    public function converterImagemParaBase64($caminhoOuUrl)
    {
        if (empty($caminhoOuUrl)) {
            return null;
        }

        // Se já for data URI, retorna direto
        if (strpos($caminhoOuUrl, 'data:image/') === 0) {
            return $caminhoOuUrl;
        }

        $caminhoAbsoluto = null;

        // Limpa barras iniciais
        $pathLimpo = ltrim($caminhoOuUrl, '/');

        // Tenta resolver alias local
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
            // Tenta buscar via HTTP
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
     * Formata um valor numérico para a moeda corrente (R$ 0,00)
     */
    public function formatarMoeda($valor)
    {
        return 'R$ ' . number_format((float)$valor, 2, ',', '.');
    }
}
