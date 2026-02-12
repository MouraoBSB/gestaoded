"""
Autor: Thiago Mourão
Instagram: https://www.instagram.com/mouraoeguerin/
Data: 2026-02-11 17:18:00
"""

import mysql.connector
from mysql.connector import Error
import hashlib

def criar_tabelas():
    try:
        print("Conectando ao banco de dados remoto...")
        
        conexao = mysql.connector.connect(
            host='186.209.113.101',
            database='cemaneto_chamadaded',
            user='cemaneto_chamadaded',
            password='QIHIt}bZa}[chA@P',
            charset='utf8mb4',
            collation='utf8mb4_unicode_ci'
        )
        
        if conexao.is_connected():
            print("✓ Conexão estabelecida com sucesso!\n")
            
            cursor = conexao.cursor()
            
            queries = {
                "usuarios": """CREATE TABLE IF NOT EXISTS usuarios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    senha VARCHAR(255) NOT NULL,
                    tipo ENUM('gestor', 'diretor', 'instrutor') NOT NULL,
                    ativo TINYINT(1) DEFAULT 1,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_tipo (tipo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci""",
                
                "alunos": """CREATE TABLE IF NOT EXISTS alunos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(255) NOT NULL,
                    foto VARCHAR(255) DEFAULT NULL,
                    endereco TEXT,
                    data_nascimento DATE NOT NULL,
                    ativo TINYINT(1) DEFAULT 1,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_nome (nome)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci""",
                
                "cursos": """CREATE TABLE IF NOT EXISTS cursos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(255) NOT NULL,
                    ano INT NOT NULL,
                    instrutor_id INT,
                    ativo TINYINT(1) DEFAULT 1,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (instrutor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
                    INDEX idx_ano (ano),
                    INDEX idx_instrutor (instrutor_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci""",
                
                "matriculas": """CREATE TABLE IF NOT EXISTS matriculas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    aluno_id INT NOT NULL,
                    curso_id INT NOT NULL,
                    data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
                    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_matricula (aluno_id, curso_id),
                    INDEX idx_aluno (aluno_id),
                    INDEX idx_curso (curso_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci""",
                
                "aulas": """CREATE TABLE IF NOT EXISTS aulas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    curso_id INT NOT NULL,
                    data_aula DATE NOT NULL,
                    descricao TEXT,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
                    INDEX idx_curso_data (curso_id, data_aula)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci""",
                
                "presencas": """CREATE TABLE IF NOT EXISTS presencas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    aula_id INT NOT NULL,
                    aluno_id INT NOT NULL,
                    presente TINYINT(1) NOT NULL,
                    registrado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE,
                    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_presenca (aula_id, aluno_id),
                    INDEX idx_aula (aula_id),
                    INDEX idx_aluno (aluno_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci""",
                
                "conclusoes": """CREATE TABLE IF NOT EXISTS conclusoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    aluno_id INT NOT NULL,
                    curso_id INT NOT NULL,
                    aprovado TINYINT(1) NOT NULL,
                    ano_conclusao INT NOT NULL,
                    observacoes TEXT,
                    registrado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
                    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_conclusao (aluno_id, curso_id),
                    INDEX idx_aluno (aluno_id),
                    INDEX idx_curso (curso_id),
                    INDEX idx_ano (ano_conclusao)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"""
            }
            
            print("Criando tabelas...\n")
            
            for tabela, query in queries.items():
                try:
                    cursor.execute(query)
                    print(f"✓ Tabela '{tabela}' criada com sucesso")
                except Error as e:
                    print(f"✗ Erro ao criar tabela '{tabela}': {e}")
            
            print("\nCriando usuário administrador...")
            
            cursor.execute("SELECT COUNT(*) FROM usuarios WHERE email = 'admin@sistema.com'")
            existe = cursor.fetchone()[0]
            
            if not existe:
                import bcrypt
                senha_hash = bcrypt.hashpw('admin123'.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
                
                cursor.execute(
                    "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (%s, %s, %s, %s)",
                    ('Administrador', 'admin@sistema.com', senha_hash, 'gestor')
                )
                conexao.commit()
                print("✓ Usuário administrador criado")
                print("\nCredenciais de acesso:")
                print("Email: admin@sistema.com")
                print("Senha: admin123")
            else:
                print("✓ Usuário administrador já existe")
            
            print("\n" + "="*40)
            print("INSTALAÇÃO CONCLUÍDA COM SUCESSO!")
            print("="*40)
            print("\nPróximos passos:")
            print("1. Acesse o sistema em: http://seudominio.com/login.php")
            print("2. Faça login com as credenciais acima")
            print("3. IMPORTANTE: Altere a senha padrão após o primeiro login!")
            print("4. Delete este arquivo (criar_tabelas.py) por segurança")
            
    except Error as e:
        print(f"✗ ERRO: {e}")
        return False
    
    finally:
        if conexao.is_connected():
            cursor.close()
            conexao.close()
            print("\nConexão encerrada.")
    
    return True

if __name__ == "__main__":
    criar_tabelas()
