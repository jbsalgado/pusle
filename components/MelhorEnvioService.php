<?php

namespace app\components;

use Yii;
use yii\base\Component;
use app\modules\vendas\models\LojaConfiguracao;

/**
 * MelhorEnvioService — Integração com a API REST v2 do Melhor Envio
 * 
 * Permite cotação de fretes multitenant para lojas virtuais com fail-safe garantido.
 */
class MelhorEnvioService extends Component
{
    const URL_PRODUCAO = 'https://melhorenvio.com.br/api/v2';
    const URL_SANDBOX  = 'https://sandbox.melhorenvio.com.br/api/v2';

    // Lista de serviços suportados
    const SERVICOS_DISPONIVEIS = [
        '1' => ['nome' => 'PAC', 'transportadora' => 'Correios'],
        '2' => ['nome' => 'SEDEX', 'transportadora' => 'Correios'],
        '3' => ['nome' => '.Package', 'transportadora' => 'Jadlog'],
        '4' => ['nome' => '.Com', 'transportadora' => 'Jadlog'],
        '17' => ['nome' => 'Mini Envios', 'transportadora' => 'Correios'],
        '27' => ['nome' => 'Express', 'transportadora' => 'Loggi'],
    ];

    /**
     * Retorna a URL base de acordo com o ambiente
     */
    public static function getBaseUrl(string $ambiente = 'production'): string
    {
        return $ambiente === 'sandbox' ? self::URL_SANDBOX : self::URL_PRODUCAO;
    }

