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

// Buscar turmas ativas para o filtro
$turmas = $pdo->query("
    SELECT t.id, c.nome as curso_nome, t.ano, t.semestre,
           COUNT(DISTINCT m.aluno_id) as total_alunos
    FROM turmas t
    INNER JOIN cursos c ON t.curso_id = c.id
    LEFT JOIN matriculas m ON m.turma_id = t.id
    LEFT JOIN alunos a ON m.aluno_id = a.id AND a.ativo = 1
    WHERE t.status = 'ativa' AND c.ativo = 1
    GROUP BY t.id
    ORDER BY t.ano DESC, c.nome
")->fetchAll();

$turmaSelecionada = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$porPagina = 18;

// Condições WHERE comuns
$where = " WHERE a.ativo = 1";
$params = [];

if ($turmaSelecionada) {
    $where .= " AND t.id = ?";
    $params[] = $turmaSelecionada;
}

if ($busca) {
    $where .= " AND a.nome LIKE ?";
    $params[] = '%' . $busca . '%';
}

// Contar total de registros
$sqlCount = "
    SELECT COUNT(*) FROM (
        SELECT a.id, t.id as tid
        FROM alunos a
        INNER JOIN matriculas m ON a.id = m.aluno_id
        INNER JOIN turmas t ON m.turma_id = t.id AND t.status = 'ativa'
        INNER JOIN cursos c ON t.curso_id = c.id AND c.ativo = 1
        $where
        GROUP BY a.id, t.id
    ) as sub
";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$totalRegistros = (int)$stmtCount->fetchColumn();
$totalPaginas = max(1, ceil($totalRegistros / $porPagina));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $porPagina;

// Buscar alunos da página atual
$sql = "
    SELECT a.id, a.nome, a.foto,
           c.id as curso_id, c.nome as curso_nome, t.ano, t.id as turma_id,
           COUNT(DISTINCT au.id) as total_aulas,
           SUM(CASE WHEN p.presente IN (1,2) THEN 1 ELSE 0 END) as total_presencas,
           CASE
               WHEN COUNT(DISTINCT au.id) > 0
               THEN ROUND((SUM(CASE WHEN p.presente IN (1,2) THEN 1 ELSE 0 END) / COUNT(DISTINCT au.id)) * 100)
               ELSE 0
           END as frequencia
    FROM alunos a
    INNER JOIN matriculas m ON a.id = m.aluno_id
    INNER JOIN turmas t ON m.turma_id = t.id AND t.status = 'ativa'
    INNER JOIN cursos c ON t.curso_id = c.id AND c.ativo = 1
    LEFT JOIN aulas au ON (au.turma_id = t.id OR (au.turma_id IS NULL AND au.curso_id = t.curso_id))
    LEFT JOIN presencas p ON au.id = p.aula_id AND a.id = p.aluno_id
    $where
    GROUP BY a.id, t.id
    ORDER BY a.nome, c.nome
    LIMIT $porPagina OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alunos = $stmt->fetchAll();

// Montar query string para paginação
$queryParams = [];
if ($turmaSelecionada) $queryParams['turma_id'] = $turmaSelecionada;
if ($busca) $queryParams['busca'] = $busca;

$pageTitle = 'Frequência dos Alunos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Frequência dos Alunos</h1>
    <p class="text-gray-600 mt-2">Visualize a frequência individual de cada aluno matriculado em turmas ativas</p>
</div>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Aluno</label>
            <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Digite o nome do aluno..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
        </div>
        <div class="w-full md:w-80">
            <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Turma</label>
            <select name="turma_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                <option value="">Todas as turmas</option>
                <?php foreach ($turmas as $turma): ?>
                    <option value="<?= $turma['id'] ?>" <?= $turmaSelecionada == $turma['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($turma['curso_nome']) ?> - <?= $turma['ano'] ?> (<?= $turma['total_alunos'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition font-semibold">
                Filtrar
            </button>
            <?php if ($turmaSelecionada || $busca): ?>
                <a href="/gestor/frequencia_alunos.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition font-semibold">
                    Limpar
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (!empty($alunos)): ?>
    <div class="mb-4 flex justify-between items-center">
        <span class="text-sm text-gray-600">
            <?= $totalRegistros ?> resultado(s) encontrado(s) — Página <?= $pagina ?> de <?= $totalPaginas ?>
        </span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($alunos as $aluno): ?>
            <?php
            $frequencia = $aluno['frequencia'] ?? 0;
            $corFrequencia = $frequencia >= 75 ? 'bg-green-500' : ($frequencia >= 50 ? 'bg-yellow-500' : 'bg-red-500');
            $statusTexto = $frequencia >= 75 ? 'Ótima' : ($frequencia >= 50 ? 'Regular' : 'Baixa');
            ?>
            <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <?php if ($aluno['foto']): ?>
                            <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="w-16 h-16 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white font-bold text-xl">
                                <?= strtoupper(mb_substr($aluno['nome'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>

                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-900 truncate"><?= htmlspecialchars($aluno['nome']) ?></h3>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($aluno['curso_nome']) ?> - <?= $aluno['ano'] ?></p>
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
                        <a href="/gestor/perfil_aluno.php?aluno_id=<?= $aluno['id'] ?>&curso_id=<?= $aluno['curso_id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-lg text-sm transition">
                            Frequência
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPaginas > 1): ?>
        <nav class="mt-8 flex justify-center">
            <ul class="flex items-center gap-1">
                <?php if ($pagina > 1): ?>
                    <li>
                        <a href="?<?= http_build_query(array_merge($queryParams, ['pagina' => $pagina - 1])) ?>"
                           class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                            &laquo; Anterior
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $inicio = max(1, $pagina - 2);
                $fim = min($totalPaginas, $pagina + 2);
                if ($inicio > 1): ?>
                    <li>
                        <a href="?<?= http_build_query(array_merge($queryParams, ['pagina' => 1])) ?>"
                           class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 transition">1</a>
                    </li>
                    <?php if ($inicio > 2): ?>
                        <li><span class="px-2 text-gray-400">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                    <li>
                        <?php if ($i == $pagina): ?>
                            <span class="px-4 py-2 rounded-lg bg-purple-600 text-white font-semibold"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?<?= http_build_query(array_merge($queryParams, ['pagina' => $i])) ?>"
                               class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 transition"><?= $i ?></a>
                        <?php endif; ?>
                    </li>
                <?php endfor; ?>

                <?php if ($fim < $totalPaginas): ?>
                    <?php if ($fim < $totalPaginas - 1): ?>
                        <li><span class="px-2 text-gray-400">...</span></li>
                    <?php endif; ?>
                    <li>
                        <a href="?<?= http_build_query(array_merge($queryParams, ['pagina' => $totalPaginas])) ?>"
                           class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 transition"><?= $totalPaginas ?></a>
                    </li>
                <?php endif; ?>

                <?php if ($pagina < $totalPaginas): ?>
                    <li>
                        <a href="?<?= http_build_query(array_merge($queryParams, ['pagina' => $pagina + 1])) ?>"
                           class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                            Próxima &raquo;
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <p class="text-gray-500 text-lg">
            <?= $busca ? 'Nenhum aluno encontrado para "' . htmlspecialchars($busca) . '"' : 'Nenhum aluno matriculado em turmas ativas' ?>
        </p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
