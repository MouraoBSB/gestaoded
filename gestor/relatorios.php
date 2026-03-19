<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['gestor', 'diretor']);

$pdo = getConnection();

// Filtros
$turmaFiltro = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
$anoFiltro = isset($_GET['ano']) ? (int)$_GET['ano'] : 0;
$abaAtiva = $_GET['aba'] ?? 'turmas';

// Dados para filtros
$anos = $pdo->query("SELECT DISTINCT ano FROM turmas ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);

$turmasParaFiltro = $pdo->query("
    SELECT t.id, c.nome as curso_nome, t.ano, t.semestre, t.status
    FROM turmas t
    INNER JOIN cursos c ON t.curso_id = c.id
    ORDER BY t.ano DESC, c.nome
")->fetchAll();

// === ABA TURMAS ===
$sqlTurmas = "
    SELECT t.id as turma_id, t.ano, t.semestre, t.status,
           c.id as curso_id, c.nome as curso_nome,
           COUNT(DISTINCT m.aluno_id) as total_alunos,
           COUNT(DISTINCT au.id) as total_aulas,
           COUNT(DISTINCT CASE WHEN co.status = 'aprovado' THEN co.id END) as total_aprovados,
           COUNT(DISTINCT CASE WHEN co.status = 'reprovado' THEN co.id END) as total_reprovados,
           GROUP_CONCAT(DISTINCT u.nome ORDER BY u.nome SEPARATOR ', ') as instrutores,
           ROUND(AVG(CASE WHEN p.presente IS NOT NULL THEN
               CASE WHEN p.presente IN (1,2) THEN 100 ELSE 0 END
           END), 0) as media_frequencia
    FROM turmas t
    INNER JOIN cursos c ON t.curso_id = c.id
    LEFT JOIN matriculas m ON m.turma_id = t.id
    LEFT JOIN aulas au ON (au.turma_id = t.id OR (au.turma_id IS NULL AND au.curso_id = t.curso_id))
    LEFT JOIN presencas p ON au.id = p.aula_id
    LEFT JOIN conclusoes co ON co.curso_id = c.id AND co.aluno_id = m.aluno_id
    LEFT JOIN turma_instrutores ti ON t.id = ti.turma_id
    LEFT JOIN usuarios u ON ti.instrutor_id = u.id
    WHERE 1=1
";
$paramsTurmas = [];

if ($anoFiltro) {
    $sqlTurmas .= " AND t.ano = ?";
    $paramsTurmas[] = $anoFiltro;
}
if ($turmaFiltro) {
    $sqlTurmas .= " AND t.id = ?";
    $paramsTurmas[] = $turmaFiltro;
}

$sqlTurmas .= " GROUP BY t.id ORDER BY t.ano DESC, c.nome";
$stmtTurmas = $pdo->prepare($sqlTurmas);
$stmtTurmas->execute($paramsTurmas);
$relatorioTurmas = $stmtTurmas->fetchAll();

// === ABA ALUNOS (quando turma selecionada) ===
$alunosTurma = [];
if ($turmaFiltro) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, a.foto,
               c.nome as curso_nome, t.ano,
               COUNT(DISTINCT au.id) as total_aulas,
               SUM(CASE WHEN p.presente IN (1,2) THEN 1 ELSE 0 END) as total_presencas,
               CASE WHEN COUNT(DISTINCT au.id) > 0
                   THEN ROUND((SUM(CASE WHEN p.presente IN (1,2) THEN 1 ELSE 0 END) / COUNT(DISTINCT au.id)) * 100)
                   ELSE 0 END as frequencia,
               co.status as status_conclusao
        FROM alunos a
        INNER JOIN matriculas m ON a.id = m.aluno_id
        INNER JOIN turmas t ON m.turma_id = t.id
        INNER JOIN cursos c ON t.curso_id = c.id
        LEFT JOIN aulas au ON (au.turma_id = t.id OR (au.turma_id IS NULL AND au.curso_id = t.curso_id))
        LEFT JOIN presencas p ON au.id = p.aula_id AND a.id = p.aluno_id
        LEFT JOIN conclusoes co ON co.aluno_id = a.id AND co.curso_id = c.id
        WHERE t.id = ? AND a.ativo = 1
        GROUP BY a.id, co.status
        ORDER BY a.nome
    ");
    $stmt->execute([$turmaFiltro]);
    $alunosTurma = $stmt->fetchAll();
}

// === RESUMO GERAL ===
$resumo = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM turmas WHERE status = 'ativa') as turmas_ativas,
        (SELECT COUNT(*) FROM turmas WHERE status != 'ativa') as turmas_finalizadas,
        (SELECT COUNT(DISTINCT m.aluno_id) FROM matriculas m INNER JOIN turmas t ON m.turma_id = t.id WHERE t.status = 'ativa') as alunos_ativos,
        (SELECT COUNT(*) FROM aulas) as total_aulas,
        (SELECT COUNT(*) FROM conclusoes WHERE status = 'aprovado') as total_aprovados,
        (SELECT COUNT(*) FROM conclusoes WHERE status = 'reprovado') as total_reprovados
