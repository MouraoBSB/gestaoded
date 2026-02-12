<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 20:20:00
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(['gestor']);

$pdo = getConnection();
$pageTitle = 'Template de Email HTML';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateHtml = $_POST['template_html'] ?? '';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES ('email_template_html', ?) ON DUPLICATE KEY UPDATE valor = ?");
        $stmt->execute([$templateHtml, $templateHtml]);
        
        setFlashMessage('Template de email salvo com sucesso!', 'success');
        redirect('/gestor/template_email.php');
    } catch (Exception $e) {
        setFlashMessage('Erro ao salvar template: ' . $e->getMessage(), 'error');
    }
}

$stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'email_template_html'");
$stmt->execute();
$templateAtual = $stmt->fetchColumn();

if (!$templateAtual) {
    $templateAtual = '<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h2 style="color: #8b5cf6;">{{TITULO}}</h2>
        <p>{{MENSAGEM}}</p>
        <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
        <p style="font-size: 12px; color: #666;">Sistema de Chamada DED</p>
    </div>
</body>
</html>';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Template de Email HTML</h2>
    <p class="text-gray-600 mt-2">Personalize o template HTML usado nos emails do sistema</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Editor de Template</h3>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Código HTML do Template</label>
                <textarea name="template_html" rows="20" class="w-full border border-gray-300 rounded-lg px-4 py-2 font-mono text-sm" required><?= htmlspecialchars($templateAtual) ?></textarea>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <h4 class="font-semibold text-blue-800 mb-2">Variáveis Disponíveis:</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li><code class="bg-blue-100 px-2 py-1 rounded">{{TITULO}}</code> - Título do email</li>
                    <li><code class="bg-blue-100 px-2 py-1 rounded">{{MENSAGEM}}</code> - Corpo da mensagem</li>
                    <li><code class="bg-blue-100 px-2 py-1 rounded">{{NOME_USUARIO}}</code> - Nome do destinatário</li>
                    <li><code class="bg-blue-100 px-2 py-1 rounded">{{LINK}}</code> - Link de ação (se houver)</li>
                    <li><code class="bg-blue-100 px-2 py-1 rounded">{{DATA}}</code> - Data atual</li>
                </ul>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition font-semibold">
                    Salvar Template
                </button>
                <button type="button" onclick="resetarTemplate()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition font-semibold">
                    Restaurar Padrão
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Preview do Template</h3>
        
        <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
            <iframe id="preview" style="width: 100%; height: 600px; border: none; background: white;"></iframe>
        </div>
        
        <button type="button" onclick="atualizarPreview()" class="mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
            🔄 Atualizar Preview
        </button>
    </div>
</div>

<script>
function atualizarPreview() {
    const template = document.querySelector('textarea[name="template_html"]').value;
    const preview = document.getElementById('preview');
    
    const htmlComDados = template
        .replace(/{{TITULO}}/g, 'Exemplo de Título')
        .replace(/{{MENSAGEM}}/g, 'Esta é uma mensagem de exemplo para visualizar como ficará o email.')
        .replace(/{{NOME_USUARIO}}/g, 'João Silva')
        .replace(/{{LINK}}/g, 'https://exemplo.com')
        .replace(/{{DATA}}/g, new Date().toLocaleDateString('pt-BR'));
    
    preview.srcdoc = htmlComDados;
}

function resetarTemplate() {
    if (confirm('Deseja restaurar o template padrão? Suas alterações serão perdidas.')) {
        const templatePadrao = `<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h2 style="color: #8b5cf6;">{{TITULO}}</h2>
        <p>{{MENSAGEM}}</p>
        <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
        <p style="font-size: 12px; color: #666;">Sistema de Chamada DED</p>
    </div>
</body>
</html>`;
        
        document.querySelector('textarea[name="template_html"]').value = templatePadrao;
        atualizarPreview();
    }
}

atualizarPreview();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
