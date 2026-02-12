<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['gestor', 'instrutor']);

$pdo = getConnection();
$instrutorId = getUserId();

$cursoId = isset($_GET['curso']) ? (int)$_GET['curso'] : null;

if (!$cursoId) {
    setFlashMessage('error', 'Selecione um curso');
    redirect('/instrutor/dashboard.php');
}

$stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ? AND instrutor_id = ? AND ativo = 1");
$stmt->execute([$cursoId, $instrutorId]);
$curso = $stmt->fetch();

if (!$curso) {
    setFlashMessage('error', 'Curso não encontrado ou você não tem permissão');
    redirect('/instrutor/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'criar_aula') {
        $dataAula = sanitize($_POST['data_aula']);
        $descricao = sanitize($_POST['descricao']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO aulas (curso_id, data_aula, descricao) VALUES (?, ?, ?)");
            $stmt->execute([$cursoId, $dataAula, $descricao]);
            setFlashMessage('success', 'Aula registrada com sucesso!');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Erro ao registrar aula: ' . $e->getMessage());
        }
        redirect("/instrutor/aulas.php?curso=$cursoId");
    }
    
    if ($acao === 'registrar_presencas') {
        $aulaId = (int)$_POST['aula_id'];
        $presencas = $_POST['presencas'] ?? [];
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("DELETE FROM presencas WHERE aula_id = ?");
            $stmt->execute([$aulaId]);
            
            $stmt = $pdo->prepare("INSERT INTO presencas (aula_id, aluno_id, presente) VALUES (?, ?, ?)");
            foreach ($presencas as $alunoId => $presente) {
                $stmt->execute([$aulaId, $alunoId, $presente]);
            }
            
            $pdo->commit();
            setFlashMessage('success', 'Presenças registradas com sucesso!');
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlashMessage('error', 'Erro ao registrar presenças: ' . $e->getMessage());
        }
        redirect("/instrutor/aulas.php?curso=$cursoId");
    }
}

$aulas = $pdo->prepare("
    SELECT a.*, 
           COUNT(DISTINCT p.id) as total_presencas,
           COUNT(DISTINCT m.aluno_id) as total_alunos
    FROM aulas a
    LEFT JOIN presencas p ON a.id = p.aula_id
    LEFT JOIN matriculas m ON a.curso_id = m.curso_id
    WHERE a.curso_id = ?
    GROUP BY a.id
    ORDER BY a.data_aula DESC
");
$aulas->execute([$cursoId]);
$aulasLista = $aulas->fetchAll();

$pageTitle = 'Aulas - ' . htmlspecialchars($curso['nome']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <a href="/instrutor/dashboard.php" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
        ← Voltar ao Dashboard
    </a>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($curso['nome']) ?></h1>
            <p class="text-gray-600">Ano: <?= $curso['ano'] ?></p>
        </div>
        <button onclick="abrirModal('modalCriarAula')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
            + Nova Aula
        </button>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <?php if (empty($aulasLista)): ?>
        <div class="p-8 text-center text-gray-500">
            Nenhuma aula registrada ainda. Clique em "Nova Aula" para começar.
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Presenças</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($aulasLista as $aula): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?= formatarData($aula['data_aula']) ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600"><?= htmlspecialchars($aula['descricao']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600">
                                <?= $aula['total_presencas'] ?> / <?= $aula['total_alunos'] ?> registradas
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="/instrutor/presenca.php?aula=<?= $aula['id'] ?>&curso=<?= $cursoId ?>" 
                                class="text-blue-600 hover:text-blue-900">
                                Registrar Presença
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="modalCriarAula" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Nova Aula</h3>
            <button onclick="fecharModal('modalCriarAula')" class="text-gray-600 hover:text-gray-900">&times;</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="acao" value="criar_aula">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data da Aula</label>
                <input type="date" name="data_aula" required value="<?= date('Y-m-d') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea name="descricao" rows="3" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">
                Registrar Aula
            </button>
        </form>
    </div>
</div>

<script>
function abrirModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function fecharModal(id) {
    document.getElementById(id).classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
