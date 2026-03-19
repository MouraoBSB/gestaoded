<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    // Verificar colunas da tabela alunos
    echo "=== Colunas da tabela alunos ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM alunos");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\n=== Testando query de alunos matriculados ===\n";
    $turmaId = 7;
    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, a.email, m.data_matricula, m.status
        FROM alunos a
        INNER JOIN matriculas m ON a.id = m.aluno_id
        WHERE m.turma_id = ?
        ORDER BY a.nome
    ");
    $stmt->execute([$turmaId]);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Encontrados: " . count($alunos) . " alunos\n";
    print_r($alunos);
    
    echo "\n=== Testando query de alunos disponíveis ===\n";
    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, a.email
        FROM alunos a
        WHERE a.id NOT IN (
            SELECT COALESCE(m.aluno_id, 0)
            FROM matriculas m 
            WHERE m.turma_id = ?
        )
        ORDER BY a.nome
    ");
    $stmt->execute([$turmaId]);
    $disponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Encontrados: " . count($disponiveis) . " alunos disponíveis\n";
    print_r($disponiveis);
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
