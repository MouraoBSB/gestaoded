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

$aulaId = isset($_GET['aula']) ? (int)$_GET['aula'] : null;
$cursoId = isset($_GET['curso']) ? (int)$_GET['curso'] : null;

if (!$aulaId || !$cursoId) {
    setFlashMessage('error', 'Parâmetros inválidos');
    redirect('/instrutor/dashboard.php');
}

$stmt = $pdo->prepare("
    SELECT a.*, c.nome as curso_nome, c.ano
    FROM aulas a
    INNER JOIN cursos c ON a.curso_id = c.id
    WHERE a.id = ? AND c.id = ? AND c.instrutor_id = ? AND c.ativo = 1
");
$stmt->execute([$aulaId, $cursoId, $instrutorId]);
$aula = $stmt->fetch();

if (!$aula) {
    setFlashMessage('error', 'Aula não encontrada ou você não tem permissão');
    redirect('/instrutor/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $presencas = $_POST['presencas'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("DELETE FROM presencas WHERE aula_id = ?");
        $stmt->execute([$aulaId]);
        
        $stmt = $pdo->prepare("INSERT INTO presencas (aula_id, aluno_id, presente) VALUES (?, ?, ?)");
        foreach ($presencas as $alunoId => $presente) {
            $stmt->execute([$aulaId, $alunoId, $presente == '1' ? 1 : 0]);
        }
        
        $pdo->commit();
        setFlashMessage('success', 'Presenças registradas com sucesso!');
        redirect("/instrutor/aulas.php?curso=$cursoId");
    } catch (PDOException $e) {
        $pdo->rollBack();
        setFlashMessage('error', 'Erro ao registrar presenças: ' . $e->getMessage());
    }
}

$alunos = $pdo->prepare("
    SELECT a.id, a.nome, a.foto, p.presente
    FROM matriculas m
    INNER JOIN alunos a ON m.aluno_id = a.id
    LEFT JOIN presencas p ON p.aluno_id = a.id AND p.aula_id = ?
    WHERE m.curso_id = ? AND a.ativo = 1
    ORDER BY a.nome
");
$alunos->execute([$aulaId, $cursoId]);
$alunosLista = $alunos->fetchAll();

$pageTitle = 'Registrar Presença';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <a href="/instrutor/aulas.php?curso=<?= $cursoId ?>" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
        ← Voltar às Aulas
    </a>
    <h1 class="text-3xl font-bold text-gray-800">Registrar Presença</h1>
    <p class="text-gray-600 mt-2">
        Curso: <?= htmlspecialchars($aula['curso_nome']) ?> (<?= $aula['ano'] ?>) | 
        Data: <?= formatarData($aula['data_aula']) ?>
    </p>
    <p class="text-gray-600"><?= htmlspecialchars($aula['descricao']) ?></p>
</div>

<?php if (empty($alunosLista)): ?>
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
        Nenhum aluno matriculado neste curso.
    </div>
<?php else: ?>
    <form method="POST">
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Presença</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($alunosLista as $aluno): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($aluno['foto']): ?>
                                    <img src="/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="h-10 w-10 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-gray-600 text-xs font-semibold">
                                            <?= strtoupper(substr($aluno['nome'], 0, 2)) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($aluno['nome']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center gap-4">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="presencas[<?= $aluno['id'] ?>]" value="1" 
                                            <?= $aluno['presente'] === 1 ? 'checked' : '' ?>
                                            class="form-radio h-5 w-5 text-green-600">
                                        <span class="ml-2 text-sm font-medium text-green-700">Presente</span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="presencas[<?= $aluno['id'] ?>]" value="0" 
                                            <?= $aluno['presente'] === 0 ? 'checked' : '' ?>
                                            class="form-radio h-5 w-5 text-red-600">
                                        <span class="ml-2 text-sm font-medium text-red-700">Falta</span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="flex justify-end gap-3">
            <a href="/instrutor/aulas.php?curso=<?= $cursoId ?>" 
                class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition">
                Cancelar
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                Salvar Presenças
            </button>
        </div>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
