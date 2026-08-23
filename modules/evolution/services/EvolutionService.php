<?php

namespace app\modules\evolution\services;

use app\modules\evolution\models\WhatsappConfig;
use Yii;
use yii\httpclient\Client;
use yii\httpclient\Exception as HttpClientException;

/**
 * EvolutionService — Camada de integração com a Evolution API Go (Engine v0.7.1).
 *
 * Centraliza todas as chamadas HTTP ao motor Go e isola a lógica de persistência
 * local dos metadados de conexão WhatsApp por empresa/tenant (multi-loja).
 *
 * Regras estritas de autenticação HTTP (homologadas):
 *   - Ações administrativas globais: header `apiKey` (K maiúsculo) → global key.
 *   - Ações de envio/mensagens:       header `apikey` (tudo minúsculo) → token da instância.
 *
 * Configuração esperada em config/params.php:
 *   'evolution' => [
 *       'baseUrl'      => 'http://localhost:8083',
 *       'globalApiKey' => 'SUA_GLOBAL_KEY_AQUI',
 *   ],
 */
class EvolutionService
{
    /**
     * URL base da Evolution API Go, lida de Yii::$app->params.
     */
    private string $baseUrl;

    /**
     * Chave global da API, usada em ações administrativas.
     */
    private string $globalApiKey;

    public function __construct()
    {
        $rawUrl             = rtrim(Yii::$app->params['evolution']['baseUrl'], '/');
        $this->baseUrl      = $this->resolveBaseUrl($rawUrl);
        $this->globalApiKey = Yii::$app->params['evolution']['globalApiKey'];
    }

