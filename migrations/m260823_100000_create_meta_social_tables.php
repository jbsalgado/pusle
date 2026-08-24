<?php

use yii\db\Migration;

/**
 * Migration: Criação das tabelas prest_social_accounts e prest_social_posts
 * para a integração multi-tenant com Meta Graph API (Instagram Business e Facebook Pages).
 */
class m260823_100000_create_meta_social_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Tabela de Contas e Páginas Sociais Conectadas por Tenant
        $this->createTable('prest_social_accounts', [
            'id'                           => 'UUID PRIMARY KEY DEFAULT gen_random_uuid()',
            'tenant_id'                    => 'UUID NOT NULL',
            'facebook_page_id'             => 'VARCHAR(255) NULL',
            'instagram_business_account_id'=> 'VARCHAR(255) NULL',
            'page_name'                    => 'VARCHAR(255) NOT NULL',
            'access_token'                 => 'TEXT NOT NULL',
            'token_expires_at'             => 'TIMESTAMPTZ NULL',
            'status'                       => "VARCHAR(50) NOT NULL DEFAULT 'ACTIVE'",
            'created_at'                   => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
            'updated_at'                   => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
        ]);

        // FK de prest_social_accounts para prest_usuarios (dono da loja / tenant)
        $this->addForeignKey(
            'fk_social_accounts_tenant',
            'prest_social_accounts',
            'tenant_id',
            'prest_usuarios',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Índices de busca rápida em prest_social_accounts
        $this->createIndex('idx_social_accounts_tenant', 'prest_social_accounts', 'tenant_id');
        $this->createIndex('idx_social_accounts_ig_acc', 'prest_social_accounts', 'instagram_business_account_id');
        $this->createIndex('idx_social_accounts_fb_page', 'prest_social_accounts', 'facebook_page_id');

        // 2. Tabela de Histórico e Log de Publicações
        $this->createTable('prest_social_posts', [
            'id'                 => 'UUID PRIMARY KEY DEFAULT gen_random_uuid()',
            'tenant_id'          => 'UUID NOT NULL',
            'social_account_id'  => 'UUID NOT NULL',
            'platform'           => "VARCHAR(20) NOT NULL DEFAULT 'INSTAGRAM'", // INSTAGRAM, FACEBOOK, BOTH
            'media_type'         => 'VARCHAR(20) NOT NULL',                    // IMAGE, REELS, VIDEO
            'media_url'          => 'TEXT NOT NULL',
            'caption'            => 'TEXT NULL',
            'creation_id'        => 'VARCHAR(255) NULL',                       // Container ID retornado pela Meta
            'published_media_id' => 'VARCHAR(255) NULL',                       // ID final da mídia publicada
            'status'             => "VARCHAR(50) NOT NULL DEFAULT 'PENDING'",   // PENDING, PROCESSING, PUBLISHED, FAILED
            'error_payload'      => 'JSONB NULL',
            'created_at'         => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
            'updated_at'         => 'TIMESTAMPTZ NOT NULL DEFAULT NOW()',
        ]);

        // FKs de prest_social_posts
        $this->addForeignKey(
            'fk_social_posts_tenant',
            'prest_social_posts',
            'tenant_id',
            'prest_usuarios',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_social_posts_account',
            'prest_social_posts',
            'social_account_id',
            'prest_social_accounts',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Índices para prest_social_posts
        $this->createIndex('idx_social_posts_tenant', 'prest_social_posts', 'tenant_id');
        $this->createIndex('idx_social_posts_account', 'prest_social_posts', 'social_account_id');
        $this->createIndex('idx_social_posts_status', 'prest_social_posts', 'status');
        $this->createIndex('idx_social_posts_creation', 'prest_social_posts', 'creation_id');

        // Trigger para automatizar a atualização do updated_at no PostgreSQL
        $this->execute("
            CREATE OR REPLACE FUNCTION update_social_tables_updated_at()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        $this->execute("
            CREATE TRIGGER trg_social_accounts_updated_at
            BEFORE UPDATE ON prest_social_accounts
            FOR EACH ROW EXECUTE FUNCTION update_social_tables_updated_at();
        ");

        $this->execute("
            CREATE TRIGGER trg_social_posts_updated_at
            BEFORE UPDATE ON prest_social_posts
            FOR EACH ROW EXECUTE FUNCTION update_social_tables_updated_at();
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("DROP TRIGGER IF EXISTS trg_social_posts_updated_at ON prest_social_posts;");
        $this->execute("DROP TRIGGER IF EXISTS trg_social_accounts_updated_at ON prest_social_accounts;");
        $this->execute("DROP FUNCTION IF EXISTS update_social_tables_updated_at();");

        $this->dropTable('prest_social_posts');
        $this->dropTable('prest_social_accounts');
    }
}
