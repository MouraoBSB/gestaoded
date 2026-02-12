-- Autor: Thiago Mourão
-- Instagram: https://www.instagram.com/mouraoeguerin/
-- Data: 2026-02-11 18:02:00

-- Adicionar campo de foto para usuarios (instrutores)
ALTER TABLE usuarios ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER email;

-- Adicionar campo de capa para cursos
ALTER TABLE cursos ADD COLUMN capa VARCHAR(255) DEFAULT NULL AFTER nome;

-- Criar tabela para múltiplos instrutores por curso
CREATE TABLE IF NOT EXISTS curso_instrutores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    instrutor_id INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (instrutor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_curso_instrutor (curso_id, instrutor_id),
    INDEX idx_curso (curso_id),
    INDEX idx_instrutor (instrutor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela de configurações SMTP
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações SMTP padrão
INSERT INTO configuracoes (chave, valor) VALUES
('smtp_host', ''),
('smtp_port', '587'),
('smtp_usuario', ''),
('smtp_senha', ''),
('smtp_de_email', ''),
('smtp_de_nome', 'Sistema de Chamada'),
('smtp_seguranca', 'tls')
ON DUPLICATE KEY UPDATE chave = chave;

-- Criar tabela para tokens de reset de senha
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
