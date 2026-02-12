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
        $email = sanitize($_POST['email']);
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $tipo = sanitize($_POST['tipo']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $senha, $tipo]);
            setFlashMessage('Usuário criado com sucesso!', 'success');
        } catch (PDOException $e) {
            setFlashMessage('Erro ao criar usuário: ' . $e->getMessage(), 'error');
        }
        redirect('/gestor/usuarios.php');
    }
    
    if ($acao === 'editar') {
        $id = (int)$_POST['id'];
        $nome = sanitize($_POST['nome']);
        $email = sanitize($_POST['email']);
        $tipo = sanitize($_POST['tipo']);
        
        $sql = "UPDATE usuarios SET nome = ?, email = ?, tipo = ?";
        $params = [$nome, $email, $tipo];
        
        if (!empty($_POST['senha'])) {
            $sql .= ", senha = ?";
            $params[] = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            setFlashMessage('Usuário atualizado com sucesso!', 'success');
        } catch (PDOException $e) {
            setFlashMessage('Erro ao atualizar usuário: ' . $e->getMessage(), 'error');
        }
        redirect('/gestor/usuarios.php');
    }
    
    if ($acao === 'desativar') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage('Usuário desativado com sucesso!', 'success');
        redirect('/gestor/usuarios.php');
    }
}

$usuarios = $pdo->query("SELECT * FROM usuarios WHERE ativo = 1 ORDER BY nome")->fetchAll();

$pageTitle = 'Gerenciar Usuários - Gestor';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Gerenciar Usuários</h1>
    <button onclick="abrirModal('modalCriar')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
        + Novo Usuário
    </button>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($usuarios as $usuario): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($usuario['nome']) ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-600"><?= htmlspecialchars($usuario['email']) ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?= $usuario['tipo'] === 'gestor' ? 'bg-red-100 text-red-800' : 
                                ($usuario['tipo'] === 'diretor' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                            <?= ucfirst($usuario['tipo']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button onclick='editarUsuario(<?= json_encode($usuario) ?>)' 
                            class="text-blue-600 hover:text-blue-900 mr-3">Editar</button>
                        <button onclick="confirmarDesativar(<?= $usuario['id'] ?>)" 
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
            <h3 class="text-lg font-bold">Novo Usuário</h3>
            <button onclick="fecharModal('modalCriar')" class="text-gray-600 hover:text-gray-900">&times;</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="acao" value="criar">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                <input type="password" name="senha" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select name="tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="diretor">Diretor</option>
                    <option value="instrutor">Instrutor</option>
                    <option value="gestor">Gestor</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">
                Criar Usuário
            </button>
        </form>
    </div>
</div>

<div id="modalEditar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Editar Usuário</h3>
            <button onclick="fecharModal('modalEditar')" class="text-gray-600 hover:text-gray-900">&times;</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input type="text" name="nome" id="edit_nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="edit_email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha (deixe em branco para não alterar)</label>
                <input type="password" name="senha" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select name="tipo" id="edit_tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="diretor">Diretor</option>
                    <option value="instrutor">Instrutor</option>
                    <option value="gestor">Gestor</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">
                Atualizar Usuário
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

function editarUsuario(usuario) {
    document.getElementById('edit_id').value = usuario.id;
    document.getElementById('edit_nome').value = usuario.nome;
    document.getElementById('edit_email').value = usuario.email;
    document.getElementById('edit_tipo').value = usuario.tipo;
    abrirModal('modalEditar');
}

function confirmarDesativar(id) {
    if (confirm('Tem certeza que deseja desativar este usuário?')) {
        document.getElementById('desativar_id').value = id;
        document.getElementById('formDesativar').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
