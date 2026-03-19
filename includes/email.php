<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 20:43:00
 */

function getSmtpResponse($socket) {
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') {
            break;
        }
    }
    return $response;
}

function smtpCommand($socket, $command, $expectedCode = null, $errorMsg = '') {
    fputs($socket, $command . "\r\n");
    $response = getSmtpResponse($socket);
    if ($expectedCode !== null && substr($response, 0, 3) != (string)$expectedCode) {
        $msg = $errorMsg ?: "Erro SMTP no comando: {$command}";
        throw new Exception("{$msg} - Resposta: " . trim($response));
    }
    return $response;
}

function dotStuff($text) {
    return preg_replace('/^\./m', '..', $text);
}

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

    $host = trim($configs['smtp_host'] ?? '');
    $port = trim($configs['smtp_port'] ?? '587');
    $usuario = trim($configs['smtp_usuario'] ?? '');
    $senha = trim($configs['smtp_senha'] ?? '');
    $remetente = trim($configs['smtp_de_email'] ?? $usuario);
    $nomeRemetente = trim($configs['smtp_de_nome'] ?? 'Sistema de Chamada');
    $isHtml = ($configs['smtp_html'] ?? '1') === '1';
    $seguranca = trim($configs['smtp_seguranca'] ?? 'tls');

    if (empty($senha)) {
        throw new Exception('Senha SMTP não configurada. Configure em Configurações SMTP.');
    }

    $ehloHost = gethostname() ?: 'localhost';

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = stream_socket_client(
        ($seguranca === 'ssl' ? "ssl://{$host}:{$port}" : "{$host}:{$port}"),
        $errno,
        $errstr,
        30,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$socket) {
        throw new Exception("Não foi possível conectar ao servidor SMTP: {$errstr} ({$errno})");
    }

    stream_set_timeout($socket, 30);

    $banner = getSmtpResponse($socket);
    if (substr($banner, 0, 3) != '220') {
        fclose($socket);
        throw new Exception("Servidor SMTP não respondeu corretamente: " . trim($banner));
    }

    smtpCommand($socket, "EHLO {$ehloHost}", 250, "Falha no EHLO");

    if ($seguranca === 'tls') {
        smtpCommand($socket, "STARTTLS", 220, "Falha ao iniciar TLS");
        $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }
        if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
            fclose($socket);
            throw new Exception("Falha ao habilitar criptografia TLS");
        }
        smtpCommand($socket, "EHLO {$ehloHost}", 250, "Falha no EHLO após STARTTLS");
    }

    smtpCommand($socket, "AUTH LOGIN", 334, "Servidor não suporta AUTH LOGIN");
    smtpCommand($socket, base64_encode($usuario), 334, "Falha ao enviar usuário SMTP");

    $resposta = smtpCommand($socket, base64_encode($senha), null);
    if (substr($resposta, 0, 3) != '235') {
        $erro = "Falha na autenticação SMTP. ";
        $erro .= "Usuário: " . substr($usuario, 0, 3) . "***@" . substr(strrchr($usuario, "@"), 1) . " ";
        $erro .= "Resposta do servidor: " . trim($resposta);
        fclose($socket);
        throw new Exception($erro);
    }

    smtpCommand($socket, "MAIL FROM: <{$remetente}>", 250, "Remetente rejeitado pelo servidor");
    smtpCommand($socket, "RCPT TO: <{$destinatario}>", 250, "Destinatário rejeitado pelo servidor");
    smtpCommand($socket, "DATA", 354, "Servidor não aceitou comando DATA");

    $boundary = md5(uniqid(time(), true));
    $messageId = '<' . uniqid('msg_', true) . '@' . ($ehloHost !== 'localhost' ? $ehloHost : explode('@', $remetente)[1] ?? 'localhost') . '>';

    $corpo = "Message-ID: {$messageId}\r\n";
    $corpo .= "Date: " . date('r') . "\r\n";
    $corpo .= "Subject: =?UTF-8?B?" . base64_encode($assunto) . "?=\r\n";
    $corpo .= "From: =?UTF-8?B?" . base64_encode($nomeRemetente) . "?= <{$remetente}>\r\n";
    $corpo .= "To: =?UTF-8?B?" . base64_encode($nomeDestinatario) . "?= <{$destinatario}>\r\n";
    $corpo .= "Reply-To: {$remetente}\r\n";
    $corpo .= "MIME-Version: 1.0\r\n";
    $corpo .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $corpo .= "\r\n";

    $textoPlain = dotStuff($mensagemTexto ?: strip_tags($mensagemHtml));
    $corpo .= "--{$boundary}\r\n";
    $corpo .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $corpo .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $corpo .= quoted_printable_encode($textoPlain);
    $corpo .= "\r\n\r\n";

    if ($isHtml) {
        $htmlContent = dotStuff($mensagemHtml);
        $corpo .= "--{$boundary}\r\n";
        $corpo .= "Content-Type: text/html; charset=UTF-8\r\n";
        $corpo .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $corpo .= quoted_printable_encode($htmlContent);
        $corpo .= "\r\n\r\n";
    }

    $corpo .= "--{$boundary}--\r\n";
    $corpo .= ".\r\n";

    fputs($socket, $corpo);
    $resposta = getSmtpResponse($socket);

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    if (substr($resposta, 0, 3) != '250') {
        throw new Exception("Erro ao enviar email: " . trim($resposta));
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
