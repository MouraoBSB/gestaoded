<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-15 15:41:00
 *
 * Script para adicionar campo whatsapp na tabela usuarios
 */

require_once __DIR__ . '/config/database.php';

$executado = false;
$mensagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar'])) {
    try {
        $pdo = getConnection();

        $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'whatsapp'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN whatsapp VARCHAR(20) NULL AFTER email");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo whatsapp (VARCHAR 20) adicionado na tabela usuarios'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo whatsapp já existe'];
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
    <title>Adicionar WhatsApp - Usuários</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-4xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Adicionar Campo WhatsApp aos Usuários</h1>

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
                <a href="/gestor/instrutores.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition">
                    Gerenciar Instrutores
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">O que será feito?</h2>

            <div class="space-y-4 mb-6">
                <div class="border-l-4 border-purple-500 pl-4">
                    <h3 class="font-bold text-gray-800">Campo WhatsApp</h3>
                    <p class="text-gray-600">Adiciona campo whatsapp (VARCHAR 20) para armazenar número de telefone</p>
                    <p class="text-sm text-gray-500 mt-1">Exemplo: (61) 99999-9999</p>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-blue-800 font-semibold">📋 Aplicável a:</p>
                <ul class="list-disc list-inside text-blue-700 mt-2 space-y-1">
                    <li>Instrutores</li>
                    <li>Gestores</li>
                    <li>Todos os tipos de usuários do sistema</li>
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
