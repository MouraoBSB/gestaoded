<?php
/**
 * Migration: Adicionar turma_id na tabela aulas
 * Chamadas/aulas devem ser vinculadas a turmas, nao a cursos diretamente
 */

require_once __DIR__ . '/config/database.php';

$pdo = getConnection();

try {
    $colunas = $pdo->query("SHOW COLUMNS FROM aulas LIKE 'turma_id'")->fetchAll();

    if (empty($colunas)) {
        $pdo->exec("ALTER TABLE aulas ADD COLUMN turma_id INT NULL AFTER curso_id");
        $pdo->exec("ALTER TABLE aulas ADD INDEX idx_turma (turma_id)");

        // Tentar associar aulas existentes a turmas (pela relacao curso_id)
        $pdo->exec("
            UPDATE aulas a
            INNER JOIN turmas t ON t.curso_id = a.curso_id AND t.status = 'ativa'
            SET a.turma_id = t.id
            WHERE a.turma_id IS NULL
        ");

        echo "Coluna turma_id adicionada na tabela aulas com sucesso!<br>";
        echo "Aulas existentes foram associadas as turmas ativas.";
    } else {
        echo "Coluna turma_id ja existe na tabela aulas.";
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
