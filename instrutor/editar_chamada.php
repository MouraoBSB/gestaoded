<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 19:01:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['instrutor', 'gestor']);

$pdo = getConnection();
$aulaId = isset($_GET['aula_id']) ? (int)$_GET['aula_id'] : 0;

if (!$aulaId) {
    setFlashMessage('Aula não encontrada!', 'error');
    redirect('/instrutor/nova_chamada.php');
}

$stmt = $pdo->prepare("
    SELECT a.*, c.nome as curso_nome, c.id as curso_id, a.turma_id
    FROM aulas a
    INNER JOIN cursos c ON a.curso_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$aulaId]);
$aula = $stmt->fetch();

if (!$aula) {
    setFlashMessage('Aula não encontrada!', 'error');
    redirect('/instrutor/nova_chamada.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataAula = $_POST['data_aula'] ?? $aula['data_aula'];
    $descricao = sanitize($_POST['descricao'] ?? '');
    $presencas = $_POST['presenca'] ?? [];
    $observacoes = $_POST['observacao'] ?? [];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE aulas SET data_aula = ?, descricao = ? WHERE id = ?");
        $stmt->execute([$dataAula, $descricao, $aulaId]);

        $stmt = $pdo->prepare("UPDATE presencas SET presente = ?, observacao = ? WHERE aula_id = ? AND aluno_id = ?");

        foreach ($presencas as $alunoId => $presente) {
            $obs = isset($observacoes[$alunoId]) ? trim($observacoes[$alunoId]) : null;
            $valorPresenca = in_array((int)$presente, [0, 1, 2]) ? (int)$presente : 0;
            $stmt->execute([$valorPresenca, $obs ?: null, $aulaId, $alunoId]);
        }

        $pdo->commit();

        setFlashMessage('Chamada atualizada com sucesso!', 'success');
        $redirectParam = $aula['turma_id'] ? 'turma_id=' . $aula['turma_id'] : 'curso_id=' . $aula['curso_id'];
        redirect('/instrutor/historico_chamadas.php?' . $redirectParam);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMessage('Erro ao atualizar chamada: ' . $e->getMessage(), 'error');
    }
}

