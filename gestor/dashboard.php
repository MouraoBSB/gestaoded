<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('gestor');

$pdo = getConnection();

$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1")->fetchColumn();
$totalAlunos = $pdo->query("SELECT COUNT(*) FROM alunos WHERE ativo = 1")->fetchColumn();
$totalCursos = $pdo->query("SELECT COUNT(*) FROM cursos WHERE ativo = 1")->fetchColumn();
$totalMatriculas = $pdo->query("SELECT COUNT(*) FROM matriculas")->fetchColumn();

$pageTitle = 'Dashboard - Gestor';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4" style="border-color: #4e4483;">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">Usuários</p>
                <p class="text-3xl font-bold" style="color: #4e4483;"><?= $totalUsuarios ?></p>
            </div>
            <div class="rounded-full p-3" style="background-color: rgba(78, 68, 131, 0.1);">
                <svg class="w-8 h-8" style="color: #4e4483;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4" style="border-color: #89ab98;">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">Alunos</p>
                <p class="text-3xl font-bold" style="color: #89ab98;"><?= $totalAlunos ?></p>
            </div>
            <div class="rounded-full p-3" style="background-color: rgba(137, 171, 152, 0.1);">
                <svg class="w-8 h-8" style="color: #89ab98;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4" style="border-color: #6e9fcb;">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">Cursos</p>
                <p class="text-3xl font-bold" style="color: #6e9fcb;"><?= $totalCursos ?></p>
            </div>
            <div class="rounded-full p-3" style="background-color: rgba(110, 159, 203, 0.1);">
                <svg class="w-8 h-8" style="color: #6e9fcb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4" style="border-color: #e79048;">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">Matrículas</p>
                <p class="text-3xl font-bold" style="color: #e79048;"><?= $totalMatriculas ?></p>
            </div>
            <div class="rounded-full p-3" style="background-color: rgba(231, 144, 72, 0.1);">
                <svg class="w-8 h-8" style="color: #e79048;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Acesso Rápido</h2>
        <div class="space-y-3">
            <a href="/gestor/instrutores.php" class="block p-4 rounded-lg transition border-l-4" style="background-color: rgba(78, 68, 131, 0.1); border-color: #4e4483;">
                <h3 class="font-semibold" style="color: #4e4483;">Gerenciar Instrutores</h3>
                <p class="text-sm text-gray-600">Criar, editar e visualizar instrutores</p>
            </a>
            <a href="/gestor/alunos.php" class="block p-4 rounded-lg transition border-l-4" style="background-color: rgba(137, 171, 152, 0.1); border-color: #89ab98;">
                <h3 class="font-semibold" style="color: #89ab98;">Gerenciar Alunos</h3>
                <p class="text-sm text-gray-600">Cadastrar e gerenciar alunos</p>
            </a>
            <a href="/gestor/cursos.php" class="block p-4 rounded-lg transition border-l-4" style="background-color: rgba(78, 68, 131, 0.1); border-color: #4e4483;">
                <h3 class="font-semibold" style="color: #4e4483;">Gerenciar Cursos</h3>
                <p class="text-sm text-gray-600">Criar e administrar cursos</p>
            </a>
            <a href="/diretor/matriculas_kanban.php" class="block p-4 rounded-lg transition border-l-4" style="background-color: rgba(110, 159, 203, 0.1); border-color: #6e9fcb;">
                <h3 class="font-semibold" style="color: #6e9fcb;">Vincular Alunos a Cursos</h3>
                <p class="text-sm text-gray-600">Arraste e solte alunos nos cursos (Kanban)</p>
            </a>
            <a href="/instrutor/nova_chamada.php" class="block p-4 rounded-lg transition border-l-4" style="background-color: rgba(231, 144, 72, 0.1); border-color: #e79048;">
                <h3 class="font-semibold" style="color: #e79048;">Registrar Chamada</h3>
                <p class="text-sm text-gray-600">Fazer chamada e registrar presença dos alunos</p>
            </a>
            <a href="/gestor/selecionar_curso_historico.php" class="block p-4 rounded-lg transition border-l-4" style="background-color: rgba(78, 68, 131, 0.1); border-color: #4e4483;">
                <h3 class="font-semibold" style="color: #4e4483;">Histórico de Chamadas</h3>
                <p class="text-sm text-gray-600">Ver chamadas e frequência dos cursos</p>
            </a>
            <a href="/gestor/frequencia_alunos.php" class="block p-4 rounded-lg transition border-l-4" style="background-color: rgba(137, 171, 152, 0.1); border-color: #89ab98;">
                <h3 class="font-semibold" style="color: #89ab98;">Frequência dos Alunos</h3>
                <p class="text-sm text-gray-600">Ver frequência individual de cada aluno</p>
            </a>
            <a href="/gestor/relatorios.php" class="block p-4 rounded-lg transition border-l-4" style="background-color: rgba(231, 144, 72, 0.1); border-color: #e79048;">
                <h3 class="font-semibold" style="color: #e79048;">Relatórios</h3>
                <p class="text-sm text-gray-600">Visualizar relatórios e estatísticas</p>
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Cursos Recentes</h2>
        <?php
        $cursosRecentes = $pdo->query("
            SELECT c.nome, c.ano, u.nome as instrutor
            FROM cursos c
            LEFT JOIN usuarios u ON c.instrutor_id = u.id
            WHERE c.ativo = 1
            ORDER BY c.criado_em DESC
            LIMIT 5
        ")->fetchAll();
        
        if ($cursosRecentes):
        ?>
            <div class="space-y-3">
                <?php foreach ($cursosRecentes as $curso): ?>
                    <div class="border-l-4 border-purple-500 pl-4 py-2">
                        <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($curso['nome']) ?></h3>
                        <p class="text-sm text-gray-600">
                            Ano: <?= $curso['ano'] ?> | 
                            Instrutor: <?= htmlspecialchars($curso['instrutor'] ?? 'Não atribuído') ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-600">Nenhum curso cadastrado ainda.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
