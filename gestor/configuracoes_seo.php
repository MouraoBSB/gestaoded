<?php
/**
 * Autor: Thiago Mourao
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-03-18
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(['gestor']);

$pdo = getConnection();
$pageTitle = 'Configuracoes SEO e Imagens';

// Processar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [
        'seo_titulo_site'    => trim($_POST['seo_titulo_site'] ?? ''),
        'seo_descricao'      => trim($_POST['seo_descricao'] ?? ''),
        'seo_palavras_chave' => trim($_POST['seo_palavras_chave'] ?? ''),
        'seo_og_titulo'      => trim($_POST['seo_og_titulo'] ?? ''),
        'seo_og_descricao'   => trim($_POST['seo_og_descricao'] ?? ''),
        'seo_url_site'       => trim($_POST['seo_url_site'] ?? ''),
        'seo_cor_tema'       => trim($_POST['seo_cor_tema'] ?? '#4e4483'),
        'seo_google_analytics' => trim($_POST['seo_google_analytics'] ?? ''),
    ];

    // Upload de imagens
    $imagensUpload = [
        'seo_favicon'    => 'favicon',
        'seo_og_imagem'  => 'og_imagem',
        'seo_logo_site'  => 'logo_site',
        'seo_logo_email' => 'logo_email',
    ];

    foreach ($imagensUpload as $chave => $campo) {
        if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
            $arquivo = uploadFoto($_FILES[$campo], 'seo');
            if ($arquivo) {
                // Remover arquivo antigo se existir
                $stmtOld = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
                $stmtOld->execute([$chave]);
                $oldVal = $stmtOld->fetchColumn();
                if ($oldVal && file_exists(__DIR__ . '/../assets/uploads/' . $oldVal)) {
                    // Manter prefixo seo/ no path do upload, remover arquivo antigo
                    $oldPath = __DIR__ . '/../assets/uploads/' . $oldVal;
                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $campos[$chave] = $arquivo;
            }
        }
    }

    // Remover imagens se solicitado
    foreach (['seo_favicon', 'seo_og_imagem', 'seo_logo_site', 'seo_logo_email'] as $chave) {
        if (isset($_POST['remover_' . $chave])) {
            $stmtOld = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
            $stmtOld->execute([$chave]);
            $oldVal = $stmtOld->fetchColumn();
            if ($oldVal) {
                $oldPath = __DIR__ . '/../assets/uploads/' . $oldVal;
                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }
            $campos[$chave] = '';
        }
    }

    try {
        foreach ($campos as $chave => $valor) {
            $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
            $stmt->execute([$chave, $valor, $valor]);
        }
        setFlashMessage('Configuracoes SEO salvas com sucesso!', 'success');
        redirect('/gestor/configuracoes_seo.php');
    } catch (Exception $e) {
        setFlashMessage('Erro ao salvar configuracoes: ' . $e->getMessage(), 'error');
    }
}

// Carregar configuracoes atuais
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'seo_%'");
$configs = [];
foreach ($stmt->fetchAll() as $row) {
    $configs[$row['chave']] = $row['valor'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="/gestor/configuracoes_geral.php" class="text-gray-500 hover:text-gray-700 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <h2 class="text-3xl font-bold text-gray-800">SEO e Imagens do Site</h2>
    </div>
    <p class="text-gray-600 mt-2">Configure as meta tags para motores de busca, favicon e imagens de divulgacao</p>
</div>

<form method="POST" enctype="multipart/form-data" class="space-y-8 max-w-4xl">

    <!-- Informacoes Basicas SEO -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Meta Tags - SEO
        </h3>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Titulo do Site</label>
                <input type="text" name="seo_titulo_site" value="<?= htmlspecialchars($configs['seo_titulo_site'] ?? '') ?>"
                       placeholder="Cursos CEMA - Centro Espirita Maria Madalena"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                <p class="text-xs text-gray-500 mt-1">Aparece na aba do navegador e nos resultados de busca (ideal: 50-60 caracteres)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descricao do Site</label>
                <textarea name="seo_descricao" rows="3"
                          placeholder="Cursos gratuitos de estudo espirita oferecidos pelo CEMA..."
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"><?= htmlspecialchars($configs['seo_descricao'] ?? '') ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Descricao exibida nos resultados do Google (ideal: 150-160 caracteres)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Palavras-chave</label>
                <input type="text" name="seo_palavras_chave" value="<?= htmlspecialchars($configs['seo_palavras_chave'] ?? '') ?>"
                       placeholder="cursos espiritas, espiritismo, CEMA, estudo espirita, cursos gratuitos"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                <p class="text-xs text-gray-500 mt-1">Separe as palavras-chave por virgula</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL do Site</label>
                <input type="url" name="seo_url_site" value="<?= htmlspecialchars($configs['seo_url_site'] ?? '') ?>"
                       placeholder="https://portal.cursoscema.com.br"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                <p class="text-xs text-gray-500 mt-1">URL completa do site (usado nas meta tags Open Graph)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor do Tema</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="seo_cor_tema" value="<?= htmlspecialchars($configs['seo_cor_tema'] ?? '#4e4483') ?>"
                           class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                    <span class="text-sm text-gray-500">Cor exibida na barra do navegador mobile</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Google Analytics ID</label>
                <input type="text" name="seo_google_analytics" value="<?= htmlspecialchars($configs['seo_google_analytics'] ?? '') ?>"
                       placeholder="G-XXXXXXXXXX"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                <p class="text-xs text-gray-500 mt-1">ID de medicao do Google Analytics 4 (opcional)</p>
            </div>
        </div>
    </div>

    <!-- Open Graph (Redes Sociais) -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
            </svg>
            Open Graph - Compartilhamento em Redes Sociais
        </h3>
        <p class="text-gray-500 text-sm mb-4">Estas informacoes sao exibidas quando alguem compartilha o link do site no WhatsApp, Facebook, Instagram, etc.</p>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Titulo para Compartilhamento</label>
                <input type="text" name="seo_og_titulo" value="<?= htmlspecialchars($configs['seo_og_titulo'] ?? '') ?>"
                       placeholder="Cursos Gratuitos - CEMA"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Se vazio, usa o titulo do site</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descricao para Compartilhamento</label>
                <textarea name="seo_og_descricao" rows="2"
                          placeholder="Inscreva-se nos cursos gratuitos do CEMA..."
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($configs['seo_og_descricao'] ?? '') ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Se vazio, usa a descricao do site</p>
            </div>

            <!-- Imagem OG -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Imagem de Compartilhamento (OG Image)</label>
                <?php if (!empty($configs['seo_og_imagem'])): ?>
                    <div class="mb-3 p-3 bg-gray-50 rounded-lg flex items-center gap-4">
                        <img src="/assets/uploads/<?= htmlspecialchars($configs['seo_og_imagem']) ?>" alt="OG Image" class="h-20 rounded shadow">
                        <div>
                            <p class="text-sm text-green-600 font-medium">Imagem atual configurada</p>
                            <label class="flex items-center gap-2 mt-1 cursor-pointer">
                                <input type="checkbox" name="remover_seo_og_imagem" value="1" class="w-4 h-4 text-red-600 rounded">
                                <span class="text-sm text-red-600">Remover imagem</span>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="og_imagem" accept="image/*"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:font-semibold hover:file:bg-blue-100">
                <p class="text-xs text-gray-500 mt-1">Tamanho recomendado: 1200x630 pixels (JPG ou PNG)</p>
            </div>
        </div>
    </div>

    <!-- Favicon e Logos -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Favicon e Logos
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Favicon -->
            <div class="p-4 border border-gray-200 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                <p class="text-xs text-gray-500 mb-3">Icone que aparece na aba do navegador</p>
                <?php if (!empty($configs['seo_favicon'])): ?>
                    <div class="mb-3 p-3 bg-gray-50 rounded-lg flex items-center gap-3">
                        <img src="/assets/uploads/<?= htmlspecialchars($configs['seo_favicon']) ?>" alt="Favicon" class="h-10 w-10 rounded shadow">
                        <div>
                            <p class="text-xs text-green-600 font-medium">Favicon configurado</p>
                            <label class="flex items-center gap-2 mt-1 cursor-pointer">
                                <input type="checkbox" name="remover_seo_favicon" value="1" class="w-4 h-4 text-red-600 rounded">
                                <span class="text-xs text-red-600">Remover</span>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="favicon" accept="image/png,image/x-icon,image/svg+xml,.ico"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-green-50 file:text-green-700 file:font-semibold hover:file:bg-green-100">
                <p class="text-xs text-gray-500 mt-1">Formatos: ICO, PNG ou SVG (32x32 ou 64x64 px)</p>
            </div>

            <!-- Logo do Site -->
            <div class="p-4 border border-gray-200 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">Logo do Site</label>
                <p class="text-xs text-gray-500 mb-3">Logo principal exibido no cabecalho e paginas publicas</p>
                <?php if (!empty($configs['seo_logo_site'])): ?>
                    <div class="mb-3 p-3 bg-gray-50 rounded-lg flex items-center gap-3">
                        <img src="/assets/uploads/<?= htmlspecialchars($configs['seo_logo_site']) ?>" alt="Logo" class="h-10 rounded shadow">
                        <div>
                            <p class="text-xs text-green-600 font-medium">Logo configurado</p>
                            <label class="flex items-center gap-2 mt-1 cursor-pointer">
                                <input type="checkbox" name="remover_seo_logo_site" value="1" class="w-4 h-4 text-red-600 rounded">
                                <span class="text-xs text-red-600">Remover</span>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="logo_site" accept="image/*"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-green-50 file:text-green-700 file:font-semibold hover:file:bg-green-100">
                <p class="text-xs text-gray-500 mt-1">Formatos: PNG, JPG, SVG ou WebP</p>
            </div>

            <!-- Logo para Emails -->
            <div class="p-4 border border-gray-200 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">Logo para Emails</label>
                <p class="text-xs text-gray-500 mb-3">Logo usado no cabecalho dos emails enviados</p>
                <?php if (!empty($configs['seo_logo_email'])): ?>
                    <div class="mb-3 p-3 bg-gray-50 rounded-lg flex items-center gap-3">
                        <img src="/assets/uploads/<?= htmlspecialchars($configs['seo_logo_email']) ?>" alt="Logo Email" class="h-10 rounded shadow">
                        <div>
                            <p class="text-xs text-green-600 font-medium">Logo email configurado</p>
                            <label class="flex items-center gap-2 mt-1 cursor-pointer">
                                <input type="checkbox" name="remover_seo_logo_email" value="1" class="w-4 h-4 text-red-600 rounded">
                                <span class="text-xs text-red-600">Remover</span>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="logo_email" accept="image/*"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-green-50 file:text-green-700 file:font-semibold hover:file:bg-green-100">
                <p class="text-xs text-gray-500 mt-1">Formatos: PNG ou JPG (recomendado: 200px de largura)</p>
            </div>
        </div>
    </div>

    <!-- Preview SEO -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            Preview - Resultado no Google
        </h3>

        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="max-w-xl">
                <p class="text-sm text-green-700 mb-1" id="preview-url">
                    <?= htmlspecialchars($configs['seo_url_site'] ?? 'https://seusite.com.br') ?>
                </p>
                <h4 class="text-xl text-blue-800 hover:underline cursor-pointer mb-1" id="preview-title">
                    <?= htmlspecialchars($configs['seo_titulo_site'] ?? 'Titulo do seu site') ?>
                </h4>
                <p class="text-sm text-gray-600" id="preview-description">
                    <?= htmlspecialchars($configs['seo_descricao'] ?? 'Descricao do seu site aparecera aqui nos resultados de busca...') ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Botoes -->
    <div class="flex gap-3">
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg transition font-semibold shadow-md">
            Salvar Configuracoes SEO
        </button>
        <a href="/gestor/configuracoes_geral.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition font-semibold inline-block">
            Cancelar
        </a>
    </div>
</form>

<script>
// Preview em tempo real
document.querySelector('input[name="seo_titulo_site"]').addEventListener('input', function() {
    document.getElementById('preview-title').textContent = this.value || 'Titulo do seu site';
});
document.querySelector('textarea[name="seo_descricao"]').addEventListener('input', function() {
    document.getElementById('preview-description').textContent = this.value || 'Descricao do seu site aparecera aqui nos resultados de busca...';
});
document.querySelector('input[name="seo_url_site"]').addEventListener('input', function() {
    document.getElementById('preview-url').textContent = this.value || 'https://seusite.com.br';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
