<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:02:00
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(['gestor']);

$pdo = getConnection();
$pageTitle = 'Gerenciar Instrutores';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'criar' || $acao === 'editar') {
        $id = $_POST['id'] ?? null;
        $nome = sanitize($_POST['nome']);
        $email = sanitize($_POST['email']);
        $senha = $_POST['senha'] ?? '';
        
        $foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto = uploadFoto($_FILES['foto'], 'instrutores');
            if (!$foto) {
                setFlashMessage('Erro ao fazer upload da foto. Verifique o formato do arquivo.', 'error');
                redirect('/gestor/instrutores.php');
            }
        }
        
        try {
            if ($acao === 'criar') {
                if (empty($senha)) {
                    throw new Exception('Senha é obrigatória para novo instrutor');
                }
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo, foto) VALUES (?, ?, ?, 'instrutor', ?)");
                $stmt->execute([$nome, $email, $senhaHash, $foto]);
                setFlashMessage('Instrutor criado com sucesso!', 'success');
            } else {
                if (!empty($senha)) {
                    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                    if ($foto) {
                        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ?, foto = ? WHERE id = ? AND tipo = 'instrutor'");
                        $stmt->execute([$nome, $email, $senhaHash, $foto, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ? AND tipo = 'instrutor'");
                        $stmt->execute([$nome, $email, $senhaHash, $id]);
                    }
                } else {
                    if ($foto) {
                        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, foto = ? WHERE id = ? AND tipo = 'instrutor'");
                        $stmt->execute([$nome, $email, $foto, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ? AND tipo = 'instrutor'");
                        $stmt->execute([$nome, $email, $id]);
                    }
                }
                setFlashMessage('Instrutor atualizado com sucesso!', 'success');
            }
        } catch (Exception $e) {
            setFlashMessage('Erro: ' . $e->getMessage(), 'error');
        }
        
        redirect('/gestor/instrutores.php');
    }
    
    if ($acao === 'desativar') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ? AND tipo = 'instrutor'");
        $stmt->execute([$id]);
        setFlashMessage('Instrutor desativado com sucesso!', 'success');
        redirect('/gestor/instrutores.php');
    }
}

$stmt = $pdo->query("SELECT * FROM usuarios WHERE tipo = 'instrutor' ORDER BY ativo DESC, nome ASC");
$instrutores = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <h2 class="text-3xl font-bold text-gray-800">Gerenciar Instrutores</h2>
    <button onclick="abrirModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition font-semibold">
        + Novo Instrutor
    </button>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Foto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($instrutores as $instrutor): ?>
                <tr>
                    <td class="px-6 py-4">
                        <?php if (isset($instrutor['foto']) && $instrutor['foto']): ?>
                            <img src="/assets/uploads/<?= htmlspecialchars($instrutor['foto']) ?>" alt="Foto" class="w-12 h-12 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold text-lg shadow-md">
                                <?= strtoupper(substr($instrutor['nome'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($instrutor['nome']) ?></td>
                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($instrutor['email']) ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full <?= $instrutor['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= $instrutor['ativo'] ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm space-x-2">
                        <button onclick='editarInstrutor(<?= json_encode($instrutor) ?>)' class="text-blue-600 hover:text-blue-800">
                            Editar
                        </button>
                        <?php if ($instrutor['ativo']): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Desativar este instrutor?')">
                            <input type="hidden" name="acao" value="desativar">
                            <input type="hidden" name="id" value="<?= $instrutor['id'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800">Desativar</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-40">
    <div class="bg-white rounded-lg max-w-2xl w-full p-6">
        <h3 id="modalTitle" class="text-2xl font-bold mb-4">Novo Instrutor</h3>
        <form method="POST" enctype="multipart/form-data" id="formInstrutor">
            <input type="hidden" name="acao" id="acao" value="criar">
            <input type="hidden" name="id" id="id">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Foto do Instrutor (150x150px)</label>
                <input type="file" name="foto" id="fotoInput" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <img id="fotoPreview" class="hidden mt-2 w-32 h-32 rounded-full object-cover border-2 border-gray-300">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Nome *</label>
                <input type="text" name="nome" id="nome" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                <input type="email" name="email" id="email" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Senha <span id="senhaOpcional" class="text-gray-500 text-xs">(deixe em branco para manter a atual)</span></label>
                <input type="password" name="senha" id="senha" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="fecharModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition font-semibold">
                    Cancelar
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition font-semibold">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/crop-modal.php'; ?>

<script>
function abrirModal() {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Novo Instrutor';
    document.getElementById('acao').value = 'criar';
    document.getElementById('formInstrutor').reset();
    document.getElementById('id').value = '';
    document.getElementById('fotoPreview').classList.add('hidden');
    document.getElementById('senhaOpcional').classList.add('hidden');
    document.getElementById('senha').required = true;
}

function fecharModal() {
    document.getElementById('modal').classList.add('hidden');
}

function editarInstrutor(instrutor) {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Editar Instrutor';
    document.getElementById('acao').value = 'editar';
    document.getElementById('id').value = instrutor.id;
    document.getElementById('nome').value = instrutor.nome;
    document.getElementById('email').value = instrutor.email;
    document.getElementById('senha').value = '';
    document.getElementById('senha').required = false;
    document.getElementById('senhaOpcional').classList.remove('hidden');
    
    if (instrutor.foto && instrutor.foto !== null) {
        document.getElementById('fotoPreview').src = '/assets/uploads/' + instrutor.foto;
        document.getElementById('fotoPreview').classList.remove('hidden');
    } else {
        document.getElementById('fotoPreview').classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initImageCrop('fotoInput', 'fotoPreview', 150, 150);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
