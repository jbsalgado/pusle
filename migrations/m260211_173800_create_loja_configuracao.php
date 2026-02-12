<?php

use yii\db\Migration;

/**
 * Cria tabela loja_configuracao para centralizar dados da loja
 * Permite configuração editável de nome, endereço, telefone, CNPJ, etc.
 */
class m260211_173800_create_loja_configuracao extends Migration
{
    public function safeUp()
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS loja_configuracao (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                usuario_id UUID NOT NULL,
                
                -- Dados Básicos
                nome_loja VARCHAR(255) NOT NULL,
                nome_fantasia VARCHAR(255),
                razao_social VARCHAR(255),
                
                -- Documentos
                cpf_cnpj VARCHAR(18) NOT NULL,
                inscricao_estadual VARCHAR(20),
                inscricao_municipal VARCHAR(20),
                
                -- Contato
                telefone VARCHAR(20),
                celular VARCHAR(20),
                email VARCHAR(255),
                site VARCHAR(255),
                
                -- Endereço
                cep VARCHAR(10),
                logradouro VARCHAR(255),
                numero VARCHAR(20),
                complemento VARCHAR(100),
                bairro VARCHAR(100),
                cidade VARCHAR(100),
                estado VARCHAR(2),
                codigo_municipio_ibge VARCHAR(7),
                
                -- Logo
                logo_path VARCHAR(500),
                
                -- Metadados
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                CONSTRAINT fk_loja_config_usuario FOREIGN KEY (usuario_id) 
                    REFERENCES prest_usuarios(id) ON DELETE CASCADE,
                CONSTRAINT uq_loja_config_usuario UNIQUE(usuario_id)
            );
            
            -- Índice para busca rápida por usuario_id
            CREATE INDEX IF NOT EXISTS idx_loja_config_usuario 
                ON loja_configuracao(usuario_id);
            
            -- Trigger para atualizar updated_at automaticamente
            CREATE OR REPLACE FUNCTION update_loja_configuracao_updated_at()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            
            CREATE TRIGGER trigger_loja_configuracao_updated_at
                BEFORE UPDATE ON loja_configuracao
                FOR EACH ROW
                EXECUTE FUNCTION update_loja_configuracao_updated_at();
        ");

        echo "✅ Tabela loja_configuracao criada com sucesso!\n";
    }

    public function safeDown()
    {
        $this->execute("
            DROP TRIGGER IF EXISTS trigger_loja_configuracao_updated_at ON loja_configuracao;
            DROP FUNCTION IF EXISTS update_loja_configuracao_updated_at();
            DROP TABLE IF EXISTS loja_configuracao CASCADE;
        ");

        echo "✅ Tabela loja_configuracao removida!\n";
    }
}
