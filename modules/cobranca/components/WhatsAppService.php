<?php

namespace app\modules\cobranca\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;
use app\modules\cobranca\models\CobrancaConfiguracao;

/**
 * WhatsAppService
 * 
 * Serviço de integração com APIs de WhatsApp
 * Suporta: Z-API, Twilio, Evolution API
 */
class WhatsAppService extends Component
{
    /**
     * @var CobrancaConfiguracao
     */
    protected $config;

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * Construtor
     */
    public function __construct(CobrancaConfiguracao $config, $httpConfig = [])
    {
        $this->config = $config;
        $this->httpClient = new Client($httpConfig);
        parent::__construct();
    }

    /**
     * Envia mensagem via WhatsApp
     * 
     * @param string $telefone Número do telefone (com ou sem código do país)
     * @param string $mensagem Texto da mensagem
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function enviarMensagem($telefone, $mensagem)
    {
        try {
            $telefone = $this->formatarTelefone($telefone);

            switch ($this->config->whatsapp_provider) {
                case CobrancaConfiguracao::PROVIDER_ZAPI:
                    return $this->enviarZApi($telefone, $mensagem);

                case CobrancaConfiguracao::PROVIDER_TWILIO:
                    return $this->enviarTwilio($telefone, $mensagem);

                case CobrancaConfiguracao::PROVIDER_EVOLUTION:
                    return $this->enviarEvolution($telefone, $mensagem);

                default:
                    return [
                        'success' => false,
                        'message' => 'Provedor WhatsApp não suportado: ' . $this->config->whatsapp_provider,
                        'data' => null,
                    ];
            }
        } catch (\Exception $e) {
            Yii::error('Erro ao enviar mensagem WhatsApp: ' . $e->getMessage(), __METHOD__);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Envia mensagem via Z-API
     */
    protected function enviarZApi($telefone, $mensagem)
    {
        $url = sprintf(
            'https://api.z-api.io/instances/%s/token/%s/send-text',
            $this->config->zapi_instance_id,
            $this->config->zapi_token
        );

        $response = $this->httpClient->createRequest()
            ->setMethod('POST')
            ->setUrl($url)
            ->setFormat(Client::FORMAT_JSON)
            ->setData([
                'phone' => $telefone,
                'message' => $mensagem,
            ])
            ->send();

        if ($response->isOk) {
            $data = $response->data;

            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso',
                'data' => $data,
            ];
        }

        return [
            'success' => false,
            'message' => $response->data['message'] ?? 'Erro ao enviar mensagem',
            'data' => $response->data,
        ];
    }

    /**
     * Envia mensagem via Twilio
     */
    protected function enviarTwilio($telefone, $mensagem)
    {
        // TODO: Implementar integração Twilio
        return [
            'success' => false,
            'message' => 'Twilio não implementado ainda',
            'data' => null,
        ];
    }

    /**
     * Envia mensagem via Evolution API
     */
    protected function enviarEvolution($telefone, $mensagem)
    {
        // TODO: Implementar integração Evolution API
        return [
            'success' => false,
            'message' => 'Evolution API não implementada ainda',
            'data' => null,
        ];
    }

    /**
     * Formata número de telefone para padrão internacional
     * 
     * @param string $telefone
     * @return string Telefone formatado (ex: 5581999999999)
     */
    protected function formatarTelefone($telefone)
    {
        // Remove todos os caracteres não numéricos
        $telefone = preg_replace('/[^0-9]/', '', $telefone);

        // Se já tem 13 dígitos (55 + DDD + número), retorna
        if (strlen($telefone) === 13) {
            return $telefone;
        }

        // Se tem 11 dígitos (DDD + número), adiciona código do país
        if (strlen($telefone) === 11) {
            return '55' . $telefone;
        }

        // Se tem 10 dígitos (DDD + número sem 9), adiciona código do país e 9
        if (strlen($telefone) === 10) {
            $ddd = substr($telefone, 0, 2);
            $numero = substr($telefone, 2);
            return '55' . $ddd . '9' . $numero;
        }

        // Retorna como está se não se encaixar nos padrões
        return $telefone;
    }

    /**
     * Valida se o telefone está em formato válido
     * 
     * @param string $telefone
     * @return bool
     */
    public function validarTelefone($telefone)
    {
        $telefone = preg_replace('/[^0-9]/', '', $telefone);

        // Deve ter entre 10 e 13 dígitos
        return strlen($telefone) >= 10 && strlen($telefone) <= 13;
    }

    /**
     * Testa a conexão com a API
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function testarConexao()
    {
        try {
            switch ($this->config->whatsapp_provider) {
                case CobrancaConfiguracao::PROVIDER_ZAPI:
                    return $this->testarZApi();

                default:
                    return [
                        'success' => false,
                        'message' => 'Teste de conexão não implementado para este provedor',
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao testar conexão: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Testa conexão com Z-API
     */
    protected function testarZApi()
    {
        $url = sprintf(
            'https://api.z-api.io/instances/%s/token/%s/status',
            $this->config->zapi_instance_id,
            $this->config->zapi_token
        );

        $response = $this->httpClient->createRequest()
            ->setMethod('GET')
            ->setUrl($url)
            ->send();

        if ($response->isOk && isset($response->data['connected'])) {
            if ($response->data['connected']) {
                return [
                    'success' => true,
                    'message' => 'WhatsApp conectado e pronto para uso!',
                ];
            }

            return [
                'success' => false,
                'message' => 'WhatsApp não está conectado. Escaneie o QR Code na Z-API.',
            ];
        }

        return [
            'success' => false,
            'message' => 'Credenciais inválidas ou instância não encontrada',
        ];
    }
}
