<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('gestor');

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'criar') {
        $nome = sanitize($_POST['nome']);
        $descricao = sanitize($_POST['descricao'] ?? '');
        $preRequisito = sanitize($_POST['pre_requisito'] ?? '');
        $cargaHoraria = (int)$_POST['carga_horaria'];
        $tipoPeriodo = $_POST['tipo_periodo'];
        $diasSemana = isset($_POST['dias_semana']) ? json_encode($_POST['dias_semana']) : null;
        $horarioInicio = !empty($_POST['horario_inicio']) ? $_POST['horario_inicio'] : null;
        $horarioFim = !empty($_POST['horario_fim']) ? $_POST['horario_fim'] : null;
        
        $capa = null;
        if (isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
            $capa = uploadFoto($_FILES['capa'], 'cursos');
            if (!$capa) {
                setFlashMessage('Erro ao fazer upload da capa. Verifique o formato do arquivo.', 'error');
                redirect('/gestor/cursos.php');
            }
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO cursos (nome, descricao, pre_requisito, dias_semana, horario_inicio, horario_fim, carga_horaria, tipo_periodo, capa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $descricao, $preRequisito, $diasSemana, $horarioInicio, $horarioFim, $cargaHoraria, $tipoPeriodo, $capa]);
            setFlashMessage('Curso criado com sucesso! Agora crie turmas para este curso.', 'success');
        } catch (PDOException $e) {
            setFlashMessage('Erro ao criar curso: ' . $e->getMessage(), 'error');
        }
        redirect('/gestor/cursos.php');
    }
    
    if ($acao === 'editar') {
        $id = (int)$_POST['id'];
        $nome = sanitize($_POST['nome']);
        $descricao = sanitize($_POST['descricao'] ?? '');
        $preRequisito = sanitize($_POST['pre_requisito'] ?? '');
        $cargaHoraria = (int)$_POST['carga_horaria'];
        $tipoPeriodo = $_POST['tipo_periodo'];
        $diasSemana = isset($_POST['dias_semana']) ? json_encode($_POST['dias_semana']) : null;
        $horarioInicio = !empty($_POST['horario_inicio']) ? $_POST['horario_inicio'] : null;
        $horarioFim = !empty($_POST['horario_fim']) ? $_POST['horario_fim'] : null;
        
        $sql = "UPDATE cursos SET nome = ?, descricao = ?, pre_requisito = ?, dias_semana = ?, horario_inicio = ?, horario_fim = ?, carga_horaria = ?, tipo_periodo = ?";
        $params = [$nome, $descricao, $preRequisito, $diasSemana, $horarioInicio, $horarioFim, $cargaHoraria, $tipoPeriodo];
        
        if (isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
            $capa = uploadFoto($_FILES['capa'], 'cursos');
            if ($capa) {
                $sql .= ", capa = ?";
                $params[] = $capa;
            }
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            setFlashMessage('Curso atualizado com sucesso!', 'success');
        } catch (PDOException $e) {
            setFlashMessage('Erro ao atualizar curso: ' . $e->getMessage(), 'error');
        }
        redirect('/gestor/cursos.php');
    }
    
    if ($acao === 'desativar') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE cursos SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage('Curso desativado com sucesso!', 'success');
        redirect('/gestor/cursos.php');
    }
}

