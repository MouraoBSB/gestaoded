<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 19:01:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['instrutor', 'gestor']);

$pdo = getConnection();
$aulaId = isset($_POST['aula_id']) ? (int)$_POST['aula_id'] : 0;
$cursoId = isset($_POST['curso_id']) ? (int)$_POST['curso_id'] : 0;

if (!$aulaId) {
    setFlashMessage('Aula não encontrada!', 'error');
    redirect('/instrutor/nova_chamada.php');
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("DELETE FROM presencas WHERE aula_id = ?");
    $stmt->execute([$aulaId]);
    
    $stmt = $pdo->prepare("DELETE FROM aulas WHERE id = ?");
    $stmt->execute([$aulaId]);
    
    $pdo->commit();
    
    setFlashMessage('Chamada excluída com sucesso!', 'success');
    
} catch (Exception $e) {
    $pdo->rollBack();
    setFlashMessage('Erro ao excluir chamada: ' . $e->getMessage(), 'error');
}

if ($cursoId) {
    redirect('/instrutor/historico_chamadas.php?curso_id=' . $cursoId);
} else {
    redirect('/instrutor/nova_chamada.php');
}
?>
