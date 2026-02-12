<?php
require_once 'config/database.php';

$pdo = getConnection();
$stmt = $pdo->query('DESCRIBE cursos');
echo "Estrutura da tabela cursos:\n\n";
while($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