$stmt = $pdo->prepare("
    SELECT a.*, p.presente, p.observacao, p.id as presenca_id
    FROM alunos a
    INNER JOIN presencas p ON a.id = p.aluno_id
    WHERE p.aula_id = ?
    ORDER BY a.nome
");
$stmt->execute([$aulaId]);
$alunos = $stmt->fetchAll();

$pageTitle = 'Editar Chamada - ' . htmlspecialchars($aula['curso_nome']);
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.aluno-item {
    transition: all 0.2s;
}

.aluno-item:hover {
    background: #f9fafb;
}

.btn-presenca {
    padding: 0.5rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.2s;
    cursor: pointer;
    border: 2px solid transparent;
}

.btn-presente {
    background: #e8f5e9;
    color: #2e7d32;
    border-color: #c8e6c9;
}

.btn-presente.active {
    background: #4caf50;
    color: white;
    border-color: #4caf50;
}

.btn-falta {
    background: #ffebee;
    color: #c62828;
    border-color: #ffcdd2;
}

.btn-falta.active {
    background: #f44336;
    color: white;
    border-color: #f44336;
}

.btn-justificada {
    background: #fff8e1;
    color: #f57f17;
    border-color: #fff176;
}

.btn-justificada.active {
    background: #ff9800;
    color: white;
    border-color: #ff9800;
}

.contador {
    font-size: 2rem;
    font-weight: bold;
}
</style>

<div class="mb-6">
    <a href="/instrutor/historico_chamadas.php?<?= $aula['turma_id'] ? 'turma_id=' . $aula['turma_id'] : 'curso_id=' . $aula['curso_id'] ?>" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Voltar ao Histórico
    </a>
    
    <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg shadow-lg p-6 text-white mb-6">
        <h1 class="text-3xl font-bold mb-2">Editar Chamada</h1>
        <p class="text-lg opacity-90"><?= htmlspecialchars($aula['curso_nome']) ?> - <?= formatarData($aula['data_aula']) ?></p>
    </div>
</div>

<form method="POST" id="formChamada">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
        <div class="lg:col-span-3 bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">Editar Presença</h2>
                <div class="flex gap-2">
                    <button type="button" onclick="marcarTodos(true)" class="text-sm bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200 transition">
                        ✓ Marcar Todos Presentes
                    </button>
                    <button type="button" onclick="marcarTodos(false)" class="text-sm bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 transition">
                        ✗ Marcar Todos Falta
                    </button>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Data da Aula</label>
                <input type="date" name="data_aula" value="<?= $aula['data_aula'] ?>" required class="w-full md:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Descrição da Aula</label>
                <textarea name="descricao" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($aula['descricao']) ?></textarea>
            </div>
            
            <div class="space-y-3">
                <?php foreach ($alunos as $index => $aluno): ?>
                    <div class="aluno-item border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <span class="text-gray-500 font-semibold text-lg w-8"><?= $index + 1 ?>.</span>
                                
                                <?php if ($aluno['foto']): ?>
                                    <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="w-12 h-12 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold">
                                        <?= strtoupper(substr($aluno['nome'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 truncate"><?= htmlspecialchars($aluno['nome']) ?></h3>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="toggleObs(<?= $aluno['id'] ?>)" class="<?= !empty($aluno['observacao']) ? 'text-yellow-600' : 'text-gray-400' ?> hover:text-yellow-600 transition" title="Observacao">
                                    <svg class="w-5 h-5 obs-icon-<?= $aluno['id'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" <?= !empty($aluno['observacao']) ? 'style="color:#d97706"' : '' ?>>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                    </svg>
                                </button>
                                <input type="hidden" name="presenca[<?= $aluno['id'] ?>]" value="<?= $aluno['presente'] ?>" class="presenca-input-<?= $aluno['id'] ?>">
                                <button type="button" onclick="marcarPresenca(<?= $aluno['id'] ?>, 1)" class="btn-presenca btn-presente presenca-btn-<?= $aluno['id'] ?>-1 <?= $aluno['presente'] == 1 ? 'active' : '' ?>">
                                    Presente
                                </button>
                                <button type="button" onclick="marcarPresenca(<?= $aluno['id'] ?>, 2)" class="btn-presenca btn-justificada presenca-btn-<?= $aluno['id'] ?>-2 <?= $aluno['presente'] == 2 ? 'active' : '' ?>">
                                    Justificada
                                </button>
                                <button type="button" onclick="marcarPresenca(<?= $aluno['id'] ?>, 0)" class="btn-presenca btn-falta presenca-btn-<?= $aluno['id'] ?>-0 <?= $aluno['presente'] == 0 ? 'active' : '' ?>">
                                    Falta
                                </button>
                            </div>
                        </div>
                        <div id="obs-<?= $aluno['id'] ?>" class="<?= !empty($aluno['observacao']) ? '' : 'hidden' ?> mt-3 ml-12">
                            <input type="text" name="observacao[<?= $aluno['id'] ?>]" value="<?= htmlspecialchars($aluno['observacao'] ?? '') ?>"
                                   placeholder="Ex: Justificou ausencia, chegou atrasado..."
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-400 focus:border-transparent"
                                   oninput="atualizarIconeObs(<?= $aluno['id'] ?>, this.value)">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Resumo</h3>
                
                <div class="space-y-4">
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <p class="text-sm text-green-700 mb-1">Presentes</p>
                        <p class="contador text-green-600" id="totalPresentes">0</p>
                    </div>

                    <div class="text-center p-4 bg-yellow-50 rounded-lg">
                        <p class="text-sm text-yellow-700 mb-1">Justificadas</p>
                        <p class="contador text-yellow-600" id="totalJustificadas">0</p>
                    </div>

                    <div class="text-center p-4 bg-red-50 rounded-lg">
                        <p class="text-sm text-red-700 mb-1">Faltas</p>
                        <p class="contador text-red-600" id="totalFaltas">0</p>
                    </div>
                    
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-700 mb-1">Total</p>
                        <p class="contador text-gray-600"><?= count($alunos) ?></p>
                    </div>
                </div>
                
                <button type="submit" class="w-full mt-6 bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-bold transition">
                    Salvar Alterações
                </button>
            </div>
        </div>
    </div>
</form>

<script>
function toggleObs(alunoId) {
    const div = document.getElementById('obs-' + alunoId);
    div.classList.toggle('hidden');
    if (!div.classList.contains('hidden')) {
        div.querySelector('input').focus();
    }
}

function atualizarIconeObs(alunoId, valor) {
    const icon = document.querySelector('.obs-icon-' + alunoId);
    if (valor.trim()) {
        icon.style.color = '#d97706';
        icon.closest('button').classList.add('text-yellow-600');
        icon.closest('button').classList.remove('text-gray-400');
    } else {
        icon.style.color = '';
        icon.closest('button').classList.remove('text-yellow-600');
        icon.closest('button').classList.add('text-gray-400');
    }
}

function marcarTodos(presente) {
    <?php foreach ($alunos as $aluno): ?>
        marcarPresenca(<?= $aluno['id'] ?>, presente);
    <?php endforeach; ?>
}

const presencas = {};

<?php foreach ($alunos as $aluno): ?>
presencas[<?= $aluno['id'] ?>] = <?= (int)$aluno['presente'] ?>;
<?php endforeach; ?>

function marcarPresenca(alunoId, valor) {
    presencas[alunoId] = valor;
    document.querySelector(`.presenca-input-${alunoId}`).value = valor;

    [0, 1, 2].forEach(v => {
        const btn = document.querySelector(`.presenca-btn-${alunoId}-${v}`);
        if (btn) btn.classList.toggle('active', v === valor);
    });

    atualizarContadores();
}

function atualizarContadores() {
    let presentes = 0;
    let justificadas = 0;
    let faltas = 0;

    for (let alunoId in presencas) {
        const v = presencas[alunoId];
        if (v === 1) presentes++;
        else if (v === 2) justificadas++;
        else faltas++;
    }

    document.getElementById('totalPresentes').textContent = presentes;
    document.getElementById('totalJustificadas').textContent = justificadas;
    document.getElementById('totalFaltas').textContent = faltas;
}

atualizarContadores();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
