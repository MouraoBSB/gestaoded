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

// Processar POST (criar, editar, desativar)
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
        $redirectTo = $_POST['redirect_to'] ?? '/gestor/alunos.php';
        redirect($redirectTo);
    }

    if ($acao === 'desativar') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE alunos SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage('Aluno desativado com sucesso!', 'success');
        redirect('/gestor/alunos.php');
    }
}

// Filtros
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$turmaFiltro = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
$filtro = $_GET['filtro'] ?? '';
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$porPagina = 20;

// Turmas para filtro
$turmas = $pdo->query("
    SELECT t.id, c.nome as curso_nome, t.ano, t.semestre, t.status
    FROM turmas t
    INNER JOIN cursos c ON t.curso_id = c.id
    ORDER BY t.ano DESC, c.nome
")->fetchAll();

// Construir query
$where = " WHERE a.ativo = 1";
$joins = "";
$params = [];

if ($turmaFiltro) {
    $joins .= " INNER JOIN matriculas m ON a.id = m.aluno_id AND m.turma_id = ?";
    $params[] = $turmaFiltro;
} elseif ($filtro === 'sem_turma') {
    $joins .= " LEFT JOIN matriculas m ON a.id = m.aluno_id";
    $joins .= " LEFT JOIN turmas t ON m.turma_id = t.id AND t.status = 'ativa'";
    $where .= " AND t.id IS NULL";
}

if ($busca) {
    $where .= " AND a.nome LIKE ?";
    $params[] = '%' . $busca . '%';
}

switch ($filtro) {
    case 'sem_email':
        $where .= " AND (a.email IS NULL OR a.email = '')";
        break;
    case 'com_email':
        $where .= " AND a.email IS NOT NULL AND a.email != ''";
        break;
    case 'sem_whatsapp':
        $where .= " AND (a.whatsapp IS NULL OR a.whatsapp = '')";
        break;
    case 'com_whatsapp':
        $where .= " AND a.whatsapp IS NOT NULL AND a.whatsapp != ''";
        break;
    case 'sem_foto':
        $where .= " AND (a.foto IS NULL OR a.foto = '')";
        break;
    case 'com_foto':
        $where .= " AND a.foto IS NOT NULL AND a.foto != ''";
        break;
    case 'sem_nascimento':
        $where .= " AND a.data_nascimento IS NULL";
        break;
    case 'sem_endereco':
        $where .= " AND (a.endereco IS NULL OR a.endereco = '')";
        break;
}

// Contar total
$sqlCount = "SELECT COUNT(DISTINCT a.id) FROM alunos a $joins $where";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$totalRegistros = (int)$stmtCount->fetchColumn();
$totalPaginas = max(1, ceil($totalRegistros / $porPagina));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $porPagina;

// Buscar alunos paginados
$sqlAlunos = "
    SELECT DISTINCT a.*
    FROM alunos a
    $joins
    $where
    ORDER BY a.nome
    LIMIT $porPagina OFFSET $offset
";
$stmtAlunos = $pdo->prepare($sqlAlunos);
$stmtAlunos->execute($params);
$alunos = $stmtAlunos->fetchAll();

// Query params para paginação
$queryParams = [];
if ($busca) $queryParams['busca'] = $busca;
if ($turmaFiltro) $queryParams['turma_id'] = $turmaFiltro;
if ($filtro) $queryParams['filtro'] = $filtro;

$pageTitle = 'Gerenciar Alunos - Gestor';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Gerenciar Alunos</h1>
        <p class="text-gray-500 text-sm mt-1"><?= $totalRegistros ?> aluno(s) encontrado(s)</p>
    </div>
    <button onclick="abrirModal('modalCriar')" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition font-semibold">
        + Novo Aluno
    </button>
</div>

<!-- Busca Ajax -->
<div class="bg-white rounded-lg shadow-md p-4 mb-4">
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input type="text" id="buscaAlunoAjax" placeholder="Busca rápida por nome..."
               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
               autocomplete="off">
        <div id="resultadosBuscaAjax" class="absolute z-20 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 hidden max-h-80 overflow-y-auto"></div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-md p-4 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3 items-end">
        <div class="w-full md:w-64">
            <label class="block text-xs font-medium text-gray-700 mb-1">Turma</label>
            <select name="turma_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm">
                <option value="">Todas as turmas</option>
                <?php foreach ($turmas as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $turmaFiltro == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['curso_nome']) ?> - <?= $t['ano'] ?> (<?= $t['status'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full md:w-52">
            <label class="block text-xs font-medium text-gray-700 mb-1">Filtro especial</label>
            <select name="filtro" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm">
                <option value="">Todos</option>
                <option value="sem_turma" <?= $filtro === 'sem_turma' ? 'selected' : '' ?>>Sem turma ativa</option>
                <option value="sem_email" <?= $filtro === 'sem_email' ? 'selected' : '' ?>>Sem email</option>
                <option value="com_email" <?= $filtro === 'com_email' ? 'selected' : '' ?>>Com email</option>
                <option value="sem_whatsapp" <?= $filtro === 'sem_whatsapp' ? 'selected' : '' ?>>Sem WhatsApp</option>
                <option value="com_whatsapp" <?= $filtro === 'com_whatsapp' ? 'selected' : '' ?>>Com WhatsApp</option>
                <option value="sem_foto" <?= $filtro === 'sem_foto' ? 'selected' : '' ?>>Sem foto</option>
                <option value="com_foto" <?= $filtro === 'com_foto' ? 'selected' : '' ?>>Com foto</option>
                <option value="sem_nascimento" <?= $filtro === 'sem_nascimento' ? 'selected' : '' ?>>Sem data de nascimento</option>
                <option value="sem_endereco" <?= $filtro === 'sem_endereco' ? 'selected' : '' ?>>Sem endereço</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg transition font-semibold text-sm">
                Filtrar
            </button>
            <?php if ($busca || $turmaFiltro || $filtro): ?>
                <a href="/gestor/alunos.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition font-semibold text-sm">
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
                                <button onclick="confirmarDesativar(<?= $aluno['id'] ?>)" class="text-red-600 hover:text-red-900 text-sm font-medium">Desativar</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginação -->
<?php if ($totalPaginas > 1): ?>
    <nav class="mt-6 flex justify-center">
        <ul class="flex items-center gap-1">
            <?php if ($pagina > 1): ?>
                <li>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['pagina' => $pagina - 1])) ?>"
                       class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 transition text-sm">
                        &laquo; Anterior
                    </a>
                </li>
            <?php endif; ?>

            <?php
            $inicio = max(1, $pagina - 2);
            $fim = min($totalPaginas, $pagina + 2);
            if ($inicio > 1): ?>
                <li><a href="?<?= http_build_query(array_merge($queryParams, ['pagina' => 1])) ?>" class="px-3 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 transition text-sm">1</a></li>
                <?php if ($inicio > 2): ?><li><span class="px-2 text-gray-400">...</span></li><?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                <li>
                    <?php if ($i == $pagina): ?>
                        <span class="px-3 py-2 rounded-lg bg-purple-600 text-white font-semibold text-sm"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($queryParams, ['pagina' => $i])) ?>" class="px-3 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 transition text-sm"><?= $i ?></a>
                    <?php endif; ?>
                </li>
            <?php endfor; ?>

            <?php if ($fim < $totalPaginas): ?>
                <?php if ($fim < $totalPaginas - 1): ?><li><span class="px-2 text-gray-400">...</span></li><?php endif; ?>
                <li><a href="?<?= http_build_query(array_merge($queryParams, ['pagina' => $totalPaginas])) ?>" class="px-3 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 transition text-sm"><?= $totalPaginas ?></a></li>
            <?php endif; ?>

            <?php if ($pagina < $totalPaginas): ?>
                <li>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['pagina' => $pagina + 1])) ?>"
                       class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 transition text-sm">
                        Próxima &raquo;
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Modal Criar -->
<div id="modalCriar" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Novo Aluno</h3>
            <button onclick="fecharModal('modalCriar')" class="text-gray-600 hover:text-gray-900 text-2xl">&times;</button>
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
function abrirModal(id) { document.getElementById(id).classList.remove('hidden'); }
function fecharModal(id) { document.getElementById(id).classList.add('hidden'); }

