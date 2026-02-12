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
    $conclusoes = $_POST['conclusoes'] ?? [];
    $anoConclusao = (int)$_POST['ano_conclusao'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("DELETE FROM conclusoes WHERE curso_id = ?");
        $stmt->execute([$cursoId]);
        
        $stmt = $pdo->prepare("
            INSERT INTO conclusoes (aluno_id, curso_id, aprovado, ano_conclusao, observacoes) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($conclusoes as $alunoId => $dados) {
            if (isset($dados['status'])) {
                $aprovado = $dados['status'] === 'aprovado' ? 1 : 0;
                $observacoes = sanitize($dados['observacoes'] ?? '');
                $stmt->execute([$alunoId, $cursoId, $aprovado, $anoConclusao, $observacoes]);
            }
        }
        
        $pdo->commit();
        setFlashMessage('success', 'Conclusões registradas com sucesso!');
        redirect("/instrutor/conclusao.php?curso=$cursoId");
    } catch (PDOException $e) {
        $pdo->rollBack();
        setFlashMessage('error', 'Erro ao registrar conclusões: ' . $e->getMessage());
    }
}

$alunos = $pdo->prepare("
    SELECT a.id, a.nome, a.foto,
           c.aprovado, c.ano_conclusao, c.observacoes,
           COUNT(DISTINCT aulas.id) as total_aulas,
           SUM(CASE WHEN p.presente = 1 THEN 1 ELSE 0 END) as total_presencas
    FROM matriculas m
    INNER JOIN alunos a ON m.aluno_id = a.id
    LEFT JOIN conclusoes c ON c.aluno_id = a.id AND c.curso_id = m.curso_id
    LEFT JOIN aulas ON aulas.curso_id = m.curso_id
    LEFT JOIN presencas p ON p.aula_id = aulas.id AND p.aluno_id = a.id
    WHERE m.curso_id = ? AND a.ativo = 1
    GROUP BY a.id, a.nome, a.foto, c.aprovado, c.ano_conclusao, c.observacoes
    ORDER BY a.nome
");
$alunos->execute([$cursoId]);
$alunosLista = $alunos->fetchAll();

$pageTitle = 'Conclusão do Curso';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <a href="/instrutor/dashboard.php" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
        ← Voltar ao Dashboard
    </a>
    <h1 class="text-3xl font-bold text-gray-800">Conclusão do Curso</h1>
    <p class="text-gray-600 mt-2">
        Curso: <?= htmlspecialchars($curso['nome']) ?> (<?= $curso['ano'] ?>)
    </p>
</div>

<?php if (empty($alunosLista)): ?>
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
        Nenhum aluno matriculado neste curso.
    </div>
<?php else: ?>
    <form method="POST">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Ano de Conclusão</label>
            <input type="number" name="ano_conclusao" required min="2000" max="2100" 
                value="<?= date('Y') ?>"
                class="w-full sm:w-64 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Frequência</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($alunosLista as $aluno): 
                            $percentualPresenca = $aluno['total_aulas'] > 0 
                                ? round(($aluno['total_presencas'] / $aluno['total_aulas']) * 100) 
                                : 0;
                            $statusAtual = $aluno['aprovado'] !== null 
                                ? ($aluno['aprovado'] ? 'aprovado' : 'reprovado') 
                                : '';
                        ?>
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
                                <div class="text-sm text-gray-600">
                                    <?= $aluno['total_presencas'] ?> / <?= $aluno['total_aulas'] ?> 
                                    <span class="text-xs">(<?= $percentualPresenca ?>%)</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex justify-center gap-3">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="conclusoes[<?= $aluno['id'] ?>][status]" value="aprovado" 
                                            <?= $statusAtual === 'aprovado' ? 'checked' : '' ?>
                                            class="form-radio h-4 w-4 text-green-600">
                                        <span class="ml-2 text-sm text-green-700">Aprovado</span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="conclusoes[<?= $aluno['id'] ?>][status]" value="reprovado" 
                                            <?= $statusAtual === 'reprovado' ? 'checked' : '' ?>
                                            class="form-radio h-4 w-4 text-red-600">
                                        <span class="ml-2 text-sm text-red-700">Reprovado</span>
                                    </label>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <input type="text" name="conclusoes[<?= $aluno['id'] ?>][observacoes]" 
                                    value="<?= htmlspecialchars($aluno['observacoes'] ?? '') ?>"
                                    placeholder="Observações opcionais"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="flex justify-end gap-3">
            <a href="/instrutor/dashboard.php" 
                class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition">
                Cancelar
            </a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition">
                Salvar Conclusões
            </button>
        </div>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
