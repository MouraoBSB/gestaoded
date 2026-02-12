<?php
/**
 * Arquivo de exemplo de configuração do banco de dados
 * 
 * INSTRUÇÕES:
 * 1. Copie este arquivo para database.php
 * 2. Preencha com suas credenciais do banco de dados
 * 3. NUNCA commite o arquivo database.php (está no .gitignore)
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'chamada_ded');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_CHARSET', 'utf8mb4');

function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Erro de conexão: " . $e->getMessage());
    }
}
