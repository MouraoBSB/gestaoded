<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 20:43:00
 */

function enviarEmail($destinatario, $nomeDestinatario, $assunto, $mensagemHtml, $mensagemTexto = '') {
    $pdo = getConnection();
    
    $stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'smtp_%'");
    $configs = [];
    foreach ($stmt->fetchAll() as $row) {
        $configs[$row['chave']] = $row['valor'];
    }
    
    if (empty($configs['smtp_host']) || empty($configs['smtp_usuario'])) {
        throw new Exception('Configurações SMTP não encontradas. Configure em Configurações SMTP.');
    }
    
    $host = $configs['smtp_host'];
    $port = $configs['smtp_port'] ?? 587;
    $usuario = $configs['smtp_usuario'];
    $senha = $configs['smtp_senha'];
    $remetente = $configs['smtp_de_email'] ?? $usuario;
    $nomeRemetente = $configs['smtp_de_nome'] ?? 'Sistema de Chamada';
    $isHtml = ($configs['smtp_html'] ?? '1') === '1';
    $seguranca = $configs['smtp_seguranca'] ?? 'tls';
    
    $socket = @fsockopen($seguranca === 'ssl' ? "ssl://{$host}" : $host, $port, $errno, $errstr, 30);
    
    if (!$socket) {
        throw new Exception("Não foi possível conectar ao servidor SMTP: {$errstr} ({$errno})");
    }
    
    $resposta = fgets($socket);
    if (substr($resposta, 0, 3) != '220') {
        throw new Exception("Erro na conexão SMTP: {$resposta}");
    }
    
    fputs($socket, "EHLO {$host}\r\n");
    $resposta = fgets($socket);
    
    if ($seguranca === 'tls') {
        fputs($socket, "STARTTLS\r\n");
        $resposta = fgets($socket);
        if (substr($resposta, 0, 3) != '220') {
            throw new Exception("Erro ao iniciar TLS: {$resposta}");
        }
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        fputs($socket, "EHLO {$host}\r\n");
        $resposta = fgets($socket);
    }
    
    fputs($socket, "AUTH LOGIN\r\n");
    fgets($socket);
    
    fputs($socket, base64_encode($usuario) . "\r\n");
    fgets($socket);
    
    fputs($socket, base64_encode($senha) . "\r\n");
    $resposta = fgets($socket);
    if (substr($resposta, 0, 3) != '235') {
        throw new Exception("Falha na autenticação SMTP. Verifique usuário e senha.");
    }
    
    fputs($socket, "MAIL FROM: <{$remetente}>\r\n");
    fgets($socket);
    
    fputs($socket, "RCPT TO: <{$destinatario}>\r\n");
    fgets($socket);
    
    fputs($socket, "DATA\r\n");
    fgets($socket);
    
    $boundary = md5(time());
    $headers = "From: {$nomeRemetente} <{$remetente}>\r\n";
    $headers .= "Reply-To: {$remetente}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    
    $corpo = "Subject: {$assunto}\r\n";
    $corpo .= $headers;
    $corpo .= "\r\n";
    $corpo .= "--{$boundary}\r\n";
    $corpo .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $corpo .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $corpo .= $mensagemTexto ?: strip_tags($mensagemHtml);
    $corpo .= "\r\n\r\n";
    
    if ($isHtml) {
        $corpo .= "--{$boundary}\r\n";
        $corpo .= "Content-Type: text/html; charset=UTF-8\r\n";
        $corpo .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $corpo .= $mensagemHtml;
        $corpo .= "\r\n\r\n";
    }
    
    $corpo .= "--{$boundary}--\r\n";
    $corpo .= ".\r\n";
    
    fputs($socket, $corpo);
    $resposta = fgets($socket);
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    if (substr($resposta, 0, 3) != '250') {
        throw new Exception("Erro ao enviar email: {$resposta}");
    }
    
    return true;
}

function obterTemplateEmail($titulo, $mensagem, $nomeUsuario = '', $link = '') {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'email_template_html'");
    $stmt->execute();
    $template = $stmt->fetchColumn();
    
    if ($template) {
        return str_replace(
            ['{{TITULO}}', '{{MENSAGEM}}', '{{NOME_USUARIO}}', '{{LINK}}', '{{DATA}}'],
            [$titulo, $mensagem, $nomeUsuario, $link, date('d/m/Y H:i:s')],
            $template
        );
    }
    
    return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
                <h2 style="color: #8b5cf6;">' . htmlspecialchars($titulo) . '</h2>
                <div>' . $mensagem . '</div>
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="font-size: 12px; color: #666;">Sistema de Chamada DED - ' . date('d/m/Y H:i:s') . '</p>
            </div>
        </body>
        </html>
    ';
}
