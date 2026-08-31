<?php

namespace app\modules\evolution\services;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;
use app\modules\evolution\models\WhatsappConfig;
use app\modules\evolution\models\WhatsappTemplate;

/**
 * MetaWhatsAppService
 *
 * Serviço de integração nativa com a API Oficial da Meta (WhatsApp Cloud API / Graph API v19.0+).
 */
class MetaWhatsAppService extends Component
{
    public string $apiVersion = 'v19.0';
    public string $baseUrl = 'https://graph.facebook.com';

    public ?string $lastError = null;
    public ?array $lastResponse = null;

    /**
     * @var Client
     */
    private $_httpClient;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->_httpClient = new Client([
            'baseUrl' => rtrim($this->baseUrl, '/') . '/' . trim($this->apiVersion, '/'),
            'requestConfig' => [
                'format' => Client::FORMAT_JSON,
            ],
            'responseConfig' => [
                'format' => Client::FORMAT_JSON,
            ],
        ]);
    }

    /**
     * Normaliza número para padrão internacional do WhatsApp (ex: 5581999998888)
     */
    public static function formatarNumero(string $numero): string
    {
        $numero = preg_replace('/[^0-9]/', '', $numero);
        if (strlen($numero) === 11) {
            $numero = '55' . $numero;
        } elseif (strlen($numero) === 10) {
            $ddd = substr($numero, 0, 2);
            $rest = substr($numero, 2);
            $numero = '55' . $ddd . '9' . $rest;
        }
        return $numero;
    }

    /**
     * Envia mensagem de texto livre (válido apenas dentro da janela de atendimento de 24h)
     */
    public function sendTextMessage(string $phoneNumberId, string $accessToken, string $to, string $text): bool
    {
        $to = self::formatarNumero($to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => [
                'preview_url' => true,
                'body'        => $text,
            ],
        ];

        return $this->postMessage($phoneNumberId, $accessToken, $payload);
    }

    /**
     * Envia mensagem baseada em Template HSM aprovado pela Meta
     *
     * @param string $phoneNumberId
     * @param string $accessToken
     * @param string $to
     * @param string $templateName
     * @param string $language (ex: 'pt_BR')
     * @param array  $components Variáveis de cabeçalho, corpo e botões
     * @return bool
     */
    public function sendTemplateMessage(
        string $phoneNumberId,
        string $accessToken,
        string $to,
        string $templateName,
        string $language = 'pt_BR',
        array $components = []
    ): bool {
        $to = self::formatarNumero($to);

        $templatePayload = [
            'name'     => $templateName,
            'language' => ['code' => $language],
        ];

        if (!empty($components)) {
            $templatePayload['components'] = $components;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'template',
            'template'          => $templatePayload,
        ];

        return $this->postMessage($phoneNumberId, $accessToken, $payload);
    }

    /**
     * Envia mídia (imagem, vídeo ou documento)
     */
    public function sendMediaMessage(
        string $phoneNumberId,
        string $accessToken,
        string $to,
        string $type,
        string $mediaUrl,
        ?string $caption = null,
        ?string $filename = null
    ): bool {
        $to = self::formatarNumero($to);

        $mediaObj = ['link' => $mediaUrl];
        if (!empty($caption)) {
            $mediaObj['caption'] = $caption;
        }
        if (!empty($filename) && $type === 'document') {
            $mediaObj['filename'] = $filename;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => $type,
            $type               => $mediaObj,
        ];

        return $this->postMessage($phoneNumberId, $accessToken, $payload);
    }

    /**
     * Executa a requisição POST no endpoint messages da Meta Cloud API
     */
    private function postMessage(string $phoneNumberId, string $accessToken, array $payload): bool
    {
        try {
            $this->lastError = null;
            $this->lastResponse = null;

            $response = $this->_httpClient->createRequest()
                ->setMethod('POST')
                ->setUrl("/{$phoneNumberId}/messages")
                ->addHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ])
                ->setData($payload)
                ->send();

            $data = json_decode($response->content, true) ?: [];
            $this->lastResponse = $data;

            if ($response->isOk && !empty($data['messages'])) {
                return true;
            }

            $errorMessage = $data['error']['message'] ?? $response->content;
            $errorCode    = $data['error']['code'] ?? $response->statusCode;
            $this->lastError = "[Erro {$errorCode}] {$errorMessage}";
            Yii::error("Erro Meta WhatsApp Cloud API: {$this->lastError}", __METHOD__);
            return false;

        } catch (\Throwable $t) {
            $this->lastError = $t->getMessage();
            Yii::error("Exceção Meta WhatsApp Cloud API: {$this->lastError}", __METHOD__);
            return false;
        }
    }

    /**
     * Sincroniza os templates aprovados na conta da Meta (WABA) para a base do Pulse
     *
     * @param string $wabaId
     * @param string $accessToken
     * @param string $empresaId
     * @return array ['success' => bool, 'total_synced' => int, 'message' => string]
     */
    public function syncTemplates(string $wabaId, string $accessToken, string $empresaId): array
    {
        try {
            $response = $this->_httpClient->createRequest()
                ->setMethod('GET')
                ->setUrl("/{$wabaId}/message_templates")
                ->addHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])
                ->setData([
                    'fields' => 'name,status,category,language,components,id',
                    'limit'  => 100,
                ])
                ->send();

            $data = json_decode($response->content, true) ?: [];

            if (!$response->isOk) {
                $err = $data['error']['message'] ?? 'Erro desconhecido ao buscar templates na Meta';
                return ['success' => false, 'total_synced' => 0, 'message' => $err];
            }

            $templates = $data['data'] ?? [];
            $synced = 0;

            foreach ($templates as $t) {
                $name = $t['name'] ?? null;
                $language = $t['language'] ?? 'pt_BR';
                if (empty($name)) continue;

                $model = WhatsappTemplate::findOne([
                    'empresa_id' => $empresaId,
                    'name'       => $name,
                    'language'   => $language,
                ]);

                if ($model === null) {
                    $model = new WhatsappTemplate();
                    $model->empresa_id = $empresaId;
                    $model->name       = $name;
                    $model->language   = $language;
                }

                $model->category         = $t['category'] ?? WhatsappTemplate::CATEGORY_UTILITY;
                $model->status           = $t['status'] ?? WhatsappTemplate::STATUS_PENDING;
                $model->meta_template_id = $t['id'] ?? null;
                $model->components_json  = $t['components'] ?? [];

                // Extrair corpo e cabeçalho dos components
                $headerText = null;
                $headerType = 'NONE';
                $bodyText = '';
                $footerText = null;
                $buttons = [];

                if (!empty($t['components']) && is_array($t['components'])) {
                    foreach ($t['components'] as $c) {
                        $type = $c['type'] ?? '';
                        if ($type === 'HEADER') {
                            $format = $c['format'] ?? 'TEXT';
                            $headerType = in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT', 'TEXT']) ? $format : 'TEXT';
                            $headerText = $c['text'] ?? null;
                        } elseif ($type === 'BODY') {
                            $bodyText = $c['text'] ?? '';
                        } elseif ($type === 'FOOTER') {
                            $footerText = $c['text'] ?? null;
                        } elseif ($type === 'BUTTONS') {
                            $buttons = $c['buttons'] ?? [];
                        }
                    }
                }

                $model->header_type  = $headerType;
                $model->header_text  = $headerText;
                $model->body_text    = $bodyText ?: ($model->body_text ?: $name);
                $model->footer_text  = $footerText;
                $model->buttons_json = $buttons;

                if ($model->save()) {
                    $synced++;
                }
            }

            return [
                'success'      => true,
                'total_synced' => $synced,
                'message'      => "{$synced} template(s) sincronizado(s) com sucesso da Meta.",
            ];

        } catch (\Throwable $t) {
            return ['success' => false, 'total_synced' => 0, 'message' => $t->getMessage()];
        }
    }

    /**
     * Cria e submete um novo template para aprovação na Meta Graph API
     */
    public function createTemplate(string $wabaId, string $accessToken, WhatsappTemplate $template): bool
    {
        try {
            $components = [];

            // Header
            if ($template->header_type !== 'NONE') {
                $header = [
                    'type'   => 'HEADER',
                    'format' => $template->header_type,
                ];
                if ($template->header_type === 'TEXT' && !empty($template->header_text)) {
                    $header['text'] = $template->header_text;
                }
                $components[] = $header;
            }

            // Body (Obrigatório)
            $components[] = [
                'type' => 'BODY',
                'text' => $template->body_text,
            ];

            // Footer
            if (!empty($template->footer_text)) {
                $components[] = [
                    'type' => 'FOOTER',
                    'text' => $template->footer_text,
                ];
            }

            $payload = [
                'name'       => $template->name,
                'category'   => $template->category,
                'language'   => $template->language,
                'components' => $components,
            ];

            $response = $this->_httpClient->createRequest()
                ->setMethod('POST')
                ->setUrl("/{$wabaId}/message_templates")
                ->addHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ])
                ->setData($payload)
                ->send();

            $data = json_decode($response->content, true) ?: [];

            if ($response->isOk && !empty($data['id'])) {
                $template->meta_template_id = $data['id'];
                $template->status = $data['status'] ?? WhatsappTemplate::STATUS_PENDING;
                $template->components_json = $components;
                $template->save(false);
                return true;
            }

            $errorMessage = $data['error']['message'] ?? $response->content;
            $this->lastError = $errorMessage;
            return false;

        } catch (\Throwable $t) {
            $this->lastError = $t->getMessage();
            return false;
        }
    }
}
