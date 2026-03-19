<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    redirect('/gestao.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $lembrar = isset($_POST['lembrar']);
    
    if (login($email, $senha, $lembrar)) {
        redirect('/gestao.php');
    } else {
        $erro = 'Email ou senha inválidos';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
    $seoConfigs = getSeoConfigs();
    $seoSiteTitle = $seoConfigs['seo_titulo_site'] ?? 'Gestão de Cursos CEMA';
?>
    <title>Login - <?= htmlspecialchars($seoSiteTitle) ?></title>
<?= renderSeoMeta('Login') ?>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4" style="background: linear-gradient(135deg, #4e4483 0%, #6e9fcb 50%, #89ab98 100%);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <img src="/assets/images/logo-cema.png" alt="CEMA" class="h-20 mx-auto mb-4">
            <h1 class="text-3xl font-bold" style="color: #4e4483;">Gestão de Cursos CEMA</h1>
            <p class="mt-2" style="color: #89ab98;">Faça login para continuar</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="border-2 px-4 py-3 rounded-lg mb-4" style="background-color: #e79048; border-color: #4e4483; color: white;">
                <strong>✗</strong> <?= $erro ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium mb-2" style="color: #4e4483;">Email</label>
                <input type="email" id="email" name="email" required
                    class="w-full px-4 py-3 border-2 rounded-lg transition" style="border-color: #89ab98; focus:border-color: #4e4483; focus:ring-2; focus:ring-color: #6e9fcb;">
            </div>
            
            <div>
                <label for="senha" class="block text-sm font-medium mb-2" style="color: #4e4483;">Senha</label>
                <input type="password" id="senha" name="senha" required
                    class="w-full px-4 py-3 border-2 rounded-lg transition" style="border-color: #89ab98; focus:border-color: #4e4483; focus:ring-2; focus:ring-color: #6e9fcb;">
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" id="lembrar" name="lembrar" value="1"
                    class="w-4 h-4 rounded border-gray-300 focus:ring-2" style="color: #4e4483; focus:ring-color: #6e9fcb;">
                <label for="lembrar" class="ml-2 text-sm font-medium" style="color: #4e4483;">
                    Lembrar-me por 30 dias
                </label>
            </div>
            
            <button type="submit"
                class="w-full text-white font-semibold py-3 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg"
                style="background: linear-gradient(90deg, #4e4483 0%, #6e9fcb 100%);">
                Entrar
            </button>
            
            <div class="text-center mt-4">
                <a href="/esqueci_senha.php" class="font-semibold hover:underline" style="color: #e79048;">
                    Esqueci minha senha
                </a>
            </div>
        </form>
    </div>
</body>
</html>
