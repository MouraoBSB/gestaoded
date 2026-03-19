<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-14 14:56:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole('gestor');

header('Content-Type: application/json');

$turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;

if (!$turmaId) {
    echo json_encode([]);
    exit;
}

$pdo = getConnection();

$stmt = $pdo->prepare("SELECT instrutor_id FROM turma_instrutores WHERE turma_id = ?");
$stmt->execute([$turmaId]);
$instrutores = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($instrutores);
