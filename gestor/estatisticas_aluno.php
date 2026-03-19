<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-13 14:24:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('gestor');

$alunoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$alunoId) {
    redirect('/gestor/relatorios.php');
}

$pdo = getConnection();

$aluno = $pdo->prepare("SELECT * FROM alunos WHERE id = ? AND ativo = 1");
$aluno->execute([$alunoId]);
$aluno = $aluno->fetch();

if (!$aluno) {
    setFlashMessage('Aluno não encontrado.', 'error');
    redirect('/gestor/relatorios.php');
}

$cursos = $pdo->prepare("
    SELECT 
        c.id,
        c.nome,
        c.ano,
        co.status,
        co.observacoes,
        co.registrado_em as data_conclusao,
        m.data_matricula,
        COUNT(DISTINCT aulas.id) as total_aulas,
        SUM(CASE WHEN p.presente IN (1,2) THEN 1 ELSE 0 END) as total_presencas
    FROM cursos c
    LEFT JOIN matriculas m ON c.id = m.curso_id AND m.aluno_id = ?
    LEFT JOIN conclusoes co ON c.id = co.curso_id AND co.aluno_id = ?
    LEFT JOIN aulas ON aulas.curso_id = c.id
    LEFT JOIN presencas p ON p.aula_id = aulas.id AND p.aluno_id = ?
    WHERE (m.aluno_id = ? OR co.aluno_id = ?) AND c.ativo = 1
    GROUP BY c.id, c.nome, c.ano, co.status, co.observacoes, co.registrado_em, m.data_matricula
    ORDER BY 
        CASE 
            WHEN co.registrado_em IS NOT NULL THEN co.registrado_em
            WHEN m.data_matricula IS NOT NULL THEN m.data_matricula
            ELSE c.ano
        END DESC,
        c.nome
");
$cursos->execute([$alunoId, $alunoId, $alunoId, $alunoId, $alunoId]);
$cursosLista = $cursos->fetchAll();

$totalCursos = count($cursosLista);
$totalAprovados = 0;
$totalReprovados = 0;
$totalEmAndamento = 0;

foreach ($cursosLista as $curso) {
    if ($curso['status'] === 'aprovado') {
        $totalAprovados++;
    } elseif ($curso['status'] === 'reprovado') {
        $totalReprovados++;
    } else {
        $totalEmAndamento++;
    }
}

$pageTitle = 'Estatísticas do Aluno - ' . $aluno['nome'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <a href="/gestor/relatorios.php" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
        ← Voltar aos Relatórios
    </a>
</div>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex items-center gap-4 mb-6">
        <?php if ($aluno['foto']): ?>
            <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="h-20 w-20 rounded-full object-cover border-4 border-purple-500">
        <?php else: ?>
            <div class="h-20 w-20 rounded-full bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center border-4 border-purple-500">
                <span class="text-white text-2xl font-bold">
                    <?= strtoupper(substr($aluno['nome'], 0, 2)) ?>
                </span>
            </div>
        <?php endif; ?>
        
        <div>
            <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($aluno['nome']) ?></h1>
            <p class="text-gray-600">
                Data de Nascimento: <?= date('d/m/Y', strtotime($aluno['data_nascimento'])) ?>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <div class="text-sm text-blue-600 font-semibold">Total de Cursos</div>
            <div class="text-2xl font-bold text-blue-700"><?= $totalCursos ?></div>
        </div>
        
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
            <div class="text-sm text-green-600 font-semibold">Aprovados</div>
            <div class="text-2xl font-bold text-green-700"><?= $totalAprovados ?></div>
        </div>
        
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <div class="text-sm text-red-600 font-semibold">Reprovados</div>
            <div class="text-2xl font-bold text-red-700"><?= $totalReprovados ?></div>
        </div>
        
        <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded">
            <div class="text-sm text-orange-600 font-semibold">Em Andamento</div>
            <div class="text-2xl font-bold text-orange-700"><?= $totalEmAndamento ?></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 border-b">
        <h2 class="text-xl font-bold text-gray-800">Cursos Matriculados</h2>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Frequência</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($cursosLista)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            Nenhum curso encontrado
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cursosLista as $curso): 
                        $percentualPresenca = $curso['total_aulas'] > 0 
                            ? round(($curso['total_presencas'] / $curso['total_aulas']) * 100) 
                            : 0;
                        
                        $statusClass = '';
                        $statusTexto = 'Em Andamento';
                        if ($curso['status'] === 'aprovado') {
                            $statusClass = 'bg-green-100 text-green-800';
                            $statusTexto = 'Aprovado';
                        } elseif ($curso['status'] === 'reprovado') {
                            $statusClass = 'bg-red-100 text-red-800';
                            $statusTexto = 'Reprovado';
                        } else {
                            $statusClass = 'bg-orange-100 text-orange-800';
                        }
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($curso['nome']) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600"><?= $curso['ano'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="text-sm text-gray-600">
                                <?= $curso['total_presencas'] ?> / <?= $curso['total_aulas'] ?>
                                <span class="text-xs block mt-1">(<?= $percentualPresenca ?>%)</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                <?= $statusTexto ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600">
                                <?= $curso['observacoes'] ? htmlspecialchars($curso['observacoes']) : '-' ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
