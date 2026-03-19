<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 19:07:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['gestor', 'diretor']);

$pdo = getConnection();
$alunoId = isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : 0;
$cursoId = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;

if (!$alunoId || !$cursoId) {
    setFlashMessage('Aluno ou curso não encontrado!', 'error');
    redirect('/gestor/frequencia_alunos.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'aprovar' || $acao === 'reprovar') {
        $status = $acao === 'aprovar' ? 'aprovado' : 'reprovado';
        $observacoes = sanitize($_POST['observacoes'] ?? '');
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO conclusoes (aluno_id, curso_id, status, observacoes)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), observacoes = VALUES(observacoes)
            ");
            $stmt->execute([$alunoId, $cursoId, $status, $observacoes]);
            
            $mensagem = $status === 'aprovado' ? 'Aluno aprovado com sucesso!' : 'Aluno reprovado!';
            $tipo = $status === 'aprovado' ? 'success' : 'error';
            setFlashMessage($mensagem, $tipo);
            
        } catch (Exception $e) {
            setFlashMessage('Erro ao registrar conclusão: ' . $e->getMessage(), 'error');
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ? AND ativo = 1");
$stmt->execute([$alunoId]);
$aluno = $stmt->fetch();

if (!$aluno) {
    setFlashMessage('Aluno não encontrado!', 'error');
    redirect('/gestor/frequencia_alunos.php');
}

$stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ? AND ativo = 1");
$stmt->execute([$cursoId]);
$curso = $stmt->fetch();

if (!$curso) {
    setFlashMessage('Curso não encontrado!', 'error');
    redirect('/gestor/frequencia_alunos.php');
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
$totalPresencas = count(array_filter($aulas, fn($a) => in_array($a['presente'], [1, 2])));
$totalFaltas = $totalAulas - $totalPresencas;
$frequencia = $totalAulas > 0 ? round(($totalPresencas / $totalAulas) * 100) : 0;

$stmt = $pdo->prepare("SELECT * FROM conclusoes WHERE aluno_id = ? AND curso_id = ?");
$stmt->execute([$alunoId, $cursoId]);
$conclusao = $stmt->fetch();

$pageTitle = 'Perfil do Aluno - ' . htmlspecialchars($aluno['nome']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <a href="/gestor/frequencia_alunos.php?curso_id=<?= $cursoId ?>" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4">
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
                        <div class="border border-gray-200 rounded-lg p-4 <?= $aula['presente'] == 1 ? 'bg-green-50 border-green-200' : ($aula['presente'] == 2 ? 'bg-yellow-50 border-yellow-200' : 'bg-red-50 border-red-200') ?>">
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
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 sticky top-4">
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
            
            <div class="space-y-3 mb-6">
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
            
            <?php if ($conclusao): ?>
                <div class="mb-6 p-4 rounded-lg <?= $conclusao['status'] === 'aprovado' ? 'bg-green-100 border-2 border-green-500' : 'bg-red-100 border-2 border-red-500' ?>">
                    <div class="flex items-center gap-2 mb-2">
                        <?php if ($conclusao['status'] === 'aprovado'): ?>
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-bold text-green-800">APROVADO</span>
                        <?php else: ?>
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-bold text-red-800">REPROVADO</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($conclusao['observacoes']): ?>
                        <p class="text-sm <?= $conclusao['status'] === 'aprovado' ? 'text-green-700' : 'text-red-700' ?>">
                            <?= htmlspecialchars($conclusao['observacoes']) ?>
                        </p>
                    <?php endif; ?>
                    <?php if (isset($conclusao['criado_em']) && $conclusao['criado_em']): ?>
                        <p class="text-xs text-gray-600 mt-2">
                            Registrado em: <?= formatarData($conclusao['criado_em']) ?>
                        </p>
                    <?php elseif (isset($conclusao['registrado_em']) && $conclusao['registrado_em']): ?>
                        <p class="text-xs text-gray-600 mt-2">
                            Registrado em: <?= formatarData($conclusao['registrado_em']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="border-t pt-4">
                <h4 class="font-bold text-gray-800 mb-3">Conclusão do Curso</h4>
                
                <form method="POST" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observações (opcional)</label>
                        <textarea name="observacoes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Adicione comentários sobre o desempenho do aluno..."><?= $conclusao ? htmlspecialchars($conclusao['observacoes']) : '' ?></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <button type="submit" name="acao" value="aprovar" onclick="return confirm('Confirma a APROVAÇÃO deste aluno?')" class="bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-semibold transition">
                            ✓ Aprovar
                        </button>
                        <button type="submit" name="acao" value="reprovar" onclick="return confirm('Confirma a REPROVAÇÃO deste aluno?')" class="bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg font-semibold transition">
                            ✗ Reprovar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
