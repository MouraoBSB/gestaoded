<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-15 15:02:00
 * 
 * Script para adicionar campos de dias da semana e horário aos cursos
 */

require_once __DIR__ . '/config/database.php';

$executado = false;
$mensagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar'])) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->query("SHOW COLUMNS FROM cursos LIKE 'dias_semana'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN dias_semana JSON NULL AFTER pre_requisito");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo dias_semana (JSON) adicionado na tabela cursos'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo dias_semana já existe'];
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM cursos LIKE 'horario_inicio'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN horario_inicio TIME NULL AFTER dias_semana");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo horario_inicio (TIME) adicionado na tabela cursos'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo horario_inicio já existe'];
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM cursos LIKE 'horario_fim'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN horario_fim TIME NULL AFTER horario_inicio");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo horario_fim (TIME) adicionado na tabela cursos'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo horario_fim já existe'];
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
    <title>Adicionar Horários - Cursos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-4xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Adicionar Campos de Horários aos Cursos</h1>
    
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
                <div class="border-l-4 border-purple-500 pl-4">
                    <h3 class="font-bold text-gray-800">Campo Dias da Semana</h3>
                    <p class="text-gray-600">Adiciona campo dias_semana (JSON) para armazenar múltiplos dias</p>
                    <p class="text-sm text-gray-500 mt-1">Exemplo: ["segunda", "quarta", "sexta"]</p>
                </div>
                
                <div class="border-l-4 border-purple-500 pl-4">
                    <h3 class="font-bold text-gray-800">Campo Horário Início</h3>
                    <p class="text-gray-600">Adiciona campo horario_inicio (TIME) para horário de início da aula</p>
                    <p class="text-sm text-gray-500 mt-1">Exemplo: 20:00</p>
                </div>
                
                <div class="border-l-4 border-purple-500 pl-4">
                    <h3 class="font-bold text-gray-800">Campo Horário Fim</h3>
                    <p class="text-gray-600">Adiciona campo horario_fim (TIME) para horário de término da aula</p>
                    <p class="text-sm text-gray-500 mt-1">Exemplo: 21:30</p>
                </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-blue-800 font-semibold">📋 Como Funciona:</p>
                <ul class="list-disc list-inside text-blue-700 mt-2 space-y-1">
                    <li>Selecione um ou mais dias da semana para o curso</li>
                    <li>Defina horário de início e fim das aulas</li>
                    <li>Na página pública aparecerá: "Todas as segundas e quartas, das 20h00 às 21h30"</li>
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
