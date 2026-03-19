<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['gestor', 'instrutor']);

$pdo = getConnection();
$instrutorId = getUserId();

$meusCursos = $pdo->prepare("
    SELECT COUNT(DISTINCT t.id) FROM turmas t
    INNER JOIN cursos c ON t.curso_id = c.id
    INNER JOIN turma_instrutores ti ON t.id = ti.turma_id
    WHERE ti.instrutor_id = ? AND t.status = 'ativa' AND c.ativo = 1
");
$meusCursos->execute([$instrutorId]);
$totalMeusCursos = $meusCursos->fetchColumn();

$minhasAulas = $pdo->prepare("
    SELECT COUNT(*) FROM aulas a
    INNER JOIN turmas t ON a.turma_id = t.id
    INNER JOIN turma_instrutores ti ON t.id = ti.turma_id
    WHERE ti.instrutor_id = ? AND t.status = 'ativa'
");
$minhasAulas->execute([$instrutorId]);
$totalMinhasAulas = $minhasAulas->fetchColumn();

$cursosLista = $pdo->prepare("
    SELECT t.id as turma_id, c.id, c.nome, t.ano, t.data_inicio, t.data_fim,
           COUNT(DISTINCT m.aluno_id) as total_alunos
    FROM turmas t
    INNER JOIN cursos c ON t.curso_id = c.id
    INNER JOIN turma_instrutores ti ON t.id = ti.turma_id
    LEFT JOIN matriculas m ON t.id = m.turma_id
    WHERE ti.instrutor_id = ? AND t.status = 'ativa' AND c.ativo = 1
    GROUP BY t.id, c.id, c.nome, t.ano, t.data_inicio, t.data_fim
    ORDER BY t.ano DESC, c.nome
");
$cursosLista->execute([$instrutorId]);
$cursos = $cursosLista->fetchAll();

$pageTitle = 'Dashboard - Instrutor';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">Meus Cursos</p>
                <p class="text-3xl font-bold text-purple-600"><?= $totalMeusCursos ?></p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">Aulas Registradas</p>
                <p class="text-3xl font-bold text-blue-600"><?= $totalMinhasAulas ?></p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Ações Rápidas</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-lg overflow-hidden border border-blue-200">
            <a href="/instrutor/nova_chamada.php" class="block bg-blue-50 hover:bg-blue-100 p-4 transition">
                <h3 class="font-semibold text-blue-800">Registrar Chamada</h3>
                <p class="text-sm text-gray-600">Fazer chamada e registrar presença dos alunos</p>
            </a>
            <div class="border-t border-blue-200">
                <a href="/instrutor/selecionar_curso_historico.php" class="flex items-center gap-2 px-4 py-2.5 hover:bg-blue-100 transition" style="background-color: rgba(59, 130, 246, 0.05);">
                    <svg class="w-4 h-4 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-blue-700">Histórico de Chamadas</span>
                    <span class="text-xs text-gray-500">- Por curso/turma</span>
                </a>
                <a href="/instrutor/frequencia_alunos.php" class="flex items-center gap-2 px-4 py-2.5 hover:bg-blue-100 transition border-t border-blue-100" style="background-color: rgba(59, 130, 246, 0.05);">
                    <svg class="w-4 h-4 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-sm font-medium text-blue-700">Frequência dos Alunos</span>
                    <span class="text-xs text-gray-500">- Individual</span>
                </a>
            </div>
        </div>
        <a href="/instrutor/alunos.php" class="block bg-green-50 hover:bg-green-100 p-4 rounded-lg transition">
            <h3 class="font-semibold text-green-800">Meus Alunos</h3>
            <p class="text-sm text-gray-600">Ver e editar dados dos alunos das suas turmas</p>
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Meus Cursos</h2>
    <?php if (empty($cursos)): ?>
        <p class="text-gray-600">Você ainda não possui cursos atribuídos.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($cursos as $curso): ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                    <h3 class="font-semibold text-gray-800 mb-2"><?= htmlspecialchars($curso['nome']) ?></h3>
                    <p class="text-sm text-gray-600 mb-1">Ano: <?= $curso['ano'] ?></p>
                    <?php if ($curso['data_inicio']): ?>
                        <p class="text-xs text-gray-500 mb-1">
                            <?= date('d/m/Y', strtotime($curso['data_inicio'])) ?>
                            <?= $curso['data_fim'] ? ' - ' . date('d/m/Y', strtotime($curso['data_fim'])) : '' ?>
                        </p>
                    <?php endif; ?>
                    <p class="text-sm text-gray-600 mb-4">Alunos: <?= $curso['total_alunos'] ?></p>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="/instrutor/historico_chamadas.php?turma_id=<?= $curso['turma_id'] ?>"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-center py-2 rounded-lg transition text-sm">
                            Histórico
                        </a>
                        <a href="/instrutor/registrar_chamada.php?turma_id=<?= $curso['turma_id'] ?>"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-lg transition text-sm">
                            Nova Chamada
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
