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
        $endereco = sanitize($_POST['endereco']);
        $dataNascimento = !empty($_POST['data_nascimento']) ? sanitize($_POST['data_nascimento']) : null;
        
        $foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto = uploadFoto($_FILES['foto']);
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO alunos (nome, foto, endereco, data_nascimento) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $foto, $endereco, $dataNascimento]);
            setFlashMessage('Aluno cadastrado com sucesso!', 'success');
        } catch (PDOException $e) {
            setFlashMessage('Erro ao cadastrar aluno: ' . $e->getMessage(), 'error');
        }
        redirect('/gestor/alunos.php');
    }
    
    if ($acao === 'editar') {
        $id = (int)$_POST['id'];
        $nome = sanitize($_POST['nome']);
        $endereco = sanitize($_POST['endereco']);
        $dataNascimento = !empty($_POST['data_nascimento']) ? sanitize($_POST['data_nascimento']) : null;
        
        $sql = "UPDATE alunos SET nome = ?, endereco = ?, data_nascimento = ?";
        $params = [$nome, $endereco, $dataNascimento];
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto = uploadFoto($_FILES['foto']);
            if ($foto) {
                $sql .= ", foto = ?";
                $params[] = $foto;
            }
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            setFlashMessage('Aluno atualizado com sucesso!', 'success');
        } catch (PDOException $e) {
            setFlashMessage('Erro ao atualizar aluno: ' . $e->getMessage(), 'error');
        }
        redirect('/gestor/alunos.php');
    }
    
    if ($acao === 'desativar') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE alunos SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage('Aluno desativado com sucesso!', 'success');
        redirect('/gestor/alunos.php');
    }
}

$alunos = $pdo->query("SELECT * FROM alunos WHERE ativo = 1 ORDER BY nome")->fetchAll();

$pageTitle = 'Gerenciar Alunos - Gestor';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Gerenciar Alunos</h1>
    <div class="flex gap-3">
        <a href="/gestor/seed_alunos.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition text-sm">
            🌱 Seed (50 alunos)
        </a>
        <button onclick="abrirModal('modalCriar')" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">
            + Novo Aluno
        </button>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Idade</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Nascimento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Endereço</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($alunos as $aluno): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($aluno['foto']): ?>
                            <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="h-10 w-10 rounded-full object-cover">
                        <?php else: ?>
                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center text-white font-bold">
                                <?= strtoupper(substr($aluno['nome'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($aluno['nome']) ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-600">
                            <?= $aluno['data_nascimento'] ? calcularIdade($aluno['data_nascimento']) . ' anos' : '<span class="text-gray-400">Não informado</span>' ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-600">
                            <?= $aluno['data_nascimento'] ? formatarData($aluno['data_nascimento']) : '<span class="text-gray-400">Não informado</span>' ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-600"><?= htmlspecialchars($aluno['endereco']) ?></div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <a href="/aluno_perfil.php?id=<?= $aluno['id'] ?>" class="text-purple-600 hover:text-purple-800 font-medium">
                                Ver Perfil
                            </a>
                            <button onclick='editarAluno(<?= json_encode($aluno) ?>)' 
                                class="text-blue-600 hover:text-blue-900 mr-3">Editar</button>
                            <button onclick="confirmarDesativar(<?= $aluno['id'] ?>)" 
                                class="text-red-600 hover:text-red-900">Desativar</button>
                        </div>
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
            <h3 class="text-lg font-bold">Novo Aluno</h3>
            <button onclick="fecharModal('modalCriar')" class="text-gray-600 hover:text-gray-900">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="acao" value="criar">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento (opcional)</label>
                <input type="date" name="data_nascimento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                <textarea name="endereco" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto do Aluno (150x150px)</label>
                <input type="file" name="foto" id="fotoInputCriar" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                <img id="fotoPreviewCriar" class="hidden mt-2 w-32 h-32 rounded-full object-cover border-2 border-gray-300">
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition">
                Cadastrar Aluno
            </button>
        </form>
    </div>
</div>

<div id="modalEditar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Editar Aluno</h3>
            <button onclick="fecharModal('modalEditar')" class="text-gray-600 hover:text-gray-900">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input type="text" name="nome" id="edit_nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento (opcional)</label>
                <input type="date" name="data_nascimento" id="edit_data_nascimento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                <textarea name="endereco" id="edit_endereco" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nova Foto (150x150px - deixe em branco para não alterar)</label>
                <input type="file" name="foto" id="fotoInputEditar" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                <img id="fotoPreviewEditar" class="hidden mt-2 w-32 h-32 rounded-full object-cover border-2 border-gray-300">
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition">
                Atualizar Aluno
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

function editarAluno(aluno) {
    document.getElementById('edit_id').value = aluno.id;
    document.getElementById('edit_nome').value = aluno.nome;
    document.getElementById('edit_data_nascimento').value = aluno.data_nascimento;
    document.getElementById('edit_endereco').value = aluno.endereco;
    abrirModal('modalEditar');
}

function confirmarDesativar(id) {
    if (confirm('Tem certeza que deseja desativar este aluno?')) {
        document.getElementById('desativar_id').value = id;
        document.getElementById('formDesativar').submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initImageCrop('fotoInputCriar', 'fotoPreviewCriar', 150, 150);
    initImageCrop('fotoInputEditar', 'fotoPreviewEditar', 150, 150);
});
</script>

<?php require_once __DIR__ . '/../includes/crop-modal.php'; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
