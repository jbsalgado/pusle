<?php

namespace app\components;

use Yii;

/**
 * TenantHelper — Resolução do Tenant (Loja) no contexto multi-loja SaaS
 *
 * Responsável por retornar sempre o UUID do DONO DA LOJA (tenant),
 * independentemente de quem está logado (dono ou colaborador).
 *
 * Uso:
 *   TenantHelper::getId()   → UUID do dono da loja (tenant_id)
 *   TenantHelper::get()     → Objeto Usuario do dono da loja
 *   TenantHelper::isAdmin() → true se o usuário logado é admin do sistema
 *
 * Regra de resolução:
 *  - Se o usuário logado é DONO (eh_dono_loja = true) → retorna seu próprio ID
 *  - Se o usuário logado é COLABORADOR → busca o Colaborador vinculado e retorna o usuario_id (dono)
 */
class TenantHelper
{
    /** @var string|null Cache do tenant_id para evitar consultas repetidas por request */
    private static ?string $_tenantId = null;

    /**
     * Retorna o UUID do Dono da Loja (tenant_id) a partir da identidade atual.
     * Se nenhum usuário estiver logado, retorna string vazia.
     *
     * @return string UUID do dono da loja
     */
    public static function getId(): string
    {
        if (self::$_tenantId !== null) {
            return self::$_tenantId;
        }

        $identity = Yii::$app->user->identity;

        if (!$identity) {
            return '';
        }

        self::$_tenantId = $identity->getTenantId();
        return self::$_tenantId;
    }

    /**
     * Retorna o objeto Usuario do Dono da Loja.
     *
     * @return \app\models\Usuario|null
     */
    public static function get(): ?\app\models\Usuario
    {
        $id = self::getId();
        if (!$id) {
            return null;
        }

        $identity = Yii::$app->user->identity;

        // Se já é o dono, retorna diretamente
        if ($identity && $identity->getTenantId() === $identity->id) {
            return $identity;
        }

        // Se é colaborador, busca o dono
        return \app\models\Usuario::findOne($id);
    }

    /**
     * Verifica se o usuário logado é administrador do sistema SaaS.
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        $identity = Yii::$app->user->identity;
        if (!$identity) {
            return false;
        }

        // Suporta boolean do PostgreSQL (t/f/true/false/1/0)
        $isAdmin = $identity->is_admin ?? false;
        if (is_string($isAdmin)) {
            return in_array(strtolower(trim($isAdmin)), ['t', 'true', '1']);
        }
        return (bool) $isAdmin;
    }

    /**
     * Limpa o cache do tenant_id.
     * Útil após troca de sessão ou em testes.
     */
    public static function flush(): void
    {
        self::$_tenantId = null;
    }
}
