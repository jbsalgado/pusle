<?php

namespace app\modules\api\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use GuzzleHttp\Client;
use app\modules\evolution\models\WhatsappConfig;
use app\modules\vendas\models\BridgeWhatsappLoja;
use app\modules\vendas\models\BridgeWhatsappMensagem;
use app\modules\vendas\services\BridgeWhatsappService;

/**
 * Class WhatsappController
 *
 * Proxy inteligente e unificado para envio de mensagens via WhatsApp:
 * - Detecta automaticamente a conexão ativa: WhatsApp Local (Agente Whatsmeow) ou Evolution API (Cloud / Meta).
 * - Suporta envio de texto e comprovantes/mídias em Base64 para ambos os canais.
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
        // Torna a autenticação Bearer opcional para 'send' e 'status-conexao',
        // permitindo que o Yii2 use a sessão (Cookie) do backend se o token não for enviado.
        if (isset($behaviors['authenticator'])) {
            $behaviors['authenticator']['optional'] = ['send', 'status-conexao'];
        }
        return $behaviors;
    }

    /**
     * Detecta automaticamente as conexões de WhatsApp ativas para a empresa.
     * Retorna o canal prioritário ('agente_local' | 'evolution' | 'nenhum') e detalhes das conexões.
     */
    public static function detectarConexaoWhatsapp(string $empresaId): array
    {
        // 1. Canal WhatsApp Local (Agente Whatsmeow / Pulse Bridge)
        $bridge = BridgeWhatsappLoja::findOne(['usuario_id' => $empresaId]);
        $agenteOnline = $bridge ? $bridge->isAgenteOnline() : false;
        $agenteConectado = $bridge ? $bridge->isWhatsappConectado() : false;
        $agenteTelefone = $bridge ? $bridge->telefone_conectado : null;
        $agenteNome = $bridge ? $bridge->push_name : null;

        // 2. Canal Evolution API / Meta Cloud
        $config = WhatsappConfig::findByEmpresa($empresaId);
        $evolutionConectado = false;
        $evolutionTipo = 'evolution';
        $evolutionTelefone = null;

        if ($config !== null) {
            if ($config->isMetaOficial()) {
                $evolutionConectado = true;
                $evolutionTipo = 'meta_cloud';
                $evolutionTelefone = $config->meta_phone_number_id;
            } elseif ($config->status === 'CONNECTED' && !empty($config->token)) {
                $evolutionConectado = true;
                $evolutionTipo = 'evolution';
            }
        }

        // 3. Determinação do Canal Ativo / Prioritário
        // Se o Agente Local estiver com WhatsApp conectado e online na máquina física do caixa, prioriza ele.
        // Caso contrário, se a Evolution API estiver conectada no servidor, utiliza Evolution API.
        $canalAtivo = 'nenhum';
        $descricao = 'Nenhum WhatsApp conectado';

        if ($agenteConectado) {
            $canalAtivo = 'agente_local';
            $descricao = 'WhatsApp Local (Agente)' . ($agenteNome ? " - {$agenteNome}" : '') . ($agenteTelefone ? " ({$agenteTelefone})" : '');
        } elseif ($evolutionConectado) {
            $canalAtivo = 'evolution';
            $descricao = ($evolutionTipo === 'meta_cloud') ? 'WhatsApp Meta Oficial' : 'WhatsApp Cloud (Evolution API)';
        }

        return [
            'canal_ativo' => $canalAtivo,
            'descricao' => $descricao,
            'agente_local' => [
                'configurado' => (bool)$bridge,
                'online' => $agenteOnline,
                'conectado' => $agenteConectado,
                'telefone' => $agenteTelefone,
                'nome' => $agenteNome,
            ],
            'evolution' => [
                'configurado' => (bool)$config,
                'conectado' => $evolutionConectado,
                'tipo' => $evolutionTipo,
                'telefone' => $evolutionTelefone,
            ],
        ];
    }

    /**
     * Retorna o status das conexões de WhatsApp ativas do tenant atual.
     * GET /api/whatsapp/status-conexao
     */
    public function actionStatusConexao()
    {
        if (Yii::$app->user->isGuest) {
            throw new \yii\web\UnauthorizedHttpException('Autenticação necessária.');
        }

        $usuario = Yii::$app->user->identity;
        if (!$usuario) {
            throw new BadRequestHttpException('Tenant não identificado.');
        }
        $empresaId = $usuario->getTenantId();

        $status = self::detectarConexaoWhatsapp($empresaId);
        return $this->success($status);
    }

    /**
     * Envia mensagem de texto ou imagem via WhatsApp utilizando o canal ativo
     * detectado automaticamente (Agente Local ou Evolution API).
     *
     * POST /api/whatsapp/send
     * Campos aceitos:
     *   - numero   (obrigatório)
     *   - mensagem (texto ou legenda da imagem)
     *   - base64   (imagem em base64 com ou sem prefixo data:)
     *   - canal    (opcional: 'agente_local' | 'evolution' para forçar canal)
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

        $data          = json_decode($request->getRawBody(), true) ?: [];
        $numero        = $data['numero']        ?? null;
        $mensagem      = $data['mensagem']      ?? null;
        $base64        = $data['base64']        ?? null;
        $canalDesejado = $data['canal']         ?? null;

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

        // 4. Detecção automática de conexão ativa
        $statusConexao = self::detectarConexaoWhatsapp($empresaId);
        $canal = $canalDesejado ?: $statusConexao['canal_ativo'];

        // Se o canal desejado foi forçado mas não está conectado, recai no canal_ativo detectado
        if ($canal === 'agente_local' && !$statusConexao['agente_local']['conectado']) {
            $canal = $statusConexao['canal_ativo'];
        } elseif ($canal === 'evolution' && !$statusConexao['evolution']['conectado']) {
            $canal = $statusConexao['canal_ativo'];
        }

        // Caso nenhum canal esteja conectado
        if ($canal === 'nenhum') {
            $fallbackUrl = 'https://api.whatsapp.com/send?phone=' . $numero . '&text=' . rawurlencode($textoFinal);
            return [
                'success' => false,
                'canal' => 'nenhum',
                'message' => 'Nenhuma conexão de WhatsApp está ativa no momento (Agente Local offline e Evolution API desconectada).',
                'fallback_url' => $fallbackUrl,
            ];
        }

        // 5. Roteamento: DISPARO VIA AGENTE LOCAL (Pulse Bridge Go Whatsmeow)
        if ($canal === 'agente_local') {
            $midiaUrl = null;
            $tipoMsg = BridgeWhatsappMensagem::TIPO_TEXT;

            if ($base64) {
                $caminhoRelativo = $this->salvarImagemTemporaria($base64);
                if ($caminhoRelativo) {
                    $midiaUrl = Yii::$app->request->hostInfo . $caminhoRelativo;
                    $tipoMsg = BridgeWhatsappMensagem::TIPO_IMAGE;
                }
            }

            $resFila = BridgeWhatsappService::enfileirarMensagem(
                $empresaId,
                $numero,
                $textoFinal,
                $midiaUrl,
                $tipoMsg
            );

            if ($resFila['success']) {
                return $this->success([
                    'canal' => 'agente_local',
                    'mensagem_id' => $resFila['mensagem_id'] ?? null,
                    'telefone_conectado' => $statusConexao['agente_local']['telefone'] ?? null,
                ], 'Comprovante enfileirado com sucesso para envio via WhatsApp Local (Agente)!');
            } else {
                return $this->error('Falha ao enfileirar no Agente Local: ' . ($resFila['message'] ?? 'Erro desconhecido'), 500);
            }
        }

        // 6. Roteamento: DISPARO VIA EVOLUTION API (Cloud Go Engine)
        if ($canal === 'evolution') {
            $config = WhatsappConfig::findByEmpresa($empresaId);
            if ($config === null || empty($config->token)) {
                return $this->error('Instância da Evolution API não configurada ou inativa.', 400);
            }

            // Validação de limite diário de mensagens por loja
            if (!$config->podeEnviarHoje()) {
                return $this->error("Limite diário de envios atingido para este WhatsApp ({$config->mensagens_enviadas_hoje}/{$config->limite_diario_mensagens}). Envios pausados por segurança anti-ban.", 429);
            }

            // Cálculo do delay dinâmico seguro
            $delayMin = isset($config->delay_min) ? (int)$config->delay_min : 15000;
            $delayMax = isset($config->delay_max) ? (int)$config->delay_max : 45000;
            if ($delayMin > $delayMax) {
                $delayMax = $delayMin;
            }
            $delay = rand($delayMin, $delayMax);
            $simularDigitacao = isset($config->simular_digitacao) ? (bool)$config->simular_digitacao : true;

            $apiDelay = 0;
            if ($delay > 0 && $simularDigitacao) {
                $apiDelay = min(3000, $delay);
            }

            $evolutionConfig = Yii::$app->params['evolution'] ?? [];
            $baseUrl = rtrim($evolutionConfig['baseUrl'] ?? 'http://localhost:8080', '/');

            // Limpeza de imagens antigas
            $this->limparImagensAntigas();

            try {
                $client = new \yii\httpclient\Client(['baseUrl' => $baseUrl]);

                if ($base64) {
                    // Aplica o Anti-Ban Media Randomizer para quebrar o hash de imagens duplicadas
                    $cleanBase64 = \app\modules\evolution\helpers\MediaRandomizerHelper::randomizeImageHash($base64);
                    $cleanBase64 = preg_replace('/^data:image\/[a-z]+;base64,/i', '', $cleanBase64);
                    
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

                $config->incrementarEnvioHoje();
                $body = json_decode($response->content, true);
                return $this->success(array_merge(is_array($body) ? $body : [], [
                    'canal' => 'evolution'
                ]), 'Mensagem enviada com sucesso para o WhatsApp.');

            } catch (\Exception $e) {
                Yii::error('Exceção ao enviar mensagem WhatsApp: ' . $e->getMessage(), __METHOD__);
                throw new ServerErrorHttpException('Erro de comunicação com o servidor de WhatsApp: ' . $e->getMessage());
            }
        }

        return $this->error('Canal de WhatsApp não suportado.', 400);
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
