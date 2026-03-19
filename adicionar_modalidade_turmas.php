<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-15 14:43:00
 * 
 * Script para adicionar campo modalidade na tabela turmas
 */

require_once __DIR__ . '/config/database.php';

$executado = false;
$mensagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar'])) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->query("SHOW COLUMNS FROM turmas LIKE 'modalidade'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE turmas ADD COLUMN modalidade ENUM('presencial', 'online') DEFAULT 'presencial' AFTER inscricoes_abertas");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo modalidade adicionado na tabela turmas'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo modalidade já existe'];
        }
        
        $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Atualização concluída com sucesso!'];
        $executado = true;
        
    } catch (PDOException $e) {
        $mensagens[] = ['tipo' => 'error', 'texto' => '✗ Erro: ' . $e->getMessage()];
        $executado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Modalidade - Turmas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-4xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Adicionar Campo Modalidade às Turmas</h1>
    
    <?php if ($executado): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Resultado</h2>
            <div class="space-y-2">
                <?php foreach ($mensagens as $msg): ?>
                    <div class="flex items-start gap-2 p-3 rounded <?= $msg['tipo'] === 'success' ? 'bg-green-50 text-green-800' : ($msg['tipo'] === 'error' ? 'bg-red-50 text-red-800' : 'bg-blue-50 text-blue-800') ?>">
                        <?= $msg['texto'] ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-6 flex gap-4">
                <a href="/gestor/dashboard.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                    Ir para Dashboard
                </a>
                <a href="/gestor/turmas.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition">
                    Gerenciar Turmas
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">O que será feito?</h2>
            
            <div class="space-y-4 mb-6">
                <div class="border-l-4 border-purple-500 pl-4">
                    <h3 class="font-bold text-gray-800">Campo Modalidade</h3>
                    <p class="text-gray-600">Adiciona campo modalidade (ENUM: presencial/online) na tabela turmas</p>
                    <p class="text-sm text-gray-500 mt-1">Cada turma poderá ter sua própria modalidade (presencial ou online)</p>
                </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-yellow-800 font-semibold">⚠️ Importante:</p>
                <ul class="list-disc list-inside text-yellow-700 mt-2 space-y-1">
                    <li>Campo será adicionado de forma segura</li>
                    <li>Turmas existentes terão modalidade "presencial" por padrão</li>
                    <li>Informação aparecerá na página pública de inscrições</li>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit" name="executar" value="1" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg transition font-semibold">
                    Executar Atualização
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
