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
$instrutorId = getUserId();
$userType = getUserType();

// Buscar turmas ativas (instrutor ve todas para consulta)
$stmtTurmas = $pdo->query("
    SELECT t.id as turma_id, c.nome, t.ano
    FROM turmas t
    INNER JOIN cursos c ON t.curso_id = c.id
    WHERE t.status = 'ativa' AND c.ativo = 1
    ORDER BY t.ano DESC, c.nome
");
$cursos = $stmtTurmas->fetchAll();

$turmaSelecionada = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;

// Suporte legado: se veio curso_id, buscar turma ativa
if (!$turmaSelecionada && isset($_GET['curso_id'])) {
    $cursoId = (int)$_GET['curso_id'];
    $stmt = $pdo->prepare("SELECT id FROM turmas WHERE curso_id = ? AND status = 'ativa' ORDER BY ano DESC LIMIT 1");
    $stmt->execute([$cursoId]);
    $turmaSelecionada = (int)$stmt->fetchColumn();
}

$alunos = [];
if ($turmaSelecionada) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, a.foto,
               COUNT(DISTINCT au.id) as total_aulas,
               SUM(CASE WHEN p.presente IN (1,2) THEN 1 ELSE 0 END) as total_presencas,
               ROUND((SUM(CASE WHEN p.presente IN (1,2) THEN 1 ELSE 0 END) / COUNT(DISTINCT au.id)) * 100) as frequencia
        FROM alunos a
        INNER JOIN matriculas m ON a.id = m.aluno_id
        LEFT JOIN aulas au ON m.turma_id = au.turma_id
        LEFT JOIN presencas p ON au.id = p.aula_id AND a.id = p.aluno_id
        WHERE m.turma_id = ? AND a.ativo = 1
        GROUP BY a.id
        ORDER BY a.nome
    ");
    $stmt->execute([$turmaSelecionada]);
    $alunos = $stmt->fetchAll();
}

$pageTitle = 'Frequência dos Alunos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Frequência dos Alunos</h1>
    <p class="text-gray-600 mt-2">Visualize a frequência individual de cada aluno por curso</p>
</div>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">Selecione o Curso</label>
    <select onchange="window.location.href='/instrutor/frequencia_alunos.php?turma_id=' + this.value" class="w-full md:w-96 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500">
        <option value="">Selecione um curso...</option>
        <?php foreach ($cursos as $curso): ?>
            <option value="<?= $curso['turma_id'] ?>" <?= $turmaSelecionada == $curso['turma_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($curso['nome']) ?> (<?= $curso['ano'] ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

<?php if ($turmaSelecionada && empty($alunos)): ?>
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <p class="text-gray-500 text-lg">Nenhum aluno matriculado neste curso</p>
    </div>
<?php elseif ($turmaSelecionada): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($alunos as $aluno): ?>
            <?php
            $frequencia = $aluno['frequencia'] ?? 0;
            $corFrequencia = $frequencia >= 75 ? 'bg-green-500' : ($frequencia >= 50 ? 'bg-yellow-500' : 'bg-red-500');
            $statusTexto = $frequencia >= 75 ? 'Ótima' : ($frequencia >= 50 ? 'Regular' : 'Baixa');
            ?>
            <div class="block bg-white rounded-lg shadow-md hover:shadow-xl transition overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <?php if ($aluno['foto']): ?>
                            <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="w-16 h-16 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white font-bold text-xl">
                                <?= strtoupper(substr($aluno['nome'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-900 truncate"><?= htmlspecialchars($aluno['nome']) ?></h3>
                            <p class="text-sm text-gray-600">Clique para ver detalhes</p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Frequência</span>
                            <span class="text-2xl font-bold <?= $frequencia >= 75 ? 'text-green-600' : ($frequencia >= 50 ? 'text-yellow-600' : 'text-red-600') ?>">
                                <?= $frequencia ?>%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="<?= $corFrequencia ?> h-3 rounded-full transition-all" style="width: <?= $frequencia ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1"><?= $statusTexto ?> frequência</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-center mb-4">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-600">Aulas</p>
                            <p class="text-lg font-bold text-gray-900"><?= $aluno['total_aulas'] ?></p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3">
                            <p class="text-xs text-green-700">Presenças</p>
                            <p class="text-lg font-bold text-green-600"><?= $aluno['total_presencas'] ?></p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <a href="/aluno_perfil.php?id=<?= $aluno['id'] ?>" class="bg-purple-600 hover:bg-purple-700 text-white text-center py-2 rounded-lg text-sm transition">
                            Ver Perfil
                        </a>
                        <a href="/instrutor/perfil_aluno.php?aluno_id=<?= $aluno['id'] ?>&turma_id=<?= $turmaSelecionada ?>" class="bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-lg text-sm transition">
                            Frequência
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
