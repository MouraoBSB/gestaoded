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

$cursoFiltro = isset($_GET['curso']) ? (int)$_GET['curso'] : null;

$sql = "
    SELECT 
        a.id as aluno_id,
        a.nome as aluno_nome,
        c.id as curso_id,
        c.nome as curso_nome,
        c.ano,
        COUNT(DISTINCT aulas.id) as total_aulas,
        SUM(CASE WHEN p.presente IN (1,2) THEN 1 ELSE 0 END) as total_presencas,
        co.aprovado,
        co.ano_conclusao
    FROM matriculas m
    INNER JOIN alunos a ON m.aluno_id = a.id
    INNER JOIN cursos c ON m.curso_id = c.id
    LEFT JOIN aulas ON aulas.curso_id = c.id
    LEFT JOIN presencas p ON p.aula_id = aulas.id AND p.aluno_id = a.id
    LEFT JOIN conclusoes co ON co.aluno_id = a.id AND co.curso_id = c.id
    WHERE a.ativo = 1 AND c.ativo = 1
";

if ($cursoFiltro) {
    $sql .= " AND c.id = " . $cursoFiltro;
}

$sql .= " GROUP BY a.id, a.nome, c.id, c.nome, c.ano, co.aprovado, co.ano_conclusao ORDER BY c.nome, a.nome";

$relatorios = $pdo->query($sql)->fetchAll();

$cursos = $pdo->query("SELECT id, nome, ano FROM cursos WHERE ativo = 1 ORDER BY ano DESC, nome")->fetchAll();

$pageTitle = 'Relatórios - Diretor';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Relatórios de Frequência</h1>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Filtros</h2>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
        <div class="flex items-end">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">
                Filtrar
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequência</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($relatorios)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Nenhum dado encontrado
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($relatorios as $relatorio): 
                        $percentualPresenca = $relatorio['total_aulas'] > 0 
                            ? round(($relatorio['total_presencas'] / $relatorio['total_aulas']) * 100) 
                            : 0;
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($relatorio['aluno_nome']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600"><?= htmlspecialchars($relatorio['curso_nome']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600"><?= $relatorio['ano'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm text-gray-600 mr-3">
                                    <?= $relatorio['total_presencas'] ?> / <?= $relatorio['total_aulas'] ?>
                                </div>
                                <div class="w-24 bg-gray-200 rounded-full h-2.5">
                                    <div class="<?= $percentualPresenca >= 75 ? 'bg-green-600' : ($percentualPresenca >= 50 ? 'bg-yellow-600' : 'bg-red-600') ?> h-2.5 rounded-full" 
                                        style="width: <?= $percentualPresenca ?>%"></div>
                                </div>
                                <span class="ml-2 text-sm font-medium"><?= $percentualPresenca ?>%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($relatorio['aprovado'] !== null): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $relatorio['aprovado'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $relatorio['aprovado'] ? 'Aprovado' : 'Reprovado' ?>
                                    <?php if ($relatorio['ano_conclusao']): ?>
                                        (<?= $relatorio['ano_conclusao'] ?>)
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Em andamento
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
