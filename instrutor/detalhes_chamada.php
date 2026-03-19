<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 19:03:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['instrutor', 'gestor', 'diretor']);

$pdo = getConnection();
$aulaId = isset($_GET['aula_id']) ? (int)$_GET['aula_id'] : 0;

if (!$aulaId) {
    setFlashMessage('Aula não encontrada!', 'error');
    redirect('/instrutor/nova_chamada.php');
}

$stmt = $pdo->prepare("
    SELECT a.*, c.nome as curso_nome, c.ano as curso_ano, c.id as curso_id, a.turma_id
    FROM aulas a
    INNER JOIN cursos c ON a.curso_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$aulaId]);
$aula = $stmt->fetch();

if (!$aula) {
    setFlashMessage('Aula não encontrada!', 'error');
    redirect('/instrutor/nova_chamada.php');
}

$stmt = $pdo->prepare("
    SELECT a.*, p.presente, p.observacao, p.id as presenca_id
    FROM alunos a
    INNER JOIN presencas p ON a.id = p.aluno_id
    WHERE p.aula_id = ?
    ORDER BY a.nome
");
$stmt->execute([$aulaId]);
$alunos = $stmt->fetchAll();

$totalAlunos = count($alunos);
$totalPresentes = count(array_filter($alunos, fn($a) => $a['presente'] == 1));
$totalJustificadas = count(array_filter($alunos, fn($a) => $a['presente'] == 2));
$totalFaltas = $totalAlunos - $totalPresentes - $totalJustificadas;
$frequencia = $totalAlunos > 0 ? round((($totalPresentes + $totalJustificadas) / $totalAlunos) * 100) : 0;

$pageTitle = 'Detalhes da Chamada - ' . htmlspecialchars($aula['curso_nome']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <a href="/instrutor/historico_chamadas.php?<?= $aula['turma_id'] ? 'turma_id=' . $aula['turma_id'] : 'curso_id=' . $aula['curso_id'] ?>" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Voltar ao Histórico
    </a>
</div>

<div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg shadow-lg p-8 text-white mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($aula['curso_nome']) ?></h1>
            <p class="text-xl opacity-90">Aula de <?= formatarData($aula['data_aula']) ?></p>
            <?php if ($aula['descricao']): ?>
                <p class="mt-2 opacity-90"><?= htmlspecialchars($aula['descricao']) ?></p>
            <?php endif; ?>
        </div>
        <div class="flex gap-3">
            <a href="/instrutor/editar_chamada.php?aula_id=<?= $aulaId ?>" class="bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                ✏️ Editar Chamada
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total de Alunos</p>
                <p class="text-3xl font-bold text-gray-900"><?= $totalAlunos ?></p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Presentes</p>
                <p class="text-3xl font-bold text-green-600"><?= $totalPresentes ?></p>
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
                <p class="text-sm text-gray-600">Faltas</p>
                <p class="text-3xl font-bold text-red-600"><?= $totalFaltas ?></p>
            </div>
            <div class="bg-red-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Frequência</p>
                <p class="text-3xl font-bold <?= $frequencia >= 75 ? 'text-green-600' : ($frequencia >= 50 ? 'text-yellow-600' : 'text-red-600') ?>">
                    <?= $frequencia ?>%
                </p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h2 class="text-xl font-bold text-gray-800">Lista de Presença</h2>
    </div>
    
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($alunos as $index => $aluno): ?>
                <div class="border border-gray-200 rounded-lg p-4 <?= $aluno['presente'] == 1 ? 'bg-green-50 border-green-200' : ($aluno['presente'] == 2 ? 'bg-yellow-50 border-yellow-200' : 'bg-red-50 border-red-200') ?>">
                    <div class="flex items-center gap-3">
                        <span class="text-gray-500 font-semibold text-lg w-8"><?= $index + 1 ?>.</span>
                        
                        <?php if ($aluno['foto']): ?>
                            <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="w-12 h-12 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold">
                                <?= strtoupper(substr($aluno['nome'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 truncate"><?= htmlspecialchars($aluno['nome']) ?></h3>
                            <?php if ($aluno['data_nascimento']): ?>
                                <p class="text-sm text-gray-600"><?= calcularIdade($aluno['data_nascimento']) ?> anos</p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <?php if ($aluno['presente'] == 1): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-600 text-white">
                                    Presente
                                </span>
                            <?php elseif ($aluno['presente'] == 2): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-500 text-white">
                                    Justificada
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-600 text-white">
                                    Falta
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($aluno['observacao'])): ?>
                        <div class="mt-2 ml-11 flex items-start gap-2 text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                            <span><?= htmlspecialchars($aluno['observacao']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="mt-6 bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
    <p><strong>Registrado em:</strong> <?= date('d/m/Y H:i', strtotime($aula['criado_em'])) ?></p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
