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
            try {
                // Invalidar tokens anteriores do mesmo usuário
                $stmt = $pdo->prepare("UPDATE tokens_recuperacao SET usado = 1 WHERE usuario_id = ? AND usado = 0");
                $stmt->execute([$usuario['id']]);

                // Limpar tokens expirados de todos os usuários
                $pdo->exec("DELETE FROM tokens_recuperacao WHERE expiracao < NOW() AND usado = 1");

                $token = bin2hex(random_bytes(32));
                $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $stmt = $pdo->prepare("INSERT INTO tokens_recuperacao (usuario_id, token, expiracao) VALUES (?, ?, ?)");
                $stmt->execute([$usuario['id'], $token, $expiracao]);

                $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $linkRecuperacao = $protocolo . '://' . $_SERVER['HTTP_HOST'] . '/redefinir_senha.php?token=' . $token;

                $mensagemHtml = obterTemplateEmail(
                    'Recuperação de Senha',
                    'Olá <strong>' . htmlspecialchars($usuario['nome']) . '</strong>,<br><br>' .
                    'Recebemos uma solicitação para redefinir sua senha no sistema CEMA.<br><br>' .
                    'Clique no botão abaixo para criar uma nova senha:<br><br>' .
                    '<a href="' . $linkRecuperacao . '" style="display: inline-block; background: linear-gradient(90deg, #4e4483 0%, #6e9fcb 100%); color: white; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">Redefinir Minha Senha</a><br><br>' .
                    'Ou copie e cole este link no navegador:<br>' .
                    '<a href="' . $linkRecuperacao . '" style="color: #4e4483;">' . $linkRecuperacao . '</a><br><br>' .
                    '<strong style="color: #e79048;">Este link expira em 1 hora.</strong><br><br>' .
                    'Se você não solicitou esta redefinição, ignore este email. Sua senha permanecerá inalterada.',
                    htmlspecialchars($usuario['nome']),
                    $linkRecuperacao
                );

                $mensagemTexto = "Recuperação de Senha\n\nOlá {$usuario['nome']},\n\nRecebemos uma solicitação para redefinir sua senha no sistema CEMA.\n\nAcesse este link para criar uma nova senha:\n{$linkRecuperacao}\n\nEste link expira em 1 hora.\n\nSe você não solicitou esta redefinição, ignore este email.";

                enviarEmail($email, $usuario['nome'], 'Recuperação de Senha - CEMA', $mensagemHtml, $mensagemTexto);
            } catch (Exception $e) {
                error_log("Erro ao enviar email de recuperação para {$email}: " . $e->getMessage());
            }
        }

        // Mensagem genérica independente de o email existir ou não (segurança)
        $sucesso = 'Se este email estiver cadastrado, você receberá as instruções para redefinir sua senha em instantes.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci Minha Senha - CEMA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4" style="background: linear-gradient(135deg, #4e4483 0%, #6e9fcb 50%, #89ab98 100%);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <img src="/assets/images/logo-cema.png" alt="CEMA" class="h-20 mx-auto mb-4">
            <h1 class="text-2xl font-bold" style="color: #4e4483;">Esqueci Minha Senha</h1>
            <p class="mt-2 text-sm" style="color: #89ab98;">Digite seu email para receber o link de recuperação</p>
        </div>

        <?php if (isset($sucesso)): ?>
            <div class="border-2 px-4 py-4 rounded-lg mb-6" style="background-color: #f0fdf4; border-color: #89ab98; color: #166534;">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 flex-shrink-0 mt-0.5" style="color: #89ab98;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <div>
                        <p class="font-semibold text-sm"><?= htmlspecialchars($sucesso) ?></p>
                        <p class="text-xs mt-2" style="color: #6b7280;">Verifique também a pasta de spam/lixo eletrônico.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
            <div class="border-2 px-4 py-3 rounded-lg mb-6" style="background-color: #e79048; border-color: #4e4483; color: white;">
                <strong>&#10007;</strong> <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($sucesso)): ?>
        <form method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium mb-2" style="color: #4e4483;">Email cadastrado</label>
                <input type="email" id="email" name="email" required autofocus
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       class="w-full px-4 py-3 border-2 rounded-lg transition focus:outline-none"
                       style="border-color: #89ab98;"
                       onfocus="this.style.borderColor='#4e4483'; this.style.boxShadow='0 0 0 3px rgba(78,68,131,0.1)'"
                       onblur="this.style.borderColor='#89ab98'; this.style.boxShadow='none'"
                       placeholder="seu-email@exemplo.com">
            </div>

            <button type="submit"
                class="w-full text-white font-semibold py-3 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg"
                style="background: linear-gradient(90deg, #4e4483 0%, #6e9fcb 100%);">
                Enviar Link de Recuperação
            </button>
        </form>
        <?php endif; ?>

        <div class="text-center mt-6">
            <a href="/login.php" class="font-semibold hover:underline text-sm" style="color: #e79048;">
                &larr; Voltar para o Login
            </a>
        </div>
    </div>
</body>
</html>
