<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-03-18
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$pdo = getConnection();
$userId = getUserId();
$userType = getUserType();

// Garantir que coluna foto existe
try {
    $pdo->query("SELECT foto FROM usuarios LIMIT 0");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER whatsapp");
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'atualizar_perfil') {
        $nome = sanitize($_POST['nome']);
        $email = sanitize($_POST['email']);
        $whatsapp = sanitize($_POST['whatsapp'] ?? '');

        // Upload de foto
        $fotoNome = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $permitidas) && $_FILES['foto']['size'] <= 5 * 1024 * 1024) {
                $fotoNome = uniqid() . '.' . $ext;
                $destino = __DIR__ . '/assets/uploads/' . $fotoNome;
                move_uploaded_file($_FILES['foto']['tmp_name'], $destino);
            } else {
                setFlashMessage('Formato de imagem inválido ou arquivo muito grande (máx. 5MB).', 'error');
                redirect('/meu_perfil.php');
            }
        }

        try {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, whatsapp = ?";
            $params = [$nome, $email, $whatsapp];

            if ($fotoNome) {
                $sql .= ", foto = ?";
                $params[] = $fotoNome;
            }

            $sql .= " WHERE id = ?";
            $params[] = $userId;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $_SESSION['usuario_nome'] = $nome;
            setFlashMessage('Perfil atualizado com sucesso!', 'success');
        } catch (PDOException $e) {
            setFlashMessage('Erro ao atualizar perfil: ' . $e->getMessage(), 'error');
        }
        redirect('/meu_perfil.php');
    }

    if ($acao === 'alterar_senha') {
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        if (empty($senhaAtual) || empty($novaSenha)) {
            setFlashMessage('Preencha todos os campos de senha.', 'error');
            redirect('/meu_perfil.php');
        }

        if ($novaSenha !== $confirmarSenha) {
            setFlashMessage('A nova senha e a confirmação não coincidem.', 'error');
            redirect('/meu_perfil.php');
        }

        if (strlen($novaSenha) < 6) {
            setFlashMessage('A nova senha deve ter pelo menos 6 caracteres.', 'error');
            redirect('/meu_perfil.php');
        }

        $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $usuario = $stmt->fetch();

        if (!password_verify($senhaAtual, $usuario['senha'])) {
            setFlashMessage('Senha atual incorreta.', 'error');
            redirect('/meu_perfil.php');
        }

        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->execute([password_hash($novaSenha, PASSWORD_DEFAULT), $userId]);

        $_SESSION['senha_padrao'] = false;

        setFlashMessage('Senha alterada com sucesso!', 'success');
        redirect('/meu_perfil.php');
    }
}

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$usuario = $stmt->fetch();

