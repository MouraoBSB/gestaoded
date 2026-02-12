<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:48:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['instrutor', 'gestor']);

$pdo = getConnection();
$instrutorId = getUserId();

$userType = getUserType();

if ($userType === 'gestor') {
    $cursos = $pdo->query("
        SELECT c.id, c.nome, c.ano, c.capa,
               COUNT(DISTINCT m.aluno_id) as total_alunos
        FROM cursos c
        LEFT JOIN matriculas m ON c.id = m.curso_id
        WHERE c.ativo = 1
        GROUP BY c.id
        ORDER BY c.ano DESC, c.nome
    ")->fetchAll();
} else {
    $cursos = $pdo->prepare("
        SELECT c.id, c.nome, c.ano, c.capa,
               COUNT(DISTINCT m.aluno_id) as total_alunos
        FROM cursos c
        LEFT JOIN matriculas m ON c.id = m.curso_id
        WHERE c.instrutor_id = ? AND c.ativo = 1
        GROUP BY c.id
        ORDER BY c.ano DESC, c.nome
    ");
    $cursos->execute([$instrutorId]);
    $cursos = $cursos->fetchAll();
}

$pageTitle = 'Nova Chamada';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Iniciar Nova Chamada</h1>
    <p class="text-gray-600 mt-2">Selecione o curso para registrar a presença dos alunos</p>
</div>

<?php if (empty($cursos)): ?>
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
        <p class="text-gray-500 text-lg mb-4">Você não possui cursos atribuídos</p>
        <p class="text-gray-400 text-sm">Entre em contato com o gestor para atribuir cursos a você</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($cursos as $curso): ?>
            <a href="/instrutor/registrar_chamada.php?curso_id=<?= $curso['id'] ?>" class="block bg-white rounded-lg shadow-md hover:shadow-xl transition overflow-hidden group">
                <?php if ($curso['capa']): ?>
                    <div class="h-48 overflow-hidden">
                        <img src="/assets/uploads/<?= htmlspecialchars($curso['capa']) ?>" alt="Capa" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                <?php else: ?>
                    <div class="h-48 bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center">
                        <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                <?php endif; ?>
                
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-purple-600 transition">
                        <?= htmlspecialchars($curso['nome']) ?>
                    </h3>
                    <p class="text-gray-600 mb-4">Ano: <?= $curso['ano'] ?></p>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="text-sm"><?= $curso['total_alunos'] ?> alunos</span>
                        </div>
                        
                        <div class="bg-purple-600 text-white px-4 py-2 rounded-lg group-hover:bg-purple-700 transition">
                            Iniciar Chamada
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
