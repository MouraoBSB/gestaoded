<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-03-03 00:10:00
 */

require_once __DIR__ . '/config/database.php';

$pdo = getConnection();

$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'smtp_%'");
$configs = [];
foreach ($stmt->fetchAll() as $row) {
    $configs[$row['chave']] = $row['valor'];
}

$host = $configs['smtp_host'] ?? '';
$port = $configs['smtp_port'] ?? '587';
$usuario = $configs['smtp_usuario'] ?? '';
$senha = $configs['smtp_senha'] ?? '';
$seguranca = $configs['smtp_seguranca'] ?? 'tls';

echo "<h2>Teste de Conexão SMTP</h2>";
echo "<pre>";
echo "Host: {$host}\n";
echo "Porta: {$port}\n";
echo "Usuário: {$usuario}\n";
echo "Senha: " . str_repeat('*', strlen($senha)) . " (" . strlen($senha) . " caracteres)\n";
echo "Segurança: {$seguranca}\n";
echo "\n--- INICIANDO TESTE ---\n\n";

try {
    // Passo 1: Conectar
    echo "1. Conectando ao servidor...\n";
    $socket = @fsockopen($seguranca === 'ssl' ? "ssl://{$host}" : $host, $port, $errno, $errstr, 30);
    
    if (!$socket) {
        throw new Exception("Erro ao conectar: {$errstr} ({$errno})");
    }
    echo "   ✓ Conectado com sucesso\n\n";
    
    // Passo 2: Receber saudação
    echo "2. Recebendo saudação do servidor...\n";
    $resposta = fgets($socket);
    echo "   Resposta: " . trim($resposta) . "\n";
    if (substr($resposta, 0, 3) != '220') {
        throw new Exception("Erro na saudação");
    }
    echo "   ✓ Saudação OK\n\n";
    
    // Passo 3: EHLO
    echo "3. Enviando EHLO...\n";
    fputs($socket, "EHLO {$host}\r\n");
    do {
        $linha = fgets($socket);
        if ($linha === false) break;
        echo "   Resposta: " . trim($linha) . "\n";
    } while (substr($linha, 3, 1) !== ' ');
    echo "   ✓ EHLO OK (todas as linhas consumidas)\n\n";
    
    // Passo 4: STARTTLS (se TLS)
    if ($seguranca === 'tls') {
        echo "4. Iniciando TLS...\n";
        fputs($socket, "STARTTLS\r\n");
        $resposta = fgets($socket);
        echo "   Resposta: " . trim($resposta) . "\n";
        if (substr($resposta, 0, 3) != '220') {
            throw new Exception("Erro ao iniciar TLS");
        }
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        fputs($socket, "EHLO {$host}\r\n");
        do {
            $linha = fgets($socket);
            if ($linha === false) break;
            echo "   Resposta: " . trim($linha) . "\n";
        } while (substr($linha, 3, 1) !== ' ');
        echo "   ✓ TLS OK (todas as linhas consumidas)\n\n";
    } else {
        echo "4. SSL já ativo (pulando STARTTLS)\n\n";
    }
    
    // Passo 5: AUTH LOGIN
    echo "5. Iniciando autenticação...\n";
    fputs($socket, "AUTH LOGIN\r\n");
    $resposta = fgets($socket);
    echo "   Resposta: " . trim($resposta) . "\n";
    echo "   ✓ AUTH LOGIN aceito\n\n";
    
    // Passo 6: Enviar usuário
    echo "6. Enviando usuário...\n";
    $usuarioBase64 = base64_encode($usuario);
    echo "   Usuário (base64): {$usuarioBase64}\n";
    fputs($socket, $usuarioBase64 . "\r\n");
    $resposta = fgets($socket);
    echo "   Resposta: " . trim($resposta) . "\n";
    echo "   ✓ Usuário aceito\n\n";
    
    // Passo 7: Enviar senha
    echo "7. Enviando senha...\n";
    $senhaBase64 = base64_encode($senha);
    echo "   Senha (base64): " . substr($senhaBase64, 0, 10) . "...\n";
    fputs($socket, $senhaBase64 . "\r\n");
    $resposta = fgets($socket);
    echo "   Resposta: " . trim($resposta) . "\n";
    
    if (substr($resposta, 0, 3) != '235') {
        throw new Exception("FALHA NA AUTENTICAÇÃO! Código: " . substr($resposta, 0, 3));
    }
    echo "   ✓ AUTENTICAÇÃO SUCESSO!\n\n";
    
    // Fechar conexão
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    echo "\n=== TESTE COMPLETO: SUCESSO ===\n";
    echo "Todas as etapas foram concluídas com sucesso!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='/gestor/configuracoes.php'>← Voltar para Configurações</a>";
?>
