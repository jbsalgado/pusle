-- SQL para criação da tabela de notificações do App Pulse
CREATE TABLE sys_notificacoes_app (
    id SERIAL PRIMARY KEY,
    usuario_id INT NOT NULL, 
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    lida BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificacao_usuario FOREIGN KEY (usuario_id) REFERENCES sys_usuarios(id) ON DELETE CASCADE
);

CREATE INDEX idx_notif_usuario_lida ON sys_notificacoes_app(usuario_id, lida);
