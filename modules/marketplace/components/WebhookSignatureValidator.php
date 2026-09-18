<?php

namespace app\modules\marketplace\components;

use Yii;
use yii\base\Component;

/**
 * Validador de assinaturas de webhooks
 * 
 * Implementa validação de assinatura para cada marketplace
 */
class WebhookSignatureValidator extends Component
{
    /**
     * Valida assinatura de webhook do Mercado Livre
     * 
     * @param string $signature Assinatura recebida no header X-Signature
     * @param string $payload Corpo bruto da requisição
     * @param string $secret Client Secret do marketplace
     * @return bool
     */
    public function validateMercadoLivre($signature, $payload, $secret)
    {
        if (empty($signature) || empty($secret)) {
            Yii::warning('Assinatura ou secret vazio (Mercado Livre)', __METHOD__);
            return false;
        }

        // Mercado Livre usa HMAC-SHA256
        $calculatedSignature = hash_hmac('sha256', $payload, $secret);

        // Usa hash_equals para prevenir timing attacks
        $isValid = hash_equals($signature, $calculatedSignature);

        if (!$isValid) {
            Yii::warning('Assinatura inválida (Mercado Livre)', __METHOD__);
        }

        return $isValid;
    }

    /**
     * Valida assinatura de webhook da Shopee
     * 
     * @param string $signature Assinatura recebida no header Authorization
     * @param string $payload Corpo bruto da requisição
     * @param string $path URL path da requisição
     * @param string $timestamp Timestamp recebido no header
     * @param string $secret Partner Key da Shopee
     * @return bool
     */
    public function validateShopee($signature, $payload, $path, $timestamp, $secret)
    {
        if (empty($signature) || empty($secret)) {
            Yii::warning('Assinatura ou secret vazio (Shopee)', __METHOD__);
            return false;
        }

        // Shopee usa: HMAC-SHA256(path|body)
        $baseString = "{$path}|{$payload}";
        $calculatedSignature = hash_hmac('sha256', $baseString, $secret);

        $isValid = hash_equals($signature, $calculatedSignature);

        if (!$isValid) {
            Yii::warning('Assinatura inválida (Shopee)', __METHOD__);
        }

        return $isValid;
    }

    /**
     * Valida token de webhook do Magazine Luiza
     * 
     * @param string $receivedToken Token recebido no header
     * @param string $expectedToken Token configurado
     * @return bool
     */
    public function validateMagazineLuiza($receivedToken, $expectedToken)
    {
        if (empty($receivedToken) || empty($expectedToken)) {
            Yii::warning('Token vazio (Magazine Luiza)', __METHOD__);
            return false;
        }

        $isValid = hash_equals($receivedToken, $expectedToken);

        if (!$isValid) {
            Yii::warning('Token inválido (Magazine Luiza)', __METHOD__);
        }

        return $isValid;
    }

    /**
     * Valida assinatura ou token de webhook do Magazine Luiza
     * 
     * Suporta:
     * 1. Token no header X-Auth-Token ou Authorization Bearer
     * 2. Assinatura HMAC-SHA256 no header X-Signature
     * 
     * @param array|string $headersOuToken Headers recebidos ou token string
     * @param string $rawBody Corpo bruto da requisição
     * @param string $secretOuToken Client Secret ou Token configurado
     * @return bool
     */
    public function validateMagalu($headersOuToken, $rawBody = '', $secretOuToken = '')
    {
        if (is_string($headersOuToken) && empty($rawBody) && !empty($secretOuToken)) {
            return $this->validateMagazineLuiza($headersOuToken, $secretOuToken);
        }

        $headers = is_array($headersOuToken) ? $headersOuToken : [];

        // 1. Verificação por HMAC-SHA256 se houver X-Signature
        $signature = $headers['x-signature'] ?? $headers['X-Signature'] ?? null;
        if (!empty($signature) && !empty($secretOuToken)) {
            $calculated = hash_hmac('sha256', $rawBody, $secretOuToken);
            if (hash_equals($signature, $calculated)) {
                return true;
            }
        }

        // 2. Verificação por Token (X-Auth-Token ou Authorization Bearer)
        $receivedToken = $headers['x-auth-token'] ?? $headers['X-Auth-Token'] ?? null;
        if (!$receivedToken && isset($headers['authorization'])) {
            $auth = $headers['authorization'];
            if (stripos($auth, 'Bearer ') === 0) {
                $receivedToken = trim(substr($auth, 7));
            } else {
                $receivedToken = trim($auth);
            }
        }

        if (!empty($receivedToken) && !empty($secretOuToken)) {
            return $this->validateMagazineLuiza($receivedToken, $secretOuToken);
        }

        // Se nenhum método de assinatura foi enviado mas há secret esperado, rejeita
        if (!empty($secretOuToken)) {
            Yii::warning('Webhook Magalu sem token ou assinatura válidos no header', __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * Valida assinatura SNS da Amazon
     * 
     * @param array $message Mensagem SNS decodificada
     * @param string $signature Assinatura recebida
     * @return bool
     */
    public function validateAmazon($message, $signature)
    {
        // TODO: Implementar validação SNS da Amazon
        // Requer verificação de certificado SSL
        Yii::warning('Validação Amazon SNS ainda não implementada', __METHOD__);
        return false;
    }

    /**
     * Valida assinatura genérica baseada no marketplace
     * 
     * @param string $marketplace Nome do marketplace
     * @param string $rawBody Corpo bruto
     * @param array $headers Headers da requisição
     * @param array $config Configuração do marketplace
     * @return bool
     */
    public function validate($marketplace, $rawBody, $headers, $config)
    {
        switch ($marketplace) {
            case 'MERCADO_LIVRE':
            case 'mercado-livre':
                $signature = $headers['x-signature'] ?? $headers['X-Signature'] ?? null;
                $secret = $config['client_secret'] ?? null;
                return $this->validateMercadoLivre($signature, $rawBody, $secret);

            case 'SHOPEE':
            case 'shopee':
                $signature = $headers['authorization'] ?? $headers['Authorization'] ?? null;
                $timestamp = $headers['timestamp'] ?? $headers['Timestamp'] ?? null;
                $path = Yii::$app->request->url;
                $secret = $config['client_secret'] ?? null;
                return $this->validateShopee($signature, $rawBody, $path, $timestamp, $secret);

            case 'MAGAZINE_LUIZA':
            case 'magazine-luiza':
            case 'magalu':
                $secret = $config['client_secret'] ?? $config['access_token'] ?? null;
                return $this->validateMagalu($headers, $rawBody, $secret);

            case 'AMAZON':
            case 'amazon':
                $message = json_decode($rawBody, true);
                $signature = $headers['x-amz-sns-signature'] ?? null;
                return $this->validateAmazon($message, $signature);

            default:
                Yii::error("Marketplace não suportado: {$marketplace}", __METHOD__);
                return false;
        }
    }
}
