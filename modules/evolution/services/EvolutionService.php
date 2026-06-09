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
        $this->baseUrl      = rtrim(Yii::$app->params['evolution']['baseUrl'], '/');
        $this->globalApiKey = Yii::$app->params['evolution']['globalApiKey'];
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
            // Se já tivermos um token salvo, tentamos usar ele. Senão, gera um novo.
            $instanceToken = $config->token ?: Yii::$app->security->generateRandomString(32);

            $client   = new Client(['baseUrl' => $this->baseUrl]);
            
            // 1. Tenta CRIAR a instância
            $response = $client->createRequest()
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

            if ($response->isOk) {
                // Criou com sucesso! Salva o novo token.
                $config->token = $instanceToken;
                $config->status = 'DISCONNECTED';
                $config->save(false);
            } else {
                // Falhou ao criar. Pode ser que a instância já exista no motor Go (Retorna 500).
                // Se nós NÃO temos um token salvo localmente, então é uma falha fatal real.
                if (empty($config->token)) {
                    Yii::error("EvolutionService::createInstance — falha fatal ao criar: " . $response->content, __METHOD__);
                    return null;
                }
                // Se nós TEMOS um token, significa que ela já existe. Vamos apenas seguir em frente e conectar!
            }

            // Fluxo novo v0.7.1: Conecta a instância
            $client->createRequest()
                ->setMethod('POST')
                ->setUrl('/instance/connect')
                ->addHeaders([
                    'Content-Type' => 'application/json',
                    'apikey'       => $instanceToken,
                ])
                ->setData([]) // REQUIRED: Send empty JSON '{}' to prevent EOF error
                ->send();

            // Pega o QR Code gerado após a conexão (o Baileys demora ~2s para gerar)
            $qrBase64 = null;
            for ($i = 0; $i < 4; $i++) {
                sleep(1); // Espera 1s a cada tentativa
                
                $qrResponse = $client->createRequest()
                    ->setMethod('GET')
                    ->setUrl('/instance/qr')
                    ->addHeaders([
                        'Content-Type' => 'application/json',
                        'apikey'       => $instanceToken,
                    ])
                    ->send();

                if ($qrResponse->isOk) {
                    $qrBody = $qrResponse->data ?? json_decode($qrResponse->content, true);

                    // Extrai a string Base64 do QR Code (suportando a v0.7.1 e legados)
                    $qrBase64 = $qrBody['data']['Qrcode'] 
                        ?? $qrBody['qrcode']['base64'] 
                        ?? $qrBody['base64'] 
                        ?? null;

                    if (!empty($qrBase64)) {
                        break; // Se achou o QR Code, sai do loop imediatamente
                    }
                }
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
            $response = $client->createRequest()
                ->setMethod('DELETE')
                ->setUrl("/instance/delete/{$instanceName}")
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
     *
     * @param string $number Número bruto (pode conter +, -, espaços, parênteses)
     * @return string        Número sanitizado no formato 5511999998888
     */
    private function sanitizePhoneNumber(string $number): string
    {
        // Remove tudo que não for dígito
        $digits = preg_replace('/\D/', '', $number);

        // Injeta DDI 55 se não estiver presente
        if (!str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }

        return $digits;
    }
}
