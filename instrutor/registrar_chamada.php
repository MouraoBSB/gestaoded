<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:48:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['instrutor', 'gestor']);

$pdo = getConnection();
$instrutorId = getUserId();
$cursoId = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;

if (!$cursoId) {
    setFlashMessage('Curso não encontrado!', 'error');
    redirect('/instrutor/nova_chamada.php');
}

$userType = getUserType();

if ($userType === 'gestor') {
    $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ? AND ativo = 1");
    $stmt->execute([$cursoId]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ? AND instrutor_id = ? AND ativo = 1");
    $stmt->execute([$cursoId, $instrutorId]);
}

$curso = $stmt->fetch();

if (!$curso) {
    setFlashMessage('Você não tem permissão para acessar este curso!', 'error');
    redirect('/instrutor/nova_chamada.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataAula = $_POST['data_aula'] ?? date('Y-m-d');
    $descricao = sanitize($_POST['descricao'] ?? '');
    $presencas = $_POST['presenca'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO aulas (curso_id, data_aula, descricao) VALUES (?, ?, ?)");
        $stmt->execute([$cursoId, $dataAula, $descricao]);
        $aulaId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO presencas (aula_id, aluno_id, presente) VALUES (?, ?, ?)");
        
        foreach ($presencas as $alunoId => $presente) {
            $stmt->execute([$aulaId, $alunoId, $presente === '1' ? 1 : 0]);
        }
        
        $pdo->commit();
        
        setFlashMessage('Chamada registrada com sucesso!', 'success');
        redirect('/instrutor/historico_chamadas.php?curso_id=' . $cursoId);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMessage('Erro ao registrar chamada: ' . $e->getMessage(), 'error');
    }
}

$stmt = $pdo->prepare("
    SELECT a.*, m.data_matricula
    FROM alunos a
    INNER JOIN matriculas m ON a.id = m.aluno_id
    WHERE m.curso_id = ? AND a.ativo = 1
    ORDER BY a.nome
");
$stmt->execute([$cursoId]);
$alunos = $stmt->fetchAll();

$pageTitle = 'Registrar Chamada - ' . htmlspecialchars($curso['nome']);
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
        <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($curso['nome']) ?></h1>
        <p class="text-lg opacity-90">Ano: <?= $curso['ano'] ?> | Total de alunos: <?= count($alunos) ?></p>
    </div>
</div>

<?php if (empty($alunos)): ?>
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <p class="text-gray-500 text-lg">Nenhum aluno matriculado neste curso</p>
    </div>
<?php else: ?>
    <form method="POST" id="formChamada">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
            <div class="lg:col-span-3 bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Lista de Presença</h2>
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
                    <input type="date" name="data_aula" value="<?= date('Y-m-d') ?>" required class="w-full md:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição da Aula (opcional)</label>
                    <textarea name="descricao" rows="2" placeholder="Ex: Introdução ao tema, revisão, prática..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
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
                                        <p class="text-sm text-gray-500">
                                            <?= $aluno['data_nascimento'] ? calcularIdade($aluno['data_nascimento']) . ' anos' : 'Idade não informada' ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex gap-2">
                                    <input type="hidden" name="presenca[<?= $aluno['id'] ?>]" value="0" class="presenca-input-<?= $aluno['id'] ?>">
                                    <button type="button" onclick="marcarPresenca(<?= $aluno['id'] ?>, true)" class="btn-presenca btn-presente presenca-btn-<?= $aluno['id'] ?>-presente">
                                        ✓ Presente
                                    </button>
                                    <button type="button" onclick="marcarPresenca(<?= $aluno['id'] ?>, false)" class="btn-presenca btn-falta presenca-btn-<?= $aluno['id'] ?>-falta">
                                        ✗ Falta
                                    </button>
                                </div>
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
const presencas = {};

function marcarPresenca(alunoId, presente) {
    presencas[alunoId] = presente;
    
    document.querySelector(`.presenca-input-${alunoId}`).value = presente ? '1' : '0';
    
    const btnPresente = document.querySelector(`.presenca-btn-${alunoId}-presente`);
    const btnFalta = document.querySelector(`.presenca-btn-${alunoId}-falta`);
    
    if (presente) {
        btnPresente.classList.add('active');
        btnFalta.classList.remove('active');
    } else {
        btnFalta.classList.add('active');
        btnPresente.classList.remove('active');
    }
    
    atualizarContadores();
}

function marcarTodos(presente) {
    <?php foreach ($alunos as $aluno): ?>
        marcarPresenca(<?= $aluno['id'] ?>, presente);
    <?php endforeach; ?>
}

function atualizarContadores() {
    let presentes = 0;
    let faltas = 0;
    
    for (let alunoId in presencas) {
        if (presencas[alunoId]) {
            presentes++;
        } else {
            faltas++;
        }
    }
    
    document.getElementById('totalPresentes').textContent = presentes;
    document.getElementById('totalFaltas').textContent = faltas;
}

document.getElementById('formChamada').addEventListener('submit', function(e) {
    const totalMarcados = Object.keys(presencas).length;
    const totalAlunos = <?= count($alunos) ?>;
    
    if (totalMarcados === 0) {
        e.preventDefault();
        alert('Por favor, marque a presença de pelo menos um aluno!');
        return false;
    }
    
    if (totalMarcados < totalAlunos) {
        if (!confirm(`Você marcou apenas ${totalMarcados} de ${totalAlunos} alunos. Deseja continuar?`)) {
            e.preventDefault();
            return false;
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
