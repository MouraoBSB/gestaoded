<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:32:00
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['gestor', 'diretor']);

$pdo = getConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$alunoId = (int)$input['aluno_id'];
$novoCursoId = (int)$input['curso_id'];
$cursoAnteriorId = (int)$input['curso_anterior_id'];

try {
    $pdo->beginTransaction();
    
    if ($cursoAnteriorId > 0) {
        $stmt = $pdo->prepare("DELETE FROM matriculas WHERE aluno_id = ? AND curso_id = ?");
        $stmt->execute([$alunoId, $cursoAnteriorId]);
    }
    
    if ($novoCursoId > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO matriculas (aluno_id, curso_id) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE curso_id = VALUES(curso_id)
        ");
        $stmt->execute([$alunoId, $novoCursoId]);
        $mensagem = 'Aluno matriculado com sucesso!';
    } else {
        $mensagem = 'Matrícula removida com sucesso!';
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $mensagem
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    
    if ($e->getCode() == 23000) {
        echo json_encode([
            'success' => false,
            'message' => 'Este aluno já está matriculado neste curso!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao processar matrícula: ' . $e->getMessage()
        ]);
    }
}
?>
