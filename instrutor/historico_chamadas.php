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
$cursoId = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;

if ($cursoId) {
    $userType = getUserType();
    
    if ($userType === 'gestor') {
        $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ? AND ativo = 1");
        $stmt->execute([$cursoId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ? AND instrutor_id = ? AND ativo = 1");
        $stmt->execute([$cursoId, $instrutorId]);
    }
    
    $curso = $stmt->fetch();
    
    if (!$curso) {
        setFlashMessage('Você não tem permissão para acessar este curso!', 'error');
        redirect('/instrutor/dashboard.php');
    }
    
    $stmt = $pdo->prepare("
        SELECT a.*, 
               COUNT(DISTINCT p.aluno_id) as total_alunos,
               SUM(CASE WHEN p.presente = 1 THEN 1 ELSE 0 END) as total_presentes,
               SUM(CASE WHEN p.presente = 0 THEN 1 ELSE 0 END) as total_faltas
        FROM aulas a
        LEFT JOIN presencas p ON a.id = p.aula_id
        WHERE a.curso_id = ?
        GROUP BY a.id
        ORDER BY a.data_aula DESC, a.criado_em DESC
    ");
    $stmt->execute([$cursoId]);
    $aulas = $stmt->fetchAll();
} else {
    $curso = null;
    $aulas = [];
}

$pageTitle = $curso ? 'Histórico de Chamadas - ' . htmlspecialchars($curso['nome']) : 'Histórico de Chamadas';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <a href="<?= $curso ? '/instrutor/nova_chamada.php' : '/instrutor/dashboard.php' ?>" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Voltar
    </a>
    
    <?php if ($curso): ?>
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Histórico de Chamadas</h1>
                <p class="text-gray-600 mt-2"><?= htmlspecialchars($curso['nome']) ?> - <?= $curso['ano'] ?></p>
            </div>
            <a href="/instrutor/registrar_chamada.php?curso_id=<?= $cursoId ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg transition font-semibold">
                + Nova Chamada
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($aulas)): ?>
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        <p class="text-gray-500 text-lg mb-4">Nenhuma chamada registrada ainda</p>
        <?php if ($curso): ?>
            <a href="/instrutor/registrar_chamada.php?curso_id=<?= $cursoId ?>" class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition">
                Registrar Primeira Chamada
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Presentes</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Faltas</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Frequência</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($aulas as $aula): ?>
                        <?php 
                        $frequencia = $aula['total_alunos'] > 0 ? round(($aula['total_presentes'] / $aula['total_alunos']) * 100) : 0;
                        $corFrequencia = $frequencia >= 75 ? 'text-green-600' : ($frequencia >= 50 ? 'text-yellow-600' : 'text-red-600');
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= formatarData($aula['data_aula']) ?></div>
                                <div class="text-xs text-gray-500"><?= date('H:i', strtotime($aula['criado_em'])) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?= $aula['descricao'] ? htmlspecialchars($aula['descricao']) : '<span class="text-gray-400 italic">Sem descrição</span>' ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <?= $aula['total_presentes'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    <?= $aula['total_faltas'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-medium text-gray-900"><?= $aula['total_alunos'] ?></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-lg font-bold <?= $corFrequencia ?>"><?= $frequencia ?>%</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <a href="/instrutor/detalhes_chamada.php?aula_id=<?= $aula['id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                                        Ver Detalhes
                                    </a>
                                    <a href="/instrutor/editar_chamada.php?aula_id=<?= $aula['id'] ?>" class="text-green-600 hover:text-green-800 font-medium">
                                        Editar
                                    </a>
                                    <button onclick="confirmarExcluir(<?= $aula['id'] ?>)" class="text-red-600 hover:text-red-800 font-medium">
                                        Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total de Aulas</p>
                    <p class="text-3xl font-bold text-gray-900"><?= count($aulas) ?></p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Média de Presença</p>
                    <p class="text-3xl font-bold text-green-600">
                        <?php
                        $totalPresentes = array_sum(array_column($aulas, 'total_presentes'));
                        $totalAlunos = array_sum(array_column($aulas, 'total_alunos'));
                        $mediaPresenca = $totalAlunos > 0 ? round(($totalPresentes / $totalAlunos) * 100) : 0;
                        echo $mediaPresenca;
                        ?>%
                    </p>
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
                    <p class="text-sm text-gray-600">Última Aula</p>
                    <p class="text-lg font-bold text-gray-900"><?= formatarData($aulas[0]['data_aula']) ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<form id="formExcluir" method="POST" action="/instrutor/excluir_chamada.php" class="hidden">
    <input type="hidden" name="aula_id" id="excluir_aula_id">
    <input type="hidden" name="curso_id" value="<?= $cursoId ?>">
</form>

<script>
function confirmarExcluir(aulaId) {
    if (confirm('Tem certeza que deseja excluir esta chamada?\n\nEsta ação não pode ser desfeita e todos os registros de presença serão perdidos.')) {
        document.getElementById('excluir_aula_id').value = aulaId;
        document.getElementById('formExcluir').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
