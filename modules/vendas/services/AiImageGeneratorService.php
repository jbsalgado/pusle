<?php

namespace app\modules\vendas\services;

use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\LojaConfiguracao;

/**
 * Serviço de geração de imagens e cenários fotográficos via Inteligência Artificial.
 * Projetado para suportar provedores livres (sem custo obrigatório) e chaves personalizadas,
 * com persistência em cache local e integração direta ao Disparo em Massa de Cards.
 */
class AiImageGeneratorService
{
    // Presets Comerciais de 1 Clique
    const PRESET_ESTUDIO_MINIMALISTA   = 'estudio_minimalista';
    const PRESET_BLACK_FRIDAY          = 'black_friday';
    const PRESET_NATAL_FESTAS          = 'natal_festas';
    const PRESET_VERAO_TROPICAL        = 'verao_tropical';
    const PRESET_STREETWEAR_URBANO     = 'streetwear_urbano';
    const PRESET_LUXO_PREMIUM          = 'luxo_premium';
    const PRESET_MODELO_URBANO_CALCADO = 'modelo_urbano_calcado';
    const PRESET_MODELO_ESPORTIVO      = 'modelo_esportivo';
    const PRESET_MODELO_MODA_ESTUDIO   = 'modelo_moda_estudio';

    /**
     * Retorna a lista de presets comerciais disponíveis com labels amigáveis e ícones.
     */
    public static function getPresetsDisponiveis()
    {
        return [
            self::PRESET_ESTUDIO_MINIMALISTA => [
                'titulo' => 'Estúdio Minimalista & Mármore',
                'icone' => '🛍️',
                'descricao' => 'Pódio de luxo, mármore branco, detalhes dourados e luz comercial suave.',
                'tipo' => 'cenario',
            ],
            self::PRESET_BLACK_FRIDAY => [
                'titulo' => 'Black Friday & Liquidação Neon',
                'icone' => '⚡',
                'descricao' => 'Palco escuro dramático, luz volumétrica neon vermelha e dourada.',
                'tipo' => 'cenario',
            ],
            self::PRESET_NATAL_FESTAS => [
                'titulo' => 'Natal & Fim de Ano',
                'icone' => '🎄',
                'descricao' => 'Atmosfera natalina elegante, bokeh dourado, laços vermelhos e ramos.',
                'tipo' => 'cenario',
            ],
            self::PRESET_VERAO_TROPICAL => [
                'titulo' => 'Verão & Tropical',
                'icone' => '🌴',
                'descricao' => 'Terraço ensolarado, areia branca, céu suave e sombras naturais.',
                'tipo' => 'cenario',
            ],
            self::PRESET_STREETWEAR_URBANO => [
                'titulo' => 'Streetwear & Calçados Urbano',
                'icone' => '👟',
                'descricao' => 'Concreto urbano moderno, luz neon ciano e magenta, estilo boutique.',
                'tipo' => 'cenario',
            ],
            self::PRESET_LUXO_PREMIUM => [
                'titulo' => 'Luxo & Cosméticos / Jóias',
                'icone' => '💎',
                'descricao' => 'Pedestal de obsidiana escura, seda ondulada e micropartículas de ouro.',
                'tipo' => 'cenario',
            ],
            self::PRESET_MODELO_URBANO_CALCADO => [
                'titulo' => 'Modelo Calçando o Produto (Rua Urbana)',
                'icone' => '🧍‍♀️',
                'descricao' => 'Modelo profissional de corpo inteiro usando o produto em cenário urbano com miniatura no rodapé.',
                'tipo' => 'modelo',
            ],
            self::PRESET_MODELO_ESPORTIVO => [
                'titulo' => 'Modelo em Treino / Ação Esportiva',
                'icone' => '🏃‍♂️',
                'descricao' => 'Modelo atlético em movimento usando o calçado/roupa, luz matinal dinâmica.',
                'tipo' => 'modelo',
            ],
            self::PRESET_MODELO_MODA_ESTUDIO => [
                'titulo' => 'Modelo Editorial de Moda (Estúdio)',
                'icone' => '👗',
                'descricao' => 'Modelo fashion em pose elegante vestindo a peça, fundo de passarela/estúdio limpo.',
                'tipo' => 'modelo',
            ],
        ];
    }