")->fetch();

// Conclusões por ano
$conclusoesPorAno = $pdo->query("
    SELECT
        t.ano,
        COUNT(DISTINCT co.id) as total,
        SUM(CASE WHEN co.status = 'aprovado' THEN 1 ELSE 0 END) as aprovados,
        SUM(CASE WHEN co.status = 'reprovado' THEN 1 ELSE 0 END) as reprovados
    FROM conclusoes co
    INNER JOIN cursos c ON co.curso_id = c.id
    INNER JOIN turmas t ON t.curso_id = c.id
    GROUP BY t.ano
    ORDER BY t.ano DESC
")->fetchAll();

$pageTitle = 'Relatórios';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Relatórios</h1>
    <p class="text-gray-600 mt-2">Visão geral de turmas, alunos e desempenho</p>
</div>

<!-- Cards Resumo -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-md p-4 text-center">
        <p class="text-2xl font-bold text-green-600"><?= $resumo['turmas_ativas'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Turmas Ativas</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-4 text-center">
        <p class="text-2xl font-bold text-gray-500"><?= $resumo['turmas_finalizadas'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Turmas Finalizadas</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-4 text-center">
        <p class="text-2xl font-bold text-blue-600"><?= $resumo['alunos_ativos'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Alunos Ativos</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-4 text-center">
        <p class="text-2xl font-bold text-purple-600"><?= $resumo['total_aulas'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Total de Aulas</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-4 text-center">
        <p class="text-2xl font-bold text-green-600"><?= $resumo['total_aprovados'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Aprovados</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-4 text-center">
        <p class="text-2xl font-bold text-red-600"><?= $resumo['total_reprovados'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Reprovados</p>
    </div>
</div>

<!-- Busca de Aluno -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-bold text-gray-800 mb-3">Buscar Aluno</h2>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input type="text" id="buscaAluno" placeholder="Digite o nome do aluno..."
               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
               autocomplete="off">
        <div id="resultadosBusca" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 hidden max-h-96 overflow-y-auto"></div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-bold text-gray-800 mb-3">Filtros</h2>
    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <input type="hidden" name="aba" value="<?= htmlspecialchars($abaAtiva) ?>">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Turma</label>
            <select name="turma_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                <option value="">Todas as turmas</option>
                <?php foreach ($turmasParaFiltro as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $turmaFiltro == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['curso_nome']) ?> - <?= $t['ano'] ?><?= $t['semestre'] ? '/' . $t['semestre'] : '' ?>
                        (<?= $t['status'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full md:w-40">
            <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
            <select name="ano" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                <option value="">Todos</option>
                <?php foreach ($anos as $ano): ?>
                    <option value="<?= $ano ?>" <?= $anoFiltro == $ano ? 'selected' : '' ?>><?= $ano ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition font-semibold">
                Filtrar
            </button>
            <?php if ($turmaFiltro || $anoFiltro): ?>
                <a href="/gestor/relatorios.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition font-semibold">
                    Limpar
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Abas -->
<div class="mb-6">
    <div class="border-b border-gray-200">
        <nav class="flex gap-1">
            <a href="?aba=turmas<?= $turmaFiltro ? '&turma_id=' . $turmaFiltro : '' ?><?= $anoFiltro ? '&ano=' . $anoFiltro : '' ?>"
               class="px-5 py-3 text-sm font-semibold rounded-t-lg transition <?= $abaAtiva === 'turmas' ? 'bg-white text-purple-700 border border-b-white border-gray-200 -mb-px' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' ?>">
                Relatório por Turma
            </a>
            <?php if ($turmaFiltro): ?>
                <a href="?aba=alunos&turma_id=<?= $turmaFiltro ?><?= $anoFiltro ? '&ano=' . $anoFiltro : '' ?>"
                   class="px-5 py-3 text-sm font-semibold rounded-t-lg transition <?= $abaAtiva === 'alunos' ? 'bg-white text-purple-700 border border-b-white border-gray-200 -mb-px' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' ?>">
                    Alunos da Turma (<?= count($alunosTurma) ?>)
                </a>
            <?php endif; ?>
            <a href="?aba=conclusoes<?= $anoFiltro ? '&ano=' . $anoFiltro : '' ?>"
               class="px-5 py-3 text-sm font-semibold rounded-t-lg transition <?= $abaAtiva === 'conclusoes' ? 'bg-white text-purple-700 border border-b-white border-gray-200 -mb-px' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' ?>">
                Conclusões por Ano
            </a>
        </nav>
    </div>
</div>

<?php if ($abaAtiva === 'turmas'): ?>
    <!-- RELATÓRIO POR TURMA -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Curso / Turma</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ano</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Instrutor(es)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Alunos</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aulas</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Freq. Média</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aprovados</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Reprovados</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($relatorioTurmas)): ?>
                        <tr><td colspan="10" class="px-6 py-8 text-center text-gray-500">Nenhuma turma encontrada</td></tr>
                    <?php else: ?>
                        <?php foreach ($relatorioTurmas as $r): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($r['curso_nome']) ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <?= $r['ano'] ?><?= $r['semestre'] ? '/' . $r['semestre'] : '' ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        <?= $r['status'] === 'ativa' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                                        <?= ucfirst($r['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 max-w-xs">
                                    <?= $r['instrutores'] ? htmlspecialchars($r['instrutores']) : '<span class="text-gray-400 italic">Não atribuído</span>' ?>
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-semibold text-gray-900"><?= $r['total_alunos'] ?></td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600"><?= $r['total_aulas'] ?></td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                    $freq = $r['media_frequencia'] ?? 0;
                                    $corFreq = $freq >= 75 ? 'text-green-600' : ($freq >= 50 ? 'text-yellow-600' : 'text-red-600');
                                    ?>
                                    <span class="text-sm font-bold <?= $corFreq ?>"><?= $freq ?>%</span>
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-semibold text-green-600"><?= $r['total_aprovados'] ?></td>
                                <td class="px-4 py-3 text-center text-sm font-semibold text-red-600"><?= $r['total_reprovados'] ?></td>
                                <td class="px-4 py-3 text-center">
                                    <a href="?aba=alunos&turma_id=<?= $r['turma_id'] ?>" class="text-purple-600 hover:text-purple-800 text-sm font-semibold">
                                        Ver Alunos
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($abaAtiva === 'alunos' && $turmaFiltro): ?>
    <!-- ALUNOS DA TURMA -->
    <?php
    // Buscar info da turma
    $stmtTurma = $pdo->prepare("
        SELECT t.*, c.nome as curso_nome,
               GROUP_CONCAT(DISTINCT u.nome ORDER BY u.nome SEPARATOR ', ') as instrutores
        FROM turmas t
        INNER JOIN cursos c ON t.curso_id = c.id
        LEFT JOIN turma_instrutores ti ON t.id = ti.turma_id
        LEFT JOIN usuarios u ON ti.instrutor_id = u.id
        WHERE t.id = ?
        GROUP BY t.id
    ");
    $stmtTurma->execute([$turmaFiltro]);
    $turmaInfo = $stmtTurma->fetch();
    ?>

    <?php if ($turmaInfo): ?>
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($turmaInfo['curso_nome']) ?></h3>
                    <p class="text-sm text-gray-500">
                        Ano: <?= $turmaInfo['ano'] ?><?= $turmaInfo['semestre'] ? '/' . $turmaInfo['semestre'] : '' ?>
                        | Status: <span class="font-semibold"><?= ucfirst($turmaInfo['status']) ?></span>
                        | Instrutor(es): <span class="font-semibold"><?= $turmaInfo['instrutores'] ?: 'Não atribuído' ?></span>
                    </p>
                </div>
                <span class="px-3 py-1 text-sm font-semibold rounded-full
                    <?= $turmaInfo['status'] === 'ativa' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                    <?= count($alunosTurma) ?> aluno(s)
                </span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($alunosTurma)): ?>
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <p class="text-gray-500 text-lg">Nenhum aluno matriculado nesta turma</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aluno</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aulas</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Presenças</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Frequência</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($alunosTurma as $aluno): ?>
                            <?php
                            $freq = $aluno['frequencia'] ?? 0;
                            $corFreq = $freq >= 75 ? 'text-green-600' : ($freq >= 50 ? 'text-yellow-600' : 'text-red-600');
                            $barCor = $freq >= 75 ? 'bg-green-500' : ($freq >= 50 ? 'bg-yellow-500' : 'bg-red-500');
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <?php if ($aluno['foto']): ?>
                                            <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" class="w-8 h-8 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white text-xs font-bold">
                                                <?= strtoupper(mb_substr($aluno['nome'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($aluno['nome']) ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600"><?= $aluno['total_aulas'] ?></td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600"><?= $aluno['total_presencas'] ?></td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                            <div class="<?= $barCor ?> h-2 rounded-full" style="width: <?= $freq ?>%"></div>
                                        </div>
                                        <span class="text-sm font-bold <?= $corFreq ?>"><?= $freq ?>%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($aluno['status_conclusao'] === 'aprovado'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aprovado</span>
                                    <?php elseif ($aluno['status_conclusao'] === 'reprovado'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Reprovado</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Em andamento</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="/aluno_perfil.php?id=<?= $aluno['id'] ?>" class="text-purple-600 hover:text-purple-800 text-sm font-semibold">
                                        Ver Perfil
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php elseif ($abaAtiva === 'conclusoes'): ?>
    <!-- CONCLUSÕES POR ANO -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ano</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aprovados</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Reprovados</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Taxa de Aprovação</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($conclusoesPorAno)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Nenhuma conclusão registrada</td></tr>
                    <?php else: ?>
                        <?php foreach ($conclusoesPorAno as $c):
                            $taxa = $c['total'] > 0 ? round(($c['aprovados'] / $c['total']) * 100) : 0;
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-900"><?= $c['ano'] ?></td>
                                <td class="px-6 py-4 text-center text-gray-600"><?= $c['total'] ?></td>
                                <td class="px-6 py-4 text-center text-green-600 font-semibold"><?= $c['aprovados'] ?></td>
                                <td class="px-6 py-4 text-center text-red-600 font-semibold"><?= $c['reprovados'] ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-green-600 h-2.5 rounded-full" style="width: <?= $taxa ?>%"></div>
                                        </div>
                                        <span class="text-sm font-semibold"><?= $taxa ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
const inputBusca = document.getElementById('buscaAluno');
const resultadosBusca = document.getElementById('resultadosBusca');
let timeoutBusca;

inputBusca.addEventListener('input', function() {
    clearTimeout(timeoutBusca);
    const termo = this.value.trim();

    if (termo.length < 2) {
        resultadosBusca.classList.add('hidden');
        return;
    }

    timeoutBusca = setTimeout(() => {
        fetch(`/gestor/api_buscar_aluno.php?termo=${encodeURIComponent(termo)}`)
            .then(r => r.json())
            .then(alunos => {
                if (alunos.length === 0) {
                    resultadosBusca.innerHTML = '<div class="p-4 text-gray-500 text-center">Nenhum aluno encontrado</div>';
                    resultadosBusca.classList.remove('hidden');
                    return;
                }

                let html = '<div class="divide-y divide-gray-200">';
                alunos.forEach(a => {
                    const foto = a.foto
                        ? `<img src="/assets/uploads/${a.foto}" class="h-10 w-10 rounded-full object-cover">`
                        : `<div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center">
                             <span class="text-white text-sm font-bold">${a.nome.substring(0, 1).toUpperCase()}</span>
                           </div>`;
                    html += `
                        <a href="/aluno_perfil.php?id=${a.id}" class="flex items-center gap-3 p-3 hover:bg-gray-50 transition">
                            ${foto}
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">${a.nome}</div>
                                <div class="text-xs text-gray-500">
                                    ${a.total_cursos} curso(s) •
                                    <span class="text-green-600">${a.total_aprovados} aprovado(s)</span> •
                                    <span class="text-red-600">${a.total_reprovados} reprovado(s)</span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>`;
                });
                html += '</div>';
                resultadosBusca.innerHTML = html;
                resultadosBusca.classList.remove('hidden');
            })
            .catch(() => {
                resultadosBusca.innerHTML = '<div class="p-4 text-red-500 text-center">Erro ao buscar</div>';
                resultadosBusca.classList.remove('hidden');
            });
    }, 300);
});

document.addEventListener('click', function(e) {
    if (!inputBusca.contains(e.target) && !resultadosBusca.contains(e.target)) {
        resultadosBusca.classList.add('hidden');
    }
});

inputBusca.addEventListener('focus', function() {
    if (this.value.trim().length >= 2 && resultadosBusca.innerHTML) {
        resultadosBusca.classList.remove('hidden');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
