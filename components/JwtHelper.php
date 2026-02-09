<?php

namespace app\components;

use Exception;

/**
 * JwtHelper - Implementação nativa e leve para JWT (HS256)
 * Evita dependência de bibliotecas externas como firebase/php-jwt.
 */
class JwtHelper
{
    /**
     * Gera um token JWT
     * @param array $payload Dados a serem incluídos no token
     * @param string $secret Chave secreta para assinatura
     * @return string
     */
    public static function encode(array $payload, string $secret): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Decodifica e valida um token JWT
     * @param string $token O token recebido
     * @param string $secret Chave secreta para validação
     * @return array|null Dados decodificados ou null se inválido
     */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        list($header, $payload, $signature) = $parts;

        // Valida assinatura
        $validSignature = hash_hmac('sha256', $header . "." . $payload, $secret, true);
        if (!hash_equals(self::base64UrlEncode($validSignature), $signature)) {
            return null;
        }

        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);

        // Verifica expiração (exp) se presente
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return null;
        }

        return $decodedPayload;
    }

    /**
     * Encoder Base64Url
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decoder Base64Url
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
