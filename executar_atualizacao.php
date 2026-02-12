<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:18:00
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    echo "Executando atualizações...\n\n";
    
    // Adicionar coluna foto para usuarios
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER email");
        echo "✓ Coluna 'foto' adicionada à tabela usuarios\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ Coluna 'foto' já existe\n";
        } else {
            echo "✗ Erro: " . $e->getMessage() . "\n";
        }
    }
    
    // Adicionar coluna capa para cursos
    try {
        $pdo->exec("ALTER TABLE cursos ADD COLUMN capa VARCHAR(255) DEFAULT NULL AFTER nome");
        echo "✓ Coluna 'capa' adicionada à tabela cursos\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ Coluna 'capa' já existe\n";
        } else {
            echo "✗ Erro: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✓ Atualização concluída!\n";
    echo "\nRecarregue as páginas para ver as mudanças.\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}
?>
