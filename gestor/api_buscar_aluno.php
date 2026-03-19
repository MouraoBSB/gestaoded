<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-13 14:24:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole('gestor');

header('Content-Type: application/json');

$pdo = getConnection();
$termo = $_GET['termo'] ?? '';

if (strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        a.id,
        a.nome,
        a.foto,
        COUNT(DISTINCT m.curso_id) as total_cursos,
        COUNT(DISTINCT CASE WHEN co.status = 'aprovado' THEN co.id END) as total_aprovados,
        COUNT(DISTINCT CASE WHEN co.status = 'reprovado' THEN co.id END) as total_reprovados
    FROM alunos a
    LEFT JOIN matriculas m ON a.id = m.aluno_id
    LEFT JOIN conclusoes co ON a.id = co.aluno_id
    WHERE a.ativo = 1 AND a.nome LIKE ?
    GROUP BY a.id, a.nome, a.foto
    ORDER BY a.nome
    LIMIT 10
");

$stmt->execute(['%' . $termo . '%']);
$alunos = $stmt->fetchAll();

echo json_encode($alunos);
