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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'matricular') {
        $alunoId = (int)$_POST['aluno_id'];
        $cursoId = (int)$_POST['curso_id'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO matriculas (aluno_id, curso_id) VALUES (?, ?)");
            $stmt->execute([$alunoId, $cursoId]);
            setFlashMessage('Aluno matriculado com sucesso!', 'success');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                setFlashMessage('Este aluno já está matriculado neste curso!', 'error');
            } else {
                setFlashMessage('Erro ao matricular aluno: ' . $e->getMessage(), 'error');
            }
        }
        redirect('/diretor/matriculas.php');
    }
    
    if ($acao === 'remover') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM matriculas WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage('Matrícula removida com sucesso!', 'success');
        redirect('/diretor/matriculas.php');
    }
}

$cursoFiltro = isset($_GET['curso']) ? (int)$_GET['curso'] : null;

$sql = "
    SELECT m.id, a.nome as aluno_nome, c.nome as curso_nome, c.ano, m.data_matricula, c.id as curso_id
    FROM matriculas m
    INNER JOIN alunos a ON m.aluno_id = a.id
    INNER JOIN cursos c ON m.curso_id = c.id
    WHERE a.ativo = 1 AND c.ativo = 1
";

if ($cursoFiltro) {
    $sql .= " AND c.id = " . $cursoFiltro;
}

$sql .= " ORDER BY c.nome, a.nome";

$matriculas = $pdo->query($sql)->fetchAll();

$alunos = $pdo->query("SELECT id, nome FROM alunos WHERE ativo = 1 ORDER BY nome")->fetchAll();
$cursos = $pdo->query("SELECT id, nome, ano FROM cursos WHERE ativo = 1 ORDER BY ano DESC, nome")->fetchAll();

$pageTitle = 'Gerenciar Matrículas - Diretor';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Gerenciar Matrículas</h1>
        <p class="text-gray-600 mt-1">Visualização em lista</p>
    </div>
    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
        <a href="/diretor/matriculas_kanban.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition whitespace-nowrap flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
            </svg>
            Ver Kanban
        </a>
        <select onchange="window.location.href='/diretor/matriculas.php?curso=' + this.value" 
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            <option value="">Todos os cursos</option>
            <?php foreach ($cursos as $curso): ?>
                <option value="<?= $curso['id'] ?>" <?= $cursoFiltro == $curso['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($curso['nome']) ?> (<?= $curso['ano'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button onclick="abrirModal('modalMatricular')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition whitespace-nowrap">
            + Nova Matrícula
        </button>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Matrícula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($matriculas)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center">
                            <div class="flex flex-col items-center justify-center py-8">
                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <p class="text-gray-500 text-lg font-medium mb-2">Nenhuma matrícula encontrada</p>
                                <p class="text-gray-400 text-sm mb-4">Clique em "+ Nova Matrícula" para vincular alunos aos cursos</p>
                                <button onclick="abrirModal('modalMatricular')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                                    + Nova Matrícula
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($matriculas as $matricula): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($matricula['aluno_nome']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="/diretor/curso_detalhes.php?id=<?= $matricula['curso_id'] ?>" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
                                <?= htmlspecialchars($matricula['curso_nome']) ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600"><?= $matricula['ano'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600"><?= formatarData($matricula['data_matricula']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick="confirmarRemover(<?= $matricula['id'] ?>)" 
                                class="text-red-600 hover:text-red-900">Remover</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalMatricular" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Nova Matrícula</h3>
            <button onclick="fecharModal('modalMatricular')" class="text-gray-600 hover:text-gray-900">&times;</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="acao" value="matricular">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Aluno</label>
                <select name="aluno_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Selecione um aluno</option>
                    <?php foreach ($alunos as $aluno): ?>
                        <option value="<?= $aluno['id'] ?>"><?= htmlspecialchars($aluno['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
                <select name="curso_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Selecione um curso</option>
                    <?php foreach ($cursos as $curso): ?>
                        <option value="<?= $curso['id'] ?>">
                            <?= htmlspecialchars($curso['nome']) ?> (<?= $curso['ano'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">
                Matricular Aluno
            </button>
        </form>
    </div>
</div>

<form id="formRemover" method="POST" class="hidden">
    <input type="hidden" name="acao" value="remover">
    <input type="hidden" name="id" id="remover_id">
</form>

<script>
function abrirModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function fecharModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function confirmarRemover(id) {
    if (confirm('Tem certeza que deseja remover esta matrícula?')) {
        document.getElementById('remover_id').value = id;
        document.getElementById('formRemover').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
