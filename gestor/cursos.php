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
        $ano = (int)$_POST['ano'];
        $instrutoresIds = $_POST['instrutores_ids'] ?? [];
        
        $capa = null;
        if (isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
            $capa = uploadFoto($_FILES['capa'], 'cursos');
            if (!$capa) {
                setFlashMessage('Erro ao fazer upload da capa. Verifique o formato do arquivo.', 'error');
                redirect('/gestor/cursos.php');
            }
        }
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO cursos (nome, ano, capa) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $ano, $capa]);
            $cursoId = $pdo->lastInsertId();
            
            // Adicionar instrutores (máximo 4)
            $instrutoresIds = array_slice(array_filter($instrutoresIds), 0, 4);
            foreach ($instrutoresIds as $instrutorId) {
                $stmt = $pdo->prepare("INSERT INTO curso_instrutores (curso_id, instrutor_id) VALUES (?, ?)");
                $stmt->execute([$cursoId, (int)$instrutorId]);
            }
            
            $pdo->commit();
            setFlashMessage('Curso criado com sucesso!', 'success');
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlashMessage('Erro ao criar curso: ' . $e->getMessage(), 'error');
        }
        redirect('/gestor/cursos.php');
    }
    
    if ($acao === 'editar') {
        $id = (int)$_POST['id'];
        $nome = sanitize($_POST['nome']);
        $ano = (int)$_POST['ano'];
        $instrutoresIds = $_POST['instrutores_ids'] ?? [];
        
        $sql = "UPDATE cursos SET nome = ?, ano = ?";
        $params = [$nome, $ano];
        
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
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Remover instrutores antigos
            $pdo->prepare("DELETE FROM curso_instrutores WHERE curso_id = ?")->execute([$id]);
            
            // Adicionar novos instrutores (máximo 4)
            $instrutoresIds = array_slice(array_filter($instrutoresIds), 0, 4);
            foreach ($instrutoresIds as $instrutorId) {
                $stmt = $pdo->prepare("INSERT INTO curso_instrutores (curso_id, instrutor_id) VALUES (?, ?)");
                $stmt->execute([$id, (int)$instrutorId]);
            }
            
            $pdo->commit();
            setFlashMessage('Curso atualizado com sucesso!', 'success');
        } catch (PDOException $e) {
            $pdo->rollBack();
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
           GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') as instrutores_nomes,
           GROUP_CONCAT(u.id ORDER BY u.nome SEPARATOR ',') as instrutores_ids
    FROM cursos c
    LEFT JOIN curso_instrutores ci ON c.id = ci.curso_id
    LEFT JOIN usuarios u ON ci.instrutor_id = u.id
    WHERE c.ativo = 1
    GROUP BY c.id
    ORDER BY c.ano DESC, c.nome
")->fetchAll();

$instrutores = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo = 'instrutor' AND ativo = 1 ORDER BY nome")->fetchAll();

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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instrutor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
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
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="/diretor/curso_detalhes.php?id=<?= $curso['id'] ?>" class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline">
                            <?= htmlspecialchars($curso['nome']) ?>
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-600"><?= $curso['ano'] ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-600">
                            <?= $curso['instrutores_nomes'] ? htmlspecialchars($curso['instrutores_nomes']) : '<span class="text-gray-400">Não atribuído</span>' ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Curso</label>
                <input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                <input type="number" name="ano" required min="2000" max="2100" value="<?= date('Y') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Instrutores (selecione de 1 a 4)</label>
                <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-300 rounded-lg p-3">
                    <?php foreach ($instrutores as $instrutor): ?>
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                            <input type="checkbox" name="instrutores_ids[]" value="<?= $instrutor['id'] ?>" 
                                   class="instrutor-checkbox rounded text-purple-600 focus:ring-purple-500"
                                   onchange="limitarInstrutores(this)">
                            <span class="text-sm"><?= htmlspecialchars($instrutor['nome']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-1">Máximo de 4 instrutores por curso</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capa do Curso (1080x1350px)</label>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Curso</label>
                <input type="text" name="nome" id="edit_nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                <input type="number" name="ano" id="edit_ano" required min="2000" max="2100" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Instrutores (selecione de 1 a 4)</label>
                <div id="edit_instrutores_container" class="space-y-2 max-h-40 overflow-y-auto border border-gray-300 rounded-lg p-3">
                    <?php foreach ($instrutores as $instrutor): ?>
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                            <input type="checkbox" name="instrutores_ids[]" value="<?= $instrutor['id'] ?>" 
                                   class="instrutor-checkbox-edit rounded text-purple-600 focus:ring-purple-500"
                                   onchange="limitarInstrutoresEdit(this)">
                            <span class="text-sm"><?= htmlspecialchars($instrutor['nome']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-1">Máximo de 4 instrutores por curso</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nova Capa (1080x1350px - deixe em branco para não alterar)</label>
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
    document.getElementById('edit_ano').value = curso.ano;
    
    // Desmarcar todos os checkboxes
    document.querySelectorAll('.instrutor-checkbox-edit').forEach(cb => cb.checked = false);
    
    // Marcar instrutores do curso
    if (curso.instrutores_ids) {
        const instrutoresIds = curso.instrutores_ids.split(',');
        instrutoresIds.forEach(id => {
            const checkbox = document.querySelector(`.instrutor-checkbox-edit[value="${id}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    abrirModal('modalEditar');
}

function limitarInstrutores(checkbox) {
    const checkboxes = document.querySelectorAll('.instrutor-checkbox');
    const selecionados = Array.from(checkboxes).filter(cb => cb.checked);
    
    if (selecionados.length > 4) {
        checkbox.checked = false;
        alert('Você pode selecionar no máximo 4 instrutores por curso.');
    }
}

function limitarInstrutoresEdit(checkbox) {
    const checkboxes = document.querySelectorAll('.instrutor-checkbox-edit');
    const selecionados = Array.from(checkboxes).filter(cb => cb.checked);
    
    if (selecionados.length > 4) {
        checkbox.checked = false;
        alert('Você pode selecionar no máximo 4 instrutores por curso.');
    }
}

function confirmarDesativar(id) {
    if (confirm('Tem certeza que deseja desativar este curso?')) {
        document.getElementById('desativar_id').value = id;
        document.getElementById('formDesativar').submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initImageCrop('capaInputCriar', 'capaPreviewCriar', 1080, 1350);
    initImageCrop('capaInputEditar', 'capaPreviewEditar', 1080, 1350);
});
</script>

<?php require_once __DIR__ . '/../includes/crop-modal.php'; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
