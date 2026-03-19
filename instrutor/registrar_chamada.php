<?php
/**
 * Autor: Thiago Mourao
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:48:00
 *
 * Registrar chamada de uma turma
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['instrutor', 'gestor']);

$pdo = getConnection();
$instrutorId = getUserId();
$userType = getUserType();

// Suportar tanto turma_id (novo) quanto curso_id (legado)
$turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
$cursoId = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;

// Se veio curso_id (legado), tentar encontrar a turma ativa desse curso
if (!$turmaId && $cursoId) {
    $stmt = $pdo->prepare("SELECT id FROM turmas WHERE curso_id = ? AND status = 'ativa' ORDER BY ano DESC LIMIT 1");
    $stmt->execute([$cursoId]);
    $turmaId = (int)$stmt->fetchColumn();
}

if (!$turmaId) {
    setFlashMessage('Turma nao encontrada!', 'error');
    redirect('/instrutor/nova_chamada.php');
}

// Buscar turma com info do curso
if ($userType === 'gestor') {
    $stmt = $pdo->prepare("
        SELECT t.*, c.nome as curso_nome, c.capa as curso_capa, c.tipo_periodo
        FROM turmas t
        INNER JOIN cursos c ON t.curso_id = c.id
        WHERE t.id = ? AND t.status = 'ativa' AND c.ativo = 1
    ");
    $stmt->execute([$turmaId]);
} else {
    $stmt = $pdo->prepare("
        SELECT t.*, c.nome as curso_nome, c.capa as curso_capa, c.tipo_periodo
        FROM turmas t
        INNER JOIN cursos c ON t.curso_id = c.id
        INNER JOIN turma_instrutores ti ON t.id = ti.turma_id
        WHERE t.id = ? AND ti.instrutor_id = ? AND t.status = 'ativa' AND c.ativo = 1
    ");
    $stmt->execute([$turmaId, $instrutorId]);
}

$turma = $stmt->fetch();

if (!$turma) {
    setFlashMessage('Voce nao tem permissao para acessar esta turma!', 'error');
    redirect('/instrutor/nova_chamada.php');
}

$periodo = $turma['ano'];
if ($turma['semestre']) {
    $periodo .= '/' . $turma['semestre'] . 'o Sem';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataAula = $_POST['data_aula'] ?? date('Y-m-d');
    $descricao = sanitize($_POST['descricao'] ?? '');
    $presencas = $_POST['presenca'] ?? [];
    $observacoes = $_POST['observacao'] ?? [];

    // Verificar se ja existe chamada nesta data para esta turma
    $stmtCheck = $pdo->prepare("SELECT id FROM aulas WHERE turma_id = ? AND data_aula = ?");
    $stmtCheck->execute([$turmaId, $dataAula]);
    $aulaExistente = $stmtCheck->fetch();

    if ($aulaExistente) {
        setFlashMessage('Ja existe uma chamada registrada para esta data! Edite ou exclua a chamada existente antes de registrar uma nova.', 'error');
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO aulas (curso_id, turma_id, data_aula, descricao) VALUES (?, ?, ?, ?)");
            $stmt->execute([$turma['curso_id'], $turmaId, $dataAula, $descricao]);
            $aulaId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO presencas (aula_id, aluno_id, presente, observacao) VALUES (?, ?, ?, ?)");

            foreach ($presencas as $alunoId => $presente) {
                $obs = isset($observacoes[$alunoId]) ? trim($observacoes[$alunoId]) : null;
                $valorPresenca = in_array((int)$presente, [0, 1, 2]) ? (int)$presente : 0;
                $stmt->execute([$aulaId, $alunoId, $valorPresenca, $obs ?: null]);
            }

            $pdo->commit();

            setFlashMessage('Chamada registrada com sucesso!', 'success');
            redirect('/instrutor/historico_chamadas.php?turma_id=' . $turmaId);

        } catch (Exception $e) {
            $pdo->rollBack();
            setFlashMessage('Erro ao registrar chamada: ' . $e->getMessage(), 'error');
        }
    }
}

// Buscar alunos da TURMA (nao do curso)
$stmt = $pdo->prepare("
    SELECT a.*, m.data_matricula
    FROM alunos a
    INNER JOIN matriculas m ON a.id = m.aluno_id
    WHERE m.turma_id = ? AND a.ativo = 1
    ORDER BY a.nome
");
$stmt->execute([$turmaId]);
$alunos = $stmt->fetchAll();

// Buscar chamadas ja registradas para esta turma
$stmtHistorico = $pdo->prepare("
    SELECT a.id, a.data_aula, a.descricao,
           SUM(CASE WHEN p.presente = 1 THEN 1 ELSE 0 END) as presentes,
           SUM(CASE WHEN p.presente = 2 THEN 1 ELSE 0 END) as justificadas,
           SUM(CASE WHEN p.presente = 0 THEN 1 ELSE 0 END) as faltas
    FROM aulas a
    LEFT JOIN presencas p ON a.id = p.aula_id
    WHERE a.turma_id = ?
    GROUP BY a.id, a.data_aula, a.descricao
    ORDER BY a.data_aula DESC
    LIMIT 10
");
$stmtHistorico->execute([$turmaId]);
$chamadasAnteriores = $stmtHistorico->fetchAll();

// Verificar se ja existe chamada para a data de hoje
$stmtHoje = $pdo->prepare("SELECT id FROM aulas WHERE turma_id = ? AND data_aula = ?");
$stmtHoje->execute([$turmaId, date('Y-m-d')]);
$chamadaHoje = $stmtHoje->fetch();

$pageTitle = 'Registrar Chamada - ' . htmlspecialchars($turma['curso_nome']);
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
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.2s;
    cursor: pointer;
    border: 2px solid transparent;
}

@media (min-width: 768px) {
    .btn-presenca {
        padding: 0.5rem 1.5rem;
    }
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
    <a href="/instrutor/nova_chamada.php" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Voltar
    </a>

    <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg shadow-lg p-6 text-white mb-6">
        <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($turma['curso_nome']) ?></h1>
        <p class="text-lg opacity-90"><?= $periodo ?> | Total de alunos: <?= count($alunos) ?></p>
        <?php if ($turma['data_inicio']): ?>
            <p class="text-sm opacity-75">
                <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?>
                <?= $turma['data_fim'] ? ' - ' . date('d/m/Y', strtotime($turma['data_fim'])) : '' ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php if ($chamadaHoje): ?>
    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <div>
                <p class="font-semibold text-yellow-800">Ja existe uma chamada registrada para hoje (<?= date('d/m/Y') ?>)</p>
                <p class="text-sm text-yellow-700 mt-1">
                    Para registrar novamente,
                    <a href="/instrutor/editar_chamada.php?aula_id=<?= $chamadaHoje['id'] ?>" class="underline font-semibold">edite a chamada existente</a>
                    ou <a href="/instrutor/excluir_chamada.php?aula_id=<?= $chamadaHoje['id'] ?>&turma_id=<?= $turmaId ?>" class="underline font-semibold">exclua-a</a> antes.
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($chamadasAnteriores)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Chamadas Registradas</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-gray-600">Data</th>
                        <th class="text-left py-2 px-3 text-gray-600">Descricao</th>
                        <th class="text-center py-2 px-3 text-green-600">Presentes</th>
                        <th class="text-center py-2 px-3 text-yellow-600">Justificadas</th>
                        <th class="text-center py-2 px-3 text-red-600">Faltas</th>
                        <th class="text-center py-2 px-3 text-gray-600">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chamadasAnteriores as $chamada): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-2 px-3 font-medium"><?= date('d/m/Y', strtotime($chamada['data_aula'])) ?></td>
                            <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($chamada['descricao'] ?: '-') ?></td>
                            <td class="py-2 px-3 text-center"><span class="bg-green-100 text-green-700 px-2 py-1 rounded"><?= $chamada['presentes'] ?></span></td>
                            <td class="py-2 px-3 text-center"><span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded"><?= $chamada['justificadas'] ?></span></td>
                            <td class="py-2 px-3 text-center"><span class="bg-red-100 text-red-700 px-2 py-1 rounded"><?= $chamada['faltas'] ?></span></td>
                            <td class="py-2 px-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="/instrutor/editar_chamada.php?aula_id=<?= $chamada['id'] ?>" class="text-blue-600 hover:text-blue-800" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <a href="/instrutor/excluir_chamada.php?aula_id=<?= $chamada['id'] ?>&turma_id=<?= $turmaId ?>" class="text-red-600 hover:text-red-800" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta chamada?')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($alunos)): ?>
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <p class="text-gray-500 text-lg">Nenhum aluno matriculado nesta turma</p>
    </div>
<?php else: ?>
    <form method="POST" id="formChamada">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
            <div class="lg:col-span-3 bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Lista de Presença</h2>
                    <div class="flex gap-2">
                        <button type="button" onclick="marcarTodos(1)" class="text-sm bg-green-100 text-green-700 px-3 py-2 rounded-lg hover:bg-green-200 transition">
                            Todos Presentes
                        </button>
                        <button type="button" onclick="marcarTodos(0)" class="text-sm bg-red-100 text-red-700 px-3 py-2 rounded-lg hover:bg-red-200 transition">
                            Todos Falta
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data da Aula</label>
                    <input type="date" name="data_aula" value="<?= date('Y-m-d') ?>" required class="w-full md:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descricao da Aula (opcional)</label>
                    <textarea name="descricao" rows="2" placeholder="Ex: Introducao ao tema, revisao, pratica..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
                </div>

                <div class="space-y-3">
                    <?php foreach ($alunos as $index => $aluno): ?>
                        <div class="aluno-item border border-gray-200 rounded-lg p-4">
                            <!-- Info do aluno -->
                            <div class="flex items-center gap-3 mb-3">
                                <span class="text-gray-500 font-semibold text-lg w-6 flex-shrink-0"><?= $index + 1 ?>.</span>

                                <?php if ($aluno['foto']): ?>
                                    <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="w-10 h-10 md:w-12 md:h-12 rounded-full object-cover flex-shrink-0">
                                <?php else: ?>
                                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                                        <?= strtoupper(substr($aluno['nome'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 truncate text-sm md:text-base"><?= htmlspecialchars($aluno['nome']) ?></h3>
                                    <p class="text-xs md:text-sm text-gray-500">
                                        <?= $aluno['data_nascimento'] ? calcularIdade($aluno['data_nascimento']) . ' anos' : 'Idade nao informada' ?>
                                    </p>
                                </div>

                                <button type="button" onclick="toggleObs(<?= $aluno['id'] ?>)" class="text-gray-400 hover:text-yellow-600 transition flex-shrink-0" title="Adicionar observacao">
                                    <svg class="w-5 h-5 obs-icon-<?= $aluno['id'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Botoes de presenca -->
                            <div class="flex items-center gap-2 ml-9 md:ml-11">
                                <input type="hidden" name="presenca[<?= $aluno['id'] ?>]" value="0" class="presenca-input-<?= $aluno['id'] ?>">
                                <button type="button" onclick="marcarPresenca(<?= $aluno['id'] ?>, 1)" class="btn-presenca btn-presente presenca-btn-<?= $aluno['id'] ?>-1 flex-1 md:flex-none text-sm md:text-base">
                                    Presente
                                </button>
                                <button type="button" onclick="marcarPresenca(<?= $aluno['id'] ?>, 2)" class="btn-presenca btn-justificada presenca-btn-<?= $aluno['id'] ?>-2 flex-1 md:flex-none text-sm md:text-base">
                                    Justificada
                                </button>
                                <button type="button" onclick="marcarPresenca(<?= $aluno['id'] ?>, 0)" class="btn-presenca btn-falta presenca-btn-<?= $aluno['id'] ?>-0 flex-1 md:flex-none text-sm md:text-base">
                                    Falta
                                </button>
                            </div>

                            <div id="obs-<?= $aluno['id'] ?>" class="hidden mt-3 ml-9 md:ml-11">
                                <input type="text" name="observacao[<?= $aluno['id'] ?>]" placeholder="Ex: Justificou ausencia, chegou atrasado..."
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
                        Salvar Chamada
                    </button>
                </div>
            </div>
        </div>
    </form>
<?php endif; ?>

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

const presencas = {};

function marcarPresenca(alunoId, valor) {
    presencas[alunoId] = valor;
    document.querySelector(`.presenca-input-${alunoId}`).value = valor;

    [0, 1, 2].forEach(v => {
        const btn = document.querySelector(`.presenca-btn-${alunoId}-${v}`);
        if (btn) btn.classList.toggle('active', v === valor);
    });

    atualizarContadores();
}

function marcarTodos(valor) {
    <?php foreach ($alunos as $aluno): ?>
        marcarPresenca(<?= $aluno['id'] ?>, valor);
    <?php endforeach; ?>
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

// Datas que ja possuem chamada registrada
const datasRegistradas = <?= json_encode(array_map(function($c) { return $c['data_aula']; }, $chamadasAnteriores)) ?>;

const inputData = document.querySelector('input[name="data_aula"]');
const btnSalvar = document.querySelector('button[type="submit"]');
const avisoData = document.createElement('div');
avisoData.className = 'mt-2 text-sm text-red-600 font-semibold hidden';
avisoData.id = 'aviso-data-duplicada';
inputData?.parentNode?.appendChild(avisoData);

inputData?.addEventListener('change', function() {
    if (datasRegistradas.includes(this.value)) {
        avisoData.textContent = 'Ja existe uma chamada registrada nesta data! Edite ou exclua antes de registrar uma nova.';
        avisoData.classList.remove('hidden');
        btnSalvar.disabled = true;
        btnSalvar.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        avisoData.classList.add('hidden');
        btnSalvar.disabled = false;
        btnSalvar.classList.remove('opacity-50', 'cursor-not-allowed');
    }
});

// Verificar data inicial
if (inputData && datasRegistradas.includes(inputData.value)) {
    avisoData.textContent = 'Ja existe uma chamada registrada nesta data! Edite ou exclua antes de registrar uma nova.';
    avisoData.classList.remove('hidden');
    btnSalvar.disabled = true;
    btnSalvar.classList.add('opacity-50', 'cursor-not-allowed');
}

document.getElementById('formChamada')?.addEventListener('submit', function(e) {
    const dataAula = inputData?.value;
    if (datasRegistradas.includes(dataAula)) {
        e.preventDefault();
        alert('Ja existe uma chamada registrada nesta data! Edite ou exclua antes de registrar uma nova.');
        return false;
    }

    const totalMarcados = Object.keys(presencas).length;
    const totalAlunos = <?= count($alunos) ?>;

    if (totalMarcados === 0) {
        e.preventDefault();
        alert('Por favor, marque a presenca de pelo menos um aluno!');
        return false;
    }

    if (totalMarcados < totalAlunos) {
        if (!confirm(`Voce marcou apenas ${totalMarcados} de ${totalAlunos} alunos. Deseja continuar?`)) {
            e.preventDefault();
            return false;
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
