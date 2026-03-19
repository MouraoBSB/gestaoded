<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:02:00
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

requireLogin();
requireRole(['gestor']);

$pdo = getConnection();
$pageTitle = 'Configurações SMTP';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enviar_teste'])) {
        $emailTeste = $_POST['email_teste'] ?? '';
        
        if (empty($emailTeste)) {
            setFlashMessage('Digite um email para enviar o teste!', 'error');
        } else {
            try {
                $configs = [
                    'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                    'smtp_port' => trim($_POST['smtp_port'] ?? '587'),
                    'smtp_usuario' => trim($_POST['smtp_usuario'] ?? ''),
                    'smtp_senha' => trim($_POST['smtp_senha'] ?? ''),
                    'smtp_de_email' => trim($_POST['smtp_de_email'] ?? ''),
                    'smtp_de_nome' => trim($_POST['smtp_de_nome'] ?? 'Sistema de Chamada'),
                    'smtp_seguranca' => trim($_POST['smtp_seguranca'] ?? 'tls'),
                    'smtp_html' => trim($_POST['smtp_html'] ?? '1'),
                ];
                
                foreach ($configs as $chave => $valor) {
                    $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
                    $stmt->execute([$chave, $valor, $valor]);
                }
                
                $mensagemHtml = obterTemplateEmail(
                    '✅ Teste de Email',
                    'Este é um email de teste do <strong>Sistema de Chamada DED</strong>.<br>Se você recebeu este email, significa que suas configurações SMTP estão funcionando corretamente!'
                );
                
                $mensagemTexto = "Teste de Email\n\nEste é um email de teste do Sistema de Chamada DED.\n\nSe você recebeu este email, significa que suas configurações SMTP estão funcionando corretamente!";
                
                enviarEmail($emailTeste, '', 'Teste de Configuração SMTP - Sistema de Chamada', $mensagemHtml, $mensagemTexto);
                
                setFlashMessage('Email de teste enviado com sucesso para ' . $emailTeste . '!', 'success');
                redirect('/gestor/configuracoes.php');
            } catch (Exception $e) {
                setFlashMessage('Erro ao enviar email de teste: ' . $e->getMessage(), 'error');
                redirect('/gestor/configuracoes.php');
            }
        }
    } else {
        $configs = [
            'smtp_host' => trim($_POST['smtp_host'] ?? ''),
            'smtp_port' => trim($_POST['smtp_port'] ?? '587'),
            'smtp_usuario' => trim($_POST['smtp_usuario'] ?? ''),
            'smtp_senha' => trim($_POST['smtp_senha'] ?? ''),
            'smtp_de_email' => trim($_POST['smtp_de_email'] ?? ''),
            'smtp_de_nome' => trim($_POST['smtp_de_nome'] ?? 'Sistema de Chamada'),
            'smtp_seguranca' => trim($_POST['smtp_seguranca'] ?? 'tls'),
            'smtp_html' => isset($_POST['smtp_html']) ? '1' : '0',
        ];
    
    try {
        foreach ($configs as $chave => $valor) {
            $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
            $stmt->execute([$chave, $valor, $valor]);
        }
        setFlashMessage('Configurações SMTP salvas com sucesso!', 'success');
        redirect('/gestor/configuracoes.php');
    } catch (Exception $e) {
        setFlashMessage('Erro ao salvar configurações: ' . $e->getMessage(), 'error');
    }
    }
}

