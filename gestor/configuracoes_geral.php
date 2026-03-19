<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-03-18
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['gestor']);

$pdo = getConnection();

// Contadores
$totalUsuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1")->fetchColumn();
$totalInstrutores = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'instrutor' AND ativo = 1")->fetchColumn();
$totalGestores = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'gestor' AND ativo = 1")->fetchColumn();

// Verificar se SMTP está configurado
$smtpConfigurado = (bool)$pdo->query("SELECT COUNT(*) FROM configuracoes WHERE chave = 'smtp_host' AND valor != ''")->fetchColumn();

// Verificar se SEO está configurado
$seoConfigurado = (bool)$pdo->query("SELECT COUNT(*) FROM configuracoes WHERE chave = 'seo_titulo_site' AND valor != ''")->fetchColumn();
$faviconConfigurado = (bool)$pdo->query("SELECT COUNT(*) FROM configuracoes WHERE chave = 'seo_favicon' AND valor != ''")->fetchColumn();

$pageTitle = 'Configurações';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Configurações</h1>
    <p class="text-gray-600 mt-2">Gerencie usuários, email e configurações do sistema</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Gerenciar Usuários -->
    <a href="/gestor/usuarios.php" class="bg-white rounded-lg shadow-md hover:shadow-xl transition p-6 group">
        <div class="flex items-center gap-4 mb-4">
            <div class="bg-purple-100 p-3 rounded-full group-hover:bg-purple-200 transition">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-purple-600 transition">Gerenciar Usuários</h3>
                <p class="text-sm text-gray-500">Criar, editar e desativar usuários</p>
            </div>
        </div>
        <div class="flex gap-4 text-sm">
            <span class="bg-gray-100 px-3 py-1 rounded-full text-gray-600"><?= $totalUsuarios ?> usuários</span>
            <span class="bg-green-100 px-3 py-1 rounded-full text-green-700"><?= $totalInstrutores ?> instrutores</span>
            <span class="bg-red-100 px-3 py-1 rounded-full text-red-700"><?= $totalGestores ?> gestores</span>
        </div>
    </a>

    <!-- Configurações SMTP -->
    <a href="/gestor/configuracoes.php" class="bg-white rounded-lg shadow-md hover:shadow-xl transition p-6 group">
        <div class="flex items-center gap-4 mb-4">
            <div class="bg-blue-100 p-3 rounded-full group-hover:bg-blue-200 transition">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition">Configurações SMTP</h3>
                <p class="text-sm text-gray-500">Servidor de email e envio de teste</p>
            </div>
        </div>
        <div class="text-sm">
            <?php if ($smtpConfigurado): ?>
                <span class="bg-green-100 px-3 py-1 rounded-full text-green-700">Configurado</span>
            <?php else: ?>
                <span class="bg-yellow-100 px-3 py-1 rounded-full text-yellow-700">Não configurado</span>
            <?php endif; ?>
        </div>
    </a>

    <!-- SEO e Imagens -->
    <a href="/gestor/configuracoes_seo.php" class="bg-white rounded-lg shadow-md hover:shadow-xl transition p-6 group">
        <div class="flex items-center gap-4 mb-4">
            <div class="bg-green-100 p-3 rounded-full group-hover:bg-green-200 transition">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-green-600 transition">SEO e Imagens</h3>
                <p class="text-sm text-gray-500">Favicon, logos, meta tags e Open Graph</p>
            </div>
        </div>
        <div class="flex gap-2 text-sm">
            <?php if ($seoConfigurado): ?>
                <span class="bg-green-100 px-3 py-1 rounded-full text-green-700">SEO configurado</span>
            <?php else: ?>
                <span class="bg-yellow-100 px-3 py-1 rounded-full text-yellow-700">SEO pendente</span>
            <?php endif; ?>
            <?php if ($faviconConfigurado): ?>
                <span class="bg-green-100 px-3 py-1 rounded-full text-green-700">Favicon</span>
            <?php else: ?>
                <span class="bg-yellow-100 px-3 py-1 rounded-full text-yellow-700">Sem favicon</span>
            <?php endif; ?>
        </div>
    </a>

    <!-- Template de Email -->
    <a href="/gestor/template_email.php" class="bg-white rounded-lg shadow-md hover:shadow-xl transition p-6 group">
        <div class="flex items-center gap-4 mb-4">
            <div class="bg-orange-100 p-3 rounded-full group-hover:bg-orange-200 transition">
                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-orange-600 transition">Template de Email</h3>
                <p class="text-sm text-gray-500">Personalizar layout dos emails</p>
            </div>
        </div>
        <div class="text-sm">
            <span class="bg-gray-100 px-3 py-1 rounded-full text-gray-600">Editar template</span>
        </div>
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
