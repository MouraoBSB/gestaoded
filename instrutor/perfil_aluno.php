<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 20:00:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['instrutor', 'gestor', 'diretor']);

$pdo = getConnection();
$alunoId = isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : 0;
$cursoId = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;

if (!$alunoId || !$cursoId) {
    setFlashMessage('Aluno ou curso não encontrado!', 'error');
    redirect('/instrutor/frequencia_alunos.php');
}

$stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ? AND ativo = 1");
$stmt->execute([$alunoId]);
$aluno = $stmt->fetch();

if (!$aluno) {
    setFlashMessage('Aluno não encontrado!', 'error');
    redirect('/instrutor/frequencia_alunos.php');
}

$stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ? AND ativo = 1");
$stmt->execute([$cursoId]);
$curso = $stmt->fetch();

if (!$curso) {
    setFlashMessage('Curso não encontrado!', 'error');
    redirect('/instrutor/frequencia_alunos.php');
}

$stmt = $pdo->prepare("
    SELECT au.*, 
           p.presente,
           p.id as presenca_id
    FROM aulas au
    LEFT JOIN presencas p ON au.id = p.aula_id AND p.aluno_id = ?
    WHERE au.curso_id = ?
    ORDER BY au.data_aula DESC
");
$stmt->execute([$alunoId, $cursoId]);
$aulas = $stmt->fetchAll();

$totalAulas = count($aulas);
$totalPresencas = count(array_filter($aulas, fn($a) => $a['presente'] == 1));
$totalFaltas = $totalAulas - $totalPresencas;
$frequencia = $totalAulas > 0 ? round(($totalPresencas / $totalAulas) * 100) : 0;

$pageTitle = 'Perfil do Aluno - ' . htmlspecialchars($aluno['nome']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <a href="/instrutor/frequencia_alunos.php?curso_id=<?= $cursoId ?>" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Voltar
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-start gap-6">
                <?php if ($aluno['foto']): ?>
                    <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="w-32 h-32 rounded-full object-cover">
                <?php else: ?>
                    <div class="w-32 h-32 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white font-bold text-5xl">
                        <?= strtoupper(substr($aluno['nome'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($aluno['nome']) ?></h1>
                    <p class="text-gray-600 mb-2">
                        <?= $aluno['data_nascimento'] ? calcularIdade($aluno['data_nascimento']) . ' anos' : 'Idade não informada' ?>
                    </p>
                    <?php if ($aluno['endereco']): ?>
                        <p class="text-gray-600"><?= htmlspecialchars($aluno['endereco']) ?></p>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <span class="inline-block bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">
                            <?= htmlspecialchars($curso['nome']) ?> - <?= $curso['ano'] ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Histórico de Presenças</h2>
            
            <?php if (empty($aulas)): ?>
                <p class="text-gray-500 text-center py-8">Nenhuma aula registrada ainda</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($aulas as $aula): ?>
                        <div class="border border-gray-200 rounded-lg p-4 <?= $aula['presente'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' ?>">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900"><?= formatarData($aula['data_aula']) ?></p>
                                    <?php if ($aula['descricao']): ?>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($aula['descricao']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($aula['presente']): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-600 text-white">
                                            ✓ Presente
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-600 text-white">
                                            ✗ Falta
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Estatísticas</h3>
            
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700">Frequência</span>
                    <span class="text-3xl font-bold <?= $frequencia >= 75 ? 'text-green-600' : ($frequencia >= 50 ? 'text-yellow-600' : 'text-red-600') ?>">
                        <?= $frequencia ?>%
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="<?= $frequencia >= 75 ? 'bg-green-500' : ($frequencia >= 50 ? 'bg-yellow-500' : 'bg-red-500') ?> h-4 rounded-full transition-all" style="width: <?= $frequencia ?>%"></div>
                </div>
            </div>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Total de Aulas</span>
                    <span class="text-lg font-bold text-gray-900"><?= $totalAulas ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                    <span class="text-sm text-green-700">Presenças</span>
                    <span class="text-lg font-bold text-green-600"><?= $totalPresencas ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                    <span class="text-sm text-red-700">Faltas</span>
                    <span class="text-lg font-bold text-red-600"><?= $totalFaltas ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
