<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:41:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['gestor', 'diretor', 'instrutor']);

$pdo = getConnection();

$cursoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cursoId) {
    setFlashMessage('Curso não encontrado!', 'error');
    redirect('/gestor/cursos.php');
}

$stmt = $pdo->prepare("
    SELECT c.*, u.nome as instrutor_nome, u.foto as instrutor_foto
    FROM cursos c
    LEFT JOIN usuarios u ON c.instrutor_id = u.id
    WHERE c.id = ? AND c.ativo = 1
");
$stmt->execute([$cursoId]);
$curso = $stmt->fetch();

if (!$curso) {
    setFlashMessage('Curso não encontrado!', 'error');
    redirect('/gestor/cursos.php');
}

$stmt = $pdo->prepare("
    SELECT a.*, m.data_matricula
    FROM alunos a
    INNER JOIN matriculas m ON a.id = m.aluno_id
    WHERE m.curso_id = ? AND a.ativo = 1
    ORDER BY a.nome
");
$stmt->execute([$cursoId]);
$alunos = $stmt->fetchAll();

$totalAulas = $pdo->prepare("SELECT COUNT(*) FROM aulas WHERE curso_id = ?");
$totalAulas->execute([$cursoId]);
$totalAulas = $totalAulas->fetchColumn();

$pageTitle = htmlspecialchars($curso['nome']) . ' - Detalhes';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <a href="javascript:history.back()" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Voltar
    </a>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-8 text-white">
        <div class="flex flex-col md:flex-row gap-6 items-start">
            <?php if ($curso['capa']): ?>
                <img src="/assets/uploads/<?= htmlspecialchars($curso['capa']) ?>" alt="Capa" class="w-48 h-60 object-cover rounded-lg shadow-lg">
            <?php else: ?>
                <div class="w-48 h-60 bg-white bg-opacity-20 rounded-lg shadow-lg flex items-center justify-center">
                    <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            <?php endif; ?>
            
            <div class="flex-1">
                <h1 class="text-4xl font-bold mb-2"><?= htmlspecialchars($curso['nome']) ?></h1>
                <p class="text-xl opacity-90 mb-4">Ano: <?= $curso['ano'] ?></p>
                
                <?php if ($curso['instrutor_nome']): ?>
                    <div class="flex items-center gap-3 bg-white bg-opacity-20 rounded-lg p-3 inline-flex">
                        <?php if ($curso['instrutor_foto']): ?>
                            <img src="/assets/uploads/<?= htmlspecialchars($curso['instrutor_foto']) ?>" alt="Instrutor" class="w-12 h-12 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-white bg-opacity-30 flex items-center justify-center text-white font-bold text-lg">
                                <?= strtoupper(substr($curso['instrutor_nome'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="text-sm opacity-75">Instrutor</p>
                            <p class="font-semibold"><?= htmlspecialchars($curso['instrutor_nome']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mt-6 grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <p class="text-sm opacity-75">Alunos Matriculados</p>
                        <p class="text-3xl font-bold"><?= count($alunos) ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <p class="text-sm opacity-75">Total de Aulas</p>
                        <p class="text-3xl font-bold"><?= $totalAulas ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <p class="text-sm opacity-75">Status</p>
                        <p class="text-lg font-bold">Ativo</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-gray-800">Alunos Matriculados</h2>
    </div>
    
    <?php if (empty($alunos)): ?>
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <p class="text-gray-500 text-lg">Nenhum aluno matriculado neste curso</p>
            <a href="/diretor/matriculas_kanban.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                Matricular Alunos
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
            <?php foreach ($alunos as $aluno): ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <?php if ($aluno['foto']): ?>
                            <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="w-16 h-16 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold text-xl">
                                <?= strtoupper(substr($aluno['nome'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 truncate"><?= htmlspecialchars($aluno['nome']) ?></h3>
                            <p class="text-sm text-gray-600">
                                <?= $aluno['data_nascimento'] ? calcularIdade($aluno['data_nascimento']) . ' anos' : 'Idade não informada' ?>
                            </p>
                            <p class="text-xs text-gray-500">Matriculado em: <?= formatarData($aluno['data_matricula']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