    /**
     * Monta o prompt em inglês comercial baseado no preset ou no texto livre do usuário.
     */
    public function construirPrompt($preset, $promptLivre = '', $produto = null)
    {
        $promptBase = '';

        switch ($preset) {
            case self::PRESET_ESTUDIO_MINIMALISTA:
                $promptBase = 'Clean luxury commercial product podium, minimalist white marble surface with subtle brushed gold accents, soft diffused commercial studio lighting, bokeh, 8k resolution, elegant retail advertising background';
                break;

            case self::PRESET_BLACK_FRIDAY:
                $promptBase = 'Dark dramatic stage, intense volumetric neon red and warm gold lighting, futuristic sleek retail showroom, glossy floor reflections, high contrast commercial display';
                break;

            case self::PRESET_NATAL_FESTAS:
                $promptBase = 'Festive elegant Christmas luxury atmosphere, warm bokeh fairy lights, subtle golden glitter particles, fresh pine branches and deep crimson silk ribbons, soft focus background';
                break;

            case self::PRESET_VERAO_TROPICAL:
                $promptBase = 'Sunlit Mediterranean beachside terrace, pristine white sand, gentle pastel blue sky bokeh, soft tropical palm leaf shadows, fresh vibrant summer retail atmosphere';
                break;

            case self::PRESET_STREETWEAR_URBANO:
                $promptBase = 'Modern urban concrete texture, dramatic dual-tone cyan and magenta neon rim lights, high-end sneaker boutique ambiance, clean commercial showroom';
                break;

            case self::PRESET_LUXO_PREMIUM:
                $promptBase = 'Glossy dark obsidian pedestal, soft black silk drapery ripples, floating micro golden dust particles, luxury cosmetics and jewelry catalog aesthetic, cinematic lighting';
                break;

            case self::PRESET_MODELO_URBANO_CALCADO:
                $infoProd = $this->extrairInfoProduto($produto);
                $promptBase = "Full-body commercial fashion photography of a stylish professional human model walking on a clean modern city street sidewalk, wearing the exact {$infoProd} from reference, athletic casual matching outfit, natural daylight, photorealistic, with a neat picture-in-picture small product box thumbnail in the bottom corner";
                break;

            case self::PRESET_MODELO_ESPORTIVO:
                $infoProd = $this->extrairInfoProduto($produto);
                $promptBase = "Dynamic athletic action shot of a professional runner outdoors on an urban track wearing the exact {$infoProd} from reference, natural golden hour sunlight, sharp focus, with small product box thumbnail in the bottom corner";
                break;

            case self::PRESET_MODELO_MODA_ESTUDIO:
                $infoProd = $this->extrairInfoProduto($produto);
                $promptBase = "High-end fashion editorial photography of an elegant human model in studio wearing the exact {$infoProd} from reference, minimalist grey studio background, high fashion magazine lighting, with small product thumbnail in the bottom corner";
                break;

            default:
                if (!empty(trim($promptLivre))) {
                    $promptBase = trim($promptLivre);
                } else {
                    $promptBase = 'Clean commercial product showcase background, elegant studio lighting, soft depth of field, photorealistic 8k';
                }
                break;
        }

        // Se o usuário adicionou texto extra a um preset existente
        if (!empty(trim($promptLivre)) && $preset && $preset !== 'custom') {
            $promptBase .= ', ' . trim($promptLivre);
        }

        // Adiciona regras universais de qualidade comercial e exclusão de marcas d'água indesejadas
        $modificadoresQualidade = 'photorealistic, 8k resolution, professional commercial catalog advertising photography, sharp details, no watermarks, no blurry artifacts, no distorted limbs';

        return $promptBase . ', ' . $modificadoresQualidade;
    }

