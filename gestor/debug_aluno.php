<?php
require_once __DIR__ . '/../config/database.php';

$alunoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$alunoId) {
    die("Informe o ID do aluno via ?id=X");
}

$pdo = getConnection();

echo "<h1>Debug - Aluno ID: $alunoId</h1>";

// Verificar matrículas
echo "<h2>Matrículas:</h2>";
$stmt = $pdo->prepare("SELECT m.*, c.nome as curso_nome FROM matriculas m INNER JOIN cursos c ON m.curso_id = c.id WHERE m.aluno_id = ?");
$stmt->execute([$alunoId]);
$matriculas = $stmt->fetchAll();
echo "<pre>";
print_r($matriculas);
echo "</pre>";

// Verificar conclusões
echo "<h2>Conclusões:</h2>";
$stmt = $pdo->prepare("SELECT co.*, c.nome as curso_nome FROM conclusoes co INNER JOIN cursos c ON co.curso_id = c.id WHERE co.aluno_id = ?");
$stmt->execute([$alunoId]);
$conclusoes = $stmt->fetchAll();
echo "<pre>";
print_r($conclusoes);
echo "</pre>";

// Query atual
echo "<h2>Query Atual (estatisticas_aluno.php):</h2>";
$cursos = $pdo->prepare("
    SELECT 
        c.id,
        c.nome,
        c.ano,
        co.status,
        co.observacoes,
        COUNT(DISTINCT aulas.id) as total_aulas,
        SUM(CASE WHEN p.presente IN (1,2) THEN 1 ELSE 0 END) as total_presencas
    FROM matriculas m
    INNER JOIN cursos c ON m.curso_id = c.id
    LEFT JOIN conclusoes co ON co.aluno_id = m.aluno_id AND co.curso_id = c.id
    LEFT JOIN aulas ON aulas.curso_id = c.id
    LEFT JOIN presencas p ON p.aula_id = aulas.id AND p.aluno_id = m.aluno_id
    WHERE m.aluno_id = ? AND c.ativo = 1
    GROUP BY c.id, c.nome, c.ano, co.status, co.observacoes
    ORDER BY c.ano DESC, c.nome
");
$cursos->execute([$alunoId]);
$cursosLista = $cursos->fetchAll();
echo "<pre>";
print_r($cursosLista);
echo "</pre>";
echo "<p><strong>Total de cursos retornados: " . count($cursosLista) . "</strong></p>";
