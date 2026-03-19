<?php
/**
 * Autor: Thiago Mourao
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-03-18
 *
 * Pagina de alunos do instrutor - apenas alunos vinculados as suas turmas
 * Permite editar: foto, telefone, email, data de nascimento, endereco
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['instrutor', 'gestor']);

$pdo = getConnection();
$instrutorId = getUserId();
$userType = getUserType();

// Processar edicao
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'editar') {
        $id = (int)$_POST['id'];

        // Verificar se o aluno esta vinculado a uma turma do instrutor
        if ($userType === 'instrutor') {
            $stmtCheck = $pdo->prepare("
                SELECT 1 FROM matriculas m
                INNER JOIN turma_instrutores ti ON m.turma_id = ti.turma_id
                WHERE m.aluno_id = ? AND ti.instrutor_id = ?
                LIMIT 1
            ");
            $stmtCheck->execute([$id, $instrutorId]);
            if (!$stmtCheck->fetchColumn()) {
                setFlashMessage('Voce nao tem permissao para editar este aluno!', 'error');
                redirect('/instrutor/alunos.php');
            }
        }

        $email = sanitize($_POST['email'] ?? '');
        $whatsapp = sanitize($_POST['whatsapp'] ?? '');
        $dataNascimento = !empty($_POST['data_nascimento']) ? sanitize($_POST['data_nascimento']) : null;
        $endereco = sanitize($_POST['endereco'] ?? '');

        $sql = "UPDATE alunos SET email = ?, whatsapp = ?, data_nascimento = ?, endereco = ?";
        $params = [$email, $whatsapp, $dataNascimento, $endereco];

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
        redirect('/instrutor/alunos.php' . (isset($_POST['turma_filtro']) ? '?turma_id=' . (int)$_POST['turma_filtro'] : ''));
    }
}

// Buscar turmas do instrutor
if ($userType === 'gestor') {
    $stmtTurmas = $pdo->query("
        SELECT t.id, c.nome as curso_nome, t.ano
        FROM turmas t
        INNER JOIN cursos c ON t.curso_id = c.id
        WHERE t.status = 'ativa' AND c.ativo = 1
        ORDER BY t.ano DESC, c.nome
    ");
} else {
    $stmtTurmas = $pdo->prepare("
        SELECT t.id, c.nome as curso_nome, t.ano
        FROM turmas t
        INNER JOIN cursos c ON t.curso_id = c.id
        INNER JOIN turma_instrutores ti ON t.id = ti.turma_id
        WHERE ti.instrutor_id = ? AND t.status = 'ativa' AND c.ativo = 1
        ORDER BY t.ano DESC, c.nome
    ");
    $stmtTurmas->execute([$instrutorId]);
}
$turmas = $stmtTurmas->fetchAll();

// Filtros
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$turmaFiltro = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;

// Se nenhuma turma selecionada e instrutor tem turmas, usar todas as turmas do instrutor
$turmaIds = array_column($turmas, 'id');

// Construir query de alunos
$where = " WHERE a.ativo = 1";
$joins = "";
$params = [];

if ($turmaFiltro && in_array($turmaFiltro, $turmaIds)) {
    $joins .= " INNER JOIN matriculas m ON a.id = m.aluno_id AND m.turma_id = ?";
    $params[] = $turmaFiltro;
} elseif (!empty($turmaIds)) {
    $placeholders = implode(',', array_fill(0, count($turmaIds), '?'));
    $joins .= " INNER JOIN matriculas m ON a.id = m.aluno_id AND m.turma_id IN ($placeholders)";
    $params = array_merge($params, $turmaIds);
} else {
    // Instrutor sem turmas
    $where .= " AND 1 = 0";
}

if ($busca) {
    $where .= " AND a.nome LIKE ?";
    $params[] = '%' . $busca . '%';
}

$sqlAlunos = "
    SELECT DISTINCT a.*
    FROM alunos a
    $joins
    $where
    ORDER BY a.nome
";
$stmtAlunos = $pdo->prepare($sqlAlunos);
$stmtAlunos->execute($params);
$alunos = $stmtAlunos->fetchAll();

$pageTitle = 'Meus Alunos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Meus Alunos</h1>
        <p class="text-gray-500 text-sm mt-1"><?= count($alunos) ?> aluno(s) encontrado(s)</p>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-md p-4 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3 items-end">
        <div class="w-full md:flex-1">
            <label class="block text-xs font-medium text-gray-700 mb-1">Buscar por nome</label>
            <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Digite o nome do aluno..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 text-sm">
        </div>
        <div class="w-full md:w-64">
            <label class="block text-xs font-medium text-gray-700 mb-1">Turma</label>
            <select name="turma_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 text-sm">
                <option value="">Todas as minhas turmas</option>
                <?php foreach ($turmas as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $turmaFiltro == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['curso_nome']) ?> - <?= $t['ano'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg transition font-semibold text-sm">
                Filtrar
            </button>
            <?php if ($busca || $turmaFiltro): ?>
                <a href="/instrutor/alunos.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition font-semibold text-sm">
                    Limpar
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabela -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aluno</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">WhatsApp</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Nascimento</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($alunos)): ?>
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Nenhum aluno encontrado</td></tr>
                <?php else: ?>
                    <?php foreach ($alunos as $aluno): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <?php if ($aluno['foto']): ?>
                                    <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="h-10 w-10 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center text-white font-bold">
                                        <?= strtoupper(mb_substr($aluno['nome'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($aluno['nome']) ?></div>
                                    <?php if (!empty($aluno['endereco'])): ?>
                                        <div class="text-xs text-gray-400 truncate max-w-xs"><?= htmlspecialchars($aluno['endereco']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <?= !empty($aluno['email']) ? htmlspecialchars($aluno['email']) : '<span class="text-gray-300">-</span>' ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?php if (!empty($aluno['whatsapp'])):
                                $whatsappNumero = preg_replace('/\D/', '', $aluno['whatsapp']);
                            ?>
                                <a href="https://wa.me/<?= $whatsappNumero ?>" target="_blank" class="text-green-600 hover:text-green-800 flex items-center gap-1">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    <?= htmlspecialchars($aluno['whatsapp']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-gray-300">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600">
                            <?= $aluno['data_nascimento'] ? formatarData($aluno['data_nascimento']) : '<span class="text-gray-300">-</span>' ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-3">
                                <a href="/aluno_perfil.php?id=<?= $aluno['id'] ?>" class="text-purple-600 hover:text-purple-800 font-medium text-sm">Perfil</a>
                                <button onclick='editarAluno(<?= json_encode($aluno) ?>)' class="text-blue-600 hover:text-blue-900 text-sm font-medium">Editar</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Editar -->
<div id="modalEditar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Editar Aluno</h3>
            <button onclick="fecharModal('modalEditar')" class="text-gray-600 hover:text-gray-900 text-2xl">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="turma_filtro" value="<?= $turmaFiltro ?>">

            <div class="flex justify-center mb-2">
                <div id="edit_foto_atual" class="w-24 h-24 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center text-white font-bold text-3xl overflow-hidden">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input type="text" id="edit_nome" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="edit_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                <input type="text" name="whatsapp" id="edit_whatsapp" placeholder="(00) 00000-0000" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                <input type="date" name="data_nascimento" id="edit_data_nascimento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Endereco</label>
                <textarea name="endereco" id="edit_endereco" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nova Foto (150x150px - deixe em branco para nao alterar)</label>
                <input type="file" name="foto" id="fotoInputEditar" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <img id="fotoPreviewEditar" class="hidden mt-2 w-32 h-32 rounded-full object-cover border-2 border-gray-300">
            </div>
            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg transition font-semibold">
                Atualizar Aluno
            </button>
        </form>
    </div>
</div>

<script>
function fecharModal(id) { document.getElementById(id).classList.add('hidden'); }

function editarAluno(aluno) {
    document.getElementById('edit_id').value = aluno.id;
    document.getElementById('edit_nome').value = aluno.nome;
    document.getElementById('edit_email').value = aluno.email || '';
    document.getElementById('edit_whatsapp').value = aluno.whatsapp || '';
    document.getElementById('edit_data_nascimento').value = aluno.data_nascimento || '';
    document.getElementById('edit_endereco').value = aluno.endereco || '';

    const fotoDiv = document.getElementById('edit_foto_atual');
    if (aluno.foto) {
        fotoDiv.innerHTML = `<img src="/assets/uploads/${aluno.foto}" class="w-full h-full object-cover">`;
    } else {
        fotoDiv.innerHTML = `<span>${aluno.nome.substring(0,1).toUpperCase()}</span>`;
    }

    document.getElementById('fotoPreviewEditar').classList.add('hidden');
    document.getElementById('fotoInputEditar').value = '';
    document.getElementById('modalEditar').classList.remove('hidden');
}

// Preview da foto
document.getElementById('fotoInputEditar')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const preview = document.getElementById('fotoPreviewEditar');
            preview.src = ev.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
});

// Mascara WhatsApp
document.getElementById('edit_whatsapp')?.addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '');
    if (v.length > 11) v = v.substring(0, 11);
    if (v.length > 7) v = `(${v.substring(0,2)}) ${v.substring(2,7)}-${v.substring(7)}`;
    else if (v.length > 2) v = `(${v.substring(0,2)}) ${v.substring(2)}`;
    else if (v.length > 0) v = `(${v}`;
    e.target.value = v;
});

document.addEventListener('DOMContentLoaded', function() {
    if (typeof initImageCrop === 'function') {
        initImageCrop('fotoInputEditar', 'fotoPreviewEditar', 150, 150);
    }
});
</script>

<?php
if (file_exists(__DIR__ . '/../includes/crop-modal.php')) {
    require_once __DIR__ . '/../includes/crop-modal.php';
}
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
