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
    <title><?= $pageTitle ?? 'Gestão de Cursos - CEMA' ?></title>
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
                    <a href="/index.php" class="flex items-center space-x-3 hover:opacity-80 transition">
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
                            <?php if ($tipoUsuario === 'gestor'): ?>
                                <a href="/gestor/instrutores.php" class="block px-4 py-2 text-sm text-gray-700 transition" style="hover:background-color: #4e4483; hover:color: white;">
                                    <span class="font-semibold">👥 Todos os Usuários</span>
                                </a>
                                <a href="/gestor/usuarios.php" class="block px-4 py-2 text-sm text-gray-700 transition" style="hover:background-color: #6e9fcb; hover:color: white;">
                                    <span class="font-semibold">🔐 Gerenciar Usuários</span>
                                </a>
                                <a href="/gestor/configuracoes.php" class="block px-4 py-2 text-sm text-gray-700 transition" style="hover:background-color: #89ab98; hover:color: white;">
                                    <span class="font-semibold">⚙️ Configurações SMTP</span>
                                </a>
                                <a href="/gestor/template_email.php" class="block px-4 py-2 text-sm text-gray-700 transition" style="hover:background-color: #e79048; hover:color: white;">
                                    <span class="font-semibold">📧 Template de Email</span>
                                </a>
                                <hr class="my-2">
                            <?php endif; ?>
                            <a href="/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition font-semibold">
                                🚪 Sair
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
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
