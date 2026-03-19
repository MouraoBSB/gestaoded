<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-03-18
 * Descrição: API Segura para gerenciar Migrations automatizadas via PowerShell
 */

// Teste rápido para debug
$caminhoConfig = __DIR__ . '/config';
if (!file_exists($caminhoConfig . '/database.php')) {
    $arquivosNaPasta = is_dir($caminhoConfig) ? scandir($caminhoConfig) : 'Pasta config nao existe!';
    http_response_code(200);
    echo json_encode(['sucesso' => false, 'erro' => 'Server debug: database.php nao localizado em ' . $caminhoConfig, 'arquivos' => $arquivosNaPasta]);
    exit;
}
require_once $caminhoConfig . '/database.php';

// Token exclusivo para garantir segurança na execução automatizada
define('MIGRATION_TOKEN', 'CHAMADA_DED_SECURE_MIGRATE_2026');

header('Content-Type: application/json; charset=utf-8');

// Validar Token que vem no header da requisição do PowerShell
$tokenEnviado = isset($_SERVER['HTTP_X_MIGRATION_TOKEN']) ? $_SERVER['HTTP_X_MIGRATION_TOKEN'] : '';

if ($tokenEnviado !== MIGRATION_TOKEN) {
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado. Token de Migration inválido.', 'token_recebido' => $tokenEnviado]);
    exit;
}

try {
    $pdo = getConnection();
    
    // 1. Garantir que a tabela base de migrations existe no banco
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations_db (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    $pastaMigrations = __DIR__ . '/database/migrations';
    
    if (!is_dir($pastaMigrations)) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Pasta de migrations não encontrada no servidor.', 'arquivos_rodados' => []]);
        exit;
    }
    
    // 2. Coletar arquivos sql locais e colocar em ordem cronológica alfabética
    $arquivosSql = glob($pastaMigrations . '/*.sql');
    if (!$arquivosSql) {
        $arquivosSql = [];
    }
    sort($arquivosSql); 
    
    // 3. Obter migrations registradas no banco (já executadas)
    $stmt = $pdo->query("SELECT migration FROM migrations_db");
    $jaExecutadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $executadosAgora = [];
    
    // 4. Executar os que faltam (com try individual para cada arquivo)
    foreach ($arquivosSql as $arquivo) {
        $nomeArquivo = basename($arquivo);
        
        if (!in_array($nomeArquivo, $jaExecutadas)) {
            $sqlParaExecutar = file_get_contents($arquivo);
            
            // Só executa se o arquivo não estiver vazio
            if (trim($sqlParaExecutar) !== '') {
                try {
                    $pdo->exec($sqlParaExecutar);
                    
                    // Se o SQL rodou sem erro, insere na tabela de log
                    $stmtInsert = $pdo->prepare("INSERT INTO migrations_db (migration) VALUES (?)");
                    $stmtInsert->execute([$nomeArquivo]);
                    
                    $executadosAgora[] = $nomeArquivo;
                } catch (PDOException $ex) {
                    throw new Exception("Falha ao rodar o arquivo '$nomeArquivo': " . $ex->getMessage());
                }
            }
        }
    }
    
    // 5. Devolver resposta JSON de sucesso
    echo json_encode([
        'sucesso' => true, 
        'mensagem' => count($executadosAgora) > 0 ? "Migrations aplicadas com sucesso." : "O banco já está totalmente atualizado (Nenhuma migration nova).", 
        'arquivos_rodados' => $executadosAgora
    ]);
    
} catch (Throwable $e) { // Captura Error e Exception
    http_response_code(200); // Força 200 pro powershell conseguir ler o body JSON
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()]);
}
?>
