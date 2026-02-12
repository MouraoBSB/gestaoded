<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 20:22:00
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $erro = 'Digite seu email';
    } else {
        $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            try {
                $stmt = $pdo->prepare("INSERT INTO tokens_recuperacao (usuario_id, token, expiracao) VALUES (?, ?, ?)");
                $stmt->execute([$usuario['id'], $token, $expiracao]);
                
                $linkRecuperacao = 'http://' . $_SERVER['HTTP_HOST'] . '/redefinir_senha.php?token=' . $token;
                
                $mensagemHtml = obterTemplateEmail(
                    '🔐 Recuperação de Senha',
                    'Olá <strong>' . htmlspecialchars($usuario['nome']) . '</strong>,<br><br>' .
                    'Recebemos uma solicitação para redefinir sua senha.<br><br>' .
                    'Clique no botão abaixo para criar uma nova senha:<br><br>' .
                    '<a href="' . $linkRecuperacao . '" style="display: inline-block; background-color: #8b5cf6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">Redefinir Senha</a><br><br>' .
                    'Ou copie e cole este link no navegador:<br>' .
                    '<a href="' . $linkRecuperacao . '">' . $linkRecuperacao . '</a><br><br>' .
                    '<strong>Este link expira em 1 hora.</strong><br><br>' .
                    'Se você não solicitou esta redefinição, ignore este email.',
                    htmlspecialchars($usuario['nome']),
                    $linkRecuperacao
                );
                
                $mensagemTexto = "Recuperação de Senha\n\nOlá {$usuario['nome']},\n\nRecebemos uma solicitação para redefinir sua senha.\n\nAcesse este link para criar uma nova senha:\n{$linkRecuperacao}\n\nEste link expira em 1 hora.\n\nSe você não solicitou esta redefinição, ignore este email.";
                
                try {
                    enviarEmail($email, $usuario['nome'], 'Recuperação de Senha - Sistema de Chamada', $mensagemHtml, $mensagemTexto);
                    $sucesso = 'Email de recuperação enviado! Verifique sua caixa de entrada.';
                } catch (Exception $e) {
                    $erro = 'Erro ao enviar email: ' . $e->getMessage();
                }
            } catch (Exception $e) {
                $erro = 'Erro ao enviar email: ' . $e->getMessage();
            }
        } else {
            $sucesso = 'Se o email existir em nossa base, você receberá instruções para redefinir sua senha.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci Minha Senha - Sistema de Chamada</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-500 to-pink-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">🔐 Esqueci Minha Senha</h1>
            <p class="text-gray-600">Digite seu email para recuperar o acesso</p>
        </div>
        
        <?php if (isset($sucesso)): ?>
            <div class="bg-green-100 border-2 border-green-500 text-green-800 p-4 rounded-lg mb-6">
                <p class="font-semibold"><?= $sucesso ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($erro)): ?>
            <div class="bg-red-100 border-2 border-red-500 text-red-800 p-4 rounded-lg mb-6">
                <p class="font-semibold"><?= $erro ?></p>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">Email</label>
                <input type="email" name="email" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                       placeholder="seu-email@exemplo.com">
            </div>
            
            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg transition mb-4">
                Enviar Link de Recuperação
            </button>
            
            <div class="text-center">
                <a href="/login.php" class="text-purple-600 hover:text-purple-800 font-semibold">
                    ← Voltar para o Login
                </a>
            </div>
        </form>
    </div>
</body>
</html>