$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'smtp_%'");
$configs = [];
foreach ($stmt->fetchAll() as $row) {
    $configs[$row['chave']] = $row['valor'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Configurações SMTP</h2>
    <p class="text-gray-600 mt-2">Configure o servidor de email para envio de reset de senha</p>
</div>

<div class="bg-white rounded-lg shadow-md p-6 max-w-3xl">
    <form method="POST">
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Servidor SMTP (Host)</label>
            <input type="text" name="smtp_host" value="<?= htmlspecialchars($configs['smtp_host'] ?? '') ?>" 
                   placeholder="smtp.gmail.com" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            <p class="text-xs text-gray-500 mt-1">Exemplo: smtp.gmail.com, smtp.office365.com</p>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Porta SMTP</label>
            <input type="number" name="smtp_port" value="<?= htmlspecialchars($configs['smtp_port'] ?? '587') ?>" 
                   class="w-full border border-gray-300 rounded-lg px-4 py-2">
            <p class="text-xs text-gray-500 mt-1">Geralmente 587 (TLS) ou 465 (SSL)</p>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Segurança</label>
            <select name="smtp_seguranca" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <option value="tls" <?= ($configs['smtp_seguranca'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                <option value="ssl" <?= ($configs['smtp_seguranca'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
            </select>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Usuário SMTP (Email)</label>
            <input type="email" name="smtp_usuario" value="<?= htmlspecialchars($configs['smtp_usuario'] ?? '') ?>" 
                   placeholder="seu-email@dominio.com" class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Senha SMTP</label>
            <div class="relative">
                <input type="password" id="smtp_senha" name="smtp_senha" value="<?= htmlspecialchars($configs['smtp_senha'] ?? '') ?>" 
                       placeholder="Senha do email" class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-12">
                <button type="button" onclick="togglePassword('smtp_senha')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                    <svg id="eye-smtp_senha" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <svg id="eye-slash-smtp_senha" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                    </svg>
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Para Gmail, use uma senha de app específica</p>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Email Remetente</label>
            <input type="email" name="smtp_de_email" value="<?= htmlspecialchars($configs['smtp_de_email'] ?? '') ?>" 
                   placeholder="noreply@dominio.com" class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Remetente</label>
            <input type="text" name="smtp_de_nome" value="<?= htmlspecialchars($configs['smtp_de_nome'] ?? 'Sistema de Chamada') ?>" 
                   class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        
        <div class="mb-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="smtp_html" value="1" <?= ($configs['smtp_html'] ?? '1') === '1' ? 'checked' : '' ?> 
                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="text-sm font-medium text-gray-700">Enviar emails em formato HTML</span>
            </label>
            <p class="text-xs text-gray-500 mt-1 ml-6">Emails HTML permitem formatação rica com cores, imagens e estilos</p>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-blue-800 mb-2">Instruções para Gmail:</h3>
            <ol class="list-decimal list-inside text-sm text-blue-700 space-y-1">
                <li>Ative a verificação em duas etapas na sua conta Google</li>
                <li>Acesse: Conta Google → Segurança → Senhas de app</li>
                <li>Crie uma senha de app para "Email"</li>
                <li>Use essa senha no campo "Senha SMTP" acima</li>
            </ol>
        </div>
        
        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition font-semibold">
                Salvar Configurações
            </button>
            <a href="/gestor/dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition font-semibold inline-block">
                Cancelar
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-md p-6 max-w-3xl mt-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Testar Configurações SMTP</h3>
    <p class="text-gray-600 mb-4">Envie um email de teste para verificar se as configurações estão corretas</p>
    
    <form method="POST">
        <input type="hidden" name="smtp_host" value="<?= htmlspecialchars($configs['smtp_host'] ?? '') ?>">
        <input type="hidden" name="smtp_port" value="<?= htmlspecialchars($configs['smtp_port'] ?? '587') ?>">
        <input type="hidden" name="smtp_usuario" value="<?= htmlspecialchars($configs['smtp_usuario'] ?? '') ?>">
        <input type="hidden" name="smtp_senha" value="<?= htmlspecialchars($configs['smtp_senha'] ?? '') ?>">
        <input type="hidden" name="smtp_de_email" value="<?= htmlspecialchars($configs['smtp_de_email'] ?? '') ?>">
        <input type="hidden" name="smtp_de_nome" value="<?= htmlspecialchars($configs['smtp_de_nome'] ?? 'Sistema de Chamada') ?>">
        <input type="hidden" name="smtp_seguranca" value="<?= htmlspecialchars($configs['smtp_seguranca'] ?? 'tls') ?>">
        <input type="hidden" name="smtp_html" value="<?= htmlspecialchars($configs['smtp_html'] ?? '1') ?>">
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Email de Destino</label>
            <input type="email" name="email_teste" placeholder="seu-email@exemplo.com" required
                   class="w-full border border-gray-300 rounded-lg px-4 py-2">
            <p class="text-xs text-gray-500 mt-1">Digite o email onde deseja receber o teste</p>
        </div>
        
        <button type="submit" name="enviar_teste" value="1" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition font-semibold">
            📧 Enviar Email de Teste
        </button>
    </form>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const eyeIcon = document.getElementById('eye-' + fieldId);
    const eyeSlashIcon = document.getElementById('eye-slash-' + fieldId);
    
    if (field.type === 'password') {
        field.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeSlashIcon.classList.remove('hidden');
    } else {
        field.type = 'password';
        eyeIcon.classList.remove('hidden');
        eyeSlashIcon.classList.add('hidden');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
