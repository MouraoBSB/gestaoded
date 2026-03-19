<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-15 15:45:00
 *
 * Script para deletar todos os usuários exceto o administrador
 */

require_once __DIR__ . '/config/database.php';

$executado = false;
$mensagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar'])) {
    try {
        $pdo = getConnection();
        $pdo->beginTransaction();

        // Contar usuários antes da limpeza
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        $totalAntes = $stmt->fetchColumn();

        // Contar administradores
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'gestor'");
        $totalAdmins = $stmt->fetchColumn();

        if ($totalAdmins === 0) {
            throw new Exception("ERRO: Nenhum administrador encontrado no sistema! Operação cancelada.");
        }

        // Deletar relacionamentos primeiro (para evitar erros de foreign key)
        $pdo->exec("DELETE FROM turma_instrutores WHERE instrutor_id IN (SELECT id FROM usuarios WHERE tipo != 'gestor')");
        $mensagens[] = ['tipo' => 'info', 'texto' => '• Relacionamentos de instrutores removidos'];

        // Deletar usuários não-administradores
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE tipo != 'gestor'");
        $stmt->execute();
        $deletados = $stmt->rowCount();

        // Contar usuários após limpeza
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        $totalDepois = $stmt->fetchColumn();

        $pdo->commit();

        $mensagens[] = ['tipo' => 'success', 'texto' => "✓ Total de usuários antes: $totalAntes"];
        $mensagens[] = ['tipo' => 'success', 'texto' => "✓ Usuários deletados: $deletados"];
        $mensagens[] = ['tipo' => 'success', 'texto' => "✓ Administradores mantidos: $totalAdmins"];
        $mensagens[] = ['tipo' => 'success', 'texto' => "✓ Total de usuários agora: $totalDepois"];
        $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Limpeza concluída com sucesso!'];
        $executado = true;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagens[] = ['tipo' => 'error', 'texto' => '✗ Erro: ' . $e->getMessage()];
        $executado = true;
    }
}

// Buscar informações atuais
try {
    $pdo = getConnection();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $totalUsuarios = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'gestor'");
    $totalGestores = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'instrutor'");
    $totalInstrutores = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT nome, email FROM usuarios WHERE tipo = 'gestor' ORDER BY nome");
    $gestores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $totalUsuarios = 0;
    $totalGestores = 0;
    $totalInstrutores = 0;
    $gestores = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpar Usuários</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-4xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Limpar Usuários do Sistema</h1>

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
            <h2 class="text-xl font-bold text-gray-800 mb-4">Situação Atual</h2>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-blue-600"><?= $totalUsuarios ?></div>
                    <div class="text-sm text-blue-800 mt-1">Total de Usuários</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-green-600"><?= $totalGestores ?></div>
                    <div class="text-sm text-green-800 mt-1">Administradores</div>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-purple-600"><?= $totalInstrutores ?></div>
                    <div class="text-sm text-purple-800 mt-1">Instrutores</div>
                </div>
            </div>

            <h3 class="font-bold text-gray-800 mb-3">Administradores que serão mantidos:</h3>
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <?php if (!empty($gestores)): ?>
                    <ul class="space-y-2">
                        <?php foreach ($gestores as $gestor): ?>
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-medium"><?= htmlspecialchars($gestor['nome']) ?></span>
                                <span class="text-gray-500 text-sm">(<?= htmlspecialchars($gestor['email']) ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-red-600">⚠️ Nenhum administrador encontrado!</p>
                <?php endif; ?>
            </div>

            <h2 class="text-xl font-bold text-gray-800 mb-4">O que será feito?</h2>

            <div class="space-y-4 mb-6">
                <div class="border-l-4 border-red-500 pl-4">
                    <h3 class="font-bold text-gray-800">Deletar Instrutores</h3>
                    <p class="text-gray-600">Remove todos os usuários do tipo "instrutor"</p>
                    <p class="text-sm text-gray-500 mt-1">Total a deletar: <?= $totalInstrutores ?></p>
                </div>

                <div class="border-l-4 border-green-500 pl-4">
                    <h3 class="font-bold text-gray-800">Manter Administradores</h3>
                    <p class="text-gray-600">Preserva todos os usuários do tipo "gestor"</p>
                    <p class="text-sm text-gray-500 mt-1">Total a manter: <?= $totalGestores ?></p>
                </div>

                <div class="border-l-4 border-blue-500 pl-4">
                    <h3 class="font-bold text-gray-800">Limpar Relacionamentos</h3>
                    <p class="text-gray-600">Remove vínculos de instrutores com turmas</p>
                    <p class="text-sm text-gray-500 mt-1">Tabela: turma_instrutores</p>
                </div>
            </div>

            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-red-800 font-semibold">⚠️ ATENÇÃO - AÇÃO IRREVERSÍVEL:</p>
                <ul class="list-disc list-inside text-red-700 mt-2 space-y-1">
                    <li>Todos os instrutores serão deletados permanentemente</li>
                    <li>Vínculos com turmas serão removidos</li>
                    <li>Esta ação NÃO pode ser desfeita</li>
                    <li>Apenas administradores serão mantidos</li>
                </ul>
            </div>

            <?php if ($totalGestores > 0): ?>
                <form method="POST" onsubmit="return confirm('TEM CERTEZA ABSOLUTA que deseja DELETAR PERMANENTEMENTE todos os instrutores?\n\nEsta ação NÃO PODE SER DESFEITA!\n\nApenas os <?= $totalGestores ?> administrador(es) serão mantidos.');">
                    <button type="submit" name="executar" value="1" class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg transition font-semibold">
                        Confirmar e Executar Limpeza
                    </button>
                </form>
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-yellow-800 font-semibold">⚠️ Operação bloqueada!</p>
                    <p class="text-yellow-700 mt-2">Não é possível executar a limpeza sem pelo menos um administrador no sistema.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
