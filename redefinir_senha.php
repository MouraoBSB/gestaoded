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

                // Marcar TODOS os tokens do usuário como usados
                $stmt = $pdo->prepare("UPDATE tokens_recuperacao SET usado = 1 WHERE usuario_id = ?");
                $stmt->execute([$recuperacao['usuario_id']]);

                $pdo->commit();

                $sucesso = true;
            } catch (Exception $e) {
                $pdo->rollBack();
                $erro = 'Erro ao redefinir senha. Tente novamente.';
                error_log("Erro ao redefinir senha do usuário {$recuperacao['usuario_id']}: " . $e->getMessage());
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
    <title>Redefinir Senha - CEMA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4" style="background: linear-gradient(135deg, #4e4483 0%, #6e9fcb 50%, #89ab98 100%);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <?php if (isset($tokenInvalido)): ?>
            <div class="text-center">
                <img src="/assets/images/logo-cema.png" alt="CEMA" class="h-16 mx-auto mb-4">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background-color: #fef3cd;">
                    <svg class="w-8 h-8" style="color: #e79048;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold mb-3" style="color: #4e4483;">Link Inválido ou Expirado</h1>
                <p class="text-gray-600 mb-6 text-sm">Este link de recuperação não é válido ou já foi utilizado. Os links expiram após 1 hora.</p>
                <a href="/esqueci_senha.php"
                   class="inline-block text-white font-semibold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg"
                   style="background: linear-gradient(90deg, #4e4483 0%, #6e9fcb 100%);">
                    Solicitar Novo Link
                </a>
                <div class="mt-4">
                    <a href="/login.php" class="text-sm font-semibold hover:underline" style="color: #e79048;">&larr; Voltar para o Login</a>
                </div>
            </div>

        <?php elseif (isset($sucesso)): ?>
            <div class="text-center">
                <img src="/assets/images/logo-cema.png" alt="CEMA" class="h-16 mx-auto mb-4">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background-color: #d1fae5;">
                    <svg class="w-8 h-8" style="color: #89ab98;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold mb-3" style="color: #4e4483;">Senha Redefinida!</h1>
                <p class="text-gray-600 mb-6 text-sm">Sua senha foi alterada com sucesso. Agora você já pode fazer login com a nova senha.</p>
                <a href="/login.php"
                   class="inline-block text-white font-semibold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg"
                   style="background: linear-gradient(90deg, #4e4483 0%, #6e9fcb 100%);">
                    Fazer Login
                </a>
            </div>

        <?php else: ?>
            <div class="text-center mb-8">
                <img src="/assets/images/logo-cema.png" alt="CEMA" class="h-16 mx-auto mb-4">
                <h1 class="text-2xl font-bold" style="color: #4e4483;">Redefinir Senha</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Olá, <strong style="color: #4e4483;"><?= htmlspecialchars($recuperacao['nome']) ?></strong>
                </p>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($recuperacao['email']) ?></p>
            </div>

            <?php if (isset($erro)): ?>
                <div class="border-2 px-4 py-3 rounded-lg mb-6" style="background-color: #e79048; border-color: #4e4483; color: white;">
                    <strong>&#10007;</strong> <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5" id="formSenha">
                <div>
                    <label class="block text-sm font-medium mb-2" style="color: #4e4483;">Nova Senha</label>
                    <div class="relative">
                        <input type="password" name="senha" id="senha" required minlength="6"
                               class="w-full px-4 py-3 border-2 rounded-lg transition focus:outline-none pr-12"
                               style="border-color: #89ab98;"
                               onfocus="this.style.borderColor='#4e4483'; this.style.boxShadow='0 0 0 3px rgba(78,68,131,0.1)'"
                               onblur="this.style.borderColor='#89ab98'; this.style.boxShadow='none'"
                               placeholder="Mínimo 6 caracteres"
                               oninput="verificarForca(this.value)">
                        <button type="button" onclick="toggleSenha('senha', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="flex gap-1">
                            <div id="forca1" class="h-1 flex-1 rounded-full bg-gray-200 transition-colors"></div>
                            <div id="forca2" class="h-1 flex-1 rounded-full bg-gray-200 transition-colors"></div>
                            <div id="forca3" class="h-1 flex-1 rounded-full bg-gray-200 transition-colors"></div>
                            <div id="forca4" class="h-1 flex-1 rounded-full bg-gray-200 transition-colors"></div>
                        </div>
                        <p id="forcaTexto" class="text-xs mt-1 text-gray-400"></p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" style="color: #4e4483;">Confirmar Nova Senha</label>
                    <div class="relative">
                        <input type="password" name="confirmar_senha" id="confirmar_senha" required minlength="6"
                               class="w-full px-4 py-3 border-2 rounded-lg transition focus:outline-none pr-12"
                               style="border-color: #89ab98;"
                               onfocus="this.style.borderColor='#4e4483'; this.style.boxShadow='0 0 0 3px rgba(78,68,131,0.1)'"
                               onblur="this.style.borderColor='#89ab98'; this.style.boxShadow='none'"
                               placeholder="Digite a senha novamente"
                               oninput="verificarConfirmacao()">
                        <button type="button" onclick="toggleSenha('confirmar_senha', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <p id="confirmacaoTexto" class="text-xs mt-1"></p>
                </div>

                <button type="submit" id="btnSubmit"
                    class="w-full text-white font-semibold py-3 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg"
                    style="background: linear-gradient(90deg, #4e4483 0%, #6e9fcb 100%);">
                    Redefinir Senha
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
    function toggleSenha(id, btn) {
        const input = document.getElementById(id);
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>';
        } else {
            input.type = 'password';
            btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
        }
    }

    function verificarForca(senha) {
        let forca = 0;
        if (senha.length >= 6) forca++;
        if (senha.length >= 10) forca++;
        if (/[A-Z]/.test(senha) && /[a-z]/.test(senha)) forca++;
        if (/[0-9]/.test(senha) && /[^A-Za-z0-9]/.test(senha)) forca++;

        const cores = ['#ef4444', '#e79048', '#6e9fcb', '#89ab98'];
        const textos = ['Fraca', 'Razoável', 'Boa', 'Forte'];

        for (let i = 1; i <= 4; i++) {
            document.getElementById('forca' + i).style.backgroundColor = i <= forca ? cores[forca - 1] : '#e5e7eb';
        }
        document.getElementById('forcaTexto').textContent = forca > 0 ? textos[forca - 1] : '';
        document.getElementById('forcaTexto').style.color = forca > 0 ? cores[forca - 1] : '#9ca3af';

        verificarConfirmacao();
    }

    function verificarConfirmacao() {
        const senha = document.getElementById('senha').value;
        const confirmar = document.getElementById('confirmar_senha').value;
        const texto = document.getElementById('confirmacaoTexto');

        if (confirmar.length === 0) {
            texto.textContent = '';
            return;
        }

        if (senha === confirmar) {
            texto.textContent = 'Senhas coincidem';
            texto.style.color = '#89ab98';
        } else {
            texto.textContent = 'Senhas não coincidem';
            texto.style.color = '#ef4444';
        }
    }
    </script>
</body>
</html>
