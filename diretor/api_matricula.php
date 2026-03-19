<?php
/**
 * Autor: Thiago Mourao
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:32:00
 *
 * API de matricula - vincula alunos a turmas (e curso associado)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['gestor', 'diretor']);

$pdo = getConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados invalidos']);
    exit;
}

$alunoId = (int)($input['aluno_id'] ?? 0);
$turmaId = (int)($input['turma_id'] ?? 0);
$cursoId = (int)($input['curso_id'] ?? 0);
$turmaAnteriorId = (int)($input['turma_anterior_id'] ?? 0);
$cursoAnteriorId = (int)($input['curso_anterior_id'] ?? 0);

try {
    $pdo->beginTransaction();

    // Remover matricula anterior (se veio de uma turma)
    if ($turmaAnteriorId > 0) {
        $stmt = $pdo->prepare("DELETE FROM matriculas WHERE aluno_id = ? AND turma_id = ?");
        $stmt->execute([$alunoId, $turmaAnteriorId]);
    }

    // Inserir nova matricula (se destino e uma turma)
    if ($turmaId > 0) {
        // Buscar curso_id da turma caso nao tenha sido enviado
        if (!$cursoId) {
            $stmt = $pdo->prepare("SELECT curso_id FROM turmas WHERE id = ?");
            $stmt->execute([$turmaId]);
            $cursoId = (int)$stmt->fetchColumn();
        }

        $stmt = $pdo->prepare("
            INSERT INTO matriculas (aluno_id, turma_id, curso_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE turma_id = VALUES(turma_id), curso_id = VALUES(curso_id)
        ");
        $stmt->execute([$alunoId, $turmaId, $cursoId]);
        $mensagem = 'Aluno matriculado na turma com sucesso!';
    } else {
        $mensagem = 'Matricula removida com sucesso!';
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
            'message' => 'Este aluno ja esta matriculado nesta turma!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao processar matricula: ' . $e->getMessage()
        ]);
    }
}
