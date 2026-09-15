<?php

namespace app\components\fiscal;

/**
 * Helper com tabelas e utilitários do IBGE e SEFAZ
 */
class IbgeHelper
{
    /**
     * Mapeamento de Siglas de UF para códigos numéricos do IBGE (cUF)
     */
    const UF_CODES = [
        'RO' => 11,
        'AC' => 12,
        'AM' => 13,
        'RR' => 14,
        'PA' => 15,
        'AP' => 16,
        'TO' => 17,
        'MA' => 21,
        'PI' => 22,
        'CE' => 23,
        'RN' => 24,
        'PB' => 25,
        'PE' => 26,
        'AL' => 27,
        'SE' => 28,
        'BA' => 29,
        'MG' => 31,
        'ES' => 32,
        'RJ' => 33,
        'SP' => 35,
        'PR' => 41,
        'SC' => 42,
        'RS' => 43,
        'MS' => 50,
        'MT' => 51,
        'GO' => 52,
        'DF' => 53,
    ];

    /**
     * Retorna o código numérico IBGE da UF (ex: SP -> 35, PE -> 26)
     */
    public static function getUfCode(?string $uf): int
    {
        if (!$uf) {
            return 35; // Default SP
        }

        $uf = strtoupper(trim($uf));
        return self::UF_CODES[$uf] ?? 35;
    }

    /**
     * Retorna a sigla da UF pelo código numérico
     */
    public static function getUfSigla(int $code): string
    {
        $flipped = array_flip(self::UF_CODES);
        return $flipped[$code] ?? 'SP';
    }

    /**
     * Limpa CEP para conter apenas 8 números
     */
    public static function sanitizeCep(?string $cep): string
    {
        $digits = preg_replace('/\D/', '', (string)$cep);
        return str_pad(substr($digits, 0, 8), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Limpa CPF ou CNPJ
     */
    public static function sanitizeDoc(?string $doc): string
    {
        return preg_replace('/\D/', '', (string)$doc);
    }
}