function editarAluno(aluno) {
    document.getElementById('edit_id').value = aluno.id;
    document.getElementById('edit_nome').value = aluno.nome;
    document.getElementById('edit_data_nascimento').value = aluno.data_nascimento || '';
    document.getElementById('edit_endereco').value = aluno.endereco || '';
    abrirModal('modalEditar');
}

function confirmarDesativar(id) {
    if (confirm('Tem certeza que deseja desativar este aluno?')) {
        document.getElementById('desativar_id').value = id;
        document.getElementById('formDesativar').submit();
    }
}

// Busca Ajax
const inputAjax = document.getElementById('buscaAlunoAjax');
const resultadosAjax = document.getElementById('resultadosBuscaAjax');
let timeoutAjax;

inputAjax.addEventListener('input', function() {
    clearTimeout(timeoutAjax);
    const termo = this.value.trim();
    if (termo.length < 2) { resultadosAjax.classList.add('hidden'); return; }

    timeoutAjax = setTimeout(() => {
        fetch(`/gestor/api_buscar_aluno.php?termo=${encodeURIComponent(termo)}`)
            .then(r => r.json())
            .then(alunos => {
                if (alunos.length === 0) {
                    resultadosAjax.innerHTML = '<div class="p-4 text-gray-500 text-center">Nenhum aluno encontrado</div>';
                    resultadosAjax.classList.remove('hidden');
                    return;
                }
                let html = '<div class="divide-y divide-gray-200">';
                alunos.forEach(a => {
                    const foto = a.foto
                        ? `<img src="/assets/uploads/${a.foto}" class="h-10 w-10 rounded-full object-cover">`
                        : `<div class="h-10 w-10 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center"><span class="text-white text-sm font-bold">${a.nome.substring(0,1).toUpperCase()}</span></div>`;
                    html += `
                        <a href="/aluno_perfil.php?id=${a.id}" class="flex items-center gap-3 p-3 hover:bg-gray-50 transition">
                            ${foto}
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">${a.nome}</div>
                                <div class="text-xs text-gray-500">${a.total_cursos} curso(s) • <span class="text-green-600">${a.total_aprovados} aprov.</span> • <span class="text-red-600">${a.total_reprovados} reprov.</span></div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>`;
                });
                html += '</div>';
                resultadosAjax.innerHTML = html;
                resultadosAjax.classList.remove('hidden');
            })
            .catch(() => {
                resultadosAjax.innerHTML = '<div class="p-4 text-red-500 text-center">Erro na busca</div>';
                resultadosAjax.classList.remove('hidden');
            });
    }, 300);
});

document.addEventListener('click', function(e) {
    if (!inputAjax.contains(e.target) && !resultadosAjax.contains(e.target)) {
        resultadosAjax.classList.add('hidden');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    if (typeof initImageCrop === 'function') {
        initImageCrop('fotoInputCriar', 'fotoPreviewCriar', 150, 150);
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
