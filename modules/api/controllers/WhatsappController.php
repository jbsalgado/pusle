<?php

namespace app\modules\api\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use GuzzleHttp\Client;

/**
 * Class WhatsappController
 *
 * Proxy seguro para envio de mensagens via WhatsApp (Evolution API / Node Wrapper).
 *
 * Estratégia para envio de imagens:
 * 1. Recebe a imagem em base64 do frontend.
 * 2. Salva o arquivo temporariamente em /web/uploads/whatsapp/ (pasta pública).
 * 3. Envia a URL pública do arquivo para o wrapper Node, que a baixa e envia via WhatsApp.
 * 4. Limpa arquivos com mais de 1 hora automaticamente.
 *
 * Por que URL e não base64 direto?
 * O wrapper Node leve instalado na VPS só suporta envio de texto.
 * Ao hospedar a imagem no próprio servidor (em produção, o mesmo IP),
 * o wrapper consegue acessar e encaminhar a mídia ao WhatsApp.
 */
class WhatsappController extends BaseController
{
    public $enableCsrfValidation = false;

    // Diretório público onde as imagens serão salvas temporariamente
    const UPLOAD_DIR = '@webroot/uploads/whatsapp';
    const UPLOAD_URL = '/uploads/whatsapp';
    const FILE_TTL   = 3600; // 1 hora em segundos

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // Torna a autenticação Bearer opcional para a action 'send', 
        // permitindo que o Yii2 use a sessão (Cookie) do backend se o token não for enviado.
        if (isset($behaviors['authenticator'])) {
            $behaviors['authenticator']['optional'] = ['send'];
        }
        return $behaviors;
    }

    /**
     * Envia mensagem de texto ou imagem via WhatsApp.
     *
     * POST /api/whatsapp/send
     * Campos aceitos:
     *   - numero  (obrigatório)
     *   - mensagem (texto ou legenda da imagem)
     *   - base64  (imagem em base64 com ou sem prefixo data:)
     */
    public function actionSend()
    {
        // Garante que a requisição está autenticada (seja por Token ou por Sessão)
        if (Yii::$app->user->isGuest) {
            throw new \yii\web\UnauthorizedHttpException('Autenticação necessária.');
        }

        $request = Yii::$app->request;
        if (!$request->isPost) {
            throw new BadRequestHttpException('Apenas requisições POST são permitidas.');
        }

        $data     = json_decode($request->getRawBody(), true);
        $numero   = $data['numero']   ?? null;
        $mensagem = $data['mensagem'] ?? null;
        $base64   = $data['base64']   ?? null;

        if (!$numero) {
            throw new BadRequestHttpException('O número de WhatsApp é obrigatório.');
        }
        if (!$mensagem && !$base64) {
            throw new BadRequestHttpException('Mensagem ou imagem base64 deve ser informada.');
        }

        // 1. Identificar a empresa (tenant) ativa
        $usuario = Yii::$app->user->identity;
        if (!$usuario) {
            throw new BadRequestHttpException('Tenant não identificado.');
        }
        $empresaId = $usuario->getTenantId();

        $config = \app\modules\evolution\models\WhatsappConfig::findByEmpresa($empresaId);
        if ($config === null || empty($config->token)) {
            throw new BadRequestHttpException('Integração com WhatsApp não configurada ou inativa para esta empresa.');
        }

        // 2. Sanitização e normalização do número
        $numero = preg_replace('/[^0-9]/', '', $numero);

        // Adicionar DDI 55 se necessário
        if (strlen($numero) === 11) {
            $numero = '55' . $numero;
        } elseif (strlen($numero) === 10) {
            $ddd  = substr($numero, 0, 2);
            $rest = substr($numero, 2);
            $numero = '55' . $ddd . '9' . $rest;
        }

        // Normalização do nono dígito:
        // WhatsApp BR remove o 9 para DDDs >= 20 (fora de São Paulo).
        if (strlen($numero) === 13 && strpos($numero, '55') === 0) {
            $ddd = (int) substr($numero, 2, 2);
            if ($ddd >= 20 && substr($numero, 4, 1) === '9') {
                $numero = '55' . $ddd . substr($numero, 5);
            }
        }

        // 3. Anti-banimento: variação sutil no texto final
        $textoFinal = $mensagem ?: 'Comprovante';
        if ($mensagem) {
            // Adiciona referência única invisível para evitar mensagens 100% idênticas
            $textoFinal .= "\n\n_Ref: " . substr(uniqid(), -5) . '_';
        }

        // 3.5. Cálculo do delay dinâmico
        $delayMin = isset($config->delay_min) ? (int)$config->delay_min : 1500;
        $delayMax = isset($config->delay_max) ? (int)$config->delay_max : 2500;
        if ($delayMin > $delayMax) {
            $delayMax = $delayMin;
        }
        $delay = rand($delayMin, $delayMax);
        $simularDigitacao = isset($config->simular_digitacao) ? (bool)$config->simular_digitacao : true;

        $apiDelay = 0;
        if ($delay > 0) {
            if ($simularDigitacao) {
                // Passa o delay diretamente para a API do Go para simular digitação (composing)
                $apiDelay = $delay;
            } else {
                // Apenas aguarda localmente no PHP sem simular digitação
                usleep($delay * 1000);
            }
        }

        // 4. Configurações da API Evolution Go
        $evolutionConfig = Yii::$app->params['evolution'] ?? [];
        $baseUrl = rtrim($evolutionConfig['baseUrl'] ?? 'http://localhost:8080', '/');

        // 5. Limpeza de imagens antigas (Opcional, mantido para limpar sujeira passada)
        $this->limparImagensAntigas();

        try {
            $client = new \yii\httpclient\Client(['baseUrl' => $baseUrl]);

            if ($base64) {
                // Na versão Evolution Go v0.7.1, enviamos o código puro (Base64) direto para API.
                // O motor vai entender sozinho, evitando problemas de download de URLs de Loopback!
                $cleanBase64 = preg_replace('/^data:image\/[a-z]+;base64,/i', '', $base64);
                
                // Tenta descobrir a extensão a partir do prefixo original, senao assume jpg
                $extension = 'jpg';
                if (preg_match('/^data:image\/([a-z]+);base64,/i', $base64, $matches)) {
                    $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                }

                $response = $client->createRequest()
                    ->setMethod('POST')
                    ->setFormat(\yii\httpclient\Client::FORMAT_JSON)
                    ->setUrl('/send/media')
                    ->addHeaders([
                        'Content-Type' => 'application/json',
                        'apikey'       => $config->token, // token da instância do tenant
                    ])
                    ->setData([
                        'number'   => $numero,
                        'url'      => $cleanBase64,
                        'type'     => 'image',
                        'caption'  => $textoFinal,
                        'filename' => 'comprovante.' . $extension,
                        'delay'    => $apiDelay,
                    ])
                    ->send();

            } else {
                $response = $client->createRequest()
                    ->setMethod('POST')
                    ->setFormat(\yii\httpclient\Client::FORMAT_JSON)
                    ->setUrl('/send/text')
                    ->addHeaders([
                        'Content-Type' => 'application/json',
                        'apikey'       => $config->token,
                    ])
                    ->setData([
                        'number' => $numero,
                        'text'   => $textoFinal,
                        'delay'  => $apiDelay,
                    ])
                    ->send();
            }

            if (!$response->isOk) {
                Yii::error('Erro na Evolution API: ' . $response->statusCode . ' ' . $response->content, __METHOD__);
                return $this->error('Erro ao enviar mensagem via WhatsApp: ' . $response->content, $response->statusCode);
            }

            $body = json_decode($response->content, true);
            return $this->success($body, 'Mensagem enviada com sucesso para o WhatsApp.');

        } catch (\Exception $e) {
            Yii::error('Exceção ao enviar mensagem WhatsApp: ' . $e->getMessage(), __METHOD__);
            throw new ServerErrorHttpException('Erro de comunicação com o servidor de WhatsApp: ' . $e->getMessage());
        }
    }

    /**
     * Salva a imagem (base64) em arquivo temporário público.
     * Retorna o caminho relativo (URL path) ou null em caso de falha.
     */
    private function salvarImagemTemporaria(string $base64): ?string
    {
        try {
            // Detectar tipo e extrair dados puros
            $mimeType  = 'image/jpeg';
            $extension = 'jpg';
            $rawBase64 = $base64;

            if (preg_match('/^data:(image\/[a-z]+);base64,(.+)$/i', $base64, $matches)) {
                $mimeType  = $matches[1];
                $rawBase64 = $matches[2];

                $ext_map = [
                    'image/jpeg' => 'jpg',
                    'image/jpg'  => 'jpg',
                    'image/png'  => 'png',
                    'image/gif'  => 'gif',
                    'image/webp' => 'webp',
                ];
                $extension = $ext_map[strtolower($mimeType)] ?? 'jpg';
            }

            $imageData = base64_decode($rawBase64);
            if (!$imageData || strlen($imageData) < 100) {
                Yii::warning('Base64 inválido ou muito pequeno para salvar.', __METHOD__);
                return null;
            }

            $uploadDir = Yii::getAlias(self::UPLOAD_DIR);
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Nome único com timestamp para garantir que o cache não interfira
            $filename = 'wz_' . time() . '_' . substr(md5(rand()), 0, 6) . '.' . $extension;
            $filePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

            if (file_put_contents($filePath, $imageData) === false) {
                Yii::error('Não foi possível salvar imagem em: ' . $filePath, __METHOD__);
                return null;
            }

            chmod($filePath, 0644);
            Yii::info('Imagem WhatsApp salva: ' . $filename, __METHOD__);

            return self::UPLOAD_URL . '/' . $filename;

        } catch (\Exception $e) {
            Yii::error('Erro ao salvar imagem temporária: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Remove imagens WhatsApp temporárias com mais de 1 hora.
     */
    private function limparImagensAntigas(): void
    {
        try {
            $uploadDir = Yii::getAlias(self::UPLOAD_DIR);
            if (!is_dir($uploadDir)) {
                return;
            }
            $agora = time();
            foreach (glob($uploadDir . '/wz_*.{jpg,png,gif,webp}', GLOB_BRACE) as $arquivo) {
                if (($agora - filemtime($arquivo)) > self::FILE_TTL) {
                    @unlink($arquivo);
                }
            }
        } catch (\Exception $e) {
            // Silencioso — limpeza não pode interromper o fluxo principal
        }
    }
}