// Buscar turmas atuais (ativas) do usuário
if ($userType === 'instrutor') {
    $stmt = $pdo->prepare("
        SELECT t.id as turma_id, t.ano, t.semestre, t.status, c.nome as curso_nome,
               COUNT(DISTINCT m.aluno_id) as total_alunos,
               COUNT(DISTINCT au.id) as total_aulas
        FROM turma_instrutores ti
        INNER JOIN turmas t ON ti.turma_id = t.id
        INNER JOIN cursos c ON t.curso_id = c.id
        LEFT JOIN matriculas m ON m.turma_id = t.id
        LEFT JOIN aulas au ON (au.turma_id = t.id OR (au.turma_id IS NULL AND au.curso_id = t.curso_id))
        WHERE ti.instrutor_id = ? AND t.status = 'ativa' AND c.ativo = 1
        GROUP BY t.id
        ORDER BY t.ano DESC, c.nome
    ");
    $stmt->execute([$userId]);
    $turmasAtuais = $stmt->fetchAll();

    // Turmas anteriores (finalizadas)
    $stmt = $pdo->prepare("
        SELECT t.id as turma_id, t.ano, t.semestre, t.status, c.nome as curso_nome,
               COUNT(DISTINCT m.aluno_id) as total_alunos,
               COUNT(DISTINCT au.id) as total_aulas
        FROM turma_instrutores ti
        INNER JOIN turmas t ON ti.turma_id = t.id
        INNER JOIN cursos c ON t.curso_id = c.id
        LEFT JOIN matriculas m ON m.turma_id = t.id
        LEFT JOIN aulas au ON (au.turma_id = t.id OR (au.turma_id IS NULL AND au.curso_id = t.curso_id))
        WHERE ti.instrutor_id = ? AND t.status != 'ativa'
        GROUP BY t.id
        ORDER BY t.ano DESC, c.nome
    ");
    $stmt->execute([$userId]);
    $turmasAnteriores = $stmt->fetchAll();
} else {
    // Gestor/Diretor vê todas as turmas
    $turmasAtuais = $pdo->query("
        SELECT t.id as turma_id, t.ano, t.semestre, t.status, c.nome as curso_nome,
               COUNT(DISTINCT m.aluno_id) as total_alunos,
               COUNT(DISTINCT au.id) as total_aulas
        FROM turmas t
        INNER JOIN cursos c ON t.curso_id = c.id
        LEFT JOIN matriculas m ON m.turma_id = t.id
        LEFT JOIN aulas au ON (au.turma_id = t.id OR (au.turma_id IS NULL AND au.curso_id = t.curso_id))
        WHERE t.status = 'ativa' AND c.ativo = 1
        GROUP BY t.id
        ORDER BY t.ano DESC, c.nome
    ")->fetchAll();

    $turmasAnteriores = $pdo->query("
        SELECT t.id as turma_id, t.ano, t.semestre, t.status, c.nome as curso_nome,
               COUNT(DISTINCT m.aluno_id) as total_alunos,
               COUNT(DISTINCT au.id) as total_aulas
        FROM turmas t
        INNER JOIN cursos c ON t.curso_id = c.id
        LEFT JOIN matriculas m ON m.turma_id = t.id
        LEFT JOIN aulas au ON (au.turma_id = t.id OR (au.turma_id IS NULL AND au.curso_id = t.curso_id))
        WHERE t.status != 'ativa'
        GROUP BY t.id
        ORDER BY t.ano DESC, c.nome
    ")->fetchAll();
}

$tipoLabel = ['gestor' => 'Gestor', 'diretor' => 'Diretor', 'instrutor' => 'Instrutor'][$userType] ?? '';

$pageTitle = 'Meu Perfil';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header do Perfil -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="h-32" style="background: linear-gradient(135deg, #4e4483 0%, #6e9fcb 100%);"></div>
    <div class="px-6 pb-6 relative">
        <div class="flex flex-col md:flex-row items-start md:items-end gap-4 -mt-12">
            <div class="relative group">
                <?php if (!empty($usuario['foto'])): ?>
                    <img src="/assets/uploads/<?= htmlspecialchars($usuario['foto']) ?>" alt="Foto"
                         class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white font-bold text-3xl border-4 border-white shadow-lg">
                        <?= strtoupper(mb_substr($usuario['nome'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-1 pt-2 md:pt-0">
                <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($usuario['nome']) ?></h1>
                <div class="flex items-center gap-3 mt-1">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        <?= $userType === 'gestor' ? 'bg-red-100 text-red-800' : ($userType === 'diretor' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                        <?= $tipoLabel ?>
                    </span>
                    <span class="text-sm text-gray-500">Membro desde <?= date('d/m/Y', strtotime($usuario['criado_em'])) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Coluna esquerda: Dados do perfil -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Informações Pessoais -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Informações Pessoais
            </h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="acao" value="atualizar_perfil">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                        <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                        <input type="text" name="whatsapp" value="<?= htmlspecialchars($usuario['whatsapp'] ?? '') ?>" placeholder="(61) 99999-9999"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto de Perfil</label>
                        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                        <p class="text-xs text-gray-500 mt-1">JPG, PNG ou WebP. Máximo 5MB.</p>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition font-semibold">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>

        <!-- Alterar Senha -->
        <div class="bg-white rounded-lg shadow-md p-6 <?= !empty($_SESSION['senha_padrao']) ? 'ring-2 ring-orange-400 shadow-orange-100' : '' ?>">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Alterar Senha
            </h2>
            <?php if (!empty($_SESSION['senha_padrao'])): ?>
                <div class="mb-4 px-4 py-3 rounded-lg border-2" style="background-color: #e79048; border-color: #4e4483; color: white;">
                    <strong>⚠</strong> Você está usando uma senha temporária. Defina uma nova senha abaixo.
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="alterar_senha">
                <?php if (!empty($_SESSION['senha_padrao'])): ?>
                    <input type="hidden" name="senha_atual" value="cema2026">
                <?php else: ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Senha Atual</label>
                        <input type="password" name="senha_atual" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                <?php endif; ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1 <?= !empty($_SESSION['senha_padrao']) ? 'text-orange-600 font-bold' : 'text-gray-700' ?>">Nova Senha</label>
                        <input type="password" name="nova_senha" required minlength="6" autofocus
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 <?= !empty($_SESSION['senha_padrao']) ? 'border-orange-400 focus:ring-orange-500 focus:border-orange-500 bg-orange-50' : 'border-gray-300 focus:ring-purple-500 focus:border-purple-500' ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 <?= !empty($_SESSION['senha_padrao']) ? 'text-orange-600 font-bold' : 'text-gray-700' ?>">Confirmar Nova Senha</label>
                        <input type="password" name="confirmar_senha" required minlength="6"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 <?= !empty($_SESSION['senha_padrao']) ? 'border-orange-400 focus:ring-orange-500 focus:border-orange-500 bg-orange-50' : 'border-gray-300 focus:ring-purple-500 focus:border-purple-500' ?>">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg transition font-semibold">
                        Alterar Senha
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Coluna direita: Turmas -->
    <div class="space-y-6">
        <!-- Resumo -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-3">Resumo</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Tipo de Acesso</span>
                    <span class="font-semibold text-gray-900"><?= $tipoLabel ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Turmas Ativas</span>
                    <span class="font-semibold text-green-600"><?= count($turmasAtuais) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Turmas Anteriores</span>
                    <span class="font-semibold text-gray-500"><?= count($turmasAnteriores) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Cadastrado em</span>
                    <span class="font-semibold text-gray-900"><?= date('d/m/Y', strtotime($usuario['criado_em'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Turmas Ativas -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                Turmas Ativas
            </h2>
            <?php if (empty($turmasAtuais)): ?>
                <p class="text-sm text-gray-500 italic">Nenhuma turma ativa</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($turmasAtuais as $turma): ?>
                        <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition">
                            <h4 class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($turma['curso_nome']) ?></h4>
                            <div class="flex gap-3 mt-1 text-xs text-gray-500">
                                <span><?= $turma['ano'] ?><?= $turma['semestre'] ? '/' . $turma['semestre'] : '' ?></span>
                                <span><?= $turma['total_alunos'] ?> alunos</span>
                                <span><?= $turma['total_aulas'] ?> aulas</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Turmas Anteriores -->
        <?php if (!empty($turmasAnteriores)): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                    <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                    Turmas Anteriores
                </h2>
                <div class="space-y-3">
                    <?php foreach ($turmasAnteriores as $turma): ?>
                        <div class="border border-gray-200 rounded-lg p-3 opacity-75">
                            <h4 class="font-semibold text-gray-700 text-sm"><?= htmlspecialchars($turma['curso_nome']) ?></h4>
                            <div class="flex gap-3 mt-1 text-xs text-gray-500">
                                <span><?= $turma['ano'] ?><?= $turma['semestre'] ? '/' . $turma['semestre'] : '' ?></span>
                                <span><?= $turma['total_alunos'] ?> alunos</span>
                                <span><?= $turma['total_aulas'] ?> aulas</span>
                                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600"><?= $turma['status'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
