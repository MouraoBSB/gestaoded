<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 20:05:00
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

requireRole(['gestor', 'diretor', 'instrutor']);

$pdo = getConnection();
$alunoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$alunoId) {
    setFlashMessage('Aluno não encontrado!', 'error');
    redirect('/gestor/alunos.php');
}

$stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
$stmt->execute([$alunoId]);
$aluno = $stmt->fetch();

if (!$aluno) {
    setFlashMessage('Aluno não encontrado!', 'error');
    redirect('/gestor/alunos.php');
}

$stmt = $pdo->prepare("
    SELECT c.id, c.nome, c.ano, c.capa, m.data_matricula,
           COUNT(DISTINCT a.id) as total_aulas,
           SUM(CASE WHEN p.presente = 1 THEN 1 ELSE 0 END) as total_presencas,
           CASE 
               WHEN COUNT(DISTINCT a.id) > 0 
               THEN ROUND((SUM(CASE WHEN p.presente = 1 THEN 1 ELSE 0 END) / COUNT(DISTINCT a.id)) * 100)
               ELSE 0
           END as frequencia,
           co.status as status_conclusao,
           co.observacoes as observacoes_conclusao
    FROM matriculas m
    INNER JOIN cursos c ON m.curso_id = c.id
    LEFT JOIN aulas a ON c.id = a.curso_id
    LEFT JOIN presencas p ON a.id = p.aula_id AND p.aluno_id = m.aluno_id
    LEFT JOIN conclusoes co ON co.aluno_id = m.aluno_id AND co.curso_id = c.id
    WHERE m.aluno_id = ? AND c.ativo = 1
    GROUP BY c.id, m.data_matricula, co.status, co.observacoes
    ORDER BY 
        CASE 
            WHEN co.status IS NULL THEN 0
            WHEN co.status = 'aprovado' THEN 1
            ELSE 2
        END,
        c.ano DESC, c.nome
");
$stmt->execute([$alunoId]);
$cursos = $stmt->fetchAll();

$cursosEmAndamento = array_filter($cursos, fn($c) => $c['status_conclusao'] === null);
$cursosAprovados = array_filter($cursos, fn($c) => $c['status_conclusao'] === 'aprovado');
$cursosReprovados = array_filter($cursos, fn($c) => $c['status_conclusao'] === 'reprovado');

$pageTitle = 'Perfil - ' . htmlspecialchars($aluno['nome']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-6">
    <a href="javascript:history.back()" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Voltar
    </a>
</div>

<div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-32"></div>
    <div class="px-8 pb-8">
        <div class="flex flex-col md:flex-row items-start md:items-end gap-6 -mt-16">
            <div class="relative">
                <?php if ($aluno['foto']): ?>
                    <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg">
                <?php else: ?>
                    <div class="w-32 h-32 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white font-bold text-5xl border-4 border-white shadow-lg">
                        <?= strtoupper(substr($aluno['nome'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="flex-1">
                <h1 class="text-4xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($aluno['nome']) ?></h1>
                <div class="flex flex-wrap gap-4 text-gray-600">
                    <?php if ($aluno['data_nascimento']): ?>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span><?= calcularIdade($aluno['data_nascimento']) ?> anos</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Nascimento: <?= formatarData($aluno['data_nascimento']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($aluno['endereco']): ?>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span><?= htmlspecialchars($aluno['endereco']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="flex gap-2">
                <?php if (getUserType() === 'gestor' || getUserType() === 'diretor'): ?>
                    <a href="/gestor/alunos.php?editar=<?= $aluno['id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        Editar Perfil
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Cursos em Andamento</p>
                <p class="text-3xl font-bold text-blue-600"><?= count($cursosEmAndamento) ?></p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Cursos Aprovados</p>
                <p class="text-3xl font-bold text-green-600"><?= count($cursosAprovados) ?></p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Cursos Reprovados</p>
                <p class="text-3xl font-bold text-red-600"><?= count($cursosReprovados) ?></p>
            </div>
            <div class="bg-red-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($cursosEmAndamento)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            Cursos em Andamento
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($cursosEmAndamento as $curso): ?>
                <div class="border border-blue-200 rounded-lg overflow-hidden hover:shadow-lg transition">
                    <?php if ($curso['capa']): ?>
                        <div class="h-32 overflow-hidden">
                            <img src="/assets/uploads/<?= htmlspecialchars($curso['capa']) ?>" alt="Capa" class="w-full h-full object-cover">
                        </div>
                    <?php else: ?>
                        <div class="h-32 bg-gradient-to-br from-blue-400 to-purple-500"></div>
                    <?php endif; ?>
                    <div class="p-4">
                        <h3 class="font-bold text-gray-900 mb-1"><?= htmlspecialchars($curso['nome']) ?></h3>
                        <p class="text-sm text-gray-600 mb-3">Ano: <?= $curso['ano'] ?></p>
                        
                        <div class="mb-3">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs text-gray-600">Frequência</span>
                                <span class="text-sm font-bold <?= $curso['frequencia'] >= 75 ? 'text-green-600' : ($curso['frequencia'] >= 50 ? 'text-yellow-600' : 'text-red-600') ?>">
                                    <?= $curso['frequencia'] ?>%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="<?= $curso['frequencia'] >= 75 ? 'bg-green-500' : ($curso['frequencia'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') ?> h-2 rounded-full" style="width: <?= $curso['frequencia'] ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                            <div class="bg-gray-50 p-2 rounded text-center">
                                <p class="text-gray-600">Aulas</p>
                                <p class="font-bold text-gray-900"><?= $curso['total_aulas'] ?></p>
                            </div>
                            <div class="bg-green-50 p-2 rounded text-center">
                                <p class="text-green-700">Presenças</p>
                                <p class="font-bold text-green-600"><?= $curso['total_presencas'] ?></p>
                            </div>
                        </div>
                        
                        <a href="/instrutor/perfil_aluno.php?aluno_id=<?= $aluno['id'] ?>&curso_id=<?= $curso['id'] ?>" class="block text-center bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm transition">
                            Ver Detalhes
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($cursosAprovados)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Cursos Concluídos (Aprovado)
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($cursosAprovados as $curso): ?>
                <div class="border border-green-200 rounded-lg overflow-hidden bg-green-50">
                    <?php if ($curso['capa']): ?>
                        <div class="h-32 overflow-hidden">
                            <img src="/assets/uploads/<?= htmlspecialchars($curso['capa']) ?>" alt="Capa" class="w-full h-full object-cover">
                        </div>
                    <?php else: ?>
                        <div class="h-32 bg-gradient-to-br from-green-400 to-emerald-500"></div>
                    <?php endif; ?>
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-bold text-green-800">APROVADO</span>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1"><?= htmlspecialchars($curso['nome']) ?></h3>
                        <p class="text-sm text-gray-600 mb-2">Ano: <?= $curso['ano'] ?></p>
                        
                        <?php if ($curso['observacoes_conclusao']): ?>
                            <p class="text-xs text-gray-700 bg-white p-2 rounded mb-3">
                                <?= htmlspecialchars($curso['observacoes_conclusao']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="text-xs text-gray-600">
                            <p>Frequência Final: <span class="font-bold text-green-600"><?= $curso['frequencia'] ?>%</span></p>
                            <p>Presenças: <?= $curso['total_presencas'] ?>/<?= $curso['total_aulas'] ?> aulas</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($cursosReprovados)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Cursos Não Concluídos (Reprovado)
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($cursosReprovados as $curso): ?>
                <div class="border border-red-200 rounded-lg overflow-hidden bg-red-50">
                    <?php if ($curso['capa']): ?>
                        <div class="h-32 overflow-hidden">
                            <img src="/assets/uploads/<?= htmlspecialchars($curso['capa']) ?>" alt="Capa" class="w-full h-full object-cover">
                        </div>
                    <?php else: ?>
                        <div class="h-32 bg-gradient-to-br from-red-400 to-rose-500"></div>
                    <?php endif; ?>
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-bold text-red-800">REPROVADO</span>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1"><?= htmlspecialchars($curso['nome']) ?></h3>
                        <p class="text-sm text-gray-600 mb-2">Ano: <?= $curso['ano'] ?></p>
                        
                        <?php if ($curso['observacoes_conclusao']): ?>
                            <p class="text-xs text-gray-700 bg-white p-2 rounded mb-3">
                                <?= htmlspecialchars($curso['observacoes_conclusao']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="text-xs text-gray-600">
                            <p>Frequência Final: <span class="font-bold text-red-600"><?= $curso['frequencia'] ?>%</span></p>
                            <p>Presenças: <?= $curso['total_presencas'] ?>/<?= $curso['total_aulas'] ?> aulas</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($cursos)): ?>
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
        <p class="text-gray-500 text-lg">Este aluno ainda não está matriculado em nenhum curso</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
