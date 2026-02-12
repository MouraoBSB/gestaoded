<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 19:10:00
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    echo "Verificando estrutura da tabela conclusoes...\n\n";
    
    $stmt = $pdo->query("DESCRIBE conclusoes");
    $colunas = $stmt->fetchAll();
    
    if (empty($colunas)) {
        echo "Tabela conclusoes não existe. Criando...\n\n";
        
        $pdo->exec("
            CREATE TABLE conclusoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aluno_id INT NOT NULL,
                curso_id INT NOT NULL,
                status ENUM('aprovado', 'reprovado') NOT NULL,
                observacoes TEXT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_aluno_curso (aluno_id, curso_id),
                FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
                FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "✓ Tabela conclusoes criada com sucesso!\n";
    } else {
        echo "Estrutura atual da tabela conclusoes:\n";
        foreach ($colunas as $coluna) {
            echo "- {$coluna['Field']} ({$coluna['Type']})\n";
        }
        
        $temStatus = false;
        foreach ($colunas as $coluna) {
            if ($coluna['Field'] === 'status') {
                $temStatus = true;
                break;
            }
        }
        
        if (!$temStatus) {
            echo "\n⚠ Coluna 'status' não encontrada. Adicionando...\n";
            $pdo->exec("ALTER TABLE conclusoes ADD COLUMN status ENUM('aprovado', 'reprovado') NOT NULL AFTER curso_id");
            echo "✓ Coluna 'status' adicionada com sucesso!\n";
        } else {
            echo "\n✓ Tabela conclusoes está correta!\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}
?>