    /**
     * Resolve dinamicamente a URL base ativa da Evolution API no servidor.
     * Caso a porta configurada esteja indisponível, testa a porta alternativa (4000 <-> 8080).
     */
    private function resolveBaseUrl(string $configuredUrl): string
    {
        if (strpos($configuredUrl, 'localhost') === false && strpos($configuredUrl, '127.0.0.1') === false) {
            return $configuredUrl;
        }

        $parsed = parse_url($configuredUrl);
        $port   = $parsed['port'] ?? 8080;

        $fp = @fsockopen('127.0.0.1', (int)$port, $errno, $errstr, 0.2);
        if (is_resource($fp)) {
            fclose($fp);
            return $configuredUrl;
        }

        // Tenta a porta alternativa (4000 se configurado 8080, ou 8080 se configurado 4000)
        $altPort = ((int)$port === 4000) ? 8080 : 4000;
        $fpAlt   = @fsockopen('127.0.0.1', $altPort, $errno, $errstr, 0.2);
        if (is_resource($fpAlt)) {
            fclose($fpAlt);
            return "http://localhost:{$altPort}";
        }

        return $configuredUrl;
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Cria (ou reutiliza) uma instância WhatsApp no motor Go para a empresa.
     *
     * Se já existir registro local com token, retorna direto o QR Code atual.
     * Caso contrário, cria uma nova instância na API Go, salva os metadados
     * localmente e retorna a string Base64 do QR Code para exibição imediata.
     *
     * @param string $empresaId UUID do tenant em prest_usuarios
     * @return string|null      String Base64 do QR Code ou null em caso de falha
     */
    public function createInstance(string $empresaId): ?string
    {
        $instanceName = $this->buildInstanceName($empresaId);

        // Garante que o registro local existe (upsert mínimo)
        $config = WhatsappConfig::findByEmpresa($empresaId);
        if ($config === null) {
            $config                = new WhatsappConfig();
            $config->empresa_id    = $empresaId;
            $config->instance_name = $instanceName;
            $config->status        = 'DISCONNECTED';
        }

        try {
            $client = new Client(['baseUrl' => $this->baseUrl]);

            // 1. Consulta motor GO para verificar se a instância já existe e recuperar seu UUID
            $existingUuid = null;
            $isConnected  = false;

            try {
                $allResponse = $client->createRequest()
                    ->setMethod('GET')
                    ->setUrl('/instance/all')
                    ->addHeaders(['apiKey' => $this->globalApiKey])
                    ->send();

                if ($allResponse->isOk) {
                    $responseData = json_decode($allResponse->content, true);
                    $instancesList = $responseData['data'] ?? (is_array($responseData) ? $responseData : []);
                    foreach ($instancesList as $inst) {
                        $name = $inst['name'] ?? $inst['instanceName'] ?? null;
                        if ($name === $instanceName) {
                            $existingUuid = $inst['id'] ?? null;
                            $isConnected  = !empty($inst['connected']);

                            // Se encontrou uma instância antiga não conectada, deleta usando seu UUID
                            if (!$isConnected && !empty($existingUuid)) {
                                try {
                                    $client->createRequest()
                                        ->setMethod('DELETE')
                                        ->setUrl("/instance/delete/{$existingUuid}")
                                        ->addHeaders(['apiKey' => $this->globalApiKey])
                                        ->send();
                                    sleep(1);
                                } catch (\Throwable $dt) {
                                    Yii::warning("EvolutionService::createInstance — aviso ao deletar instância antiga: " . $dt->getMessage(), __METHOD__);
                                }
                            }
                            break;
                        }
                    }
                }
            } catch (\Throwable $t) {
                Yii::warning("EvolutionService::createInstance — erro ao consultar /instance/all: " . $t->getMessage(), __METHOD__);
            }

            // Se já estiver conectada no WhatsApp, salva status e encerra (não precisa de QR Code)
            if ($isConnected) {
                $config->status = 'CONNECTED';
                $config->save(false);
                return null;
            }

            // 2. Cria instância limpa no motor GO
            $instanceToken = Yii::$app->security->generateRandomString(32);
            $createResponse = $client->createRequest()
                ->setMethod('POST')
                ->setFormat(Client::FORMAT_JSON)
                ->setUrl('/instance/create')
                ->addHeaders([
                    'Content-Type' => 'application/json',
                    'apiKey'       => $this->globalApiKey,
                ])
                ->setData([
                    'name'         => $instanceName,
                    'instanceName' => $instanceName,
                    'token'        => $instanceToken,
                    'qrcode'       => true,
                ])
                ->send();

            if (!$createResponse->isOk) {
                Yii::error("EvolutionService::createInstance — falha ao criar instância: " . $createResponse->content, __METHOD__);
                return null;
            }

            $config->token  = $instanceToken;
            $config->status = 'DISCONNECTED';
            $config->save(false);

            // 3. Conecta para disparar emissão do QR Code pelo Baileys
            $client->createRequest()
                ->setMethod('POST')
                ->setFormat(Client::FORMAT_JSON)
                ->setUrl('/instance/connect')
                ->addHeaders(['apikey' => $instanceToken])
                ->setData(['qrcode' => true])
                ->send();

            // 4. Polling do QR Code (até 12 segundos para o Baileys emitir a imagem)
            $qrBase64 = null;
            for ($i = 0; $i < 12; $i++) {
                sleep(1);

                $qrResponse = $client->createRequest()
                    ->setMethod('GET')
                    ->setUrl('/instance/qr')
                    ->addHeaders(['apikey' => $instanceToken])
                    ->send();

                if ($qrResponse->isOk) {
                    $qrBody = json_decode($qrResponse->content, true);

                    if (is_array($qrBody)) {
                        $rawQr = $qrBody['data']['Qrcode'] 
                            ?? $qrBody['data']['qrcode']
                            ?? $qrBody['qrcode']['base64'] 
                            ?? $qrBody['qrcode'] 
                            ?? $qrBody['base64'] 
                            ?? null;

                        if (is_array($rawQr) && isset($rawQr['base64'])) {
                            $rawQr = $rawQr['base64'];
                        }

                        if (!empty($rawQr) && is_string($rawQr)) {
                            $qrBase64 = $rawQr;
                            break;
                        }
                    }
                }
            }

            if (!empty($qrBase64) && strpos($qrBase64, 'data:image') !== 0) {
                $qrBase64 = 'data:image/png;base64,' . ltrim($qrBase64, 'data:image/png;base64,');
            }

            return $qrBase64;
        } catch (HttpClientException $e) {
            Yii::error(
                "EvolutionService::createInstance — falha HTTP: " . $e->getMessage(),
                __METHOD__
            );
            return null;
        }
    }

    /**
     * Envia uma mensagem de texto para um número via instância da empresa.
     *
     * Recupera o token específico do banco local, sanitiza o número de destino
     * e dispara o POST para a Evolution API Go usando o header `apikey` (minúsculo).
     *
     * @param string $empresaId UUID do tenant em prest_usuarios
     * @param string $to        Número de destino (com ou sem formatação)
     * @param string $text      Texto da mensagem
     * @return bool             true em caso de sucesso, false em falha
     */
    public function sendMessage(string $empresaId, string $to, string $text): bool
    {
        $config = WhatsappConfig::findByEmpresa($empresaId);

        if ($config === null || empty($config->token)) {
            Yii::error(
                "EvolutionService::sendMessage — instância não encontrada ou sem token para empresa {$empresaId}.",
                __METHOD__
            );
            return false;
        }

        $sanitizedNumber = $this->sanitizePhoneNumber($to);
        if (strlen($sanitizedNumber) > 13 || strlen($sanitizedNumber) < 10) {
            Yii::error("EvolutionService::sendMessage — número de telefone inválido: '{$to}' (sanitizado: '{$sanitizedNumber}')", __METHOD__);
            return false;
        }

        try {
            $client   = new Client(['baseUrl' => $this->baseUrl]);
            $response = $client->createRequest()
                ->setMethod('POST')
                ->setFormat(Client::FORMAT_JSON)
                ->setUrl('/send/text')
                ->addHeaders([
                    'Content-Type' => 'application/json',
                    'apikey'       => $config->token, // minúsculo — token da instância
                ])
                ->setData([
                    'number'  => $sanitizedNumber,
                    'text'    => $text,
                    'options' => ['delay' => 1200],
                ])
                ->send();

            if (!$response->isOk) {
                Yii::error(
                    "EvolutionService::sendMessage — resposta não-OK: "
                    . $response->statusCode . ' ' . $response->content,
                    __METHOD__
                );
                return false;
            }

            return true;
        } catch (HttpClientException $e) {
            Yii::error(
                "EvolutionService::sendMessage — falha HTTP: " . $e->getMessage(),
                __METHOD__
            );
            return false;
        }
    }

    /**
     * Verifica o status de conexão da instância da empresa no motor Go.
     *
     * Faz um GET em /instance/all (endpoint administrativo global), localiza
     * a instância pelo instanceName e atualiza o registro local com o status
     * retornado ('CONNECTED' ou 'DISCONNECTED').
     *
     * @param string $empresaId UUID do tenant em prest_usuarios
     * @return bool             true se conectado, false se desconectado ou erro
     */
    public function checkStatus(string $empresaId): bool
    {
        $instanceName = $this->buildInstanceName($empresaId);

        try {
            $client   = new Client(['baseUrl' => $this->baseUrl]);
            $response = $client->createRequest()
                ->setMethod('GET')
                ->setUrl('/instance/all')
                ->addHeaders([
                    'Content-Type' => 'application/json',
                    'apiKey'       => $this->globalApiKey, // K maiúsculo — admin global
                ])
                ->send();

            if (!$response->isOk) {
                Yii::error(
                    "EvolutionService::checkStatus — resposta não-OK: "
                    . $response->statusCode . ' ' . $response->content,
                    __METHOD__
                );
                return false;
            }

            $responseData = json_decode($response->content, true);
            $instancesList = [];
            if (isset($responseData['data']) && is_array($responseData['data'])) {
                $instancesList = $responseData['data'];
            } elseif (is_array($responseData)) {
                $instancesList = $responseData;
            }

            $connected = false;
            foreach ($instancesList as $instance) {
                $name = $instance['name']
                    ?? $instance['instanceName']
                    ?? $instance['instance']['instanceName']
                    ?? $instance['instance']['name']
                    ?? null;

                if ($name === $instanceName) {
                    $connected = (bool) (
                        $instance['connected']
                        ?? $instance['instance']['connected']
                        ?? false
                    );
                    break;
                }
            }

            // Atualiza o status persistido no banco local
            $config = WhatsappConfig::findByEmpresa($empresaId);
            if ($config !== null) {
                $config->status = $connected ? 'CONNECTED' : 'DISCONNECTED';
                $config->save(false);
            }

            return $connected;
        } catch (HttpClientException $e) {
            Yii::error(
                "EvolutionService::checkStatus — falha HTTP: " . $e->getMessage(),
                __METHOD__
            );
            return false;
        }
    }

    /**
     * Deleta/desconecta a instância da empresa no motor Go e atualiza o banco local.
     *
     * @param string $empresaId UUID do tenant em prest_usuarios
     * @return bool             true em caso de sucesso, false em falha
     */
    public function deleteInstance(string $empresaId): bool
    {
        $instanceName = $this->buildInstanceName($empresaId);

        try {
            $client   = new Client(['baseUrl' => $this->baseUrl]);

            // Busca o ID UUID da instância no motor Go
            $targetId = $instanceName;
            try {
                $allResponse = $client->createRequest()
                    ->setMethod('GET')
                    ->setUrl('/instance/all')
                    ->addHeaders(['apiKey' => $this->globalApiKey])
                    ->send();

                if ($allResponse->isOk) {
                    $responseData = json_decode($allResponse->content, true);
                    $instancesList = $responseData['data'] ?? (is_array($responseData) ? $responseData : []);
                    foreach ($instancesList as $inst) {
                        $name = $inst['name'] ?? $inst['instanceName'] ?? null;
                        if ($name === $instanceName && !empty($inst['id'])) {
                            $targetId = $inst['id'];
                            break;
                        }
                    }
                }
            } catch (\Throwable $t) {
                // ignora e tenta usar instanceName diretamente
            }

            $response = $client->createRequest()
                ->setMethod('DELETE')
                ->setUrl("/instance/delete/{$targetId}")
                ->addHeaders([
                    'Content-Type' => 'application/json',
                    'apiKey'       => $this->globalApiKey,
                ])
                ->send();

            // Aceita tanto 200 quanto 404 (já deletada previamente)
            if (!$response->isOk && $response->statusCode !== 404) {
                Yii::error(
                    "EvolutionService::deleteInstance — resposta não-OK: "
                    . $response->statusCode . ' ' . $response->content,
                    __METHOD__
                );
                return false;
            }
        } catch (HttpClientException $e) {
            Yii::error(
                "EvolutionService::deleteInstance — falha HTTP: " . $e->getMessage(),
                __METHOD__
            );
            return false;
        }

        // Atualiza o status local independentemente da resposta da API
        $config = WhatsappConfig::findByEmpresa($empresaId);
        if ($config !== null) {
            $config->status = 'DISCONNECTED';
            $config->token  = '';
            $config->save(false);
        }

        return true;
    }

    /**
     * Envia um arquivo/documento (como PDF) via WhatsApp.
     *
     * @param string $empresaId UUID do tenant
     * @param string $to        Número de destino
     * @param string $base64    Conteúdo do arquivo em base64 (sem o cabeçalho data:)
     * @param string $filename  Nome do arquivo (ex: pedido.pdf)
     * @param string $caption   Mensagem/Legenda opcional
     * @return bool             true em caso de sucesso, false em falha
     */
    public function sendDocument(string $empresaId, string $to, string $base64, string $filename, string $caption = ''): bool
    {
        $config = WhatsappConfig::findByEmpresa($empresaId);

        if ($config === null || empty($config->token)) {
            Yii::error(
                "EvolutionService::sendDocument — instância não encontrada ou sem token para empresa {$empresaId}.",
                __METHOD__
            );
            return false;
        }

        $sanitizedNumber = $this->sanitizePhoneNumber($to);
        $cleanBase64 = preg_replace('/^data:[a-zA-Z0-9\/+-]+;base64,/i', '', $base64);

        // Configurações do delay e comportamento anti-ban
        $delayMin = isset($config->delay_min) ? (int)$config->delay_min : 1500;
        $delayMax = isset($config->delay_max) ? (int)$config->delay_max : 2500;
        $delay = rand($delayMin, $delayMax);

        try {
            $client   = new Client(['baseUrl' => $this->baseUrl]);
            $response = $client->createRequest()
                ->setMethod('POST')
                ->setFormat(Client::FORMAT_JSON)
                ->setUrl('/send/media')
                ->addHeaders([
                    'Content-Type' => 'application/json',
                    'apikey'       => $config->token,
                ])
                ->setData([
                    'number'   => $sanitizedNumber,
                    'url'      => $cleanBase64,
                    'type'     => 'document',
                    'caption'  => $caption,
                    'filename' => $filename,
                    'delay'    => $delay,
                ])
                ->send();

            if (!$response->isOk) {
                Yii::error(
                    "EvolutionService::sendDocument — resposta não-OK: "
                    . $response->statusCode . ' ' . $response->content,
                    __METHOD__
                );
                return false;
            }

            return true;
        } catch (HttpClientException $e) {
            Yii::error(
                "EvolutionService::sendDocument — falha HTTP: " . $e->getMessage(),
                __METHOD__
            );
            return false;
        }
    }

    /**
     * Envia uma imagem/mídia (como o card de produto) via WhatsApp para um contato.
     *
     * @param string $empresaId UUID do tenant
     * @param string $to        Número de destino
     * @param string $mediaData Conteúdo em Base64 ou URL da imagem
     * @param string $caption   Legenda/mensagem promocional
     * @param string $mediaType Tipo de mídia (padrão 'image')
     * @return bool             true em caso de sucesso
     */
    public function sendMedia(string $empresaId, string $to, string $mediaData, string $caption = '', string $mediaType = 'image'): bool
    {
        $config = WhatsappConfig::findByEmpresa($empresaId);

        if ($config === null || empty($config->token)) {
            Yii::error(
                "EvolutionService::sendMedia — instância não encontrada ou sem token para empresa {$empresaId}.",
                __METHOD__
            );
            return false;
        }

        $sanitizedNumber = $this->sanitizePhoneNumber($to);
        if (strlen($sanitizedNumber) > 13 || strlen($sanitizedNumber) < 10) {
            Yii::error("EvolutionService::sendMedia — número de telefone inválido ou concatenado: '{$to}' (sanitizado: '{$sanitizedNumber}')", __METHOD__);
            return false;
        }

        $cleanBase64 = preg_replace('/^data:[a-zA-Z0-9\/+-]+;base64,/i', '', $mediaData);

        $delayMin = isset($config->delay_min) ? (int)$config->delay_min : 2000;
        $delayMax = isset($config->delay_max) ? (int)$config->delay_max : 4000;
        $delay = rand($delayMin, $delayMax);

        try {
            $client   = new Client(['baseUrl' => $this->baseUrl]);
            $response = $client->createRequest()
                ->setMethod('POST')
                ->setFormat(Client::FORMAT_JSON)
                ->setUrl('/send/media')
                ->addHeaders([
                    'Content-Type' => 'application/json',
                    'apikey'       => $config->token,
                ])
                ->setData([
                    'number'  => $sanitizedNumber,
                    'url'     => $cleanBase64,
                    'type'    => $mediaType,
                    'caption' => $caption,
                    'delay'   => $delay,
                ])
                ->send();

            if (!$response->isOk) {
                Yii::error(
                    "EvolutionService::sendMedia — resposta não-OK: "
                    . $response->statusCode . ' ' . $response->content,
                    __METHOD__
                );
                return false;
            }

            return true;
        } catch (HttpClientException $e) {
            Yii::error(
                "EvolutionService::sendMedia — falha HTTP: " . $e->getMessage(),
                __METHOD__
            );
            return false;
        }
    }

    /**
     * Posta uma imagem/mídia diretamente no Status/Stories do WhatsApp.
     *
     * @param string $empresaId UUID do tenant
     * @param string $mediaData Conteúdo em Base64 ou URL da imagem/vídeo
     * @param string $caption   Legenda promocional do Status
     * @param string $mediaType Tipo de mídia ('image' ou 'video')
     * @return bool             true em caso de sucesso
     */
    public function sendWhatsAppStatus(string $empresaId, string $mediaData, string $caption = '', string $mediaType = 'image'): bool
    {
        $config = WhatsappConfig::findByEmpresa($empresaId);

        if ($config === null || empty($config->token)) {
            Yii::error(
                "EvolutionService::sendWhatsAppStatus — instância não encontrada para empresa {$empresaId}.",
                __METHOD__
            );
            return false;
        }

        $cleanBase64 = preg_replace('/^data:[a-zA-Z0-9\/+-]+;base64,/i', '', $mediaData);

        try {
            $client   = new Client(['baseUrl' => $this->baseUrl]);
            
            // Endpoints suportados para envio de status (stories): /send/status/media (Go v0.7.1), /send/status, /send/media
            $endpoints = ['/send/status/media', '/send/status', '/send/media'];
            $success = false;

            foreach ($endpoints as $endpoint) {
                $response = $client->createRequest()
                    ->setMethod('POST')
                    ->setFormat(Client::FORMAT_JSON)
                    ->setUrl($endpoint)
                    ->addHeaders([
                        'Content-Type' => 'application/json',
                        'apikey'       => $config->token,
                    ])
                    ->setData([
                        'url'     => $cleanBase64,
                        'type'    => $mediaType ?: 'image',
                        'caption' => $caption,
                        'status'  => true,
                    ])
                    ->send();

                if ($response->isOk) {
                    $success = true;
                    break;
                } else {
                    Yii::warning("EvolutionService::sendWhatsAppStatus — endpoint {$endpoint} retornou HTTP {$response->statusCode}: {$response->content}", __METHOD__);
                }
            }

            if (!$success) {
                Yii::warning("EvolutionService::sendWhatsAppStatus — endpoints de status não retornaram OK.", __METHOD__);
            }

            return $success;
        } catch (HttpClientException $e) {
            Yii::error(
                "EvolutionService::sendWhatsAppStatus — falha HTTP: " . $e->getMessage(),
                __METHOD__
            );
            return false;
        }
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    /**
     * Constrói o nome canônico da instância a partir do UUID da empresa.
     *
     * Usa os primeiros 8 caracteres do UUID (antes do primeiro hífen) para
     * manter o nome legível e compatível com os limites da API Go.
     *
     * Exemplo: UUID "3f2504e0-4f89-11d3-9a0c-0305e82c3301"
     *          → "pulse_empresa_id_3f2504e0"
     *
     * @param string $empresaId UUID completo do tenant
     * @return string
     */
    private function buildInstanceName(string $empresaId): string
    {
        $config = WhatsappConfig::findByEmpresa($empresaId);
        if ($config !== null && !empty($config->instance_name)) {
            return $config->instance_name;
        }
        
        // Remove hífens e usa os primeiros 8 caracteres para compacidade
        $short = substr(str_replace('-', '', $empresaId), 0, 12);
        return "pulse_empresa_id_{$short}";
    }

    /**
     * Sanitiza um número de telefone para o formato esperado pela Evolution API.
     *
     * Regras aplicadas:
     *   1. Remove todos os caracteres não numéricos.
     *   2. Injeta o DDI 55 (Brasil) caso o número não comece com ele.
     *   3. Garante o formato: 55DDD9NÚMERO (sem caracteres especiais).
     *   4. Remove o 9º dígito se for DDD do Brasil >= 20.
     *
     * @param string $number Número bruto (pode conter +, -, espaços, parênteses)
     * @return string        Número sanitizado no formato 5511999998888 ou sem nono dígito.
     */
    private function sanitizePhoneNumber(string $number): string
    {
        // Remove tudo que não for dígito
        $numero = preg_replace('/\D/', '', $number);

        // Adicionar DDI 55 se necessário
        if (strlen($numero) === 11) {
            $numero = '55' . $numero;
        } elseif (strlen($numero) === 10) {
            $ddd  = substr($numero, 0, 2);
            $rest = substr($numero, 2);
            $numero = '55' . $ddd . '9' . $rest;
        }

        // Se o número não começar com 55 (DDI internacional), garante que tenha pelo menos o DDI se o usuário digitar completo
        if (strlen($numero) < 10) {
            // Número muito curto, apenas garante 55 no início
            if (!str_starts_with($numero, '55')) {
                $numero = '55' . $numero;
            }
        }

        // Normalização do nono dígito:
        // WhatsApp BR remove o 9 para DDDs >= 20 (fora de São Paulo).
        if (strlen($numero) === 13 && strpos($numero, '55') === 0) {
            $ddd = (int) substr($numero, 2, 2);
            if ($ddd >= 20 && substr($numero, 4, 1) === '9') {
                $numero = '55' . $ddd . substr($numero, 5);
            }
        }

        return $numero;
    }
}
