<?php
/**
 * Autor: Thiago Mourao
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:48:00
 *
 * Selecionar turma para iniciar chamada
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['instrutor', 'gestor']);

$pdo = getConnection();
$instrutorId = getUserId();
$userType = getUserType();

if ($userType === 'gestor') {
    $turmas = $pdo->query("
        SELECT t.id, t.ano, t.semestre, t.data_inicio, t.data_fim, t.vagas,
               c.nome as curso_nome, c.capa as curso_capa, c.tipo_periodo,
               COUNT(DISTINCT m.aluno_id) as total_alunos
        FROM turmas t
        INNER JOIN cursos c ON t.curso_id = c.id
        LEFT JOIN matriculas m ON t.id = m.turma_id
        WHERE t.status = 'ativa' AND c.ativo = 1
        GROUP BY t.id
        ORDER BY c.nome, t.ano DESC
    ")->fetchAll();
} else {
    $turmas = $pdo->prepare("
        SELECT t.id, t.ano, t.semestre, t.data_inicio, t.data_fim, t.vagas,
               c.nome as curso_nome, c.capa as curso_capa, c.tipo_periodo,
               COUNT(DISTINCT m.aluno_id) as total_alunos
        FROM turmas t
        INNER JOIN cursos c ON t.curso_id = c.id
        INNER JOIN turma_instrutores ti ON t.id = ti.turma_id
        LEFT JOIN matriculas m ON t.id = m.turma_id
        WHERE ti.instrutor_id = ? AND t.status = 'ativa' AND c.ativo = 1
        GROUP BY t.id
        ORDER BY c.nome, t.ano DESC
    ");
    $turmas->execute([$instrutorId]);
    $turmas = $turmas->fetchAll();
}

$pageTitle = 'Nova Chamada';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Iniciar Nova Chamada</h1>
    <p class="text-gray-600 mt-2">Selecione a turma para registrar a presença dos alunos</p>
</div>

<?php if (empty($turmas)): ?>
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
        <p class="text-gray-500 text-lg mb-4">Você não possui turmas atribuídas</p>
        <p class="text-gray-400 text-sm">Entre em contato com o gestor para atribuir turmas a você</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($turmas as $turma):
            $periodo = $turma['ano'];
            if ($turma['semestre']) {
                $periodo .= '/' . $turma['semestre'] . 'º Sem';
            }
        ?>
            <a href="/instrutor/registrar_chamada.php?turma_id=<?= $turma['id'] ?>" class="block bg-white rounded-lg shadow-md hover:shadow-xl transition overflow-hidden group">
                <?php if ($turma['curso_capa']): ?>
                    <div class="h-48 overflow-hidden">
                        <img src="/assets/uploads/<?= htmlspecialchars($turma['curso_capa']) ?>" alt="Capa" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                <?php else: ?>
                    <div class="h-48 bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center">
                        <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                <?php endif; ?>

                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-1 group-hover:text-purple-600 transition">
                        <?= htmlspecialchars($turma['curso_nome']) ?>
                    </h3>
                    <p class="text-gray-600 mb-1"><?= $periodo ?></p>
                    <?php if ($turma['data_inicio']): ?>
                        <p class="text-xs text-gray-500 mb-4">
                            <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?>
                            <?= $turma['data_fim'] ? ' - ' . date('d/m/Y', strtotime($turma['data_fim'])) : '' ?>
                        </p>
                    <?php else: ?>
                        <p class="mb-4"></p>
                    <?php endif; ?>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="text-sm"><?= $turma['total_alunos'] ?> alunos</span>
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
