<?php
require_once __DIR__ . '/config/database.php';

$pdo = getConnection();

echo "=== Estrutura da tabela turma_instrutores ===\n";
$stmt = $pdo->query("SHOW CREATE TABLE turma_instrutores");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo $result['Create Table'] . "\n\n";

echo "=== Foreign Keys da tabela turma_instrutores ===\n";
$stmt = $pdo->query("
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'turma_instrutores'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