    /**
     * Extrai termos em inglês descritivos do produto para enriquecer o prompt da IA.
     */
    protected function extrairInfoProduto($produto)
    {
        if (!$produto instanceof Produto) {
            return 'footwear sneakers';
        }

        $termos = [];
        if (!empty($produto->nome)) {
            $termos[] = $produto->nome;
        }
        if (!empty($produto->marca)) {
            $termos[] = 'brand ' . $produto->marca;
        }
        if ($produto->categoria && !empty($produto->categoria->nome)) {
            $termos[] = $produto->categoria->nome;
        }

        return !empty($termos) ? implode(' ', $termos) : 'footwear product';
    }

    /**
     * Gera uma imagem via IA utilizando provedores livres com fallback inteligente.
     * Salva o arquivo gerado em cache local e retorna o caminho físico e URL pública.
     *
     * @param string $prompt Prompt de geração
     * @param string $formato 'feed' (1080x1080) ou 'stories' (1080x1920)
     * @param array $opcoes ['preset' => ..., 'seed' => ..., 'lojaId' => ..., 'imagemReferenciaUrl' => ...]
     * @return array
     */
    public function gerarImagem($prompt, $formato = 'feed', $opcoes = [])
    {
        $lojaId = $opcoes['lojaId'] ?? 'global';
        $seed = !empty($opcoes['seed']) ? (int)$opcoes['seed'] : mt_rand(100000, 999999);
        $referenciaUrl = $opcoes['imagemReferenciaUrl'] ?? null;

        // Dimensões otimizadas para redes sociais
        $largura = 1080;
        $altura = ($formato === 'stories') ? 1920 : 1080;

        // Limpeza preventiva de fundos temporários antigos (> 24h)
        $this->limparTemporariosAntigos(24);

        // 1. Tenta gerar via Pollinations.ai (FLUX / Turbo livre)
        try {
            $resultado = $this->gerarViaPollinations($prompt, $largura, $altura, $seed, $referenciaUrl, $lojaId);
            if ($resultado && !empty($resultado['success'])) {
                return $resultado;
            }
        } catch (\Throwable $t) {
            Yii::warning("AiImageGeneratorService: falha ao gerar via Pollinations: " . $t->getMessage(), __METHOD__);
        }

        // 2. Fallback: Provedor Secundário Livre (Hugging Face / Community Mirror)
        try {
            $resultadoFallback = $this->gerarViaHuggingFace($prompt, $largura, $altura, $seed, $lojaId);
            if ($resultadoFallback && !empty($resultadoFallback['success'])) {
                return $resultadoFallback;
            }
        } catch (\Throwable $t) {
            Yii::warning("AiImageGeneratorService: falha no fallback Hugging Face: " . $t->getMessage(), __METHOD__);
        }

        return [
            'success' => false,
            'message' => 'Não foi possível gerar a imagem no momento. O serviço de IA pode estar momentaneamente ocupado. Tente novamente em alguns instantes.',
        ];
    }

