<?php
/**
 * Migration: Adicionar campos de datas do 2o semestre na tabela turmas
 * Para cursos anuais que tem intervalo entre semestres
 */

require_once __DIR__ . '/config/database.php';

$pdo = getConnection();

try {
    // Verificar se as colunas já existem
    $colunas = $pdo->query("SHOW COLUMNS FROM turmas LIKE 'data_inicio_s2'")->fetchAll();

    if (empty($colunas)) {
        $pdo->exec("ALTER TABLE turmas ADD COLUMN data_inicio_s2 DATE NULL AFTER data_fim");
        $pdo->exec("ALTER TABLE turmas ADD COLUMN data_fim_s2 DATE NULL AFTER data_inicio_s2");
        echo "Colunas data_inicio_s2 e data_fim_s2 adicionadas com sucesso!";
    } else {
        echo "Colunas ja existem.";
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
