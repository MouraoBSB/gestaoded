<?php
/**
 * Migration: Adicionar campo observacao na tabela presencas
 */

require_once __DIR__ . '/config/database.php';

$pdo = getConnection();

try {
    $colunas = $pdo->query("SHOW COLUMNS FROM presencas LIKE 'observacao'")->fetchAll();

    if (empty($colunas)) {
        $pdo->exec("ALTER TABLE presencas ADD COLUMN observacao TEXT NULL AFTER presente");
        echo "Coluna observacao adicionada na tabela presencas com sucesso!";
    } else {
        echo "Coluna observacao ja existe.";
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