    /**
     * Motor Primário: Pollinations.ai (Livre, sem necessidade de cartão de crédito)
     */
    protected function gerarViaPollinations($prompt, $largura, $altura, $seed, $referenciaUrl = null, $lojaId = 'global')
    {
        $promptEncoded = rawurlencode($prompt);
        
        // Monta URL de geração de alta resolução
        $url = "https://image.pollinations.ai/prompt/{$promptEncoded}?width={$largura}&height={$altura}&seed={$seed}&nologo=true";

        if (!empty($referenciaUrl) && filter_var($referenciaUrl, FILTER_VALIDATE_URL)) {
            $url .= '&image=' . urlencode($referenciaUrl);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45); // Timeout generoso para renderização de IA
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Pulse-ERP-AI/2.0 (image-generator)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: image/webp,image/jpeg,image/png,*/*',
        ]);

        $conteudoImagem = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($conteudoImagem === false || $httpCode !== 200 || empty($conteudoImagem)) {
            Yii::warning("Pollinations retornou HTTP {$httpCode}. Erro cURL: {$curlError}", __METHOD__);
            return ['success' => false, 'message' => "Servidor de IA respondeu com código {$httpCode}."];
        }

        // Valida se o conteúdo retornado é realmente uma imagem binária
        if (strpos($contentType, 'image/') === false && @getimagesizefromstring($conteudoImagem) === false) {
            Yii::warning("Pollinations não retornou imagem válida. Content-Type: {$contentType}", __METHOD__);
            return ['success' => false, 'message' => 'Resposta da IA não foi reconhecida como imagem válida.'];
        }

        return $this->salvarImagemLocal($conteudoImagem, $lojaId, $prompt, $seed, $largura, $altura);
    }

    /**
     * Motor de Fallback: Hugging Face Serverless / Mirror
     */
    protected function gerarViaHuggingFace($prompt, $largura, $altura, $seed, $lojaId = 'global')
    {
        // Se houver chave configurada no sistema/env, utiliza; caso contrário, usa mirror comunitário
        $hfToken = getenv('HUGGINGFACE_API_TOKEN') ?: null;
        $loja = LojaConfiguracao::findOne(['usuario_id' => $lojaId]);
        if ($loja && !empty($loja->ai_api_key)) {
            $hfToken = $loja->ai_api_key;
        }

        if (!$hfToken) {
            return ['success' => false, 'message' => 'Fallback Hugging Face requer token configurado.'];
        }

        $endpoint = "https://api-inference.huggingface.co/models/black-forest-labs/FLUX.1-schnell";
        $payload = json_encode([
            'inputs' => $prompt,
            'parameters' => [
                'width' => $largura,
                'height' => $altura,
                'seed' => $seed,
            ]
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $hfToken
        ]);

        $conteudoImagem = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($conteudoImagem)) {
            return $this->salvarImagemLocal($conteudoImagem, $lojaId, $prompt, $seed, $largura, $altura);
        }

        return ['success' => false, 'message' => "Hugging Face respondeu com HTTP {$httpCode}."];
    }

    /**
     * Salva o fluxo binário da imagem gerada no diretório local de uploads do Pulse.
     */
    protected function salvarImagemLocal($conteudoImagem, $lojaId, $prompt, $seed, $largura, $altura)
    {
        $diretorioUpload = Yii::getAlias('@app/web/uploads/cards/ai_backgrounds');
        if (!is_dir($diretorioUpload)) {
            FileHelper::createDirectory($diretorioUpload, 0777, true);
        }

        $hash = substr(md5($prompt . $seed . microtime()), 0, 10);
        $nomeArquivo = sprintf('ai_%s_%s_%s.jpg', substr(preg_replace('/[^a-zA-Z0-9]/', '', (string)$lojaId), 0, 8), time(), $hash);
        $caminhoFisico = $diretorioUpload . DIRECTORY_SEPARATOR . $nomeArquivo;

        $salvou = file_put_contents($caminhoFisico, $conteudoImagem);
        if (!$salvou) {
            return ['success' => false, 'message' => 'Falha ao salvar a imagem gerada no disco do servidor.'];
        }

        $caminhoRelativo = 'uploads/cards/ai_backgrounds/' . $nomeArquivo;
        $urlPublica = Url::to('@web/' . $caminhoRelativo, true);

        return [
            'success' => true,
            'url' => $urlPublica,
            'caminho_relativo' => $caminhoRelativo,
            'caminho_fisico' => $caminhoFisico,
            'seed' => $seed,
            'largura' => $largura,
            'altura' => $altura,
            'prompt' => $prompt,
        ];
    }

    /**
     * Remove fundos gerados por IA com mais de $horas de idade para evitar acúmulo de disco.
     */
    public function limparTemporariosAntigos($horas = 24)
    {
        try {
            $diretorio = Yii::getAlias('@app/web/uploads/cards/ai_backgrounds');
            if (!is_dir($diretorio)) {
                return;
            }

            $arquivos = glob($diretorio . DIRECTORY_SEPARATOR . 'ai_*.*');
            $limiteTempo = time() - ($horas * 3600);

            if ($arquivos) {
                foreach ($arquivos as $arquivo) {
                    if (is_file($arquivo) && filemtime($arquivo) < $limiteTempo) {
                        @unlink($arquivo);
                    }
                }
            }
        } catch (\Throwable $t) {
            Yii::warning("Erro ao limpar temporários de IA: " . $t->getMessage(), __METHOD__);
        }
    }
}
