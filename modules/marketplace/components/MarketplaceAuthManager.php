<?php

namespace app\modules\marketplace\components;

use Yii;
use yii\base\Component;
use app\modules\marketplace\models\MarketplaceConfig;

/**
 * Gerenciador de autenticação para marketplaces
 * 
 * Responsável por:
 * - Armazenar e recuperar tokens
 * - Renovar tokens automaticamente
 * - Criptografar credenciais sensíveis
 */
class MarketplaceAuthManager extends Component
{
    /**
     * Obtém configuração de um marketplace para um usuário
     * @param string $usuarioId
     * @param string $marketplace
     * @return MarketplaceConfig|null
     */
    public function getConfig($usuarioId, $marketplace)
    {
        return MarketplaceConfig::findOne([
            'usuario_id' => $usuarioId,
            'marketplace' => $marketplace,
        ]);
    }

    /**
     * Salva ou atualiza configuração de marketplace
     * @param string $usuarioId
     * @param string $marketplace
     * @param array $dados
     * @return MarketplaceConfig
     */
    public function saveConfig($usuarioId, $marketplace, $dados)
    {
        $config = $this->getConfig($usuarioId, $marketplace);

        if (!$config) {
            $config = new MarketplaceConfig();
            $config->usuario_id = $usuarioId;
            $config->marketplace = $marketplace;
        }

        // Atualiza dados
        foreach ($dados as $key => $value) {
            if ($config->hasAttribute($key)) {
                $config->$key = $value;
            }
        }

        $config->data_atualizacao = new \yii\db\Expression('NOW()');
        $config->save();

        return $config;
    }

    /**
     * Atualiza tokens de acesso
     * @param string $usuarioId
     * @param string $marketplace
     * @param string $accessToken
     * @param string $refreshToken
     * @param int $expiresIn Tempo de expiração em segundos
     * @return bool
     */
    public function updateTokens($usuarioId, $marketplace, $accessToken, $refreshToken = null, $expiresIn = null)
    {
        $config = $this->getConfig($usuarioId, $marketplace);

        if (!$config) {
            return false;
        }

        $config->access_token = $this->encrypt($accessToken);

        if ($refreshToken) {
            $config->refresh_token = $this->encrypt($refreshToken);
        }

        if ($expiresIn) {
            $expiraEm = new \DateTime();
            $expiraEm->modify("+{$expiresIn} seconds");
            $config->token_expira_em = $expiraEm->format('Y-m-d H:i:s');
        }

        $config->data_atualizacao = new \yii\db\Expression('NOW()');

        return $config->save();
    }

    /**
     * Obtém access token descriptografado
     * @param MarketplaceConfig $config
     * @return string|null
     */
    public function getAccessToken($config)
    {
        if (empty($config->access_token)) {
            return null;
        }

        return $this->decrypt($config->access_token);
    }

    /**
     * Obtém refresh token descriptografado
     * @param MarketplaceConfig $config
     * @return string|null
     */
    public function getRefreshToken($config)
    {
        if (empty($config->refresh_token)) {
            return null;
        }

        return $this->decrypt($config->refresh_token);
    }

    /**
     * Verifica se o token está expirado
     * @param MarketplaceConfig $config
     * @return bool
     */
    public function isTokenExpired($config)
    {
        if (empty($config->token_expira_em)) {
            return true;
        }

        $expiraEm = new \DateTime($config->token_expira_em);
        $agora = new \DateTime();

        // Considera expirado se faltar menos de 5 minutos
        $expiraEm->modify('-5 minutes');

        return $agora >= $expiraEm;
    }

    /**
     * Criptografa dados sensíveis
     * @param string $data
     * @return string
     */
    protected function encrypt($data)
    {
        // Em produção, usar criptografia real (AES-256, etc)
        // Por enquanto, apenas base64 (NÃO É SEGURO EM PRODUÇÃO!)
        // TODO: Implementar criptografia real com Yii::$app->security

        return base64_encode($data);
    }

    /**
     * Descriptografa dados sensíveis
     * @param string $data
     * @return string
     */
    protected function decrypt($data)
    {
        // Em produção, usar descriptografia real
        // Por enquanto, apenas base64 (NÃO É SEGURO EM PRODUÇÃO!)
        // TODO: Implementar descriptografia real com Yii::$app->security

        return base64_decode($data);
    }

    /**
     * Desativa integração de um marketplace
     * @param string $usuarioId
     * @param string $marketplace
     * @return bool
     */
    public function disable($usuarioId, $marketplace)
    {
        $config = $this->getConfig($usuarioId, $marketplace);

        if (!$config) {
            return false;
        }

        $config->ativo = false;
        $config->data_atualizacao = new \yii\db\Expression('NOW()');

        return $config->save();
    }

    /**
     * Ativa integração de um marketplace
     * @param string $usuarioId
     * @param string $marketplace
     * @return bool
     */
    public function enable($usuarioId, $marketplace)
    {
        $config = $this->getConfig($usuarioId, $marketplace);

        if (!$config) {
            return false;
        }

        $config->ativo = true;
        $config->data_atualizacao = new \yii\db\Expression('NOW()');

        return $config->save();
    }
}
