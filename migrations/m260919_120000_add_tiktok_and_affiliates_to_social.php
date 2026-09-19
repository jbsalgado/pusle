<?php

use yii\db\Migration;

/**
 * Migration: Adiciona suporte ao TikTok e a afiliados/colaboradores nas tabelas prest_social_accounts e prest_social_posts.
 */
class m260919_120000_add_tiktok_and_affiliates_to_social extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Colunas para prest_social_accounts
        $this->addColumn('prest_social_accounts', 'provider', "VARCHAR(50) NOT NULL DEFAULT 'META'");
        $this->addColumn('prest_social_accounts', 'tiktok_open_id', "VARCHAR(255) NULL");
        $this->addColumn('prest_social_accounts', 'tiktok_refresh_token', "TEXT NULL");
        $this->addColumn('prest_social_accounts', 'tiktok_refresh_expires_at', "TIMESTAMPTZ NULL");
        $this->addColumn('prest_social_accounts', 'account_avatar_url', "TEXT NULL");
        $this->addColumn('prest_social_accounts', 'colaborador_id', "UUID NULL");

        // FK de colaborador se a tabela prest_colaboradores existir
        try {
            $this->addForeignKey(
                'fk_social_accounts_colaborador',
                'prest_social_accounts',
                'colaborador_id',
                'prest_colaboradores',
                'id',
                'SET NULL',
                'CASCADE'
            );
        } catch (\Exception $e) {
            Yii::warning("Aviso ao criar FK fk_social_accounts_colaborador: " . $e->getMessage());
        }

        $this->createIndex('idx_social_accounts_provider', 'prest_social_accounts', 'provider');
        $this->createIndex('idx_social_accounts_tt_open_id', 'prest_social_accounts', 'tiktok_open_id');
        $this->createIndex('idx_social_accounts_colaborador', 'prest_social_accounts', 'colaborador_id');

        // 2. Colunas para prest_social_posts
        $this->addColumn('prest_social_posts', 'colaborador_id', "UUID NULL");

        try {
            $this->addForeignKey(
                'fk_social_posts_colaborador',
                'prest_social_posts',
                'colaborador_id',
                'prest_colaboradores',
                'id',
                'SET NULL',
                'CASCADE'
            );
        } catch (\Exception $e) {
            Yii::warning("Aviso ao criar FK fk_social_posts_colaborador: " . $e->getMessage());
        }

        $this->createIndex('idx_social_posts_colaborador', 'prest_social_posts', 'colaborador_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx_social_posts_colaborador', 'prest_social_posts');
        try {
            $this->dropForeignKey('fk_social_posts_colaborador', 'prest_social_posts');
        } catch (\Exception $e) {}
        $this->dropColumn('prest_social_posts', 'colaborador_id');

        $this->dropIndex('idx_social_accounts_colaborador', 'prest_social_accounts');
        $this->dropIndex('idx_social_accounts_tt_open_id', 'prest_social_accounts');
        $this->dropIndex('idx_social_accounts_provider', 'prest_social_accounts');

        try {
            $this->dropForeignKey('fk_social_accounts_colaborador', 'prest_social_accounts');
        } catch (\Exception $e) {}

        $this->dropColumn('prest_social_accounts', 'colaborador_id');
        $this->dropColumn('prest_social_accounts', 'account_avatar_url');
        $this->dropColumn('prest_social_accounts', 'tiktok_refresh_expires_at');
        $this->dropColumn('prest_social_accounts', 'tiktok_refresh_token');
        $this->dropColumn('prest_social_accounts', 'tiktok_open_id');
        $this->dropColumn('prest_social_accounts', 'provider');
    }
}
