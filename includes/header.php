<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 */

$nomeUsuario = getUserName();
$tipoUsuario = getUserType();
$tipoLabel = [
    'gestor' => 'Gestor',
    'diretor' => 'Diretor',
    'instrutor' => 'Instrutor'
][$tipoUsuario] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
    $seoConfigs = getSeoConfigs();
    $seoSiteTitle = $seoConfigs['seo_titulo_site'] ?? 'Gestão de Cursos - CEMA';
    $finalTitle = isset($pageTitle) ? $pageTitle . ' - ' . $seoSiteTitle : $seoSiteTitle;
?>
    <title><?= htmlspecialchars($finalTitle) ?></title>
<?= renderSeoMeta($pageTitle ?? null) ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --cema-orange: #e79048;
            --cema-green: #89ab98;
            --cema-blue: #6e9fcb;
            --cema-purple: #4e4483;
            --cema-beige: #f3eddd;
        }
    </style>
</head>
<body class="min-h-screen" style="background: linear-gradient(135deg, #f3eddd 0%, #ffffff 100%);">
    <nav class="shadow-lg" style="background: linear-gradient(90deg, #4e4483 0%, #6e9fcb 100%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/gestao.php" class="flex items-center space-x-3 hover:opacity-80 transition">
                        <img src="/assets/images/logo-cema.png" alt="CEMA" class="h-12">
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-white hover:bg-white hover:bg-opacity-20 px-3 py-2 rounded-lg transition">
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-medium"><?= htmlspecialchars($nomeUsuario) ?></p>
                                <p class="text-xs opacity-90"><?= $tipoLabel ?></p>
                            </div>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-2 z-50">
                            <a href="/meu_perfil.php" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="font-semibold">Meu Perfil</span>
                            </a>
                            <?php if ($tipoUsuario === 'gestor'): ?>
                                <a href="/gestor/configuracoes_geral.php" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="font-semibold">Configurações</span>
                                </a>
                            <?php endif; ?>
                            <hr class="my-2">
                            <a href="/logout.php" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span class="font-semibold">Sair</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <?php
    $flash = getFlashMessage();
    if ($flash):
        $bgColor = $flash['tipo'] === 'success' ? 'border-2' : 'border-2';
        $bgStyle = $flash['tipo'] === 'success' ? 'background-color: #89ab98; border-color: #4e4483; color: white;' : 'background-color: #e79048; border-color: #4e4483; color: white;';
        $icon = $flash['tipo'] === 'success' ? '✓' : '✗';
    ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="<?= $bgColor ?> px-4 py-3 rounded-lg flex items-center gap-2 shadow-md" style="<?= $bgStyle ?>">
                <span class="font-bold text-lg"><?= $icon ?></span>
                <span class="font-medium"><?= htmlspecialchars($flash['mensagem']) ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['senha_padrao']) && basename($_SERVER['SCRIPT_NAME']) !== 'meu_perfil.php'): ?>
    <div id="modalSenhaPadrao" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50" x-data="{ show: true }" x-show="show" x-transition>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8 text-center">
            <div class="mx-auto mb-4 w-16 h-16 rounded-full flex items-center justify-center" style="background-color: #e79048;">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold mb-2" style="color: #4e4483;">Sua senha precisa ser trocada</h2>
            <p class="text-gray-600 mb-6">Por segurança, você está usando uma senha temporária. Por favor, altere sua senha o mais rápido possível.</p>
            <div class="flex flex-col gap-3">
                <a href="https://portal.cursoscema.com.br/meu_perfil.php"
                   class="w-full text-white font-semibold py-3 rounded-lg transition duration-200 transform hover:scale-105 shadow-lg inline-block"
                   style="background: linear-gradient(90deg, #4e4483 0%, #6e9fcb 100%);">
                    Trocar Senha Agora
                </a>
                <button @click="show = false" class="text-sm font-medium hover:underline" style="color: #e79048;">
                    Lembrar mais tarde
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
