<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:18:00
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Conectando ao banco de dados remoto...\n";

try {
    $pdo = new PDO(
        "mysql:host=186.209.113.101;dbname=cemaneto_chamadaded;charset=utf8mb4",
        "cemaneto_chamadaded",
        "QIHIt}bZa}[chA@P",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    echo "✓ Conexão estabelecida com sucesso!\n\n";
    
    $queries = [
        "usuarios" => "CREATE TABLE IF NOT EXISTS usuarios (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "alunos" => "CREATE TABLE IF NOT EXISTS alunos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            foto VARCHAR(255) DEFAULT NULL,
            endereco TEXT,
            data_nascimento DATE NOT NULL,
            ativo TINYINT(1) DEFAULT 1,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_nome (nome)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "cursos" => "CREATE TABLE IF NOT EXISTS cursos (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "matriculas" => "CREATE TABLE IF NOT EXISTS matriculas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aluno_id INT NOT NULL,
            curso_id INT NOT NULL,
            data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
            FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_matricula (aluno_id, curso_id),
            INDEX idx_aluno (aluno_id),
            INDEX idx_curso (curso_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "aulas" => "CREATE TABLE IF NOT EXISTS aulas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            curso_id INT NOT NULL,
            data_aula DATE NOT NULL,
            descricao TEXT,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
            INDEX idx_curso_data (curso_id, data_aula)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "presencas" => "CREATE TABLE IF NOT EXISTS presencas (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "conclusoes" => "CREATE TABLE IF NOT EXISTS conclusoes (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    echo "Criando tabelas...\n\n";
    
    foreach ($queries as $tabela => $query) {
        try {
            $pdo->exec($query);
            echo "✓ Tabela '$tabela' criada com sucesso\n";
        } catch (PDOException $e) {
            echo "✗ Erro ao criar tabela '$tabela': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nCriando usuário administrador...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE email = 'admin@sistema.com'");
    $existe = $stmt->fetchColumn();
    
    if (!$existe) {
        $senhaHash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO usuarios (nome, email, senha, tipo) VALUES ('Administrador', 'admin@sistema.com', '$senhaHash', 'gestor')");
        echo "✓ Usuário administrador criado\n";
        echo "\nCredenciais de acesso:\n";
        echo "Email: admin@sistema.com\n";
        echo "Senha: admin123\n";
    } else {
        echo "✓ Usuário administrador já existe\n";
    }
    
    echo "\n========================================\n";
    echo "INSTALAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "========================================\n";
    echo "\nPróximos passos:\n";
    echo "1. Acesse o sistema em: http://seudominio.com/login.php\n";
    echo "2. Faça login com as credenciais acima\n";
    echo "3. IMPORTANTE: Altere a senha padrão após o primeiro login!\n";
    echo "4. Delete este arquivo (criar_tabelas.php) por segurança\n";
    
} catch (PDOException $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
?>
