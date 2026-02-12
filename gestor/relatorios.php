<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('gestor');

$pdo = getConnection();

$cursoFiltro = isset($_GET['curso']) ? (int)$_GET['curso'] : null;
$anoFiltro = isset($_GET['ano']) ? (int)$_GET['ano'] : null;

$sql = "
    SELECT 
        c.id as curso_id,
        c.nome as curso_nome,
        c.ano,
        COUNT(DISTINCT m.aluno_id) as total_alunos,
        COUNT(DISTINCT a.id) as total_aulas,
        SUM(CASE WHEN co.aprovado = 1 THEN 1 ELSE 0 END) as total_aprovados,
        SUM(CASE WHEN co.aprovado = 0 THEN 1 ELSE 0 END) as total_reprovados,
        u.nome as instrutor_nome
    FROM cursos c
    LEFT JOIN matriculas m ON c.id = m.curso_id
    LEFT JOIN aulas a ON c.id = a.curso_id
    LEFT JOIN conclusoes co ON c.id = co.curso_id
    LEFT JOIN usuarios u ON c.instrutor_id = u.id
    WHERE c.ativo = 1
";

if ($cursoFiltro) {
    $sql .= " AND c.id = " . $cursoFiltro;
}

if ($anoFiltro) {
    $sql .= " AND c.ano = " . $anoFiltro;
}

$sql .= " GROUP BY c.id, c.nome, c.ano, u.nome ORDER BY c.ano DESC, c.nome";

$relatorios = $pdo->query($sql)->fetchAll();

$cursos = $pdo->query("SELECT id, nome, ano FROM cursos WHERE ativo = 1 ORDER BY ano DESC, nome")->fetchAll();
$anos = $pdo->query("SELECT DISTINCT ano FROM cursos WHERE ativo = 1 ORDER BY ano DESC")->fetchAll();

$conclusoesPorAno = $pdo->query("
    SELECT 
        co.ano_conclusao,
        COUNT(*) as total,
        SUM(CASE WHEN co.aprovado = 1 THEN 1 ELSE 0 END) as aprovados,
        SUM(CASE WHEN co.aprovado = 0 THEN 1 ELSE 0 END) as reprovados
    FROM conclusoes co
    GROUP BY co.ano_conclusao
    ORDER BY co.ano_conclusao DESC
")->fetchAll();

$pageTitle = 'Relatórios - Gestor';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Relatórios</h1>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Filtros</h2>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
            <select name="curso" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Todos os cursos</option>
                <?php foreach ($cursos as $curso): ?>
                    <option value="<?= $curso['id'] ?>" <?= $cursoFiltro == $curso['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($curso['nome']) ?> (<?= $curso['ano'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
            <select name="ano" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Todos os anos</option>
                <?php foreach ($anos as $ano): ?>
                    <option value="<?= $ano['ano'] ?>" <?= $anoFiltro == $ano['ano'] ? 'selected' : '' ?>>
                        <?= $ano['ano'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">
                Filtrar
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Conclusões por Ano</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aprovados</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reprovados</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taxa de Aprovação</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($conclusoesPorAno as $conclusao): 
                    $taxaAprovacao = $conclusao['total'] > 0 
                        ? round(($conclusao['aprovados'] / $conclusao['total']) * 100) 
                        : 0;
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap font-semibold"><?= $conclusao['ano_conclusao'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= $conclusao['total'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-green-600 font-semibold"><?= $conclusao['aprovados'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-red-600 font-semibold"><?= $conclusao['reprovados'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2" style="max-width: 100px;">
                                <div class="bg-green-600 h-2.5 rounded-full" style="width: <?= $taxaAprovacao ?>%"></div>
                            </div>
                            <span class="text-sm font-medium"><?= $taxaAprovacao ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-bold text-gray-800">Relatório de Cursos</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instrutor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aulas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aprovados</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reprovados</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($relatorios)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            Nenhum dado encontrado
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($relatorios as $relatorio): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($relatorio['curso_nome']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600"><?= $relatorio['ano'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600">
                                <?= $relatorio['instrutor_nome'] ? htmlspecialchars($relatorio['instrutor_nome']) : '<span class="text-gray-400">Não atribuído</span>' ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600"><?= $relatorio['total_alunos'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600"><?= $relatorio['total_aulas'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-green-600 font-semibold"><?= $relatorio['total_aprovados'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-red-600 font-semibold"><?= $relatorio['total_reprovados'] ?></div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