$cursos = $pdo->query("
    SELECT c.*,
           COUNT(DISTINCT t.id) as total_turmas
    FROM cursos c
    LEFT JOIN turmas t ON c.id = t.curso_id
    WHERE c.ativo = 1
    GROUP BY c.id
    ORDER BY c.nome
")->fetchAll();

$pageTitle = 'Gerenciar Cursos - Gestor';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Gerenciar Cursos</h1>
    <button onclick="abrirModal('modalCriar')" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition">
        + Novo Curso
    </button>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome do Curso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Carga Horária</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Turmas</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($cursos as $curso): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($curso['capa']): ?>
                            <img src="/assets/uploads/<?= htmlspecialchars($curso['capa']) ?>" alt="Capa" class="h-16 w-12 object-cover rounded shadow-sm">
                        <?php else: ?>
                            <div class="h-16 w-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded shadow-sm flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($curso['nome']) ?></div>
                        <?php if ($curso['descricao']): ?>
                            <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars(substr($curso['descricao'], 0, 60)) ?><?= strlen($curso['descricao']) > 60 ? '...' : '' ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $curso['tipo_periodo'] === 'anual' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                            <?= ucfirst($curso['tipo_periodo']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="text-sm text-gray-900"><?= $curso['carga_horaria'] ?>h</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <a href="/gestor/turmas.php?curso=<?= $curso['id'] ?>" class="text-sm font-semibold text-purple-600 hover:text-purple-800">
                            <?= $curso['total_turmas'] ?> turma<?= $curso['total_turmas'] != 1 ? 's' : '' ?>
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                        <button onclick='editarCurso(<?= json_encode($curso) ?>)' 
                            class="text-blue-600 hover:text-blue-900 mr-3">Editar</button>
                        <button onclick="confirmarDesativar(<?= $curso['id'] ?>)" 
                            class="text-red-600 hover:text-red-900">Desativar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalCriar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Novo Curso</h3>
            <button onclick="fecharModal('modalCriar')" class="text-gray-600 hover:text-gray-900">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="acao" value="criar">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Curso *</label>
                <input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea name="descricao" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pré-requisito</label>
                <textarea name="pre_requisito" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Ex: Conhecimento básico de doutrina espírita"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Dias da Semana</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="segunda" class="form-checkbox h-4 w-4 text-purple-600">
                        <span class="ml-2 text-sm text-gray-700">Segunda</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="terca" class="form-checkbox h-4 w-4 text-purple-600">
                        <span class="ml-2 text-sm text-gray-700">Terça</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="quarta" class="form-checkbox h-4 w-4 text-purple-600">
                        <span class="ml-2 text-sm text-gray-700">Quarta</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="quinta" class="form-checkbox h-4 w-4 text-purple-600">
                        <span class="ml-2 text-sm text-gray-700">Quinta</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="sexta" class="form-checkbox h-4 w-4 text-purple-600">
                        <span class="ml-2 text-sm text-gray-700">Sexta</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="sabado" class="form-checkbox h-4 w-4 text-purple-600">
                        <span class="ml-2 text-sm text-gray-700">Sábado</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="domingo" class="form-checkbox h-4 w-4 text-purple-600">
                        <span class="ml-2 text-sm text-gray-700">Domingo</span>
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Horário Início</label>
                    <input type="time" name="horario_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Horário Fim</label>
                    <input type="time" name="horario_fim" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Carga Horária *</label>
                    <input type="number" name="carga_horaria" required min="1" value="40" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Período *</label>
                    <select name="tipo_periodo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="semestral">Semestral</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capa do Curso (600x1000px - Formato Livro)</label>
                <input type="file" name="capa" id="capaInputCriar" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <img id="capaPreviewCriar" class="hidden mt-2 w-full max-w-xs rounded-lg object-cover border-2 border-gray-300">
            </div>
            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg transition">
                Criar Curso
            </button>
        </form>
    </div>
</div>

<div id="modalEditar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Editar Curso</h3>
            <button onclick="fecharModal('modalEditar')" class="text-gray-600 hover:text-gray-900">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Curso *</label>
                <input type="text" name="nome" id="edit_nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea name="descricao" id="edit_descricao" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pré-requisito</label>
                <textarea name="pre_requisito" id="edit_pre_requisito" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Dias da Semana</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="segunda" class="form-checkbox h-4 w-4 text-purple-600 edit-dia-semana" data-dia="segunda">
                        <span class="ml-2 text-sm text-gray-700">Segunda</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="terca" class="form-checkbox h-4 w-4 text-purple-600 edit-dia-semana" data-dia="terca">
                        <span class="ml-2 text-sm text-gray-700">Terça</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="quarta" class="form-checkbox h-4 w-4 text-purple-600 edit-dia-semana" data-dia="quarta">
                        <span class="ml-2 text-sm text-gray-700">Quarta</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="quinta" class="form-checkbox h-4 w-4 text-purple-600 edit-dia-semana" data-dia="quinta">
                        <span class="ml-2 text-sm text-gray-700">Quinta</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="sexta" class="form-checkbox h-4 w-4 text-purple-600 edit-dia-semana" data-dia="sexta">
                        <span class="ml-2 text-sm text-gray-700">Sexta</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="sabado" class="form-checkbox h-4 w-4 text-purple-600 edit-dia-semana" data-dia="sabado">
                        <span class="ml-2 text-sm text-gray-700">Sábado</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="dias_semana[]" value="domingo" class="form-checkbox h-4 w-4 text-purple-600 edit-dia-semana" data-dia="domingo">
                        <span class="ml-2 text-sm text-gray-700">Domingo</span>
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Horário Início</label>
                    <input type="time" name="horario_inicio" id="edit_horario_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Horário Fim</label>
                    <input type="time" name="horario_fim" id="edit_horario_fim" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Carga Horária *</label>
                    <input type="number" name="carga_horaria" id="edit_carga_horaria" required min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Período *</label>
                    <select name="tipo_periodo" id="edit_tipo_periodo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="semestral">Semestral</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nova Capa (600x1000px - Formato Livro - deixe em branco para não alterar)</label>
                <input type="file" name="capa" id="capaInputEditar" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <img id="capaPreviewEditar" class="hidden mt-2 w-full max-w-xs rounded-lg object-cover border-2 border-gray-300">
            </div>
            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg transition">
                Atualizar Curso
            </button>
        </form>
    </div>
</div>

<form id="formDesativar" method="POST" class="hidden">
    <input type="hidden" name="acao" value="desativar">
    <input type="hidden" name="id" id="desativar_id">
</form>

<script>
function abrirModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function fecharModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function editarCurso(curso) {
    document.getElementById('edit_id').value = curso.id;
    document.getElementById('edit_nome').value = curso.nome;
    document.getElementById('edit_descricao').value = curso.descricao || '';
    document.getElementById('edit_pre_requisito').value = curso.pre_requisito || '';
    document.getElementById('edit_carga_horaria').value = curso.carga_horaria || 40;
    document.getElementById('edit_tipo_periodo').value = curso.tipo_periodo || 'semestral';
    
    document.querySelectorAll('.edit-dia-semana').forEach(cb => cb.checked = false);
    if (curso.dias_semana) {
        try {
            const dias = JSON.parse(curso.dias_semana);
            dias.forEach(dia => {
                const checkbox = document.querySelector(`.edit-dia-semana[data-dia="${dia}"]`);
                if (checkbox) checkbox.checked = true;
            });
        } catch (e) {}
    }
    
    document.getElementById('edit_horario_inicio').value = curso.horario_inicio || '';
    document.getElementById('edit_horario_fim').value = curso.horario_fim || '';
    
    abrirModal('modalEditar');
}

function confirmarDesativar(id) {
    if (confirm('Tem certeza que deseja desativar este curso?')) {
        document.getElementById('desativar_id').value = id;
        document.getElementById('formDesativar').submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initImageCrop('capaInputCriar', 'capaPreviewCriar', 600, 1000);
    initImageCrop('capaInputEditar', 'capaPreviewEditar', 600, 1000);
});
</script>

<?php require_once __DIR__ . '/../includes/crop-modal.php'; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
