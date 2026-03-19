<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-15 14:58:00
 * 
 * Script para limpar todas as capas de cursos existentes
 */

require_once __DIR__ . '/config/database.php';

$executado = false;
$mensagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar'])) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->query("SELECT id, capa FROM cursos WHERE capa IS NOT NULL AND capa != ''");
        $cursos = $stmt->fetchAll();
        
        $capasRemovidas = 0;
        $arquivosDeletados = 0;
        
        foreach ($cursos as $curso) {
            $caminhoArquivo = __DIR__ . '/assets/uploads/' . $curso['capa'];
            
            if (file_exists($caminhoArquivo)) {
                if (unlink($caminhoArquivo)) {
                    $arquivosDeletados++;
                }
            }
            
            $updateStmt = $pdo->prepare("UPDATE cursos SET capa = NULL WHERE id = ?");
            $updateStmt->execute([$curso['id']]);
            $capasRemovidas++;
        }
        
        $mensagens[] = ['tipo' => 'success', 'texto' => "✓ {$capasRemovidas} registros de capas removidos do banco de dados"];
        $mensagens[] = ['tipo' => 'success', 'texto' => "✓ {$arquivosDeletados} arquivos de imagem deletados do servidor"];
        $mensagens[] = ['tipo' => 'info', 'texto' => '• Agora você pode fazer upload das novas capas no formato 600x1000px'];
        $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Limpeza concluída com sucesso!'];
        $executado = true;
        
    } catch (Exception $e) {
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
    <title>Limpar Capas de Cursos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-4xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Limpar Capas de Cursos</h1>
    
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
                <div class="border-l-4 border-red-500 pl-4">
                    <h3 class="font-bold text-gray-800">Remoção de Capas</h3>
                    <p class="text-gray-600">Remove todas as capas de cursos do banco de dados</p>
                    <p class="text-sm text-gray-500 mt-1">Campo "capa" será definido como NULL para todos os cursos</p>
                </div>
                
                <div class="border-l-4 border-red-500 pl-4">
                    <h3 class="font-bold text-gray-800">Exclusão de Arquivos</h3>
                    <p class="text-gray-600">Deleta todos os arquivos de imagem de capas do servidor</p>
                    <p class="text-sm text-gray-500 mt-1">Arquivos serão removidos permanentemente da pasta /assets/uploads/</p>
                </div>
            </div>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-red-800 font-semibold">⚠️ ATENÇÃO - AÇÃO IRREVERSÍVEL:</p>
                <ul class="list-disc list-inside text-red-700 mt-2 space-y-1">
                    <li>Todas as capas de cursos serão removidas</li>
                    <li>Os arquivos de imagem serão deletados permanentemente</li>
                    <li>Esta ação NÃO pode ser desfeita</li>
                    <li>Você precisará fazer upload das novas capas no formato 600x1000px</li>
                </ul>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-blue-800 font-semibold">📋 Novo Formato:</p>
                <ul class="list-disc list-inside text-blue-700 mt-2 space-y-1">
                    <li>Dimensões: 600x1000 pixels (formato de capa de livro)</li>
                    <li>Proporção: 3:5 (vertical)</li>
                    <li>Na página pública, a capa será clicável e abrirá em lightbox</li>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit" name="executar" value="1" class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg transition font-semibold">
                    Confirmar e Executar Limpeza
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
