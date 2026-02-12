<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:46:00
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    echo "Alterando coluna data_nascimento para permitir NULL...\n\n";
    
    $pdo->exec("ALTER TABLE alunos MODIFY COLUMN data_nascimento DATE NULL");
    
    echo "✓ Coluna data_nascimento agora permite valores NULL\n";
    echo "\nAgora você pode cadastrar alunos sem informar a data de nascimento!\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}
?>
