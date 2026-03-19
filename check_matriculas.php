<?php
require_once __DIR__ . '/config/database.php';

$pdo = getConnection();
$stmt = $pdo->query("SHOW COLUMNS FROM matriculas");
echo "Colunas da tabela matriculas:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
