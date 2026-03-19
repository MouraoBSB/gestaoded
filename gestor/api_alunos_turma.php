<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-15 15:15:00
 * 
 * API para gerenciar alunos de uma turma específica
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || $_SESSION['usuario_tipo'] !== 'gestor') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

try {
    $pdo = getConnection();
    $turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;

    if (!$turmaId) {
        echo json_encode(['error' => 'ID da turma é obrigatório']);
        exit;
    }

    // Buscar alunos matriculados na turma
    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, a.email, m.data_matricula
        FROM alunos a
        INNER JOIN matriculas m ON a.id = m.aluno_id
        WHERE m.turma_id = ?
        ORDER BY a.nome
    ");
    $stmt->execute([$turmaId]);
    $alunosMatriculados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar alunos NÃO matriculados na turma
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
    $alunosDisponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'matriculados' => $alunosMatriculados,
        'disponiveis' => $alunosDisponiveis
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao carregar alunos',
        'message' => $e->getMessage()
    ]);
}
