<?php
/**
 * Script para resetar todas as senhas para cema2026
 * Executar apenas uma vez via navegador ou CLI
 */

require_once __DIR__ . '/config/database.php';

$pdo = getConnection();
$senhaHash = password_hash('cema2026', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE usuarios SET senha = ?");
$stmt->execute([$senhaHash]);

$total = $stmt->rowCount();
echo "Senhas atualizadas com sucesso! Total de usuários afetados: {$total}";
