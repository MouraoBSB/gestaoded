<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 20:22:00
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getConnection();
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: /esqueci_senha.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT tr.*, u.nome, u.email 
    FROM tokens_recuperacao tr
    INNER JOIN usuarios u ON tr.usuario_id = u.id
    WHERE tr.token = ? AND tr.usado = 0 AND tr.expiracao > NOW()
");
$stmt->execute([$token]);
$recuperacao = $stmt->fetch();

if (!$recuperacao) {
    $tokenInvalido = true;
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $novaSenha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        
        if (empty($novaSenha)) {
            $erro = 'Digite a nova senha';
        } elseif (strlen($novaSenha) < 6) {
            $erro = 'A senha deve ter no mínimo 6 caracteres';
        } elseif ($novaSenha !== $confirmarSenha) {
            $erro = 'As senhas não coincidem';
        } else {
            try {
                $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
                
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt->execute([$senhaHash, $recuperacao['usuario_id']]);
                
                $stmt = $pdo->prepare("UPDATE tokens_recuperacao SET usado = 1 WHERE id = ?");
                $stmt->execute([$recuperacao['id']]);
                
                $pdo->commit();
                
                $sucesso = true;
            } catch (Exception $e) {
                $pdo->rollBack();
                $erro = 'Erro ao redefinir senha: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Sistema de Chamada</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-500 to-pink-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-2xl p-8 w-full max-w-md">
        <?php if (isset($tokenInvalido)): ?>
            <div class="text-center">
                <div class="text-6xl mb-4">⚠️</div>
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Link Inválido ou Expirado</h1>
                <p class="text-gray-600 mb-6">Este link de recuperação não é válido ou já expirou.</p>
                <a href="/esqueci_senha.php" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg inline-block">
                    Solicitar Novo Link
                </a>
            </div>
        <?php elseif (isset($sucesso)): ?>
            <div class="text-center">
                <div class="text-6xl mb-4">✅</div>
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Senha Redefinida!</h1>
                <p class="text-gray-600 mb-6">Sua senha foi alterada com sucesso.</p>
                <a href="/login.php" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg inline-block">
                    Fazer Login
                </a>
            </div>
        <?php else: ?>
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">🔑 Redefinir Senha</h1>
                <p class="text-gray-600">Olá, <?= htmlspecialchars($recuperacao['nome']) ?></p>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($recuperacao['email']) ?></p>
            </div>
            
            <?php if (isset($erro)): ?>
                <div class="bg-red-100 border-2 border-red-500 text-red-800 p-4 rounded-lg mb-6">
                    <p class="font-semibold"><?= $erro ?></p>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Nova Senha</label>
                    <input type="password" name="senha" required minlength="6"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="Mínimo 6 caracteres">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Confirmar Nova Senha</label>
                    <input type="password" name="confirmar_senha" required minlength="6"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="Digite a senha novamente">
                </div>
                
                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg transition">
                    Redefinir Senha
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
