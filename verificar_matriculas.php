<?php
require_once 'config/database.php';

$pdo = getConnection();

echo "=== VERIFICAÇÃO DE DADOS ===\n\n";

echo "ALUNOS ATIVOS:\n";
$alunos = $pdo->query("SELECT id, nome FROM alunos WHERE ativo = 1")->fetchAll();
echo "Total: " . count($alunos) . "\n";
foreach ($alunos as $aluno) {
    echo "- ID: {$aluno['id']} | Nome: {$aluno['nome']}\n";
}

echo "\nCURSOS ATIVOS:\n";
$cursos = $pdo->query("SELECT id, nome, ano FROM cursos WHERE ativo = 1")->fetchAll();
echo "Total: " . count($cursos) . "\n";
foreach ($cursos as $curso) {
    echo "- ID: {$curso['id']} | Nome: {$curso['nome']} | Ano: {$curso['ano']}\n";
}

echo "\nMATRÍCULAS:\n";
$matriculas = $pdo->query("SELECT m.id, a.nome as aluno, c.nome as curso FROM matriculas m INNER JOIN alunos a ON m.aluno_id = a.id INNER JOIN cursos c ON m.curso_id = c.id")->fetchAll();
echo "Total: " . count($matriculas) . "\n";
foreach ($matriculas as $mat) {
    echo "- ID: {$mat['id']} | Aluno: {$mat['aluno']} | Curso: {$mat['curso']}\n";
}
?>
