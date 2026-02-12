<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:17:00
 */

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalação - Sistema de Chamada</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 min-h-screen flex items-center justify-center p-4'>
    <div class='bg-white rounded-lg shadow-2xl w-full max-w-2xl p-8'>";

echo "<h1 class='text-3xl font-bold text-gray-800 mb-6'>Instalação do Sistema</h1>";

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4'>
            ✓ Conexão com banco de dados estabelecida com sucesso!
          </div>";
} catch (Exception $e) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>
            ✗ Erro ao conectar ao banco de dados: " . htmlspecialchars($e->getMessage()) . "
          </div>";
    echo "</div></body></html>";
    exit;
}

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

echo "<div class='space-y-2 mb-6'>";

try {
    foreach ($queries as $tabela => $query) {
        $pdo->exec($query);
        echo "<div class='flex items-center text-green-700'>
                <svg class='w-5 h-5 mr-2' fill='currentColor' viewBox='0 0 20 20'>
                    <path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'/>
                </svg>
                Tabela '<strong>$tabela</strong>' criada com sucesso
              </div>";
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE email = 'admin@sistema.com'");
    $existe = $stmt->fetchColumn();
    
    if (!$existe) {
        $senhaHash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO usuarios (nome, email, senha, tipo) VALUES ('Administrador', 'admin@sistema.com', '$senhaHash', 'gestor')");
        
        echo "<div class='flex items-center text-blue-700 mt-4'>
                <svg class='w-5 h-5 mr-2' fill='currentColor' viewBox='0 0 20 20'>
                    <path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'/>
                </svg>
                Usuário administrador criado
              </div>";
    } else {
        echo "<div class='flex items-center text-yellow-700 mt-4'>
                <svg class='w-5 h-5 mr-2' fill='currentColor' viewBox='0 0 20 20'>
                    <path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z' clip-rule='evenodd'/>
                </svg>
                Usuário administrador já existe
              </div>";
    }
    
    echo "</div>";
    
    echo "<div class='bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4'>
            <p class='font-bold mb-2'>✓ Instalação concluída com sucesso!</p>
            <p class='text-sm'>Credenciais de acesso:</p>
            <p class='text-sm'><strong>Email:</strong> admin@sistema.com</p>
            <p class='text-sm'><strong>Senha:</strong> admin123</p>
          </div>";
    
    echo "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4'>
            <p class='font-bold'>⚠️ IMPORTANTE:</p>
            <p class='text-sm'>Altere a senha padrão após o primeiro login!</p>
            <p class='text-sm mt-2'>Por segurança, delete este arquivo após a instalação.</p>
          </div>";
    
    echo "<div class='flex gap-3'>
            <a href='/login.php' class='flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg transition font-semibold'>
                Acessar Sistema
            </a>
            <a href='javascript:history.back()' class='flex-1 bg-gray-500 hover:bg-gray-600 text-white text-center py-3 rounded-lg transition font-semibold'>
                Voltar
            </a>
          </div>";
    
} catch (PDOException $e) {
    echo "</div>";
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded'>
            <p class='font-bold'>✗ Erro durante a instalação:</p>
            <p class='text-sm'>" . htmlspecialchars($e->getMessage()) . "</p>
          </div>";
}

echo "</div></body></html>";
?>
