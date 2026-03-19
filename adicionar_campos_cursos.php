<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-14 15:25:00
 * 
 * Script para adicionar campos pré-requisito, período e modalidade na tabela cursos
 */

require_once __DIR__ . '/config/database.php';

$executado = false;
$mensagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar'])) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->query("SHOW COLUMNS FROM cursos LIKE 'pre_requisito'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN pre_requisito TEXT NULL AFTER descricao");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo pre_requisito adicionado na tabela cursos'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo pre_requisito já existe'];
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM cursos LIKE 'data_inicio'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN data_inicio DATE NULL AFTER tipo_periodo");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo data_inicio adicionado na tabela cursos'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo data_inicio já existe'];
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM cursos LIKE 'data_fim'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN data_fim DATE NULL AFTER data_inicio");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo data_fim adicionado na tabela cursos'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo data_fim já existe'];
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM cursos LIKE 'modalidade'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN modalidade ENUM('presencial', 'online') DEFAULT 'presencial' AFTER data_fim");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo modalidade adicionado na tabela cursos'];
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
    <title>Adicionar Campos - Cursos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-4xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Adicionar Campos aos Cursos</h1>
    
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
                <a href="/gestor/cursos.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition">
                    Gerenciar Cursos
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">O que será feito?</h2>
            
            <div class="space-y-4 mb-6">
                <div class="border-l-4 border-blue-500 pl-4">
                    <h3 class="font-bold text-gray-800">1. Campo Pré-requisito</h3>
                    <p class="text-gray-600">Adiciona campo pre_requisito (TEXT, opcional) para informar requisitos do curso</p>
                </div>
                
                <div class="border-l-4 border-green-500 pl-4">
                    <h3 class="font-bold text-gray-800">2. Campos de Período</h3>
                    <p class="text-gray-600">Adiciona campos data_inicio e data_fim (DATE, opcionais) para definir período do curso</p>
                </div>
                
                <div class="border-l-4 border-purple-500 pl-4">
                    <h3 class="font-bold text-gray-800">3. Campo Modalidade</h3>
                    <p class="text-gray-600">Adiciona campo modalidade (ENUM: presencial/online, padrão presencial)</p>
                </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-yellow-800 font-semibold">⚠️ Importante:</p>
                <ul class="list-disc list-inside text-yellow-700 mt-2 space-y-1">
                    <li>Campos serão adicionados de forma segura</li>
                    <li>Dados existentes não serão afetados</li>
                    <li>Informações aparecerão na página pública de inscrições</li>
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