    /**
     * Valida e testa a conexão com o Melhor Envio usando um Bearer Token
     * 
     * @param string $token
     * @param string $ambiente
     * @return array [success => bool, message => string, data => array]
     */
    public static function testarConexao(string $token, string $ambiente = 'production'): array
    {
        $token = trim($token);
        if (empty($token)) {
            return ['success' => false, 'message' => 'O token não foi informado.'];
        }

        $url = self::getBaseUrl($ambiente) . '/me';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
                'User-Agent: PulsePlus/2.0 (suporte@oncode.app.br)',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => 'Falha de comunicação: ' . $curlError];
        }

        $json = json_decode($response, true);

        if ($httpCode === 200 && is_array($json) && !empty($json['id'])) {
            return [
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso!',
                'data' => [
                    'id' => $json['id'],
                    'nome' => $json['firstname'] ?? ($json['name'] ?? 'Usuário'),
                    'email' => $json['email'] ?? null,
                    'documento' => $json['document'] ?? null,
                    'saldo' => isset($json['balance']) ? (float)$json['balance'] : 0.0,
                ],
            ];
        }

        $msgErro = $json['message'] ?? ($json['error'] ?? 'Token inválido ou não autorizado.');
        return ['success' => false, 'message' => "Erro ({$httpCode}): {$msgErro}"];
    }

    /**
     * Cota frete no Melhor Envio para uma loja específica
     * 
     * @param LojaConfiguracao $lojaConfig
     * @param string $cepDestino
     * @param float $subtotal
     * @param string $porte 'P', 'M', 'G', 'X'
     * @return array Lista de opções calculadas
     */
    public static function cotarFrete(LojaConfiguracao $lojaConfig, string $cepDestino, float $subtotal = 0.0, string $porte = 'P'): array
    {
        $tokenGlobal = trim(getenv('MELHOR_ENVIO_GLOBAL_TOKEN') ?: ($_ENV['MELHOR_ENVIO_GLOBAL_TOKEN'] ?? ''));
        $tokenLoja = trim($lojaConfig->melhor_envio_token ?? '');
        $tokenEfetivo = !empty($tokenLoja) ? $tokenLoja : $tokenGlobal;

        // Se não houver nenhum token configurado (nem individual nem global da plataforma), pula
        if (empty($tokenEfetivo)) {
            return [];
        }

        // Se a loja desativou expressamente e possui token próprio, respeita a desativação
        if (!empty($tokenLoja) && !$lojaConfig->melhor_envio_ativo) {
            return [];
        }

        $cepOrigem = preg_replace('/\D/', '', $lojaConfig->melhor_envio_cep_origem ?: $lojaConfig->cep);
        $cepDestinoLimpo = preg_replace('/\D/', '', $cepDestino);

        if (strlen($cepOrigem) !== 8 || strlen($cepDestinoLimpo) !== 8) {
            return [];
        }

        // Mapeamento de dimensões médias baseadas no maior porte do carrinho
        $dimensoes = self::getDimensoesPorPorte($porte);

        // Prepara serviços a cotar
        $servicos = '1,2,3,4,17';
        if (!empty($lojaConfig->melhor_envio_servicos)) {
            if (is_array($lojaConfig->melhor_envio_servicos)) {
                $servicos = implode(',', $lojaConfig->melhor_envio_servicos);
            } else {
                $servicos = trim($lojaConfig->melhor_envio_servicos);
            }
        }

        $payload = [
            'from' => [
                'postal_code' => $cepOrigem,
            ],
            'to' => [
                'postal_code' => $cepDestinoLimpo,
            ],
            'package' => [
                'width' => $dimensoes['largura'],
                'height' => $dimensoes['altura'],
                'length' => $dimensoes['comprimento'],
                'weight' => $dimensoes['peso'],
            ],
            'options' => [
                'receipt' => false,
                'own_hand' => false,
                'reverse' => false,
                'non_commercial' => true,
            ],
            'services' => $servicos,
        ];

        $url = self::getBaseUrl($lojaConfig->melhor_envio_ambiente ?: 'production') . '/me/shipment/calculate';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => 2500, // Timeout estrito de 2.5s para fail-safe
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim($tokenEfetivo),
                'User-Agent: PulsePlus/2.0 (suporte@oncode.app.br)',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            Yii::warning("[Melhor Envio] Falha na cotação (HTTP {$httpCode}): {$response}", __METHOD__);
            return [];
        }

        $cotacoes = json_decode($response, true);
        if (!is_array($cotacoes)) {
            return [];
        }

        $opcoes = [];
        $acrescimoDias = (int)($lojaConfig->melhor_envio_acrescimo_dias ?? 0);
        $acrescimoValor = (float)($lojaConfig->melhor_envio_acrescimo_valor ?? 0);

        foreach ($cotacoes as $item) {
            // Se o item contém erro da transportadora, pula
            if (!empty($item['error']) || empty($item['price'])) {
                continue;
            }

            $idServico = (string)($item['id'] ?? '');
            $nomeServico = $item['name'] ?? 'Entrega';
            $empresa = $item['company']['name'] ?? 'Transportadora';
            $precoBase = (float)($item['custom_price'] ?? ($item['price'] ?? 0));
            $prazoBase = (int)($item['custom_delivery_time'] ?? ($item['delivery_time'] ?? 1));

            $precoFinal = max(0, $precoBase + $acrescimoValor);
            $prazoFinal = max(1, $prazoBase + $acrescimoDias);

            $opcoes[] = [
                'id' => 'me_' . $idServico,
                'servico' => "{$nomeServico} ({$empresa})",
                'transportadora' => $empresa,
                'valor' => $precoFinal,
                'valor_original' => $precoBase,
                'gratis' => false,
                'prazo_dias_min' => $prazoFinal,
                'prazo_dias_max' => $prazoFinal + 2,
                'prazo_descricao' => "{$prazoFinal} a " . ($prazoFinal + 2) . " dias úteis",
                'tipo' => 'MELHOR_ENVIO',
                'servico_id' => $idServico,
            ];
        }

        return $opcoes;
    }

    /**
     * Dimensões de pacote estimadas por porte
     */
    private static function getDimensoesPorPorte(string $porte): array
    {
        switch (strtoupper($porte)) {
            case 'X':
                return ['altura' => 25, 'largura' => 35, 'comprimento' => 40, 'peso' => 4.0];
            case 'G':
                return ['altura' => 15, 'largura' => 25, 'comprimento' => 30, 'peso' => 2.0];
            case 'M':
                return ['altura' => 10, 'largura' => 18, 'comprimento' => 22, 'peso' => 0.9];
            case 'P':
            default:
                return ['altura' => 5, 'largura' => 12, 'comprimento' => 17, 'peso' => 0.3];
        }
    }
}
