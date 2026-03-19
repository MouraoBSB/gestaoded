<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-14 14:56:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('gestor');

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'criar') {
        $cursoId = (int)$_POST['curso_id'];
        $ano = (int)$_POST['ano'];
        $semestre = !empty($_POST['semestre']) ? (int)$_POST['semestre'] : null;
        $dataInicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
        $dataFim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
        $dataInicioS2 = !empty($_POST['data_inicio_s2']) ? $_POST['data_inicio_s2'] : null;
        $dataFimS2 = !empty($_POST['data_fim_s2']) ? $_POST['data_fim_s2'] : null;
        $vagas = (int)$_POST['vagas'];
        $modalidade = $_POST['modalidade'] ?? 'presencial';
        $instrutores = $_POST['instrutores'] ?? [];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO turmas (curso_id, ano, semestre, data_inicio, data_fim, data_inicio_s2, data_fim_s2, vagas, modalidade, status, inscricoes_abertas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativa', 1)
            ");
            $stmt->execute([$cursoId, $ano, $semestre, $dataInicio, $dataFim, $dataInicioS2, $dataFimS2, $vagas, $modalidade]);
            $turmaId = $pdo->lastInsertId();
            
            if (!empty($instrutores)) {
                $stmt = $pdo->prepare("INSERT INTO turma_instrutores (turma_id, instrutor_id) VALUES (?, ?)");
                foreach ($instrutores as $instrutorId) {
                    $stmt->execute([$turmaId, $instrutorId]);
                }
            }
            
            $pdo->commit();
            setFlashMessage('Turma criada com sucesso!', 'success');
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlashMessage('Erro ao criar turma: ' . $e->getMessage(), 'error');
        }
        
        redirect('/gestor/turmas.php');
    }
    
    if ($acao === 'editar') {
        $turmaId = (int)$_POST['id'];
        $ano = (int)$_POST['ano'];
        $semestre = !empty($_POST['semestre']) ? (int)$_POST['semestre'] : null;
        $dataInicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
        $dataFim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
        $dataInicioS2 = !empty($_POST['data_inicio_s2']) ? $_POST['data_inicio_s2'] : null;
        $dataFimS2 = !empty($_POST['data_fim_s2']) ? $_POST['data_fim_s2'] : null;
        $vagas = (int)$_POST['vagas'];
        $modalidade = $_POST['modalidade'] ?? 'presencial';
        $instrutores = $_POST['instrutores'] ?? [];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE turmas
                SET ano = ?, semestre = ?, data_inicio = ?, data_fim = ?, data_inicio_s2 = ?, data_fim_s2 = ?, vagas = ?, modalidade = ?
                WHERE id = ?
            ");
            $stmt->execute([$ano, $semestre, $dataInicio, $dataFim, $dataInicioS2, $dataFimS2, $vagas, $modalidade, $turmaId]);
            
            $stmt = $pdo->prepare("DELETE FROM turma_instrutores WHERE turma_id = ?");
            $stmt->execute([$turmaId]);
            
            if (!empty($instrutores)) {
                $stmt = $pdo->prepare("INSERT INTO turma_instrutores (turma_id, instrutor_id) VALUES (?, ?)");
                foreach ($instrutores as $instrutorId) {
                    $stmt->execute([$turmaId, $instrutorId]);
                }
            }
            
            $pdo->commit();
            setFlashMessage('Turma atualizada com sucesso!', 'success');
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlashMessage('Erro ao atualizar turma: ' . $e->getMessage(), 'error');
        }
        
        redirect('/gestor/turmas.php');
    }
    
    if ($acao === 'toggle_status') {
        $turmaId = (int)$_POST['turma_id'];
        $novoStatus = $_POST['status'] === 'ativa' ? 'fechada' : 'ativa';
        
        $stmt = $pdo->prepare("UPDATE turmas SET status = ? WHERE id = ?");
        $stmt->execute([$novoStatus, $turmaId]);
        
        setFlashMessage('Status da turma atualizado!', 'success');
        redirect('/gestor/turmas.php');
    }
    
    if ($acao === 'toggle_inscricoes') {
        $turmaId = (int)$_POST['turma_id'];
        $novoStatus = (int)$_POST['inscricoes_abertas'] === 1 ? 0 : 1;
        
        $stmt = $pdo->prepare("UPDATE turmas SET inscricoes_abertas = ? WHERE id = ?");
        $stmt->execute([$novoStatus, $turmaId]);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($acao === 'deletar') {
        $turmaId = (int)$_POST['id'];
        
        try {
            $pdo->beginTransaction();
            
            // Verificar se há matrículas na turma
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM matriculas WHERE turma_id = ?");
            $stmt->execute([$turmaId]);
            $totalMatriculas = $stmt->fetchColumn();
            
            if ($totalMatriculas > 0) {
                throw new Exception("Não é possível deletar uma turma que possui alunos matriculados. Total de matrículas: $totalMatriculas");
            }
            
            // Deletar instrutores da turma
            $pdo->prepare("DELETE FROM turma_instrutores WHERE turma_id = ?")->execute([$turmaId]);
            
            // Deletar a turma
            $pdo->prepare("DELETE FROM turmas WHERE id = ?")->execute([$turmaId]);
            
            $pdo->commit();
            setFlashMessage('Turma deletada com sucesso!', 'success');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlashMessage('Erro ao deletar turma: ' . $e->getMessage(), 'error');
        }
        
        redirect('/gestor/turmas.php');
    }
    
    if ($acao === 'remover_aluno') {
        $turmaId = (int)$_POST['turma_id'];
        $alunoId = (int)$_POST['aluno_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM matriculas WHERE turma_id = ? AND aluno_id = ?");
            $stmt->execute([$turmaId, $alunoId]);
            
            echo json_encode(['success' => true, 'message' => 'Aluno removido da turma com sucesso!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao remover aluno: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($acao === 'adicionar_aluno') {
        $turmaId = (int)$_POST['turma_id'];
        $alunoId = (int)$_POST['aluno_id'];
        
        try {
            // Verificar se já está matriculado
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM matriculas WHERE turma_id = ? AND aluno_id = ?");
            $stmt->execute([$turmaId, $alunoId]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Aluno já está matriculado nesta turma");
            }
            
            // Buscar informações da turma (incluindo curso_id)
            $stmt = $pdo->prepare("
                SELECT t.curso_id, t.vagas, COUNT(m.id) as matriculados 
                FROM turmas t 
                LEFT JOIN matriculas m ON t.id = m.turma_id 
                WHERE t.id = ? 
                GROUP BY t.id, t.curso_id, t.vagas
            ");
            $stmt->execute([$turmaId]);
            $turma = $stmt->fetch();
            
            if (!$turma) {
                throw new Exception("Turma não encontrada");
            }
            
            if ($turma['matriculados'] >= $turma['vagas']) {
                throw new Exception("Não há vagas disponíveis nesta turma");
            }
            
            // Adicionar matrícula (incluindo curso_id)
            $stmt = $pdo->prepare("INSERT INTO matriculas (turma_id, aluno_id, curso_id, data_matricula) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$turmaId, $alunoId, $turma['curso_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Aluno adicionado à turma com sucesso!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar aluno: ' . $e->getMessage()]);
        }
        exit;
    }
}

$cursoFiltro = isset($_GET['curso']) ? (int)$_GET['curso'] : null;
$statusFiltro = $_GET['status'] ?? '';

$sql = "
    SELECT 
        t.*,
        c.nome as curso_nome,
        c.tipo_periodo,
        COUNT(DISTINCT m.aluno_id) as total_alunos,
        GROUP_CONCAT(DISTINCT u.nome ORDER BY u.nome SEPARATOR ', ') as instrutores
    FROM turmas t
    INNER JOIN cursos c ON t.curso_id = c.id
    LEFT JOIN matriculas m ON t.id = m.turma_id
    LEFT JOIN turma_instrutores ti ON t.id = ti.turma_id
    LEFT JOIN usuarios u ON ti.instrutor_id = u.id
    WHERE 1=1
";

if ($cursoFiltro) {
    $sql .= " AND t.curso_id = $cursoFiltro";
}

if ($statusFiltro) {
    $sql .= " AND t.status = '$statusFiltro'";
}

$sql .= " GROUP BY t.id, t.curso_id, t.ano, t.semestre, t.data_inicio, t.data_fim, t.data_inicio_s2, t.data_fim_s2, t.vagas, t.status, t.inscricoes_abertas, c.nome, c.tipo_periodo";
$sql .= " ORDER BY t.ano DESC, t.semestre DESC, c.nome";

$turmas = $pdo->query($sql)->fetchAll();

$cursos = $pdo->query("SELECT * FROM cursos WHERE ativo = 1 ORDER BY nome")->fetchAll();
$instrutores = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo = 'instrutor' AND ativo = 1 ORDER BY nome")->fetchAll();

$pageTitle = 'Gerenciar Turmas';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Gerenciar Turmas</h1>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">Filtros</h2>
        <button onclick="document.getElementById('modalCriar').classList.remove('hidden')" 
                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition">
            + Nova Turma
        </button>
    </div>
    
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
            <select name="curso" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <option value="">Todos os cursos</option>
                <?php foreach ($cursos as $curso): ?>
                    <option value="<?= $curso['id'] ?>" <?= $cursoFiltro == $curso['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($curso['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <option value="">Todos</option>
                <option value="ativa" <?= $statusFiltro === 'ativa' ? 'selected' : '' ?>>Ativas</option>
                <option value="fechada" <?= $statusFiltro === 'fechada' ? 'selected' : '' ?>>Fechadas</option>
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
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Período</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instrutores</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Inscrições</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($turmas)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        Nenhuma turma encontrada
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($turmas as $turma): 
                    $periodo = $turma['ano'];
                    if ($turma['semestre']) {
                        $periodo .= '/' . $turma['semestre'] . 'º Sem';
                    }
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($turma['curso_nome']) ?></div>
                        <div class="text-xs text-gray-500"><?= ucfirst($turma['tipo_periodo']) ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?= $periodo ?></div>
                        <?php if ($turma['data_inicio']): ?>
                            <div class="text-xs text-gray-500">
                                1º: <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> -
                                <?= $turma['data_fim'] ? date('d/m/Y', strtotime($turma['data_fim'])) : '...' ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($turma['data_inicio_s2'])): ?>
                            <div class="text-xs text-purple-600">
                                2º: <?= date('d/m/Y', strtotime($turma['data_inicio_s2'])) ?> -
                                <?= $turma['data_fim_s2'] ? date('d/m/Y', strtotime($turma['data_fim_s2'])) : '...' ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-600">
                            <?= $turma['instrutores'] ?: '<span class="text-gray-400">Não atribuído</span>' ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="text-sm text-gray-900"><?= $turma['total_alunos'] ?> / <?= $turma['vagas'] ?></div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <form method="POST" class="inline">
                            <input type="hidden" name="acao" value="toggle_status">
                            <input type="hidden" name="turma_id" value="<?= $turma['id'] ?>">
                            <input type="hidden" name="status" value="<?= $turma['status'] ?>">
                            <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition <?= $turma['status'] === 'ativa' ? 'bg-green-600' : 'bg-gray-300' ?>">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition <?= $turma['status'] === 'ativa' ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                            </button>
                        </form>
                        <div class="text-xs mt-1 <?= $turma['status'] === 'ativa' ? 'text-green-600' : 'text-gray-500' ?>">
                            <?= ucfirst($turma['status']) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <form method="POST" class="inline">
                            <input type="hidden" name="acao" value="toggle_inscricoes">
                            <input type="hidden" name="turma_id" value="<?= $turma['id'] ?>">
                            <input type="hidden" name="inscricoes_abertas" value="<?= $turma['inscricoes_abertas'] ?>">
                            <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition <?= $turma['inscricoes_abertas'] ? 'bg-blue-600' : 'bg-gray-300' ?>">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition <?= $turma['inscricoes_abertas'] ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                            </button>
                        </form>
                        <div class="text-xs mt-1 <?= $turma['inscricoes_abertas'] ? 'text-blue-600' : 'text-gray-500' ?>">
                            <?= $turma['inscricoes_abertas'] ? 'Abertas' : 'Fechadas' ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button onclick="editarTurma(<?= htmlspecialchars(json_encode($turma)) ?>)" 
                                class="text-blue-600 hover:text-blue-800 mr-2">
                            Editar
                        </button>
                        <button onclick="confirmarDeletar(<?= $turma['id'] ?>, '<?= htmlspecialchars($turma['curso_nome']) ?>', <?= $turma['total_alunos'] ?>)" 
                                class="text-red-600 hover:text-red-800">
                            Deletar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Criar -->
<div id="modalCriar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Nova Turma</h3>
            <button onclick="document.getElementById('modalCriar').classList.add('hidden')" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form method="POST" id="formCriar">
            <input type="hidden" name="acao" value="criar">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Curso *</label>
                    <select name="curso_id" id="curso_criar" required onchange="atualizarSemestre('criar')" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Selecione...</option>
                        <?php foreach ($cursos as $curso): ?>
                            <option value="<?= $curso['id'] ?>" data-tipo="<?= $curso['tipo_periodo'] ?>">
                                <?= htmlspecialchars($curso['nome']) ?> (<?= ucfirst($curso['tipo_periodo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ano *</label>
                        <input type="number" name="ano" value="<?= date('Y') ?>" required min="2020" max="2050" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    
                    <div id="semestre_criar_container">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Semestre</label>
                        <select name="semestre" id="semestre_criar" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">-</option>
                            <option value="1">1º Semestre</option>
                            <option value="2">2º Semestre</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Início (1º Sem)</label>
                        <input type="date" name="data_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Fim (1º Sem)</label>
                        <input type="date" name="data_fim" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>

                <div id="datas_s2_criar" class="hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2">
                        <p class="text-sm font-semibold text-purple-700 mb-3">2º Semestre (após intervalo)</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Início (2º Sem)</label>
                                <input type="date" name="data_inicio_s2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Fim (2º Sem)</label>
                                <input type="date" name="data_fim_s2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vagas *</label>
                    <input type="number" name="vagas" value="30" required min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Modalidade *</label>
                    <select name="modalidade" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="presencial">Presencial</option>
                        <option value="online">Online</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Instrutores (máx. 4)</label>
                    <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-300 rounded-lg p-3">
                        <?php foreach ($instrutores as $instrutor): ?>
                            <label class="flex items-center">
                                <input type="checkbox" name="instrutores[]" value="<?= $instrutor['id'] ?>" 
                                       class="form-checkbox h-4 w-4 text-purple-600 instrutor-checkbox-criar"
                                       onchange="limitarInstrutores('criar')">
                                <span class="ml-2 text-sm text-gray-700"><?= htmlspecialchars($instrutor['nome']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalCriar').classList.add('hidden')" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    Criar Turma
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div id="modalEditar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Editar Turma</h3>
            <button onclick="document.getElementById('modalEditar').classList.add('hidden')" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form method="POST" id="formEditar">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="id" id="edit_turma_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
                    <input type="text" id="edit_curso_nome" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ano *</label>
                        <input type="number" name="ano" id="edit_ano" required min="2020" max="2050" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    
                    <div id="semestre_editar_container">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Semestre</label>
                        <select name="semestre" id="edit_semestre" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">-</option>
                            <option value="1">1º Semestre</option>
                            <option value="2">2º Semestre</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Início (1º Sem)</label>
                        <input type="date" name="data_inicio" id="edit_data_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Fim (1º Sem)</label>
                        <input type="date" name="data_fim" id="edit_data_fim" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>

                <div id="datas_s2_editar" class="hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2">
                        <p class="text-sm font-semibold text-purple-700 mb-3">2º Semestre (após intervalo)</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Início (2º Sem)</label>
                                <input type="date" name="data_inicio_s2" id="edit_data_inicio_s2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Fim (2º Sem)</label>
                                <input type="date" name="data_fim_s2" id="edit_data_fim_s2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vagas *</label>
                    <input type="number" name="vagas" id="edit_vagas" required min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Modalidade *</label>
                    <select name="modalidade" id="edit_modalidade" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="presencial">Presencial</option>
                        <option value="online">Online</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Instrutores (máx. 4)</label>
                    <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-300 rounded-lg p-3" id="edit_instrutores_container">
                        <?php foreach ($instrutores as $instrutor): ?>
                            <label class="flex items-center">
                                <input type="checkbox" name="instrutores[]" value="<?= $instrutor['id'] ?>" 
                                       class="form-checkbox h-4 w-4 text-purple-600 instrutor-checkbox-editar"
                                       onchange="limitarInstrutores('editar')">
                                <span class="ml-2 text-sm text-gray-700"><?= htmlspecialchars($instrutor['nome']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Gestão de Alunos -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alunos Matriculados</label>
                    
                    <!-- Lista de alunos matriculados -->
                    <div class="border border-gray-300 rounded-lg p-3 mb-3">
                        <div id="alunos_matriculados_lista" class="space-y-2 max-h-40 overflow-y-auto">
                            <!-- Será preenchido via JavaScript -->
                        </div>
                        <div id="sem_alunos_msg" class="text-gray-500 text-sm text-center py-2 hidden">
                            Nenhum aluno matriculado nesta turma
                        </div>
                    </div>
                    
                    <!-- Adicionar novo aluno -->
                    <div class="flex gap-2">
                        <select id="select_novo_aluno" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">Selecione um aluno para adicionar...</option>
                        </select>
                        <button type="button" onclick="adicionarAluno()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            Adicionar
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalEditar').classList.add('hidden')" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function atualizarSemestre(tipo) {
    const select = document.getElementById('curso_' + tipo);
    const option = select.options[select.selectedIndex];
    const tipoPeriodo = option.getAttribute('data-tipo');
    const semestreSelect = document.getElementById('semestre_' + tipo);
    const datasS2 = document.getElementById('datas_s2_' + tipo);

    if (tipoPeriodo === 'semestral') {
        semestreSelect.required = true;
        semestreSelect.parentElement.classList.remove('hidden');
        if (datasS2) datasS2.classList.add('hidden');
    } else if (tipoPeriodo === 'anual') {
        semestreSelect.required = false;
        semestreSelect.value = '';
        semestreSelect.parentElement.classList.add('hidden');
        if (datasS2) datasS2.classList.remove('hidden');
    } else {
        semestreSelect.required = false;
        semestreSelect.value = '';
        semestreSelect.parentElement.classList.add('hidden');
        if (datasS2) datasS2.classList.add('hidden');
    }
}

function limitarInstrutores(tipo) {
    const checkboxes = document.querySelectorAll('.instrutor-checkbox-' + tipo);
    const checked = Array.from(checkboxes).filter(cb => cb.checked);
    
    if (checked.length >= 4) {
        checkboxes.forEach(cb => {
            if (!cb.checked) {
                cb.disabled = true;
            }
        });
    } else {
        checkboxes.forEach(cb => cb.disabled = false);
    }
}

function editarTurma(turma) {
    document.getElementById('edit_turma_id').value = turma.id;
    document.getElementById('edit_curso_nome').value = turma.curso_nome;
    document.getElementById('edit_ano').value = turma.ano;
    document.getElementById('edit_semestre').value = turma.semestre || '';
    document.getElementById('edit_data_inicio').value = turma.data_inicio || '';
    document.getElementById('edit_data_fim').value = turma.data_fim || '';
    document.getElementById('edit_data_inicio_s2').value = turma.data_inicio_s2 || '';
    document.getElementById('edit_data_fim_s2').value = turma.data_fim_s2 || '';
    document.getElementById('edit_vagas').value = turma.vagas;
    document.getElementById('edit_modalidade').value = turma.modalidade || 'presencial';

    if (turma.tipo_periodo === 'anual') {
        document.getElementById('semestre_editar_container').classList.add('hidden');
        document.getElementById('datas_s2_editar').classList.remove('hidden');
    } else {
        document.getElementById('semestre_editar_container').classList.remove('hidden');
        document.getElementById('datas_s2_editar').classList.add('hidden');
    }
    
    fetch(`/gestor/api_turma_instrutores.php?turma_id=${turma.id}`)
        .then(response => response.json())
        .then(instrutores => {
            document.querySelectorAll('.instrutor-checkbox-editar').forEach(cb => {
                cb.checked = instrutores.includes(parseInt(cb.value));
            });
            limitarInstrutores('editar');
        });
    
    // Carregar alunos da turma
    carregarAlunosTurma(turma.id);
    
    document.getElementById('modalEditar').classList.remove('hidden');
}

function confirmarDeletar(turmaId, cursoNome, totalAlunos) {
    if (totalAlunos > 0) {
        alert(`Não é possível deletar a turma "${cursoNome}" pois ela possui ${totalAlunos} aluno(s) matriculado(s).`);
        return;
    }
    
    if (confirm(`Tem certeza que deseja DELETAR PERMANENTEMENTE a turma "${cursoNome}"?\n\nEsta ação não pode ser desfeita!`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="acao" value="deletar">
            <input type="hidden" name="id" value="${turmaId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

let turmaAtualId = null;

function carregarAlunosTurma(turmaId) {
    turmaAtualId = turmaId;
    
    console.log('Carregando alunos da turma:', turmaId);
    
    fetch(`/gestor/api_alunos_turma.php?turma_id=${turmaId}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`Erro na resposta do servidor: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            try {
                const data = JSON.parse(text);
                if (data.error) {
                    throw new Error(data.message || data.error);
                }
                return data;
            } catch (e) {
                console.error('Erro ao fazer parse do JSON:', e);
                throw new Error('Resposta inválida do servidor: ' + text.substring(0, 100));
            }
        })
        .then(data => {
            console.log('Dados recebidos:', data);
            
            const listaMatriculados = document.getElementById('alunos_matriculados_lista');
            const semAlunosMsg = document.getElementById('sem_alunos_msg');
            const selectNovoAluno = document.getElementById('select_novo_aluno');
            
            // Limpar listas
            listaMatriculados.innerHTML = '';
            selectNovoAluno.innerHTML = '<option value="">Selecione um aluno para adicionar...</option>';
            
            // Preencher alunos matriculados
            if (data.matriculados && data.matriculados.length > 0) {
                semAlunosMsg.classList.add('hidden');
                listaMatriculados.classList.remove('hidden');
                
                data.matriculados.forEach(aluno => {
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between p-2 bg-gray-50 rounded';
                    
                    const nomeSeguro = String(aluno.nome).replace(/'/g, "\\'");
                    const emailTexto = aluno.email ? String(aluno.email) : '';
                    
                    div.innerHTML = `
                        <div>
                            <span class="font-medium text-gray-900">${aluno.nome}</span>
                            <span class="text-sm text-gray-500 ml-2">${emailTexto}</span>
                        </div>
                        <button type="button" onclick="removerAluno(${aluno.id}, '${nomeSeguro}')" 
                                class="text-red-600 hover:text-red-800 font-bold text-lg leading-none">
                            ×
                        </button>
                    `;
                    listaMatriculados.appendChild(div);
                });
            } else {
                semAlunosMsg.classList.remove('hidden');
                listaMatriculados.classList.add('hidden');
            }
            
            // Preencher alunos disponíveis
            if (data.disponiveis && data.disponiveis.length > 0) {
                data.disponiveis.forEach(aluno => {
                    const option = document.createElement('option');
                    option.value = aluno.id;
                    const emailTexto = aluno.email ? ' (' + aluno.email + ')' : '';
                    option.textContent = aluno.nome + emailTexto;
                    selectNovoAluno.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Erro ao carregar alunos:', error);
            alert('Erro ao carregar lista de alunos: ' + error.message);
        });
}

function removerAluno(alunoId, nomeAluno) {
    if (!confirm(`Tem certeza que deseja remover ${nomeAluno} desta turma?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('acao', 'remover_aluno');
    formData.append('turma_id', turmaAtualId);
    formData.append('aluno_id', alunoId);
    
    fetch('/gestor/turmas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            carregarAlunosTurma(turmaAtualId);
            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao remover aluno');
    });
}

function adicionarAluno() {
    const select = document.getElementById('select_novo_aluno');
    const alunoId = select.value;
    
    if (!alunoId) {
        alert('Selecione um aluno para adicionar');
        return;
    }
    
    const formData = new FormData();
    formData.append('acao', 'adicionar_aluno');
    formData.append('turma_id', turmaAtualId);
    formData.append('aluno_id', alunoId);
    
    fetch('/gestor/turmas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            carregarAlunosTurma(turmaAtualId);
            select.value = '';
            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao adicionar aluno');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
