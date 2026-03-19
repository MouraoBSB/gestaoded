<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-14 15:17:00
 * 
 * API para processar inscrição pública em curso
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getConnection();
    
    $turmaId = isset($_POST['turma_id']) ? (int)$_POST['turma_id'] : 0;
    $nome = trim($_POST['nome'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dataNascimento = $_POST['data_nascimento'] ?? '';
    $endereco = trim($_POST['endereco'] ?? '');
    
    if (!$turmaId || !$nome || !$whatsapp || !$dataNascimento) {
        echo json_encode([
            'success' => false,
            'message' => 'Preencha todos os campos obrigatórios.'
        ]);
        exit;
    }
    
    $whatsappLimpo = preg_replace('/\D/', '', $whatsapp);
    if (strlen($whatsappLimpo) < 10) {
        echo json_encode([
            'success' => false,
            'message' => 'WhatsApp inválido.'
        ]);
        exit;
    }
    
    $turma = $pdo->prepare("
        SELECT t.*, c.nome as curso_nome,
               COUNT(DISTINCT m.aluno_id) as total_inscritos
        FROM turmas t
        INNER JOIN cursos c ON t.curso_id = c.id
        LEFT JOIN matriculas m ON t.id = m.turma_id
        WHERE t.id = ? AND c.ativo = 1 AND t.status = 'ativa' AND t.inscricoes_abertas = 1
        GROUP BY t.id, t.curso_id, t.ano, t.semestre, t.vagas, c.nome
    ");
    $turma->execute([$turmaId]);
    $turma = $turma->fetch();
    
    if (!$turma) {
        echo json_encode([
            'success' => false,
            'message' => 'Turma não disponível para inscrição.'
        ]);
        exit;
    }
    
    $vagasDisponiveis = $turma['vagas'] - $turma['total_inscritos'];
    if ($vagasDisponiveis <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Não há mais vagas disponíveis para este curso.'
        ]);
        exit;
    }
    
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        SELECT id FROM alunos 
        WHERE nome = ? AND data_nascimento = ?
        LIMIT 1
    ");
    $stmt->execute([$nome, $dataNascimento]);
    $alunoExistente = $stmt->fetch();
    
    if ($alunoExistente) {
        $alunoId = $alunoExistente['id'];
        
        $stmt = $pdo->prepare("
            UPDATE alunos 
            SET whatsapp = ?, email = ?, endereco = ?
            WHERE id = ?
        ");
        $stmt->execute([$whatsapp, $email ?: null, $endereco ?: null, $alunoId]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO alunos (nome, whatsapp, email, data_nascimento, endereco, ativo)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$nome, $whatsapp, $email ?: null, $dataNascimento, $endereco ?: null]);
        $alunoId = $pdo->lastInsertId();
    }
    
    $stmt = $pdo->prepare("
        SELECT id FROM matriculas 
        WHERE aluno_id = ? AND turma_id = ?
    ");
    $stmt->execute([$alunoId, $turmaId]);
    
    if ($stmt->fetch()) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Você já está inscrito neste curso.'
        ]);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO matriculas (aluno_id, curso_id, turma_id)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$alunoId, $turma['curso_id'], $turmaId]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Inscrição realizada com sucesso! Você receberá mais informações em breve.'
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar inscrição. Tente novamente.'
    ]);
}
