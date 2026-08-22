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

        // 1. Carregar Configuração da Loja / Marca d'água
        $loja = LojaConfiguracao::findOne(['usuario_id' => $produto->usuario_id]);
        
        // 2. Foto do Produto
        $fotoPrincipal = $produto->fotoPrincipal;
        $imagemBase64 = null;
        if ($fotoPrincipal && !empty($fotoPrincipal->arquivo_path)) {
            $imagemBase64 = $this->converterImagemParaBase64($fotoPrincipal->arquivo_path);
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

        // 6. Definir caminhos de saída
        $uniqueId = sprintf('%s_%s_%s_%s', $produto->id, $formato, $template, time());
        $nomeArquivo = "card_{$uniqueId}.png";
        $diretorioUpload = Yii::getAlias('@app/web/uploads/cards');
        if (!is_dir($diretorioUpload)) {
            FileHelper::createDirectory($diretorioUpload, 0777, true);
        }

        $caminhoAbsolutoSaida = $diretorioUpload . DIRECTORY_SEPARATOR . $nomeArquivo;
        $caminhoRelativo = "uploads/cards/" . $nomeArquivo;

        // 7. Montar Payload para o script Node.js
        $payload = [
            'formato' => $formato,
            'template' => $template,
            'corTema' => $corTema,
            'fundoEstilo' => $fundoEstilo,
            'imagemFundoBase64' => $imagemFundoBase64,
            'outputPath' => $caminhoAbsolutoSaida,
            'produto' => [
                'id' => $produto->id,
                'nome' => $produto->nome,
                'marca' => $produto->marca ?: ($produto->categoria ? $produto->categoria->nome : ''),
                'precoOriginal' => $precoOriginal,
                'precoPromocional' => $precoPromocionalStr,
                'precoVenda' => $precoPromocionalStr,
                'emPromocao' => $emPromocao,
                'descontoPercentual' => $descontoPercentual > 0 ? "{$descontoPercentual}%" : '',
                'badgeTexto' => $badgeTexto,
                'parcelamento' => $parcelamentoText,
                'imagemBase64' => $imagemBase64,
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
